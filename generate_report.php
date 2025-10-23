<?php
include_once 'db.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

$page = $_GET['page'] ?? 'channels';
$reportType = $_GET['type'] ?? 'pdf';

require_once 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
// Ensure fonts support the Peso sign (₱)
$options->set('defaultFont', 'DejaVu Sans');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

$dompdf = new Dompdf($options);

switch ($page) {
    case 'channels':
        // Channel Management Report
        $stmt = $conn->query("SELECT * FROM channels ORDER BY created_at DESC");
        $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->query("SELECT cb.*, c.channel_name FROM channel_bookings cb JOIN channels c ON cb.channel_id = c.id ORDER BY cb.booking_date DESC LIMIT 50");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; margin: 20px; }
                h1 { color: #0dcaf0; text-align: center; }
                h2 { color: #495057; border-bottom: 2px solid #0dcaf0; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .stats { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .stat-item { display: inline-block; margin-right: 20px; }
            </style>
        </head>
        <body>
            <h1>Channel Management Report</h1>
            <p><strong>Generated on:</strong> ' . date('F d, Y H:i:s') . '</p>

            <h2>Channel Statistics</h2>
            <div class="stats">
                <div class="stat-item"><strong>Total Channels:</strong> ' . count($channels) . '</div>
                <div class="stat-item"><strong>Active Channels:</strong> ' . count(array_filter($channels, fn($c) => $c['status'] === 'Active')) . '</div>
                <div class="stat-item"><strong>Pending Channels:</strong> ' . count(array_filter($channels, fn($c) => $c['status'] === 'Pending')) . '</div>
                <div class="stat-item"><strong>Average Commission:</strong> ' . (count($channels) > 0 ? number_format(array_sum(array_column($channels, 'commission_rate')) / count($channels), 1) : 0) . '%</div>
            </div>

            <h2>Channels List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Channel Name</th>
                        <th>Type</th>
                        <th>Commission Rate</th>
                        <th>Status</th>
                        <th>Contact Email</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($channels as $channel) {
            $html .= '<tr>
                <td>' . htmlspecialchars($channel['channel_name']) . '</td>
                <td>' . htmlspecialchars($channel['channel_type']) . '</td>
                <td>' . number_format($channel['commission_rate'], 2) . '%</td>
                <td>' . htmlspecialchars($channel['status']) . '</td>
                <td>' . htmlspecialchars($channel['contact_email'] ?: 'N/A') . '</td>
                <td>' . date('M d, Y', strtotime($channel['created_at'])) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>

            <h2>Recent Bookings</h2>
            <table>
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Booking Reference</th>
                        <th>Guest Name</th>
                        <th>Check-in Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($bookings as $booking) {
            $html .= '<tr>
                <td>' . htmlspecialchars($booking['channel_name']) . '</td>
                <td>' . htmlspecialchars($booking['booking_reference']) . '</td>
                <td>' . htmlspecialchars($booking['guest_name']) . '</td>
                <td>' . date('M d, Y', strtotime($booking['check_in_date'])) . '</td>
                <td>₱' . number_format($booking['total_amount'], 2) . '</td>
                <td>' . htmlspecialchars($booking['booking_status']) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>
        </body></html>';
        break;

    case 'guests':
        // Guest Management Report
        $stmt = $conn->query("SELECT * FROM guests ORDER BY created_at DESC");
        $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; margin: 20px; }
                h1 { color: #0dcaf0; text-align: center; }
                h2 { color: #495057; border-bottom: 2px solid #0dcaf0; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .stats { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <h1>Guest Management Report</h1>
            <p><strong>Generated on:</strong> ' . date('F d, Y H:i:s') . '</p>

            <div class="stats">
                <strong>Total Guests:</strong> ' . count($guests) . '
            </div>

            <h2>Guest List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>ID Type</th>
                        <th>ID Number</th>
                        <th>Nationality</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($guests as $guest) {
            $html .= '<tr>
                <td>' . htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']) . '</td>
                <td>' . htmlspecialchars($guest['email']) . '</td>
                <td>' . htmlspecialchars($guest['phone'] ?: 'N/A') . '</td>
                <td>' . htmlspecialchars($guest['id_type']) . '</td>
                <td>' . htmlspecialchars($guest['id_number']) . '</td>
                <td>' . htmlspecialchars($guest['nationality'] ?: 'N/A') . '</td>
                <td>' . date('M d, Y', strtotime($guest['created_at'])) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>
        </body></html>';
        break;

    case 'rooms':
        // Room Management Report
        $stmt = $conn->query("SELECT * FROM rooms ORDER BY room_floor ASC, room_number ASC");
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = $conn->query("
            SELECT
                COUNT(*) as total_rooms,
                COUNT(CASE WHEN room_status = 'Vacant' THEN 1 END) as vacant_rooms,
                COUNT(CASE WHEN room_status = 'Occupied' THEN 1 END) as occupied_rooms,
                COUNT(CASE WHEN room_type = 'Single' THEN 1 END) as single_rooms,
                COUNT(CASE WHEN room_type = 'Double' THEN 1 END) as double_rooms,
                COUNT(CASE WHEN room_type = 'Deluxe' THEN 1 END) as deluxe_rooms,
                COUNT(CASE WHEN room_type = 'Suite' THEN 1 END) as suite_rooms
            FROM rooms
        ")->fetch(PDO::FETCH_ASSOC);

        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; margin: 20px; }
                h1 { color: #0dcaf0; text-align: center; }
                h2 { color: #495057; border-bottom: 2px solid #0dcaf0; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .stats { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .stat-item { display: inline-block; margin-right: 20px; }
            </style>
        </head>
        <body>
            <h1>Room Management Report</h1>
            <p><strong>Generated on:</strong> ' . date('F d, Y H:i:s') . '</p>

            <h2>Room Statistics</h2>
            <div class="stats">
                <div class="stat-item"><strong>Total Rooms:</strong> ' . $stats['total_rooms'] . '</div>
                <div class="stat-item"><strong>Vacant:</strong> ' . $stats['vacant_rooms'] . '</div>
                <div class="stat-item"><strong>Occupied:</strong> ' . $stats['occupied_rooms'] . '</div>
                <div class="stat-item"><strong>Single Rooms:</strong> ' . $stats['single_rooms'] . '</div>
                <div class="stat-item"><strong>Double Rooms:</strong> ' . $stats['double_rooms'] . '</div>
                <div class="stat-item"><strong>Deluxe Rooms:</strong> ' . $stats['deluxe_rooms'] . '</div>
                <div class="stat-item"><strong>Suites:</strong> ' . $stats['suite_rooms'] . '</div>
            </div>

            <h2>Room List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Type</th>
                        <th>Floor</th>
                        <th>Status</th>
                        <th>Max Guests</th>
                        <th>Amenities</th>
                        <th>Last Cleaned</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($rooms as $room) {
            $html .= '<tr>
                <td>' . htmlspecialchars($room['room_number']) . '</td>
                <td>' . htmlspecialchars($room['room_type']) . '</td>
                <td>' . htmlspecialchars($room['room_floor']) . '</td>
                <td>' . htmlspecialchars($room['room_status']) . '</td>
                <td>' . htmlspecialchars($room['room_max_guests']) . '</td>
                <td>' . htmlspecialchars($room['room_amenities'] ?: 'N/A') . '</td>
                <td>' . ($room['room_last_cleaned'] ? date('M d, Y', strtotime($room['room_last_cleaned'])) : 'Never') . '</td>
            </tr>';
        }

        $html .= '</tbody></table>
        </body></html>';
        break;

    case 'events':
        // Event Management Report
        $stmt = $conn->query("SELECT * FROM event_venues ORDER BY created_at DESC");
        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->query("
            SELECT er.*, ev.venue_name, ev.venue_capacity
            FROM event_reservation er
            LEFT JOIN event_venues ev ON er.event_venue_id = ev.id
            ORDER BY er.created_at DESC
        ");
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = $conn->query("
            SELECT
                (SELECT COUNT(*) FROM event_venues) as total_venues,
                (SELECT COUNT(*) FROM event_venues WHERE venue_status = 'Available') as available_venues,
                (SELECT COUNT(*) FROM event_reservation) as total_reservations,
                (SELECT COUNT(*) FROM event_reservation WHERE event_status = 'Checked In') as active_events,
                (SELECT COUNT(*) FROM event_reservation WHERE event_status = 'Archived') as archived_events
        ")->fetch(PDO::FETCH_ASSOC);

        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; margin: 20px; }
                h1 { color: #0dcaf0; text-align: center; }
                h2 { color: #495057; border-bottom: 2px solid #0dcaf0; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .stats { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .stat-item { display: inline-block; margin-right: 20px; }
            </style>
        </head>
        <body>
            <h1>Event Management Report</h1>
            <p><strong>Generated on:</strong> ' . date('F d, Y H:i:s') . '</p>

            <h2>Event Statistics</h2>
            <div class="stats">
                <div class="stat-item"><strong>Total Venues:</strong> ' . $stats['total_venues'] . '</div>
                <div class="stat-item"><strong>Available Venues:</strong> ' . $stats['available_venues'] . '</div>
                <div class="stat-item"><strong>Total Reservations:</strong> ' . $stats['total_reservations'] . '</div>
                <div class="stat-item"><strong>Active Events:</strong> ' . $stats['active_events'] . '</div>
                <div class="stat-item"><strong>Archived Events:</strong> ' . $stats['archived_events'] . '</div>
            </div>

            <h2>Venues List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Venue Name</th>
                        <th>Address</th>
                        <th>Capacity</th>
                        <th>Rate (per hour)</th>
                        <th>Status</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($venues as $venue) {
            $html .= '<tr>
                <td>' . htmlspecialchars($venue['venue_name']) . '</td>
                <td>' . htmlspecialchars($venue['venue_address']) . '</td>
                <td>' . htmlspecialchars($venue['venue_capacity']) . '</td>
                <td>₱' . number_format($venue['venue_rate'] ?: 0, 2) . '</td>
                <td>' . htmlspecialchars($venue['venue_status']) . '</td>
                <td>' . date('M d, Y', strtotime($venue['created_at'])) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>

            <h2>Event Reservations</h2>
            <table>
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Organizer</th>
                        <th>Venue</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Attendees</th>
                        <th>Status</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($reservations as $reservation) {
            $html .= '<tr>
                <td>' . htmlspecialchars($reservation['event_title']) . '</td>
                <td>' . htmlspecialchars($reservation['event_organizer']) . '</td>
                <td>' . htmlspecialchars($reservation['venue_name'] ?: 'No venue') . '</td>
                <td>' . date('M d, Y H:i', strtotime($reservation['event_checkin'])) . '</td>
                <td>' . date('M d, Y H:i', strtotime($reservation['event_checkout'])) . '</td>
                <td>' . htmlspecialchars($reservation['event_expected_attendees']) . '</td>
                <td>' . htmlspecialchars($reservation['event_status']) . '</td>
                <td>' . date('M d, Y', strtotime($reservation['created_at'])) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>
        </body></html>';
        break;

    case 'inventory':
        // Inventory Management Report
        $stmt = $conn->query("SELECT * FROM items ORDER BY created_at DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->query("
            SELECT im.*, COALESCE(i.item_name, 'Deleted Item') as item_name
            FROM inventory_movements im
            LEFT JOIN items i ON im.item_id = i.id
            ORDER BY im.movement_date DESC
            LIMIT 50
        ");
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = $conn->query("
            SELECT
                COUNT(*) as total_items,
                COUNT(CASE WHEN item_status = 'Active' THEN 1 END) as active_items,
                COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock_items,
                SUM(current_stock * unit_cost) as total_value
            FROM items
        ")->fetch(PDO::FETCH_ASSOC);

        $movement_stats = $conn->query("
            SELECT
                SUM(CASE WHEN movement_type = 'IN' THEN quantity ELSE 0 END) as total_in,
                SUM(CASE WHEN movement_type = 'OUT' THEN quantity ELSE 0 END) as total_out
            FROM inventory_movements
        ")->fetch(PDO::FETCH_ASSOC);

        $stats['total_in'] = $movement_stats['total_in'] ?: 0;
        $stats['total_out'] = $movement_stats['total_out'] ?: 0;

        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; margin: 20px; }
                h1 { color: #0dcaf0; text-align: center; }
                h2 { color: #495057; border-bottom: 2px solid #0dcaf0; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .stats { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .stat-item { display: inline-block; margin-right: 20px; }
            </style>
        </head>
        <body>
            <h1>Inventory Management Report</h1>
            <p><strong>Generated on:</strong> ' . date('F d, Y H:i:s') . '</p>

            <h2>Inventory Statistics</h2>
            <div class="stats">
                <div class="stat-item"><strong>Total Items:</strong> ' . $stats['total_items'] . '</div>
                <div class="stat-item"><strong>Active Items:</strong> ' . $stats['active_items'] . '</div>
                <div class="stat-item"><strong>Low Stock Items:</strong> ' . $stats['low_stock_items'] . '</div>
                <div class="stat-item"><strong>Total Value:</strong> ₱' . number_format($stats['total_value'] ?: 0, 2) . '</div>
                <div class="stat-item"><strong>Total Stock In:</strong> ' . $stats['total_in'] . '</div>
                <div class="stat-item"><strong>Total Stock Out:</strong> ' . $stats['total_out'] . '</div>
            </div>

            <h2>Items List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Min Stock</th>
                        <th>Max Stock</th>
                        <th>Unit Cost</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($items as $item) {
            $html .= '<tr>
                <td>' . htmlspecialchars($item['item_name']) . '</td>
                <td>' . htmlspecialchars($item['item_category'] ?: 'N/A') . '</td>
                <td>' . htmlspecialchars($item['current_stock']) . ' ' . htmlspecialchars($item['unit_of_measure']) . '</td>
                <td>' . htmlspecialchars($item['minimum_stock']) . '</td>
                <td>' . htmlspecialchars($item['maximum_stock']) . '</td>
                <td>₱' . number_format($item['unit_cost'], 2) . '</td>
                <td>₱' . number_format($item['unit_price'], 2) . '</td>
                <td>₱' . number_format($item['current_stock'] * $item['unit_cost'], 2) . '</td>
                <td>' . htmlspecialchars($item['item_status']) . '</td>
                <td>' . date('M d, Y', strtotime($item['created_at'])) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>

            <h2>Recent Movements</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reason</th>
                        <th>Reference</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($movements as $movement) {
            $html .= '<tr>
                <td>' . htmlspecialchars($movement['item_name']) . '</td>
                <td>' . htmlspecialchars($movement['movement_type']) . '</td>
                <td>' . number_format($movement['quantity'], 2) . '</td>
                <td>' . htmlspecialchars($movement['reason'] ?: 'N/A') . '</td>
                <td>' . htmlspecialchars($movement['reference_id'] ?: 'N/A') . '</td>
                <td>' . date('M d, Y H:i', strtotime($movement['movement_date'])) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>
        </body></html>';
        break;

    default:
        $html = '<html><body><h1>Report Not Available</h1><p>The requested report type is not available.</p></body></html>';
}

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream($page . '_report_' . date('Y-m-d') . '.pdf', array('Attachment' => true));
exit;
?>