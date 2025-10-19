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
                    // Create new guest
                    $stmt = $conn->prepare("INSERT INTO guests (first_name, last_name, email, phone, address, city, country, id_type, id_number, date_of_birth, nationality, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['email'],
                        $_POST['phone'] ?: null,
                        $_POST['address'] ?: null,
                        $_POST['city'] ?: null,
                        $_POST['country'] ?: null,
                        $_POST['id_type'],
                        $_POST['id_number'],
                        $_POST['date_of_birth'],
                        $_POST['nationality'] ?: null,
                        $_POST['notes'] ?: null
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Guest created successfully']);
                    break;

                case 'update':
                    // Update guest
                    $stmt = $conn->prepare("UPDATE guests SET first_name=?, last_name=?, email=?, phone=?, address=?, city=?, country=?, id_type=?, id_number=?, date_of_birth=?, nationality=?, notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['email'],
                        $_POST['phone'] ?: null,
                        $_POST['address'] ?: null,
                        $_POST['city'] ?: null,
                        $_POST['country'] ?: null,
                        $_POST['id_type'],
                        $_POST['id_number'],
                        $_POST['date_of_birth'],
                        $_POST['nationality'] ?: null,
                        $_POST['notes'] ?: null,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Guest updated successfully']);
                    break;

                case 'delete':
                    // Delete guest
                    $stmt = $conn->prepare("DELETE FROM guests WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Guest deleted successfully']);
                    break;

                case 'get':
                    // Get guest data for editing
                    $stmt = $conn->prepare("SELECT * FROM guests WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $guest = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($guest);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Guest Loyalty Program: Update VIP status based on stay count and spend
$loyaltyUpdate = $conn->prepare("
    UPDATE guests
    SET loyalty_status = CASE
        WHEN (stay_count >= 5 OR total_spend >= 1000) THEN 'VIP'
        ELSE 'Regular'
    END
    WHERE stay_count >= 5 OR total_spend >= 1000
");
$loyaltyUpdate->execute();

// Get all guests for display
$stmt = $conn->query("SELECT * FROM guests ORDER BY created_at DESC");
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get guest statistics
$stats = $conn->query("
    SELECT
        COUNT(*) as total_guests,
        COUNT(CASE WHEN nationality = 'American' THEN 1 END) as american_guests,
        COUNT(CASE WHEN nationality = 'Canadian' THEN 1 END) as canadian_guests,
        COUNT(CASE WHEN id_type = 'Passport' THEN 1 END) as passport_guests,
        COUNT(CASE WHEN loyalty_status = 'VIP' THEN 1 END) as vip_guests,
        AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())) as avg_age
    FROM guests
")->fetch(PDO::FETCH_ASSOC);

// Get recent guests (last 10)
$recentGuests = array_slice($guests, 0, 10);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guests - Hotel Management System</title>

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
        .guest-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0dcaf0, #667eea);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        .reservation-card {
            transition: transform 0.2s;
        }
        .reservation-card:hover {
            transform: translateY(-2px);
        }

        /* Custom font for title */
        @font-face {
            font-family: 'TheFont';
            src: url("https://garet.typeforward.com/assets/fonts/shared/TFMixVF.woff2") format('woff2');
        }

        @keyframes letter-breathe {
            from, to {
                font-variation-settings: 'wght' 100;
            }
            50% {
                font-variation-settings: 'wght' 900;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">

        <!-- Statistics Card -->
        <div class="card stats-card text-white mb-4">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-2">
                        <h6 class="mb-1">Total</h6>
                        <h4 class="mb-0"><?php echo $stats['total_guests']; ?></h4>
                    </div>
                    <div class="col-2">
                        <h6 class="mb-1">American</h6>
                        <h4 class="mb-0"><?php echo $stats['american_guests']; ?></h4>
                    </div>
                    <div class="col-2">
                        <h6 class="mb-1">Passports</h6>
                        <h4 class="mb-0"><?php echo $stats['passport_guests']; ?></h4>
                    </div>
                    <div class="col-2">
                        <h6 class="mb-1">VIP</h6>
                        <h4 class="mb-0"><?php echo $stats['vip_guests']; ?></h4>
                    </div>
                    <div class="col-2">
                        <h6 class="mb-1">Canadian</h6>
                        <h4 class="mb-0"><?php echo $stats['canadian_guests']; ?></h4>
                    </div>
                    <div class="col-2">
                        <h6 class="mb-1">Avg Age</h6>
                        <h4 class="mb-0"><?php echo round($stats['avg_age'] ?: 0); ?> yrs</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guests Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Guests</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" onclick="generateReport()">
                        <i class="cil-file-pdf me-1"></i>Report
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openCreateModal()">
                        <i class="cil-plus me-1"></i>Add Guest
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Guest</th>
                                <th>Contact</th>
                                <th>ID Info</th>
                                <th>Nationality</th>
                                <th>Status</th>
                                <th>Birth Date</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($guests as $guest): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="guest-avatar me-3">
                                            <?php echo strtoupper(substr($guest['first_name'], 0, 1) . substr($guest['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($guest['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($guest['phone'] ?: 'N/A'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars(($guest['city'] ?: '') . ', ' . ($guest['country'] ?: '')); ?></small>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($guest['id_type']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($guest['id_number']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($guest['nationality'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($guest['loyalty_status'] ?? 'Regular') === 'VIP' ? 'warning' : 'secondary'; ?>">
                                        <i class="cil-star me-1"></i><?php echo htmlspecialchars($guest['loyalty_status'] ?? 'Regular'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($guest['date_of_birth'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($guest['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editGuest(<?php echo $guest['id']; ?>)">
                                        <i class="cil-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteGuest(<?php echo $guest['id']; ?>, '<?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>')">
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

        <!-- Recent Guests -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Guests</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recentGuests as $guest): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100" style="border-left: 4px solid <?php echo ($guest['loyalty_status'] ?? 'Regular') === 'VIP' ? '#fd7e14' : '#6c757d'; ?>;">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="guest-avatar me-3">
                                        <?php echo strtoupper(substr($guest['first_name'], 0, 1) . substr($guest['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($guest['email']); ?></small>
                                    </div>
                                </div>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Nationality</small>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($guest['nationality'] ?: 'N/A'); ?></span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">ID Type</small>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($guest['id_type']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Guest Modal -->
    <div class="modal fade" id="guestModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Guest</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="guestForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="guestId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="id_type" class="form-label">ID Type *</label>
                                <select class="form-select" id="id_type" name="id_type" required>
                                    <option value="Passport">Passport</option>
                                    <option value="Driver License">Driver License</option>
                                    <option value="National ID">National ID</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="id_number" class="form-label">ID Number *</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nationality" class="form-label">Nationality</label>
                                <input type="text" class="form-control" id="nationality" name="nationality">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitGuestForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add Guest';
            document.getElementById('formAction').value = 'create';
            document.getElementById('guestId').value = '';
            document.getElementById('guestForm').reset();
        }

        function editGuest(id) {
            document.getElementById('modalTitle').textContent = 'Edit Guest';
            document.getElementById('formAction').value = 'update';

            // Fetch guest data
            fetch('guests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('guestId').value = data.id;
                document.getElementById('first_name').value = data.first_name;
                document.getElementById('last_name').value = data.last_name;
                document.getElementById('email').value = data.email;
                document.getElementById('phone').value = data.phone || '';
                document.getElementById('address').value = data.address || '';
                document.getElementById('city').value = data.city || '';
                document.getElementById('country').value = data.country || '';
                document.getElementById('id_type').value = data.id_type;
                document.getElementById('id_number').value = data.id_number;
                document.getElementById('date_of_birth').value = data.date_of_birth;
                document.getElementById('nationality').value = data.nationality || '';
                document.getElementById('notes').value = data.notes || '';

                new coreui.Modal(document.getElementById('guestModal')).show();
            });
        }

        function deleteGuest(id, name) {
            if (confirm('Are you sure you want to delete the guest "' + name + '"? This action cannot be undone.')) {
                fetch('guests.php', {
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

        function submitGuestForm() {
            const form = document.getElementById('guestForm');
            const formData = new FormData(form);

            fetch('guests.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('guestModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function generateReport() {
            window.open('generate_report.php?page=guests&type=pdf', '_blank');
        }
    </script>
</body>
</html>