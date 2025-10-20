<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CoreUI CSS -->
    <link href="css/coreui.min.css" rel="stylesheet">
    <link href="css/coreui-grid.min.css" rel="stylesheet">
    <link href="css/coreui-reboot.min.css" rel="stylesheet">
    <link href="css/coreui-utilities.min.css" rel="stylesheet">
    <link href="css/coreui-forms.min.css" rel="stylesheet">
    <title>Reservations</title>
    <!-- HTMX Library -->
    <script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.7/dist/htmx.min.js"></script>
    <script src="js/htmx.min.js"></script>
    <script src="/js/htmx.min.js"></script>
    <style>
        .metric-card {
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .action-button {
            transition: all 0.2s;
        }
        .action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .guest-card {
            transition: all 0.2s;
            margin-bottom: 1rem;
        }
        .guest-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .guest-result-item {
            cursor: pointer;
            border-bottom: 1px solid #495057;
            background: #343a40;
            color: #ffffff;
        }
        .guest-result-item:hover {
            background: #495057;
        }
        .guest-result-item strong {
            color: #ffffff;
        }
        .guest-result-item small {
            color: #adb5bd;
        }
        .htmx-indicator {
            display: none;
        }
        .htmx-request .htmx-indicator {
            display: inline;
        }
        .htmx-request.htmx-indicator {
            display: inline;
        }
        .stats-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .stats-container {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-center mb-4">
            <div class="bg-dark" style="display: flex; align-items: center; ">

    <!-- Code snippet to create a "breathe" animation effect with a variable font -->
      <style>
        /* The @font-face rule is used to define a custom font that you want to use on your webpage. Here 'TheFont' is a name we give to reference the font later in CSS. The 'src' property specifies the path to the font file, and 'format' specifies the font format. */
        @font-face {
        font-family: 'TheFont';

        /* Variable fonts like the one linked below allow for fine-tuned control over various font properties dynamically via CSS, such as weight ('wght'), width ('wdth'), etc. This link is where your web browser will download the font from. */
        /* Insert the link to your custom variable font */
        src: url("https://garet.typeforward.com/assets/fonts/shared/TFMixVF.woff2")
          format('woff2'); }

      /* Keyframes define the sequence of styles that an element will go through during an animation. */
      @keyframes letter-breathe {

        /* The 'from' and 'to' keyframes establish the initial and final states of the animation, respectively, using 'font-variation-settings'. This CSS property is used with variable fonts to adjust their weight ('wght'), width ('wdth'), etc., during the animation. */
        from,
        to {
          /* Starting weight; adjust the numbers according to your specific font */
          font-variation-settings: 'wght' 100;
        }

        /* At the midpoint (50%) of the animation, the font weight changes to 900. */
        50% {
          /* Ending weight; adjust the numbers according to your specific font */
          font-variation-settings: 'wght' 900;
        }
      }
      </style>


    <span style="font-family: 'TheFont'; font-size: clamp(10px, 25px, 50px); color: white; text-align: center; animation: letter-breathe 3s ease-in-out infinite;">Reservations</span>
</div>
        </div>

        <!-- Success/Error Messages -->
        
        
        <!-- Key Metrics Cards (Centered) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stats-container">
                                        <div class="card metric-card" style="min-width: 200px;">
                        <div class="card-body text-center">
                            <h3 class="card-title text-primary mb-2">2</h3>
                            <p class="card-text text-muted mb-2">Total Reservations</p>
                            <i class="cil-calendar" style="font-size: 2.5rem; opacity: 0.7; color: #0d6efd;"></i>
                        </div>
                    </div>
                    <div class="card metric-card" style="min-width: 200px;">
                        <div class="card-body text-center">
                            <h3 class="card-title text-success mb-2">0</h3>
                            <p class="card-text text-muted mb-2">Confirmed</p>
                            <i class="cil-check-circle" style="font-size: 2.5rem; opacity: 0.7; color: #198754;"></i>
                        </div>
                    </div>
                    <div class="card metric-card" style="min-width: 200px;">
                        <div class="card-body text-center">
                            <h3 class="card-title text-warning mb-2">1</h3>
                            <p class="card-text text-muted mb-2">Pending</p>
                            <i class="cil-clock" style="font-size: 2.5rem; opacity: 0.7; color: #ffc107;"></i>
                        </div>
                    </div>
                    <div class="card metric-card" style="min-width: 200px;">
                        <div class="card-body text-center">
                            <h3 class="card-title text-danger mb-2">0</h3>
                            <p class="card-text text-muted mb-2">Cancelled</p>
                            <i class="cil-x-circle" style="font-size: 2.5rem; opacity: 0.7; color: #dc3545;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                
                <div class="d-flex flex-wrap gap-3">
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary action-button" data-coreui-toggle="modal" data-coreui-target="#staticBackdrop">
                        <i class="cil-plus me-2"></i>New Room Reservation
                    </button>

                    <!-- Modal -->
                    <div class="modal fade" id="staticBackdrop" data-coreui-backdrop="static" data-coreui-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="staticBackdropLabel">New Room Reservation</h5>
                                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="quickReservationForm" method="POST" action="/reservations.php">
                                        <input type="hidden" name="action" value="create_reservation">
                                        <div class="row g-3">
                                            <!-- Guest Selection -->
                                            <div class="col-md-6">
                                                <label for="quick_guest_search" class="form-label">Search Guest *</label>
                                                <input type="text" class="form-control" id="quick_guest_search" placeholder="Type guest name or email..." list="guest-list" autocomplete="off" hx-get="search_guests.php" hx-target="#guest-search-container" hx-trigger="keyup changed delay:300ms" hx-vals='js:{"q": document.getElementById("quick_guest_search").value}' hx-swap="innerHTML">
                                                <datalist id="guest-list"></datalist>
                                                <div id="search-message" class="mt-1"></div>
                                                <div id="guest-search-container" hx-swap-oob="innerHTML"></div>
                                                <input type="hidden" id="quick_selected_guest_id" name="guest_id">
                                                <div id="guest_status" class="mt-2 small text-muted"></div>
                                            </div>


                                            <!-- Room Selection -->
                                            <div class="col-md-6">
                                                <label for="quick_room_selection" class="form-label">Select Room *</label>
                                                <select class="form-select" id="quick_room_selection" name="room_id" required>
                                                    <option value="">Select a room...</option>
                                                    <option value="2">100 - Single (Floor 2) - ₱1,500.00/night (Vacant)</option><option value="1">99 - Suite (Floor 1) - ₱4,500.00/night (Vacant)</option>                                                </select>
                                            </div>

                                            <!-- Check-in Date -->
                                            <div class="col-md-6">
                                                <label for="quick_check_in_date" class="form-label">Check-in Date & Time *</label>
                                                <input type="datetime-local" class="form-control" id="quick_check_in_date" name="check_in_date" value="2025-10-20T19:46" required>
                                            </div>

                                            <!-- Check-out Date -->
                                            <div class="col-md-6">
                                                <label for="quick_check_out_date" class="form-label">Check-out Date & Time *</label>
                                                <input type="datetime-local" class="form-control" id="quick_check_out_date" name="check_out_date" value="2025-10-21T19:46" required>
                                            </div>

                                            <!-- Reservation Type (Hidden, defaults to Room) -->
                                            <input type="hidden" name="reservation_type" value="Room">

                                            <!-- Duration -->
                                            <div class="col-md-6">
                                                <label for="quick_hour_count" class="form-label">Duration (hours) *</label>
                                                <select class="form-select" id="quick_hour_count" name="reservation_hour_count" required>
                                                    <option value="8" selected>8 hours</option>
                                                    <option value="16">16 hours</option>
                                                    <option value="24">24 hours</option>
                                                </select>
                                            </div>

                                            <!-- Days Count (for longer stays) -->
                                            <div class="col-md-6">
                                                <label for="quick_days_count" class="form-label">Number of Days (optional)</label>
                                                <input type="number" class="form-control" id="quick_days_count" name="reservation_days_count" min="1" max="30" placeholder="Leave blank for hourly">
                                            </div>

                                            <!-- Status (Hidden, defaults to Pending) -->
                                            <input type="hidden" name="reservation_status" value="Pending">

                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary" form="quickReservationForm">Create Reservation</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-info action-button" data-coreui-toggle="modal" data-coreui-target="#newEventReservationModal">
                        <i class="cil-plus me-2"></i>New Event Reservation
                    </button>
                    <button class="btn btn-secondary action-button" onclick="generateReport()">
                        <i class="cil-chart-line me-2"></i>View Reports
                    </button>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search reservations by guest name, room, or ID..." onkeyup="searchReservations()">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="statusFilter" onchange="filterReservations()">
                                    <option value="">All Statuses</option>
                                    <option value="Confirmed">Confirmed</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="typeFilter" onchange="filterReservations()">
                                    <option value="">All Types</option>
                                    <option value="Room">Room</option>
                                    <option value="Event">Event</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100" onclick="refreshReservationList()">
                                    <i class="cil-reload me-2"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservations List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Reservation List</h4>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="itemsPerPage" onchange="changeItemsPerPage()" style="width: auto;">
                                <option value="10">10 per page</option>
                                <option value="25" selected>25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="reservationsList">
                            <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Reservation ID</th>
                                                <th>Guest</th>
                                                <th>Room</th>
                                                <th>Type</th>
                                                <th>Check-in</th>
                                                <th>Check-out</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody><tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-3">
                                                    <div class="avatar-initial bg-label-primary rounded-circle">R</div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">RES-202510191929116750</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <h6 class="mb-0">Test User</h6>
                                                <small class="text-muted">test.user@example.com</small>
                                            </div>
                                        </td>
                                        <td>99</td>
                                        <td>Room</td>
                                        <td>Oct 20, 2023</td>
                                        <td>Oct 21, 2023</td>
                                        <td><span class="badge bg-warning status-badge">Pending</span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="viewReservation('RES-202510191929116750')"><i class="cil-eye me-2"></i>View Details</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="editReservation('RES-202510191929116750')"><i class="cil-pencil me-2"></i>Edit Reservation</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="viewReservationHistory('RES-202510191929116750')"><i class="cil-history me-2"></i>View History</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-success" href="#" onclick="confirmReservation('RES-202510191929116750')"><i class="cil-check me-2"></i>Confirm</a></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="cancelReservation('RES-202510191929116750')"><i class="cil-x me-2"></i>Cancel</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr><tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-3">
                                                    <div class="avatar-initial bg-label-primary rounded-circle">R</div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">RES-202510191833313173</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <h6 class="mb-0">Alice Williams</h6>
                                                <small class="text-muted">alice.williams@example.com</small>
                                            </div>
                                        </td>
                                        <td>99</td>
                                        <td>Room</td>
                                        <td>Oct 20, 2025</td>
                                        <td>Oct 20, 2025</td>
                                        <td><span class="badge bg-info status-badge">Completed</span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="viewReservation('RES-202510191833313173')"><i class="cil-eye me-2"></i>View Details</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="editReservation('RES-202510191833313173')"><i class="cil-pencil me-2"></i>Edit Reservation</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="viewReservationHistory('RES-202510191833313173')"><i class="cil-history me-2"></i>View History</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-success" href="#" onclick="confirmReservation('RES-202510191833313173')"><i class="cil-check me-2"></i>Confirm</a></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="cancelReservation('RES-202510191833313173')"><i class="cil-x me-2"></i>Cancel</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr></tbody>
                                    </table>
                                </div>                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservation Statistics Chart -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Reservation Activity Trend (Last 30 Days)</h4>
                    </div>
                    <div class="card-body">
                        <div id="reservationChart" style="height: 300px; background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                            <div class="text-center">
                                <i class="cil-chart-line" style="font-size: 4rem; opacity: 0.5;"></i>
                                <p class="mt-3">Interactive Chart Placeholder</p>
                                <small class="text-muted">Reservation bookings and trends over time</small>
                                <br>
                                <button class="btn btn-sm btn-outline-light mt-2" onclick="loadChartData()">Load Chart Data</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Room Reservation Modal -->
    <div class="modal fade" id="newReservationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Room Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newReservationForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="guest_search" class="form-label">Search Guest</label>
                                <input type="text" class="form-control" id="guest_search" placeholder="Type guest name or email..." onkeyup="searchGuests()">
                                <div id="guest_results" class="mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                                <input type="hidden" id="selected_guest_id" name="guest_id">
                            </div>
                            <div class="col-md-6">
                                <label for="room_selection" class="form-label">Select Room</label>
                                <select class="form-select" id="room_selection" name="room_id" required>
                                    <option value="">Select a room...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="check_in_date" class="form-label">Check-in Date *</label>
                                <input type="datetime-local" class="form-control" id="check_in_date" name="check_in_date" value="2025-10-20T19:46" required onchange="updateAvailableRooms()">
                            </div>
                            <div class="col-md-6">
                                <label for="check_out_date" class="form-label">Check-out Date *</label>
                                <input type="datetime-local" class="form-control" id="check_out_date" name="check_out_date" required onchange="updateAvailableRooms()">
                            </div>
                            <div class="col-md-6">
                                <label for="reservation_type" class="form-label">Reservation Type</label>
                                <select class="form-select" id="reservation_type" name="reservation_type">
                                    <option value="Room" selected>Room</option>
                                    <option value="Event">Event</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="reservation_hour_count" class="form-label">Duration (hours)</label>
                                <select class="form-select" id="reservation_hour_count" name="reservation_hour_count">
                                    <option value="8" selected>8 hours</option>
                                    <option value="16">16 hours</option>
                                    <option value="24">24 hours</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="reservation_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="reservation_notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- New Event Reservation Modal -->
    <div class="modal fade" id="newEventReservationModal" data-coreui-backdrop="static" data-coreui-keyboard="false" tabindex="-1" aria-labelledby="newEventReservationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newEventReservationModalLabel">New Event Reservation</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newEventReservationForm">
                        <div class="row g-3">
                            <!-- Event Title -->
                            <div class="col-md-6">
                                <label for="event_title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" id="event_title" name="event_title" placeholder="Enter event title..." required>
                            </div>

                            <!-- Event Organizer -->
                            <div class="col-md-6">
                                <label for="event_organizer" class="form-label">Event Organizer *</label>
                                <input type="text" class="form-control" id="event_organizer" name="event_organizer" placeholder="Enter organizer name..." required>
                            </div>

                            <!-- Organizer Contact -->
                            <div class="col-md-6">
                                <label for="event_organizer_contact" class="form-label">Organizer Contact *</label>
                                <input type="text" class="form-control" id="event_organizer_contact" name="event_organizer_contact" placeholder="Phone or email..." required>
                            </div>

                            <!-- Expected Attendees -->
                            <div class="col-md-6">
                                <label for="event_expected_attendees" class="form-label">Expected Attendees *</label>
                                <input type="number" class="form-control" id="event_expected_attendees" name="event_expected_attendees" min="1" placeholder="Number of attendees..." required>
                            </div>

                            <!-- Venue Selection -->
                            <div class="col-md-6">
                                <label for="event_venue_selection" class="form-label">Select Venue *</label>
                                <select class="form-select" id="event_venue_selection" name="event_venue_id" required onchange="updateEventVenueInfo()">
                                    <option value="">Select a venue...</option>
                                </select>
                                <div id="venue_info" class="mt-1 small text-muted"></div>
                            </div>

                            <!-- Event Status -->
                            <div class="col-md-6">
                                <label for="event_status" class="form-label">Initial Status</label>
                                <select class="form-select" id="event_status" name="event_status">
                                    <option value="Pending" selected>Pending</option>
                                    <option value="Checked In">Checked In</option>
                                    <option value="Checked Out">Checked Out</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>

                            <!-- Check-in Date -->
                            <div class="col-md-6">
                                <label for="event_checkin" class="form-label">Event Check-in Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="event_checkin" name="event_checkin" value="2025-10-20T19:46" required onchange="updateAvailableVenues()">
                            </div>

                            <!-- Check-out Date -->
                            <div class="col-md-6">
                                <label for="event_checkout" class="form-label">Event Check-out Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="event_checkout" name="event_checkout" value="2025-10-21T19:46" required onchange="updateAvailableVenues()">
                            </div>

                            <!-- Duration -->
                            <div class="col-md-6">
                                <label for="event_hour_count" class="form-label">Duration (hours) *</label>
                                <select class="form-select" id="event_hour_count" name="event_hour_count" required>
                                    <option value="8" selected>8 hours</option>
                                    <option value="16">16 hours</option>
                                    <option value="24">24 hours</option>
                                </select>
                            </div>

                            <!-- Days Count -->
                            <div class="col-md-6">
                                <label for="event_days_count" class="form-label">Number of Days (optional)</label>
                                <input type="number" class="form-control" id="event_days_count" name="event_days_count" min="1" max="30" placeholder="Leave blank for hourly">
                            </div>

                            <!-- Event Description -->
                            <div class="col-12">
                                <label for="event_description" class="form-label">Event Description</label>
                                <textarea class="form-control" id="event_description" name="event_description" rows="3" placeholder="Describe the event..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" form="newEventReservationForm">Create Event Reservation</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CoreUI JS -->
    <script src="js/coreui.bundle.js"></script>
    <script src="js/bootstrap.bundle.js"></script>
    <script>
        let currentPage = 1;
        let itemsPerPage = 25;
        let currentReservations = [{"id":"RES-202510191929116750","guest_name":"Test User","guest_email":"test.user@example.com","room_number":"99","reservation_type":"Room","check_in_date":"2023-10-20 10:00:00","check_out_date":"2023-10-21 18:00:00","status":"Pending","status_display":"Pending","status_badge_class":"bg-warning"},{"id":"RES-202510191833313173","guest_name":"Alice Williams","guest_email":"alice.williams@example.com","room_number":"99","reservation_type":"Room","check_in_date":"2025-10-20 00:33:00","check_out_date":"2025-10-20 08:33:00","status":"Checked Out","status_display":"Completed","status_badge_class":"bg-info"}];
        let allGuests = [{"id":4,"first_name":"Alice","last_name":"Williams","email":"alice.williams@example.com","phone":"2233445566","full_name":"Alice Williams"},{"id":13,"first_name":"Benjamin","last_name":"Martinez","email":"benjamin.martinez@example.com","phone":"1122334455","full_name":"Benjamin Martinez"},{"id":3,"first_name":"Bob","last_name":"Johnson","email":"bob.johnson@example.com","phone":"1122334455","full_name":"Bob Johnson"},{"id":5,"first_name":"David","last_name":"Brown","email":"david.brown@example.com","phone":"3344556677","full_name":"David Brown"},{"id":6,"first_name":"Emily","last_name":"Davis","email":"emily.davis@example.com","phone":"4455667788","full_name":"Emily Davis"},{"id":14,"first_name":"Isabella","last_name":"Rodriguez","email":"isabella.rodriguez@example.com","phone":"2233445566","full_name":"Isabella Rodriguez"},{"id":11,"first_name":"James","last_name":"Miller","email":"james.miller@example.com","phone":"9900112233","full_name":"James Miller"},{"id":2,"first_name":"Jane","last_name":"Smith","email":"jane.smith@example.com","phone":"0987654321","full_name":"Jane Smith"},{"id":1,"first_name":"John","last_name":"Doe","email":"john.doe@example.com","phone":"1234567890","full_name":"John Doe"},{"id":15,"first_name":"Lucas","last_name":"Lopez","email":"lucas.lopez@example.com","phone":"3344556677","full_name":"Lucas Lopez"},{"id":7,"first_name":"Michael","last_name":"Wilson","email":"michael.wilson@example.com","phone":"5566778899","full_name":"Michael Wilson"},{"id":10,"first_name":"Olivia","last_name":"Green","email":"olivia.green@example.com","phone":"8899001122","full_name":"Olivia Green"},{"id":8,"first_name":"Sarah","last_name":"Taylor","email":"sarah.taylor@example.com","phone":"6677889900","full_name":"Sarah Taylor"},{"id":12,"first_name":"Sophia","last_name":"Garcia","email":"sophia.garcia@example.com","phone":"0011223344","full_name":"Sophia Garcia"},{"id":16,"first_name":"Test","last_name":"User","email":"test.user@example.com","phone":"1234567890","full_name":"Test User"},{"id":9,"first_name":"William","last_name":"Anderson","email":"william.anderson@example.com","phone":"7788990011","full_name":"William Anderson"}];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set active page in pagination
            document.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = parseInt(this.textContent);
                    loadPage(page);
                });
            });

            // Initialize available rooms on page load
            updateAvailableRooms();
            updateQuickAvailableRooms();
            updateAvailableVenues();

            // Initialize guest search status
            updateGuestStatus();
        });

        function showNewReservationModal() {
            const modal = new bootstrap.Modal(document.getElementById('newReservationModal'));
            modal.show();
        }

        function showNewEventReservationModal() {
            // For now, just show the same modal but could be customized for events
            document.getElementById('reservation_type').value = 'Event';
            showNewReservationModal();
        }

        function updateAvailableVenues() {
            const checkInDate = document.getElementById('event_checkin').value;
            const checkOutDate = document.getElementById('event_checkout').value;

            if (checkInDate && checkOutDate) {
                // Use direct database query for available venues
                
                const venues = [{"id":1,"venue_name":"Theater","venue_address":"123 Theater St","venue_capacity":50,"venue_rate":"5000.00","venue_description":"Theater venue"},{"id":2,"venue_name":"Auditorium","venue_address":"456 Auditorium Ave","venue_capacity":100,"venue_rate":"8000.00","venue_description":"Auditorium venue"},{"id":3,"venue_name":"Convention Center","venue_address":"789 Convention Blvd","venue_capacity":200,"venue_rate":"15000.00","venue_description":"Convention Center venue"},{"id":4,"venue_name":"Exhibition Hall","venue_address":"101 Exhibition Rd","venue_capacity":500,"venue_rate":"25000.00","venue_description":"Exhibition Hall venue"}];
                const venueSelect = document.getElementById('event_venue_selection');
                venueSelect.innerHTML = '<option value="">Select a venue...</option>';

                venues.forEach(venue => {
                    const option = document.createElement('option');
                    option.value = venue.id;
                    option.textContent = `${venue.venue_name} - ${venue.venue_address} (Capacity: ${venue.venue_capacity}) - ₱${venue.venue_rate}/hr`;
                    venueSelect.appendChild(option);
                });
            }
        }

        function updateEventVenueInfo() {
            const venueSelect = document.getElementById('event_venue_selection');
            const selectedOption = venueSelect.options[venueSelect.selectedIndex];
            const venueInfo = document.getElementById('venue_info');

            if (selectedOption.value) {
                // In a real implementation, you'd fetch venue details from the database
                venueInfo.textContent = 'Venue selected - details will be loaded from database';
            } else {
                venueInfo.textContent = '';
            }
        }

        function searchReservations() {
            const searchTerm = document.getElementById('searchInput').value;
            if (searchTerm.length >= 2) {
                // Use direct database query
                const searchData = [{"id":"RES-202510191929116750","guest_name":"Test User","guest_email":"test.user@example.com","room_number":"99","reservation_type":"Room","check_in_date":"2023-10-20 10:00:00","check_out_date":"2023-10-21 18:00:00","status":"Pending","status_display":"Pending","status_badge_class":"bg-warning"},{"id":"RES-202510191833313173","guest_name":"Alice Williams","guest_email":"alice.williams@example.com","room_number":"99","reservation_type":"Room","check_in_date":"2025-10-20 00:33:00","check_out_date":"2025-10-20 08:33:00","status":"Checked Out","status_display":"Completed","status_badge_class":"bg-info"}];
                updateReservationsTable(searchData);
            } else {
                refreshReservationList();
            }
        }

        function filterReservations() {
            const statusFilter = document.getElementById('statusFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;

            // Use direct database query with filters
            const filteredData = [{"id":"RES-202510191929116750","guest_name":"Test User","guest_email":"test.user@example.com","room_number":"99","reservation_type":"Room","check_in_date":"2023-10-20 10:00:00","check_out_date":"2023-10-21 18:00:00","status":"Pending","status_display":"Pending","status_badge_class":"bg-warning"},{"id":"RES-202510191833313173","guest_name":"Alice Williams","guest_email":"alice.williams@example.com","room_number":"99","reservation_type":"Room","check_in_date":"2025-10-20 00:33:00","check_out_date":"2025-10-20 08:33:00","status":"Checked Out","status_display":"Completed","status_badge_class":"bg-info"}];
            updateReservationsTable(filteredData);
        }

        function loadPage(page) {
            currentPage = page;
            const offset = (page - 1) * itemsPerPage;

            // Use direct database query for pagination
            const pageData = [];
[];
            updateReservationsTable(pageData);
            updatePagination(page);
        }

        function confirmReservation(reservationId) {
            if (confirm('Are you sure you want to confirm this reservation?')) {
                
                if (success) {
                    alert('Reservation confirmed successfully');
                    refreshReservationList();
                } else {
                    alert('Error confirming reservation');
                }
            }
        }

        function cancelReservation(reservationId) {
            if (confirm('Are you sure you want to cancel this reservation?')) {
                 $reservationId = mysqli_real_escape_string($conn, $_POST['reservation_id']);
                const success = true;
                if (success) {
                    alert('Reservation cancelled successfully');
                    refreshReservationList();
                } else {
                    alert('Error cancelling reservation');
                }
            }
        }



        function updateQuickAvailableRooms() {
            const checkInDate = document.getElementById('quick_check_in_date').value;
            const checkOutDate = document.getElementById('quick_check_out_date').value;

            if (checkInDate && checkOutDate) {
                $checkInDate = mysqli_real_escape_string($conn, $_POST['quick_check_in_date']);
                $checkOutDate = mysqli_real_escape_string($conn, $_POST['quick_check_out_date']);
                const rooms = [];
                const roomSelect = document.getElementById('quick_room_selection');
                roomSelect.innerHTML = '<option value="">Select a room...</option>';

                rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `${room.room_number} - ${room.room_type} (Floor ${room.floor}) - ₱${room.price_per_hour}/hr`;
                    roomSelect.appendChild(option);
                });
            }
        }

        // Form submission for quick reservation - now handled by PHP POST
        // The form will submit normally and redirect back with success/error messages

        function updateReservationsTable(reservations) {
            const tbody = document.querySelector('#reservationsList tbody');
            if (!tbody) return;

            if (reservations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted">No reservations found</td></tr>';
                return;
            }

            tbody.innerHTML = reservations.map(reservation => `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3">
                                <div class="avatar-initial bg-label-primary rounded-circle">${reservation.id.charAt(0).toUpperCase()}</div>
                            </div>
                            <div>
                                <h6 class="mb-0">${escapeHtml(reservation.id)}</h6>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>
                            <h6 class="mb-0">${escapeHtml(reservation.guest_name || 'Walk-in Guest')}</h6>
                            <small class="text-muted">${escapeHtml(reservation.guest_email || '')}</small>
                        </div>
                    </td>
                    <td>${escapeHtml(reservation.room_number || '-')}</td>
                    <td>${escapeHtml(reservation.reservation_type)}</td>
                    <td>${reservation.check_in_date ? new Date(reservation.check_in_date).toLocaleDateString() : '-'}</td>
                    <td>${reservation.check_out_date ? new Date(reservation.check_out_date).toLocaleDateString() : '-'}</td>
                    <td><span class="badge ${reservation.status_badge_class} status-badge">${escapeHtml(reservation.status_display || 'Unknown')}</span></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="viewReservation('${reservation.id}')"><i class="cil-eye me-2"></i>View Details</a></li>
                                <li><a class="dropdown-item" href="#" onclick="editReservation('${reservation.id}')"><i class="cil-pencil me-2"></i>Edit Reservation</a></li>
                                <li><a class="dropdown-item" href="#" onclick="viewReservationHistory('${reservation.id}')"><i class="cil-history me-2"></i>View History</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-success" href="#" onclick="confirmReservation('${reservation.id}')"><i class="cil-check me-2"></i>Confirm</a></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="cancelReservation('${reservation.id}')"><i class="cil-x me-2"></i>Cancel</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function updatePagination(activePage) {
            document.querySelectorAll('.page-item').forEach((item, index) => {
                item.classList.toggle('active', index + 1 === activePage);
            });
        }

        function viewReservation(reservationId) {
            window.location.href = `reservation-details.php?id=${reservationId}`;
        }

        function editReservation(reservationId) {
            window.location.href = `reservation-edit.php?id=${reservationId}`;
        }

        function viewReservationHistory(reservationId) {
            window.location.href = `reservation-history.php?id=${reservationId}`;
        }


        function confirmReservation(reservationId) {
            if (confirm('Are you sure you want to confirm this reservation?')) {
                fetch(`reservations-logic.php?action=confirm&id=${reservationId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Reservation confirmed successfully');
                        refreshReservationList();
                    } else {
                        alert('Error confirming reservation: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Confirm error:', error);
                    alert('Error confirming reservation');
                });
            }
        }

        function cancelReservation(reservationId) {
            if (confirm('Are you sure you want to cancel this reservation?')) {
                fetch(`reservations-logic.php?action=cancel&id=${reservationId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Reservation cancelled successfully');
                        refreshReservationList();
                    } else {
                        alert('Error cancelling reservation: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Cancel error:', error);
                    alert('Error cancelling reservation');
                });
            }
        }

        function generateReport() {
            window.location.href = 'reservation-reports.php';
        }

        function updateAvailableRooms() {
            const checkInDate = document.getElementById('check_in_date').value;
            const checkOutDate = document.getElementById('check_out_date').value;

            if (checkInDate && checkOutDate) {
                // Use direct database query instead of logic file
                const rooms = [];
                const roomSelect = document.getElementById('room_selection');
                roomSelect.innerHTML = '<option value="">Select a room...</option>';

                rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `${room.room_number} - ${room.room_type} (Floor ${room.floor})`;
                    roomSelect.appendChild(option);
                });
            }
        }

        function searchGuests() {
            const searchTerm = document.getElementById('guest_search').value;

            if (searchTerm.length >= 2) {
                fetch(`guest_search.php?q=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(guests => {
                        const resultsDiv = document.getElementById('guest_results');
                        resultsDiv.innerHTML = '';

                        if (guests.length === 0) {
                            resultsDiv.innerHTML = '<div class="text-muted">No guests found</div>';
                            return;
                        }

                        guests.forEach(guest => {
                            const guestDiv = document.createElement('div');
                            guestDiv.className = 'guest-result-item';
                            guestDiv.style.cssText = 'padding: 8px; cursor: pointer; border-bottom: 1px solid #eee;';
                            guestDiv.innerHTML = `
                                <strong>${escapeHtml(guest.full_name)}</strong><br>
                                <small class="text-muted">${escapeHtml(guest.email)} | ${escapeHtml(guest.phone || 'No phone')}</small>
                            `;
                            guestDiv.onclick = () => selectGuest(guest);
                            resultsDiv.appendChild(guestDiv);
                        });
                    })
                    .catch(error => console.error('Error searching guests:', error));
            } else {
                document.getElementById('guest_results').innerHTML = '';
            }
        }

        function updateGuestStatus() {
            const statusDiv = document.getElementById('guest_status');
            const selectedGuestId = document.getElementById('quick_selected_guest_id').value;

            if (selectedGuestId) {
                statusDiv.innerHTML = `<i class="cil-check-circle text-success me-1"></i> Guest selected`;
            } else if (allGuests && allGuests.length > 0) {
                statusDiv.innerHTML = `<i class="cil-check-circle text-success me-1"></i> ${allGuests.length} guests available`;
            } else {
                statusDiv.innerHTML = `<i class="cil-x-circle text-warning me-1"></i> No guests data available`;
            }
        }


        function selectGuestQuick(guest) {
            document.getElementById('quick_guest_search').value = guest.full_name;
            document.getElementById('quick_selected_guest_id').value = guest.id;
            document.getElementById('quick_guest_results').innerHTML = '';
            updateGuestStatus();
        }

        // Handle datalist selection
        document.getElementById('quick_guest_search').addEventListener('input', function() {
            const selectedValue = this.value;
            const datalist = document.getElementById('guest-list');
            const options = datalist.querySelectorAll('option');

            for (let option of options) {
                if (option.value === selectedValue) {
                    document.getElementById('quick_selected_guest_id').value = option.getAttribute('data-id');
                    updateGuestStatus();
                    break;
                }
            }
        });

        function selectGuest(guest) {
            document.getElementById('guest_search').value = guest.full_name;
            document.getElementById('selected_guest_id').value = guest.id;
            document.getElementById('guest_results').innerHTML = '';
        }

        function loadChartData() {
            alert('Chart data loading would be implemented here');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }


        // Form submission for new reservation
        document.getElementById('newReservationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const reservationData = Object.fromEntries(formData.entries());
            reservationData.reservation_date = new Date().toISOString();

            fetch('reservations-logic.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(reservationData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Reservation created successfully');
                    bootstrap.Modal.getInstance(document.getElementById('newReservationModal')).hide();
                    this.reset();
                    document.getElementById('guest_results').innerHTML = '';
                    refreshReservationList();
                } else {
                    alert('Error creating reservation: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Create reservation error:', error);
                alert('Error creating reservation');
            });
        });

        // Form submission for new event reservation
        document.getElementById('newEventReservationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const eventData = Object.fromEntries(formData.entries());

            // Add creation timestamp
            eventData.created_at = new Date().toISOString();

            
            const event_id = false;

            if (event_id) {
                alert('Event reservation created successfully');
                // Close modal using CoreUI
                const modal = document.getElementById('newEventReservationModal');
                const modalInstance = window.coreui.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                this.reset();
                refreshReservationList();
            } else {
                alert('Error creating event reservation. Please check all required fields.');
            }
        });
    </script>
</body>
</html>

