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
                     $stmt = $conn->prepare("INSERT INTO event_venues (venue_name, venue_address, venue_capacity, venue_rate, venue_description, venue_status) VALUES (?, ?, ?, ?, ?, ?)");
                     $stmt->execute([
                         $_POST['venue_name'],
                         $_POST['venue_address'],
                         $_POST['venue_capacity'],
                         $_POST['venue_rate'] ?: null,
                         $_POST['venue_description'] ?: null,
                         $_POST['venue_status']
                     ]);
                     echo json_encode(['success' => true, 'message' => 'Venue created successfully']);
                     break;

                 case 'update_venue':
                     // Update venue
                     $stmt = $conn->prepare("UPDATE event_venues SET venue_name=?, venue_address=?, venue_capacity=?, venue_rate=?, venue_description=?, venue_status=? WHERE id=?");
                     $stmt->execute([
                         $_POST['venue_name'],
                         $_POST['venue_address'],
                         $_POST['venue_capacity'],
                         $_POST['venue_rate'] ?: null,
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
                     // Validate expected attendees against venue capacity
                     if (!empty($_POST['event_venue_id'])) {
                         $venueStmt = $conn->prepare("SELECT venue_capacity FROM event_venues WHERE id = ?");
                         $venueStmt->execute([$_POST['event_venue_id']]);
                         $venue = $venueStmt->fetch(PDO::FETCH_ASSOC);
                         if ($venue && $_POST['event_expected_attendees'] > $venue['venue_capacity']) {
                             echo json_encode(['success' => false, 'message' => 'Expected attendees (' . $_POST['event_expected_attendees'] . ') cannot exceed venue capacity (' . $venue['venue_capacity'] . ')']);
                             exit;
                         }
                     }

                     // Calculate check-out date based on hours and days
                     $event_checkin = $_POST['event_checkin'];
                     $hours = (int)($_POST['event_hours'] ?: 0);
                     $days = (int)($_POST['event_days'] ?: 0);
                     $total_hours = $hours + $days;

                     // Validate duration - hours must be specified
                     if ($total_hours === 0) {
                         echo json_encode(['success' => false, 'message' => 'Event duration must be specified.']);
                         break;
                     }

                     $event_checkout = date('Y-m-d H:i:s', strtotime($event_checkin) + ($total_hours * 3600));

                     // Check for time conflicts if a venue is selected
                     if (!empty($_POST['event_venue_id'])) {
                         $stmt = $conn->prepare("SELECT COUNT(*) as conflict_count FROM event_reservation WHERE event_venue_id = ? AND event_status IN ('Pending', 'Checked In', 'Checked Out') AND event_status != 'Archived' AND (
                             (event_checkin < ? AND event_checkout > ?) OR
                             (event_checkin < ? AND event_checkout > ?) OR
                             (event_checkin >= ? AND event_checkout <= ?)
                         )");
                         $stmt->execute([
                             $_POST['event_venue_id'],
                             $event_checkout, $event_checkin,  // overlap start
                             $event_checkin, $event_checkout,  // overlap end
                             $event_checkin, $event_checkout   // contained within
                         ]);
                         $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
                         if ($conflict['conflict_count'] > 0) {
                             // Find next available time for the venue
                             $stmt = $conn->prepare("SELECT event_checkout FROM event_reservation WHERE event_venue_id = ? AND event_status IN ('Pending', 'Checked In') AND event_checkout > ? ORDER BY event_checkout ASC LIMIT 1");
                             $stmt->execute([$_POST['event_venue_id'], $event_checkin]);
                             $nextAvailable = $stmt->fetch(PDO::FETCH_ASSOC);

                             $message = 'Time conflict: The selected venue is already reserved for this time period.';
                             if ($nextAvailable) {
                                 $nextTime = strtotime($nextAvailable['event_checkout']);

                                 // Calculate requested duration based on hours value
                                 if ($hours <= 16) {
                                     $requestedDuration = $hours * 3600; // hours
                                     $minGap = 8 * 3600; // 8 hours minimum gap
                                 } else {
                                     $requestedDuration = $hours * 24 * 3600; // days
                                     $minGap = 24 * 3600; // 24 hours minimum gap
                                 }

                                 if (($nextTime - strtotime($event_checkin)) < $minGap) {
                                     // Not enough time, suggest time after the next reservation
                                     $suggestedTime = date('Y-m-d H:i:s', $nextTime);
                                     $message .= ' Venue will be available after ' . date('M-d H:i', $nextTime) . '.';
                                 } else {
                                     // There might be a gap, but we don't allow reservations in small gaps
                                     $message .= ' Venue will be available after ' . date('M-d H:i', $nextTime) . '.';
                                 }
                             }
                             echo json_encode(['success' => false, 'message' => $message]);
                             break;
                         }
                     }

                     // Create new reservation
                     $stmt = $conn->prepare("INSERT INTO event_reservation (event_title, event_organizer, event_organizer_contact, event_expected_attendees, event_description, event_venue_id, event_checkin, event_checkout, event_hour_count, event_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                     $stmt->execute([
                         $_POST['event_title'],
                         $_POST['event_organizer'],
                         $_POST['event_organizer_contact'],
                         $_POST['event_expected_attendees'],
                         $_POST['event_description'] ?: null,
                         $_POST['event_venue_id'] ?: null,
                         $event_checkin,
                         $event_checkout,
                         $total_hours,
                         $_POST['event_status'] ?: 'Pending'
                     ]);
                     echo json_encode(['success' => true, 'message' => 'Reservation created successfully']);
                     break;

                case 'update_reservation':
                     // Get current reservation data to handle venue status changes
                     $stmt = $conn->prepare("SELECT event_venue_id, event_status, event_checkin, event_checkout FROM event_reservation WHERE id=?");
                     $stmt->execute([$_POST['id']]);
                     $current = $stmt->fetch(PDO::FETCH_ASSOC);

                     // Calculate new check-out date if dates changed
                     $event_checkin = $_POST['event_checkin'];
                     $hours = (int)($_POST['event_hours'] ?: 0);
                     $days = (int)($_POST['event_days'] ?: 0);
                     $total_hours = $hours + $days;

                     // Validate duration
                     if ($total_hours === 0) {
                         echo json_encode(['success' => false, 'message' => 'Event duration must be specified.']);
                         break;
                     }

                     $event_checkout = date('Y-m-d H:i:s', strtotime($event_checkin) + ($total_hours * 3600));

                     // Validate expected attendees against venue capacity
                     if (!empty($_POST['event_venue_id'])) {
                         $venueStmt = $conn->prepare("SELECT venue_capacity FROM event_venues WHERE id = ?");
                         $venueStmt->execute([$_POST['event_venue_id']]);
                         $venue = $venueStmt->fetch(PDO::FETCH_ASSOC);
                         if ($venue && $_POST['event_expected_attendees'] > $venue['venue_capacity']) {
                             echo json_encode(['success' => false, 'message' => 'Expected attendees (' . $_POST['event_expected_attendees'] . ') cannot exceed venue capacity (' . $venue['venue_capacity'] . ')']);
                             exit;
                         }
                     }

                     // Check for time conflicts if venue changed or dates changed
                     if (!empty($_POST['event_venue_id']) && (!empty($_POST['event_venue_id']) || $event_checkin !== $current['event_checkin'] || $event_checkout !== $current['event_checkout'])) {
                         $stmt = $conn->prepare("SELECT COUNT(*) as conflict_count FROM event_reservation WHERE event_venue_id = ? AND id != ? AND event_status IN ('Pending', 'Checked In', 'Checked Out') AND event_status != 'Archived' AND (
                             (event_checkin < ? AND event_checkout > ?) OR
                             (event_checkin < ? AND event_checkout > ?) OR
                             (event_checkin >= ? AND event_checkout <= ?)
                         )");
                         $stmt->execute([
                             $_POST['event_venue_id'],
                             $_POST['id'],  // Exclude current reservation
                             $event_checkout, $event_checkin,  // overlap start
                             $event_checkin, $event_checkout,  // overlap end
                             $event_checkin, $event_checkout   // contained within
                         ]);
                         $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
                         if ($conflict['conflict_count'] > 0) {
                             // Find next available time for the venue
                             $stmt = $conn->prepare("SELECT event_checkout FROM event_reservation WHERE event_venue_id = ? AND id != ? AND event_status IN ('Pending', 'Checked In') AND event_checkout > ? ORDER BY event_checkout ASC LIMIT 1");
                             $stmt->execute([$_POST['event_venue_id'], $_POST['id'], $event_checkin]);
                             $nextAvailable = $stmt->fetch(PDO::FETCH_ASSOC);

                             $message = 'Time conflict: The selected venue is already reserved for this time period.';
                             if ($nextAvailable) {
                                 $nextTime = strtotime($nextAvailable['event_checkout']);

                                 // Calculate requested duration based on hours value
                                 if ($hours <= 16) {
                                     $requestedDuration = $hours * 3600; // hours
                                     $minGap = 8 * 3600; // 8 hours minimum gap
                                 } else {
                                     $requestedDuration = $hours * 24 * 3600; // days
                                     $minGap = 24 * 3600; // 24 hours minimum gap
                                 }

                                 if (($nextTime - strtotime($event_checkin)) < $minGap) {
                                     // Not enough time, suggest time after the next reservation
                                     $suggestedTime = date('Y-m-d H:i:s', $nextTime);
                                     $message .= ' Venue will be available after ' . date('M-d H:i', $nextTime) . '.';
                                 } else {
                                     // There might be a gap, but we don't allow reservations in small gaps
                                     $message .= ' Venue will be available after ' . date('M-d H:i', $nextTime) . '.';
                                 }
                             }
                             echo json_encode(['success' => false, 'message' => $message]);
                             break;
                         }
                     }

                     // Update reservation
                     $stmt = $conn->prepare("UPDATE event_reservation SET event_title=?, event_organizer=?, event_organizer_contact=?, event_expected_attendees=?, event_description=?, event_venue_id=?, event_checkin=?, event_checkout=?, event_hour_count=?, event_status=? WHERE id=?");
                     $stmt->execute([
                         $_POST['event_title'],
                         $_POST['event_organizer'],
                         $_POST['event_organizer_contact'],
                         $_POST['event_expected_attendees'],
                         $_POST['event_description'] ?: null,
                         $_POST['event_venue_id'] ?: null,
                         $event_checkin,
                         $event_checkout,
                         $total_hours,
                         $_POST['event_status'],
                         $_POST['id']
                     ]);

                     // Set check_out_date in the database
                     $stmt = $conn->prepare("UPDATE event_reservation SET event_checkout=? WHERE id=?");
                     $stmt->execute([$event_checkout, $_POST['id']]);

                     echo json_encode(['success' => true, 'message' => 'Reservation updated successfully']);
                     break;

                case 'delete_reservation':
                    // Delete reservation
                    $stmt = $conn->prepare("DELETE FROM event_reservation WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Reservation deleted successfully']);
                    break;

                case 'checkin':
                    // Check-in reservation
                    $stmt = $conn->prepare("UPDATE event_reservation SET event_status = 'Checked In' WHERE id = ? AND event_status = 'Pending'");
                    $result = $stmt->execute([$_POST['id']]);

                    if ($result && $stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'Event checked in successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Check-in failed or event not found']);
                    }
                    break;

                case 'checkout':
                    // Check-out reservation
                    $stmt = $conn->prepare("UPDATE event_reservation SET event_status = 'Checked Out' WHERE id = ? AND event_status = 'Checked In'");
                    $result = $stmt->execute([$_POST['id']]);

                    if ($result && $stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'Event checked out successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Check-out failed or event not found']);
                    }
                    break;

                case 'archive':
                    // Archive reservation
                    $stmt = $conn->prepare("UPDATE event_reservation SET event_status = 'Archived' WHERE id = ?");
                    $result = $stmt->execute([$_POST['id']]);

                    if ($result && $stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'Event archived successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Archive failed or event not found']);
                    }
                    break;

                case 'get_reservation':
                    // Get reservation data for editing
                    $stmt = $conn->prepare("SELECT * FROM event_reservation WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($reservation);
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
   WHERE er.event_status != 'Archived'
   ORDER BY er.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$archived_reservations = $conn->query("
   SELECT er.*, ev.venue_name, ev.venue_capacity
   FROM event_reservation er
   LEFT JOIN event_venues ev ON er.event_venue_id = ev.id
   WHERE er.event_status = 'Archived'
   ORDER BY er.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $conn->query("
   SELECT
       (SELECT COUNT(*) FROM event_venues) as total_venues,
       (SELECT COUNT(*) FROM event_venues WHERE venue_status = 'Available') as available_venues,
       (SELECT COUNT(*) FROM event_reservation) as total_reservations,
       (SELECT COUNT(*) FROM event_reservation WHERE event_status = 'Checked In') as active_events
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>(function(){try{var s=localStorage.getItem('theme-preference');var t=(s==='light'||s==='dark')?s:(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-coreui-theme',t);var bg=t==='dark'?'#1a1a1a':'#ffffff';document.documentElement.style.backgroundColor=bg;document.documentElement.style.background=bg;var st=document.createElement('style');st.id='early-theme-bg';st.textContent='html,body{background:'+bg+' !important;}';document.head.appendChild(st);}catch(e){}})();</script>
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
    <script src="js/page-loader.js"></script>

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
        .event-card {
            cursor: pointer;
        }
        .event-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .event-actions {
            display: none;
        }
        .event-card:hover .event-actions {
            display: flex;
        }
        .venue-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .venue-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .venue-available {
            background: linear-gradient(135deg, #2d5016, #1a3326);
            color: #d4edda;
        }
        .venue-booked {
            background: linear-gradient(135deg, #721c24, #4a0f14);
            color: #f8d7da;
        }
        .venue-maintenance {
            background: linear-gradient(135deg, #0c5460, #062a30);
            color: #d1ecf1;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header with Stats -->
        <div class="mb-4">
            <div class="d-flex justify-content-between gap-3 text-center">
                <div class="flex-grow-1 text-start">
                    <h2>Events</h2>
                </div>
                <div>
                    <small class="text-muted d-block">Venues</small>
                    <span class="fw-bold text-primary"><?php echo $stats['total_venues']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Available</small>
                    <span class="fw-bold text-success"><?php echo $stats['available_venues']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Reservations</small>
                    <span class="fw-bold text-warning"><?php echo $stats['total_reservations']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Active Events</small>
                    <span class="fw-bold text-info"><?php echo $stats['active_events']; ?></span>
                </div>
            </div>
        </div>

        <!-- Events -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Events</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" onclick="generateReport()">
                        <i class="cil-file-pdf me-1"></i>Report
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openCreateVenueModal()">
                        <i class="cil-plus me-1"></i>New Venue
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openCreateReservationModal()">
                        <i class="cil-plus me-1"></i>New Reservation
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="openViewVenuesModal()">
                        <i class="cil-building me-1"></i>View Venues
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="window.location.href='?page=event_billing'">
                        <i class="cil-dollar me-1"></i>Event Billing
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="eventsContainer">
                    <?php foreach ($reservations as $reservation): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 event-card" style="border-left: 4px solid <?php
                            echo $reservation['event_status'] === 'Checked In' ? '#198754' :
                                 ($reservation['event_status'] === 'Pending' ? '#fd7e14' :
                                 ($reservation['event_status'] === 'Checked Out' ? '#0d6efd' : '#dc3545'));
                        ?>;">
                            <div class="card-body">
                                <div class="event-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($reservation['event_title']); ?></h6>
                                            <small class="text-muted">
                                                <?php if ($reservation['venue_name']): ?>
                                                    <?php echo htmlspecialchars($reservation['venue_name']); ?> • <?php echo date('M-d H:i', strtotime($reservation['event_checkin'])); ?> to <?php echo date('M-d H:i', strtotime($reservation['event_checkout'])); ?>
                                                <?php else: ?>
                                                    No venue • <?php echo date('M-d H:i', strtotime($reservation['event_checkin'])); ?> to <?php echo date('M-d H:i', strtotime($reservation['event_checkout'])); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-<?php
                                                echo $reservation['event_status'] === 'Checked In' ? 'success' :
                                                     ($reservation['event_status'] === 'Pending' ? 'warning' :
                                                     ($reservation['event_status'] === 'Checked Out' ? 'primary' : 'danger'));
                                            ?>">
                                                <?php echo htmlspecialchars($reservation['event_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="event-actions justify-content-center">
                                    <?php if ($reservation['event_status'] === 'Pending'): ?>
                                        <button class="btn btn-sm btn-success me-2" onclick="checkInReservation(<?php echo $reservation['id']; ?>)" title="Check In">
                                            <i class="cil-check me-1"></i>Check In
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary me-2" onclick="editReservation(<?php echo $reservation['id']; ?>)" title="Edit">
                                            <i class="cil-pencil me-1"></i>Edit
                                        </button>
                                    <?php elseif ($reservation['event_status'] === 'Checked In'): ?>
                                        <button class="btn btn-sm btn-warning me-2" onclick="checkOutReservation(<?php echo $reservation['id']; ?>)" title="Check Out">
                                            <i class="cil-arrow-right me-1"></i>Check Out
                                        </button>
                                    <?php elseif ($reservation['event_status'] === 'Checked Out'): ?>
                                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="archiveReservation(<?php echo $reservation['id']; ?>)" title="Archive">
                                            <i class="cil-archive me-1"></i>Archive
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteReservation(<?php echo $reservation['id']; ?>, '<?php echo htmlspecialchars($reservation['event_title']); ?>')" title="Remove">
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

        <!-- Archived Events -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Archived Events</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($archived_reservations)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Organizer</th>
                                <th>Venue</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_reservations as $reservation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['event_title']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['event_organizer']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['venue_name'] ?: 'No venue'); ?></td>
                                <td><?php echo date('M-d H:i', strtotime($reservation['event_checkin'])); ?></td>
                                <td><?php echo date('M-d H:i', strtotime($reservation['event_checkout'])); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($reservation['event_status']); ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteReservation(<?php echo $reservation['id']; ?>, '<?php echo htmlspecialchars($reservation['event_title']); ?>')" title="Remove">
                                        <i class="cil-trash me-1"></i>Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">No archived events yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Housekeeping Modal -->
    <div class="modal fade" id="housekeepingModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="housekeepingModalTitle">Assign Housekeeper</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="housekeepingForm">
                        <input type="hidden" name="action" value="create_housekeeping">
                        <input type="hidden" name="room_id" id="housekeepingRoomId">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="housekeepingVenueNumber" class="form-label fw-bold">Venue Name</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-building"></i></span>
                                    <input type="text" class="form-control" id="housekeepingVenueNumber" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="housekeeper_id" class="form-label fw-bold">Housekeeper</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-user"></i></span>
                                    <select class="form-select" id="housekeeper_id" name="housekeeper_id">
                                        <option value="">Select Housekeeper</option>
                                        <?php
                                        $housekeepers = $conn->query("SELECT id, first_name, last_name, employee_id FROM housekeepers WHERE status = 'Active' ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($housekeepers as $housekeeper): ?>
                                        <option value="<?php echo $housekeeper['id']; ?>"><?php echo htmlspecialchars($housekeeper['first_name'] . ' ' . $housekeeper['last_name'] . ' (' . $housekeeper['employee_id'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="task_type" class="form-label fw-bold">Task Type</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-task"></i></span>
                                    <select class="form-select" id="task_type" name="task_type">
                                        <option value="Regular Cleaning">Regular Cleaning</option>
                                        <option value="Deep Cleaning">Deep Cleaning</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Inspection">Inspection</option>
                                        <option value="Emergency">Emergency</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label fw-bold">Priority</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-bell"></i></span>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="Low">Low</option>
                                        <option value="Normal">Normal</option>
                                        <option value="High">High</option>
                                        <option value="Urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label fw-bold">Scheduled Date</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-calendar"></i></span>
                                    <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="scheduled_time" class="form-label fw-bold">Scheduled Time</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-clock"></i></span>
                                    <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" value="09:00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="estimated_duration_minutes" class="form-label fw-bold">Estimated Duration (minutes)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-timer"></i></span>
                                    <input type="number" class="form-control" id="estimated_duration_minutes" name="estimated_duration_minutes" min="15" max="480" value="60" placeholder="Minutes">
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="supervisor_notes" class="form-label fw-bold">Supervisor Notes</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="cil-notes"></i></span>
                                    <textarea class="form-control" id="supervisor_notes" name="supervisor_notes" rows="2" placeholder="Supervisor notes or special instructions"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Task</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Venue Modal -->
    <div class="modal fade" id="venueModal" tabindex="-1" style="z-index: 1060;">
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
                                <input type="text" class="form-control" id="venue_name" name="venue_name" required>
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

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="venue_address" class="form-label">Address *</label>
                                <input type="text" class="form-control" id="venue_address" name="venue_address" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="venue_capacity" class="form-label">Capacity *</label>
                                <input type="number" class="form-control" id="venue_capacity" name="venue_capacity" min="1" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="venue_rate" class="form-label">Rate (per hour)</label>
                            <input type="number" class="form-control" id="venue_rate" name="venue_rate" step="0.01" min="0">
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

    <!-- Event Billing Modal -->
    <div class="modal fade" id="eventBillingModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Billing</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="eventBillingContainer">
                        <?php
                        $eventBillings = $conn->query("
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
                        ?>
                        <?php foreach ($eventBillings as $billing): ?>
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
                                                <small class="text-muted">Balance: $<?php echo number_format($billing['calculated_balance'], 2); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="billing-actions justify-content-center">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openEventBillingForm(<?php echo $billing['id']; ?>, <?php echo $billing['calculated_balance']; ?>, '<?php echo htmlspecialchars($billing['venue_name']); ?>', '<?php echo htmlspecialchars($billing['event_organizer']); ?>', <?php echo $billing['venue_id']; ?>)" title="Create Billing">
                                            <i class="cil-plus me-1"></i>Create Billing
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Venues Modal -->
    <div class="modal fade" id="viewVenuesModal" tabindex="-1" style="--cui-modal-border-radius: 16px; --cui-modal-box-shadow: 0 10px 40px rgba(0,0,0,0.3); --cui-modal-bg: #2d3748; --cui-modal-border-color: #4a5568;">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Venue Management</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="venuesContainer">
                        <?php foreach ($venues as $venue): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card venue-card venue-<?php echo strtolower($venue['venue_status']); ?> text-white h-100" onclick="editVenue(<?php echo $venue['id']; ?>)">
                                <div class="card-body text-center position-relative">
                                    <?php
                                    // Check if venue has an assigned housekeeper
                                    $hasHousekeeper = $conn->query("SELECT COUNT(*) as count FROM housekeeping WHERE room_id = {$venue['id']} AND status IN ('Pending', 'In Progress')")->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                                    ?>
                                    <?php if ($venue['venue_status'] === 'Maintenance'): ?>
                                    <div class="position-absolute top-0 end-0" style="margin-top: -8px; margin-right: -8px;">
                                        <button class="btn btn-warning btn-sm rounded-circle shadow" onclick="event.stopPropagation(); openHousekeepingModal(<?php echo $venue['id']; ?>, '<?php echo htmlspecialchars($venue['venue_name']); ?>')" title="Assign Housekeeper">
                                            <i class="cil-settings text-dark"></i>
                                        </button>
                                    </div>
                                    <?php elseif ($hasHousekeeper): ?>
                                    <div class="position-absolute top-0 end-0" style="margin-top: -8px; margin-right: -8px;">
                                        <button class="btn btn-info btn-sm rounded-circle shadow" onclick="event.stopPropagation(); openHousekeepingModal(<?php echo $venue['id']; ?>, '<?php echo htmlspecialchars($venue['venue_name']); ?>')" title="View Housekeeping Task">
                                            <i class="cil-broom text-white"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($venue['venue_name']); ?></h5>
                                    <p class="card-text mb-2"><?php echo htmlspecialchars($venue['venue_address']); ?></p>
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($venue['venue_status']); ?></span>
                                    <br><small class="mt-2 d-block"><?php echo htmlspecialchars($venue['venue_capacity']); ?> capacity</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
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
                            <div class="col-md-6 mb-3" id="hoursInputContainer">
                                <label for="event_hour_count" class="form-label">Hours *</label>
                                <input type="number" class="form-control" id="event_hour_count" name="event_hour_count" min="1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_checkin" class="form-label">Check-in Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="event_checkin" name="event_checkin" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small mb-2">Duration *</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select class="form-select form-select-sm" id="event_hours" name="event_hours" style="width: 120px;">
                                        <option value="0">Hours</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                        <option value="7">7</option>
                                        <option value="8">8</option>
                                        <option value="9">9</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                    </select>
                                    <select class="form-select form-select-sm" id="event_days" name="event_days" style="width: 100px;">
                                        <option value="0">Days</option>
                                        <option value="24">1</option>
                                        <option value="48">2</option>
                                        <option value="72">3</option>
                                        <option value="96">4</option>
                                        <option value="120">5</option>
                                        <option value="144">6</option>
                                        <option value="168">7</option>
                                        <option value="336">14</option>
                                        <option value="504">21</option>
                                        <option value="720">30</option>
                                        <option value="1440">60</option>
                                        <option value="2160">90</option>
                                        <option value="2880">120</option>
                                        <option value="3600">150</option>
                                        <option value="4320">180</option>
                                        <option value="5040">210</option>
                                        <option value="5760">240</option>
                                        <option value="6480">270</option>
                                        <option value="7200">300</option>
                                        <option value="7920">330</option>
                                        <option value="8760">365</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="event_status" id="event_status" value="Pending">

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


    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        // Venue functions
        function openCreateVenueModal() {
            document.getElementById('venueModalTitle').textContent = 'Add Venue';
            document.getElementById('venueFormAction').value = 'create_venue';
            document.getElementById('venueId').value = '';
            document.getElementById('venueForm').reset();
            document.getElementById('venue_status').value = 'Available';
            new coreui.Modal(document.getElementById('venueModal')).show();
        }

        function editVenue(id) {
            // Close the view venues modal first
            new coreui.Modal(document.getElementById('viewVenuesModal')).hide();

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
                 document.getElementById('venue_rate').value = data.venue_rate || '';
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
            document.getElementById('hoursInputContainer').style.display = 'none';
            new coreui.Modal(document.getElementById('reservationModal')).show();
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

                // Calculate hours and days from total hours
                const totalHours = data.event_hour_count;
                const days = Math.floor(totalHours / 24);
                const hours = totalHours % 24;

                document.getElementById('event_hours').value = hours;
                document.getElementById('event_days').value = days * 24;
                document.getElementById('event_status').value = data.event_status || 'Pending';

                // Show hours input for editing
                document.getElementById('hoursInputContainer').style.display = 'block';

                new coreui.Modal(document.getElementById('reservationModal')).show();
            });
        }

        function checkInReservation(id) {
            if (confirm('Are you sure you want to check in this event?')) {
                fetch('events.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=checkin&id=' + id
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

        function checkOutReservation(id) {
            if (confirm('Are you sure you want to check out this event?')) {
                fetch('events.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=checkout&id=' + id
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

        function archiveReservation(id) {
            if (confirm('Are you sure you want to archive this event? This will remove it from active events.')) {
                fetch('events.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=archive&id=' + id
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

        function openEventBillingModal() {
            new coreui.Modal(document.getElementById('eventBillingModal')).show();
        }

        function openViewVenuesModal() {
            new coreui.Modal(document.getElementById('viewVenuesModal')).show();
        }

        function openEventBillingForm(reservationId, calculatedBalance, venueName, organizerName, venueId) {
            // Close the event billing modal first
            new coreui.Modal(document.getElementById('eventBillingModal')).hide();

            // Open the billing form modal (reuse the existing billing modal)
            document.getElementById('billingModalTitle').textContent = 'Create Event Billing';
            document.getElementById('billingFormAction').value = 'create';
            document.getElementById('billingId').value = '';
            document.getElementById('billingForm').reset();

            // Pre-fill form with event data
            document.getElementById('reservation_id').value = reservationId;
            document.getElementById('venue_id').value = venueId;
            document.getElementById('balance').value = calculatedBalance.toFixed(2);
            document.getElementById('balance_display').textContent = '$' + calculatedBalance.toFixed(2);
            document.getElementById('billing_description_display').textContent = 'Event charge for ' + venueName + ' - ' + organizerName;

            new coreui.Modal(document.getElementById('billingModal')).show();
        }

        function openHousekeepingModal(venueId, venueName) {
            document.getElementById('housekeepingModalTitle').textContent = 'Assign Housekeeper - Venue ' + venueName;
            document.getElementById('housekeepingRoomId').value = venueId;
            document.getElementById('housekeepingVenueNumber').value = venueName;
            new coreui.Modal(document.getElementById('housekeepingModal')).show();
        }

        function submitHousekeepingForm(event) {
            event.preventDefault();

            const form = document.getElementById('housekeepingForm');
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="cil-spinner cil-spin me-2"></i>Assigning...';

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
                    showAlert('Housekeeping task assigned successfully!', 'success');
                    new coreui.Modal(document.getElementById('housekeepingModal')).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message || 'An error occurred while assigning the task.', 'danger');
                }
            })
            .catch(error => {
                showAlert('Network error. Please try again.', 'danger');
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        function showAlert(message, type = 'danger') {
            const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
            const alertId = 'alert-' + Date.now();

            const alertHTML = `
                <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="cil-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            alertContainer.insertAdjacentHTML('beforeend', alertHTML);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }

        function createAlertContainer() {
            const container = document.createElement('div');
            container.id = 'alertContainer';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
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

        function generateReport() {
            window.open('generate_report.php?page=events&type=pdf', '_blank');
        }

    </script>
</body>
</html>