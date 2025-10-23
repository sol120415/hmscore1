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

                case 'adjust_stock':
                    // Adjust stock level directly
                    $stmt = $conn->prepare("UPDATE items SET current_stock=? WHERE id=?");
                    $stmt->execute([$_POST['new_stock'], $_POST['item_id']]);

                    // Record the adjustment as a movement
                    $adjustment = $_POST['new_stock'] - $_POST['current_stock'];
                    if ($adjustment != 0) {
                        $stmt = $conn->prepare("INSERT INTO inventory_movements (item_id, movement_type, quantity, reason, user_id, reference_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['item_id'],
                            $adjustment > 0 ? 'IN' : 'OUT',
                            abs($adjustment),
                            'Stock adjustment',
                            1,
                            'ADJUSTMENT'
                        ]);
                    }
                    echo json_encode(['success' => true, 'message' => 'Stock adjusted successfully']);
                    break;

                case 'delete_movement':
                    // Delete movement and reverse stock change
                    $stmt = $conn->prepare("SELECT * FROM inventory_movements WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $movement = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($movement) {
                        // Reverse the stock change
                        $stock_change = $movement['movement_type'] === 'IN' ? -$movement['quantity'] : $movement['quantity'];
                        $stmt = $conn->prepare("UPDATE items SET current_stock = GREATEST(0, current_stock + ?) WHERE id=?");
                        $stmt->execute([$stock_change, $movement['item_id']]);

                        // Delete the movement
                        $stmt = $conn->prepare("DELETE FROM inventory_movements WHERE id=?");
                        $stmt->execute([$_POST['id']]);
                    }
                    echo json_encode(['success' => true, 'message' => 'Movement deleted and stock reversed']);
                    break;

                case 'bulk_update':
                    // Bulk update items
                    $success_count = 0;
                    foreach ($_POST['items'] as $item_id => $data) {
                        $stmt = $conn->prepare("UPDATE items SET item_status=?, minimum_stock=?, maximum_stock=? WHERE id=?");
                        $stmt->execute([$data['status'], $data['min_stock'], $data['max_stock'], $item_id]);
                        $success_count++;
                    }
                    echo json_encode(['success' => true, 'message' => "$success_count items updated successfully"]);
                    break;

                case 'create_supplier':
                    // Create new supplier
                    $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, city, state, postal_code, country, payment_terms, supplier_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['supplier_name'],
                        $_POST['contact_person'] ?: null,
                        $_POST['email'] ?: null,
                        $_POST['phone'] ?: null,
                        $_POST['address'] ?: null,
                        $_POST['city'] ?: null,
                        $_POST['state'] ?: null,
                        $_POST['postal_code'] ?: null,
                        $_POST['country'] ?: null,
                        $_POST['payment_terms'] ?: null,
                        $_POST['supplier_status']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Supplier created successfully']);
                    break;

                case 'update_supplier':
                    // Update supplier
                    $stmt = $conn->prepare("UPDATE suppliers SET supplier_name=?, contact_person=?, email=?, phone=?, address=?, city=?, state=?, postal_code=?, country=?, payment_terms=?, supplier_status=? WHERE id=?");
                    $stmt->execute([
                        $_POST['supplier_name'],
                        $_POST['contact_person'] ?: null,
                        $_POST['email'] ?: null,
                        $_POST['phone'] ?: null,
                        $_POST['address'] ?: null,
                        $_POST['city'] ?: null,
                        $_POST['state'] ?: null,
                        $_POST['postal_code'] ?: null,
                        $_POST['country'] ?: null,
                        $_POST['payment_terms'] ?: null,
                        $_POST['supplier_status'],
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
                    break;

                case 'delete_supplier':
                    // Delete supplier
                    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
                    break;

                case 'get_supplier':
                    // Get supplier data for editing
                    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($supplier);
                    break;

                case 'get_supplier_items':
                    // Get items for a supplier
                    $stmt = $conn->prepare("SELECT * FROM items WHERE supplier_id=? ORDER BY item_name");
                    $stmt->execute([$_POST['supplier_id']]);
                    $supplier_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($supplier_items);
                    break;

                case 'check_item_duplicate':
                    // Check for duplicate item names
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM items WHERE item_name = ? AND id != ?");
                    $stmt->execute([$_POST['name'], $_POST['exclude_id'] ?: 0]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode(['exists' => $result['count'] > 0]);
                    break;

                case 'check_supplier_duplicate':
                    // Check for duplicate supplier names
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM suppliers WHERE supplier_name = ? AND id != ?");
                    $stmt->execute([$_POST['name'], $_POST['exclude_id'] ?: 0]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode(['exists' => $result['count'] > 0]);
                    break;

                case 'get_movement_trends':
                    // Get movement trends for the last 6 months
                    $trends = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $date = date('Y-m', strtotime("-$i months"));
                        $monthName = date('M Y', strtotime("-$i months"));

                        // Get stock in for this month
                        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM inventory_movements WHERE movement_type = 'IN' AND DATE_FORMAT(movement_date, '%Y-%m') = ?");
                        $stmt->execute([$date]);
                        $stockIn = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                        // Get stock out for this month
                        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM inventory_movements WHERE movement_type = 'OUT' AND DATE_FORMAT(movement_date, '%Y-%m') = ?");
                        $stmt->execute([$date]);
                        $stockOut = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                        $trends['labels'][] = $monthName;
                        $trends['stockIn'][] = (float)$stockIn;
                        $trends['stockOut'][] = (float)$stockOut;
                    }
                    echo json_encode($trends);
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

// Define enums for better data integrity
$item_categories = ['Electronics', 'Furniture', 'Office Supplies', 'Cleaning Supplies', 'Food & Beverage', 'Linens', 'Equipment', 'Maintenance', 'Other'];
$unit_measures = ['pcs' => 'Pieces (pcs)', 'kg' => 'Kilograms (kg)', 'liters' => 'Liters', 'boxes' => 'Boxes', 'sets' => 'Sets', 'meters' => 'Meters', 'feet' => 'Feet', 'gallons' => 'Gallons'];
$item_statuses = ['Active', 'Inactive', 'Discontinued'];
$movement_types = ['IN' => 'Stock In (+)', 'OUT' => 'Stock Out (-)'];
$movement_reasons = ['Purchase', 'Sale', 'Transfer', 'Adjustment', 'Return', 'Loss/Damage', 'Expired', 'Other'];
$supplier_statuses = ['Active', 'Inactive'];
$payment_terms = ['Net 15', 'Net 30', 'Net 45', 'Net 60', 'COD', 'Due on Receipt', 'Prepaid'];
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Hotel Management System</title>

    <!-- CoreUI CSS -->
    <link href="inventorytitle.css" rel="stylesheet">
    <link href="css/coreui.min.css" rel="stylesheet">
    <link href="css/coreui-grid.min.css" rel="stylesheet">
    <link href="css/coreui-reboot.min.css" rel="stylesheet">
    <link href="css/coreui-utilities.min.css" rel="stylesheet">
    <link href="css/coreui-forms.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/all.min.css">

    <!-- HTMX -->
    <script src="js/htmx.min.js"></script>

    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        .inventory-card {
            cursor: pointer;
        }
        .inventory-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .inventory-actions {
            display: none;
        }
        .inventory-card:hover .inventory-actions {
            display: flex;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .item-card {
            transition: opacity 0.3s ease;
        }
        .item-card.hidden {
            display: none;
        }
        .supplier-card:hover .supplier-actions {
            display: flex;
        }
        .supplier-actions {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header with Stats -->

        <div class="mb-4">
            <div class="d-flex justify-content-between gap-3 text-center">
                <div class="flex-grow-1 text-start">
                    <h2>Inventory</h2>
                </div>
                <div>
                    <small class="text-muted d-block">Total Items</small>
                    <span class="fw-bold text-primary"><?php echo $stats['total_items']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Active Items</small>
                    <span class="fw-bold text-success"><?php echo $stats['active_items']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Low Stock</small>
                    <span class="fw-bold text-warning"><?php echo $stats['low_stock_items']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Total Value</small>
                    <span class="fw-bold text-info">₱<?php echo number_format($stats['total_value'] ?: 0, 2); ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Movements</small>
                    <span class="fw-bold text-danger"><?php echo ($stats['total_in'] ?: 0) + ($stats['total_out'] ?: 0); ?></span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="inventoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button" role="tab" aria-controls="items" aria-selected="true">Items</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="movements-tab" data-bs-toggle="tab" data-bs-target="#movements" type="button" role="tab" aria-controls="movements" aria-selected="false">Movements</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="suppliers-tab" data-bs-toggle="tab" data-bs-target="#suppliers" type="button" role="tab" aria-controls="suppliers" aria-selected="false">Suppliers</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab" aria-controls="analytics" aria-selected="false">Analytics</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="inventoryTabContent">
            <!-- Items Tab -->
            <div class="tab-pane fade show active" id="items" role="tabpanel" aria-labelledby="items-tab">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Inventory Items</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm" onclick="generateReport()">
                                <i class="cil-file-pdf me-1"></i>Report
                            </button>
                            <button class="btn btn-info btn-sm" onclick="openBulkOperationsModal()">
                                <i class="cil-list me-1"></i>Bulk Operations
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="openCreateItemModal()">
                                <i class="cil-plus me-1"></i>Add Item
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search items...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Discontinued">Discontinued</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="stockFilter">
                                    <option value="">All Stock Levels</option>
                                    <option value="low">Low Stock</option>
                                    <option value="out">Out of Stock</option>
                                    <option value="normal">Normal</option>
                                </select>
                            </div>
                        </div>

                        <div class="row" id="inventoryContainer">
                            <?php foreach ($items as $item): ?>
                            <div class="col-md-6 col-lg-4 mb-3 item-card" data-category="<?php echo htmlspecialchars($item['item_category'] ?: ''); ?>" data-status="<?php echo htmlspecialchars($item['item_status']); ?>" data-stock-level="<?php echo $item['current_stock'] <= $item['minimum_stock'] ? ($item['current_stock'] == 0 ? 'out' : 'low') : 'normal'; ?>">
                                <div class="card h-100 inventory-card" style="border-left: 4px solid <?php
                                    echo $item['item_status'] === 'Active' ? '#198754' :
                                         ($item['item_status'] === 'Inactive' ? '#6c757d' :
                                         ($item['item_status'] === 'Discontinued' ? '#dc3545' : '#fd7e14'));
                                ?>;">
                                    <div class="card-body">
                                        <div class="inventory-content">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($item['item_category'] ?: 'No category'); ?> • <?php echo htmlspecialchars($item['current_stock']); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?>
                                                    </small>
                                                    <div class="mt-1">
                                                        <small class="text-muted">
                                                            Cost: ₱<?php echo number_format($item['unit_cost'], 2); ?> |
                                                            Price: ₱<?php echo number_format($item['unit_price'], 2); ?> |
                                                            Value: ₱<?php echo number_format($item['current_stock'] * $item['unit_cost'], 2); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge bg-<?php
                                                        echo $item['item_status'] === 'Active' ? 'success' :
                                                             ($item['item_status'] === 'Inactive' ? 'secondary' :
                                                             ($item['item_status'] === 'Discontinued' ? 'danger' : 'warning'));
                                                    ?>">
                                                        <?php echo htmlspecialchars($item['item_status']); ?>
                                                    </span>
                                                    <?php if ($item['current_stock'] <= $item['minimum_stock']): ?>
                                                    <span class="badge bg-<?php echo $item['current_stock'] == 0 ? 'dark' : 'warning'; ?>">
                                                        <?php echo $item['current_stock'] == 0 ? 'Out of Stock' : 'Low Stock'; ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="inventory-actions justify-content-center">
                                            <button class="btn btn-sm btn-outline-primary me-2" onclick="editItem(<?php echo $item['id']; ?>)" title="Edit">
                                                <i class="cil-pencil me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-info me-2" onclick="viewMovements(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')" title="Movements">
                                                <i class="cil-history me-1"></i>Movements
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning me-2" onclick="adjustStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')" title="Adjust Stock">
                                                <i class="cil-calculator me-1"></i>Adjust
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')" title="Remove">
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

            <!-- Movements Tab -->
            <div class="tab-pane fade" id="movements" role="tabpanel" aria-labelledby="movements-tab">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Inventory Movements</h5>
                        <div class="d-flex gap-2">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Reason</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movements as $movement): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($movement['item_name']); ?></td>
                                        <td><span class="badge bg-<?php echo $movement['movement_type'] === 'IN' ? 'success' : 'danger'; ?>"><?php echo $movement['movement_type']; ?></span></td>
                                        <td><?php echo number_format($movement['quantity'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($movement['reason'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($movement['reference_id'] ?: 'N/A'); ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($movement['movement_date'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMovement(<?php echo $movement['id']; ?>)">
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

            <!-- Suppliers Tab -->
            <div class="tab-pane fade" id="suppliers" role="tabpanel" aria-labelledby="suppliers-tab">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Suppliers</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="openCreateSupplierModal()">
                                <i class="cil-plus me-1"></i>Add Supplier
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="suppliersContainer">
                            <?php
                            $suppliers = $conn->query("SELECT * FROM suppliers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
                            if (count($suppliers) > 0):
                                foreach ($suppliers as $supplier): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 supplier-card" style="border-left: 4px solid <?php echo $supplier['supplier_status'] === 'Active' ? '#198754' : '#6c757d'; ?>;">
                                        <div class="card-body">
                                            <div class="supplier-content">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($supplier['supplier_name']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($supplier['contact_person'] ?: 'No contact person'); ?>
                                                        </small>
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($supplier['email'] ?: 'No email'); ?> |
                                                                <?php echo htmlspecialchars($supplier['phone'] ?: 'No phone'); ?>
                                                            </small>
                                                        </div>
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($supplier['city'] . ', ' . $supplier['country']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-<?php echo $supplier['supplier_status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                                            <?php echo htmlspecialchars($supplier['supplier_status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="supplier-actions justify-content-center">
                                                <button class="btn btn-sm btn-outline-primary me-2" onclick="editSupplier(<?php echo $supplier['id']; ?>)" title="Edit">
                                                    <i class="cil-pencil me-1"></i>Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-info me-2" onclick="viewSupplierItems(<?php echo $supplier['id']; ?>, '<?php echo htmlspecialchars($supplier['supplier_name']); ?>')" title="Items">
                                                    <i class="cil-list me-1"></i>Items
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteSupplier(<?php echo $supplier['id']; ?>, '<?php echo htmlspecialchars($supplier['supplier_name']); ?>')" title="Remove">
                                                    <i class="cil-trash me-1"></i>Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach;
                            else: ?>
                                <div class="col-12">
                                    <div class="text-center text-muted py-5">
                                        <i class="cil-building display-4 mb-3"></i>
                                        <h5>Supplier Management</h5>
                                        <p>No suppliers found. Click "Add Supplier" to create your first supplier.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div class="tab-pane fade" id="analytics" role="tabpanel" aria-labelledby="analytics-tab">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Stock Levels Overview</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="stockChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Movement Trends</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="movementChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Inventory Valuation</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Total Items</th>
                                                <th>Total Value</th>
                                                <th>Average Cost</th>
                                                <th>Low Stock Items</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $valuation = $conn->query("
                                                SELECT
                                                    COALESCE(item_category, 'Uncategorized') as category,
                                                    COUNT(*) as item_count,
                                                    SUM(current_stock * unit_cost) as total_value,
                                                    AVG(unit_cost) as avg_cost,
                                                    SUM(CASE WHEN current_stock <= minimum_stock THEN 1 ELSE 0 END) as low_stock_count
                                                FROM items
                                                GROUP BY item_category
                                                ORDER BY total_value DESC
                                            ")->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($valuation as $val): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($val['category']); ?></td>
                                                <td><?php echo $val['item_count']; ?></td>
                                                <td>₱<?php echo number_format($val['total_value'], 2); ?></td>
                                                <td>₱<?php echo number_format($val['avg_cost'], 2); ?></td>
                                                <td><?php echo $val['low_stock_count']; ?></td>
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
        </div>
    </div>

    <!-- Item Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog" style="max-width: 50vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalTitle">Add Item</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="itemForm">
                        <input type="hidden" name="action" id="itemFormAction" value="create_item">
                        <input type="hidden" name="id" id="itemId">

                        <div class="row g-3 align-items-start">
                            <div class="col-lg-7">
                                <div class="rounded-3 border p-3">
                                    <div class="mb-2">
                                        <label class="form-label small">Item Name *</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-tag"></i></span>
                                            <input type="text" class="form-control" id="item_name" name="item_name" placeholder="Item Name *" required>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">Category</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-folder"></i></span>
                                                <select class="form-select" id="item_category" name="item_category">
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($item_categories as $category): ?>
                                                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Unit *</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-balance-scale"></i></span>
                                                <select class="form-select" id="unit_of_measure" name="unit_of_measure" required>
                                                    <option value="">Select Unit</option>
                                                    <?php foreach ($unit_measures as $key => $value): ?>
                                                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($value); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Status *</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-check-circle"></i></span>
                                            <select class="form-select" id="item_status" name="item_status" required>
                                                <option value="">Select Status</option>
                                                <?php foreach ($item_statuses as $status): ?>
                                                <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Description</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-notes"></i></span>
                                            <textarea class="form-control" id="item_description" name="item_description" rows="3" placeholder="Item Description"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="rounded-3 border p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">Current Stock</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-chart"></i></span>
                                                <input type="number" class="form-control" id="current_stock" name="current_stock" min="0" step="0.01" placeholder="0">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Min Stock</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-arrow-down"></i></span>
                                                <input type="number" class="form-control" id="minimum_stock" name="minimum_stock" min="0" step="0.01" placeholder="0">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Max Stock</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-arrow-up"></i></span>
                                                <input type="number" class="form-control" id="maximum_stock" name="maximum_stock" min="0" step="0.01" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">Unit Cost</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control" id="unit_cost" name="unit_cost" placeholder="0.00" min="0" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Unit Price</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control" id="unit_price" name="unit_price" placeholder="0.00" min="0" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Supplier</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-truck"></i></span>
                                            <select class="form-select" id="supplier_id" name="supplier_id">
                                                <option value="">Select Supplier</option>
                                                <?php
                                                $suppliers_query = $conn->query("SELECT id, supplier_name, supplier_status FROM suppliers ORDER BY supplier_name");
                                                $suppliers_list = $suppliers_query->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($suppliers_list as $supplier): ?>
                                                <option value="<?php echo $supplier['id']; ?>" <?php echo $supplier['supplier_status'] === 'Inactive' ? 'style="color: #6c757d;"' : ''; ?>>
                                                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                                    <?php echo $supplier['supplier_status'] === 'Inactive' ? ' (Inactive)' : ''; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
    <div class="modal fade" id="movementModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog" style="max-width: 50vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Inventory Movement</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="movementForm">
                        <input type="hidden" name="action" value="add_movement">

                        <div class="row g-3 align-items-start">
                            <div class="col-lg-7">
                                <div class="rounded-3 border p-3">
                                    <div class="mb-2">
                                        <label class="form-label small">Item *</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-tag"></i></span>
                                            <select class="form-select" id="movement_item_id" name="item_id" required>
                                                <option value="">Select Item</option>
                                                <?php foreach ($items as $item): ?>
                                                <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?> (<?php echo $item['current_stock']; ?> <?php echo $item['unit_of_measure']; ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Reason</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-notes"></i></span>
                                            <select class="form-select" id="reason" name="reason">
                                                <option value="">Select Reason</option>
                                                <?php foreach ($movement_reasons as $reason): ?>
                                                <option value="<?php echo htmlspecialchars($reason); ?>"><?php echo htmlspecialchars($reason); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="rounded-3 border p-3">
                                    <div class="mb-2">
                                        <label class="form-label small">Type *</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-transfer"></i></span>
                                            <select class="form-select" id="movement_type" name="movement_type" required>
                                                <option value="">Select Type</option>
                                                <?php foreach ($movement_types as $key => $value): ?>
                                                <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($value); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Quantity *</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-chart"></i></span>
                                            <input type="number" class="form-control" id="quantity" name="quantity" min="0.01" step="0.01" placeholder="Quantity *" required>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Reference</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-link"></i></span>
                                            <input type="text" class="form-control" id="reference_id" name="reference_id" placeholder="Reference ID (Order/Sale)">
                                        </div>
                                    </div>
                                </div>
                            </div>
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

    <!-- Stock Adjustment Modal -->
    <div class="modal fade" id="stockAdjustmentModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog" style="max-width: 50vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock Level</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="stockAdjustmentForm">
                        <input type="hidden" name="action" value="adjust_stock">
                        <input type="hidden" name="item_id" id="adjustItemId">
                        <input type="hidden" name="current_stock" id="currentStockValue">

                        <div class="row g-3 align-items-start">
                            <div class="col-lg-7">
                                <div class="rounded-3 border p-3">
                                    <div class="alert alert-info mb-2">
                                        <strong>Item:</strong> <span id="adjustItemName"></span><br>
                                        <strong>Current Stock:</strong> <span id="currentStockDisplay"></span>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">New Stock Level *</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-calculator"></i></span>
                                            <input type="number" class="form-control" id="new_stock" name="new_stock" min="0" step="0.01" placeholder="New Stock Level *" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="rounded-3 border p-3">
                                    <label class="form-label small">Reason</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="cil-notes"></i></span>
                                        <textarea class="form-control" id="adjustment_reason" name="adjustment_reason" rows="6" placeholder="Reason for adjustment (e.g., physical count, damaged goods, etc.)"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitStockAdjustment()">Adjust Stock</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Modal -->
    <div class="modal fade" id="supplierModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog" style="max-width: 50vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalTitle">Add Supplier</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="supplierForm">
                        <input type="hidden" name="action" id="supplierFormAction" value="create_supplier">
                        <input type="hidden" name="id" id="supplierId">

                        <div class="row g-3 align-items-start">
                            <div class="col-lg-7">
                                <div class="rounded-3 border p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">Supplier Name *</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-building"></i></span>
                                                <input type="text" class="form-control" id="supplier_name" name="supplier_name" placeholder="Supplier Name *" required>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Contact Person</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-user"></i></span>
                                                <input type="text" class="form-control" id="contact_person" name="contact_person" placeholder="Contact Person">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">Email</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-envelope-closed"></i></span>
                                                <input type="email" class="form-control" id="supplier_email" name="email" placeholder="Email Address">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Phone</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-phone"></i></span>
                                                <input type="tel" class="form-control" id="supplier_phone" name="phone" placeholder="Phone Number">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Street Address</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-home"></i></span>
                                            <textarea class="form-control" id="supplier_address" name="address" rows="2" placeholder="Street Address"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="rounded-3 border p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">City</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-location-pin"></i></span>
                                                <input type="text" class="form-control" id="supplier_city" name="city" placeholder="City">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">State/Province</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-map"></i></span>
                                                <input type="text" class="form-control" id="supplier_state" name="state" placeholder="State/Province">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Postal Code</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-envelope-open"></i></span>
                                                <input type="text" class="form-control" id="supplier_postal_code" name="postal_code" placeholder="Postal Code">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Country</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-globe"></i></span>
                                                <input type="text" class="form-control" id="supplier_country" name="country" placeholder="Country">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">Payment Terms</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-dollar"></i></span>
                                                <select class="form-select" id="payment_terms" name="payment_terms">
                                                    <option value="">Select Payment Terms</option>
                                                    <?php foreach ($payment_terms as $term): ?>
                                                    <option value="<?php echo htmlspecialchars($term); ?>"><?php echo htmlspecialchars($term); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Status *</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-check-circle"></i></span>
                                                <select class="form-select" id="supplier_status" name="supplier_status" required>
                                                    <option value="">Select Status</option>
                                                    <?php foreach ($supplier_statuses as $status): ?>
                                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitSupplierForm()">Save Supplier</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Items Modal -->
    <div class="modal fade" id="supplierItemsModal" tabindex="-1">
        <div class="modal-dialog" style="max-width: 50vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierItemsModalTitle">Supplier Items</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="supplierItemsContent">
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

    <!-- Bulk Operations Modal -->
    <div class="modal fade" id="bulkOperationsModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog" style="max-width: 50vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Operations</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="bulkOperationsForm">
                        <input type="hidden" name="action" value="bulk_update">

                        <div class="row g-3 align-items-start">
                            <div class="col-lg-7">
                                <div class="rounded-3 border p-3">
                                    <label class="form-label small">Select Items</label>
                                    <div class="form-control" style="max-height: 200px; overflow-y: auto;">
                                        <?php foreach ($items as $item): ?>
                                        <div class="form-check">
                                            <input class="form-check-input bulk-item-checkbox" type="checkbox" value="<?php echo $item['id']; ?>" id="bulk_<?php echo $item['id']; ?>">
                                            <label class="form-check-label" for="bulk_<?php echo $item['id']; ?>">
                                                <?php echo htmlspecialchars($item['item_name']); ?> (<?php echo $item['current_stock']; ?> <?php echo $item['unit_of_measure']; ?>)
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="rounded-3 border p-3">
                                    <div class="mb-2">
                                        <label class="form-label small">Status</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="cil-check-circle"></i></span>
                                            <select class="form-select" id="bulk_status" name="bulk_status">
                                                <option value="">No Status Change</option>
                                                <?php foreach ($item_statuses as $status): ?>
                                                <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small">Min Stock</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-arrow-down"></i></span>
                                                <input type="number" class="form-control" id="bulk_min_stock" name="bulk_min_stock" min="0" placeholder="Min Stock">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Max Stock</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="cil-arrow-up"></i></span>
                                                <input type="number" class="form-control" id="bulk_max_stock" name="bulk_max_stock" min="0" placeholder="Max Stock">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitBulkOperations()">Apply Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        // Item functions
        function openCreateItemModal() {
            document.getElementById('itemModalTitle').textContent = 'Add Item';
            document.getElementById('itemFormAction').value = 'create_item';
            document.getElementById('itemId').value = '';
            document.getElementById('itemForm').reset();
            new coreui.Modal(document.getElementById('itemModal')).show();
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
            AppModal.confirm('Are you sure you want to delete the item "' + name + '"? This action cannot be undone.').then(function(yes){ if(!yes) return; 
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
            });
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
            new coreui.Modal(document.getElementById('movementModal')).show();
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

        // Filtering and search functionality
        function filterItems() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const stockFilter = document.getElementById('stockFilter').value;

            const itemCards = document.querySelectorAll('.item-card');

            itemCards.forEach(card => {
                const itemName = card.querySelector('h6').textContent.toLowerCase();
                const category = card.getAttribute('data-category').toLowerCase();
                const status = card.getAttribute('data-status');
                const stockLevel = card.getAttribute('data-stock-level');

                const matchesSearch = itemName.includes(searchTerm);
                const matchesCategory = categoryFilter === '' || category === categoryFilter;
                const matchesStatus = statusFilter === '' || status === statusFilter;
                const matchesStock = stockFilter === '' || stockLevel === stockFilter;

                if (matchesSearch && matchesCategory && matchesStatus && matchesStock) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        // Event listeners for filters
        document.getElementById('searchInput').addEventListener('input', filterItems);
        document.getElementById('categoryFilter').addEventListener('change', filterItems);
        document.getElementById('statusFilter').addEventListener('change', filterItems);
        document.getElementById('stockFilter').addEventListener('change', filterItems);

        // Stock adjustment functions
        function adjustStock(itemId, itemName) {
            // Get current stock
            fetch('inventory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_item&id=' + itemId
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('adjustItemId').value = data.id;
                document.getElementById('adjustItemName').textContent = data.item_name;
                document.getElementById('currentStockValue').value = data.current_stock;
                document.getElementById('currentStockDisplay').textContent = data.current_stock + ' ' + data.unit_of_measure;
                document.getElementById('new_stock').value = data.current_stock;

                new coreui.Modal(document.getElementById('stockAdjustmentModal')).show();
            });
        }

        function submitStockAdjustment() {
            const form = document.getElementById('stockAdjustmentForm');
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
                    new coreui.Modal(document.getElementById('stockAdjustmentModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Bulk operations
        function openBulkOperationsModal() {
            new coreui.Modal(document.getElementById('bulkOperationsModal')).show();
        }

        function submitBulkOperations() {
            const selectedItems = document.querySelectorAll('.bulk-item-checkbox:checked');
            if (selectedItems.length === 0) {
                alert('Please select at least one item');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'bulk_update');

            const items = {};
            selectedItems.forEach(checkbox => {
                const itemId = checkbox.value;
                items[itemId] = {
                    status: document.getElementById('bulk_status').value,
                    min_stock: document.getElementById('bulk_min_stock').value,
                    max_stock: document.getElementById('bulk_max_stock').value
                };
            });
            formData.append('items', JSON.stringify(items));

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
                    new coreui.Modal(document.getElementById('bulkOperationsModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Delete movement
        function deleteMovement(id) {
            AppModal.confirm('Are you sure you want to delete this movement? This will reverse the stock change.').then(function(yes){ if(!yes) return; 
                fetch('inventory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_movement&id=' + id
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

        // Generate report
        function generateReport() {
            window.open('generate_report.php?type=inventory', '_blank');
        }

        // Initialize Bootstrap tabs and other functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts when analytics tab is shown
            const analyticsTab = document.getElementById('analytics-tab');
            if (analyticsTab) {
                analyticsTab.addEventListener('shown.bs.tab', function() {
                    initializeCharts();
                });
            }

            // Initialize form validation and duplicate checking
            initializeFormValidation();
        });

        // Form validation and duplicate checking
        function initializeFormValidation() {
            // Item form validation
            const itemForm = document.getElementById('itemForm');
            if (itemForm) {
                itemForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    validateItemForm();
                });
            }

            // Supplier form validation
            const supplierForm = document.getElementById('supplierForm');
            if (supplierForm) {
                supplierForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    validateSupplierForm();
                });
            }

            // Movement form validation
            const movementForm = document.getElementById('movementForm');
            if (movementForm) {
                movementForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    validateMovementForm();
                });
            }
        }

        // Validate item form
        function validateItemForm() {
            const form = document.getElementById('itemForm');
            const itemName = form.item_name.value.trim();
            const category = form.item_category.value;
            const unitMeasure = form.unit_of_measure.value;
            const status = form.item_status.value;

            // Check for duplicates
            if (itemName) {
                checkItemDuplicate(itemName, form.id.value);
            }

            // Validate required fields
            if (!itemName || !unitMeasure || !status) {
                alert('Please fill in all required fields marked with *');
                return;
            }

            // Submit form
            submitItemForm();
        }

        // Validate supplier form
        function validateSupplierForm() {
            const form = document.getElementById('supplierForm');
            const supplierName = form.supplier_name.value.trim();
            const status = form.supplier_status.value;

            // Check for duplicates
            if (supplierName) {
                checkSupplierDuplicate(supplierName, form.id.value);
            }

            // Validate required fields
            if (!supplierName || !status) {
                alert('Please fill in all required fields marked with *');
                return;
            }

            // Submit form
            submitSupplierForm();
        }

        // Validate movement form
        function validateMovementForm() {
            const form = document.getElementById('movementForm');
            const itemId = form.item_id.value;
            const movementType = form.movement_type.value;
            const quantity = form.quantity.value;

            // Validate required fields
            if (!itemId || !movementType || !quantity || quantity <= 0) {
                alert('Please fill in all required fields and ensure quantity is greater than 0');
                return;
            }

            // Submit form
            submitMovementForm();
        }

        // Check for duplicate items
        function checkItemDuplicate(itemName, excludeId = '') {
            fetch('inventory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=check_item_duplicate&name=' + encodeURIComponent(itemName) + '&exclude_id=' + excludeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    alert('An item with this name already exists. Please choose a different name.');
                    return false;
                }
                return true;
            })
            .catch(error => {
                console.error('Error checking duplicate:', error);
                return true;
            });
        }

        // Check for duplicate suppliers
        function checkSupplierDuplicate(supplierName, excludeId = '') {
            fetch('inventory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=check_supplier_duplicate&name=' + encodeURIComponent(supplierName) + '&exclude_id=' + excludeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    alert('A supplier with this name already exists. Please choose a different name.');
                    return false;
                }
                return true;
            })
            .catch(error => {
                console.error('Error checking duplicate:', error);
                return true;
            });
        }

        // Supplier functions
        function openCreateSupplierModal() {
            document.getElementById('supplierModalTitle').textContent = 'Add Supplier';
            document.getElementById('supplierFormAction').value = 'create_supplier';
            document.getElementById('supplierId').value = '';
            document.getElementById('supplierForm').reset();
            new coreui.Modal(document.getElementById('supplierModal')).show();
        }

        function editSupplier(id) {
            document.getElementById('supplierModalTitle').textContent = 'Edit Supplier';
            document.getElementById('supplierFormAction').value = 'update_supplier';

            fetch('inventory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_supplier&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('supplierId').value = data.id;
                document.getElementById('supplier_name').value = data.supplier_name;
                document.getElementById('contact_person').value = data.contact_person || '';
                document.getElementById('supplier_email').value = data.email || '';
                document.getElementById('supplier_phone').value = data.phone || '';
                document.getElementById('supplier_address').value = data.address || '';
                document.getElementById('supplier_city').value = data.city || '';
                document.getElementById('supplier_state').value = data.state || '';
                document.getElementById('supplier_postal_code').value = data.postal_code || '';
                document.getElementById('supplier_country').value = data.country || '';
                document.getElementById('payment_terms').value = data.payment_terms || '';
                document.getElementById('supplier_status').value = data.supplier_status;

                new coreui.Modal(document.getElementById('supplierModal')).show();
            });
        }

        function deleteSupplier(id, name) {
            AppModal.confirm('Are you sure you want to delete the supplier "' + name + '"? This action cannot be undone.').then(function(yes){ if(!yes) return; 
                fetch('inventory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_supplier&id=' + id
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

        function submitSupplierForm() {
            const form = document.getElementById('supplierForm');
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
                    new coreui.Modal(document.getElementById('supplierModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function viewSupplierItems(supplierId, supplierName) {
            document.getElementById('supplierItemsModalTitle').textContent = 'Items from: ' + supplierName;

            fetch('inventory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_supplier_items&supplier_id=' + supplierId
            })
            .then(response => response.json())
            .then(data => {
                let html = '<div class="table-responsive"><table class="table table-hover"><thead class="table-dark"><tr><th>Item Name</th><th>Category</th><th>Current Stock</th><th>Unit Cost (₱)</th><th>Status</th></tr></thead><tbody>';

                if (data.length === 0) {
                    html += '<tr><td colspan="5" class="text-center">No items found for this supplier.</td></tr>';
                } else {
                    data.forEach(item => {
                        html += `<tr>
                            <td>${item.item_name}</td>
                            <td>${item.item_category || 'N/A'}</td>
                            <td>${item.current_stock} ${item.unit_of_measure}</td>
                            <td>₱${parseFloat(item.unit_cost).toFixed(2)}</td>
                            <td><span class="badge bg-${item.item_status === 'Active' ? 'success' : 'secondary'}">${item.item_status}</span></td>
                        </tr>`;
                    });
                }

                html += '</tbody></table></div>';
                document.getElementById('supplierItemsContent').innerHTML = html;

                new coreui.Modal(document.getElementById('supplierItemsModal')).show();
            });
        }

        function initializeCharts() {
            // Stock levels chart
            const stockCtx = document.getElementById('stockChart');
            if (stockCtx) {
                new Chart(stockCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Normal Stock', 'Low Stock', 'Out of Stock'],
                        datasets: [{
                            data: [
                                <?php echo $stats['total_items'] - $stats['low_stock_items']; ?>,
                                <?php echo $stats['low_stock_items'] - ($conn->query("SELECT COUNT(*) FROM items WHERE current_stock = 0")->fetchColumn()); ?>,
                                <?php echo $conn->query("SELECT COUNT(*) FROM items WHERE current_stock = 0")->fetchColumn(); ?>
                            ],
                            backgroundColor: [
                                'rgba(25, 135, 84, 0.8)',
                                'rgba(255, 193, 7, 0.8)',
                                'rgba(220, 53, 69, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Stock Level Distribution'
                            }
                        }
                    }
                });
            }

            // Movement trends chart - get actual data from last 6 months
            const movementCtx = document.getElementById('movementChart');
            if (movementCtx) {
                // Get movement data for the last 6 months
                fetch('inventory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=get_movement_trends'
                })
                .then(response => response.json())
                .then(data => {
                    new Chart(movementCtx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Stock In',
                                data: data.stockIn,
                                borderColor: 'rgb(25, 135, 84)',
                                tension: 0.1
                            }, {
                                label: 'Stock Out',
                                data: data.stockOut,
                                borderColor: 'rgb(220, 53, 69)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Movement Trends (Last 6 Months)'
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading movement trends:', error);
                    // Fallback to static data
                    new Chart(movementCtx, {
                        type: 'line',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                            datasets: [{
                                label: 'Stock In',
                                data: [50, 75, 60, 80, 90, 70],
                                borderColor: 'rgb(25, 135, 84)',
                                tension: 0.1
                            }, {
                                label: 'Stock Out',
                                data: [40, 60, 55, 70, 65, 80],
                                borderColor: 'rgb(220, 53, 69)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Movement Trends'
                                }
                            }
                        }
                    });
                });
            }
        }
    </script>
</body>
</html>