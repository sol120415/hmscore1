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
                case 'create':
                    // Create new room
                    $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, room_floor, room_status, room_max_guests, room_amenities, room_maintenance_notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['room_number'],
                        $_POST['room_type'],
                        $_POST['room_floor'],
                        $_POST['room_status'],
                        $_POST['room_max_guests'] ?: 2,
                        $_POST['room_amenities'] ?: null,
                        $_POST['room_maintenance_notes'] ?: null
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Room created successfully']);
                    break;

                case 'update':
                    // Update room
                    $stmt = $conn->prepare("UPDATE rooms SET room_number=?, room_type=?, room_floor=?, room_status=?, room_max_guests=?, room_amenities=?, room_maintenance_notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['room_number'],
                        $_POST['room_type'],
                        $_POST['room_floor'],
                        $_POST['room_status'],
                        $_POST['room_max_guests'] ?: 2,
                        $_POST['room_amenities'] ?: null,
                        $_POST['room_maintenance_notes'] ?: null,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
                    break;

                case 'delete':
                    // Delete room
                    $stmt = $conn->prepare("DELETE FROM rooms WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
                    break;

                case 'get':
                    // Get room data for editing
                    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $room = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($room);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get all rooms for display
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_floor ASC, room_number ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get room statistics
$stats = $conn->query("
    SELECT
        COUNT(*) as total_rooms,
        COUNT(CASE WHEN room_status = 'Vacant' THEN 1 END) as vacant_rooms,
        COUNT(CASE WHEN room_status = 'Occupied' THEN 1 END) as occupied_rooms,
        COUNT(CASE WHEN room_status = 'Cleaning' THEN 1 END) as cleaning_rooms,
        COUNT(CASE WHEN room_status = 'Maintenance' THEN 1 END) as maintenance_rooms,
        COUNT(CASE WHEN room_type = 'Single' THEN 1 END) as single_rooms,
        COUNT(CASE WHEN room_type = 'Double' THEN 1 END) as double_rooms,
        COUNT(CASE WHEN room_type = 'Deluxe' THEN 1 END) as deluxe_rooms,
        COUNT(CASE WHEN room_type = 'Suite' THEN 1 END) as suite_rooms
    FROM rooms
")->fetch(PDO::FETCH_ASSOC);

// Get rooms by floor
$roomsByFloor = [];
foreach ($rooms as $room) {
    $floor = $room['room_floor'];
    if (!isset($roomsByFloor[$floor])) {
        $roomsByFloor[$floor] = [];
    }
    $roomsByFloor[$floor][] = $room;
}
ksort($roomsByFloor);

// Get occupancy rate
$occupancyRate = $stats['total_rooms'] > 0 ? round(($stats['occupied_rooms'] / $stats['total_rooms']) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Hotel Management System</title>

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
        .room-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .room-card:hover {
            transform: translateY(-2px);
        }
        .room-vacant {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }
        .room-occupied {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        }
        .room-cleaning {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        }
        .room-maintenance {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        }
        .room-reserved {
            background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);
        }
        .modal-content {
            background: #2d3748;
            border: 1px solid #4a5568;
        }
        .floor-section {
            margin-bottom: 2rem;
        }
        .floor-header {
            background: #4a5568;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Room Management</h2>
                <p class="text-muted mb-0">Manage hotel rooms and monitor occupancy</p>
            </div>
            <button class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#roomModal" onclick="openCreateModal()">
                <i class="cil-plus me-2"></i>Add Room
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Rooms</h6>
                                <h3 class="mb-0"><?php echo $stats['total_rooms']; ?></h3>
                            </div>
                            <i class="cil-home fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Vacant</h6>
                                <h3 class="mb-0"><?php echo $stats['vacant_rooms']; ?></h3>
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
                                <h6 class="card-title mb-1">Occupied</h6>
                                <h3 class="mb-0"><?php echo $stats['occupied_rooms']; ?></h3>
                            </div>
                            <i class="cil-user fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Cleaning</h6>
                                <h3 class="mb-0"><?php echo $stats['cleaning_rooms']; ?></h3>
                            </div>
                            <i class="cil-brush fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Maintenance</h6>
                                <h3 class="mb-0"><?php echo $stats['maintenance_rooms']; ?></h3>
                            </div>
                            <i class="cil-wrench fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Occupancy</h6>
                                <h3 class="mb-0"><?php echo $occupancyRate; ?>%</h3>
                            </div>
                            <i class="cil-chart-pie fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Type Breakdown -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Single Rooms</h5>
                        <h3 class="text-primary"><?php echo $stats['single_rooms']; ?></h3>
                        <small class="text-muted">$1,500/night</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Double Rooms</h5>
                        <h3 class="text-success"><?php echo $stats['double_rooms']; ?></h3>
                        <small class="text-muted">$2,500/night</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Deluxe Rooms</h5>
                        <h3 class="text-warning"><?php echo $stats['deluxe_rooms']; ?></h3>
                        <small class="text-muted">$3,500/night</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Suites</h5>
                        <h3 class="text-danger"><?php echo $stats['suite_rooms']; ?></h3>
                        <small class="text-muted">$4,500/night</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rooms by Floor -->
        <?php foreach ($roomsByFloor as $floor => $floorRooms): ?>
        <div class="floor-section">
            <div class="floor-header">
                <h4 class="mb-0">Floor <?php echo $floor; ?> (<?php echo count($floorRooms); ?> rooms)</h4>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($floorRooms as $room): ?>
                        <div class="col-md-3 col-lg-2 mb-3">
                            <div class="card room-card room-<?php echo strtolower($room['room_status']); ?> text-white h-100" onclick="editRoom(<?php echo $room['id']; ?>)">
                                <div class="card-body text-center">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($room['room_number']); ?></h5>
                                    <p class="card-text mb-2"><?php echo htmlspecialchars($room['room_type']); ?></p>
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($room['room_status']); ?></span>
                                    <br><small class="mt-2 d-block"><?php echo htmlspecialchars($room['room_max_guests']); ?> guests max</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Room Details Table -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Room Details</h5>
                <button class="btn btn-success btn-sm" onclick="generateReport()">
                    <i class="cil-file-pdf me-2"></i>Generate Report
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Room Number</th>
                                <th>Type</th>
                                <th>Floor</th>
                                <th>Status</th>
                                <th>Max Guests</th>
                                <th>Amenities</th>
                                <th>Last Cleaned</th>
                                <th>Maintenance Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                <td><?php echo htmlspecialchars($room['room_floor']); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $room['room_status'] === 'Vacant' ? 'success' :
                                             ($room['room_status'] === 'Occupied' ? 'warning' :
                                             ($room['room_status'] === 'Cleaning' ? 'primary' :
                                             ($room['room_status'] === 'Maintenance' ? 'danger' : 'secondary')));
                                    ?>">
                                        <?php echo htmlspecialchars($room['room_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($room['room_max_guests']); ?></td>
                                <td><?php echo htmlspecialchars($room['room_amenities'] ?: 'N/A'); ?></td>
                                <td><?php echo $room['room_last_cleaned'] ? date('M d, Y', strtotime($room['room_last_cleaned'])) : 'Never'; ?></td>
                                <td><?php echo htmlspecialchars($room['room_maintenance_notes'] ?: 'N/A'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editRoom(<?php echo $room['id']; ?>)">
                                        <i class="cil-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')">
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

    <!-- Room Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Room</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="roomForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="roomId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_number" class="form-label">Room Number *</label>
                                <input type="text" class="form-control" id="room_number" name="room_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room_floor" class="form-label">Floor *</label>
                                <input type="text" class="form-control" id="room_floor" name="room_floor" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_type" class="form-label">Room Type *</label>
                                <select class="form-select" id="room_type" name="room_type" required>
                                    <option value="Single">Single</option>
                                    <option value="Double">Double</option>
                                    <option value="Deluxe">Deluxe</option>
                                    <option value="Suite">Suite</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room_status" class="form-label">Status *</label>
                                <select class="form-select" id="room_status" name="room_status" required>
                                    <option value="Vacant">Vacant</option>
                                    <option value="Occupied">Occupied</option>
                                    <option value="Cleaning">Cleaning</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Reserved">Reserved</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_max_guests" class="form-label">Max Guests</label>
                                <input type="number" class="form-control" id="room_max_guests" name="room_max_guests" min="1" value="2">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="room_amenities" class="form-label">Amenities</label>
                            <textarea class="form-control" id="room_amenities" name="room_amenities" rows="3" placeholder="e.g., TV, Air Conditioning, Bathroom, Kitchen"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="room_maintenance_notes" class="form-label">Maintenance Notes</label>
                            <textarea class="form-control" id="room_maintenance_notes" name="room_maintenance_notes" rows="3" placeholder="Any maintenance issues or notes"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitRoomForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add Room';
            document.getElementById('formAction').value = 'create';
            document.getElementById('roomId').value = '';
            document.getElementById('roomForm').reset();
        }

        function editRoom(id) {
            document.getElementById('modalTitle').textContent = 'Edit Room';
            document.getElementById('formAction').value = 'update';

            // Fetch room data
            fetch('rooms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('roomId').value = data.id;
                document.getElementById('room_number').value = data.room_number;
                document.getElementById('room_floor').value = data.room_floor;
                document.getElementById('room_type').value = data.room_type;
                document.getElementById('room_status').value = data.room_status;
                document.getElementById('room_max_guests').value = data.room_max_guests;
                document.getElementById('room_amenities').value = data.room_amenities || '';
                document.getElementById('room_maintenance_notes').value = data.room_maintenance_notes || '';

                new coreui.Modal(document.getElementById('roomModal')).show();
            });
        }

        function deleteRoom(id, roomNumber) {
            if (confirm('Are you sure you want to delete room ' + roomNumber + '? This action cannot be undone.')) {
                fetch('rooms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=delete&id=' + id
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

        function submitRoomForm() {
            const form = document.getElementById('roomForm');
            const formData = new FormData(form);

            fetch('rooms.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('roomModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function generateReport() {
            window.open('generate_report.php?page=rooms&type=pdf', '_blank');
        }
    </script>
</body>
</html>