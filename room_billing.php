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
                    // Create new billing transaction
                    $stmt = $conn->prepare("INSERT INTO room_billing (transaction_type, reservation_id, room_id, guest_id, item_description, quantity, unit_price, total_amount, payment_amount, balance, payment_method, billing_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['transaction_type'],
                        $_POST['reservation_id'] ?: null,
                        $_POST['room_id'] ?: null,
                        $_POST['guest_id'] ?: null,
                        $_POST['item_description'] ?: null,
                        $_POST['quantity'] ?: 1,
                        $_POST['unit_price'] ?: 0.00,
                        $_POST['total_amount'] ?: 0.00,
                        $_POST['payment_amount'] ?: 0.00,
                        $_POST['balance'] ?: 0.00,
                        $_POST['payment_method'],
                        $_POST['billing_status'],
                        $_POST['notes'] ?: null
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Billing transaction created successfully']);
                    break;

                case 'update':
                    // Update billing transaction
                    $stmt = $conn->prepare("UPDATE room_billing SET transaction_type=?, reservation_id=?, room_id=?, guest_id=?, item_description=?, quantity=?, unit_price=?, total_amount=?, payment_amount=?, balance=?, payment_method=?, billing_status=?, notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['transaction_type'],
                        $_POST['reservation_id'] ?: null,
                        $_POST['room_id'] ?: null,
                        $_POST['guest_id'] ?: null,
                        $_POST['item_description'] ?: null,
                        $_POST['quantity'] ?: 1,
                        $_POST['unit_price'] ?: 0.00,
                        $_POST['total_amount'] ?: 0.00,
                        $_POST['payment_amount'] ?: 0.00,
                        $_POST['balance'] ?: 0.00,
                        $_POST['payment_method'],
                        $_POST['billing_status'],
                        $_POST['notes'] ?: null,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Billing transaction updated successfully']);
                    break;

                case 'delete':
                    // Delete billing transaction
                    $stmt = $conn->prepare("DELETE FROM room_billing WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Billing transaction deleted successfully']);
                    break;

                case 'get':
                    // Get billing data for editing
                    $stmt = $conn->prepare("SELECT * FROM room_billing WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $billing = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($billing);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get data for display
$billings = $conn->query("
    SELECT rb.*, r.id as reservation_id_display, r.reservation_type, g.first_name, g.last_name, rm.room_number
    FROM room_billing rb
    LEFT JOIN reservations r ON rb.reservation_id = r.id
    LEFT JOIN guests g ON rb.guest_id = g.id
    LEFT JOIN rooms rm ON rb.room_id = rm.id
    ORDER BY rb.transaction_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$reservations = $conn->query("SELECT id, reservation_type FROM reservations ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$guests = $conn->query("SELECT id, first_name, last_name FROM guests ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $conn->query("SELECT id, room_number, room_type FROM rooms ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        COUNT(*) as total_transactions,
        COUNT(CASE WHEN billing_status = 'Paid' THEN 1 END) as paid_transactions,
        COUNT(CASE WHEN billing_status = 'Pending' THEN 1 END) as pending_transactions,
        SUM(payment_amount) as total_payments,
        SUM(balance) as total_outstanding,
        COUNT(CASE WHEN transaction_type = 'Room Charge' THEN 1 END) as room_charges,
        COUNT(CASE WHEN transaction_type = 'Event Charge' THEN 1 END) as event_charges
    FROM room_billing
")->fetch(PDO::FETCH_ASSOC);

// Get recent transactions (last 10)
$recentTransactions = array_slice($billings, 0, 10);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Billing - Hotel Management System</title>

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
        .billing-card {
            transition: transform 0.2s;
        }
        .billing-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Room Billing</h2>
                <p class="text-muted mb-0">Manage room charges, payments, and billing transactions</p>
            </div>
            <button class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#billingModal" onclick="openCreateModal()">
                <i class="cil-plus me-2"></i>Add Transaction
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Transactions</h6>
                                <h3 class="mb-0"><?php echo $stats['total_transactions']; ?></h3>
                            </div>
                            <i class="cil-dollar fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Paid</h6>
                                <h3 class="mb-0"><?php echo $stats['paid_transactions']; ?></h3>
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
                                <h3 class="mb-0"><?php echo $stats['pending_transactions']; ?></h3>
                            </div>
                            <i class="cil-clock fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Payments</h6>
                                <h3 class="mb-0">$<?php echo number_format($stats['total_payments'] ?: 0, 2); ?></h3>
                            </div>
                            <i class="cil-money fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Outstanding Balance</h6>
                                <h3 class="mb-0">$<?php echo number_format($stats['total_outstanding'] ?: 0, 2); ?></h3>
                            </div>
                            <i class="cil-balance-scale fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Billing Transactions Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Billing Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Transaction ID</th>
                                <th>Type</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Balance</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($billings as $billing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($billing['id']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $billing['transaction_type'] === 'Room Charge' ? 'primary' : 'info'; ?>">
                                        <?php echo htmlspecialchars($billing['transaction_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(($billing['first_name'] ?: '') . ' ' . ($billing['last_name'] ?: 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars($billing['room_number'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($billing['item_description'] ?: 'N/A'); ?></td>
                                <td>$<?php echo number_format($billing['total_amount'], 2); ?></td>
                                <td>$<?php echo number_format($billing['payment_amount'], 2); ?></td>
                                <td>$<?php echo number_format($billing['balance'], 2); ?></td>
                                <td><?php echo htmlspecialchars($billing['payment_method']); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $billing['billing_status'] === 'Paid' ? 'success' :
                                             ($billing['billing_status'] === 'Pending' ? 'warning' :
                                             ($billing['billing_status'] === 'Failed' ? 'danger' : 'secondary'));
                                    ?>">
                                        <?php echo htmlspecialchars($billing['billing_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($billing['transaction_date'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editBilling(<?php echo $billing['id']; ?>)">
                                        <i class="cil-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBilling(<?php echo $billing['id']; ?>)">
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

        <!-- Recent Transactions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recentTransactions as $billing): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card billing-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0">#<?php echo $billing['id']; ?></h6>
                                    <span class="badge bg-<?php
                                        echo $billing['billing_status'] === 'Paid' ? 'success' :
                                             ($billing['billing_status'] === 'Pending' ? 'warning' : 'danger');
                                    ?>">
                                        <?php echo htmlspecialchars($billing['billing_status']); ?>
                                    </span>
                                </div>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($billing['transaction_type']); ?></p>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Amount</small>
                                        <span class="fw-bold">$<?php echo number_format($billing['total_amount'], 2); ?></span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Payment</small>
                                        <span class="fw-bold">$<?php echo number_format($billing['payment_amount'], 2); ?></span>
                                    </div>
                                </div>
                                <hr>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(($billing['first_name'] ?: '') . ' ' . ($billing['last_name'] ?: '')); ?>
                                    • Room <?php echo htmlspecialchars($billing['room_number'] ?: 'N/A'); ?>
                                    • <?php echo date('M d, Y', strtotime($billing['transaction_date'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing Modal -->
    <div class="modal fade" id="billingModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Billing Transaction</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="billingForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="billingId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="transaction_type" class="form-label">Transaction Type *</label>
                                <select class="form-select" id="transaction_type" name="transaction_type" required>
                                    <option value="Room Charge">Room Charge</option>
                                    <option value="Event Charge">Event Charge</option>
                                    <option value="Refund">Refund</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="billing_status" class="form-label">Status *</label>
                                <select class="form-select" id="billing_status" name="billing_status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Failed">Failed</option>
                                    <option value="Refunded">Refunded</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reservation_id" class="form-label">Reservation</label>
                                <select class="form-select" id="reservation_id" name="reservation_id">
                                    <option value="">Select Reservation</option>
                                    <?php foreach ($reservations as $reservation): ?>
                                    <option value="<?php echo $reservation['id']; ?>"><?php echo htmlspecialchars($reservation['id'] . ' (' . $reservation['reservation_type'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="guest_id" class="form-label">Guest</label>
                                <select class="form-select" id="guest_id" name="guest_id">
                                    <option value="">Select Guest</option>
                                    <?php foreach ($guests as $guest): ?>
                                    <option value="<?php echo $guest['id']; ?>"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_id" class="form-label">Room</label>
                                <select class="form-select" id="room_id" name="room_id">
                                    <option value="">Select Room</option>
                                    <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['room_type']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="item_description" class="form-label">Item Description</label>
                            <input type="text" class="form-control" id="item_description" name="item_description" placeholder="e.g., Room service, Mini bar, etc.">
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="unit_price" class="form-label">Unit Price</label>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" min="0" step="0.01">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="total_amount" class="form-label">Total Amount *</label>
                                <input type="number" class="form-control" id="total_amount" name="total_amount" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="payment_amount" class="form-label">Payment Amount</label>
                                <input type="number" class="form-control" id="payment_amount" name="payment_amount" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="balance" class="form-label">Balance</label>
                            <input type="number" class="form-control" id="balance" name="balance" step="0.01" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitBillingForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        // Auto-calculate balance
        function calculateBalance() {
            const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
            const balance = totalAmount - paymentAmount;
            document.getElementById('balance').value = balance.toFixed(2);
        }

        // Add event listeners for auto-calculation
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('total_amount').addEventListener('input', calculateBalance);
            document.getElementById('payment_amount').addEventListener('input', calculateBalance);
        });

        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add Billing Transaction';
            document.getElementById('formAction').value = 'create';
            document.getElementById('billingId').value = '';
            document.getElementById('billingForm').reset();
            document.getElementById('balance').value = '0.00';
        }

        function editBilling(id) {
            document.getElementById('modalTitle').textContent = 'Edit Billing Transaction';
            document.getElementById('formAction').value = 'update';

            // Fetch billing data
            fetch('room_billing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('billingId').value = data.id;
                document.getElementById('transaction_type').value = data.transaction_type;
                document.getElementById('reservation_id').value = data.reservation_id || '';
                document.getElementById('room_id').value = data.room_id || '';
                document.getElementById('guest_id').value = data.guest_id || '';
                document.getElementById('item_description').value = data.item_description || '';
                document.getElementById('quantity').value = data.quantity;
                document.getElementById('unit_price').value = data.unit_price;
                document.getElementById('total_amount').value = data.total_amount;
                document.getElementById('payment_amount').value = data.payment_amount;
                document.getElementById('balance').value = data.balance;
                document.getElementById('payment_method').value = data.payment_method;
                document.getElementById('billing_status').value = data.billing_status;
                document.getElementById('notes').value = data.notes || '';

                new coreui.Modal(document.getElementById('billingModal')).show();
            });
        }

        function deleteBilling(id) {
            if (confirm('Are you sure you want to delete this billing transaction? This action cannot be undone.')) {
                fetch('room_billing.php', {
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

        function submitBillingForm() {
            const form = document.getElementById('billingForm');
            const formData = new FormData(form);

            fetch('room_billing.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('billingModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>