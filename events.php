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
                case 'create_venue':
                    // Create new venue
                    $stmt = $conn->prepare("INSERT INTO event_venues (venue_name, venue_address, venue_capacity, venue_description, venue_status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['venue_name'],
                        $_POST['venue_address'],
                        $_POST['venue_capacity'],
                        $_POST['venue_description'] ?: null,
                        $_POST['venue_status']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Venue created successfully']);
                    break;

                case 'update_venue':
                    // Update venue
                    $stmt = $conn->prepare("UPDATE event_venues SET venue_name=?, venue_address=?, venue_capacity=?, venue_description=?, venue_status=? WHERE id=?");
                    $stmt->execute([
                        $_POST['venue_name'],
                        $_POST['venue_address'],
                        $_POST['venue_capacity'],
                        $_POST['venue_description'] ?: null,
                        $_POST['venue_status'],
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Venue updated successfully']);
                    break;

                case 'delete_venue':
                    // Delete venue
                    $stmt = $conn->prepare("DELETE FROM event_venues WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Venue deleted successfully']);
                    break;

                case 'get_venue':
                    // Get venue data for editing
                    $stmt = $conn->prepare("SELECT * FROM event_venues WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $venue = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($venue);
                    break;

                case 'create_reservation':
                    // Create new reservation
                    $stmt = $conn->prepare("INSERT INTO event_reservation (event_title, event_organizer, event_organizer_contact, event_expected_attendees, event_description, event_venue_id, event_checkin, event_checkout, event_hour_count, event_days_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['event_title'],
                        $_POST['event_organizer'],
                        $_POST['event_organizer_contact'],
                        $_POST['event_expected_attendees'],
                        $_POST['event_description'] ?: null,
                        $_POST['event_venue_id'] ?: null,
                        $_POST['event_checkin'],
                        $_POST['event_checkout'],
                        $_POST['event_hour_count'],
                        $_POST['event_days_count']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Reservation created successfully']);
                    break;

                case 'update_reservation':
                    // Update reservation
                    $stmt = $conn->prepare("UPDATE event_reservation SET event_title=?, event_organizer=?, event_organizer_contact=?, event_expected_attendees=?, event_description=?, event_venue_id=?, event_checkin=?, event_checkout=?, event_hour_count=?, event_days_count=?, event_status=? WHERE id=?");
                    $stmt->execute([
                        $_POST['event_title'],
                        $_POST['event_organizer'],
                        $_POST['event_organizer_contact'],
                        $_POST['event_expected_attendees'],
                        $_POST['event_description'] ?: null,
                        $_POST['event_venue_id'] ?: null,
                        $_POST['event_checkin'],
                        $_POST['event_checkout'],
                        $_POST['event_hour_count'],
                        $_POST['event_days_count'],
                        $_POST['event_status'],
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Reservation updated successfully']);
                    break;

                case 'delete_reservation':
                    // Delete reservation
                    $stmt = $conn->prepare("DELETE FROM event_reservation WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Reservation deleted successfully']);
                    break;

                case 'get_reservation':
                    // Get reservation data for editing
                    $stmt = $conn->prepare("SELECT * FROM event_reservation WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($reservation);
                    break;

                case 'create_billing':
                    // Create new billing transaction
                    $stmt = $conn->prepare("INSERT INTO event_billing (transaction_type, event_reservation_id, payment_amount, balance, payment_method, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['transaction_type'],
                        $_POST['event_reservation_id'] ?: null,
                        $_POST['payment_amount'],
                        $_POST['balance'],
                        $_POST['payment_method'],
                        $_POST['status'],
                        $_POST['notes'] ?: null
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Billing transaction created successfully']);
                    break;

                case 'update_billing':
                    // Update billing transaction
                    $stmt = $conn->prepare("UPDATE event_billing SET transaction_type=?, event_reservation_id=?, payment_amount=?, balance=?, payment_method=?, status=?, notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['transaction_type'],
                        $_POST['event_reservation_id'] ?: null,
                        $_POST['payment_amount'],
                        $_POST['balance'],
                        $_POST['payment_method'],
                        $_POST['status'],
                        $_POST['notes'] ?: null,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Billing transaction updated successfully']);
                    break;

                case 'delete_billing':
                    // Delete billing transaction
                    $stmt = $conn->prepare("DELETE FROM event_billing WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Billing transaction deleted successfully']);
                    break;

                case 'get_billing':
                    // Get billing data for editing
                    $stmt = $conn->prepare("SELECT * FROM event_billing WHERE id=?");
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
$venues = $conn->query("SELECT * FROM event_venues ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$reservations = $conn->query("
    SELECT er.*, ev.venue_name, ev.venue_capacity
    FROM event_reservation er
    LEFT JOIN event_venues ev ON er.event_venue_id = ev.id
    ORDER BY er.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
$billings = $conn->query("
    SELECT eb.*, er.event_title
    FROM event_billing eb
    LEFT JOIN event_reservation er ON eb.event_reservation_id = er.id
    ORDER BY eb.transaction_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM event_venues) as total_venues,
        (SELECT COUNT(*) FROM event_venues WHERE venue_status = 'Available') as available_venues,
        (SELECT COUNT(*) FROM event_reservation) as total_reservations,
        (SELECT COUNT(*) FROM event_reservation WHERE event_status = 'Checked In') as active_events,
        (SELECT SUM(payment_amount) FROM event_billing WHERE status = 'Paid') as total_revenue
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - Hotel Management System</title>

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
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <div class="text-center flex-grow-1">
                    <?php include 'eventstitle.html'; ?>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Venues</h6>
                                <h3 class="mb-0"><?php echo $stats['total_venues']; ?></h3>
                            </div>
                            <i class="cil-building fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Available</h6>
                                <h3 class="mb-0"><?php echo $stats['available_venues']; ?></h3>
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
                                <h6 class="card-title mb-1">Reservations</h6>
                                <h3 class="mb-0"><?php echo $stats['total_reservations']; ?></h3>
                            </div>
                            <i class="cil-calendar fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Active Events</h6>
                                <h3 class="mb-0"><?php echo $stats['active_events']; ?></h3>
                            </div>
                            <i class="cil-play-circle fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
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
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="eventTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="venues-tab" data-coreui-toggle="tab" data-coreui-target="#venues" type="button" role="tab">Venues</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reservations-tab" data-coreui-toggle="tab" data-coreui-target="#reservations" type="button" role="tab">Reservations</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="billing-tab" data-coreui-toggle="tab" data-coreui-target="#billing" type="button" role="tab">Billing</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Venues Tab -->
            <div class="tab-pane fade show active" id="venues" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Event Venues</h4>
                    <button class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#venueModal" onclick="openCreateVenueModal()">
                        <i class="cil-plus me-2"></i>Add Venue
                    </button>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Venue Name</th>
                                        <th>Address</th>
                                        <th>Capacity</th>
                                        <th>Rate</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($venues as $venue): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($venue['venue_name']); ?></td>
                                        <td><?php echo htmlspecialchars($venue['venue_address']); ?></td>
                                        <td><?php echo htmlspecialchars($venue['venue_capacity']); ?></td>
                                        <td>$<?php echo number_format($venue['venue_rate'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $venue['venue_status'] === 'Available' ? 'success' : ($venue['venue_status'] === 'Booked' ? 'warning' : 'danger'); ?>">
                                                <?php echo htmlspecialchars($venue['venue_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editVenue(<?php echo $venue['id']; ?>)">
                                                <i class="cil-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteVenue(<?php echo $venue['id']; ?>, '<?php echo htmlspecialchars($venue['venue_name']); ?>')">
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

            <!-- Reservations Tab -->
            <div class="tab-pane fade" id="reservations" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Event Reservations</h4>
                    <button class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#reservationModal" onclick="openCreateReservationModal()">
                        <i class="cil-plus me-2"></i>Add Reservation
                    </button>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Event Title</th>
                                        <th>Organizer</th>
                                        <th>Venue</th>
                                        <th>Attendees</th>
                                        <th>Check-in</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reservation['event_title']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['event_organizer']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['venue_name'] ?: 'Not assigned'); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['event_expected_attendees']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($reservation['event_checkin'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo $reservation['event_status'] === 'Checked In' ? 'success' :
                                                     ($reservation['event_status'] === 'Pending' ? 'warning' :
                                                     ($reservation['event_status'] === 'Checked Out' ? 'primary' : 'danger'));
                                            ?>">
                                                <?php echo htmlspecialchars($reservation['event_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editReservation(<?php echo $reservation['id']; ?>)">
                                                <i class="cil-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteReservation(<?php echo $reservation['id']; ?>, '<?php echo htmlspecialchars($reservation['event_title']); ?>')">
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

            <!-- Billing Tab -->
            <div class="tab-pane fade" id="billing" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Billing Transactions</h4>
                    <button class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#billingModal" onclick="openCreateBillingModal()">
                        <i class="cil-plus me-2"></i>Add Transaction
                    </button>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Event</th>
                                        <th>Type</th>
                                        <th>Amount</th>
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
                                        <td><?php echo htmlspecialchars($billing['event_title'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($billing['transaction_type']); ?></td>
                                        <td>$<?php echo number_format($billing['payment_amount'], 2); ?></td>
                                        <td>$<?php echo number_format($billing['balance'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($billing['payment_method']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo $billing['status'] === 'Paid' ? 'success' :
                                                     ($billing['status'] === 'Pending' ? 'warning' :
                                                     ($billing['status'] === 'Failed' ? 'danger' : 'secondary'));
                                            ?>">
                                                <?php echo htmlspecialchars($billing['status']); ?>
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
            </div>
        </div>
    </div>

    <!-- Venue Modal -->
    <div class="modal fade" id="venueModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="venueModalTitle">Add Venue</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="venueForm">
                        <input type="hidden" name="action" id="venueFormAction" value="create_venue">
                        <input type="hidden" name="id" id="venueId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="venue_name" class="form-label">Venue Name *</label>
                                <select class="form-select" id="venue_name" name="venue_name" required>
                                    <option value="Theater">Theater</option>
                                    <option value="Auditorium">Auditorium</option>
                                    <option value="Convention Center">Convention Center</option>
                                    <option value="Exhibition Hall">Exhibition Hall</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="venue_status" class="form-label">Status *</label>
                                <select class="form-select" id="venue_status" name="venue_status" required>
                                    <option value="Available">Available</option>
                                    <option value="Booked">Booked</option>
                                    <option value="Maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="venue_address" class="form-label">Address *</label>
                            <input type="text" class="form-control" id="venue_address" name="venue_address" required>
                        </div>

                        <div class="mb-3">
                            <label for="venue_capacity" class="form-label">Capacity *</label>
                            <input type="number" class="form-control" id="venue_capacity" name="venue_capacity" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="venue_description" class="form-label">Description</label>
                            <textarea class="form-control" id="venue_description" name="venue_description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitVenueForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservation Modal -->
    <div class="modal fade" id="reservationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reservationModalTitle">Add Reservation</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reservationForm">
                        <input type="hidden" name="action" id="reservationFormAction" value="create_reservation">
                        <input type="hidden" name="id" id="reservationId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" id="event_title" name="event_title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="event_organizer" class="form-label">Organizer *</label>
                                <input type="text" class="form-control" id="event_organizer" name="event_organizer" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_organizer_contact" class="form-label">Organizer Contact *</label>
                                <input type="text" class="form-control" id="event_organizer_contact" name="event_organizer_contact" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="event_expected_attendees" class="form-label">Expected Attendees *</label>
                                <input type="number" class="form-control" id="event_expected_attendees" name="event_expected_attendees" min="1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_venue_id" class="form-label">Venue</label>
                                <select class="form-select" id="event_venue_id" name="event_venue_id">
                                    <option value="">Select Venue</option>
                                    <?php foreach ($venues as $venue): ?>
                                    <option value="<?php echo $venue['id']; ?>"><?php echo htmlspecialchars($venue['venue_name']); ?> (<?php echo $venue['venue_capacity']; ?> capacity)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="event_hour_count" class="form-label">Hours *</label>
                                <select class="form-select" id="event_hour_count" name="event_hour_count" required>
                                    <option value="8">8 Hours</option>
                                    <option value="16">16 Hours</option>
                                    <option value="24">24 Hours</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="event_days_count" class="form-label">Days *</label>
                                <input type="number" class="form-control" id="event_days_count" name="event_days_count" min="1" value="1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_checkin" class="form-label">Check-in Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="event_checkin" name="event_checkin" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="event_checkout" class="form-label">Check-out Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="event_checkout" name="event_checkout" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="event_description" class="form-label">Description</label>
                            <textarea class="form-control" id="event_description" name="event_description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitReservationForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing Modal -->
    <div class="modal fade" id="billingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="billingModalTitle">Add Billing Transaction</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="billingForm">
                        <input type="hidden" name="action" id="billingFormAction" value="create_billing">
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
                                <label for="event_reservation_id" class="form-label">Event Reservation</label>
                                <select class="form-select" id="event_reservation_id" name="event_reservation_id">
                                    <option value="">Select Event</option>
                                    <?php foreach ($reservations as $reservation): ?>
                                    <option value="<?php echo $reservation['id']; ?>"><?php echo htmlspecialchars($reservation['event_title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="payment_amount" class="form-label">Payment Amount *</label>
                                <input type="number" class="form-control" id="payment_amount" name="payment_amount" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="balance" class="form-label">Balance *</label>
                                <input type="number" class="form-control" id="balance" name="balance" step="0.01" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="billing_status" name="status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Failed">Failed</option>
                                    <option value="Refunded">Refunded</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="billing_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="billing_notes" name="notes" rows="3"></textarea>
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
        // Venue functions
        function openCreateVenueModal() {
            document.getElementById('venueModalTitle').textContent = 'Add Venue';
            document.getElementById('venueFormAction').value = 'create_venue';
            document.getElementById('venueId').value = '';
            document.getElementById('venueForm').reset();
        }

        function editVenue(id) {
            document.getElementById('venueModalTitle').textContent = 'Edit Venue';
            document.getElementById('venueFormAction').value = 'update_venue';

            fetch('events.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_venue&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('venueId').value = data.id;
                document.getElementById('venue_name').value = data.venue_name;
                document.getElementById('venue_address').value = data.venue_address;
                document.getElementById('venue_capacity').value = data.venue_capacity;
                document.getElementById('venue_description').value = data.venue_description || '';
                document.getElementById('venue_status').value = data.venue_status;

                new coreui.Modal(document.getElementById('venueModal')).show();
            });
        }

        function deleteVenue(id, name) {
            if (confirm('Are you sure you want to delete the venue "' + name + '"? This action cannot be undone.')) {
                fetch('events.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_venue&id=' + id
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

        function submitVenueForm() {
            const form = document.getElementById('venueForm');
            const formData = new FormData(form);

            fetch('events.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('venueModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Reservation functions
        function openCreateReservationModal() {
            document.getElementById('reservationModalTitle').textContent = 'Add Reservation';
            document.getElementById('reservationFormAction').value = 'create_reservation';
            document.getElementById('reservationId').value = '';
            document.getElementById('reservationForm').reset();
        }

        function editReservation(id) {
            document.getElementById('reservationModalTitle').textContent = 'Edit Reservation';
            document.getElementById('reservationFormAction').value = 'update_reservation';

            fetch('events.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_reservation&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('reservationId').value = data.id;
                document.getElementById('event_title').value = data.event_title;
                document.getElementById('event_organizer').value = data.event_organizer;
                document.getElementById('event_organizer_contact').value = data.event_organizer_contact;
                document.getElementById('event_expected_attendees').value = data.event_expected_attendees;
                document.getElementById('event_description').value = data.event_description || '';
                document.getElementById('event_venue_id').value = data.event_venue_id || '';
                document.getElementById('event_checkin').value = data.event_checkin ? data.event_checkin.substring(0, 16) : '';
                document.getElementById('event_checkout').value = data.event_checkout ? data.event_checkout.substring(0, 16) : '';
                document.getElementById('event_hour_count').value = data.event_hour_count;
                document.getElementById('event_days_count').value = data.event_days_count;

                new coreui.Modal(document.getElementById('reservationModal')).show();
            });
        }

        function deleteReservation(id, title) {
            if (confirm('Are you sure you want to delete the reservation "' + title + '"? This action cannot be undone.')) {
                fetch('events.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_reservation&id=' + id
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

        function submitReservationForm() {
            const form = document.getElementById('reservationForm');
            const formData = new FormData(form);

            fetch('events.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('reservationModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Billing functions
        function openCreateBillingModal() {
            document.getElementById('billingModalTitle').textContent = 'Add Billing Transaction';
            document.getElementById('billingFormAction').value = 'create_billing';
            document.getElementById('billingId').value = '';
            document.getElementById('billingForm').reset();
        }

        function editBilling(id) {
            document.getElementById('billingModalTitle').textContent = 'Edit Billing Transaction';
            document.getElementById('billingFormAction').value = 'update_billing';

            fetch('events.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get_billing&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('billingId').value = data.id;
                document.getElementById('transaction_type').value = data.transaction_type;
                document.getElementById('event_reservation_id').value = data.event_reservation_id || '';
                document.getElementById('payment_amount').value = data.payment_amount;
                document.getElementById('balance').value = data.balance;
                document.getElementById('payment_method').value = data.payment_method;
                document.getElementById('billing_status').value = data.status;
                document.getElementById('billing_notes').value = data.notes || '';

                new coreui.Modal(document.getElementById('billingModal')).show();
            });
        }

        function deleteBilling(id) {
            if (confirm('Are you sure you want to delete this billing transaction? This action cannot be undone.')) {
                fetch('events.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete_billing&id=' + id
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

            fetch('events.php', {
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