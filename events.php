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

                     // Create new reservation
                     $stmt = $conn->prepare("INSERT INTO event_reservation (event_title, event_organizer, event_organizer_contact, event_expected_attendees, event_description, event_venue_id, event_checkin, event_checkout, event_hour_count, event_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
                         $_POST['event_status'] ?: 'Pending'
                     ]);
                     echo json_encode(['success' => true, 'message' => 'Reservation created successfully']);
                     break;

                case 'update_reservation':
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

                     // Update reservation
                     $stmt = $conn->prepare("UPDATE event_reservation SET event_title=?, event_organizer=?, event_organizer_contact=?, event_expected_attendees=?, event_description=?, event_venue_id=?, event_checkin=?, event_checkout=?, event_hour_count=?, event_status=? WHERE id=?");
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
                                                <?php echo htmlspecialchars($reservation['event_organizer']); ?> • <?php echo htmlspecialchars($reservation['venue_name'] ?: 'No venue'); ?>
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
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="editReservation(<?php echo $reservation['id']; ?>)" title="Edit">
                                        <i class="cil-pencil me-1"></i>Edit
                                    </button>
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
                            <div class="col-md-6 mb-3">
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
                                <label for="event_checkout" class="form-label">Check-out Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="event_checkout" name="event_checkout" required>
                            </div>
                        </div>

                        <input type="hidden" name="event_status" value="Pending">

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
                document.getElementById('event_checkout').value = data.event_checkout ? data.event_checkout.substring(0, 16) : '';
                document.getElementById('event_hour_count').value = data.event_hour_count;
                document.getElementById('event_status').value = data.event_status || 'Pending';

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

    </script>
</body>
</html>