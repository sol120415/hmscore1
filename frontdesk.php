<?php
// Assumes `db.php` and session were already loaded by `dashboard.php`.
// Fetch key KPIs for the front desk view
include_once 'db.php';


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

    // Get guests and rooms for reservation modal
    $guests = $conn->query("SELECT id, first_name, last_name, email FROM guests WHERE guest_status = 'Active' ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
    $rooms = $conn->query("SELECT id, room_number, room_type, room_status FROM rooms ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Soft-fail the dashboard but keep the page usable
    error_log('Front desk metrics error: ' . $e->getMessage());
    $totalRooms = $occupiedRooms = $reservedRooms = $vacantRooms = $maintenanceRooms = 0;
    $inHouseCount = $pendingResCount = $arrivalsToday = $departuresToday = 0;
    $revenueToday = 0.0;
    $rooms = $upcomingArrivals = $upcomingDepartures = $recentReservations = [];
    $vacantRoomsList = [];
    $guests = [];
    $rooms = [];
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

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frontdesk - Hotel Management System</title>

    <!-- CoreUI CSS -->
    <link href="css/coreui.min.css" rel="stylesheet">
    <link href="css/coreui-grid.min.css" rel="stylesheet">
    <link href="css/coreui-reboot.min.css" rel="stylesheet">
    <link href="css/coreui-utilities.min.css" rel="stylesheet">
    <link href="css/coreui-forms.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/all.min.css">

    <!-- HTMX -->
    <script src="js/htmx.min.js"></script>

    <!-- Popper.js for popovers -->
    <script src="js/popper.min.js"></script>
    <!-- CoreUI JS -->
    <script src="js/coreui.bundle.js"></script>
    <script src="js/bootstrap.bundle.js"></script>

    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
        }
        .room-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .room-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .room-vacant {
            background: linear-gradient(135deg, #2d5016, #1a3326);
            color: #d4edda;
        }
        .room-occupied {
            background: linear-gradient(135deg, #721c24, #4a0f14);
            color: #f8d7da;
        }
        .room-cleaning {
            background: linear-gradient(135deg, #856404, #5a3d02);
            color: #fff3cd;
        }
        .room-maintenance {
            background: linear-gradient(135deg, #0c5460, #062a30);
            color: #d1ecf1;
        }
        .room-reserved {
            background: linear-gradient(135deg, #383d41, #212529);
            color: #e2e3e5;
        }
        .input-group-text {
            background: #4a5568;
            border-color: #4a5568;
            color: #e2e8f0;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .floor-section {
            margin-bottom: 2rem;
        }
        .floor-header {
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
<div class="container-fluid p-3">
    <div class="flex-grow-1 text-start">
                    <h2 style="font-family: Arial, sans-serif; font-size: 24px; font-weight: bold;">Front <span  style="color:#0dcaf0;">Desk</span></h2>
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
                    <i class="cil-money icon-2xl opacity-75"></i>
                </div>
                <div class="card-footer small text-white-50">Departures today: <?php echo $departuresToday; ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary btn-sm" data-coreui-toggle="modal" data-coreui-target="#reservationModal" onclick="openCreateModal()">
                <i class="cil-plus me-1"></i>New Reservation
            </button>
            <button class="btn btn-success btn-sm" onclick="window.location.href='?page=rooms&status=Vacant'"><i class="cil-check me-1"></i> In-house</button>
            <button class="btn btn-secondary btn-sm" onclick="window.location.href='?page=guests'"><i class="cil-people me-1"></i> Guests</button>
            <button class="btn btn-warning btn-sm" onclick="window.location.href='?page=housekeeping'"><i class="cil-broom me-1"></i> Housekeeping</button>
            <button class="btn btn-info btn-sm" onclick="window.location.href='?page=room_billing'"><i class="cil-cash me-1"></i> Billing</button>
            <button class="btn btn-dark btn-sm" onclick="window.location.href='?page=rooms'"><i class="cil-home me-1"></i> Rooms</button>
            <button class="btn btn-outline-info btn-sm me-2" data-coreui-toggle="modal" data-coreui-target="#priceListModal"><i class="cil-money me-1"></i>Room Prices</button>
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
                                    <button class="btn btn-sm btn-outline-primary w-100" data-coreui-toggle="modal" data-coreui-target="#walkInModal" onclick="openWalkInForRoom(<?php echo $room['id']; ?>)"><i class="cil-calendar me-1"></i>Reserve</button>
                                    
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
                    <a class="btn btn-sm btn-outline-secondary" href="?page=reservations&status=Pending"><i class="cil-external-link me-1"></i>View all</a>
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
                    <a class="btn btn-sm btn-outline-secondary" href="?page=reservations&status=Checked%20In"><i class="cil-external-link me-1"></i>View all</a>
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
                    <a class="btn btn-sm btn-outline-secondary" href="?page=reservations"><i class="cil-external-link me-1"></i>Open reservations</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reservation Modal -->
<div class="modal fade" id="reservationModal" tabindex="-1">
    <div class="modal-dialog modal-lg" style="max-width: 50vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Reservation</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <form id="reservationForm" hx-post="reservations.php" hx-target="#htmx-response" hx-swap="innerHTML" hx-on:htmx:after-request="handleReservationResponse(event)">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" id="reservationId" name="reservation_id" value="">
                <div class="modal-body">
                    <div class="row g-3 align-items-start">
                        <div class="col-lg-7">
                            <div class="rounded-3 border p-3">
                                <label class="form-label small mb-2">Guest *</label>
                                <ul class="nav nav-tabs nav-fill mb-3" id="guestTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="existing-guest-tab" data-coreui-toggle="tab" data-coreui-target="#existing-guest" type="button" role="tab">Existing Guest</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="new-guest-tab" data-coreui-toggle="tab" data-coreui-target="#new-guest" type="button" role="tab">New Guest</button>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="existing-guest" role="tabpanel">
                                        <div class="mb-2">
                                            <select class="form-select form-select-sm" id="guest_id" name="guest_id">
                                                <option value="">Choose a guest...</option>
                                                <?php foreach ($guests as $guest): ?>
                                                <option value="<?php echo $guest['id']; ?>"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name'] . ' (' . $guest['email'] . ')'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="new-guest" role="tabpanel">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label small">First Name *</label>
                                                <input type="text" class="form-control form-control-sm" id="new_first_name" name="new_first_name" placeholder="First Name *">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small">Last Name *</label>
                                                <input type="text" class="form-control form-control-sm" id="new_last_name" name="new_last_name" placeholder="Last Name *">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small">Email *</label>
                                                <input type="email" class="form-control form-control-sm" id="new_email" name="new_email" placeholder="Email *">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small">Phone</label>
                                                <input type="tel" class="form-control form-control-sm" id="new_phone" name="new_phone" placeholder="Phone" inputmode="numeric" maxlength="11" oninput="this.value=this.value.replace(/\\D/g,'').slice(0,11)" onkeypress="return /[0-9]/.test(event.key)">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">ID Type *</label>
                                                <select class="form-select form-select-sm" id="new_id_type" name="new_id_type">
                                                    <option value="Passport">Passport</option>
                                                    <option value="Driver License">Driver License</option>
                                                    <option value="National ID">National ID</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">ID Number *</label>
                                                <input type="text" class="form-control form-control-sm" id="new_id_number" name="new_id_number" placeholder="ID Number *">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Date of Birth *</label>
                                                <input type="date" class="form-control form-control-sm" id="new_date_of_birth" name="new_date_of_birth" placeholder="Date of Birth">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="rounded-3 border p-3">
                                <div class="mb-2">
                                    <label class="form-label small">Room *</label>
                                    <select class="form-select form-select-sm" id="room_id" name="room_id" required>
                                        <option value="">Select a room first</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>" data-status="<?php echo $room['room_status']; ?>">#<?php echo htmlspecialchars($room['room_number']); ?> • <?php echo htmlspecialchars($room['room_type']); ?> (<?php echo $room['room_status']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Duration *</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <input type="radio" class="btn-check" id="hours_8" name="reservation_hour_count" value="8" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="hours_8">8h</label>
                                                <input type="radio" class="btn-check" id="hours_16" name="reservation_hour_count" value="16" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="hours_16">16h</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select form-select-sm" id="reservation_days_count" name="reservation_hour_count">
                                                <option value="">Days</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                                <option value="6">6</option>
                                                <option value="7">7</option>
                                                <option value="14">14</option>
                                                <option value="21">21</option>
                                                <option value="30">30</option>
                                                <option value="60">60</option>
                                                <option value="90">90</option>
                                                <option value="120">120</option>
                                                <option value="150">150</option>
                                                <option value="180">180</option>
                                                <option value="210">210</option>
                                                <option value="240">240</option>
                                                <option value="270">270</option>
                                                <option value="300">300</option>
                                                <option value="330">330</option>
                                                <option value="365">365</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Check-in Date & Time *</label>
                                    <input type="datetime-local" class="form-control form-control-sm" id="check_in_date" name="check_in_date" disabled required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Room Price List Modal -->
<div class="modal fade" id="priceListModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="cil-money text-warning me-2"></i>Room Price List
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <!-- Single Room -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border" style="background: #f8f9fa;">
                            <div class="card-header text-center bg-secondary text-white">
                                <h6 class="mb-0">Single Room</h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="h4 text-primary mb-2">₱1,500</div>
                                <div class="small text-muted mb-2">per night</div>
                                <div class="small">
                                    <i class="cil-user me-1"></i>1 Guest<br>
                                    <i class="cil-drop me-1"></i>Bathroom
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Double Room -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border border-success">
                            <div class="card-header text-center bg-success text-white">
                                <h6 class="mb-0">Double Room</h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="h4 text-success mb-2">₱2,500</div>
                                <div class="small text-muted mb-2">per night</div>
                                <div class="small">
                                    <i class="cil-people me-1"></i>2 Guests<br>
                                    <i class="cil-tv me-1"></i>TV<br>
                                    <i class="cil-drop me-1"></i>Bathroom
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deluxe Room -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border border-warning" style="border-width: 2px !important;">
                            <div class="card-header text-center bg-warning">
                                <h6 class="mb-0 fw-bold">Deluxe Room</h6>
                                <span class="badge bg-warning text-dark">Popular</span>
                            </div>
                            <div class="card-body text-center">
                                <div class="h4 text-warning mb-2 fw-bold">₱3,500</div>
                                <div class="small text-muted mb-2">per night</div>
                                <div class="small">
                                    <i class="cil-people me-1"></i>3 Guests<br>
                                    <i class="cil-tv me-1"></i>TV<br>
                                    <i class="cil-fan me-1"></i>Air Conditioning<br>
                                    <i class="cil-drop me-1"></i>Bathroom
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Suite -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border border-danger shadow-lg" style="border-width: 3px !important;">
                            <div class="card-header text-center bg-danger text-white">
                                <h6 class="mb-0 fw-bold">Suite</h6>
                                <span class="badge bg-light text-danger">Premium</span>
                            </div>
                            <div class="card-body text-center">
                                <div class="h4 text-danger mb-2 fw-bold">₱4,500</div>
                                <div class="small text-muted mb-2">per night</div>
                                <div class="small">
                                    <i class="cil-people me-1"></i>4 Guests<br>
                                    <i class="cil-tv me-1"></i>TV<br>
                                    <i class="cil-fan me-1"></i>Air Conditioning<br>
                                    <i class="cil-drop me-1"></i>Bathroom<br>
                                    <i class="cil-restaurant me-1"></i>Kitchen
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
                
            </div>
        </div>
    </div>
</div>

<!-- HTMX Response Target -->
<div id="htmx-response" class="d-none"></div>

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

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add Reservation';
    document.getElementById('formAction').value = 'create';
    document.getElementById('reservationId').value = '';
    document.getElementById('reservationForm').reset();

    // Reset to existing guest tab
    const existingTab = document.getElementById('existing-guest-tab');
    const newTab = document.getElementById('new-guest-tab');
    existingTab.classList.add('active');
    newTab.classList.remove('active');
    document.getElementById('existing-guest').classList.add('show', 'active');
    document.getElementById('new-guest').classList.remove('show', 'active');

    // Enable room selection by default
    document.getElementById('room_id').disabled = false;
    document.getElementById('check_in_date').disabled = true;
    document.querySelectorAll('input[name="reservation_hour_count"]').forEach(radio => radio.disabled = true);
    document.getElementById('reservation_days_count').disabled = true;

    // Show the modal
    const modal = new coreui.Modal(document.getElementById('reservationModal'));
    modal.show();
}

function openWalkInForRoom(roomId) {
    // Pre-select the room in the walk-in modal
    setTimeout(() => {
        const roomSelect = document.getElementById('walkInModal').querySelector('select[name="room_id"]');
        roomSelect.value = roomId;
        // Disable the room selection to make it fixed
        roomSelect.disabled = true;
        // Add hidden input to ensure value is submitted since disabled selects aren't
        const hiddenRoom = document.createElement('input');
        hiddenRoom.type = 'hidden';
        hiddenRoom.name = 'room_id';
        hiddenRoom.value = roomId;
        roomSelect.parentNode.appendChild(hiddenRoom);
        // Update label to indicate pre-selected
        const label = roomSelect.closest('.mb-2').querySelector('label');
        if (label) {
            label.textContent = 'Room (Pre-selected) *';
        }
    }, 100);
}

function updateRoomSelection() {
    const roomId = document.getElementById('room_id').value;

    if (roomId) {
        // Room selected, enable date and duration
        document.getElementById('check_in_date').disabled = false;
        document.querySelectorAll('input[name="reservation_hour_count"]').forEach(radio => radio.disabled = false);
        document.getElementById('reservation_days_count').disabled = false;
    } else {
        // No room selected, disable date and duration
        document.getElementById('check_in_date').disabled = true;
        document.getElementById('check_in_date').value = '';
        document.querySelectorAll('input[name="reservation_hour_count"]').forEach(radio => {
            radio.disabled = true;
            radio.checked = false;
        });
        document.getElementById('reservation_days_count').disabled = true;
        document.getElementById('reservation_days_count').value = '';
    }
}

function updateAvailableRooms() {
    const checkInDate = document.getElementById('check_in_date').value;
    const hoursChecked = document.querySelector('input[name="reservation_hour_count"]:checked');
    const daysSelected = document.getElementById('reservation_days_count').value;

    // Determine the duration value
    let durationValue = 0;
    if (hoursChecked) {
        durationValue = hoursChecked.value;
    } else if (daysSelected) {
        durationValue = daysSelected;
    }

    if (checkInDate && durationValue > 0) {
        const formData = new FormData();
        formData.append('action', 'get_available_rooms');
        formData.append('check_in_date', checkInDate);
        formData.append('reservation_hour_count', durationValue);

        fetch('reservations.php', {
            method: 'POST',
            headers: {
                'HX-Request': 'true'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Available rooms:', data);
            const roomSelect = document.getElementById('room_id');
            const roomOptions = roomSelect.querySelectorAll('option[data-status]');

            // First, hide all room options except the "No room assignment" option
            roomOptions.forEach(option => {
                if (option.value !== '') {
                    option.style.display = 'none';
                }
            });

            // Show only available rooms
            const availableRoomIds = data.map(room => room.id.toString());
            console.log('Available room IDs:', availableRoomIds);
            roomOptions.forEach(option => {
                if (availableRoomIds.includes(option.value)) {
                    option.style.display = 'block';
                }
            });

            roomSelect.disabled = false;
            // Update the placeholder to show available count
            const placeholderOption = document.querySelector('#room_id option[value=""]');
            if (placeholderOption) {
                placeholderOption.textContent = `No room assignment (${data.length} available)`;
            }
        })
        .catch(error => {
            console.error('Error fetching available rooms:', error);
        });
    } else {
        // Show all rooms when no date/duration is selected
        const roomSelect = document.getElementById('room_id');
        const roomOptions = roomSelect.querySelectorAll('option[data-status]');
        roomOptions.forEach(option => option.style.display = 'block');
        roomSelect.disabled = false;
        const placeholderOption = document.querySelector('#room_id option[value=""]');
        if (placeholderOption) {
            placeholderOption.textContent = 'Select a room first';
        }
    }
}

function validateForm() {
    const form = document.getElementById('reservationForm');
    let isValid = true;
    const errors = [];

    // Clear previous validation states
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

    // Check if guest selection is made
    const guestId = form.querySelector('#guest_id').value;
    const newFirstName = form.querySelector('#new_first_name').value;

    if (!guestId && !newFirstName) {
        errors.push('Please select an existing guest or fill in the new guest information.');
        isValid = false;
    }

    // Validate existing guest selection
    if (guestId && newFirstName) {
        errors.push('Please select either an existing guest OR create a new guest, not both.');
        isValid = false;
    }

    // Ensure required fields are present in POST data
    if (!guestId && newFirstName) {
        // Check for new guest required fields
        const requiredNewGuestFields = ['new_first_name', 'new_last_name', 'new_email', 'new_id_type', 'new_id_number'];
        requiredNewGuestFields.forEach(field => {
            if (!form.querySelector('#' + field).value.trim()) {
                errors.push(field.replace('new_', '').replace('_', ' ').toUpperCase() + ' is required for new guest.');
                isValid = false;
            }
        });
    }

    // Validate new guest fields if creating new guest
    if (!guestId && newFirstName) {
        const requiredFields = [
            { id: 'new_first_name', name: 'First Name' },
            { id: 'new_last_name', name: 'Last Name' },
            { id: 'new_email', name: 'Email' },
            { id: 'new_id_type', name: 'ID Type' },
            { id: 'new_id_number', name: 'ID Number' },
            { id: 'new_date_of_birth', name: 'Date of Birth' }
        ];

        requiredFields.forEach(field => {
            const element = form.querySelector('#' + field.id);
            if (!element.value.trim()) {
                element.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = field.name + ' is required.';
                element.parentNode.appendChild(feedback);
                isValid = false;
            }
        });

        // Email validation
        const emailField = form.querySelector('#new_email');
        if (emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
            emailField.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = 'Please enter a valid email address.';
            emailField.parentNode.appendChild(feedback);
            isValid = false;
        }

        // Date of birth validation (must be at least 18 years old)
        const dobField = form.querySelector('#new_date_of_birth');
        if (dobField.value) {
            const dob = new Date(dobField.value);
            const today = new Date();
            const age = today.getFullYear() - dob.getFullYear();
            if (age < 18) {
                dobField.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = 'Guest must be at least 18 years old.';
                dobField.parentNode.appendChild(feedback);
                isValid = false;
            }
        }
    }

    // Validate reservation fields
    const checkInDate = form.querySelector('#check_in_date').value;
    if (!checkInDate) {
        const element = form.querySelector('#check_in_date');
        element.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = 'Check-in date and time are required.';
        element.parentNode.appendChild(feedback);
        isValid = false;
    } else {
        // Check if check-in date is not in the past (allow some buffer for form submission time)
        const checkIn = new Date(checkInDate);
        const now = new Date();
        const bufferTime = 5 * 60 * 1000; // 5 minutes buffer
        if (checkIn < (now - bufferTime)) {
            showAlert('Check-in date and time cannot be in the past.', 'warning');
            const element = form.querySelector('#check_in_date');
            element.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = 'Check-in date cannot be in the past.';
            element.parentNode.appendChild(feedback);
            isValid = false;
        }
    }

    // Validate room selection is required
    const roomId = form.querySelector('#room_id').value;
    if (!roomId) {
        showAlert('Room selection is required.', 'warning');
        const element = form.querySelector('#room_id');
        element.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = 'Please select a room.';
        element.parentNode.appendChild(feedback);
        isValid = false;
    }

    // Validate duration - either hours (8 or 16) OR days must be selected
    const hoursChecked = form.querySelector('input[name="reservation_hour_count"]:checked');
    const daysSelected = form.querySelector('#reservation_days_count').value;

    if (!hoursChecked && !daysSelected) {
        errors.push('Please select either hours or days for the reservation duration.');
        isValid = false;
    }

    // Ensure duration values are sent in POST
    if (hoursChecked) {
        // Add hidden input for hours if not already present
        let hiddenHours = form.querySelector('input[name="reservation_hour_count"][type="hidden"]');
        if (!hiddenHours) {
            hiddenHours = document.createElement('input');
            hiddenHours.type = 'hidden';
            hiddenHours.name = 'reservation_hour_count';
            form.appendChild(hiddenHours);
        }
        hiddenHours.value = hoursChecked.value;
    }
    if (daysSelected) {
        // Days dropdown uses the same name as hours, so set it properly
        const daysInput = form.querySelector('#reservation_days_count');
        if (daysInput && daysInput.value) {
            // Override the radio button selection with days value
            let hiddenHours = form.querySelector('input[name="reservation_hour_count"][type="hidden"]');
            if (!hiddenHours) {
                hiddenHours = document.createElement('input');
                hiddenHours.type = 'hidden';
                hiddenHours.name = 'reservation_hour_count';
                form.appendChild(hiddenHours);
            }
            hiddenHours.value = daysInput.value;
        }
    }

    if (!isValid && errors.length > 0) {
        errors.forEach(error => showAlert(error, 'warning'));
    }

    return isValid;
}

function handleReservationResponse(event) {
    const xhr = event.detail.xhr;
    const response = xhr.responseText;

    try {
        const data = JSON.parse(response);
        if (data.success) {
            showAlert('Reservation created successfully!', 'success');
            new coreui.Modal(document.getElementById('reservationModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'An error occurred while creating the reservation.', 'danger');
        }
    } catch (e) {
        showAlert('Server returned invalid response: ' + response, 'danger');
        console.error('JSON parse error:', e);
        console.error('Response:', response);
    }
}

function showAlert(message, type = 'danger') {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    const alertId = 'alert-' + Date.now();

    const alertHTML = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="cil-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    alertContainer.insertAdjacentHTML('beforeend', alertHTML);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Add event listeners for date/time and duration changes
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('room_id').addEventListener('change', updateRoomSelection);
    document.getElementById('check_in_date').addEventListener('change', updateAvailableRooms);
    // Listen for radio button changes for hours
    document.querySelectorAll('input[name="reservation_hour_count"]').forEach(radio => {
        radio.addEventListener('change', updateAvailableRooms);
    });
    document.getElementById('reservation_days_count').addEventListener('change', updateAvailableRooms);

    const reservationForm = document.getElementById('reservationForm');
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<!-- Walk-in Guest Modal -->
<div class="modal fade" id="walkInModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width: 50vw;">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Walk-in Guest</h6>
        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
      </div>
      <form id="walkInForm" onsubmit="submitWalkIn(event)">
        <div class="modal-body">
          <div class="row g-3 align-items-start">
            <div class="col-lg-7">
              <div class="rounded-3 border p-3">
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label small">First Name *</label>
                    <input type="text" class="form-control form-control-sm" name="first_name" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label small">Last Name *</label>
                    <input type="text" class="form-control form-control-sm" name="last_name" required>
                  </div>
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
              </div>
            </div>
            <div class="col-lg-5">
              <div class="rounded-3 border p-3">
                <div class="mb-2">
                  <label class="form-label small">Room (Vacant only) *</label>
                  <select class="form-select form-select-sm" name="room_id" required>
                    <option value="">Select room...</option>
                    <?php foreach ($vacantRoomsList as $vr): ?>
                      <option value="<?php echo (int)$vr['id']; ?>">#<?php echo htmlspecialchars($vr['room_number']); ?> • <?php echo htmlspecialchars($vr['room_type']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-2">
                  <label class="form-label small">Duration *</label>
                  <div class="row g-2">
                    <div class="col-6">
                      <select class="form-select form-select-sm" id="walkin_hours" name="reservation_hour_count">
                        <option value="8" selected>8 hours</option>
                        <option value="16">16 hours</option>
                        <option value="24">1 day (24h)</option>
                        <option value="48">2 days (48h)</option>
                        <option value="72">3 days (72h)</option>
                      </select>
                    </div>
                    <div class="col-6">
                      <input type="datetime-local" class="form-control form-control-sm" id="walkin_checkin" name="check_in_date" placeholder="dd/mm/yyyy --:--">
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
        <div class="modal-body">
          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-secondary btn-sm" data-coreui-dismiss="modal">Cancel</button>
            <button type="submit" id="walkInSubmit" class="btn btn-primary btn-sm">Check In</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>