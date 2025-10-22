<?php
include_once 'db.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

// Handle GET requests for PDF export
if (isset($_GET['action']) && $_GET['action'] === 'export_pdf') {
    $date = $_GET['date'];
    $stmt = $conn->prepare("SELECT * FROM event_billing WHERE DATE(transaction_date) = ? ORDER BY transaction_date DESC");
    $stmt->execute([$date]);
    $billings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once 'vendor/autoload.php';
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new \Dompdf\Dompdf($options);

    $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            h1 { text-align: center; }
        </style>
    </head>
    <body>
        <h1>Event Billing Report - ' . $date . '</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Transaction Type</th>
                    <th>Payment Amount</th>
                    <th>Balance</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($billings as $billing) {
        $html .= '<tr>
            <td>' . htmlspecialchars($billing['id']) . '</td>
            <td>' . htmlspecialchars($billing['transaction_type']) . '</td>
            <td>₱' . number_format($billing['payment_amount'], 2) . '</td>
            <td>₱' . number_format($billing['balance'], 2) . '</td>
            <td>' . htmlspecialchars($billing['payment_method']) . '</td>
            <td>' . htmlspecialchars($billing['billing_status']) . '</td>
            <td>' . htmlspecialchars(date('Y-m-d H:i', strtotime($billing['transaction_date']))) . '</td>
        </tr>';
    }

    $html .= '
            </tbody>
        </table>
    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('event_billing_report_' . $date . '.pdf');
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
                    $stmt = $conn->prepare("INSERT INTO event_billing (transaction_type, reservation_id, venue_id, payment_amount, balance, payment_method, billing_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['transaction_type'],
                        $_POST['reservation_id'] ?: null,
                        $_POST['venue_id'] ?: null,
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
                    $stmt = $conn->prepare("UPDATE event_billing SET transaction_type=?, reservation_id=?, venue_id=?, payment_amount=?, balance=?, payment_method=?, billing_status=?, notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['transaction_type'],
                        $_POST['reservation_id'] ?: null,
                        $_POST['venue_id'] ?: null,
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
                    $stmt = $conn->prepare("DELETE FROM event_billing WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Billing transaction deleted successfully']);
                    break;


                case 'get':
                    // Get billing data for editing
                    $stmt = $conn->prepare("SELECT * FROM event_billing WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $billing = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($billing);
                    break;

                case 'filter':
                    // Filter billing history by date
                    $date = $_POST['date'];
                    $stmt = $conn->prepare("SELECT * FROM event_billing WHERE DATE(transaction_date) = ? ORDER BY transaction_date DESC");
                    $stmt->execute([$date]);
                    $filteredBillings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($filteredBillings as $billing): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($billing['id']); ?></td>
                            <td><?php echo htmlspecialchars($billing['transaction_type']); ?></td>
                            <td>₱<?php echo number_format($billing['payment_amount'], 2); ?></td>
                            <td>₱<?php echo number_format($billing['balance'], 2); ?></td>
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
                            <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($billing['transaction_date']))); ?></td>
                        </tr>
                    <?php endforeach;
                    break;

            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


// Get pending billings from event reservations (exclude those with paid billing)
$billings = $conn->query("
    SELECT er.*, ev.id as venue_id, ev.venue_name, ev.venue_rate, (er.event_hour_count * ev.venue_rate) as calculated_balance
    FROM event_reservation er
    LEFT JOIN event_venues ev ON er.event_venue_id = ev.id
    WHERE er.event_status IN ('Checked Out', 'Archived') AND NOT EXISTS (
        SELECT 1
        FROM event_billing eb
        WHERE eb.reservation_id = er.id AND eb.billing_status = 'Paid'
    )
    ORDER BY er.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$reservations = $conn->query("SELECT id, event_title FROM event_reservation ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$venues = $conn->query("SELECT id, venue_name, venue_rate FROM event_venues ORDER BY venue_name")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        COUNT(CASE WHEN billing_status = 'Paid' THEN 1 END) as paid_transactions,
        COUNT(CASE WHEN billing_status = 'Pending' THEN 1 END) as pending_transactions,
        COUNT(CASE WHEN transaction_type = 'Event Charge' THEN 1 END) as event_charges,
        COUNT(CASE WHEN transaction_type = 'Venue Charge' THEN 1 END) as venue_charges
    FROM event_billing
")->fetch(PDO::FETCH_ASSOC);

// Get recent transactions (last 10)
$recentTransactions = array_slice($billings, 0, 10);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Billing - Hotel Management System</title>

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
        .billing-card {
            cursor: pointer;
        }
        .billing-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .billing-actions {
            display: none;
        }
        .billing-card:hover .billing-actions {
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
                <?php include 'eventstitle.html'; ?>
                </div>
                <div>
                    <small class="text-muted d-block">Paid</small>
                    <span class="fw-bold text-success"><?php echo $stats['paid_transactions']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Pending</small>
                    <span class="fw-bold text-warning"><?php echo $stats['pending_transactions']; ?></span>
                </div>
            </div>
        </div>

        <!-- Event Billing -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Event Billing</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-secondary btn-sm" onclick="window.location.href='?page=events'">
                        <i class="cil-arrow-left me-1"></i>Back to Events
                    </button>
                    <button class="btn btn-success btn-sm" onclick="openReportModal()">
                        <i class="cil-file-pdf me-1"></i>Report
                    </button>

                </div>
            </div>
            <div class="card-body">
                <div class="row" id="billingContainer">
                    <?php foreach ($billings as $billing): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 billing-card" style="border-left: 4px solid #fd7e14;">
                            <div class="card-body">
                                <div class="billing-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Event #<?php echo htmlspecialchars($billing['id']); ?> - <?php echo htmlspecialchars($billing['event_title']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($billing['event_organizer']); ?> • Venue <?php echo htmlspecialchars($billing['venue_name'] ?: 'N/A'); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-warning">Pending</span>
                                            <small class="text-muted">Balance: ₱<?php echo number_format($billing['calculated_balance'], 2); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="billing-actions justify-content-center">
                                    <button class="btn btn-sm btn-outline-primary" data-coreui-toggle="modal" data-coreui-target="#billingModal" onclick="openBillingModal(<?php echo $billing['id']; ?>, <?php echo $billing['calculated_balance']; ?>, '<?php echo htmlspecialchars($billing['venue_name']); ?>', '<?php echo htmlspecialchars($billing['event_organizer']); ?>', <?php echo $billing['venue_id']; ?>)" title="Create Billing">
                                        <i class="cil-plus me-1"></i>Create Billing
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Billing Transaction History -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Billing Transaction History</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="filterToday()">Today</button>
                    <input type="date" class="form-control form-control-sm" id="dateFilter" style="width: 150px;">
                    <button class="btn btn-sm btn-primary" onclick="filterByDate()">Filter</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="billingHistoryTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Transaction Type</th>
                                <th>Payment Amount</th>
                                <th>Balance</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="billingHistoryBody">
                            <?php
                            $billingHistory = $conn->query("SELECT * FROM event_billing ORDER BY transaction_date DESC")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($billingHistory as $billing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($billing['id']); ?></td>
                                <td><?php echo htmlspecialchars($billing['transaction_type']); ?></td>
                                <td>₱<?php echo number_format($billing['payment_amount'], 2); ?></td>
                                <td>₱<?php echo number_format($billing['balance'], 2); ?></td>
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
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($billing['transaction_date']))); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing Modal -->
    <div class="modal fade" id="billingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Billing Transaction</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="billingForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="billingId">

                        <input type="hidden" name="transaction_type" value="Event Charge">
                        <input type="hidden" name="billing_status" value="Paid">

                        <div class="mb-3">
                            <label class="form-label">Description: <span id="billing_description_display"></span></label>
                        </div>

                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method *</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="GCash">GCash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <input type="hidden" id="reservation_id" name="reservation_id">
                        <input type="hidden" id="venue_id" name="venue_id">

                        <div class="mb-3">
                            <label for="payment_amount" class="form-label">Payment Amount *</label>
                            <input type="number" class="form-control" id="payment_amount" name="payment_amount" min="0" step="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Balance: <span id="balance_display">₱0.00</span> | Change: <span id="change_display">₱0.00</span></label>
                            <input type="hidden" id="balance" name="balance" name="balance" step="0.01" readonly>
                            <input type="hidden" id="change" name="change" step="0.01" readonly>
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

    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Billing Report</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reportDate" class="form-label">Select Date</label>
                        <input type="date" class="form-control" id="reportDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="generateReport()">Generate PDF</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        // Calculate change
        function calculateChange() {
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
            const balance = parseFloat(document.getElementById('balance').value) || 0;
            if (paymentAmount > balance) {
                const change = paymentAmount - balance;
                document.getElementById('change').value = change.toFixed(2);
                document.getElementById('change_display').textContent = '₱' + change.toFixed(2);
            } else {
                document.getElementById('change').value = '0.00';
                document.getElementById('change_display').textContent = '₱0.00';
            }
        }

        // Add event listeners for auto-calculation
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('payment_amount').addEventListener('input', function() {
                calculateChange();
            });
        });

        function openReportModal() {
            new coreui.Modal(document.getElementById('reportModal')).show();
        }

        function generateReport() {
            const date = document.getElementById('reportDate').value;
            if (!date) {
                alert('Please select a date');
                return;
            }

            // Open PDF in new window/tab
            window.open('event_billing.php?action=export_pdf&date=' + date + '&HX-Request=true', '_blank');

            new coreui.Modal(document.getElementById('reportModal')).hide();
        }

        function openBillingModal(reservationId, calculatedBalance, venueName, organizerName, venueId) {
            document.getElementById('modalTitle').textContent = 'Create Event Billing';
            document.getElementById('formAction').value = 'create';
            document.getElementById('billingId').value = '';
            document.getElementById('billingForm').reset();

            // Pre-fill form with reservation data
            document.getElementById('reservation_id').value = reservationId;
            document.getElementById('venue_id').value = venueId;
            document.getElementById('balance').value = calculatedBalance.toFixed(2);
            document.getElementById('balance_display').textContent = '₱' + calculatedBalance.toFixed(2);
            document.getElementById('billing_description_display').textContent = 'Event charge for ' + venueName + ' - ' + organizerName;
            // document.getElementById('payment_amount').value = '';
            calculateChange();

            new coreui.Modal(document.getElementById('billingModal')).show();
        }

        function editBilling(id) {
            document.getElementById('modalTitle').textContent = 'Edit Billing Transaction';
            document.getElementById('formAction').value = 'update';

            // Fetch billing data
            fetch('event_billing.php', {
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
                document.getElementById('reservation_id').value = data.reservation_id || '';
                document.getElementById('venue_id').value = data.venue_id || '';
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
                fetch('event_billing.php', {
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
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
            const balance = parseFloat(document.getElementById('balance').value) || 0;

            if (paymentAmount < balance) {
                alert('Payment amount cannot be less than balance');
                return;
            }

            const form = document.getElementById('billingForm');
            const formData = new FormData(form);

            fetch('event_billing.php', {
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

        function filterToday() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dateFilter').value = today;
            filterByDate();
        }

        function filterByDate() {
            const selectedDate = document.getElementById('dateFilter').value;
            if (!selectedDate) {
                alert('Please select a date');
                return;
            }

            fetch('event_billing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=filter&date=' + selectedDate
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('billingHistoryBody').innerHTML = html;
            });
        }
    </script>
</body>
</html>