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
    <title>Marketing & Promotions - Hotel Management System</title>

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
        .nav-tabs .nav-link {
            border: none;
            color: #718096;
        }
        .nav-tabs .nav-link.active {
            background: #0dcaf0;
            color: white;
        }
        .campaign-card {
            transition: transform 0.2s;
        }
        .campaign-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Marketing & Promotions</h2>
                <p class="text-muted mb-0">Manage campaigns, offers, and track performance</p>
            </div>
            <div>
                <button class="btn btn-primary me-2" data-coreui-toggle="modal" data-coreui-target="#campaignModal" onclick="openCreateCampaignModal()">
                    <i class="cil-plus me-2"></i>Add Campaign
                </button>
                <button class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#offerModal" onclick="openCreateOfferModal()">
                    <i class="cil-plus me-2"></i>Add Offer
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
                                <h6 class="card-title mb-1">Total Campaigns</h6>
                                <h3 class="mb-0"><?php echo $stats['total_campaigns']; ?></h3>
                            </div>
                            <i class="cil-bullhorn fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Active Campaigns</h6>
                                <h3 class="mb-0"><?php echo $stats['active_campaigns']; ?></h3>
                            </div>
                            <i class="cil-play-circle fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Active Offers</h6>
                                <h3 class="mb-0"><?php echo $stats['active_offers']; ?></h3>
                            </div>
                            <i class="cil-gift fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Revenue</h6>
                                <h3 class="mb-0">$<?php echo number_format($stats['total_revenue'] ?: 0, 2); ?></h3>
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
                                <h6 class="card-title mb-1">Avg ROI</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['avg_roi'] ?: 0, 1); ?>%</h3>
                            </div>
                            <i class="cil-chart-line fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="marketingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="campaigns-tab" data-coreui-toggle="tab" data-coreui-target="#campaigns" type="button" role="tab">Campaigns</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="offers-tab" data-coreui-toggle="tab" data-coreui-target="#offers" type="button" role="tab">Promotional Offers</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="performance-tab" data-coreui-toggle="tab" data-coreui-target="#performance" type="button" role="tab">Performance</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Campaigns Tab -->
            <div class="tab-pane fade show active" id="campaigns" role="tabpanel">
                <div class="row">
                    <?php foreach ($campaigns as $campaign): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card campaign-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo htmlspecialchars($campaign['name']); ?></h6>
                                <span class="badge bg-<?php
                                    echo $campaign['status'] === 'active' ? 'success' :
                                         ($campaign['status'] === 'completed' ? 'primary' :
                                         ($campaign['status'] === 'paused' ? 'warning' : 'secondary'));
                                ?>">
                                    <?php echo htmlspecialchars($campaign['status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($campaign['description'] ?: 'No description'); ?></p>
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Type</small>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($campaign['campaign_type']); ?></span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Budget</small>
                                        <span class="fw-bold">$<?php echo number_format($campaign['budget'] ?: 0, 2); ?></span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($campaign['start_date'])); ?>
                                        <?php if ($campaign['end_date']): ?> - <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?><?php endif; ?>
                                    </small>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editCampaign(<?php echo $campaign['id']; ?>)">
                                            <i class="cil-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCampaign(<?php echo $campaign['id']; ?>, '<?php echo htmlspecialchars($campaign['name']); ?>')">
                                            <i class="cil-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Offers Tab -->
            <div class="tab-pane fade" id="offers" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Discount</th>
                                        <th>Valid Period</th>
                                        <th>Usage</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($offers as $offer): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($offer['code']); ?></code></td>
                                        <td><?php echo htmlspecialchars($offer['name']); ?></td>
                                        <td><?php echo htmlspecialchars(str_replace('_', ' ', $offer['offer_type'])); ?></td>
                                        <td>
                                            <?php if ($offer['discount_percentage']): ?>
                                                <?php echo $offer['discount_percentage']; ?>%
                                            <?php elseif ($offer['discount_value']): ?>
                                                $<?php echo number_format($offer['discount_value'], 2); ?>
                                            <?php else: ?>
                                                Special
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($offer['valid_from'])); ?> - <?php echo date('M d, Y', strtotime($offer['valid_until'])); ?></td>
                                        <td><?php echo ($offer['usage_count'] ?: 0); ?>/<?php echo $offer['usage_limit'] ?: '∞'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $offer['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $offer['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editOffer(<?php echo $offer['id']; ?>)">
                                                <i class="cil-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteOffer(<?php echo $offer['id']; ?>, '<?php echo htmlspecialchars($offer['name']); ?>')">
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

            <!-- Performance Tab -->
            <div class="tab-pane fade" id="performance" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Campaign Performance</h4>
                    <button class="btn btn-info" data-coreui-toggle="modal" data-coreui-target="#performanceModal" onclick="openAddPerformanceModal()">
                        <i class="cil-plus me-2"></i>Add Performance Data
                    </button>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Date</th>
                                        <th>Impressions</th>
                                        <th>Clicks</th>
                                        <th>Leads</th>
                                        <th>Conversions</th>
                                        <th>Revenue</th>
                                        <th>CTR</th>
                                        <th>ROI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($performance as $perf): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($perf['campaign_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($perf['performance_date'])); ?></td>
                                        <td><?php echo number_format($perf['impressions']); ?></td>
                                        <td><?php echo number_format($perf['clicks']); ?></td>
                                        <td><?php echo number_format($perf['leads']); ?></td>
                                        <td><?php echo number_format($perf['conversions']); ?></td>
                                        <td>$<?php echo number_format($perf['revenue'], 2); ?></td>
                                        <td><?php echo $perf['impressions'] > 0 ? number_format(($perf['clicks'] / $perf['impressions']) * 100, 2) : 0; ?>%</td>
                                        <td><?php echo $perf['spend'] > 0 ? number_format(($perf['revenue'] / $perf['spend']) * 100, 1) : 0; ?>%</td>
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