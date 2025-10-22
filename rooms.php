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
                    // Create new room
                    $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, room_floor, room_status, room_max_guests, room_amenities, room_maintenance_notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['room_number'],
                        $_POST['room_type'],
                        $_POST['room_floor'],
                        $_POST['room_status'],
                        $_POST['room_max_guests'] ?: 2,
                        $_POST['room_amenities'] ?: null,
                        $_POST['room_maintenance_notes'] ?: null
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Room created successfully']);
                    break;

                case 'create_housekeeping':
                    // Create housekeeping task
                    $stmt = $conn->prepare("INSERT INTO housekeeping (room_id, housekeeper_id, task_type, priority, scheduled_date, scheduled_time, estimated_duration_minutes, supervisor_notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['room_id'],
                        $_POST['housekeeper_id'] ?: null,
                        $_POST['task_type'],
                        $_POST['priority'],
                        $_POST['scheduled_date'],
                        $_POST['scheduled_time'],
                        $_POST['estimated_duration_minutes'],
                        $_POST['supervisor_notes'] ?: null,
                        $_SESSION['user_id'] ?? 1 // Default to admin user
                    ]);

                    // Update room status to Cleaning if housekeeper assigned
                    if (!empty($_POST['housekeeper_id'])) {
                        $stmt = $conn->prepare("UPDATE rooms SET room_status = 'Cleaning' WHERE id = ?");
                        $stmt->execute([$_POST['room_id']]);
                    }

                    echo json_encode(['success' => true, 'message' => 'Housekeeping task assigned successfully']);
                    break;

                case 'update':
                    // Update room
                    $stmt = $conn->prepare("UPDATE rooms SET room_number=?, room_type=?, room_floor=?, room_status=?, room_max_guests=?, room_amenities=?, room_maintenance_notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['room_number'],
                        $_POST['room_type'],
                        $_POST['room_floor'],
                        $_POST['room_status'],
                        $_POST['room_max_guests'] ?: 2,
                        $_POST['room_amenities'] ?: null,
                        $_POST['room_maintenance_notes'] ?: null,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
                    break;

                case 'delete':
                    // Delete room
                    $stmt = $conn->prepare("DELETE FROM rooms WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
                    break;

                case 'get':
                    // Get room data for editing
                    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $room = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($room);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle HTMX filter requests (GET requests)
if (isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $floor = $_GET['floor'] ?? '';
    $status = $_GET['status'] ?? '';
    $type = $_GET['type'] ?? '';

    $whereClause = '';
    $conditions = [];

    if (!empty($floor)) {
        $conditions[] = "room_floor = '$floor'";
    }
    if (!empty($status)) {
        $conditions[] = "room_status = '$status'";
    }
    if (!empty($type)) {
        $conditions[] = "room_type = '$type'";
    }

    if (!empty($conditions)) {
        $whereClause = "WHERE " . implode(" AND ", $conditions);
    }

    $rooms = $conn->query("SELECT * FROM rooms $whereClause ORDER BY room_floor ASC, room_number ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Output just the rooms HTML for HTMX
    ?>
    <div class="row">
        <?php foreach ($rooms as $room): ?>
        <div class="col-md-3 col-lg-2 mb-3">
            <div class="card room-card room-<?php echo strtolower($room['room_status']); ?> text-white h-100" onclick="editRoom(<?php echo $room['id']; ?>)">
                <div class="card-body text-center position-relative">
                    <?php
                    // Check if room has an assigned housekeeper
                    $hasHousekeeper = $conn->query("SELECT COUNT(*) as count FROM housekeeping WHERE room_id = {$room['id']} AND status IN ('Pending', 'In Progress')")->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                    ?>
                    <?php if ($room['room_status'] === 'Cleaning'): ?>
                    <div class="position-absolute top-0 end-0" style="margin-top: -8px; margin-right: -8px;">
                        <button class="btn btn-outline-success btn-sm rounded-circle shadow" onclick="event.stopPropagation(); openHousekeepingModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" title="Room Being Cleaned">
                            <i class="cil-check text-success"></i>
                        </button>
                    </div>
                    <?php elseif ($room['room_status'] === 'Maintenance'): ?>
                    <div class="position-absolute top-0 end-0" style="margin-top: -8px; margin-right: -8px;">
                        <button class="btn btn-outline-warning btn-sm rounded-circle shadow" onclick="event.stopPropagation(); openHousekeepingModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" title="Assign Housekeeper">
                            <i class="cil-settings text-warning"></i>
                        </button>
                    </div>
                    <?php elseif ($hasHousekeeper): ?>
                    <div class="position-absolute top-0 end-0" style="margin-top: -8px; margin-right: -8px;">
                        <button class="btn btn-outline-info btn-sm rounded-circle shadow" onclick="event.stopPropagation(); openHousekeepingModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" title="View Housekeeping Task">
                            <i class="cil-broom text-info"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($room['room_number']); ?></h5>
                    <p class="card-text mb-2"><?php echo htmlspecialchars($room['room_type']); ?></p>
                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($room['room_status']); ?></span>
                    <br><small class=""><?php echo htmlspecialchars($room['room_max_guests']); ?> guests max</small>
                    <br><small class="">₱<?php echo number_format($room['room_rate'], 2); ?>/night</small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    exit;
}

// Get data for display (filtered if applicable)
$floor = $_GET['floor'] ?? '';
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';

$whereClause = '';
$conditions = [];

if (!empty($floor)) {
    $conditions[] = "room_floor = '$floor'";
}
if (!empty($status)) {
    $conditions[] = "room_status = '$status'";
}
if (!empty($type)) {
    $conditions[] = "room_type = '$type'";
}

if (!empty($conditions)) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

$rooms = $conn->query("SELECT * FROM rooms $whereClause ORDER BY room_floor ASC, room_number ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get room statistics
$stats = $conn->query("
    SELECT
        COUNT(*) as total_rooms,
        COUNT(CASE WHEN room_status = 'Vacant' THEN 1 END) as vacant_rooms,
        COUNT(CASE WHEN room_status = 'Occupied' THEN 1 END) as occupied_rooms,
        COUNT(CASE WHEN room_status = 'Cleaning' THEN 1 END) as cleaning_rooms,
        COUNT(CASE WHEN room_status = 'Maintenance' THEN 1 END) as maintenance_rooms,
        COUNT(CASE WHEN room_type = 'Single' THEN 1 END) as single_rooms,
        COUNT(CASE WHEN room_type = 'Double' THEN 1 END) as double_rooms,
        COUNT(CASE WHEN room_type = 'Deluxe' THEN 1 END) as deluxe_rooms,
        COUNT(CASE WHEN room_type = 'Suite' THEN 1 END) as suite_rooms
    FROM rooms
")->fetch(PDO::FETCH_ASSOC);

// Get rooms by floor
$roomsByFloor = [];
foreach ($rooms as $room) {
    $floor = $room['room_floor'];
    if (!isset($roomsByFloor[$floor])) {
        $roomsByFloor[$floor] = [];
    }
    $roomsByFloor[$floor][] = $room;
}
ksort($roomsByFloor);

// Get occupancy rate
$occupancyRate = $stats['total_rooms'] > 0 ? round(($stats['occupied_rooms'] / $stats['total_rooms']) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - Hotel Management System</title>

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
            background: linear-gradient(135deg, #0d6efd, #084298);
            color: #b3d4ff;
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
    <div class="container-fluid p-4">

        <!-- Header with Stats -->
        <div class="mb-4">
            <div class="d-flex justify-content-between gap-3 text-center">
                <div class="flex-grow-1 text-start">
                    <h2>Rooms</h2>
                </div>
                <div>
                    <small class="text-muted d-block">Total</small>
                    <span class="fw-bold text-primary"><?php echo $stats['total_rooms']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Vacant</small>
                    <span class="fw-bold text-success"><?php echo $stats['vacant_rooms']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Occupied</small>
                    <span class="fw-bold text-warning"><?php echo $stats['occupied_rooms']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Cleaning</small>
                    <span class="fw-bold text-info"><?php echo $stats['cleaning_rooms']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Maintenance</small>
                    <span class="fw-bold text-danger"><?php echo $stats['maintenance_rooms']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Occupancy</small>
                    <span class="fw-bold text-secondary"><?php echo $occupancyRate; ?>%</span>
                </div>
                <div class="vr"></div>
                <div>
                    <small class="text-muted d-block">Single</small>
                    <span class="fw-bold text-primary"><?php echo $stats['single_rooms']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Double</small>
                    <span class="fw-bold text-success"><?php echo $stats['double_rooms']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Deluxe</small>
                    <span class="fw-bold text-warning"><?php echo $stats['deluxe_rooms']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Suites</small>
                    <span class="fw-bold text-danger"><?php echo $stats['suite_rooms']; ?></span>
                </div>
            </div>
        </div>


        <!-- Rooms -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rooms</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" data-coreui-toggle="modal" data-coreui-target="#roomModal" onclick="openCreateModal()">
                        New Room
                    </button>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="floorFilter" onchange="updateFilters()">
                            <option value="all">All Floors</option>
                            <option value="1">Floor 1</option>
                            <option value="2">Floor 2</option>
                            <option value="3">Floor 3</option>
                            <option value="4">Floor 4</option>
                            <option value="5">Floor 5</option>
                        </select>
                        <select class="form-select form-select-sm" id="statusFilter" onchange="updateFilters()">
                            <option value="all">All Status</option>
                            <option value="Vacant">Vacant</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Cleaning">Cleaning</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                        <select class="form-select form-select-sm" id="typeFilter" onchange="updateFilters()">
                            <option value="all">All Types</option>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="Suite">Suite</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="roomsContainer">
                    <?php foreach ($rooms as $room): ?>
                    <div class="col-md-3 col-lg-2 mb-3">
                        <div class="card room-card room-<?php echo strtolower($room['room_status']); ?> text-white h-100" onclick="editRoom(<?php echo $room['id']; ?>)">
                            <div class="card-body text-center position-relative">
                                <?php
                                // Check if room has an assigned housekeeper
                                $hasHousekeeper = $conn->query("SELECT COUNT(*) as count FROM housekeeping WHERE room_id = {$room['id']} AND status IN ('Pending', 'In Progress')")->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                                ?>
                                <?php if ($room['room_status'] === 'Maintenance'): ?>
                                <div class="position-absolute top-0 end-0" style="margin-top: -8px; margin-right: -8px;">
                                    <button class="btn btn-outline-warning btn-sm rounded-circle shadow" onclick="event.stopPropagation(); openHousekeepingModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" title="Assign Housekeeper">
                                        <i class="cil-settings text-warning"></i>
                                    </button>
                                </div>
                                <?php elseif ($hasHousekeeper && $room['room_status'] !== 'Cleaning'): ?>
                                <div class="position-absolute top-0 end-0" style="margin-top: -8px; margin-right: -8px;">
                                    <button class="btn btn-outline-info btn-sm rounded-circle shadow" onclick="event.stopPropagation(); openHousekeepingModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" title="View Housekeeping Task">
                                        <i class="cil-broom text-info"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($room['room_number']); ?></h5>
                                <p class="card-text mb-2"><?php echo htmlspecialchars($room['room_type']); ?></p>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($room['room_status']); ?></span>
                                <br><small class=""><?php echo htmlspecialchars($room['room_max_guests']); ?> guests max</small>
                                <br><small class="">₱<?php echo number_format($room['room_rate'], 2); ?>/night</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Housekeeping Modal -->
    <div class="modal fade" id="housekeepingModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="housekeepingModalTitle">Assign Housekeeper</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="housekeepingForm">
                        <input type="hidden" name="action" value="create_housekeeping">
                        <input type="hidden" name="room_id" id="housekeepingRoomId">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="housekeepingRoomNumber" class="form-label fw-bold">Room Number</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-home"></i></span>
                                    <input type="text" class="form-control" id="housekeepingRoomNumber" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="housekeeper_id" class="form-label fw-bold">Housekeeper</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-user"></i></span>
                                    <select class="form-select" id="housekeeper_id" name="housekeeper_id">
                                        <option value="">Select Housekeeper</option>
                                        <?php
                                        $housekeepers = $conn->query("SELECT id, first_name, last_name, employee_id FROM housekeepers WHERE status = 'Active' ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($housekeepers as $housekeeper): ?>
                                        <option value="<?php echo $housekeeper['id']; ?>"><?php echo htmlspecialchars($housekeeper['first_name'] . ' ' . $housekeeper['last_name'] . ' (' . $housekeeper['employee_id'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="task_type" class="form-label fw-bold">Task Type</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-task"></i></span>
                                    <select class="form-select" id="task_type" name="task_type">
                                        <option value="Regular Cleaning">Regular Cleaning</option>
                                        <option value="Deep Cleaning">Deep Cleaning</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Inspection">Inspection</option>
                                        <option value="Emergency">Emergency</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label fw-bold">Priority</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-bell"></i></span>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="Low">Low</option>
                                        <option value="Normal">Normal</option>
                                        <option value="High">High</option>
                                        <option value="Urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label fw-bold">Scheduled Date</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-calendar"></i></span>
                                    <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="scheduled_time" class="form-label fw-bold">Scheduled Time</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-clock"></i></span>
                                    <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" value="09:00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="estimated_duration_minutes" class="form-label fw-bold">Estimated Duration (minutes)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-timer"></i></span>
                                    <input type="number" class="form-control" id="estimated_duration_minutes" name="estimated_duration_minutes" min="15" max="480" value="60" placeholder="Minutes">
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="supervisor_notes" class="form-label fw-bold">Supervisor Notes</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-notes"></i></span>
                                    <textarea class="form-control" id="supervisor_notes" name="supervisor_notes" rows="2" placeholder="Supervisor notes or special instructions"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Task</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Room</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="roomForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="roomId">

                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-home"></i></span>
                                    <input type="text" class="form-control" id="room_number" name="room_number" placeholder="Room Number *">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-arrow-up"></i></span>
                                    <input type="text" class="form-control" id="room_floor" name="room_floor" placeholder="Floor *">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-bed"></i></span>
                                    <select class="form-select" id="room_type" name="room_type">
                                        <option value="Single">Single</option>
                                        <option value="Double">Double</option>
                                        <option value="Deluxe">Deluxe</option>
                                        <option value="Suite">Suite</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-check-circle"></i></span>
                                    <select class="form-select" id="room_status" name="room_status">
                                        <option value="Vacant">Vacant</option>
                                        <option value="Occupied">Occupied</option>
                                        <option value="Cleaning">Cleaning</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Reserved">Reserved</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-people"></i></span>
                                    <input type="number" class="form-control" id="room_max_guests" name="room_max_guests" min="1" value="2" placeholder="Max Guests">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-star"></i></span>
                                    <textarea class="form-control" id="room_amenities" name="room_amenities" rows="2" placeholder="Amenities (e.g., TV, Air Conditioning, Bathroom, Kitchen)"></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-wrench"></i></span>
                                    <textarea class="form-control" id="room_maintenance_notes" name="room_maintenance_notes" rows="2" placeholder="Maintenance Notes"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitRoomForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add Room';
            document.getElementById('formAction').value = 'create';
            document.getElementById('roomId').value = '';
            document.getElementById('roomForm').reset();
        }

        function editRoom(id) {
            document.getElementById('modalTitle').textContent = 'Edit Room';
            document.getElementById('formAction').value = 'update';

            // Fetch room data
            fetch('rooms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('roomId').value = data.id;
                document.getElementById('room_number').value = data.room_number;
                document.getElementById('room_floor').value = data.room_floor;
                document.getElementById('room_type').value = data.room_type;
                document.getElementById('room_status').value = data.room_status;
                document.getElementById('room_max_guests').value = data.room_max_guests;
                document.getElementById('room_amenities').value = data.room_amenities || '';
                document.getElementById('room_maintenance_notes').value = data.room_maintenance_notes || '';

                new coreui.Modal(document.getElementById('roomModal')).show();
            });
        }

        function deleteRoom(id, roomNumber) {
            if (confirm('Are you sure you want to delete room ' + roomNumber + '? This action cannot be undone.')) {
                fetch('rooms.php', {
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

        function submitRoomForm() {
            const form = document.getElementById('roomForm');
            const formData = new FormData(form);

            fetch('rooms.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('roomModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function openHousekeepingModal(roomId, roomNumber) {
            document.getElementById('housekeepingModalTitle').textContent = 'Assign Housekeeper - Room ' + roomNumber;
            document.getElementById('housekeepingRoomId').value = roomId;
            document.getElementById('housekeepingRoomNumber').value = roomNumber;
            new coreui.Modal(document.getElementById('housekeepingModal')).show();
        }

        function submitHousekeepingForm(event) {
            event.preventDefault();

            const form = document.getElementById('housekeepingForm');
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="cil-spinner cil-spin me-2"></i>Assigning...';

            fetch('rooms.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Housekeeping task assigned successfully!', 'success');
                    new coreui.Modal(document.getElementById('housekeepingModal')).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message || 'An error occurred while assigning the task.', 'danger');
                }
            })
            .catch(error => {
                showAlert('Network error. Please try again.', 'danger');
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        // Add form submit event listener
        document.addEventListener('DOMContentLoaded', function() {
            const housekeepingForm = document.getElementById('housekeepingForm');
            if (housekeepingForm) {
                housekeepingForm.addEventListener('submit', submitHousekeepingForm);
            }
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

        function setActive(button) {
            // Remove active class from all buttons in the same group
            button.closest('.btn-group').querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');
        }

        function updateFilters() {
            const floor = document.getElementById('floorFilter').value;
            const status = document.getElementById('statusFilter').value;
            const type = document.getElementById('typeFilter').value;

            let url = 'rooms.php';
            const params = [];

            if (floor !== 'all') params.push('floor=' + encodeURIComponent(floor));
            if (status !== 'all') params.push('status=' + encodeURIComponent(status));
            if (type !== 'all') params.push('type=' + encodeURIComponent(type));

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            fetch(url, {
                headers: {
                    'HX-Request': 'true'
                }
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('roomsContainer').innerHTML = html;
            })
            .catch(error => console.error('Error:', error));
        }

        function generateReport() {
            window.open('generate_report.php?page=rooms&type=pdf', '_blank');
        }
    </script>
</body>
</html>