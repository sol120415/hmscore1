<?php
include_once 'db.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

// Handle HTMX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_HX_REQUEST'])) {
    header('Content-Type: application/json');

    try {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];

            switch ($action) {
                case 'create':
                    // Create new reservation
                    $stmt = $conn->prepare("INSERT INTO reservations (id, guest_id, room_id, reservation_type, reservation_date, reservation_hour_count, reservation_days_count, check_in_date, check_out_date, reservation_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['reservation_id'],
                        $_POST['guest_id'] ?: null,
                        $_POST['room_id'] ?: null,
                        $_POST['reservation_type'],
                        $_POST['reservation_date'],
                        $_POST['reservation_hour_count'],
                        $_POST['reservation_days_count'] ?: null,
                        $_POST['check_in_date'],
                        $_POST['check_out_date'],
                        $_POST['reservation_status']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Reservation created successfully']);
                    break;

                case 'update':
                    // Update reservation
                    $stmt = $conn->prepare("UPDATE reservations SET guest_id=?, room_id=?, reservation_type=?, reservation_date=?, reservation_hour_count=?, reservation_days_count=?, check_in_date=?, check_out_date=?, reservation_status=? WHERE id=?");
                    $stmt->execute([
                        $_POST['guest_id'] ?: null,
                        $_POST['room_id'] ?: null,
                        $_POST['reservation_type'],
                        $_POST['reservation_date'],
                        $_POST['reservation_hour_count'],
                        $_POST['reservation_days_count'] ?: null,
                        $_POST['check_in_date'],
                        $_POST['check_out_date'],
                        $_POST['reservation_status'],
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Reservation updated successfully']);
                    break;

                case 'delete':
                    // Delete reservation
                    $stmt = $conn->prepare("DELETE FROM reservations WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Reservation deleted successfully']);
                    break;

                case 'get':
                    // Get reservation data for editing
                    $stmt = $conn->prepare("SELECT * FROM reservations WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($reservation);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get data for display
$reservations = $conn->query("
    SELECT r.*, g.first_name, g.last_name, rm.room_number, rm.room_type
    FROM reservations r
    LEFT JOIN guests g ON r.guest_id = g.id
    LEFT JOIN rooms rm ON r.room_id = rm.id
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$guests = $conn->query("SELECT id, first_name, last_name, email FROM guests ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $conn->query("SELECT id, room_number, room_type, room_status FROM rooms WHERE room_status IN ('Vacant', 'Reserved') ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        COUNT(*) as total_reservations,
        COUNT(CASE WHEN reservation_status = 'Checked In' THEN 1 END) as checked_in,
        COUNT(CASE WHEN reservation_status = 'Pending' THEN 1 END) as pending,
        COUNT(CASE WHEN reservation_status = 'Cancelled' THEN 1 END) as cancelled,
        COUNT(CASE WHEN reservation_type = 'Room' THEN 1 END) as room_reservations,
        COUNT(CASE WHEN reservation_type = 'Event' THEN 1 END) as event_reservations
    FROM reservations
")->fetch(PDO::FETCH_ASSOC);

// Get today's check-ins and check-outs
$todayCheckIns = $conn->query("
    SELECT COUNT(*) as checkins_today
    FROM reservations
    WHERE DATE(check_in_date) = CURDATE() AND reservation_status = 'Pending'
")->fetch(PDO::FETCH_ASSOC);

$todayCheckOuts = $conn->query("
    SELECT COUNT(*) as checkouts_today
    FROM reservations
    WHERE DATE(check_out_date) = CURDATE() AND reservation_status = 'Checked In'
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations Management - Hotel Management System</title>

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
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .modal-content {
            background: #2d3748;
            border: 1px solid #4a5568;
        }
        .reservation-card {
            transition: transform 0.2s;
        }
        .reservation-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Reservations Management</h2>
                <p class="text-muted mb-0">Manage room and event reservations</p>
            </div>
            <button class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#reservationModal" onclick="openCreateModal()">
                <i class="cil-plus me-2"></i>Add Reservation
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total</h6>
                                <h3 class="mb-0"><?php echo $stats['total_reservations']; ?></h3>
                            </div>
                            <i class="cil-calendar fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Checked In</h6>
                                <h3 class="mb-0"><?php echo $stats['checked_in']; ?></h3>
                            </div>
                            <i class="cil-check-circle fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Pending</h6>
                                <h3 class="mb-0"><?php echo $stats['pending']; ?></h3>
                            </div>
                            <i class="cil-clock fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Room Res.</h6>
                                <h3 class="mb-0"><?php echo $stats['room_reservations']; ?></h3>
                            </div>
                            <i class="cil-bed fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Today's Check-ins</h6>
                                <h3 class="mb-0"><?php echo $todayCheckIns['checkins_today']; ?></h3>
                            </div>
                            <i class="cil-arrow-right fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Today's Check-outs</h6>
                                <h3 class="mb-0"><?php echo $todayCheckOuts['checkouts_today']; ?></h3>
                            </div>
                            <i class="cil-arrow-left fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservations Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Reservations</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Reservation ID</th>
                                <th>Type</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($reservation['id']); ?></code></td>
                                <td>
                                    <span class="badge bg-<?php echo $reservation['reservation_type'] === 'Room' ? 'primary' : 'info'; ?>">
                                        <?php echo htmlspecialchars($reservation['reservation_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(($reservation['first_name'] ?: '') . ' ' . ($reservation['last_name'] ?: 'Walk-in')); ?></td>
                                <td>
                                    <?php if ($reservation['room_number']): ?>
                                        <?php echo htmlspecialchars($reservation['room_number'] . ' - ' . $reservation['room_type']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($reservation['check_in_date'])); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($reservation['check_out_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $reservation['reservation_status'] === 'Checked In' ? 'success' :
                                             ($reservation['reservation_status'] === 'Checked Out' ? 'primary' :
                                             ($reservation['reservation_status'] === 'Pending' ? 'warning' :
                                             ($reservation['reservation_status'] === 'Cancelled' ? 'danger' : 'secondary')));
                                    ?>">
                                        <?php echo htmlspecialchars($reservation['reservation_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($reservation['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editReservation('<?php echo $reservation['id']; ?>')">
                                        <i class="cil-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteReservation('<?php echo $reservation['id']; ?>')">
                                        <i class="cil-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Today's Activity -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Today's Check-ins</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $todayReservations = $conn->query("
                            SELECT r.*, g.first_name, g.last_name, rm.room_number
                            FROM reservations r
                            LEFT JOIN guests g ON r.guest_id = g.id
                            LEFT JOIN rooms rm ON r.room_id = rm.id
                            WHERE DATE(r.check_in_date) = CURDATE() AND r.reservation_status = 'Pending'
                            ORDER BY r.check_in_date
                        ")->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($todayReservations)): ?>
                            <p class="text-muted mb-0">No check-ins scheduled for today.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($todayReservations as $res): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars(($res['first_name'] ?: '') . ' ' . ($res['last_name'] ?: 'Walk-in')); ?></strong>
                                            <br><small class="text-muted">Room <?php echo htmlspecialchars($res['room_number'] ?: 'TBD'); ?> • <?php echo date('H:i', strtotime($res['check_in_date'])); ?></small>
                                        </div>
                                        <span class="badge bg-warning">Pending</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Today's Check-outs</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $todayCheckouts = $conn->query("
                            SELECT r.*, g.first_name, g.last_name, rm.room_number
                            FROM reservations r
                            LEFT JOIN guests g ON r.guest_id = g.id
                            LEFT JOIN rooms rm ON r.room_id = rm.id
                            WHERE DATE(r.check_out_date) = CURDATE() AND r.reservation_status = 'Checked In'
                            ORDER BY r.check_out_date
                        ")->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($todayCheckouts)): ?>
                            <p class="text-muted mb-0">No check-outs scheduled for today.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($todayCheckouts as $res): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars(($res['first_name'] ?: '') . ' ' . ($res['last_name'] ?: 'Walk-in')); ?></strong>
                                            <br><small class="text-muted">Room <?php echo htmlspecialchars($res['room_number']); ?> • <?php echo date('H:i', strtotime($res['check_out_date'])); ?></small>
                                        </div>
                                        <span class="badge bg-success">Checked In</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservation Modal -->
    <div class="modal fade" id="reservationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Reservation</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reservationForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="reservationId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reservation_id" class="form-label">Reservation ID *</label>
                                <input type="text" class="form-control" id="reservation_id" name="reservation_id" required placeholder="e.g., RES001">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reservation_type" class="form-label">Reservation Type *</label>
                                <select class="form-select" id="reservation_type" name="reservation_type" required>
                                    <option value="Room">Room</option>
                                    <option value="Event">Event</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="guest_id" class="form-label">Guest</label>
                                <select class="form-select" id="guest_id" name="guest_id">
                                    <option value="">Walk-in Guest</option>
                                    <?php foreach ($guests as $guest): ?>
                                    <option value="<?php echo $guest['id']; ?>"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name'] . ' (' . $guest['email'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room_id" class="form-label">Room</label>
                                <select class="form-select" id="room_id" name="room_id">
                                    <option value="">Not assigned</option>
                                    <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['room_type'] . ' (' . $room['room_status'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reservation_date" class="form-label">Reservation Date *</label>
                                <input type="datetime-local" class="form-control" id="reservation_date" name="reservation_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reservation_hour_count" class="form-label">Hours *</label>
                                <select class="form-select" id="reservation_hour_count" name="reservation_hour_count" required>
                                    <option value="8">8 Hours</option>
                                    <option value="16">16 Hours</option>
                                    <option value="24">24 Hours</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="check_in_date" class="form-label">Check-in Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="check_in_date" name="check_in_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="check_out_date" class="form-label">Check-out Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="check_out_date" name="check_out_date" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reservation_days_count" class="form-label">Number of Days</label>
                                <input type="number" class="form-control" id="reservation_days_count" name="reservation_days_count" min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reservation_status" class="form-label">Status *</label>
                                <select class="form-select" id="reservation_status" name="reservation_status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Checked In">Checked In</option>
                                    <option value="Checked Out">Checked Out</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitReservationForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add Reservation';
            document.getElementById('formAction').value = 'create';
            document.getElementById('reservationId').value = '';
            document.getElementById('reservationForm').reset();
        }

        function editReservation(id) {
            document.getElementById('modalTitle').textContent = 'Edit Reservation';
            document.getElementById('formAction').value = 'update';

            // Fetch reservation data
            fetch('reservations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('reservationId').value = data.id;
                document.getElementById('reservation_id').value = data.id;
                document.getElementById('reservation_type').value = data.reservation_type;
                document.getElementById('guest_id').value = data.guest_id || '';
                document.getElementById('room_id').value = data.room_id || '';
                document.getElementById('reservation_date').value = data.reservation_date ? data.reservation_date.substring(0, 16) : '';
                document.getElementById('reservation_hour_count').value = data.reservation_hour_count;
                document.getElementById('reservation_days_count').value = data.reservation_days_count || '';
                document.getElementById('check_in_date').value = data.check_in_date ? data.check_in_date.substring(0, 16) : '';
                document.getElementById('check_out_date').value = data.check_out_date ? data.check_out_date.substring(0, 16) : '';
                document.getElementById('reservation_status').value = data.reservation_status;

                new coreui.Modal(document.getElementById('reservationModal')).show();
            });
        }

        function deleteReservation(id) {
            if (confirm('Are you sure you want to delete this reservation? This action cannot be undone.')) {
                fetch('reservations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function submitReservationForm() {
            const form = document.getElementById('reservationForm');
            const formData = new FormData(form);

            fetch('reservations.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('reservationModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>