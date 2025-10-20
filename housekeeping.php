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
                    $stmt = $conn->prepare("INSERT INTO housekeeping (room_id, housekeeper_id, task_type, priority, scheduled_date, scheduled_time, estimated_duration_minutes, issues_found, maintenance_required, guest_feedback, supervisor_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
                    echo json_encode(['success' => true, 'message' => 'Task created successfully']);
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
    ORDER BY h.scheduled_date DESC, h.scheduled_time DESC
")->fetchAll(PDO::FETCH_ASSOC);
$supplies = $conn->query("SELECT * FROM housekeeping_supplies ORDER BY current_stock / minimum_stock_level ASC")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $conn->query("SELECT id, room_number, room_type, room_status FROM rooms ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM housekeepers WHERE status = 'Active') as active_housekeepers,
        (SELECT COUNT(*) FROM housekeeping WHERE status = 'Pending') as pending_tasks,
        (SELECT COUNT(*) FROM housekeeping WHERE status = 'Completed' AND DATE(scheduled_date) = CURDATE()) as completed_today,
        (SELECT COUNT(*) FROM housekeeping WHERE maintenance_required = 1) as maintenance_required,
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
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header with Stats -->
        <div class="mb-4">
            <div class="d-flex justify-content-between gap-3 text-center">
                <div class="text-center flex-grow-1">
                <?php include 'housekeepingtitle.html'; ?>
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
                    <small class="text-muted d-block">Completed Today</small>
                    <span class="fw-bold text-success"><?php echo $stats['completed_today']; ?></span>
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

        <!-- Housekeeping -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Housekeeping</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" onclick="generateReport()">
                        <i class="cil-file-pdf me-1"></i>Report
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openCreateTaskModal()">
                        <i class="cil-plus me-1"></i>Add Task
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="housekeepingContainer">
                    <?php foreach ($tasks as $task): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 housekeeping-card" style="border-left: 4px solid <?php
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
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?php echo $task['id']; ?>)" title="Remove">
                                        <i class="cil-trash me-1"></i>Remove
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
                                <input type="tel" class="form-control" id="phone" name="phone">
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

    <!-- Task Modal -->
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalTitle">Add Task</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="taskForm">
                        <input type="hidden" name="action" id="taskFormAction" value="create_task">
                        <input type="hidden" name="id" id="taskId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_id" class="form-label">Room *</label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">Select Room</option>
                                    <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['room_type'] . ' (' . $room['room_status'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="housekeeper_id" class="form-label">Housekeeper</label>
                                <select class="form-select" id="housekeeper_id" name="housekeeper_id">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($housekeepers as $housekeeper): ?>
                                    <option value="<?php echo $housekeeper['id']; ?>"><?php echo htmlspecialchars($housekeeper['first_name'] . ' ' . $housekeeper['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="task_type" class="form-label">Task Type *</label>
                                <select class="form-select" id="task_type" name="task_type" required>
                                    <option value="Regular Cleaning">Regular Cleaning</option>
                                    <option value="Deep Cleaning">Deep Cleaning</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Inspection">Inspection</option>
                                    <option value="Emergency">Emergency</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority *</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="Low">Low</option>
                                    <option value="Normal">Normal</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scheduled_date" class="form-label">Scheduled Date *</label>
                                <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scheduled_time" class="form-label">Scheduled Time</label>
                                <input type="time" class="form-control" id="scheduled_time" name="scheduled_time">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="estimated_duration_minutes" class="form-label">Estimated Duration (minutes)</label>
                                <input type="number" class="form-control" id="estimated_duration_minutes" name="estimated_duration_minutes" min="1" value="60">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="task_status" name="status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Skipped">Skipped</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="maintenance_required" name="maintenance_required">
                                <label class="form-check-label" for="maintenance_required">
                                    Maintenance Required
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="issues_found" class="form-label">Issues Found</label>
                            <textarea class="form-control" id="issues_found" name="issues_found" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="guest_feedback" class="form-label">Guest Feedback</label>
                            <textarea class="form-control" id="guest_feedback" name="guest_feedback" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="supervisor_notes" class="form-label">Supervisor Notes</label>
                            <textarea class="form-control" id="supervisor_notes" name="supervisor_notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitTaskForm()">Save</button>
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
        }

        function editTask(id) {
            document.getElementById('taskModalTitle').textContent = 'Edit Task';
            document.getElementById('taskFormAction').value = 'update_task';

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
                document.getElementById('taskId').value = data.id;
                document.getElementById('room_id').value = data.room_id;
                document.getElementById('housekeeper_id').value = data.housekeeper_id || '';
                document.getElementById('task_type').value = data.task_type;
                document.getElementById('priority').value = data.priority;
                document.getElementById('task_status').value = data.status;
                document.getElementById('scheduled_date').value = data.scheduled_date;
                document.getElementById('scheduled_time').value = data.scheduled_time || '';
                document.getElementById('estimated_duration_minutes').value = data.estimated_duration_minutes;
                document.getElementById('issues_found').value = data.issues_found || '';
                document.getElementById('maintenance_required').checked = data.maintenance_required == 1;
                document.getElementById('guest_feedback').value = data.guest_feedback || '';
                document.getElementById('supervisor_notes').value = data.supervisor_notes || '';

                new coreui.Modal(document.getElementById('taskModal')).show();
            });
        }

        function deleteTask(id) {
            if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
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
            }
        }

        function submitTaskForm() {
            const form = document.getElementById('taskForm');
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
                    new coreui.Modal(document.getElementById('taskModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
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
    </script>
</body>
</html>