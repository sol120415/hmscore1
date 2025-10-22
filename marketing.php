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
                    $stmt = $conn->prepare("INSERT INTO marketing_campaigns (name, description, campaign_type, target_audience, start_date, end_date, budget, status, leads_generated, conversions, revenue_generated, roi_percentage, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'] ?: null,
                        $_POST['campaign_type'],
                        $_POST['target_audience'] ?: null,
                        $_POST['start_date'],
                        $_POST['end_date'] ?: null,
                        $_POST['budget'] ?: null,
                        $_POST['status'],
                        $_POST['leads_generated'] ?: 0,
                        $_POST['conversions'] ?: 0,
                        $_POST['revenue_generated'] ?: 0,
                        $_POST['roi_percentage'] ?: null,
                        1 // Default user ID
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Campaign created successfully']);
                    break;

                case 'update_campaign':
                    // Update campaign
                    $stmt = $conn->prepare("UPDATE marketing_campaigns SET name=?, description=?, campaign_type=?, target_audience=?, start_date=?, end_date=?, budget=?, status=?, leads_generated=?, conversions=?, revenue_generated=?, roi_percentage=? WHERE id=?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'] ?: null,
                        $_POST['campaign_type'],
                        $_POST['target_audience'] ?: null,
                        $_POST['start_date'],
                        $_POST['end_date'] ?: null,
                        $_POST['budget'] ?: null,
                        $_POST['status'],
                        $_POST['leads_generated'] ?: 0,
                        $_POST['conversions'] ?: 0,
                        $_POST['revenue_generated'] ?: 0,
                        $_POST['roi_percentage'] ?: null,
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
                    $stmt = $conn->prepare("INSERT INTO promotional_offers (code, name, description, offer_type, discount_value, discount_percentage, min_stay_nights, max_discount_amount, applicable_room_types, applicable_rate_plans, usage_limit, usage_count, valid_from, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
                        $_POST['usage_count'] ?: 0,
                        $_POST['valid_from'],
                        $_POST['valid_until']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Promotional offer created successfully']);
                    break;

                case 'update_offer':
                    // Update promotional offer
                    $stmt = $conn->prepare("UPDATE promotional_offers SET code=?, name=?, description=?, offer_type=?, discount_value=?, discount_percentage=?, min_stay_nights=?, max_discount_amount=?, applicable_room_types=?, applicable_rate_plans=?, usage_limit=?, usage_count=?, valid_from=?, valid_until=?, is_active=? WHERE id=?");
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
                        $_POST['usage_count'] ?: 0,
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

                case 'apply_offer':
                    // Apply promotional offer (placeholder for billing integration)
                    $stmt = $conn->prepare("SELECT * FROM promotional_offers WHERE code=? AND is_active=1 AND valid_from <= CURDATE() AND valid_until >= CURDATE() AND (usage_limit IS NULL OR usage_count < usage_limit)");
                    $stmt->execute([$_POST['code']]);
                    $offer = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($offer) {
                        // Check if offer applies to the given criteria
                        $applicable = true;

                        if ($offer['applicable_room_types'] && !in_array($_POST['room_type'], explode(',', $offer['applicable_room_types']))) {
                            $applicable = false;
                        }

                        if ($offer['applicable_rate_plans'] && !in_array($_POST['rate_plan'], explode(',', $offer['applicable_rate_plans']))) {
                            $applicable = false;
                        }

                        if ($applicable) {
                            // Calculate discount
                            $discount = 0;
                            if ($offer['discount_percentage']) {
                                $discount = $_POST['base_amount'] * ($offer['discount_percentage'] / 100);
                            } elseif ($offer['discount_value']) {
                                $discount = min($offer['discount_value'], $_POST['base_amount']);
                            }

                            if ($offer['max_discount_amount']) {
                                $discount = min($discount, $offer['max_discount_amount']);
                            }

                            // Update usage count
                            $stmt = $conn->prepare("UPDATE promotional_offers SET usage_count = usage_count + 1 WHERE id=?");
                            $stmt->execute([$offer['id']]);

                            echo json_encode([
                                'success' => true,
                                'discount' => $discount,
                                'offer_name' => $offer['name'],
                                'message' => 'Offer applied successfully'
                            ]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Offer not applicable to this booking']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid or expired offer code']);
                    }
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

                case 'delete_performance':
                    // Delete performance data
                    $stmt = $conn->prepare("DELETE FROM campaign_performance WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Performance data deleted successfully']);
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
// Build analytics datasets from database for charts
$monthlyPerf = $conn->query("
    SELECT DATE_FORMAT(performance_date, '%Y-%m') as ym,
           DATE_FORMAT(performance_date, '%b') as label,
           SUM(impressions) as impressions,
           SUM(clicks) as clicks
    FROM campaign_performance
    WHERE performance_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY ym, label
    ORDER BY ym ASC
")->fetchAll(PDO::FETCH_ASSOC);

$perfLabels = [];
$perfImpressions = [];
$perfClicks = [];
foreach ($monthlyPerf as $row) {
    $perfLabels[] = $row['label'];
    $perfImpressions[] = (int)$row['impressions'];
    $perfClicks[] = (int)$row['clicks'];
}

$campaignAgg = $conn->query("
    SELECT mc.name as name,
           COALESCE(SUM(cp.revenue),0) as revenue,
           COALESCE(SUM(cp.spend),0) as spend
    FROM marketing_campaigns mc
    LEFT JOIN campaign_performance cp ON cp.campaign_id = mc.id
    GROUP BY mc.id, mc.name
    ORDER BY (COALESCE(SUM(cp.revenue),0) + COALESCE(SUM(cp.spend),0)) DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$campLabels = [];
$campRevenue = [];
$campSpend = [];
foreach ($campaignAgg as $r) {
    $campLabels[] = $r['name'];
    $campRevenue[] = (float)$r['revenue'];
    $campSpend[] = (float)$r['spend'];
}
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
        .offer-actions {
            display: none;
        }
        .offer-card:hover .offer-actions {
            display: flex;
        }
        .performance-table th, .performance-table td {
            padding: 0.5rem;
            vertical-align: middle;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header with Stats -->
        <div class="mb-4">
            <div class="d-flex justify-content-between gap-3 text-center">
                <div class="flex-grow-1 text-start">
                    <h2>Marketing</h2>
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
                <button class="nav-link active" id="campaigns-tab" data-bs-toggle="tab" data-bs-target="#campaigns" type="button" role="tab">Campaigns</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="offers-tab" data-bs-toggle="tab" data-bs-target="#offers" type="button" role="tab">Offers</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="promotions-tab" data-bs-toggle="tab" data-bs-target="#promotions" type="button" role="tab">Promotions</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">Performance</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab">Analytics</button>
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
                                                    ₱<?php echo $offer['discount_value']; ?> off
                                                    <?php else: ?>
                                                        Special offer
                                                    <?php endif; ?>
                                                    • Used <?php echo $offer['usage_count']; ?>/<?php echo $offer['usage_limit'] ?: '∞'; ?> times
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
                            <?php
                            $promotions = $conn->query("SELECT * FROM promotional_offers WHERE is_active = 1 AND valid_until >= CURDATE() ORDER BY valid_until ASC")->fetchAll(PDO::FETCH_ASSOC);
                            if (count($promotions) > 0): ?>
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
                                                            <?php foreach ($promotions as $promo): ?>
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
                            <?php else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="cil-gift display-4 mb-3"></i>
                                    <h5>Promotions Management</h5>
                                    <p>Click "Add Promotion" to create promotional offers that can be applied during billing.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Tab -->
            <div class="tab-pane fade" id="performance" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Campaign Performance</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm" onclick="generateReport()">
                                <i class="cil-file-pdf me-1"></i>Report
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped performance-table">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Date</th>
                                        <th>Impressions</th>
                                        <th>Clicks</th>
                                        <th>Leads</th>
                                        <th>Conversions</th>
                                        <th>Revenue</th>
                                        <th>Spend</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($performance as $perf): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($perf['campaign_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($perf['performance_date'])); ?></td>
                                        <td><?php echo number_format($perf['impressions']); ?></td>
                                        <td><?php echo number_format($perf['clicks']); ?></td>
                                        <td><?php echo number_format($perf['leads']); ?></td>
                                        <td><?php echo number_format($perf['conversions']); ?></td>
                                        <td>₱<?php echo number_format($perf['revenue'], 2); ?></td>
                                        <td>₱<?php echo number_format($perf['spend'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deletePerformance(<?php echo $perf['id']; ?>)">
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

            <!-- Analytics Tab -->
            <div class="tab-pane fade" id="analytics" role="tabpanel">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Campaign Performance Overview</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="performanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Revenue vs Spend</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueSpendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Campaign ROI Analysis</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Campaign</th>
                                                <th>Total Revenue</th>
                                                <th>Total Spend</th>
                                                <th>ROI</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $campaign_roi = $conn->query("
                                                SELECT mc.name, mc.status,
                                                       COALESCE(SUM(cp.revenue), 0) as total_revenue,
                                                       COALESCE(SUM(cp.spend), 0) as total_spend,
                                                       CASE WHEN SUM(cp.spend) > 0 THEN ((SUM(cp.revenue) - SUM(cp.spend)) / SUM(cp.spend)) * 100 ELSE 0 END as roi
                                                FROM marketing_campaigns mc
                                                LEFT JOIN campaign_performance cp ON mc.id = cp.campaign_id
                                                GROUP BY mc.id, mc.name, mc.status
                                                ORDER BY roi DESC
                                            ")->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($campaign_roi as $roi): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($roi['name']); ?></td>
                                                <td>₱<?php echo number_format($roi['total_revenue'], 2); ?></td>
                                                <td>₱<?php echo number_format($roi['total_spend'], 2); ?></td>
                                                <td><?php echo number_format($roi['roi'], 1); ?>%</td>
                                                <td><span class="badge bg-<?php echo $roi['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($roi['status']); ?></span></td>
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

    <!-- Campaign Modal -->
    <div class="modal fade" id="campaignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="campaignModalTitle">Add Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="leads_generated" class="form-label">Leads Generated</label>
                                <input type="number" class="form-control" id="leads_generated" name="leads_generated" min="0" value="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="conversions" class="form-label">Conversions</label>
                                <input type="number" class="form-control" id="conversions" name="conversions" min="0" value="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="revenue_generated" class="form-label">Revenue Generated (₱)</label>
                                <input type="number" class="form-control" id="revenue_generated" name="revenue_generated" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="roi_percentage" class="form-label">ROI (%)</label>
                                <input type="number" class="form-control" id="roi_percentage" name="roi_percentage" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="target_audience" class="form-label">Target Audience</label>
                            <textarea class="form-control" id="target_audience" name="target_audience" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="offerForm">
                        <input type="hidden" name="action" id="offerFormAction" value="create_offer">
                        <input type="hidden" name="id" id="offerId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="offer_code" class="form-label">Offer Code *</label>
                                <input type="text" class="form-control" id="offer_code" name="code" required placeholder="e.g., SAVE20, WELCOME10">
                                <small class="form-text text-muted">Unique code customers will enter to apply this offer</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="offer_name" class="form-label">Offer Name *</label>
                                <input type="text" class="form-control" id="offer_name" name="name" required placeholder="e.g., 20% Off Weekend Stay">
                                <small class="form-text text-muted">Descriptive name for this promotional offer</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="offer_type" class="form-label">Offer Type *</label>
                                <select class="form-select" id="offer_type" name="offer_type" required onchange="toggleDiscountFields()">
                                    <option value="">Select Offer Type</option>
                                    <option value="percentage_discount">Percentage Discount</option>
                                    <option value="fixed_amount_discount">Fixed Amount Discount</option>
                                    <option value="free_nights">Free Nights</option>
                                    <option value="upgrade">Room Upgrade</option>
                                    <option value="package_deal">Package Deal</option>
                                </select>
                                <small class="form-text text-muted">Type of discount or benefit this offer provides</small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="usage_limit" class="form-label">Usage Limit</label>
                                <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="1" placeholder="100">
                                <small class="form-text text-muted">Maximum times this offer can be used (leave empty for unlimited)</small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="usage_count" class="form-label">Current Usage</label>
                                <input type="number" class="form-control" id="usage_count" name="usage_count" min="0" value="0" readonly>
                                <small class="form-text text-muted">How many times this offer has been redeemed</small>
                            </div>
                        </div>

                        <div class="row" id="discountFields" style="display: none;">
                            <div class="col-md-6 mb-3" id="percentageField" style="display: none;">
                                <label for="discount_percentage" class="form-label">Discount Percentage (%)</label>
                                <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" step="0.01" placeholder="20">
                                <small class="form-text text-muted">Percentage discount (e.g., 20 for 20% off)</small>
                            </div>
                            <div class="col-md-6 mb-3" id="fixedAmountField" style="display: none;">
                                <label for="discount_value" class="form-label">Discount Amount (₱)</label>
                                <input type="number" class="form-control" id="discount_value" name="discount_value" min="0" step="0.01" placeholder="50.00">
                                <small class="form-text text-muted">Fixed dollar amount discount (e.g., $50 off)</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="valid_from" class="form-label">Valid From *</label>
                                <input type="date" class="form-control" id="valid_from" name="valid_from" required>
                                <small class="form-text text-muted">Date when this offer becomes active</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="valid_until" class="form-label">Valid Until *</label>
                                <input type="date" class="form-control" id="valid_until" name="valid_until" required>
                                <small class="form-text text-muted">Date when this offer expires</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_stay_nights" class="form-label">Minimum Stay Nights</label>
                                <input type="number" class="form-control" id="min_stay_nights" name="min_stay_nights" min="1" value="1" placeholder="1">
                                <small class="form-text text-muted">Minimum number of nights required to use this offer</small>
                            </div>
                            <div class="col-md-6 mb-3" id="maxDiscountField" style="display: none;">
                                <label for="max_discount_amount" class="form-label">Maximum Discount Amount (₱)</label>
                                <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount" min="0" step="0.01" placeholder="100.00">
                                <small class="form-text text-muted">Maximum discount amount (for percentage discounts)</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="applicable_room_types" class="form-label">Applicable Room Types</label>
                                <input type="text" class="form-control" id="applicable_room_types" name="applicable_room_types" placeholder="Single,Double,Deluxe,Suite">
                                <small class="form-text text-muted">Comma-separated list of room types this offer applies to (leave empty for all)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="applicable_rate_plans" class="form-label">Applicable Rate Plans</label>
                                <input type="text" class="form-control" id="applicable_rate_plans" name="applicable_rate_plans" placeholder="Standard,Premium,VIP">
                                <small class="form-text text-muted">Comma-separated list of rate plans this offer applies to (leave empty for all)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="offer_description" class="form-label">Offer Description</label>
                            <textarea class="form-control" id="offer_description" name="description" rows="3" placeholder="Describe the terms and conditions of this promotional offer"></textarea>
                            <small class="form-text text-muted">Detailed description of the offer terms, conditions, and any restrictions</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="resetOfferModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitOfferForm()">Save Offer</button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                <label for="revenue" class="form-label">Revenue (₱)</label>
                                <input type="number" class="form-control" id="revenue" name="revenue" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="spend" class="form-label">Spend (₱)</label>
                            <input type="number" class="form-control" id="spend" name="spend" min="0" step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
            new bootstrap.Modal(document.getElementById('campaignModal')).show();
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
                document.getElementById('leads_generated').value = data.leads_generated || 0;
                document.getElementById('conversions').value = data.conversions || 0;
                document.getElementById('revenue_generated').value = data.revenue_generated || 0;
                document.getElementById('roi_percentage').value = data.roi_percentage || '';

                new bootstrap.Modal(document.getElementById('campaignModal')).show();
            });
        }

        function deleteCampaign(id, name) {
            AppModal.confirm('Are you sure you want to delete the campaign "' + name + '"? This action cannot be undone.').then(function(yes){ if(!yes) return; 
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
            });
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
                    new bootstrap.Modal(document.getElementById('campaignModal')).hide();
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
            toggleDiscountFields(); // Reset discount fields visibility
            new bootstrap.Modal(document.getElementById('offerModal')).show();
        }

        function toggleDiscountFields() {
            const offerType = document.getElementById('offer_type').value;
            const discountFields = document.getElementById('discountFields');
            const percentageField = document.getElementById('percentageField');
            const fixedAmountField = document.getElementById('fixedAmountField');
            const maxDiscountField = document.getElementById('maxDiscountField');

            // Hide all discount fields initially
            discountFields.style.display = 'none';
            percentageField.style.display = 'none';
            fixedAmountField.style.display = 'none';
            maxDiscountField.style.display = 'none';

            // Show relevant fields based on offer type
            if (offerType === 'percentage_discount') {
                discountFields.style.display = 'flex';
                percentageField.style.display = 'block';
                maxDiscountField.style.display = 'block';
            } else if (offerType === 'fixed_amount_discount') {
                discountFields.style.display = 'flex';
                fixedAmountField.style.display = 'block';
            }
            // For other offer types (free_nights, upgrade, package_deal), no discount fields needed
        }

        function resetOfferModal() {
            document.getElementById('offerForm').reset();
            toggleDiscountFields(); // Reset discount fields visibility
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
                document.getElementById('applicable_rate_plans').value = data.applicable_rate_plans || '';
                document.getElementById('usage_limit').value = data.usage_limit || '';
                document.getElementById('usage_count').value = data.usage_count || 0;
                document.getElementById('valid_from').value = data.valid_from;
                document.getElementById('valid_until').value = data.valid_until;

                // Update discount fields visibility based on offer type
                toggleDiscountFields();

                new bootstrap.Modal(document.getElementById('offerModal')).show();
            });
        }

        function deleteOffer(id, name) {
            AppModal.confirm('Are you sure you want to delete the offer "' + name + '"? This action cannot be undone.').then(function(yes){ if(!yes) return; 
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
            });
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
                    new bootstrap.Modal(document.getElementById('offerModal')).hide();
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
                    new bootstrap.Modal(document.getElementById('performanceModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function deletePerformance(id) {
            AppModal.confirm('Are you sure you want to delete this performance data? This action cannot be undone.').then(function(yes){ if(!yes) return; 
                fetch('marketing.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_performance&id=' + id
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

        function generateReport() {
            // Generate campaign performance report
            window.open('generate_report.php?type=marketing', '_blank');
        }

        // Initialize charts when analytics tab is shown
        document.addEventListener('DOMContentLoaded', function() {
            const analyticsTab = document.getElementById('analytics-tab');
            if (analyticsTab) {
                analyticsTab.addEventListener('shown.bs.tab', function() {
                    initializeCharts();
                });
            }
        });

        function initializeCharts() {
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart');
            if (performanceCtx) {
                new Chart(performanceCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($perfLabels); ?>,
                        datasets: [{
                            label: 'Impressions',
                            data: <?php echo json_encode($perfImpressions); ?>,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }, {
                            label: 'Clicks',
                            data: <?php echo json_encode($perfClicks); ?>,
                            borderColor: 'rgb(255, 99, 132)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Campaign Performance Trends'
                            }
                        }
                    }
                });
            }

            // Revenue vs Spend Chart
            const revenueSpendCtx = document.getElementById('revenueSpendChart');
            if (revenueSpendCtx) {
                new Chart(revenueSpendCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($campLabels); ?>,
                        datasets: [{
                            label: 'Revenue',
                            data: <?php echo json_encode($campRevenue); ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgb(75, 192, 192)',
                            borderWidth: 1
                        }, {
                            label: 'Spend',
                            data: <?php echo json_encode($campSpend); ?>,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgb(255, 99, 132)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Revenue vs Marketing Spend'
                            }
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>