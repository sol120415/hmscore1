<?php
include_once 'db.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

// Handle GET requests for PDF export
if (isset($_GET['action']) && $_GET['action'] === 'export_pdf') {
    $date = $_GET['date'];
    $stmt = $conn->prepare("SELECT * FROM room_billing WHERE DATE(transaction_date) = ? ORDER BY transaction_date DESC");
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
        <h1>Billing Report - ' . $date . '</h1>
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
    $dompdf->stream('billing_report_' . $date . '.pdf');
    exit;
}

// Handle GET requests for invoice PDF
if (isset($_GET['action']) && $_GET['action'] === 'generate_invoice') {
    $billingId = $_GET['billing_id'];

    // Get billing details with related data
    $stmt = $conn->prepare("
        SELECT rb.*, r.room_number, r.room_rate, res.reservation_type, res.reservation_hour_count,
               g.first_name, g.last_name, g.email, g.id_type, g.id_number, g.loyalty_status, g.stay_count
        FROM room_billing rb
        LEFT JOIN reservations res ON rb.reservation_id = res.id
        LEFT JOIN rooms r ON rb.room_id = r.id
        LEFT JOIN guests g ON res.guest_id = g.id
        WHERE rb.id = ?
    ");
    $stmt->execute([$billingId]);
    $billing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$billing) {
        die('Invoice not found');
    }

    require_once 'vendor/autoload.php';
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new \Dompdf\Dompdf($options);

    // Calculate original amount and discounts
    $hours = $billing['reservation_hour_count'] ?: 8;
    $originalAmount = ($hours / 8) * ($billing['room_rate'] ?: 0);

    // Calculate discount
    $discountPercent = 0;
    $tierLabel = 'Regular';
    if (!empty($billing['stay_count'])) {
        if ($billing['stay_count'] >= 50) {
            $discountPercent = 25;
            $tierLabel = 'Diamond';
        } elseif ($billing['stay_count'] >= 20) {
            $discountPercent = 15;
            $tierLabel = 'Gold';
        } elseif ($billing['stay_count'] >= 5) {
            $discountPercent = 10;
            $tierLabel = 'Iron';
        }
    }

    $discountedAmount = $originalAmount * (1 - $discountPercent / 100);
    $change = $billing['payment_amount'] - $billing['balance'];

    $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
            .invoice-details { margin-bottom: 20px; }
            .guest-details { margin-bottom: 20px; }
            .billing-details { margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .total { font-weight: bold; background-color: #e9ecef; }
            .discount { color: #28a745; }
            .change { color: #007bff; }
            .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
            .summary { margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Hotel Invoice</h1>
            <h2>GrokFast Hotel Management</h2>
            <p>Invoice #' . htmlspecialchars($billing['id']) . ' | Date: ' . htmlspecialchars(date('Y-m-d H:i', strtotime($billing['transaction_date']))) . '</p>
        </div>

        <div class="guest-details">
            <h3>Guest Information</h3>
            <table>
                <tr>
                    <td width="30%"><strong>Name:</strong></td>
                    <td>' . htmlspecialchars(($billing['first_name'] ?: '') . ' ' . ($billing['last_name'] ?: '')) . '</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>' . htmlspecialchars($billing['email'] ?: 'N/A') . '</td>
                </tr>
                <tr>
                    <td><strong>ID:</strong></td>
                    <td>' . htmlspecialchars(($billing['id_type'] ?: '') . ' - ' . ($billing['id_number'] ?: '')) . '</td>
                </tr>
                <tr>
                    <td><strong>Loyalty Tier:</strong></td>
                    <td>' . htmlspecialchars($tierLabel) . ' (' . $billing['stay_count'] . ' stays)</td>
                </tr>
            </table>
        </div>

        <div class="billing-details">
            <h3>Reservation Details</h3>
            <table>
                <tr>
                    <td width="30%"><strong>Room:</strong></td>
                    <td>Room ' . htmlspecialchars($billing['room_number'] ?: 'N/A') . '</td>
                </tr>
                <tr>
                    <td><strong>Reservation Type:</strong></td>
                    <td>' . htmlspecialchars($billing['reservation_type'] ?: 'N/A') . '</td>
                </tr>
                <tr>
                    <td><strong>Duration:</strong></td>
                    <td>' . htmlspecialchars($hours) . ' hours</td>
                </tr>
                <tr>
                    <td><strong>Payment Method:</strong></td>
                    <td>' . htmlspecialchars($billing['payment_method']) . '</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>' . htmlspecialchars($billing['billing_status']) . '</td>
                </tr>
            </table>
        </div>

        <div class="summary">
            <h3>Payment Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Room Charge - ' . htmlspecialchars($hours) . ' hours @ ₱' . number_format($billing['room_rate'], 2) . '/night</td>
                        <td style="text-align: right;">₱' . number_format($originalAmount, 2) . '</td>
                    </tr>';

    if ($discountPercent > 0) {
        $html .= '<tr class="discount">
                        <td>Loyalty Discount (' . $discountPercent . '% - ' . $tierLabel . ')</td>
                        <td style="text-align: right;">-₱' . number_format($originalAmount * ($discountPercent / 100), 2) . '</td>
                    </tr>';
    }

    $html .= '<tr class="total">
                        <td><strong>Subtotal</strong></td>
                        <td style="text-align: right;"><strong>₱' . number_format($discountedAmount, 2) . '</strong></td>
                    </tr>
                    <tr>
                        <td>Amount Paid</td>
                        <td style="text-align: right;">₱' . number_format($billing['payment_amount'], 2) . '</td>
                    </tr>';

    if ($change > 0) {
        $html .= '<tr class="change">
                        <td>Change</td>
                        <td style="text-align: right;">₱' . number_format($change, 2) . '</td>
                    </tr>';
    }

    $html .= '<tr class="total">
                        <td><strong>Balance</strong></td>
                        <td style="text-align: right;"><strong>₱' . number_format($billing['balance'], 2) . '</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>';

    if (!empty($billing['notes'])) {
        $html .= '<div style="margin-top: 20px;">
            <h4>Notes</h4>
            <p>' . htmlspecialchars($billing['notes']) . '</p>
        </div>';
    }

    $html .= '<div class="footer">
            <p>Thank you for choosing GrokFast Hotel Management!</p>
            <p>This is a computer-generated invoice. No signature required.</p>
        </div>
    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Output PDF to browser in new tab (inline display)
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="invoice_' . $billingId . '.pdf"');
    echo $dompdf->output();
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
                    $stmt = $conn->prepare("INSERT INTO room_billing (transaction_type, reservation_id, room_id, payment_amount, balance, payment_method, billing_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['transaction_type'],
                        $_POST['reservation_id'] ?: null,
                        $_POST['room_id'] ?: null,
                        $_POST['payment_amount'] ?: 0.00,
                        $_POST['balance'] ?: 0.00,
                        $_POST['payment_method'],
                        $_POST['billing_status'],
                        $_POST['notes'] ?: null
                    ]);
                    $billingId = $conn->lastInsertId();
                    echo json_encode(['success' => true, 'message' => 'Billing transaction created successfully', 'billing_id' => $billingId]);
                    break;

                case 'update':
                    // Update billing transaction
                    $stmt = $conn->prepare("UPDATE room_billing SET transaction_type=?, reservation_id=?, room_id=?, payment_amount=?, balance=?, payment_method=?, billing_status=?, notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['transaction_type'],
                        $_POST['reservation_id'] ?: null,
                        $_POST['room_id'] ?: null,
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

                case 'filter':
                    // Filter billing history by date
                    $date = $_POST['date'];
                    $stmt = $conn->prepare("SELECT * FROM room_billing WHERE DATE(transaction_date) = ? ORDER BY transaction_date DESC");
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


// Get pending billings from reservations (exclude those with paid billing)
$billings = $conn->query("
    SELECT r.*, rm.id as room_id, rm.room_number, rm.room_rate, g.first_name, g.last_name, g.loyalty_status,
           (r.reservation_hour_count / 8 * rm.room_rate) as calculated_balance,
           CASE
               WHEN g.stay_count >= 50 THEN (r.reservation_hour_count / 8 * rm.room_rate) * 0.75
               WHEN g.stay_count >= 20 THEN (r.reservation_hour_count / 8 * rm.room_rate) * 0.85
               WHEN g.stay_count >= 5 THEN (r.reservation_hour_count / 8 * rm.room_rate) * 0.90
               ELSE (r.reservation_hour_count / 8 * rm.room_rate) * 1.0
           END as discounted_balance
    FROM reservations r
    LEFT JOIN rooms rm ON r.room_id = rm.id
    LEFT JOIN guests g ON r.guest_id = g.id
    WHERE NOT EXISTS (
        SELECT 1
        FROM room_billing rb
        WHERE rb.reservation_id = r.id AND rb.billing_status = 'Paid'
    )
    ORDER BY r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$reservations = $conn->query("SELECT id, reservation_type FROM reservations ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$guests = $conn->query("SELECT id, first_name, last_name FROM guests ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $conn->query("SELECT id, room_number, room_type FROM rooms ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        COUNT(CASE WHEN billing_status = 'Paid' THEN 1 END) as paid_transactions,
        COUNT(CASE WHEN billing_status = 'Pending' THEN 1 END) as pending_transactions,
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
                <div class="flex-grow-1 text-start">
                    <h2>Rooms Billing</h2>
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

        <!-- Room Billing -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Room Billing</h5>
                <div class="d-flex gap-2">
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
                                            <h6 class="mb-1">Reservation #<?php echo htmlspecialchars($billing['id']); ?> - <?php echo htmlspecialchars($billing['reservation_type']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(($billing['first_name'] ?: '') . ' ' . ($billing['last_name'] ?: 'N/A')); ?> • Room <?php echo htmlspecialchars($billing['room_number'] ?: 'N/A'); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-warning">Pending</span>
                                            <small class="text-muted">Balance: ₱<?php echo number_format($billing['discounted_balance'], 2); ?></small>
                                            <?php
                                                $discountPercent = 0;
                                                if (!empty($billing['calculated_balance']) && $billing['calculated_balance'] > 0) {
                                                    $discountPercent = round((1 - ($billing['discounted_balance'] / $billing['calculated_balance'])) * 100);
                                                }
                                                if ($discountPercent > 0):
                                                    $tierLabel = '';
                                                    if (isset($billing['loyalty_status']) && trim($billing['loyalty_status']) !== '' && strtolower(trim($billing['loyalty_status'])) !== 'regular') {
                                                        $tierLabel = trim($billing['loyalty_status']);
                                                    } else {
                                                        switch ($discountPercent) {
                                                            case 25:
                                                                $tierLabel = 'Diamond';
                                                                break;
                                                            case 15:
                                                                $tierLabel = 'Gold';
                                                                break;
                                                            case 10:
                                                                $tierLabel = 'Iron';
                                                                break;
                                                            default:
                                                                $tierLabel = 'Regular';
                                                        }
                                                    }
                                            ?>
                                            <small class="text-success">-<?php echo $discountPercent; ?>% (<?php echo htmlspecialchars($tierLabel); ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="billing-actions justify-content-center">
                                    <button class="btn btn-sm btn-outline-primary" data-coreui-toggle="modal" data-coreui-target="#billingModal" onclick="openBillingModal(<?php echo $billing['id']; ?>, <?php echo $billing['calculated_balance']; ?>, <?php echo $billing['discounted_balance']; ?>, '<?php echo htmlspecialchars($billing['room_number']); ?>', '<?php echo htmlspecialchars($billing['first_name'] . ' ' . $billing['last_name']); ?>', <?php echo $billing['room_id']; ?>)" title="Create Billing">
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
                            $billingHistory = $conn->query("SELECT * FROM room_billing ORDER BY transaction_date DESC")->fetchAll(PDO::FETCH_ASSOC);
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
        <div class="modal-dialog" style="max-width: 50vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Billing Transaction</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <form id="billingForm" onsubmit="submitBillingForm(event)">
                    <div class="modal-body">
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-7">
                                <div class="rounded-3 border p-3">
                                    <input type="hidden" name="action" id="formAction" value="create">
                                    <input type="hidden" name="id" id="billingId">

                                    <input type="hidden" name="transaction_type" value="Room Charge">
                                    <input type="hidden" name="billing_status" value="Paid">

                                    <div class="mb-3">
                                        <label class="form-label">Description: <span id="billing_description_display"></span></label>
                                    </div>

                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method *</label>
                                        <select class="form-select form-select-sm" id="payment_method" name="payment_method" required>
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Card</option>
                                            <option value="GCash">GCash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                        </select>
                                    </div>

                                    <input type="hidden" id="reservation_id" name="reservation_id">
                                    <input type="hidden" id="room_id" name="room_id">

                                    <div class="mb-3">
                                        <label for="payment_amount" class="form-label">Payment Amount *</label>
                                        <input type="number" class="form-control form-control-sm" id="payment_amount" name="payment_amount" min="0" step="0.01" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Balance: <span id="balance_display">₱0.00</span> | Change: <span id="change_display">₱0.00</span></label>
                                        <input type="hidden" id="balance" name="balance" step="0.01" readonly>
                                        <input type="hidden" id="change" name="change" step="0.01" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="rounded-3 border p-3">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control form-control-sm" id="notes" name="notes" rows="3"></textarea>
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input class="form-check-input" type="checkbox" id="generate_invoice" name="generate_invoice">
                                        <label class="form-check-label" for="generate_invoice">
                                            Generate Invoice
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-secondary btn-sm" data-coreui-dismiss="modal">Cancel</button>
                            <button type="submit" id="billingSubmit" class="btn btn-primary btn-sm">Save</button>
                        </div>
                    </div>
                </form>
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
        // Auto-calculate balance
        function calculateBalance() {
            const balanceAmount = parseFloat(document.getElementById('balance').value) || 0;
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
            const newBalance = balanceAmount - paymentAmount;
            document.getElementById('balance').value = newBalance.toFixed(2);
            document.getElementById('balance_display').textContent = '₱' + newBalance.toFixed(2);
        }

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


        // Auto-calculate total amount
        function calculateTotal() {
            const quantity = parseFloat(document.getElementById('quantity').value) || 1;
            const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
            const total = quantity * unitPrice;
            document.getElementById('total_amount').value = total.toFixed(2);
            calculateBalance();
        }

        // Auto-calculate balance
        function calculateBalance() {
            const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
            const balance = totalAmount - paymentAmount;
            document.getElementById('balance').value = balance.toFixed(2);
        }

        // Add event listeners for auto-calculation
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('payment_amount').addEventListener('input', function() {
                calculateChange();
            });
        });

        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add Billing Transaction';
            document.getElementById('formAction').value = 'create';
            document.getElementById('billingId').value = '';
            document.getElementById('billingForm').reset();
            document.getElementById('balance').value = '0.00';
            new coreui.Modal(document.getElementById('billingModal')).show();
        }

        function openReportModal() {
            const modal = new coreui.Modal(document.getElementById('reportModal'));
            modal.show();
        }

        function generateReport() {
            const date = document.getElementById('reportDate').value;
            if (!date) {
                alert('Please select a date');
                return;
            }

            // Open PDF in new window/tab
            window.open('room_billing.php?action=export_pdf&date=' + date + '&HX-Request=true', '_blank');

            // Hide modal
            const modal = coreui.Modal.getInstance(document.getElementById('reportModal'));
            if (modal) {
                modal.hide();
            }
        }

        function openBillingModal(reservationId, calculatedBalance, discountedBalance, roomNumber, guestName, roomId) {
            document.getElementById('modalTitle').textContent = 'Create Room Billing';
            document.getElementById('formAction').value = 'create';
            document.getElementById('billingId').value = '';
            document.getElementById('billingForm').reset();

            // Pre-fill form with reservation data
            document.getElementById('reservation_id').value = reservationId;
            document.getElementById('room_id').value = roomId;
            document.getElementById('balance').value = discountedBalance.toFixed(2);
            document.getElementById('balance_display').textContent = '₱' + discountedBalance.toFixed(2);
            document.getElementById('billing_description_display').textContent = 'Room charge for ' + roomNumber + ' - ' + guestName;
            // document.getElementById('payment_amount').value = '';
            calculateChange();

            new coreui.Modal(document.getElementById('billingModal')).show();
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
                document.getElementById('reservation_id').value = data.reservation_id || '';
                document.getElementById('room_id').value = data.room_id || '';
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

        function submitBillingForm(event) {
            event.preventDefault();

            const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
            const balance = parseFloat(document.getElementById('balance').value) || 0;

            if (paymentAmount < balance) {
                AppModal.alert('Payment amount cannot be less than balance');
                return;
            }

            const form = document.getElementById('billingForm');
            const formData = new FormData(form);
            const generateInvoice = document.getElementById('generate_invoice').checked;

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

                    // Generate invoice if checkbox was checked
                    if (generateInvoice && data.billing_id) {
                        window.open('room_billing.php?action=generate_invoice&billing_id=' + data.billing_id, '_blank');
                    }

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

            fetch('room_billing.php', {
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