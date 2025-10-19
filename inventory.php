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
                case 'create_item':
                    // Create new item
                    $stmt = $conn->prepare("INSERT INTO items (item_name, item_description, item_category, unit_of_measure, current_stock, minimum_stock, maximum_stock, unit_cost, unit_price, supplier_id, item_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['item_name'],
                        $_POST['item_description'] ?: null,
                        $_POST['item_category'] ?: null,
                        $_POST['unit_of_measure'],
                        $_POST['current_stock'] ?: 0,
                        $_POST['minimum_stock'] ?: 0,
                        $_POST['maximum_stock'] ?: 0,
                        $_POST['unit_cost'] ?: 0.00,
                        $_POST['unit_price'] ?: 0.00,
                        $_POST['supplier_id'] ?: null,
                        $_POST['item_status']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Item created successfully']);
                    break;

                case 'update_item':
                    // Update item
                    $stmt = $conn->prepare("UPDATE items SET item_name=?, item_description=?, item_category=?, unit_of_measure=?, current_stock=?, minimum_stock=?, maximum_stock=?, unit_cost=?, unit_price=?, supplier_id=?, item_status=? WHERE id=?");
                    $stmt->execute([
                        $_POST['item_name'],
                        $_POST['item_description'] ?: null,
                        $_POST['item_category'] ?: null,
                        $_POST['unit_of_measure'],
                        $_POST['current_stock'] ?: 0,
                        $_POST['minimum_stock'] ?: 0,
                        $_POST['maximum_stock'] ?: 0,
                        $_POST['unit_cost'] ?: 0.00,
                        $_POST['unit_price'] ?: 0.00,
                        $_POST['supplier_id'] ?: null,
                        $_POST['item_status'],
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
                    break;

                case 'delete_item':
                    // Delete item
                    $stmt = $conn->prepare("DELETE FROM items WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
                    break;

                case 'get_item':
                    // Get item data for editing
                    $stmt = $conn->prepare("SELECT * FROM items WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($item);
                    break;

                case 'add_movement':
                    // Add inventory movement
                    $stmt = $conn->prepare("INSERT INTO inventory_movements (item_id, movement_type, quantity, reason, user_id, reference_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['item_id'],
                        $_POST['movement_type'],
                        $_POST['quantity'],
                        $_POST['reason'] ?: null,
                        1, // Default user ID
                        $_POST['reference_id'] ?: null
                    ]);

                    // Update item stock
                    if ($_POST['movement_type'] === 'IN') {
                        $stmt = $conn->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id=?");
                    } else {
                        $stmt = $conn->prepare("UPDATE items SET current_stock = GREATEST(0, current_stock - ?) WHERE id=?");
                    }
                    $stmt->execute([$_POST['quantity'], $_POST['item_id']]);

                    echo json_encode(['success' => true, 'message' => 'Movement recorded successfully']);
                    break;

                case 'get_movements':
                    // Get movements for an item
                    $stmt = $conn->prepare("SELECT * FROM inventory_movements WHERE item_id=? ORDER BY movement_date DESC LIMIT 10");
                    $stmt->execute([$_POST['item_id']]);
                    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($movements);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get data for display
$items = $conn->query("SELECT * FROM items ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$movements = $conn->query("
    SELECT im.*, i.item_name
    FROM inventory_movements im
    JOIN items i ON im.item_id = i.id
    ORDER BY im.movement_date DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        COUNT(*) as total_items,
        COUNT(CASE WHEN item_status = 'Active' THEN 1 END) as active_items,
        COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock_items,
        SUM(current_stock * unit_cost) as total_value,
        SUM(CASE WHEN movement_type = 'IN' THEN quantity ELSE 0 END) as total_in,
        SUM(CASE WHEN movement_type = 'OUT' THEN quantity ELSE 0 END) as total_out
    FROM items
    LEFT JOIN inventory_movements ON items.id = inventory_movements.item_id
")->fetch(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = $conn->query("SELECT DISTINCT item_category FROM items WHERE item_category IS NOT NULL ORDER BY item_category")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Hotel Management System</title>

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
        .low-stock {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .out-of-stock {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #718096;
        }
        .nav-tabs .nav-link.active {
            background: #0dcaf0;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Inventory Management</h2>
                <p class="text-muted mb-0">Track items, stock levels, and movements</p>
            </div>
            <div>
                <button class="btn btn-primary me-2" data-coreui-toggle="modal" data-coreui-target="#itemModal" onclick="openCreateModal()">
                    <i class="cil-plus me-2"></i>Add Item
                </button>
                <button class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#movementModal" onclick="openMovementModal()">
                    <i class="cil-plus me-2"></i>Record Movement
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Items</h6>
                                <h3 class="mb-0"><?php echo $stats['total_items']; ?></h3>
                            </div>
                            <i class="cil-list fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Active Items</h6>
                                <h3 class="mb-0"><?php echo $stats['active_items']; ?></h3>
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
                                <h6 class="card-title mb-1">Low Stock</h6>
                                <h3 class="mb-0"><?php echo $stats['low_stock_items']; ?></h3>
                            </div>
                            <i class="cil-warning fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Value</h6>
                                <h3 class="mb-0">$<?php echo number_format($stats['total_value'] ?: 0, 2); ?></h3>
                            </div>
                            <i class="cil-dollar fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Recent Movements</h6>
                                <h3 class="mb-0"><?php echo ($stats['total_in'] ?: 0) + ($stats['total_out'] ?: 0); ?></h3>
                            </div>
                            <i class="cil-transfer fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="inventoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="items-tab" data-coreui-toggle="tab" data-coreui-target="#items" type="button" role="tab">Items</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="movements-tab" data-coreui-toggle="tab" data-coreui-target="#movements" type="button" role="tab">Movements</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Items Tab -->
            <div class="tab-pane fade show active" id="items" role="tabpanel">
                <!-- Category Filter -->
                <div class="mb-3">
                    <label for="categoryFilter" class="form-label">Filter by Category:</label>
                    <select class="form-select" id="categoryFilter" style="max-width: 300px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="itemsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Min Stock</th>
                                        <th>Unit Cost</th>
                                        <th>Unit Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr class="item-row <?php echo $item['current_stock'] <= $item['minimum_stock'] ? ($item['current_stock'] == 0 ? 'out-of-stock' : 'low-stock') : ''; ?>" data-category="<?php echo htmlspecialchars($item['item_category'] ?: ''); ?>">
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['item_category'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['current_stock']); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                                        <td><?php echo htmlspecialchars($item['minimum_stock']); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                                        <td>$<?php echo number_format($item['unit_cost'], 2); ?></td>
                                        <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $item['item_status'] === 'Active' ? 'success' : ($item['item_status'] === 'Inactive' ? 'secondary' : 'danger'); ?>">
                                                <?php echo htmlspecialchars($item['item_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editItem(<?php echo $item['id']; ?>)">
                                                <i class="cil-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info me-1" onclick="viewMovements(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                                <i class="cil-history"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')">
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
            </div>

            <!-- Movements Tab -->
            <div class="tab-pane fade" id="movements" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Reason</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movements as $movement): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($movement['item_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $movement['movement_type'] === 'IN' ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars($movement['movement_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($movement['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($movement['reason'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($movement['reference_id'] ?: 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($movement['movement_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalTitle">Add Item</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="itemForm">
                        <input type="hidden" name="action" id="itemFormAction" value="create_item">
                        <input type="hidden" name="id" id="itemId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="item_name" class="form-label">Item Name *</label>
                                <input type="text" class="form-control" id="item_name" name="item_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="item_category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="item_category" name="item_category">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="unit_of_measure" class="form-label">Unit of Measure *</label>
                                <select class="form-select" id="unit_of_measure" name="unit_of_measure" required>
                                    <option value="pcs">Pieces (pcs)</option>
                                    <option value="kg">Kilograms (kg)</option>
                                    <option value="liters">Liters</option>
                                    <option value="boxes">Boxes</option>
                                    <option value="sets">Sets</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="item_status" class="form-label">Status *</label>
                                <select class="form-select" id="item_status" name="item_status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Discontinued">Discontinued</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_stock" class="form-label">Current Stock</label>
                                <input type="number" class="form-control" id="current_stock" name="current_stock" min="0" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="minimum_stock" class="form-label">Minimum Stock</label>
                                <input type="number" class="form-control" id="minimum_stock" name="minimum_stock" min="0" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="maximum_stock" class="form-label">Maximum Stock</label>
                                <input type="number" class="form-control" id="maximum_stock" name="maximum_stock" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="unit_cost" class="form-label">Unit Cost</label>
                                <input type="number" class="form-control" id="unit_cost" name="unit_cost" min="0" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit_price" class="form-label">Unit Price</label>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="supplier_id" class="form-label">Supplier ID</label>
                            <input type="number" class="form-control" id="supplier_id" name="supplier_id">
                        </div>

                        <div class="mb-3">
                            <label for="item_description" class="form-label">Description</label>
                            <textarea class="form-control" id="item_description" name="item_description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitItemForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Movement Modal -->
    <div class="modal fade" id="movementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Inventory Movement</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="movementForm">
                        <input type="hidden" name="action" value="add_movement">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="movement_item_id" class="form-label">Item *</label>
                                <select class="form-select" id="movement_item_id" name="item_id" required>
                                    <option value="">Select Item</option>
                                    <?php foreach ($items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?> (<?php echo $item['current_stock']; ?> <?php echo $item['unit_of_measure']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="movement_type" class="form-label">Movement Type *</label>
                                <select class="form-select" id="movement_type" name="movement_type" required>
                                    <option value="IN">Stock In (+)</option>
                                    <option value="OUT">Stock Out (-)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reference_id" class="form-label">Reference ID</label>
                                <input type="text" class="form-control" id="reference_id" name="reference_id" placeholder="Order/Sale ID">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Describe the reason for this movement"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitMovementForm()">Record Movement</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Movements Detail Modal -->
    <div class="modal fade" id="movementsDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="movementsModalTitle">Item Movements</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="movementsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        // Item functions
        function openCreateModal() {
            document.getElementById('itemModalTitle').textContent = 'Add Item';
            document.getElementById('itemFormAction').value = 'create_item';
            document.getElementById('itemId').value = '';
            document.getElementById('itemForm').reset();
        }

        function editItem(id) {
            document.getElementById('itemModalTitle').textContent = 'Edit Item';
            document.getElementById('itemFormAction').value = 'update_item';

            fetch('inventory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_item&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('itemId').value = data.id;
                document.getElementById('item_name').value = data.item_name;
                document.getElementById('item_description').value = data.item_description || '';
                document.getElementById('item_category').value = data.item_category || '';
                document.getElementById('unit_of_measure').value = data.unit_of_measure;
                document.getElementById('current_stock').value = data.current_stock;
                document.getElementById('minimum_stock').value = data.minimum_stock;
                document.getElementById('maximum_stock').value = data.maximum_stock;
                document.getElementById('unit_cost').value = data.unit_cost;
                document.getElementById('unit_price').value = data.unit_price;
                document.getElementById('supplier_id').value = data.supplier_id || '';
                document.getElementById('item_status').value = data.item_status;

                new coreui.Modal(document.getElementById('itemModal')).show();
            });
        }

        function deleteItem(id, name) {
            if (confirm('Are you sure you want to delete the item "' + name + '"? This action cannot be undone.')) {
                fetch('inventory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_item&id=' + id
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

        function submitItemForm() {
            const form = document.getElementById('itemForm');
            const formData = new FormData(form);

            fetch('inventory.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('itemModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Movement functions
        function openMovementModal() {
            document.getElementById('movementForm').reset();
        }

        function submitMovementForm() {
            const form = document.getElementById('movementForm');
            const formData = new FormData(form);

            fetch('inventory.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('movementModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // View movements
        function viewMovements(itemId, itemName) {
            document.getElementById('movementsModalTitle').textContent = 'Movements for: ' + itemName;

            fetch('inventory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_movements&item_id=' + itemId
            })
            .then(response => response.json())
            .then(data => {
                let html = '<div class="table-responsive"><table class="table table-hover"><thead class="table-dark"><tr><th>Type</th><th>Quantity</th><th>Reason</th><th>Reference</th><th>Date</th></tr></thead><tbody>';

                if (data.length === 0) {
                    html += '<tr><td colspan="5" class="text-center">No movements found for this item.</td></tr>';
                } else {
                    data.forEach(movement => {
                        html += `<tr>
                            <td><span class="badge bg-${movement.movement_type === 'IN' ? 'success' : 'danger'}">${movement.movement_type}</span></td>
                            <td>${movement.quantity}</td>
                            <td>${movement.reason || 'N/A'}</td>
                            <td>${movement.reference_id || 'N/A'}</td>
                            <td>${new Date(movement.movement_date).toLocaleString()}</td>
                        </tr>`;
                    });
                }

                html += '</tbody></table></div>';
                document.getElementById('movementsContent').innerHTML = html;

                new coreui.Modal(document.getElementById('movementsDetailModal')).show();
            });
        }

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const selectedCategory = this.value.toLowerCase();
            const rows = document.querySelectorAll('.item-row');

            rows.forEach(row => {
                const category = row.getAttribute('data-category').toLowerCase();
                if (selectedCategory === '' || category === selectedCategory) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>