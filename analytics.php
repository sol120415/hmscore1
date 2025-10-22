<?php
include_once 'db.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

// Fetch comprehensive analytics data from database
// Room Statistics
$roomStats = $conn->query("
    SELECT 
        COUNT(*) as total_rooms,
        COUNT(CASE WHEN room_status = 'Vacant' THEN 1 END) as vacant_rooms,
        COUNT(CASE WHEN room_status = 'Occupied' THEN 1 END) as occupied_rooms,
        COUNT(CASE WHEN room_status = 'Maintenance' THEN 1 END) as maintenance_rooms,
        COUNT(CASE WHEN room_type = 'Single' THEN 1 END) as single_rooms,
        COUNT(CASE WHEN room_type = 'Double' THEN 1 END) as double_rooms,
        COUNT(CASE WHEN room_type = 'Deluxe' THEN 1 END) as deluxe_rooms,
        COUNT(CASE WHEN room_type = 'Suite' THEN 1 END) as suite_rooms
    FROM rooms
")->fetch(PDO::FETCH_ASSOC);

// Reservation Statistics
$reservationStats = $conn->query("
    SELECT 
        COUNT(*) as total_reservations,
        COUNT(CASE WHEN reservation_status = 'Pending' THEN 1 END) as pending_reservations,
        COUNT(CASE WHEN reservation_status = 'Checked In' THEN 1 END) as checked_in_reservations,
        COUNT(CASE WHEN reservation_status = 'Checked Out' THEN 1 END) as checked_out_reservations,
        COUNT(CASE WHEN reservation_status = 'Cancelled' THEN 1 END) as cancelled_reservations
    FROM reservations
")->fetch(PDO::FETCH_ASSOC);

// Monthly Reservations Trend (Last 6 months)
$monthlyReservations = $conn->query("
    SELECT 
        DATE_FORMAT(check_in_date, '%Y-%m') as month,
        COUNT(*) as count
    FROM reservations
    WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(check_in_date, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Billing Statistics
$billingStats = $conn->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(payment_amount) as total_revenue,
        AVG(payment_amount) as avg_transaction,
        COUNT(CASE WHEN billing_status = 'Paid' THEN 1 END) as paid_count,
        COUNT(CASE WHEN billing_status = 'Pending' THEN 1 END) as pending_count,
        SUM(CASE WHEN billing_status = 'Paid' THEN payment_amount ELSE 0 END) as paid_revenue
    FROM room_billing
")->fetch(PDO::FETCH_ASSOC);

// Monthly Revenue Trend (Last 6 months)
$monthlyRevenue = $conn->query("
    SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(payment_amount) as revenue
    FROM room_billing
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    AND billing_status = 'Paid'
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Payment Method Distribution
$paymentMethods = $conn->query("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(payment_amount) as total
    FROM room_billing
    WHERE billing_status = 'Paid'
    GROUP BY payment_method
")->fetchAll(PDO::FETCH_ASSOC);

// Guest Statistics
$guestStats = $conn->query("
    SELECT 
        COUNT(*) as total_guests,
        COUNT(CASE WHEN guest_status = 'Active' THEN 1 END) as active_guests,
        COUNT(CASE WHEN loyalty_status = 'Regular' THEN 1 END) as regular_guests,
        COUNT(CASE WHEN loyalty_status = 'Iron' THEN 1 END) as iron_guests,
        COUNT(CASE WHEN loyalty_status = 'Gold' THEN 1 END) as gold_guests,
        COUNT(CASE WHEN loyalty_status = 'Diamond' THEN 1 END) as diamond_guests,
        AVG(stay_count) as avg_stay_count,
        AVG(total_spend) as avg_total_spend
    FROM guests
")->fetch(PDO::FETCH_ASSOC);

// Event Statistics
$eventStats = $conn->query("
    SELECT 
        COUNT(*) as total_events,
        COUNT(CASE WHEN event_status = 'Pending' THEN 1 END) as pending_events,
        COUNT(CASE WHEN event_status = 'Checked In' THEN 1 END) as active_events,
        COUNT(CASE WHEN event_status = 'Checked Out' THEN 1 END) as completed_events,
        AVG(event_expected_attendees) as avg_attendees
    FROM event_reservation
")->fetch(PDO::FETCH_ASSOC);

// Inventory Statistics
$inventoryStats = $conn->query("
    SELECT 
        COUNT(*) as total_items,
        COUNT(CASE WHEN item_status = 'Active' THEN 1 END) as active_items,
        COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock_items,
        SUM(current_stock * unit_cost) as total_inventory_value
    FROM items
")->fetch(PDO::FETCH_ASSOC);

// Channel Statistics
$channelStats = $conn->query("
    SELECT 
        c.channel_name,
        COUNT(cb.id) as booking_count,
        SUM(cb.total_amount) as total_revenue
    FROM channels c
    LEFT JOIN channel_bookings cb ON c.id = cb.channel_id
    WHERE c.status = 'Active'
    GROUP BY c.id, c.channel_name
    ORDER BY total_revenue DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Housekeeping Statistics
$housekeepingStats = $conn->query("
    SELECT 
        COUNT(*) as total_tasks,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_tasks,
        COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as in_progress_tasks,
        COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_tasks,
        AVG(actual_duration_minutes) as avg_duration
    FROM housekeeping
")->fetch(PDO::FETCH_ASSOC);

// Occupancy Rate Calculation
$occupancyRate = $roomStats['total_rooms'] > 0 ? 
    round(($roomStats['occupied_rooms'] / $roomStats['total_rooms']) * 100, 1) : 0;

// Revenue per Available Room (RevPAR)
$revPAR = $roomStats['total_rooms'] > 0 ? 
    round(($billingStats['paid_revenue'] ?? 0) / $roomStats['total_rooms'], 2) : 0;
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Hotel Management System</title>
    <script>(function(){try{var s=localStorage.getItem('theme-preference');var t=(s==='light'||s==='dark')?s:(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-coreui-theme',t);var bg=t==='dark'?'#1a1a1a':'#ffffff';document.documentElement.style.backgroundColor=bg;document.documentElement.style.background=bg;var st=document.createElement('style');st.id='early-theme-bg';st.textContent='html,body{background:'+bg+' !important;}';document.head.appendChild(st);}catch(e){}})();</script>

    <!-- CoreUI CSS -->
    <link href="css/coreui.min.css" rel="stylesheet">
    <link href="css/coreui-grid.min.css" rel="stylesheet">
    <link href="css/coreui-utilities.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/all.min.css">
    <link href="css/theme-system.css" rel="stylesheet">
    <link href="css/analytics-theme.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="js/page-loader.js"></script>
    <script src="js/app-modal.js"></script>

    <style>
        .analytics-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
        }
        .chart-container {
            position: relative;
            height: 350px;
            padding: 20px;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        /* Theme-aware analytics surfaces */
        .card { background-color: var(--theme-bg-primary); border-color: var(--theme-border-color); color: var(--theme-text-primary); }
        .card-header { background-color: var(--theme-bg-secondary); border-bottom-color: var(--theme-border-color); color: var(--theme-text-primary); }
        body { color: var(--theme-text-primary) !important; }
        .text-muted { color: var(--theme-text-secondary) !important; }
        .table { color: var(--theme-text-primary); }
        .table thead th { background-color: var(--theme-bg-secondary); color: var(--theme-text-primary); border-color: var(--theme-border-color); }
        .table tbody td, .table tbody th { background-color: var(--theme-bg-primary); color: var(--theme-text-primary); border-color: var(--theme-border-color); }
        /* Chart canvas background for light mode so axes are visible */
        [data-theme="light"] .chart-container { background-color: #ffffff; }
        [data-theme="dark"] .chart-container { background-color: #23272b; }
        .table-hover > tbody > tr:hover > * { background-color: var(--theme-bg-secondary) !important; }
        .table-striped > tbody > tr:nth-of-type(odd) > * { background-color: var(--theme-bg-secondary) !important; }
        .table, .table th, .table td { border-color: var(--theme-border-color) !important; }
        /* High-contrast tables in light mode */
        [data-theme="light"] .table { color: #212529 !important; border-color: #dee2e6 !important; }
        [data-theme="light"] .table thead th { background-color: #f8f9fa !important; color: #212529 !important; border-color: #dee2e6 !important; }
        [data-theme="light"] .table tbody td,
        [data-theme="light"] .table tbody th { background-color: #ffffff !important; color: #212529 !important; border-color: #dee2e6 !important; }
        [data-theme="light"] .table-hover > tbody > tr:hover > * { background-color: #f1f3f5 !important; color: #212529 !important; }
        /* High-contrast tables in dark mode */
        [data-theme="dark"] .table { color: #ffffff !important; border-color: #495057 !important; }
        [data-theme="dark"] .table thead th { background-color: #2d2d2d !important; color: #ffffff !important; border-color: #495057 !important; }
        [data-theme="dark"] .table tbody td,
        [data-theme="dark"] .table tbody th { background-color: #1a1a1a !important; color: #ffffff !important; border-color: #495057 !important; }
        [data-theme="dark"] .table-hover > tbody > tr:hover > * { background-color: #2a2c30 !important; color: #ffffff !important; }
        [data-theme="dark"] .card-header, [data-theme="dark"] .card-header h5 { background-color: #2d2d2d !important; color: #ffffff !important; }
    </style>
</head>
<body>
    <div class="container-fluid p-4 analytics-page">
        
        <!-- Header -->
        <div class="mb-4">
            <h2 class="mb-3">Analytics Dashboard</h2>
            <p class="text-muted">Comprehensive insights and performance metrics</p>
        </div>

        <!-- Key Performance Indicators -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card analytics-card h-100">
                    <div class="card-body text-center">
                        <i class="cil-chart-line display-4 mb-2"></i>
                        <div class="metric-value"><?php echo $occupancyRate; ?>%</div>
                        <div class="metric-label">Occupancy Rate</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card analytics-card h-100">
                    <div class="card-body text-center">
                        <i class="cil-money display-4 mb-2"></i>
                        <div class="metric-value">₱<?php echo number_format($billingStats['total_revenue'] ?? 0, 0); ?></div>
                        <div class="metric-label">Total Revenue</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card analytics-card h-100">
                    <div class="card-body text-center">
                        <i class="cil-people display-4 mb-2"></i>
                        <div class="metric-value"><?php echo $guestStats['total_guests']; ?></div>
                        <div class="metric-label">Total Guests</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card analytics-card h-100">
                    <div class="card-body text-center">
                        <i class="cil-calendar display-4 mb-2"></i>
                        <div class="metric-value"><?php echo $reservationStats['total_reservations']; ?></div>
                        <div class="metric-label">Total Reservations</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1: Room & Reservation Analytics -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
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
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Room Type Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="roomTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Reservation & Billing Trends -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Reservation Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="reservationStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Monthly Reservations Trend</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyReservationsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 3: Revenue Analytics -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Monthly Revenue Trend</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyRevenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Method Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="paymentMethodChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 4: Guest & Channel Analytics -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Guest Loyalty Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="guestLoyaltyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top Booking Channels by Revenue</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="channelRevenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 5: Operations Analytics -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Housekeeping Task Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="housekeepingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Event Status Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="eventStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Revenue Per Available Room (RevPAR)</h6>
                        <h3 class="mb-0">₱<?php echo number_format($revPAR, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Average Transaction Value</h6>
                        <h3 class="mb-0">₱<?php echo number_format($billingStats['avg_transaction'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Inventory Value</h6>
                        <h3 class="mb-0">₱<?php echo number_format($inventoryStats['total_inventory_value'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Active Events</h6>
                        <h3 class="mb-0"><?php echo $eventStats['active_events']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Low Stock Items</h6>
                        <h3 class="mb-0"><?php echo $inventoryStats['low_stock_items']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Pending Housekeeping</h6>
                        <h3 class="mb-0"><?php echo $housekeepingStats['pending_tasks']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Average Guest Stays</h6>
                        <h3 class="mb-0"><?php echo number_format($guestStats['avg_stay_count'], 1); ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- CoreUI JS -->
    <script src="js/coreui.bundle.js"></script>

    <script>
        // Chart.js theme application that updates existing charts
        (function(){
            const charts = [];
            function getTheme() {
                return document.documentElement.getAttribute('data-theme') || 'light';
            }
            function getChartColors(theme){
                if (theme === 'dark') {
                    return {
                        text: '#ffffff',
                        grid: 'rgba(255,255,255,0.12)',
                        tooltipBg: '#2a2c30',
                        tooltipTitle: '#ffffff',
                        tooltipBody: '#e9ecef'
                    };
                }
                return {
                    text: '#212529',
                    grid: 'rgba(0,0,0,0.12)',
                    tooltipBg: '#ffffff',
                    tooltipTitle: '#212529',
                    tooltipBody: '#212529'
                };
            }
            function applyChartTheme(chart){
                const theme = getTheme();
                const c = getChartColors(theme);
                // Legend text
                if (chart.options.plugins && chart.options.plugins.legend) {
                    chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
                    chart.options.plugins.legend.labels.color = c.text;
                }
                // Tooltip colors
                if (chart.options.plugins && chart.options.plugins.tooltip) {
                    chart.options.plugins.tooltip.backgroundColor = c.tooltipBg;
                    chart.options.plugins.tooltip.titleColor = c.tooltipTitle;
                    chart.options.plugins.tooltip.bodyColor = c.tooltipBody;
                }
                // Scales
                if (chart.options.scales) {
                    Object.keys(chart.options.scales).forEach((k)=>{
                        const s = chart.options.scales[k];
                        if (!s.ticks) s.ticks = {};
                        if (!s.grid) s.grid = {};
                        s.ticks.color = c.text;
                        s.grid.color = c.grid;
                        s.grid.borderColor = c.grid;
                    });
                }
                chart.update('none');
            }
            function applyThemeToAll(){
                charts.forEach(applyChartTheme);
            }
            // Expose helpers so we can push charts as we create them
            window.__ANALYTICS_CHARTS__ = charts;
            window.__APPLY_ANALYTICS_THEME__ = applyThemeToAll;
            // Apply on theme change
            window.addEventListener('themechange', applyThemeToAll);
        })();

        // Room Status Chart
        (function(){ const ch = new Chart(document.getElementById('roomStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Vacant', 'Occupied', 'Maintenance'],
                datasets: [{
                    data: [
                        <?php echo $roomStats['vacant_rooms']; ?>,
                        <?php echo $roomStats['occupied_rooms']; ?>,
                        <?php echo $roomStats['maintenance_rooms']; ?>
                    ],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();

        // Room Type Chart
        (function(){ const ch = new Chart(document.getElementById('roomTypeChart'), {
            type: 'bar',
            data: {
                labels: ['Single', 'Double', 'Deluxe', 'Suite'],
                datasets: [{
                    label: 'Number of Rooms',
                    data: [
                        <?php echo $roomStats['single_rooms']; ?>,
                        <?php echo $roomStats['double_rooms']; ?>,
                        <?php echo $roomStats['deluxe_rooms']; ?>,
                        <?php echo $roomStats['suite_rooms']; ?>
                    ],
                    backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();

        // Reservation Status Chart
        (function(){ const ch = new Chart(document.getElementById('reservationStatusChart'), {
            type: 'pie',
            data: {
                labels: ['Pending', 'Checked In', 'Checked Out', 'Cancelled'],
                datasets: [{
                    data: [
                        <?php echo $reservationStats['pending_reservations']; ?>,
                        <?php echo $reservationStats['checked_in_reservations']; ?>,
                        <?php echo $reservationStats['checked_out_reservations']; ?>,
                        <?php echo $reservationStats['cancelled_reservations']; ?>
                    ],
                    backgroundColor: ['#ffc107', '#28a745', '#17a2b8', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();

        // Monthly Reservations Trend
        (function(){ const ch = new Chart(document.getElementById('monthlyReservationsChart'), {
            type: 'line',
            data: {
                labels: [<?php 
                    foreach ($monthlyReservations as $data) {
                        echo "'" . date('M Y', strtotime($data['month'] . '-01')) . "',";
                    }
                ?>],
                datasets: [{
                    label: 'Reservations',
                    data: [<?php 
                        foreach ($monthlyReservations as $data) {
                            echo $data['count'] . ',';
                        }
                    ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();

        // Monthly Revenue Trend
        (function(){ const ch = new Chart(document.getElementById('monthlyRevenueChart'), {
            type: 'line',
            data: {
                labels: [<?php 
                    foreach ($monthlyRevenue as $data) {
                        echo "'" . date('M Y', strtotime($data['month'] . '-01')) . "',";
                    }
                ?>],
                datasets: [{
                    label: 'Revenue (₱)',
                    data: [<?php 
                        foreach ($monthlyRevenue as $data) {
                            echo $data['revenue'] . ',';
                        }
                    ?>],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();

        // Payment Method Chart
        (function(){ const ch = new Chart(document.getElementById('paymentMethodChart'), {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    foreach ($paymentMethods as $method) {
                        echo "'" . $method['payment_method'] . "',";
                    }
                ?>],
                datasets: [{
                    data: [<?php 
                        foreach ($paymentMethods as $method) {
                            echo $method['total'] . ',';
                        }
                    ?>],
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();

        // Guest Loyalty Chart
        (function(){ const ch = new Chart(document.getElementById('guestLoyaltyChart'), {
            type: 'bar',
            data: {
                labels: ['Regular', 'Iron', 'Gold', 'Diamond'],
                datasets: [{
                    label: 'Number of Guests',
                    data: [
                        <?php echo $guestStats['regular_guests']; ?>,
                        <?php echo $guestStats['iron_guests']; ?>,
                        <?php echo $guestStats['gold_guests']; ?>,
                        <?php echo $guestStats['diamond_guests']; ?>
                    ],
                    backgroundColor: ['#6c757d', '#8e9aaf', '#ffd700', '#b9f2ff']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();

        // Channel Revenue Chart
        (function(){ const ch = new Chart(document.getElementById('channelRevenueChart'), {
            type: 'horizontalBar',
            data: {
                labels: [<?php 
                    foreach ($channelStats as $channel) {
                        echo "'" . addslashes($channel['channel_name']) . "',";
                    }
                ?>],
                datasets: [{
                    label: 'Revenue (₱)',
                    data: [<?php 
                        foreach ($channelStats as $channel) {
                            echo ($channel['total_revenue'] ?? 0) . ',';
                        }
                    ?>],
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();

        // Housekeeping Chart
        (function(){ const ch = new Chart(document.getElementById('housekeepingChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $housekeepingStats['pending_tasks']; ?>,
                        <?php echo $housekeepingStats['in_progress_tasks']; ?>,
                        <?php echo $housekeepingStats['completed_tasks']; ?>
                    ],
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();

        // Event Status Chart
        (function(){ const ch = new Chart(document.getElementById('eventStatusChart'), {
            type: 'pie',
            data: {
                labels: ['Pending', 'Active', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $eventStats['pending_events']; ?>,
                        <?php echo $eventStats['active_events']; ?>,
                        <?php echo $eventStats['completed_events']; ?>
                    ],
                    backgroundColor: ['#ffc107', '#28a745', '#17a2b8']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        }); window.__ANALYTICS_CHARTS__.push(ch); __APPLY_ANALYTICS_THEME__(); })();
    </script>
</body>
</html>