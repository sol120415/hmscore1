<?php
include_once 'db.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

// Handle GET requests for promotions page
if (isset($_GET['action']) && $_GET['action'] === 'get_promotions_page') {
    ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Available Promotions</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Discount</th>
                                    <th>Valid Until</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $promotions = $conn->query("SELECT * FROM promotional_offers WHERE is_active = 1 AND valid_until >= CURDATE() ORDER BY valid_until ASC")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($promotions as $promo): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($promo['code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($promo['name']); ?></td>
                                    <td><?php echo htmlspecialchars($promo['offer_type']); ?></td>
                                    <td>
                                        <?php if ($promo['discount_percentage']): ?>
                                            <?php echo $promo['discount_percentage']; ?>%
                                        <?php elseif ($promo['discount_value']): ?>
                                            ₱<?php echo number_format($promo['discount_value'], 2); ?>
                                        <?php else: ?>
                                            Special
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($promo['valid_until'])); ?></td>
                                    <td><span class="badge bg-success">Active</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-2" onclick="openCreateOfferModal()">
                        <i class="cil-plus me-1"></i>Create New Offer
                    </button>
                    <button class="btn btn-outline-secondary w-100" onclick="refreshPromotions()">
                        <i class="cil-reload me-1"></i>Refresh List
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

// Handle HTMX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_HX_REQUEST'])) {
    header('Content-Type: application/json');

    try {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];

            switch ($action) {
                case 'create_campaign':
                    // Create new campaign
                    $stmt = $conn->prepare("INSERT INTO marketing_campaigns (name, description, campaign_type, target_audience, start_date, end_date, budget, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'] ?: null,
                        $_POST['campaign_type'],
                        $_POST['target_audience'] ?: null,
                        $_POST['start_date'],
                        $_POST['end_date'] ?: null,
                        $_POST['budget'] ?: null,
                        $_POST['status'],
                        1 // Default user ID
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Campaign created successfully']);
                    break;

                case 'update_campaign':
                    // Update campaign
                    $stmt = $conn->prepare("UPDATE marketing_campaigns SET name=?, description=?, campaign_type=?, target_audience=?, start_date=?, end_date=?, budget=?, status=? WHERE id=?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'] ?: null,
                        $_POST['campaign_type'],
                        $_POST['target_audience'] ?: null,
                        $_POST['start_date'],
                        $_POST['end_date'] ?: null,
                        $_POST['budget'] ?: null,
                        $_POST['status'],
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Campaign updated successfully']);
                    break;

                case 'delete_campaign':
                    // Delete campaign
                    $stmt = $conn->prepare("DELETE FROM marketing_campaigns WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Campaign deleted successfully']);
                    break;

                case 'get_campaign':
                    // Get campaign data for editing
                    $stmt = $conn->prepare("SELECT * FROM marketing_campaigns WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($campaign);
                    break;

                case 'create_offer':
                    // Create new promotional offer
                    $stmt = $conn->prepare("INSERT INTO promotional_offers (code, name, description, offer_type, discount_value, discount_percentage, min_stay_nights, max_discount_amount, applicable_room_types, applicable_rate_plans, usage_limit, valid_from, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['code'],
                        $_POST['name'],
                        $_POST['description'] ?: null,
                        $_POST['offer_type'],
                        $_POST['discount_value'] ?: null,
                        $_POST['discount_percentage'] ?: null,
                        $_POST['min_stay_nights'] ?: 1,
                        $_POST['max_discount_amount'] ?: null,
                        $_POST['applicable_room_types'] ?: null,
                        $_POST['applicable_rate_plans'] ?: null,
                        $_POST['usage_limit'] ?: null,
                        $_POST['valid_from'],
                        $_POST['valid_until']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Promotional offer created successfully']);
                    break;

                case 'update_offer':
                    // Update promotional offer
                    $stmt = $conn->prepare("UPDATE promotional_offers SET code=?, name=?, description=?, offer_type=?, discount_value=?, discount_percentage=?, min_stay_nights=?, max_discount_amount=?, applicable_room_types=?, applicable_rate_plans=?, usage_limit=?, valid_from=?, valid_until=?, is_active=? WHERE id=?");
                    $stmt->execute([
                        $_POST['code'],
                        $_POST['name'],
                        $_POST['description'] ?: null,
                        $_POST['offer_type'],
                        $_POST['discount_value'] ?: null,
                        $_POST['discount_percentage'] ?: null,
                        $_POST['min_stay_nights'] ?: 1,
                        $_POST['max_discount_amount'] ?: null,
                        $_POST['applicable_room_types'] ?: null,
                        $_POST['applicable_rate_plans'] ?: null,
                        $_POST['usage_limit'] ?: null,
                        $_POST['valid_from'],
                        $_POST['valid_until'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Promotional offer updated successfully']);
                    break;

                case 'delete_offer':
                    // Delete promotional offer
                    $stmt = $conn->prepare("DELETE FROM promotional_offers WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Promotional offer deleted successfully']);
                    break;

                case 'get_offer':
                    // Get offer data for editing
                    $stmt = $conn->prepare("SELECT * FROM promotional_offers WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $offer = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($offer);
                    break;

                case 'add_performance':
                    // Add campaign performance data
                    $stmt = $conn->prepare("INSERT INTO campaign_performance (campaign_id, performance_date, impressions, clicks, leads, conversions, revenue, spend) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE impressions=VALUES(impressions), clicks=VALUES(clicks), leads=VALUES(leads), conversions=VALUES(conversions), revenue=VALUES(revenue), spend=VALUES(spend)");
                    $stmt->execute([
                        $_POST['campaign_id'],
                        $_POST['performance_date'],
                        $_POST['impressions'] ?: 0,
                        $_POST['clicks'] ?: 0,
                        $_POST['leads'] ?: 0,
                        $_POST['conversions'] ?: 0,
                        $_POST['revenue'] ?: 0,
                        $_POST['spend'] ?: 0
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Performance data added successfully']);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get data for display
$campaigns = $conn->query("SELECT * FROM marketing_campaigns ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$offers = $conn->query("SELECT * FROM promotional_offers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$performance = $conn->query("
    SELECT cp.*, mc.name as campaign_name
    FROM campaign_performance cp
    JOIN marketing_campaigns mc ON cp.campaign_id = mc.id
    ORDER BY cp.performance_date DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM marketing_campaigns) as total_campaigns,
        (SELECT COUNT(*) FROM marketing_campaigns WHERE status = 'active') as active_campaigns,
        (SELECT COUNT(*) FROM promotional_offers WHERE is_active = 1) as active_offers,
        (SELECT SUM(revenue) FROM campaign_performance) as total_revenue,
        (SELECT SUM(spend) FROM campaign_performance) as total_spend,
        (SELECT AVG(roi_percentage) FROM marketing_campaigns WHERE roi_percentage IS NOT NULL) as avg_roi
    FROM dual
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing - Hotel Management System</title>

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
        .marketing-card {
            cursor: pointer;
        }
        .marketing-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .marketing-actions {
            display: none;
        }
        .marketing-card:hover .marketing-actions {
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
                <?php include 'marketingtitle.html'; ?>
                </div>
                <div>
                    <small class="text-muted d-block">Total Campaigns</small>
                    <span class="fw-bold text-primary"><?php echo $stats['total_campaigns']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Active Campaigns</small>
                    <span class="fw-bold text-success"><?php echo $stats['active_campaigns']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Active Offers</small>
                    <span class="fw-bold text-warning"><?php echo $stats['active_offers']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Total Revenue</small>
                    <span class="fw-bold text-info">₱<?php echo number_format($stats['total_revenue'] ?: 0, 2); ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Avg ROI</small>
                    <span class="fw-bold text-danger"><?php echo number_format($stats['avg_roi'] ?: 0, 1); ?>%</span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="marketingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="campaigns-tab" data-coreui-toggle="tab" data-coreui-target="#campaigns" type="button" role="tab">Campaigns</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="offers-tab" data-coreui-toggle="tab" data-coreui-target="#offers" type="button" role="tab">Offers</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="promotions-tab" data-coreui-toggle="tab" data-coreui-target="#promotions" type="button" role="tab">Promotions</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="marketingTabContent">
            <!-- Campaigns Tab -->
            <div class="tab-pane fade show active" id="campaigns" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Marketing Campaigns</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm" onclick="generateReport()">
                                <i class="cil-file-pdf me-1"></i>Report
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="openCreateCampaignModal()">
                                <i class="cil-plus me-1"></i>Add Campaign
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="campaignsContainer">
                            <?php foreach ($campaigns as $campaign): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 marketing-card" style="border-left: 4px solid <?php
                                    echo $campaign['status'] === 'active' ? '#198754' :
                                         ($campaign['status'] === 'completed' ? '#0d6efd' :
                                         ($campaign['status'] === 'paused' ? '#fd7e14' :
                                         ($campaign['status'] === 'draft' ? '#6c757d' : '#dc3545')));
                                ?>;">
                                    <div class="card-body">
                                        <div class="marketing-content">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($campaign['name']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($campaign['campaign_type']); ?> • <?php echo htmlspecialchars($campaign['description'] ?: 'No description'); ?>
                                                    </small>
                                                </div>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge bg-<?php
                                                        echo $campaign['status'] === 'active' ? 'success' :
                                                             ($campaign['status'] === 'completed' ? 'primary' :
                                                             ($campaign['status'] === 'paused' ? 'warning' :
                                                             ($campaign['status'] === 'draft' ? 'secondary' : 'danger')));
                                                    ?>">
                                                        <?php echo htmlspecialchars($campaign['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="marketing-actions justify-content-center">
                                            <button class="btn btn-sm btn-outline-primary me-2" onclick="editCampaign(<?php echo $campaign['id']; ?>)" title="Edit">
                                                <i class="cil-pencil me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCampaign(<?php echo $campaign['id']; ?>, '<?php echo htmlspecialchars($campaign['name']); ?>')" title="Remove">
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

            <!-- Offers Tab -->
            <div class="tab-pane fade" id="offers" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Promotional Offers</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="openCreateOfferModal()">
                                <i class="cil-plus me-1"></i>Add Offer
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="offersContainer">
                            <?php foreach ($offers as $offer): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 offer-card" style="border-left: 4px solid <?php echo $offer['is_active'] ? '#198754' : '#6c757d'; ?>;">
                                    <div class="card-body">
                                        <div class="offer-content">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($offer['name']); ?> (<?php echo htmlspecialchars($offer['code']); ?>)</h6>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($offer['offer_type']); ?> • Valid: <?php echo date('M j', strtotime($offer['valid_from'])); ?> - <?php echo date('M j, Y', strtotime($offer['valid_until'])); ?>
                                                    </small>
                                                </div>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge bg-<?php echo $offer['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $offer['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <?php if ($offer['discount_percentage']): ?>
                                                        <?php echo $offer['discount_percentage']; ?>% off
                                                    <?php elseif ($offer['discount_value']): ?>
                                                        $<?php echo $offer['discount_value']; ?> off
                                                    <?php else: ?>
                                                        Special offer
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="offer-actions justify-content-center">
                                            <button class="btn btn-sm btn-outline-primary me-2" onclick="editOffer(<?php echo $offer['id']; ?>)" title="Edit">
                                                <i class="cil-pencil me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteOffer(<?php echo $offer['id']; ?>, '<?php echo htmlspecialchars($offer['name']); ?>')" title="Remove">
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

            <!-- Promotions Tab -->
            <div class="tab-pane fade" id="promotions" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Promotions</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="openCreatePromotionModal()">
                                <i class="cil-plus me-1"></i>Add Promotion
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="promotionsContent">
                            <!-- Promotions content will be loaded here -->
                            <div class="text-center text-muted py-5">
                                <i class="cil-gift display-4 mb-3"></i>
                                <h5>Promotions Management</h5>
                                <p>Click "Add Promotion" to create promotional offers that can be applied during billing.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Modal -->
    <div class="modal fade" id="campaignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="campaignModalTitle">Add Campaign</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="campaignForm">
                        <input type="hidden" name="action" id="campaignFormAction" value="create_campaign">
                        <input type="hidden" name="id" id="campaignId">

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="campaign_name" class="form-label">Campaign Name *</label>
                                <input type="text" class="form-control" id="campaign_name" name="name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="campaign_type" class="form-label">Type *</label>
                                <select class="form-select" id="campaign_type" name="campaign_type" required>
                                    <option value="email">Email</option>
                                    <option value="social_media">Social Media</option>
                                    <option value="advertising">Advertising</option>
                                    <option value="promotion">Promotion</option>
                                    <option value="loyalty">Loyalty</option>
                                    <option value="seasonal">Seasonal</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="budget" class="form-label">Budget</label>
                                <input type="number" class="form-control" id="budget" name="budget" min="0" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="campaign_status" name="status" required>
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="target_audience" class="form-label">Target Audience</label>
                            <textarea class="form-control" id="target_audience" name="target_audience" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitCampaignForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Offer Modal -->
    <div class="modal fade" id="offerModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="offerModalTitle">Add Promotional Offer</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="offerForm">
                        <input type="hidden" name="action" id="offerFormAction" value="create_offer">
                        <input type="hidden" name="id" id="offerId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="offer_code" class="form-label">Code *</label>
                                <input type="text" class="form-control" id="offer_code" name="code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="offer_name" class="form-label">Name *</label>
                                <input type="text" class="form-control" id="offer_name" name="name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="offer_type" class="form-label">Offer Type *</label>
                                <select class="form-select" id="offer_type" name="offer_type" required>
                                    <option value="percentage_discount">Percentage Discount</option>
                                    <option value="fixed_amount_discount">Fixed Amount Discount</option>
                                    <option value="free_nights">Free Nights</option>
                                    <option value="upgrade">Room Upgrade</option>
                                    <option value="package_deal">Package Deal</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="usage_limit" class="form-label">Usage Limit</label>
                                <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="1">
                            </div>
                        </div>

                        <div class="row" id="discountFields">
                            <div class="col-md-6 mb-3">
                                <label for="discount_percentage" class="form-label">Discount Percentage (%)</label>
                                <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="discount_value" class="form-label">Discount Value ($)</label>
                                <input type="number" class="form-control" id="discount_value" name="discount_value" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="valid_from" class="form-label">Valid From *</label>
                                <input type="date" class="form-control" id="valid_from" name="valid_from" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="valid_until" class="form-label">Valid Until *</label>
                                <input type="date" class="form-control" id="valid_until" name="valid_until" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_stay_nights" class="form-label">Min Stay Nights</label>
                                <input type="number" class="form-control" id="min_stay_nights" name="min_stay_nights" min="1" value="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_discount_amount" class="form-label">Max Discount Amount</label>
                                <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="applicable_room_types" class="form-label">Applicable Room Types</label>
                            <input type="text" class="form-control" id="applicable_room_types" name="applicable_room_types" placeholder="e.g., Single,Double,Deluxe">
                        </div>

                        <div class="mb-3">
                            <label for="offer_description" class="form-label">Description</label>
                            <textarea class="form-control" id="offer_description" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitOfferForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Modal -->
    <div class="modal fade" id="performanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Performance Data</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="performanceForm">
                        <input type="hidden" name="action" value="add_performance">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="campaign_id" class="form-label">Campaign *</label>
                                <select class="form-select" id="campaign_id" name="campaign_id" required>
                                    <option value="">Select Campaign</option>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <option value="<?php echo $campaign['id']; ?>"><?php echo htmlspecialchars($campaign['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="performance_date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="performance_date" name="performance_date" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="impressions" class="form-label">Impressions</label>
                                <input type="number" class="form-control" id="impressions" name="impressions" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clicks" class="form-label">Clicks</label>
                                <input type="number" class="form-control" id="clicks" name="clicks" min="0">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="leads" class="form-label">Leads</label>
                                <input type="number" class="form-control" id="leads" name="leads" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="conversions" class="form-label">Conversions</label>
                                <input type="number" class="form-control" id="conversions" name="conversions" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="revenue" class="form-label">Revenue ($)</label>
                                <input type="number" class="form-control" id="revenue" name="revenue" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="spend" class="form-label">Spend ($)</label>
                            <input type="number" class="form-control" id="spend" name="spend" min="0" step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitPerformanceForm()">Add Data</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        // Campaign functions
        function openCreateCampaignModal() {
            document.getElementById('campaignModalTitle').textContent = 'Add Campaign';
            document.getElementById('campaignFormAction').value = 'create_campaign';
            document.getElementById('campaignId').value = '';
            document.getElementById('campaignForm').reset();
            new coreui.Modal(document.getElementById('campaignModal')).show();
        }

        function editCampaign(id) {
            document.getElementById('campaignModalTitle').textContent = 'Edit Campaign';
            document.getElementById('campaignFormAction').value = 'update_campaign';

            fetch('marketing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_campaign&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('campaignId').value = data.id;
                document.getElementById('campaign_name').value = data.name;
                document.getElementById('description').value = data.description || '';
                document.getElementById('campaign_type').value = data.campaign_type;
                document.getElementById('target_audience').value = data.target_audience || '';
                document.getElementById('start_date').value = data.start_date;
                document.getElementById('end_date').value = data.end_date || '';
                document.getElementById('budget').value = data.budget || '';
                document.getElementById('campaign_status').value = data.status;

                new coreui.Modal(document.getElementById('campaignModal')).show();
            });
        }

        function deleteCampaign(id, name) {
            if (confirm('Are you sure you want to delete the campaign "' + name + '"? This action cannot be undone.')) {
                fetch('marketing.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_campaign&id=' + id
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

        function submitCampaignForm() {
            const form = document.getElementById('campaignForm');
            const formData = new FormData(form);

            fetch('marketing.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('campaignModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Offer functions
        function openCreateOfferModal() {
            document.getElementById('offerModalTitle').textContent = 'Add Promotional Offer';
            document.getElementById('offerFormAction').value = 'create_offer';
            document.getElementById('offerId').value = '';
            document.getElementById('offerForm').reset();
            new coreui.Modal(document.getElementById('offerModal')).show();
        }

        function openCreatePromotionModal() {
            openCreateOfferModal();
        }

        function refreshPromotions() {
            openCreatePromotionModal();
        }

        function editOffer(id) {
            document.getElementById('offerModalTitle').textContent = 'Edit Promotional Offer';
            document.getElementById('offerFormAction').value = 'update_offer';

            fetch('marketing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_offer&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('offerId').value = data.id;
                document.getElementById('offer_code').value = data.code;
                document.getElementById('offer_name').value = data.name;
                document.getElementById('offer_description').value = data.description || '';
                document.getElementById('offer_type').value = data.offer_type;
                document.getElementById('discount_percentage').value = data.discount_percentage || '';
                document.getElementById('discount_value').value = data.discount_value || '';
                document.getElementById('min_stay_nights').value = data.min_stay_nights;
                document.getElementById('max_discount_amount').value = data.max_discount_amount || '';
                document.getElementById('applicable_room_types').value = data.applicable_room_types || '';
                document.getElementById('usage_limit').value = data.usage_limit || '';
                document.getElementById('valid_from').value = data.valid_from;
                document.getElementById('valid_until').value = data.valid_until;

                new coreui.Modal(document.getElementById('offerModal')).show();
            });
        }

        function deleteOffer(id, name) {
            if (confirm('Are you sure you want to delete the offer "' + name + '"? This action cannot be undone.')) {
                fetch('marketing.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_offer&id=' + id
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

        function submitOfferForm() {
            const form = document.getElementById('offerForm');
            const formData = new FormData(form);

            fetch('marketing.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('offerModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Performance functions
        function openAddPerformanceModal() {
            document.getElementById('performanceForm').reset();
        }

        function submitPerformanceForm() {
            const form = document.getElementById('performanceForm');
            const formData = new FormData(form);

            fetch('marketing.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('performanceModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>