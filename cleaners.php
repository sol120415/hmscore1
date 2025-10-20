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
$supplies = $conn->query("SELECT * FROM housekeeping_supplies ORDER BY current_stock / minimum_stock_level ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM housekeepers WHERE status = 'Active') as active_housekeepers,
        (SELECT COUNT(*) FROM housekeeping_supplies WHERE current_stock <= minimum_stock_level) as low_stock_items,
        (SELECT COUNT(*) FROM housekeepers) as total_housekeepers,
        (SELECT COUNT(*) FROM housekeeping_supplies) as total_supplies
    FROM dual
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleaners - Hotel Management System</title>

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
        .housekeeper-card {
            cursor: pointer;
        }
        .housekeeper-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .housekeeper-actions {
            display: flex;
            justify-content: center;
            margin-top: 0.5rem;
        }
        .supply-card {
            cursor: pointer;
        }
        .supply-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                    <small class="text-muted d-block">Active Cleaners</small>
                    <span class="fw-bold text-primary"><?php echo $stats['active_housekeepers']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Total Cleaners</small>
                    <span class="fw-bold text-success"><?php echo $stats['total_housekeepers']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Total Supplies</small>
                    <span class="fw-bold text-warning"><?php echo $stats['total_supplies']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Low Stock</small>
                    <span class="fw-bold text-danger"><?php echo $stats['low_stock_items']; ?></span>
                </div>
            </div>
        </div>

        <!-- Cleaners -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Cleaners</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" onclick="generateReport()">
                        <i class="cil-file-pdf me-1"></i>Report
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openCreateHousekeeperModal()">
                        <i class="cil-plus me-1"></i>Add Cleaner
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="window.location.href='?page=housekeeping'">
                        <i class="cil-arrow-left me-1"></i>Back to Housekeeping
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="cleanersContainer">
                    <?php foreach ($housekeepers as $housekeeper): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 housekeeper-card" style="border-left: 4px solid <?php
                            echo $housekeeper['status'] === 'Active' ? '#198754' :
                                 ($housekeeper['status'] === 'Inactive' ? '#6c757d' :
                                 ($housekeeper['status'] === 'On Leave' ? '#fd7e14' : '#dc3545'));
                        ?>" onclick="editHousekeeper(<?php echo $housekeeper['id']; ?>)">
                            <div class="card-body">
                                <div class="housekeeper-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($housekeeper['first_name'] . ' ' . $housekeeper['last_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($housekeeper['employee_id']); ?> • <?php echo htmlspecialchars($housekeeper['specialty'] ?: 'General'); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-<?php
                                                echo $housekeeper['status'] === 'Active' ? 'success' :
                                                     ($housekeeper['status'] === 'Inactive' ? 'secondary' :
                                                     ($housekeeper['status'] === 'On Leave' ? 'warning' : 'danger'));
                                            ?>">
                                                <?php echo htmlspecialchars($housekeeper['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted d-block">Shift: <?php echo htmlspecialchars($housekeeper['shift_preference']); ?></small>
                                        <small class="text-muted d-block">Max Rooms/Day: <?php echo htmlspecialchars($housekeeper['max_rooms_per_day']); ?></small>
                                        <small class="text-muted d-block">Hired: <?php echo date('M-d-Y', strtotime($housekeeper['hire_date'])); ?></small>
                                    </div>
                                </div>
                                <div class="housekeeper-actions justify-content-center mt-2">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="event.stopPropagation(); editHousekeeper(<?php echo $housekeeper['id']; ?>)" title="Edit">
                                        <i class="cil-pencil me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteHousekeeper(<?php echo $housekeeper['id']; ?>, '<?php echo htmlspecialchars($housekeeper['first_name'] . ' ' . $housekeeper['last_name']); ?>')" title="Remove">
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

        <!-- Supplies -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Housekeeping Supplies</h5>
            </div>
            <div class="card-body">
                <div class="row" id="suppliesContainer">
                    <?php foreach ($supplies as $supply): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 supply-card" style="border-left: 4px solid <?php
                            echo $supply['current_stock'] <= $supply['minimum_stock_level'] ? '#dc3545' : '#198754';
                        ?>" onclick="editSupply(<?php echo $supply['id']; ?>, '<?php echo htmlspecialchars($supply['supply_name']); ?>', <?php echo $supply['current_stock']; ?>, <?php echo $supply['minimum_stock_level']; ?>, '<?php echo htmlspecialchars($supply['supplier'] ?: ''); ?>', '<?php echo $supply['last_restock_date'] ?: ''; ?>', <?php echo $supply['cost_per_unit'] ?: 0; ?>, '<?php echo htmlspecialchars($supply['notes'] ?: ''); ?>')">
                            <div class="card-body">
                                <div class="supply-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($supply['supply_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($supply['category']); ?> • <?php echo htmlspecialchars($supply['unit_of_measure']); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-<?php
                                                echo $supply['current_stock'] <= $supply['minimum_stock_level'] ? 'danger' : 'success';
                                            ?>">
                                                <?php echo $supply['current_stock'] <= $supply['minimum_stock_level'] ? 'Low Stock' : 'In Stock'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted d-block">Current: <?php echo htmlspecialchars($supply['current_stock']); ?> | Min: <?php echo htmlspecialchars($supply['minimum_stock_level']); ?></small>
                                        <small class="text-muted d-block">Cost/Unit: $<?php echo number_format($supply['cost_per_unit'], 2); ?></small>
                                        <?php if ($supply['supplier']): ?>
                                        <small class="text-muted d-block">Supplier: <?php echo htmlspecialchars($supply['supplier']); ?></small>
                                        <?php endif; ?>
                                    </div>
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
    <div class="modal fade" id="housekeeperModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
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

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label fw-bold">First Name *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-user"></i></span>
                                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label fw-bold">Last Name *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-user"></i></span>
                                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="employee_id" class="form-label fw-bold">Employee ID *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-id-card"></i></span>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id" placeholder="Employee ID" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="hire_date" class="form-label fw-bold">Hire Date *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-calendar"></i></span>
                                    <input type="date" class="form-control" id="hire_date" name="hire_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-bold">Phone</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-phone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold">Email</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-envelope-closed"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Email Address">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="specialty" class="form-label fw-bold">Specialty</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-star"></i></span>
                                    <input type="text" class="form-control" id="specialty" name="specialty" placeholder="e.g., Deep Cleaning, Maintenance">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="shift_preference" class="form-label fw-bold">Shift Preference *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-clock"></i></span>
                                    <select class="form-select" id="shift_preference" name="shift_preference" required>
                                        <option value="Morning">Morning</option>
                                        <option value="Afternoon">Afternoon</option>
                                        <option value="Evening">Evening</option>
                                        <option value="Night">Night</option>
                                        <option value="Flexible">Flexible</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="max_rooms_per_day" class="form-label fw-bold">Max Rooms/Day</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-home"></i></span>
                                    <input type="number" class="form-control" id="max_rooms_per_day" name="max_rooms_per_day" min="1" value="10" placeholder="Max Rooms">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-bold">Status *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-check-circle"></i></span>
                                    <select class="form-select" id="housekeeper_status" name="status" required>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                        <option value="On Leave">On Leave</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label fw-bold">Notes</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-notes"></i></span>
                                    <textarea class="form-control" id="housekeeper_notes" name="notes" rows="2" placeholder="Additional notes"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitHousekeeperForm()">Save Cleaner</button>
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
            new coreui.Modal(document.getElementById('housekeeperModal')).show();
        }

        function editHousekeeper(id) {
            document.getElementById('housekeeperModalTitle').textContent = 'Edit Housekeeper';
            document.getElementById('housekeeperFormAction').value = 'update_housekeeper';

            fetch('cleaners.php', {
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
                fetch('cleaners.php', {
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

            fetch('cleaners.php', {
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

            fetch('cleaners.php', {
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