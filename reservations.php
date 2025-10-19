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
                     // Handle new guest registration first if needed
                     $guest_id = $_POST['guest_id'] ?: null;

                     if (empty($guest_id) && !empty($_POST['new_first_name'])) {
                         // Register new guest first
                         $stmt = $conn->prepare("INSERT INTO guests (first_name, last_name, email, phone, id_type, id_number, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?)");
                         $stmt->execute([
                             $_POST['new_first_name'],
                             $_POST['new_last_name'],
                             $_POST['new_email'],
                             $_POST['new_phone'] ?: null,
                             $_POST['new_id_type'],
                             $_POST['new_id_number'],
                             $_POST['new_date_of_birth']
                         ]);
                         $guest_id = $conn->lastInsertId();
                     }

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
                         $guest_id,
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

// Handle filter requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'filter') {
    $filter = $_POST['filter'] ?? '';
    $whereClause = '';

    switch ($filter) {
        case 'day':
            $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $whereClause = '';
    }

    $reservations = $conn->query("
        SELECT r.*, g.first_name, g.last_name, rm.room_number, rm.room_type
        FROM reservations r
        LEFT JOIN guests g ON r.guest_id = g.id
        LEFT JOIN rooms rm ON r.room_id = rm.id
        $whereClause
        ORDER BY r.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Output just the cards HTML for HTMX
    foreach ($reservations as $reservation): ?>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100 reservation-card">
                <div class="card-body">
                    <div class="reservation-content">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars(($reservation['first_name'] ?: '') . ' ' . ($reservation['last_name'] ?: 'Walk-in')); ?></h6>
                                <small class="text-muted">
                                    <?php if ($reservation['room_number']): ?>
                                        <?php echo htmlspecialchars($reservation['room_number']); ?> • <?php echo date('M-d', strtotime($reservation['check_in_date'])); ?> • <?php echo date('M-d', strtotime($reservation['check_out_date'])); ?>
                                    <?php else: ?>
                                        No room • <?php echo date('M-d', strtotime($reservation['check_in_date'])); ?> • <?php echo date('M-d', strtotime($reservation['check_out_date'])); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <span class="badge bg-<?php echo $reservation['reservation_type'] === 'Room' ? 'primary' : 'info'; ?>">
                                    <?php echo htmlspecialchars($reservation['reservation_type']); ?>
                                </span>
                                <span class="badge bg-<?php
                                    echo $reservation['reservation_status'] === 'Checked In' ? 'success' :
                                         ($reservation['reservation_status'] === 'Checked Out' ? 'primary' :
                                         ($reservation['reservation_status'] === 'Pending' ? 'warning' :
                                         ($reservation['reservation_status'] === 'Cancelled' ? 'danger' : 'secondary')));
                                ?>">
                                    <?php echo htmlspecialchars($reservation['reservation_status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="reservation-actions d-none justify-content-center">
                        <?php if ($reservation['reservation_status'] === 'Pending'): ?>
                            <button class="btn btn-sm btn-success me-2" onclick="checkInReservation('<?php echo $reservation['id']; ?>')" title="Check In">
                                <i class="cil-check me-1"></i>Check In
                            </button>
                        <?php elseif ($reservation['reservation_status'] === 'Checked In'): ?>
                            <button class="btn btn-sm btn-warning me-2" onclick="checkOutReservation('<?php echo $reservation['id']; ?>')" title="Check Out">
                                <i class="cil-arrow-right me-1"></i>Check Out
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteReservation('<?php echo $reservation['id']; ?>')" title="Delete">
                            <i class="cil-trash me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach;
    exit;
}

// Get data for display (filtered if applicable)
$whereClause = '';

switch ($_GET['filter'] ?? '') {
    case 'day':
        $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'year':
        $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    default:
        $whereClause = '';
}

$reservations = $conn->query("
    SELECT r.*, g.first_name, g.last_name, rm.room_number, rm.room_type
    FROM reservations r
    LEFT JOIN guests g ON r.guest_id = g.id
    LEFT JOIN rooms rm ON r.room_id = rm.id
    $whereClause
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$guests = $conn->query("SELECT id, first_name, last_name, email FROM guests ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $conn->query("SELECT id, room_number, room_type, room_status FROM rooms ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);

// Get filter parameter
$filter = $_GET['filter'] ?? '';
$whereClause = '';

switch ($filter) {
    case 'day':
        $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'year':
        $whereClause = "WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    default:
        $whereClause = '';
}

// Get statistics (filtered if applicable)
$statsWhere = str_replace('r.', '', $whereClause); // Remove table alias for stats query
$stats = $conn->query("
    SELECT
        COUNT(*) as total_reservations,
        COUNT(CASE WHEN reservation_status = 'Checked In' THEN 1 END) as checked_in,
        COUNT(CASE WHEN reservation_status = 'Pending' THEN 1 END) as pending,
        COUNT(CASE WHEN reservation_type = 'Room' THEN 1 END) as room_reservations,
        COUNT(CASE WHEN reservation_type = 'Event' THEN 1 END) as event_reservations
    FROM reservations r
    $statsWhere
")->fetch(PDO::FETCH_ASSOC);

// Get today's check-ins and check-outs
$todayCheckIns = $conn->query("SELECT COUNT(*) as checkins_today FROM reservations WHERE DATE(check_in_date) = CURDATE() AND reservation_status = 'Checked In'")->fetch(PDO::FETCH_ASSOC);
$todayCheckOuts = $conn->query("SELECT COUNT(*) as checkouts_today FROM reservations WHERE DATE(check_out_date) = CURDATE() AND reservation_status = 'Checked Out'")->fetch(PDO::FETCH_ASSOC);
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
        .input-group-text {
            background: #4a5568;
            border-color: #4a5568;
            color: #e2e8f0;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .reservation-card {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .reservation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .reservation-actions {
            display: flex;
        }
        .reservation-actions {
            min-height: 32px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">

        <!-- Header with Stats -->
        <div class="mb-4">
            <div class="d-flex justify-content-between gap-3 text-center">
                <div class="text-center flex-grow-1">
                <?php include 'reservationstitle.html'; ?>
                </div>
                <div>
                    <small class="text-muted d-block">Total</small>
                    <span class="fw-bold text-primary"><?php echo $stats['total_reservations']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Checked In</small>
                    <span class="fw-bold text-success"><?php echo $stats['checked_in']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Pending</small>
                    <span class="fw-bold text-warning"><?php echo $stats['pending']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Room Res.</small>
                    <span class="fw-bold text-info"><?php echo $stats['room_reservations']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Today In</small>
                    <span class="fw-bold text-success"><?php echo $todayCheckIns['checkins_today']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Today Out</small>
                    <span class="fw-bold text-danger"><?php echo $todayCheckOuts['checkouts_today']; ?></span>
                </div>
            </div>
        </div>

        <!-- Reservations -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Reservations</h5>
                <div class="d-flex gap-2">
                   <button class="btn btn-outline-primary btn-sm" data-coreui-toggle="modal" data-coreui-target="#reservationModal" onclick="openCreateModal()">
                       <i class="cil-plus me-1"></i>New Reservation
                   </button>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" id="filter_all" name="filter_period" autocomplete="off" checked
                               hx-get="reservations.php?filter=" hx-target="#reservationsContainer" hx-swap="innerHTML">
                        <label class="btn btn-outline-secondary btn-sm" for="filter_all">All</label>

                        <input type="radio" class="btn-check" id="filter_day" name="filter_period" autocomplete="off"
                               hx-get="reservations.php?filter=day" hx-target="#reservationsContainer" hx-swap="innerHTML">
                        <label class="btn btn-outline-secondary btn-sm" for="filter_day">Day</label>

                        <input type="radio" class="btn-check" id="filter_week" name="filter_period" autocomplete="off"
                               hx-get="reservations.php?filter=week" hx-target="#reservationsContainer" hx-swap="innerHTML">
                        <label class="btn btn-outline-secondary btn-sm" for="filter_week">Week</label>

                        <input type="radio" class="btn-check" id="filter_month" name="filter_period" autocomplete="off"
                               hx-get="reservations.php?filter=month" hx-target="#reservationsContainer" hx-swap="innerHTML">
                        <label class="btn btn-outline-secondary btn-sm" for="filter_month">Month</label>

                        <input type="radio" class="btn-check" id="filter_year" name="filter_period" autocomplete="off"
                               hx-get="reservations.php?filter=year" hx-target="#reservationsContainer" hx-swap="innerHTML">
                        <label class="btn btn-outline-secondary btn-sm" for="filter_year">Year</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="reservationsContainer">
                    <?php foreach ($reservations as $reservation): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 reservation-card" style="border-left: 4px solid <?php
                            echo $reservation['reservation_status'] === 'Checked In' ? '#198754' :
                                 ($reservation['reservation_status'] === 'Checked Out' ? '#0d6efd' :
                                 ($reservation['reservation_status'] === 'Pending' ? '#fd7e14' :
                                 ($reservation['reservation_status'] === 'Cancelled' ? '#dc3545' : '#6c757d')));
                        ?>;">
                            <div class="card-body">
                                <div class="reservation-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars(($reservation['first_name'] ?: '') . ' ' . ($reservation['last_name'] ?: 'Walk-in')); ?></h6>
                                            <small class="text-muted">
                                                <?php if ($reservation['room_number']): ?>
                                                    <?php echo htmlspecialchars($reservation['room_number']); ?> • <?php echo date('M-d', strtotime($reservation['check_in_date'])); ?> • <?php echo date('M-d', strtotime($reservation['check_out_date'])); ?>
                                                <?php else: ?>
                                                    No room • <?php echo date('M-d', strtotime($reservation['check_in_date'])); ?> • <?php echo date('M-d', strtotime($reservation['check_out_date'])); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-<?php echo $reservation['reservation_type'] === 'Room' ? 'primary' : 'info'; ?>">
                                                <?php echo htmlspecialchars($reservation['reservation_type']); ?>
                                            </span>
                                            <span class="badge bg-<?php
                                                echo $reservation['reservation_status'] === 'Checked In' ? 'success' :
                                                     ($reservation['reservation_status'] === 'Checked Out' ? 'primary' :
                                                     ($reservation['reservation_status'] === 'Pending' ? 'warning' :
                                                     ($reservation['reservation_status'] === 'Cancelled' ? 'danger' : 'secondary')));
                                            ?>">
                                                <?php echo htmlspecialchars($reservation['reservation_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="reservation-actions justify-content-center">
                                    <?php if ($reservation['reservation_status'] === 'Pending'): ?>
                                        <button class="btn btn-sm btn-success me-2" onclick="checkInReservation('<?php echo $reservation['id']; ?>')" title="Check In">
                                            <i class="cil-check me-1"></i>Check In
                                        </button>
                                    <?php elseif ($reservation['reservation_status'] === 'Checked In'): ?>
                                        <button class="btn btn-sm btn-warning me-2" onclick="checkOutReservation('<?php echo $reservation['id']; ?>')" title="Check Out">
                                            <i class="cil-arrow-right me-1"></i>Check Out
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteReservation('<?php echo $reservation['id']; ?>')" title="Delete">
                                        <i class="cil-trash me-1"></i>Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservation Modal -->
    <div class="modal fade" id="reservationModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Reservation</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reservationForm" hx-post="reservations.php" hx-target="#htmx-response" hx-swap="innerHTML" hx-on:htmx:after-request="handleReservationResponse(event)">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="reservationId">


                        <!-- Guest Selection Tabs -->
                        <ul class="nav nav-tabs mb-3" id="guestTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="existing-guest-tab" data-coreui-toggle="tab" data-coreui-target="#existing-guest" type="button" role="tab">Existing Guest</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="new-guest-tab" data-coreui-toggle="tab" data-coreui-target="#new-guest" type="button" role="tab">New Guest</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Existing Guest Tab -->
                            <div class="tab-pane fade show active" id="existing-guest" role="tabpanel">
                                <div class="input-group mb-3">
                                    <span class="input-group-text"><i class="cil-user"></i></span>
                                    <select class="form-select" id="guest_id" name="guest_id">
                                        <option value="">Choose a guest...</option>
                                        <?php foreach ($guests as $guest): ?>
                                        <option value="<?php echo $guest['id']; ?>"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name'] . ' (' . $guest['email'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- New Guest Tab -->
                            <div class="tab-pane fade" id="new-guest" role="tabpanel">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-user"></i></span>
                                            <input type="text" class="form-control" id="new_first_name" name="new_first_name" placeholder="First Name *">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-user"></i></span>
                                            <input type="text" class="form-control" id="new_last_name" name="new_last_name" placeholder="Last Name *">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-envelope-closed"></i></span>
                                            <input type="email" class="form-control" id="new_email" name="new_email" placeholder="Email *">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-phone"></i></span>
                                            <input type="tel" class="form-control" id="new_phone" name="new_phone" placeholder="Phone">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-id-card"></i></span>
                                            <select class="form-select" id="new_id_type" name="new_id_type">
                                                <option value="Passport">Passport</option>
                                                <option value="Driver License">Driver License</option>
                                                <option value="National ID">National ID</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">#</span>
                                            <input type="text" class="form-control" id="new_id_number" name="new_id_number" placeholder="ID Number *">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-calendar"></i></span>
                                            <input type="date" class="form-control" id="new_date_of_birth" name="new_date_of_birth">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="mt-2 input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-home"></i></span>
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
                            <div class="col-md-6">
                                <div class="mt-2 input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-calendar-check"></i></span>
                                    <input type="datetime-local" class="form-control" id="check_in_date" name="check_in_date" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Duration *</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="btn-group btn-group-sm flex-shrink-0" role="group">
                                        <input type="radio" class="btn-check" id="hours_0" name="reservation_hour_count" value="0" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary" for="hours_0">
                                            <i class="cil-clock me-1"></i>0h
                                        </label>

                                        <input type="radio" class="btn-check" id="hours_8" name="reservation_hour_count" value="8" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="hours_8">
                                            <i class="cil-clock me-1"></i>8h
                                        </label>

                                        <input type="radio" class="btn-check" id="hours_16" name="reservation_hour_count" value="16" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="hours_16">
                                            <i class="cil-clock me-1"></i>16h
                                        </label>
                                    </div>
                                    <select class="form-select form-select-sm" id="reservation_days_count" name="reservation_days_count" style="width: 100px;">
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
                        <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
                    </form>
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

            // Reset to existing guest tab
            const existingTab = document.getElementById('existing-guest-tab');
            const newTab = document.getElementById('new-guest-tab');
            existingTab.classList.add('active');
            newTab.classList.remove('active');
            document.getElementById('existing-guest').classList.add('show', 'active');
            document.getElementById('new-guest').classList.remove('show', 'active');
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
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="cil-spinner cil-spin"></i>';

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
                        showAlert('Reservation deleted successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(data.message || 'Deletion failed.', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    showAlert('Network error. Please try again.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                });
            }
        }

        function checkInReservation(id) {
            if (confirm('Are you sure you want to check in this guest?')) {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="cil-spinner cil-spin"></i>';

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
                        showAlert('Guest checked in successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(data.message || 'Check-in failed.', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    showAlert('Network error. Please try again.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                });
            }
        }

        function checkOutReservation(id) {
            if (confirm('Are you sure you want to check out this guest?')) {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="cil-spinner cil-spin"></i>';

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
                        showAlert('Guest checked out successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(data.message || 'Check-out failed.', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    showAlert('Network error. Please try again.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
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
            document.getElementById('reservation_days_count').addEventListener('change', function() {
                updateAvailableRooms();
                // Auto-set hours to 0 when days are selected
                if (this.value) {
                    document.getElementById('hours_0').checked = true;
                }
            });
        });


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
                    const element = form.querySelector('#check_in_date');
                    element.classList.add('is-invalid');
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'Check-in date cannot be in the past.';
                    element.parentNode.appendChild(feedback);
                    isValid = false;
                }
            }

            // Validate duration - either hours OR days must be selected
            const hoursChecked = form.querySelector('input[name="reservation_hour_count"]:checked');
            const daysSelected = form.querySelector('#reservation_days_count').value;

            if (!hoursChecked && !daysSelected) {
                errors.push('Please select either hours or days for the reservation duration.');
                isValid = false;
            }

            // If hours is selected and it's 0, then days must be selected
            if (hoursChecked && hoursChecked.value === '0' && !daysSelected) {
                const element = form.querySelector('#reservation_days_count');
                element.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = 'Days are required when hours is set to 0.';
                element.parentNode.appendChild(feedback);
                isValid = false;
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
                showAlert('Server returned invalid response.', 'danger');
                console.error('JSON parse error:', e);
            }
        }

        // Add form submit event listener
        document.addEventListener('DOMContentLoaded', function() {
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
</body>
</html>