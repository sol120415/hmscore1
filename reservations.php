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
                    $id = 'RES-' . date('YmdHis') . sprintf('%04d', rand(0, 9999));
                    $check_in_date = $_POST['check_in_date'];
                    $hours = (int)$_POST['reservation_hour_count'];
                    $days = (int)($_POST['reservation_days_count'] ?: 0);
                    $check_out_date = date('Y-m-d H:i:s', strtotime($check_in_date) + ($hours * 3600) + ($days * 24 * 3600));

                    // Check for time conflicts if a room is selected
                    if (!empty($_POST['room_id'])) {
                        $stmt = $conn->prepare("SELECT COUNT(*) as conflict_count FROM reservations WHERE room_id = ? AND reservation_status IN ('Pending', 'Checked In') AND (
                            (check_in_date < ? AND check_out_date > ?) OR
                            (check_in_date < ? AND check_out_date > ?) OR
                            (check_in_date >= ? AND check_out_date <= ?)
                        )");
                        $stmt->execute([
                            $_POST['room_id'],
                            $check_out_date, $check_in_date,  // overlap start
                            $check_in_date, $check_out_date,  // overlap end
                            $check_in_date, $check_out_date   // contained within
                        ]);
                        $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($conflict['conflict_count'] > 0) {
                            echo json_encode(['success' => false, 'message' => 'Time conflict: The selected room is already reserved for this time period.']);
                            break;
                        }
                    }

                    $stmt = $conn->prepare("INSERT INTO reservations (id, guest_id, room_id, reservation_type, reservation_date, reservation_hour_count, reservation_days_count, check_in_date, check_out_date, reservation_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $id,
                        $_POST['guest_id'] ?: null,
                        $_POST['room_id'] ?: null,
                        'Room',
                        date('Y-m-d H:i:s'),
                        $_POST['reservation_hour_count'],
                        $_POST['reservation_days_count'] ?: null,
                        $check_in_date,
                        $check_out_date,
                        'Pending'
                    ]);

                    // Update room status to Reserved if a room was selected
                    if (!empty($_POST['room_id'])) {
                        $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Reserved' WHERE id = ?");
                        $stmt->execute([$_POST['room_id']]);
                    }

                    echo json_encode(['success' => true, 'message' => 'Reservation created successfully']);
                    break;

                case 'update':
                    // Get current reservation data to handle room status changes
                    $stmt = $conn->prepare("SELECT room_id, reservation_status, check_in_date, check_out_date FROM reservations WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $current = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Calculate new check-out date if dates changed
                    $check_in_date = $_POST['check_in_date'];
                    $hours = (int)$_POST['reservation_hour_count'];
                    $days = (int)($_POST['reservation_days_count'] ?: 0);
                    $check_out_date = date('Y-m-d H:i:s', strtotime($check_in_date) + ($hours * 3600) + ($days * 24 * 3600));

                    // Check for time conflicts if room changed or dates changed
                    if (!empty($_POST['room_id']) && (!empty($_POST['room_id']) || $check_in_date !== $current['check_in_date'] || $check_out_date !== $current['check_out_date'])) {
                        $stmt = $conn->prepare("SELECT COUNT(*) as conflict_count FROM reservations WHERE room_id = ? AND id != ? AND reservation_status IN ('Pending', 'Checked In') AND (
                            (check_in_date < ? AND check_out_date > ?) OR
                            (check_in_date < ? AND check_out_date > ?) OR
                            (check_in_date >= ? AND check_out_date <= ?)
                        )");
                        $stmt->execute([
                            $_POST['room_id'],
                            $_POST['id'],  // Exclude current reservation
                            $check_out_date, $check_in_date,  // overlap start
                            $check_in_date, $check_out_date,  // overlap end
                            $check_in_date, $check_out_date   // contained within
                        ]);
                        $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($conflict['conflict_count'] > 0) {
                            echo json_encode(['success' => false, 'message' => 'Time conflict: The selected room is already reserved for this time period.']);
                            break;
                        }
                    }

                    // Update reservation
                    $stmt = $conn->prepare("UPDATE reservations SET guest_id=?, room_id=?, reservation_type=?, reservation_date=?, reservation_hour_count=?, reservation_days_count=?, check_in_date=?, check_out_date=?, reservation_status=? WHERE id=?");
                    $stmt->execute([
                        $_POST['guest_id'] ?: null,
                        $_POST['room_id'] ?: null,
                        $_POST['reservation_type'],
                        $_POST['reservation_date'],
                        $_POST['reservation_hour_count'],
                        $_POST['reservation_days_count'] ?: null,
                        $check_in_date,
                        $check_out_date,
                        $_POST['reservation_status'],
                        $_POST['id']
                    ]);

                    // Handle room status changes based on reservation status
                    if ($_POST['reservation_status'] === 'Checked In' && $current['reservation_status'] !== 'Checked In') {
                        // Check-in: Change room to Occupied
                        if (!empty($_POST['room_id'])) {
                            $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Occupied' WHERE id = ?");
                            $stmt->execute([$_POST['room_id']]);
                        }
                        // If changing rooms, set old room back to Reserved
                        if (!empty($current['room_id']) && $current['room_id'] !== $_POST['room_id']) {
                            $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Reserved' WHERE id = ?");
                            $stmt->execute([$current['room_id']]);
                        }
                    } elseif ($_POST['reservation_status'] === 'Checked Out' && $current['reservation_status'] !== 'Checked Out') {
                        // Check-out: Change room to Maintenance
                        if (!empty($_POST['room_id'])) {
                            $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Maintenance' WHERE id = ?");
                            $stmt->execute([$_POST['room_id']]);
                        }
                    } elseif ($_POST['reservation_status'] === 'Cancelled' && $current['reservation_status'] !== 'Cancelled') {
                        // Cancellation: Set room back to Vacant
                        if (!empty($_POST['room_id'])) {
                            $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Vacant' WHERE id = ?");
                            $stmt->execute([$_POST['room_id']]);
                        }
                    } elseif (!empty($_POST['room_id']) && $_POST['room_id'] !== $current['room_id']) {
                        // Room changed: Set new room to Reserved, old room to Vacant if not checked in
                        $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Reserved' WHERE id = ?");
                        $stmt->execute([$_POST['room_id']]);
                        if (!empty($current['room_id']) && $current['reservation_status'] === 'Pending') {
                            $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Vacant' WHERE id = ?");
                            $stmt->execute([$current['room_id']]);
                        }
                    }

                    echo json_encode(['success' => true, 'message' => 'Reservation updated successfully']);
                    break;

                case 'delete':
                    // Get reservation data before deletion
                    $stmt = $conn->prepare("SELECT room_id, reservation_status FROM reservations WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Delete reservation
                    $stmt = $conn->prepare("DELETE FROM reservations WHERE id=?");
                    $stmt->execute([$_POST['id']]);

                    // Set room back to Vacant if it was reserved or occupied
                    if (!empty($reservation['room_id']) && in_array($reservation['reservation_status'], ['Pending', 'Checked In'])) {
                        $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Vacant' WHERE id = ?");
                        $stmt->execute([$reservation['room_id']]);
                    }

                    echo json_encode(['success' => true, 'message' => 'Reservation deleted successfully']);
                    break;

                case 'checkin':
                    // Check-in reservation - first verify room is not in maintenance
                    $stmt = $conn->prepare("SELECT r.room_id, rm.room_status FROM reservations r LEFT JOIN rooms rm ON r.room_id = rm.id WHERE r.id = ? AND r.reservation_status = 'Pending'");
                    $stmt->execute([$_POST['id']]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$reservation) {
                        echo json_encode(['success' => false, 'message' => 'Reservation not found or not in pending status']);
                        break;
                    }

                    if (!empty($reservation['room_id']) && $reservation['room_status'] === 'Maintenance') {
                        echo json_encode(['success' => false, 'message' => 'Cannot check in: Room is under maintenance']);
                        break;
                    }

                    // Proceed with check-in
                    $stmt = $conn->prepare("UPDATE reservations SET reservation_status = 'Checked In' WHERE id = ?");
                    $result = $stmt->execute([$_POST['id']]);

                    if ($result && $stmt->rowCount() > 0) {
                        // Update room status to Occupied
                        if (!empty($reservation['room_id'])) {
                            $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Occupied' WHERE id = ?");
                            $stmt->execute([$reservation['room_id']]);
                        }
                        echo json_encode(['success' => true, 'message' => 'Guest checked in successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Check-in failed']);
                    }
                    break;

                case 'checkout':
                    // Check-out reservation
                    $stmt = $conn->prepare("UPDATE reservations SET reservation_status = 'Checked Out' WHERE id = ? AND reservation_status = 'Checked In'");
                    $result = $stmt->execute([$_POST['id']]);

                    if ($result && $stmt->rowCount() > 0) {
                        // Update room status to Maintenance
                        $stmt = $conn->prepare("SELECT room_id FROM reservations WHERE id = ?");
                        $stmt->execute([$_POST['id']]);
                        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!empty($reservation['room_id'])) {
                            $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Maintenance' WHERE id = ?");
                            $stmt->execute([$reservation['room_id']]);
                        }
                        echo json_encode(['success' => true, 'message' => 'Guest checked out successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Check-out failed or reservation not found']);
                    }
                    break;

                case 'get_available_rooms':
                    // Get available rooms for a specific date/time range - rooms where the check-in/check-out time range doesn't conflict with existing reservations
                    $check_in_date = $_POST['check_in_date'];
                    $hours = (int)$_POST['reservation_hour_count'];
                    $days = (int)($_POST['reservation_days_count'] ?: 0);
                    $check_out_date = date('Y-m-d H:i:s', strtotime($check_in_date) + ($hours * 3600) + ($days * 24 * 3600));

                    $stmt = $conn->prepare("
                        SELECT r.id, r.room_number, r.room_type, r.room_status
                        FROM rooms r
                        WHERE NOT EXISTS (
                            SELECT 1 FROM reservations res
                            WHERE res.room_id = r.id
                            AND res.reservation_status IN ('Pending', 'Checked In')
                            AND (
                                (res.check_in_date <= ? AND res.check_out_date > ?) OR
                                (res.check_in_date < ? AND res.check_out_date >= ?) OR
                                (res.check_in_date >= ? AND res.check_out_date <= ?)
                            )
                        )
                        ORDER BY r.room_number
                    ");
                    $stmt->execute([
                        $check_in_date, $check_in_date,     // existing reservation overlaps new check-in
                        $check_out_date, $check_out_date,   // existing reservation overlaps new check-out
                        $check_in_date, $check_out_date     // existing reservation is contained within new reservation
                    ]);
                    $available_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($available_rooms);
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
$rooms = $conn->query("SELECT id, room_number, room_type, room_status FROM rooms ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Reservations - Hotel Management System</title>

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
            <div class="text-center flex-grow-1">
                <?php include 'reservationstitle.html'; ?>
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
                                    <?php if ($reservation['reservation_status'] === 'Pending'): ?>
                                        <button class="btn btn-sm btn-success me-1" onclick="checkInReservation('<?php echo $reservation['id']; ?>')" title="Check In">
                                            <i class="cil-check"></i>
                                        </button>
                                    <?php elseif ($reservation['reservation_status'] === 'Checked In'): ?>
                                        <button class="btn btn-sm btn-warning me-1" onclick="checkOutReservation('<?php echo $reservation['id']; ?>')" title="Check Out">
                                            <i class="cil-arrow-right"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editReservation('<?php echo $reservation['id']; ?>')" title="Edit">
                                        <i class="cil-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteReservation('<?php echo $reservation['id']; ?>')" title="Delete">
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
        <div class="modal-dialog modal-lg">
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
                                <select class="form-select" id="room_id" name="room_id" disabled>
                                    <option value="">Select check-in date and duration first</option>
                                    <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>" style="display: none;" data-status="<?php echo $room['room_status']; ?>">
                                        <?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['room_type'] . ' (' . $room['room_status'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="check_in_date" class="form-label">Check-in Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="check_in_date" name="check_in_date" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Duration *</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" class="btn-check" id="hours_8" name="reservation_hour_count" value="8" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary btn-sm" for="hours_8">
                                        <i class="cil-clock me-1"></i>8h
                                    </label>

                                    <input type="radio" class="btn-check" id="hours_16" name="reservation_hour_count" value="16" autocomplete="off">
                                    <label class="btn btn-outline-primary btn-sm" for="hours_16">
                                        <i class="cil-clock me-1"></i>16h
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="reservation_days_count" class="form-label">Days</label>
                                <select class="form-select form-select-sm" id="reservation_days_count" name="reservation_days_count">
                                    <option value="0">0 days</option>
                                    <option value="1">1 day</option>
                                    <option value="2">2 days</option>
                                    <option value="3">3 days</option>
                                    <option value="4">4 days</option>
                                    <option value="5">5 days</option>
                                    <option value="6">6 days</option>
                                    <option value="7">7 days</option>
                                    <option value="14">2 weeks</option>
                                    <option value="21">3 weeks</option>
                                    <option value="30">1 month</option>
                                    <option value="60">2 months</option>
                                    <option value="90">3 months</option>
                                    <option value="120">4 months</option>
                                    <option value="150">5 months</option>
                                    <option value="180">6 months</option>
                                    <option value="210">7 months</option>
                                    <option value="240">8 months</option>
                                    <option value="270">9 months</option>
                                    <option value="300">10 months</option>
                                    <option value="330">11 months</option>
                                    <option value="365">12 months</option>
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
            document.getElementById('room_id').disabled = true;
            // Hide all room options initially
            const roomOptions = document.querySelectorAll('#room_id option[data-status]');
            roomOptions.forEach(option => option.style.display = 'none');
            // Show only the placeholder
            document.querySelector('#room_id option[value=""]').style.display = 'block';
            document.querySelector('#room_id option[value=""]').textContent = 'Select check-in date and duration first';
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
                document.getElementById('reservation_hour_count').value = data.reservation_hour_count;
                document.getElementById('reservation_days_count').value = data.reservation_days_count || '';
                document.getElementById('check_in_date').value = data.check_in_date ? data.check_in_date.substring(0, 16) : '';
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

        function checkInReservation(id) {
            if (confirm('Are you sure you want to check in this guest?')) {
                fetch('reservations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=checkin&id=' + id
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

        function checkOutReservation(id) {
            if (confirm('Are you sure you want to check out this guest?')) {
                fetch('reservations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=checkout&id=' + id
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

        function updateAvailableRooms() {
            const checkInDate = document.getElementById('check_in_date').value;
            const hours = document.querySelector('input[name="reservation_hour_count"]:checked')?.value;
            const days = document.getElementById('reservation_days_count').value || 0;

            if (checkInDate && hours) {
                const formData = new FormData();
                formData.append('action', 'get_available_rooms');
                formData.append('check_in_date', checkInDate);
                formData.append('reservation_hour_count', hours);
                formData.append('reservation_days_count', days);

                fetch('reservations.php', {
                    method: 'POST',
                    headers: {
                        'HX-Request': 'true'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const roomSelect = document.getElementById('room_id');
                    const roomOptions = roomSelect.querySelectorAll('option[data-status]');

                    // Hide all room options first
                    roomOptions.forEach(option => option.style.display = 'none');

                    // Show only available rooms
                    const availableRoomIds = data.map(room => room.id.toString());
                    roomOptions.forEach(option => {
                        if (availableRoomIds.includes(option.value)) {
                            option.style.display = 'block';
                        }
                    });

                    roomSelect.disabled = false;
                    // Update placeholder text
                    document.querySelector('#room_id option[value=""]').textContent = 'Not assigned';
                });
            } else {
                const roomSelect = document.getElementById('room_id');
                const roomOptions = roomSelect.querySelectorAll('option[data-status]');
                roomOptions.forEach(option => option.style.display = 'none');
                roomSelect.disabled = true;
                document.querySelector('#room_id option[value=""]').textContent = 'Select check-in date and duration first';
            }
        }

        // Add event listeners for date/time and duration changes
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('check_in_date').addEventListener('change', updateAvailableRooms);
            // Listen for radio button changes for hours
            document.querySelectorAll('input[name="reservation_hour_count"]').forEach(radio => {
                radio.addEventListener('change', updateAvailableRooms);
            });
            document.getElementById('reservation_days_count').addEventListener('change', updateAvailableRooms);
        });

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