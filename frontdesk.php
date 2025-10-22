<?php
// Assumes `db.php` and session were already loaded by `dashboard.php`.
// Fetch key KPIs for the front desk view
try {
    // Rooms summary
    $roomSummaryStmt = $conn->query("SELECT 
        COUNT(*) AS total_rooms,
        SUM(CASE WHEN room_status = 'Vacant' THEN 1 ELSE 0 END) AS vacant_rooms,
        SUM(CASE WHEN room_status = 'Occupied' THEN 1 ELSE 0 END) AS occupied_rooms,
        SUM(CASE WHEN room_status = 'Maintenance' THEN 1 ELSE 0 END) AS maintenance_rooms,
        SUM(CASE WHEN room_status = 'Reserved' THEN 1 ELSE 0 END) AS reserved_rooms
    FROM rooms");
    $roomSummary = $roomSummaryStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total_rooms' => 0,
        'vacant_rooms' => 0,
        'occupied_rooms' => 0,
        'maintenance_rooms' => 0,
        'reserved_rooms' => 0,
    ];

    $totalRooms = (int)$roomSummary['total_rooms'];
    $occupiedRooms = (int)$roomSummary['occupied_rooms'];
    $reservedRooms = (int)$roomSummary['reserved_rooms'];
    $vacantRooms = (int)$roomSummary['vacant_rooms'];
    $maintenanceRooms = (int)$roomSummary['maintenance_rooms'];
    $inHouseCount = (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE reservation_status = 'Checked In'")->fetchColumn();
    $pendingResCount = (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE reservation_status = 'Pending'")->fetchColumn();
    $arrivalsToday = (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE DATE(check_in_date) = CURDATE() AND reservation_status IN ('Pending','Checked In')")->fetchColumn();
    $departuresToday = (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE DATE(check_out_date) = CURDATE() AND reservation_status IN ('Checked In','Checked Out')")->fetchColumn();
    $revenueToday = (float)$conn->query("SELECT COALESCE(SUM(payment_amount),0) FROM room_billing WHERE billing_status='Paid' AND DATE(transaction_date)=CURDATE()")
        ->fetchColumn();

    // Room list for status board
    $roomsStmt = $conn->query("SELECT id, room_number, room_type, room_status, room_floor FROM rooms ORDER BY CAST(room_floor AS UNSIGNED), CAST(room_number AS UNSIGNED), room_number");
    $rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming arrivals and departures
    $upcomingArrivalsStmt = $conn->query("SELECT r.id, r.check_in_date, g.first_name, g.last_name, rm.room_number
        FROM reservations r
        LEFT JOIN guests g ON g.id = r.guest_id
        LEFT JOIN rooms rm ON rm.id = r.room_id
        WHERE DATE(r.check_in_date) = CURDATE() AND r.reservation_status IN ('Pending','Checked In')
        ORDER BY r.check_in_date ASC LIMIT 8");
    $upcomingArrivals = $upcomingArrivalsStmt->fetchAll(PDO::FETCH_ASSOC);

    $upcomingDeparturesStmt = $conn->query("SELECT r.id, r.check_out_date, g.first_name, g.last_name, rm.room_number
        FROM reservations r
        LEFT JOIN guests g ON g.id = r.guest_id
        LEFT JOIN rooms rm ON rm.id = r.room_id
        WHERE DATE(r.check_out_date) = CURDATE() AND r.reservation_status IN ('Checked In','Checked Out')
        ORDER BY r.check_out_date ASC LIMIT 8");
    $upcomingDepartures = $upcomingDeparturesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent reservations activity
    $recentReservationsStmt = $conn->query("SELECT r.id, r.created_at, r.reservation_status, r.check_in_date, r.check_out_date,
        g.first_name, g.last_name, rm.room_number
        FROM reservations r
        LEFT JOIN guests g ON g.id = r.guest_id
        LEFT JOIN rooms rm ON rm.id = r.room_id
        ORDER BY r.created_at DESC LIMIT 6");
    $recentReservations = $recentReservationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Vacant rooms for Walk-in form
    $vacantRoomsStmt = $conn->query("SELECT id, room_number, room_type FROM rooms WHERE room_status = 'Vacant' ORDER BY CAST(room_number AS UNSIGNED), room_number");
    $vacantRoomsList = $vacantRoomsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Soft-fail the dashboard but keep the page usable
    error_log('Front desk metrics error: ' . $e->getMessage());
    $totalRooms = $occupiedRooms = $reservedRooms = $vacantRooms = $maintenanceRooms = 0;
    $inHouseCount = $pendingResCount = $arrivalsToday = $departuresToday = 0;
    $revenueToday = 0.0;
    $rooms = $upcomingArrivals = $upcomingDepartures = $recentReservations = [];
    $vacantRoomsList = [];
}

// Helper: percent occupancy
$denominator = max(1, $totalRooms);
$occupancyPct = round((($occupiedRooms + $reservedRooms) / $denominator) * 100);
?>

<?php
// Handle Walk-in submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'walkin') {
    header('Content-Type: application/json');
    try {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $idType = trim($_POST['id_type'] ?? 'National ID');
        $idNumber = trim($_POST['id_number'] ?? '');
        $dob = trim($_POST['date_of_birth'] ?? '');
        $roomId = (int)($_POST['room_id'] ?? 0);

        if ($firstName === '' || $lastName === '' || $email === '' || $idNumber === '' || $dob === '' || $roomId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields and select a room.']);
            exit;
        }

        // Ensure room is still vacant
        $roomCheck = $conn->prepare("SELECT room_status FROM rooms WHERE id = ?");
        $roomCheck->execute([$roomId]);
        $status = $roomCheck->fetchColumn();
        if ($status !== 'Vacant') {
            echo json_encode(['success' => false, 'message' => 'Selected room is no longer vacant. Please refresh.']);
            exit;
        }

        // Create guest
        $guestStmt = $conn->prepare("INSERT INTO guests (first_name, last_name, email, id_type, id_number, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)");
        $guestStmt->execute([$firstName, $lastName, $email, $idType, $idNumber, $dob]);
        $guestId = (int)$conn->lastInsertId();

        // Create reservation as Checked In for 8 hours by default
        $now = date('Y-m-d H:i:s');
        $hours = 8;
        $checkOut = date('Y-m-d H:i:s', strtotime($now) + ($hours * 3600));

        $resStmt = $conn->prepare("INSERT INTO reservations (guest_id, room_id, reservation_type, reservation_date, reservation_hour_count, check_in_date, check_out_date, reservation_status) VALUES (?, ?, 'Room', ?, ?, ?, ?, 'Checked In')");
        $resStmt->execute([$guestId, $roomId, $now, $hours, $now, $checkOut]);

        // Set room to Occupied
        $setRoom = $conn->prepare("UPDATE rooms SET room_status = 'Occupied' WHERE id = ?");
        $setRoom->execute([$roomId]);

        echo json_encode(['success' => true, 'message' => 'Walk-in guest checked in successfully.']);
    } catch (Throwable $e) {
        error_log('Walk-in error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to process walk-in.']);
    }
    exit;
}
?>

<div class="container-fluid p-3">
    <div class="flex-grow-1 text-start">
                    <h2>Frontdesk</h2>
                </div>

    <!-- KPIs -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Occupancy</div>
                        <div class="fs-4 fw-bold"><?php echo $occupancyPct; ?>%</div>
                    </div>
                    <i class="cil-graph icon-2xl opacity-75"></i>
                </div>
                <div class="card-footer small text-white-50">In-house: <?php echo $inHouseCount; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Vacant</div>
                        <div class="fs-4 fw-bold"><?php echo $vacantRooms; ?></div>
                    </div>
                    <i class="cil-home icon-2xl opacity-75"></i>
                </div>
                <div class="card-footer small text-white-50">Rooms: <?php echo $totalRooms; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Pending Res.</div>
                        <div class="fs-4 fw-bold"><?php echo $pendingResCount; ?></div>
                    </div>
                    <i class="cil-calendar icon-2xl opacity-75"></i>
                </div>
                <div class="card-footer small text-white-50">Arrivals today: <?php echo $arrivalsToday; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small">Revenue Today</div>
                        <div class="fs-4 fw-bold">₱<?php echo number_format($revenueToday, 2); ?></div>
                    </div>
                    <i class="cil-dollar icon-2xl opacity-75"></i>
                </div>
                <div class="card-footer small text-white-50">Departures today: <?php echo $departuresToday; ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-2">
            <a class="btn btn-primary btn-sm" href="reservations.php"><i class="cil-plus me-1"></i> New Reservation</a>
            <a class="btn btn-success btn-sm" href="reservations.php?status=Checked In"><i class="cil-check me-1"></i> In-house</a>
            <a class="btn btn-secondary btn-sm" href="guests.php"><i class="cil-people me-1"></i> Guests</a>
            <a class="btn btn-warning btn-sm" href="housekeeping.php"><i class="cil-broom me-1"></i> Housekeeping</a>
            <a class="btn btn-info btn-sm" href="room_billing.php"><i class="cil-cash me-1"></i> Billing</a>
            <a class="btn btn-dark btn-sm" href="rooms.php"><i class="cil-home me-1"></i> Rooms</a>
            <button class="btn btn-outline-primary btn-sm ms-auto" data-coreui-toggle="modal" data-coreui-target="#walkInModal"><i class="cil-walk me-1"></i> Walk-in Guest</button>
        </div>
    </div>

    <div class="row g-3">
        <!-- Room Status Board -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Room Status</h6>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="roomStatusFilter" style="width: 160px;">
                            <option value="">All Status</option>
                            <option value="Vacant">Vacant</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                        <input type="text" class="form-control form-control-sm" id="roomSearch" placeholder="Search room #" style="width: 140px;">
                    </div>
                </div>
                <div class="card-body">
                    <div class="row" id="roomGrid">
                        <?php foreach ($rooms as $room): 
                            $status = $room['room_status'];
                            $badge = $status === 'Vacant' ? 'success' : ($status === 'Occupied' ? 'danger' : ($status === 'Reserved' ? 'warning' : 'secondary'));
                        ?>
                        <div class="col-6 col-md-4 col-xl-3 mb-3 room-card" data-status="<?php echo htmlspecialchars($status); ?>" data-room="<?php echo htmlspecialchars($room['room_number']); ?>">
                            <div class="card h-100 border-0 shadow-sm" style="background: #1f2937;">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold">#<?php echo htmlspecialchars($room['room_number']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($room['room_type']); ?></div>
                                        </div>
                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </div>
                                </div>
                                <div class="card-footer p-2 d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary w-100" href="reservations.php"><i class="cil-calendar me-1"></i>Reserve</a>
                                    <a class="btn btn-sm btn-outline-secondary" title="Open room list" href="rooms.php"><i class="cil-external-link"></i></a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Arrivals / Departures -->
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Arrivals Today</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead>
                                <tr><th>Guest</th><th>Room</th><th class="text-end">Time</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcomingArrivals)): ?>
                                <tr><td colspan="3" class="text-center text-muted small">No arrivals scheduled today</td></tr>
                                <?php else: foreach ($upcomingArrivals as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(trim(($row['first_name'] ?: '') . ' ' . ($row['last_name'] ?: '')) ?: 'Walk-in'); ?></td>
                                    <td><?php echo htmlspecialchars($row['room_number'] ?: '—'); ?></td>
                                    <td class="text-end"><?php echo date('H:i', strtotime($row['check_in_date'])); ?></td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-end p-2">
                    <a class="btn btn-sm btn-outline-secondary" href="reservations.php?status=Pending"><i class="cil-external-link me-1"></i>View all</a>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Departures Today</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead>
                                <tr><th>Guest</th><th>Room</th><th class="text-end">Time</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcomingDepartures)): ?>
                                <tr><td colspan="3" class="text-center text-muted small">No departures scheduled today</td></tr>
                                <?php else: foreach ($upcomingDepartures as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(trim(($row['first_name'] ?: '') . ' ' . ($row['last_name'] ?: '')) ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($row['room_number'] ?: '—'); ?></td>
                                    <td class="text-end"><?php echo date('H:i', strtotime($row['check_out_date'])); ?></td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-end p-2">
                    <a class="btn btn-sm btn-outline-secondary" href="reservations.php?status=Checked%20In"><i class="cil-external-link me-1"></i>View all</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="mb-0">Recent Reservations</h6></div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($recentReservations)): ?>
                        <li class="list-group-item small text-muted">No recent activity.</li>
                        <?php else: foreach ($recentReservations as $r): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small"><?php echo htmlspecialchars(trim(($r['first_name'] ?: '') . ' ' . ($r['last_name'] ?: '')) ?: 'Walk-in'); ?></div>
                                <div class="text-muted small">#<?php echo htmlspecialchars($r['room_number'] ?: '—'); ?> • <?php echo htmlspecialchars($r['reservation_status']); ?></div>
                            </div>
                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($r['created_at'])); ?></small>
                        </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
                <div class="card-footer text-end p-2">
                    <a class="btn btn-sm btn-outline-secondary" href="reservations.php"><i class="cil-external-link me-1"></i>Open reservations</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('roomStatusFilter');
    const search = document.getElementById('roomSearch');
    const cards = document.querySelectorAll('#roomGrid .room-card');

    function applyFilters() {
        const status = filter.value.toLowerCase();
        const term = (search.value || '').toLowerCase();
        cards.forEach(card => {
            const matchesStatus = !status || card.dataset.status.toLowerCase() === status;
            const matchesSearch = !term || (card.dataset.room || '').toLowerCase().includes(term);
            card.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
        });
    }

    filter.addEventListener('change', applyFilters);
    search.addEventListener('input', applyFilters);
});

// Minimal alert helper
function fdShowAlert(message, type) {
    const containerId = 'fd-alerts';
    let container = document.getElementById(containerId);
    if (!container) {
        container = document.createElement('div');
        container.id = containerId;
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    const div = document.createElement('div');
    div.className = 'alert alert-' + (type || 'info') + ' alert-dismissible fade show';
    div.innerHTML = message + '<button type="button" class="btn-close" data-coreui-dismiss="alert"></button>';
    container.appendChild(div);
    setTimeout(() => { try { div.remove(); } catch(e){} }, 4000);
}

// Walk-in form submit
function submitWalkIn(e) {
    e.preventDefault();
    const form = document.getElementById('walkInForm');
    const btn = document.getElementById('walkInSubmit');
    btn.disabled = true; const original = btn.innerHTML; btn.innerHTML = '<i class="cil-spinner cil-spin"></i>';
    const data = new FormData(form);
    data.append('action', 'walkin');
    fetch('frontdesk.php', { method: 'POST', body: data })
      .then(r => r.json())
      .then(j => {
          if (j.success) {
              fdShowAlert(j.message, 'success');
              const modal = coreui.Modal.getInstance(document.getElementById('walkInModal')) || new coreui.Modal('#walkInModal');
              modal.hide();
              setTimeout(() => location.reload(), 800);
          } else {
              fdShowAlert(j.message || 'Failed to create walk-in.', 'danger');
          }
      })
      .catch(() => fdShowAlert('Network error. Please try again.', 'danger'))
      .finally(() => { btn.disabled = false; btn.innerHTML = original; });
}
</script>

<!-- Walk-in Guest Modal -->
<div class="modal fade" id="walkInModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Walk-in Guest</h6>
        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
      </div>
      <form id="walkInForm" onsubmit="submitWalkIn(event)">
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small">First Name *</label>
            <input type="text" class="form-control form-control-sm" name="first_name" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Last Name *</label>
            <input type="text" class="form-control form-control-sm" name="last_name" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Email *</label>
            <input type="email" class="form-control form-control-sm" name="email" required>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label small">ID Type *</label>
              <select class="form-select form-select-sm" name="id_type" required>
                <option value="Passport">Passport</option>
                <option value="Driver License">Driver License</option>
                <option value="National ID" selected>National ID</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small">ID Number *</label>
              <input type="text" class="form-control form-control-sm" name="id_number" required>
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label small">Date of Birth *</label>
            <input type="date" class="form-control form-control-sm" name="date_of_birth" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Room (Vacant only) *</label>
            <select class="form-select form-select-sm" name="room_id" required>
              <option value="">Select room...</option>
              <?php foreach ($vacantRoomsList as $vr): ?>
                <option value="<?php echo (int)$vr['id']; ?>">#<?php echo htmlspecialchars($vr['room_number']); ?> • <?php echo htmlspecialchars($vr['room_type']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="text-muted small">Walk-in will be checked in immediately for 8 hours by default. You can edit later in Reservations.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
          <button type="submit" id="walkInSubmit" class="btn btn-primary">Check In</button>
        </div>
      </form>
    </div>
  </div>
</div>