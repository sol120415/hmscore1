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
                case 'create_housekeeper':
                    // Create new housekeeper
                    $stmt = $conn->prepare("INSERT INTO housekeepers (first_name, last_name, employee_id, phone, email, hire_date, specialty, shift_preference, max_rooms_per_day, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['employee_id'],
                        $_POST['phone'] ?: null,
                        $_POST['email'] ?: null,
                        $_POST['hire_date'],
                        $_POST['specialty'] ?: null,
                        $_POST['shift_preference'],
                        $_POST['max_rooms_per_day'] ?: 10,
                        $_POST['notes'] ?: null
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Housekeeper created successfully']);
                    break;

                case 'update_housekeeper':
                    // Update housekeeper
                    $stmt = $conn->prepare("UPDATE housekeepers SET first_name=?, last_name=?, employee_id=?, phone=?, email=?, hire_date=?, status=?, specialty=?, shift_preference=?, max_rooms_per_day=?, notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['employee_id'],
                        $_POST['phone'] ?: null,
                        $_POST['email'] ?: null,
                        $_POST['hire_date'],
                        $_POST['status'],
                        $_POST['specialty'] ?: null,
                        $_POST['shift_preference'],
                        $_POST['max_rooms_per_day'] ?: 10,
                        $_POST['notes'] ?: null,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Housekeeper updated successfully']);
                    break;

                case 'delete_housekeeper':
                    // Delete housekeeper
                    $stmt = $conn->prepare("DELETE FROM housekeepers WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Housekeeper deleted successfully']);
                    break;

                case 'get_housekeeper':
                    // Get housekeeper data for editing
                    $stmt = $conn->prepare("SELECT * FROM housekeepers WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $housekeeper = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($housekeeper);
                    break;

                case 'create_task':
                    // Create new housekeeping task
                    $conn->beginTransaction();
                    try {
                        $stmt = $conn->prepare("INSERT INTO housekeeping (room_id, housekeeper_id, task_type, priority, status, scheduled_date, scheduled_time, estimated_duration_minutes, issues_found, maintenance_required, guest_feedback, supervisor_notes) VALUES (?, ?, ?, ?, 'In Progress', ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['room_id'],
                            $_POST['housekeeper_id'] ?: null,
                            $_POST['task_type'],
                            $_POST['priority'],
                            $_POST['scheduled_date'],
                            $_POST['scheduled_time'] ?: null,
                            $_POST['estimated_duration_minutes'] ?: 60,
                            $_POST['issues_found'] ?: null,
                            isset($_POST['maintenance_required']) ? 1 : 0,
                            $_POST['guest_feedback'] ?: null,
                            $_POST['supervisor_notes'] ?: null
                        ]);

                        // Update room status to Cleaning if housekeeper is assigned
                        if (!empty($_POST['housekeeper_id'])) {
                            $stmt = $conn->prepare("UPDATE rooms SET room_status='Cleaning' WHERE id=?");
                            $stmt->execute([$_POST['room_id']]);
                        }

                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Task created successfully']);
                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                    break;

                case 'update_task':
                    // Update housekeeping task
                    $stmt = $conn->prepare("UPDATE housekeeping SET room_id=?, housekeeper_id=?, task_type=?, priority=?, status=?, scheduled_date=?, scheduled_time=?, estimated_duration_minutes=?, actual_duration_minutes=?, issues_found=?, maintenance_required=?, guest_feedback=?, supervisor_notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['room_id'],
                        $_POST['housekeeper_id'] ?: null,
                        $_POST['task_type'],
                        $_POST['priority'],
                        $_POST['status'],
                        $_POST['scheduled_date'],
                        $_POST['scheduled_time'] ?: null,
                        $_POST['estimated_duration_minutes'] ?: 60,
                        $_POST['actual_duration_minutes'] ?: null,
                        $_POST['issues_found'] ?: null,
                        isset($_POST['maintenance_required']) ? 1 : 0,
                        $_POST['guest_feedback'] ?: null,
                        $_POST['supervisor_notes'] ?: null,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
                    break;

                case 'delete_task':
                    // Delete housekeeping task
                    $stmt = $conn->prepare("DELETE FROM housekeeping WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
                    break;

                case 'get_task':
                    // Get task data for editing
                    $stmt = $conn->prepare("SELECT * FROM housekeeping WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($task);
                    break;

                case 'update_supplies':
                    // Update housekeeping supplies
                    $stmt = $conn->prepare("UPDATE housekeeping_supplies SET current_stock=?, minimum_stock_level=?, supplier=?, last_restock_date=?, cost_per_unit=?, notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['current_stock'],
                        $_POST['minimum_stock_level'],
                        $_POST['supplier'] ?: null,
                        $_POST['last_restock_date'] ?: null,
                        $_POST['cost_per_unit'] ?: 0,
                        $_POST['notes'] ?: null,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Supplies updated successfully']);
                    break;

                case 'complete_task':
                    // Complete housekeeping task and set room to vacant
                    $conn->beginTransaction();
                    try {
                        // Update housekeeping task status to completed
                        $stmt = $conn->prepare("UPDATE housekeeping SET status='Completed', actual_end_time=NOW() WHERE id=?");
                        $stmt->execute([$_POST['id']]);

                        // Get room_id from the task
                        $stmt = $conn->prepare("SELECT room_id FROM housekeeping WHERE id=?");
                        $stmt->execute([$_POST['id']]);
                        $task = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($task) {
                            // Update room status to vacant
                            $stmt = $conn->prepare("UPDATE rooms SET room_status='Vacant', room_last_cleaned=NOW() WHERE id=?");
                            $stmt->execute([$task['room_id']]);
                        }

                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Task completed and room set to vacant']);
                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                    break;

                case 'get_completed_report_data':
                    // Get data for completed tasks report
                    $completedTasks = $conn->query("
                        SELECT h.*, hk.first_name, hk.last_name, r.room_number, r.room_type
                        FROM housekeeping h
                        LEFT JOIN housekeepers hk ON h.housekeeper_id = hk.id
                        LEFT JOIN rooms r ON h.room_id = r.id
                        WHERE h.status = 'Completed'
                        ORDER BY h.actual_end_time DESC, h.scheduled_date DESC
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode(['success' => true, 'tasks' => $completedTasks]);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get data for display
$housekeepers = $conn->query("SELECT * FROM housekeepers ORDER BY hire_date DESC")->fetchAll(PDO::FETCH_ASSOC);
$tasks = $conn->query("
    SELECT h.*, hk.first_name, hk.last_name, r.room_number, r.room_type
    FROM housekeeping h
    LEFT JOIN housekeepers hk ON h.housekeeper_id = hk.id
    LEFT JOIN rooms r ON h.room_id = r.id
    WHERE h.status = 'In Progress'
    ORDER BY h.scheduled_date DESC, h.scheduled_time DESC
")->fetchAll(PDO::FETCH_ASSOC);
$completedTasks = $conn->query("
    SELECT h.*, hk.first_name, hk.last_name, r.room_number, r.room_type
    FROM housekeeping h
    LEFT JOIN housekeepers hk ON h.housekeeper_id = hk.id
    LEFT JOIN rooms r ON h.room_id = r.id
    WHERE h.status = 'Completed'
    ORDER BY h.actual_end_time DESC, h.scheduled_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
$supplies = $conn->query("SELECT * FROM housekeeping_supplies ORDER BY current_stock / minimum_stock_level ASC")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $conn->query("SELECT id, room_number, room_type, room_status FROM rooms ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM housekeepers WHERE status = 'Active') as active_housekeepers,
        (SELECT COUNT(*) FROM housekeeping WHERE status = 'Pending') as pending_tasks,
        (SELECT COUNT(*) FROM rooms WHERE room_status = 'Maintenance') as maintenance_required,
        (SELECT COUNT(*) FROM housekeeping_supplies WHERE current_stock <= minimum_stock_level) as low_stock_items
    FROM dual
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Housekeeping - Hotel Management System</title>

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
    <script src="js/app-modal.js?v=<?php echo @filemtime('js/app-modal.js'); ?>"></script>

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
        .housekeeping-card {
            cursor: pointer;
        }
        .housekeeping-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .housekeeping-actions {
            display: none;
        }
        .housekeeping-card:hover .housekeeping-actions {
            display: flex;
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
        /* Light theme variants: match Rooms page tuned palette */
        [data-theme="light"] .room-vacant {
            background: linear-gradient(135deg, #b9f3e8, #41dcbe);
            color: #103c34;
        }
        [data-theme="light"] .room-occupied {
            background: linear-gradient(135deg, #e9c5cc, #d7a1ad);
            color: #5a1a21;
        }
        [data-theme="light"] .room-cleaning {
            background: linear-gradient(135deg, #c9f3ff, #00c8ff);
            color: #0a3552;
        }
        [data-theme="light"] .room-maintenance {
            background: linear-gradient(135deg, #bedee1, #afcfd3);
            color: #0a3940;
        }
        [data-theme="light"] .room-reserved {
            background: linear-gradient(135deg, #dee2e7, #d2d7df);
            color: #262a30;
        }
        [data-theme="light"] .room-card { border-color: #d3d8e1; }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header with Stats -->
        <div class="mb-4">
            <div class="d-flex justify-content-between gap-3 text-center">
                <div class="flex-grow-1 text-start">
                    <h2>Housekeeping</h2>
                </div>
                <div>
                    <small class="text-muted d-block">Active Staff</small>
                    <span class="fw-bold text-primary"><?php echo $stats['active_housekeepers']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Pending Tasks</small>
                    <span class="fw-bold text-warning"><?php echo $stats['pending_tasks']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Maintenance Req.</small>
                    <span class="fw-bold text-danger"><?php echo $stats['maintenance_required']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Low Stock</small>
                    <span class="fw-bold text-info"><?php echo $stats['low_stock_items']; ?></span>
                </div>
            </div>
        </div>

        <!-- Maintenance Rooms -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rooms Requiring Maintenance</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-info" onclick="window.location.href='?page=cleaners'">
                        <i class="cil-user me-1"></i>View Cleaners
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="maintenanceContainer">
                    <?php
                    $maintenanceRooms = $conn->query("
                        SELECT * FROM rooms
                        WHERE room_status = 'Maintenance'
                        AND id NOT IN (
                            SELECT room_id FROM housekeeping
                            WHERE status IN ('Pending', 'In Progress')
                        )
                        ORDER BY room_number
                    ")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($maintenanceRooms as $room): ?>
                    <div class="col-md-3 col-lg-2 mb-3">
                        <div class="card h-100 room-card room-maintenance text-white" onclick="openHousekeepingModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" title="Click to assign housekeeper">
                            <div class="card-body text-center position-relative">
                                <div class="position-absolute top-0 end-0" style="margin-top: -8px; margin-right: -8px;">
                                    <button class="btn btn-warning btn-sm rounded-circle shadow" onclick="event.stopPropagation(); openHousekeepingModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" title="Assign Housekeeper">
                                        <i class="cil-settings text-dark"></i>
                                    </button>
                                </div>
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($room['room_number']); ?></h5>
                                <p class="card-text mb-2"><?php echo htmlspecialchars($room['room_type']); ?></p>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($room['room_status']); ?></span>
                                <br><small class="mt-2 d-block"><?php echo htmlspecialchars($room['room_max_guests']); ?> guests max</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($maintenanceRooms)): ?>
                <p class="text-muted mb-0">No rooms currently require maintenance.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Housekeeping -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Active Housekeeping Tasks</h5>
                <div class="d-flex gap-2">
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="housekeepingContainer">
                    <?php foreach ($tasks as $task): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 housekeeping-card position-relative" style="border-left: 4px solid <?php
                            echo $task['status'] === 'Completed' ? '#198754' :
                                 ($task['status'] === 'In Progress' ? '#0d6efd' :
                                 ($task['status'] === 'Pending' ? '#fd7e14' :
                                 ($task['status'] === 'Cancelled' ? '#6c757d' : '#dc3545')));
                        ?>;">
                            <div class="card-body">
                                <div class="housekeeping-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($task['room_number'] . ' - ' . $task['room_type']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($task['task_type']); ?> • <?php echo htmlspecialchars(($task['first_name'] ?: '') . ' ' . ($task['last_name'] ?: 'Unassigned')); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-<?php
                                                echo $task['priority'] === 'Urgent' ? 'danger' :
                                                     ($task['priority'] === 'High' ? 'warning' :
                                                     ($task['priority'] === 'Normal' ? 'primary' : 'secondary'));
                                            ?>">
                                                <?php echo htmlspecialchars($task['priority']); ?>
                                            </span>
                                            <span class="badge bg-<?php
                                                echo $task['status'] === 'Completed' ? 'success' :
                                                     ($task['status'] === 'In Progress' ? 'primary' :
                                                     ($task['status'] === 'Pending' ? 'warning' :
                                                     ($task['status'] === 'Cancelled' ? 'secondary' : 'danger')));
                                            ?>">
                                                <?php echo htmlspecialchars($task['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="housekeeping-actions justify-content-center">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="editTask(<?php echo $task['id']; ?>)" title="Edit">
                                        <i class="cil-pencil me-1"></i>Edit
                                    </button>
                                    <?php if ($task['status'] === 'In Progress'): ?>
                                    <button class="btn btn-sm btn-outline-success me-2" onclick="completeTask(<?php echo $task['id']; ?>)" title="Mark as Complete">
                                        <i class="cil-check me-1"></i>Complete
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?php echo $task['id']; ?>)" title="Remove">
                                        <i class="cil-trash me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($tasks)): ?>
                <p class="text-muted mb-0">No active housekeeping tasks.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Completed Tasks -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Completed Tasks</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-info btn-sm" onclick="generateCompletedReport()">
                        <i class="cil-file-pdf me-1"></i>Completed Report
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="completedContainer">
                    <?php foreach ($completedTasks as $task): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 completed-card" style="border-left: 4px solid #198754;">
                            <div class="card-body">
                                <div class="completed-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($task['room_number'] . ' - ' . $task['room_type']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($task['task_type']); ?> • <?php echo htmlspecialchars(($task['first_name'] ?: '') . ' ' . ($task['last_name'] ?: 'Unassigned')); ?>
                                            </small>
                                            <br><small class="text-success">
                                                Completed: <?php echo $task['actual_end_time'] ? date('M j, Y g:i A', strtotime($task['actual_end_time'])) : 'N/A'; ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-<?php
                                                echo $task['priority'] === 'Urgent' ? 'danger' :
                                                     ($task['priority'] === 'High' ? 'warning' :
                                                     ($task['priority'] === 'Normal' ? 'primary' : 'secondary'));
                                            ?>">
                                                <?php echo htmlspecialchars($task['priority']); ?>
                                            </span>
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars($task['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="completed-actions justify-content-center">
                                    <button class="btn btn-sm btn-outline-info me-2" onclick="viewTaskDetails(<?php echo $task['id']; ?>)" title="View Details">
                                        <i class="cil-info me-1"></i>Details
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?php echo $task['id']; ?>)" title="Remove">
                                        <i class="cil-trash me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($completedTasks)): ?>
                <p class="text-muted mb-0">No completed tasks yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Housekeeper Modal -->
    <div class="modal fade" id="housekeeperModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="housekeeperModalTitle">Add Housekeeper</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="housekeeperForm">
                        <input type="hidden" name="action" id="housekeeperFormAction" value="create_housekeeper">
                        <input type="hidden" name="id" id="housekeeperId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">Employee ID *</label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hire_date" class="form-label">Hire Date *</label>
                                <input type="date" class="form-control" id="hire_date" name="hire_date" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                 <input type="tel" class="form-control" id="phone" name="phone" inputmode="numeric" maxlength="11" oninput="this.value=this.value.replace(/\\D/g,'').slice(0,11)" onkeypress="return /[0-9]/.test(event.key)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="specialty" class="form-label">Specialty</label>
                                <input type="text" class="form-control" id="specialty" name="specialty" placeholder="e.g., Deep Cleaning, Maintenance">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="shift_preference" class="form-label">Shift Preference *</label>
                                <select class="form-select" id="shift_preference" name="shift_preference" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Afternoon">Afternoon</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                    <option value="Flexible">Flexible</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_rooms_per_day" class="form-label">Max Rooms/Day</label>
                                <input type="number" class="form-control" id="max_rooms_per_day" name="max_rooms_per_day" min="1" value="10">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="housekeeper_status" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="On Leave">On Leave</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="housekeeper_notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitHousekeeperForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Housekeeping Task Modal -->
    <div class="modal fade" id="housekeepingModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="housekeepingModalTitle">Create Housekeeping Task</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="housekeepingForm" onsubmit="submitHousekeepingForm(event)">
                        <input type="hidden" name="action" value="create_task">
                        <input type="hidden" name="room_id" id="housekeepingRoomId">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="housekeepingRoomNumber" class="form-label fw-bold">Room Number</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-home"></i></span>
                                    <input type="text" class="form-control" id="housekeepingRoomNumber" readonly disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="housekeeper_id" class="form-label fw-bold">Housekeeper *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-user"></i></span>
                                    <select class="form-select" id="housekeeper_id" name="housekeeper_id" required>
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
                                <label for="task_type" class="form-label fw-bold">Task Type *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-task"></i></span>
                                    <select class="form-select" id="task_type" name="task_type" required>
                                        <option value="Regular Cleaning">Regular Cleaning</option>
                                        <option value="Deep Cleaning">Deep Cleaning</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Inspection">Inspection</option>
                                        <option value="Emergency">Emergency</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label fw-bold">Priority *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-bell"></i></span>
                                    <select class="form-select" id="priority" name="priority" required>
                                        <option value="Low">Low</option>
                                        <option value="Normal">Normal</option>
                                        <option value="High">High</option>
                                        <option value="Urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label fw-bold">Scheduled Date *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-calendar"></i></span>
                                    <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="scheduled_time" class="form-label fw-bold">Scheduled Time</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-clock"></i></span>
                                    <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" value="09:00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="estimated_duration_minutes" class="form-label fw-bold">Estimated Duration (minutes)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-timer"></i></span>
                                    <input type="number" class="form-control" id="estimated_duration_minutes" name="estimated_duration_minutes" min="15" max="480" value="60" placeholder="Minutes">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="issues_found" class="form-label fw-bold">Issues Found</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-warning"></i></span>
                                    <textarea class="form-control" id="issues_found" name="issues_found" rows="2" placeholder="Any issues found during task"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="guest_feedback" class="form-label fw-bold">Guest Feedback</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-comment-square"></i></span>
                                    <textarea class="form-control" id="guest_feedback" name="guest_feedback" rows="2" placeholder="Feedback from guest"></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="supervisor_notes" class="form-label fw-bold">Supervisor Notes</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-notes"></i></span>
                                    <textarea class="form-control" id="supervisor_notes" name="supervisor_notes" rows="2" placeholder="Supervisor notes or special instructions"></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="maintenance_required" name="maintenance_required" value="1">
                                    <label class="form-check-label fw-bold" for="maintenance_required">
                                        <i class="cil-settings me-1"></i>Maintenance Required
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="housekeepingForm">Create Task</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Supply Edit Modal -->
    <div class="modal fade" id="supplyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Supply Stock</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="supplyForm">
                        <input type="hidden" name="action" value="update_supplies">
                        <input type="hidden" name="id" id="supplyId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="supply_name_display" class="form-label">Supply Name</label>
                                <input type="text" class="form-control" id="supply_name_display" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="current_stock" class="form-label">Current Stock *</label>
                                <input type="number" class="form-control" id="current_stock" name="current_stock" min="0" step="0.01" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="minimum_stock_level" class="form-label">Minimum Stock Level *</label>
                                <input type="number" class="form-control" id="minimum_stock_level" name="minimum_stock_level" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cost_per_unit" class="form-label">Cost per Unit</label>
                                <input type="number" class="form-control" id="cost_per_unit" name="cost_per_unit" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" class="form-control" id="supplier" name="supplier">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_restock_date" class="form-label">Last Restock Date</label>
                                <input type="date" class="form-control" id="last_restock_date" name="last_restock_date">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="supply_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="supply_notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitSupplyForm()">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        // Housekeeper functions
        function openCreateHousekeeperModal() {
            document.getElementById('housekeeperModalTitle').textContent = 'Add Housekeeper';
            document.getElementById('housekeeperFormAction').value = 'create_housekeeper';
            document.getElementById('housekeeperId').value = '';
            document.getElementById('housekeeperForm').reset();
        }

        function editHousekeeper(id) {
            document.getElementById('housekeeperModalTitle').textContent = 'Edit Housekeeper';
            document.getElementById('housekeeperFormAction').value = 'update_housekeeper';

            fetch('housekeeping.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_housekeeper&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('housekeeperId').value = data.id;
                document.getElementById('first_name').value = data.first_name;
                document.getElementById('last_name').value = data.last_name;
                document.getElementById('employee_id').value = data.employee_id;
                document.getElementById('phone').value = data.phone || '';
                document.getElementById('email').value = data.email || '';
                document.getElementById('hire_date').value = data.hire_date;
                document.getElementById('housekeeper_status').value = data.status;
                document.getElementById('specialty').value = data.specialty || '';
                document.getElementById('shift_preference').value = data.shift_preference;
                document.getElementById('max_rooms_per_day').value = data.max_rooms_per_day;
                document.getElementById('housekeeper_notes').value = data.notes || '';

                new coreui.Modal(document.getElementById('housekeeperModal')).show();
            });
        }

        function deleteHousekeeper(id, name) {
            if (confirm('Are you sure you want to delete the housekeeper "' + name + '"? This action cannot be undone.')) {
                fetch('housekeeping.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_housekeeper&id=' + id
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

        function submitHousekeeperForm() {
            const form = document.getElementById('housekeeperForm');
            const formData = new FormData(form);

            fetch('housekeeping.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('housekeeperModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Task functions
        function openCreateTaskModal() {
            document.getElementById('taskModalTitle').textContent = 'Add Task';
            document.getElementById('taskFormAction').value = 'create_task';
            document.getElementById('taskId').value = '';
            document.getElementById('taskForm').reset();
            new coreui.Modal(document.getElementById('taskModal')).show();
        }

        function editTask(id) {
            document.getElementById('housekeepingModalTitle').textContent = 'Edit Housekeeping Task';
            document.getElementById('housekeepingForm').querySelector('input[name="action"]').value = 'update_task';

            fetch('housekeeping.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_task&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                // Populate form with existing data
                document.getElementById('housekeepingRoomId').value = data.room_id;
                document.getElementById('housekeepingRoomNumber').value = data.room_number || '';
                document.getElementById('housekeeper_id').value = data.housekeeper_id || '';
                document.getElementById('task_type').value = data.task_type;
                document.getElementById('priority').value = data.priority;
                document.getElementById('scheduled_date').value = data.scheduled_date;
                document.getElementById('scheduled_time').value = data.scheduled_time || '';
                document.getElementById('estimated_duration_minutes').value = data.estimated_duration_minutes;
                document.getElementById('issues_found').value = data.issues_found || '';
                document.getElementById('maintenance_required').checked = data.maintenance_required == 1;
                document.getElementById('guest_feedback').value = data.guest_feedback || '';
                document.getElementById('supervisor_notes').value = data.supervisor_notes || '';

                // Add hidden input for task ID when updating
                let taskIdInput = document.getElementById('housekeepingForm').querySelector('input[name="id"]');
                if (!taskIdInput) {
                    taskIdInput = document.createElement('input');
                    taskIdInput.type = 'hidden';
                    taskIdInput.name = 'id';
                    document.getElementById('housekeepingForm').appendChild(taskIdInput);
                }
                taskIdInput.value = data.id;

                // Update submit button text
                const submitBtn = document.querySelector('#housekeepingModal .btn-primary');
                submitBtn.textContent = 'Update Task';

                new coreui.Modal(document.getElementById('housekeepingModal')).show();
            });
        }

        function deleteTask(id) {
            AppModal.confirm('Are you sure you want to delete this task? This action cannot be undone.','localhost says').then(function(yes){ if(!yes) return; 
                fetch('housekeeping.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_task&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            });
        }

        function submitHousekeepingForm(event) {
            event.preventDefault();

            const form = document.getElementById('housekeepingForm');
            const formData = new FormData(form);
            const submitBtn = document.querySelector('#housekeepingModal .btn-primary');
            const originalText = submitBtn.innerHTML;
            const isUpdate = formData.get('action') === 'update_task';

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="cil-spinner cil-spin me-2"></i>${isUpdate ? 'Updating...' : 'Creating...'}`;

            fetch('housekeeping.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(`Housekeeping task ${isUpdate ? 'updated' : 'created'} successfully!`, 'success');
                    new coreui.Modal(document.getElementById('housekeepingModal')).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message || `An error occurred while ${isUpdate ? 'updating' : 'creating'} the task.`, 'danger');
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

        // Reset modal when closed
        document.getElementById('housekeepingModal').addEventListener('hidden.coreui.modal', function() {
            const form = document.getElementById('housekeepingForm');
            const submitBtn = document.querySelector('#housekeepingModal .btn-primary');

            // Reset form
            form.reset();

            // Remove any dynamically added hidden inputs
            const hiddenInputs = form.querySelectorAll('input[type="hidden"][name="id"]');
            hiddenInputs.forEach(input => input.remove());

            // Reset action to create
            form.querySelector('input[name="action"]').value = 'create_task';

            // Reset modal title and button text
            document.getElementById('housekeepingModalTitle').textContent = 'Create Housekeeping Task';
            submitBtn.textContent = 'Create Task';
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

        // Housekeeping modal function
        function openHousekeepingModal(roomId, roomNumber) {
            document.getElementById('housekeepingRoomId').value = roomId;
            document.getElementById('housekeepingRoomNumber').value = roomNumber;
            new coreui.Modal(document.getElementById('housekeepingModal')).show();
        }

        // Supply functions
        function editSupply(id, name, currentStock, minStock, supplier, lastRestock, cost, notes) {
            document.getElementById('supplyId').value = id;
            document.getElementById('supply_name_display').value = name;
            document.getElementById('current_stock').value = currentStock;
            document.getElementById('minimum_stock_level').value = minStock;
            document.getElementById('supplier').value = supplier;
            document.getElementById('last_restock_date').value = lastRestock;
            document.getElementById('cost_per_unit').value = cost;
            document.getElementById('supply_notes').value = notes;

            new coreui.Modal(document.getElementById('supplyModal')).show();
        }

        function submitSupplyForm() {
            const form = document.getElementById('supplyForm');
            const formData = new FormData(form);

            fetch('housekeeping.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('supplyModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function completeTask(id) {
            AppModal.confirm('Are you sure you want to mark this task as completed? This will set the room status to vacant.','localhost says').then(function(yes){ if(!yes) return; 
                fetch('housekeeping.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=complete_task&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Task completed and room set to vacant!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(data.message || 'An error occurred while completing the task.', 'danger');
                    }
                })
                .catch(error => {
                    showAlert('Network error. Please try again.', 'danger');
                    console.error('Error:', error);
                });
            });
        }

        function viewTaskDetails(id) {
            fetch('housekeeping.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_task&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                let details = `Room: ${data.room_number}\n`;
                details += `Task Type: ${data.task_type}\n`;
                details += `Priority: ${data.priority}\n`;
                details += `Status: ${data.status}\n`;
                details += `Scheduled: ${data.scheduled_date} ${data.scheduled_time || ''}\n`;
                details += `Duration: ${data.estimated_duration_minutes} minutes\n`;
                if (data.actual_duration_minutes) {
                    details += `Actual Duration: ${data.actual_duration_minutes} minutes\n`;
                }
                if (data.issues_found) {
                    details += `Issues Found: ${data.issues_found}\n`;
                }
                if (data.guest_feedback) {
                    details += `Guest Feedback: ${data.guest_feedback}\n`;
                }
                if (data.supervisor_notes) {
                    details += `Supervisor Notes: ${data.supervisor_notes}\n`;
                }
                AppModal.alert(details.replace(/\n/g,'<br>'),'localhost says');
            })
            .catch(error => {
                alert('Error loading task details');
                console.error('Error:', error);
            });
        }

        function generateCompletedReport() {
            // Create a new window for the PDF
            const printWindow = window.open('', '_blank');

            // Get current date for the report
            const today = new Date();
            const dateStr = today.toLocaleDateString();

            // Generate HTML content for the report
            let htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Completed Housekeeping Tasks Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #333; text-align: center; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .date { color: #666; margin-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .summary { margin-bottom: 20px; font-weight: bold; }
                        .completed { color: #28a745; }
                        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Hotel Management System</h1>
                        <h2>Completed Housekeeping Tasks Report</h2>
                        <div class="date">Generated on: ${dateStr}</div>
                    </div>
            `;

            // Add summary statistics
            htmlContent += `
                <div class="summary">
                    <p>Total Completed Tasks: <span id="totalTasks">Loading...</span></p>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Task Type</th>
                            <th>Housekeeper</th>
                            <th>Priority</th>
                            <th>Completed Date</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody id="reportBody">
                        <tr><td colspan="6">Loading data...</td></tr>
                    </tbody>
                </table>
                <div class="footer">
                    <p>Report generated by Hotel Management System</p>
                </div>
                </body>
                </html>
            `;

            printWindow.document.write(htmlContent);
            printWindow.document.close();

            // Fetch completed tasks data
            fetch('housekeeping.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_completed_report_data'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tasks) {
                    // Update total count
                    printWindow.document.getElementById('totalTasks').textContent = data.tasks.length;

                    // Generate table rows
                    let tableRows = '';
                    data.tasks.forEach(task => {
                        const completedDate = task.actual_end_time ?
                            new Date(task.actual_end_time).toLocaleDateString() + ' ' +
                            new Date(task.actual_end_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) :
                            'N/A';

                        tableRows += `
                            <tr>
                                <td>${task.room_number} - ${task.room_type}</td>
                                <td>${task.task_type}</td>
                                <td>${task.first_name ? task.first_name + ' ' + (task.last_name || '') : 'Unassigned'}</td>
                                <td>${task.priority}</td>
                                <td class="completed">${completedDate}</td>
                                <td>${task.estimated_duration_minutes} min</td>
                            </tr>
                        `;
                    });

                    printWindow.document.getElementById('reportBody').innerHTML = tableRows;

                    // Trigger print dialog
                    setTimeout(() => {
                        printWindow.print();
                    }, 500);
                } else {
                    printWindow.document.getElementById('reportBody').innerHTML =
                        '<tr><td colspan="6" style="text-align: center; color: #dc3545;">Error loading report data</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error generating report:', error);
                printWindow.document.getElementById('reportBody').innerHTML =
                    '<tr><td colspan="6" style="text-align: center; color: #dc3545;">Error loading report data</td></tr>';
            });
        }
    </script>
</body>
</html>