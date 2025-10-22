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
                case 'get_chart_data':
                    // Get data for charts based on type and period
                    $type = $_POST['type'] ?? 'reservations';
                    $period = $_POST['period'] ?? '30';

                    $data = [];

                    if ($type === 'reservations') {
                        // Get reservation data for the last N days
                        $stmt = $conn->prepare("
                            SELECT
                                DATE(created_at) as date,
                                COUNT(*) as count,
                                SUM(CASE WHEN reservation_status = 'Checked In' THEN 1 ELSE 0 END) as checked_in,
                                SUM(CASE WHEN reservation_status = 'Checked Out' THEN 1 ELSE 0 END) as checked_out
                            FROM reservations
                            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                            GROUP BY DATE(created_at)
                            ORDER BY date
                        ");
                        $stmt->execute([$period]);
                        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } elseif ($type === 'revenue') {
                        // Get revenue data from room billing
                        $stmt = $conn->prepare("
                            SELECT
                                DATE(created_at) as date,
                                SUM(payment_amount) as revenue
                            FROM room_billing
                            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                            GROUP BY DATE(created_at)
                            ORDER BY date
                        ");
                        $stmt->execute([$period]);
                        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } elseif ($type === 'occupancy') {
                        // Get room occupancy data
                        $stmt = $conn->prepare("
                            SELECT
                                DATE(created_at) as date,
                                COUNT(CASE WHEN room_status = 'Occupied' THEN 1 END) as occupied,
                                COUNT(*) as total_rooms
                            FROM rooms
                            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                            GROUP BY DATE(created_at)
                            ORDER BY date
                        ");
                        $stmt->execute([$period]);
                        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    echo json_encode($data);
                    break;

                case 'get_summary_stats':
                    // Get summary statistics
                    $stats = $conn->query("
                        SELECT
                            (SELECT COUNT(*) FROM reservations WHERE reservation_status IN ('Checked In', 'Checked Out')) as total_reservations,
                            (SELECT COUNT(*) FROM guests) as total_guests,
                            (SELECT COUNT(*) FROM rooms WHERE room_status = 'Occupied') as occupied_rooms,
                            (SELECT COUNT(*) FROM rooms) as total_rooms,
                            (SELECT COALESCE(SUM(payment_amount), 0) FROM room_billing WHERE DATE(created_at) = CURDATE()) as today_revenue,
                            (SELECT COUNT(*) FROM housekeeping WHERE status = 'Completed' AND DATE(updated_at) = CURDATE()) as today_cleanings
                    ")->fetch(PDO::FETCH_ASSOC);

                    echo json_encode($stats);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get initial data for display
$summaryStats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM reservations WHERE reservation_status IN ('Checked In', 'Checked Out')) as total_reservations,
        (SELECT COUNT(*) FROM guests) as total_guests,
        (SELECT COUNT(*) FROM rooms WHERE room_status = 'Occupied') as occupied_rooms,
        (SELECT COUNT(*) FROM rooms) as total_rooms,
        (SELECT COALESCE(SUM(payment_amount), 0) FROM room_billing WHERE DATE(created_at) = CURDATE()) as today_revenue,
        (SELECT COUNT(*) FROM housekeeping WHERE status = 'Completed' AND DATE(updated_at) = CURDATE()) as today_cleanings
")->fetch(PDO::FETCH_ASSOC);

// Get recent activities
$recentActivities = $conn->query("
    SELECT
        'reservation' as type,
        CONCAT('New reservation for ', COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, 'Walk-in')) as description,
        r.created_at as timestamp
    FROM reservations r
    LEFT JOIN guests g ON r.guest_id = g.id
    UNION ALL
    SELECT
        'billing' as type,
        CONCAT('Payment of ₱', rb.payment_amount, ' received') as description,
        rb.created_at as timestamp
    FROM room_billing rb
    UNION ALL
    SELECT
        'housekeeping' as type,
        CONCAT('Room ', r.room_number, ' cleaning completed') as description,
        h.updated_at as timestamp
    FROM housekeeping h
    LEFT JOIN rooms r ON h.room_id = r.id
    WHERE h.status = 'Completed'
    ORDER BY timestamp DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Hotel Management System</title>

    <!-- CoreUI CSS -->
    <link href="css/coreui.min.css" rel="stylesheet">
    <link href="css/coreui-grid.min.css" rel="stylesheet">
    <link href="css/coreui-reboot.min.css" rel="stylesheet">
    <link href="css/coreui-utilities.min.css" rel="stylesheet">
    <link href="css/coreui-forms.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/all.min.css">

    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            transition: transform 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .click-card { cursor: pointer; }
        .revenue-toggle {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 18px;
            border: none;
            border-radius: 10px;
            background: rgba(0,0,0,0.6);
            color: #cfd4da;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
            cursor: pointer;
        }
        .revenue-toggle:hover {
            background: rgba(0,0,0,0.75);
            color: #ffffff;
        }
        .revenue-toggle i {
            font-size: 16px;
            color: #ffffff;
            display: inline-block;
            line-height: 1;
        }
        .activity-item {
            border-left: 3px solid;
            padding-left: 10px;
            margin-bottom: 10px;
        }
        .activity-reservation {
            border-left-color: #0dcaf0;
        }
        .activity-billing {
            border-left-color: #198754;
        }
        .activity-housekeeping {
            border-left-color: #fd7e14;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">

        <!-- Header with Title -->
        <div class="mb-4">
            <div class="text-center">
                <?php include 'analyticstitle.html'; ?>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-2 mb-3">
                <div class="card stats-card text-white h-100 click-card" onclick="window.location.href='dashboard.php?page=reservations'">
                    <div class="card-body text-center">
                        <i class="cil-calendar-check display-4 mb-2"></i>
                        <h3><?php echo number_format($summaryStats['total_reservations']); ?></h3>
                        <small>Total Reservations</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card text-white h-100 click-card" onclick="window.location.href='dashboard.php?page=guests'">
                    <div class="card-body text-center">
                        <i class="cil-people display-4 mb-2"></i>
                        <h3><?php echo number_format($summaryStats['total_guests']); ?></h3>
                        <small>Total Guests</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card text-white h-100 click-card" onclick="window.location.href='dashboard.php?page=rooms'">
                    <div class="card-body text-center">
                        <i class="cil-bed display-4 mb-2"></i>
                        <h3><?php echo $summaryStats['occupied_rooms']; ?>/<?php echo $summaryStats['total_rooms']; ?></h3>
                        <small>Room Occupancy</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card text-white h-100 position-relative">
                    <button class="revenue-toggle" id="toggleRevenue" aria-label="Toggle revenue visibility" title="Show/Hide">
                        <i class="cil-low-vision"></i>
                    </button>
                    <div class="card-body text-center">
                        <i class="cil-dollar display-4 mb-2"></i>
                        <h3 id="revenueValue">₱<?php echo number_format($summaryStats['today_revenue'], 2); ?></h3>
                        <small>Today's Revenue</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card text-white h-100 click-card" onclick="window.location.href='dashboard.php?page=housekeeping'">
                    <div class="card-body text-center">
                        <i class="cil-home display-4 mb-2"></i>
                        <h3><?php echo number_format($summaryStats['today_cleanings']); ?></h3>
                        <small>Today's Cleanings</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card text-white h-100">
                    <div class="card-body text-center">
                        <i class="cil-chart-line display-4 mb-2"></i>
                        <h3><?php echo number_format(($summaryStats['occupied_rooms'] / max($summaryStats['total_rooms'], 1)) * 100, 1); ?>%</h3>
                        <small>Occupancy Rate</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Analytics Overview</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm active" onclick="changeChart('reservations')">Reservations</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="changeChart('revenue')">Revenue</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="changeChart('occupancy')">Occupancy</button>
                        </div>
                        <select class="form-select form-select-sm" id="periodSelect" style="width: auto;">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="analyticsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item activity-<?php echo $activity['type']; ?>">
                            <small class="text-muted d-block"><?php echo date('M-d H:i', strtotime($activity['timestamp'])); ?></small>
                            <div><?php echo htmlspecialchars($activity['description']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Analytics -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Room Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="roomStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Reservation Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="reservationStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentChart = 'reservations';
        let analyticsChart;
        let roomStatusChart;
        let reservationStatusChart;

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            loadChartData('reservations', 30);
            // Revenue hide/show toggle
            const toggleBtn = document.getElementById('toggleRevenue');
            const revenueEl = document.getElementById('revenueValue');
            if (toggleBtn && revenueEl) {
                let hidden = false;
                const actualText = revenueEl.textContent;
                toggleBtn.addEventListener('click', function() {
                    hidden = !hidden;
                    if (hidden) {
                        revenueEl.textContent = '••••••••';
                        toggleBtn.innerHTML = '<i class="cil-eye"></i>';
                    } else {
                        revenueEl.textContent = actualText;
                        toggleBtn.innerHTML = '<i class="cil-low-vision"></i>';
                    }
                });
            }
        });

        function initializeCharts() {
            // Main analytics chart
            const ctx = document.getElementById('analyticsChart').getContext('2d');
            analyticsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Reservations',
                        data: [],
                        borderColor: '#0dcaf0',
                        backgroundColor: 'rgba(13, 202, 240, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Room status chart
            const roomCtx = document.getElementById('roomStatusChart').getContext('2d');
            roomStatusChart = new Chart(roomCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Vacant', 'Occupied', 'Maintenance', 'Reserved'],
                    datasets: [{
                        data: [<?php
                            $roomStats = $conn->query("SELECT room_status, COUNT(*) as count FROM rooms GROUP BY room_status")->fetchAll(PDO::FETCH_KEY_PAIR);
                            echo ($roomStats['Vacant'] ?? 0) . ',';
                            echo ($roomStats['Occupied'] ?? 0) . ',';
                            echo ($roomStats['Maintenance'] ?? 0) . ',';
                            echo ($roomStats['Reserved'] ?? 0);
                        ?>],
                        backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#0dcaf0']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Reservation status chart
            const resCtx = document.getElementById('reservationStatusChart').getContext('2d');
            reservationStatusChart = new Chart(resCtx, {
                type: 'pie',
                data: {
                    labels: ['Pending', 'Checked In', 'Checked Out', 'Cancelled', 'Archived'],
                    datasets: [{
                        data: [<?php
                            $resStats = $conn->query("SELECT reservation_status, COUNT(*) as count FROM reservations GROUP BY reservation_status")->fetchAll(PDO::FETCH_KEY_PAIR);
                            echo ($resStats['Pending'] ?? 0) . ',';
                            echo ($resStats['Checked In'] ?? 0) . ',';
                            echo ($resStats['Checked Out'] ?? 0) . ',';
                            echo ($resStats['Cancelled'] ?? 0) . ',';
                            echo ($resStats['Archived'] ?? 0);
                        ?>],
                        backgroundColor: ['#ffc107', '#28a745', '#0dcaf0', '#dc3545', '#6c757d']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        function changeChart(type) {
            currentChart = type;
            const buttons = document.querySelectorAll('.btn-group button');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const period = document.getElementById('periodSelect').value;
            loadChartData(type, period);
        }

        function loadChartData(type, period) {
            fetch('analytics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_chart_data&type=' + type + '&period=' + period
            })
            .then(response => response.json())
            .then(data => {
                updateChart(data, type);
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
        }

        function updateChart(data, type) {
            const labels = data.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });

            let dataset;
            if (type === 'reservations') {
                dataset = {
                    label: 'Reservations',
                    data: data.map(item => item.count),
                    borderColor: '#0dcaf0',
                    backgroundColor: 'rgba(13, 202, 240, 0.1)'
                };
            } else if (type === 'revenue') {
                dataset = {
                    label: 'Revenue (₱)',
                    data: data.map(item => item.revenue),
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)'
                };
            } else if (type === 'occupancy') {
                dataset = {
                    label: 'Occupancy Rate (%)',
                    data: data.map(item => item.total_rooms > 0 ? (item.occupied / item.total_rooms) * 100 : 0),
                    borderColor: '#fd7e14',
                    backgroundColor: 'rgba(253, 126, 20, 0.1)'
                };
            }

            analyticsChart.data.labels = labels;
            analyticsChart.data.datasets = [dataset];
            analyticsChart.update();
        }

        // Period selector change
        document.getElementById('periodSelect').addEventListener('change', function() {
            const period = this.value;
            loadChartData(currentChart, period);
        });
    </script>
</body>
</html>