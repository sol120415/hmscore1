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
                    $stmt = $conn->prepare("INSERT INTO guests (first_name, last_name, email, phone, address, city, country, id_type, id_number, date_of_birth, nationality, guest_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)");
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
                    $stmt = $conn->prepare("UPDATE guests SET first_name=?, last_name=?, email=?, phone=?, address=?, city=?, country=?, id_type=?, id_number=?, date_of_birth=?, nationality=?, notes=?, stay_count=?, total_spend=? WHERE id=?");
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
                        $_POST['stay_count'] ?: 0,
                        $_POST['total_spend'] ?: 0.00,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Guest updated successfully']);
                    break;

                case 'archive':
                    // Archive guest
                    $stmt = $conn->prepare("UPDATE guests SET guest_status='Archived' WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Guest archived successfully']);
                    break;

                case 'restore':
                    // Restore guest
                    $stmt = $conn->prepare("UPDATE guests SET guest_status='Active' WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Guest restored successfully']);
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

// Guest Loyalty Program: Update loyalty status based on stay count
$loyaltyUpdate = $conn->prepare("
    UPDATE guests
    SET loyalty_status = CASE
        WHEN stay_count >= 50 THEN 'Diamond'
        WHEN stay_count >= 20 THEN 'Gold'
        WHEN stay_count >= 5 THEN 'Iron'
        ELSE 'Regular'
    END
    WHERE guest_status = 'Active'
");
$loyaltyUpdate->execute();

// Get all active guests for display
$stmt = $conn->query("SELECT * FROM guests WHERE guest_status = 'Active' ORDER BY created_at DESC");
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all archived guests for display
$stmt = $conn->query("SELECT * FROM guests WHERE guest_status = 'Archived' ORDER BY updated_at DESC");
$archivedGuests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        .guest-card {
            cursor: pointer;
        }
        .guest-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .guest-actions {
            display: none;
        }
        .guest-card:hover .guest-actions {
            display: flex;
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

        <!-- Header with Stats -->
        <div class="mb-4">
            <div class="d-flex justify-content-between gap-3 text-center">
                <div class="text-center flex-grow-1">
                <?php include 'gueststitle.html'; ?>
                </div>
                <div>
                    <small class="text-muted d-block">Total</small>
                    <span class="fw-bold text-primary"><?php echo $stats['total_guests']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">VIP</small>
                    <span class="fw-bold text-warning"><?php echo $stats['vip_guests']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">American</small>
                    <span class="fw-bold text-success"><?php echo $stats['american_guests']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Passports</small>
                    <span class="fw-bold text-info"><?php echo $stats['passport_guests']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Canadian</small>
                    <span class="fw-bold text-danger"><?php echo $stats['canadian_guests']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Avg Age</small>
                    <span class="fw-bold text-secondary"><?php echo round($stats['avg_age'] ?: 0); ?> yrs</span>
                </div>
            </div>
        </div>

        <!-- Active Guests -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Active Guests</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" onclick="generateReport()">
                        <i class="cil-file-pdf me-1"></i>Report
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openCreateModal()">
                        <i class="cil-plus me-1"></i>Add Guest
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="guestsContainer">
                    <?php foreach ($guests as $guest): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 guest-card" style="border-left: 4px solid <?php
                            $stayCount = $guest['stay_count'] ?? 0;
                            echo $stayCount >= 50 ? '#0d6efd' :
                                 ($stayCount >= 20 ? '#fd7e14' :
                                 ($stayCount >= 5 ? '#0dcaf0' : '#6c757d'));
                        ?>;">
                            <div class="card-body">
                                <div class="guest-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($guest['email']); ?> • <?php echo htmlspecialchars($guest['nationality'] ?: 'N/A'); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-<?php
                                                $stayCount = $guest['stay_count'] ?? 0;
                                                echo $stayCount >= 50 ? 'primary' :
                                                     ($stayCount >= 20 ? 'warning' :
                                                     ($stayCount >= 5 ? 'info' : 'secondary'));
                                            ?>">
                                                <?php
                                                $stayCount = $guest['stay_count'] ?? 0;
                                                echo $stayCount >= 50 ? 'Diamond' :
                                                     ($stayCount >= 20 ? 'Gold' :
                                                     ($stayCount >= 5 ? 'Iron' : 'Regular'));
                                                ?>
                                            </span>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($guest['id_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="guest-actions justify-content-center">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="editGuest(<?php echo $guest['id']; ?>)" title="Edit">
                                        <i class="cil-pencil me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-info me-2" onclick="viewRewards(<?php echo $guest['id']; ?>, '<?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>', '<?php echo htmlspecialchars($guest['loyalty_status']); ?>')" title="View Rewards">
                                        <i class="cil-gift me-1"></i>Rewards
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning me-2" onclick="archiveGuest(<?php echo $guest['id']; ?>, '<?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>')" title="Archive">
                                        <i class="cil-archive me-1"></i>Archive
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteGuest(<?php echo $guest['id']; ?>, '<?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>')" title="Remove">
                                        <i class="cil-trash me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($guests)): ?>
                <p class="text-muted mb-0">No active guests.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Archived Guests -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Archived Guests</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-info" onclick="generateArchivedReport()">
                        <i class="cil-file-pdf me-1"></i>Archived Report
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="archivedContainer">
                    <?php foreach ($archivedGuests as $guest): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 archived-card" style="border-left: 4px solid #6c757d; opacity: 0.7;">
                            <div class="card-body">
                                <div class="archived-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($guest['email']); ?> • <?php echo htmlspecialchars($guest['nationality'] ?: 'N/A'); ?>
                                            </small>
                                            <br><small class="text-secondary">
                                                Archived: <?php echo date('M j, Y', strtotime($guest['updated_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($guest['guest_status']); ?>
                                            </span>
                                            <span class="badge bg-<?php
                                                $stayCount = $guest['stay_count'] ?? 0;
                                                echo $stayCount >= 50 ? 'primary' :
                                                     ($stayCount >= 20 ? 'warning' :
                                                     ($stayCount >= 5 ? 'info' : 'secondary'));
                                            ?>">
                                                <?php
                                                $stayCount = $guest['stay_count'] ?? 0;
                                                echo $stayCount >= 50 ? 'Diamond' :
                                                     ($stayCount >= 20 ? 'Gold' :
                                                     ($stayCount >= 5 ? 'Iron' : 'Regular'));
                                                ?>
                                            </span>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($guest['id_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="archived-actions justify-content-center">
                                    <button class="btn btn-sm btn-outline-success me-2" onclick="restoreGuest(<?php echo $guest['id']; ?>, '<?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>')" title="Restore">
                                        <i class="cil-reload me-1"></i>Restore
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteGuest(<?php echo $guest['id']; ?>, '<?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>')" title="Remove">
                                        <i class="cil-trash me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($archivedGuests)): ?>
                <p class="text-muted mb-0">No archived guests.</p>
                <?php endif; ?>
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
                        <div class="card h-100" style="border-left: 4px solid <?php
                            $stayCount = $guest['stay_count'] ?? 0;
                            echo $stayCount >= 50 ? '#0d6efd' :
                                 ($stayCount >= 20 ? '#fd7e14' :
                                 ($stayCount >= 5 ? '#0dcaf0' : '#6c757d'));
                        ?>;">
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
                            <div class="col-md-3 mb-3">
                                <label for="stay_count" class="form-label">Stay Count</label>
                                <input type="number" class="form-control" id="stay_count" name="stay_count" min="0" value="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="total_spend" class="form-label">Total Spend ($)</label>
                                <input type="number" class="form-control" id="total_spend" name="total_spend" min="0" step="0.01" value="0.00">
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
                document.getElementById('stay_count').value = data.stay_count || 0;
                document.getElementById('total_spend').value = data.total_spend || 0.00;
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

        function archiveGuest(id, name) {
            if (confirm('Are you sure you want to archive the guest "' + name + '"? They will be moved to the archived list.')) {
                fetch('guests.php', {
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

        function restoreGuest(id, name) {
            if (confirm('Are you sure you want to restore the guest "' + name + '"? They will be moved back to active guests.')) {
                fetch('guests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'HX-Request': 'true'
                    },
                    body: 'action=restore&id=' + id
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

        function generateReport() {
            window.open('generate_report.php?page=guests&type=pdf', '_blank');
        }

        function generateArchivedReport() {
            window.open('generate_report.php?page=archived_guests&type=pdf', '_blank');
        }

        function viewRewards(guestId, guestName, currentTier) {
            // Define rewards data
            const rewardsData = [
                { tier: 'Regular', nights: 0, rewards: 'No discount; basic guest services.' },
                { tier: 'Iron', nights: 5, rewards: '10% off stays; priority check-in; free standard Wi-Fi.' },
                { tier: 'Gold', nights: 20, rewards: '15% off stays; room upgrade (subject to avail.); complimentary breakfast.' },
                { tier: 'Diamond', nights: 50, rewards: '25% off stays; suite upgrade + lounge access; late checkout no additional fee (<1hour); free parking.' }
            ];

            // Fetch current guest data to get accurate stay count
            fetch('guests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get&id=' + guestId
            })
            .then(response => response.json())
            .then(guestData => {
                const stayCount = guestData.stay_count || 0;
                const actualTier = stayCount >= 50 ? 'Diamond' :
                                  (stayCount >= 20 ? 'Gold' :
                                  (stayCount >= 5 ? 'Iron' : 'Regular'));

                showRewardsModal(guestName, actualTier, rewardsData, stayCount);
            })
            .catch(error => {
                console.error('Error fetching guest data:', error);
                // Fallback to passed currentTier
                showRewardsModal(guestName, currentTier, rewardsData, 0);
            });
        }

        function showRewardsModal(guestName, currentTier, rewardsData, stayCount) {

            // Create modal HTML
            let modalHtml = `
                <div class="modal fade" id="rewardsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Loyalty Rewards - ${guestName}</h5>
                                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <strong>Current Tier: ${currentTier}</strong> (${stayCount} stays)
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Next tier progress:</small>
                                    <div class="progress" style="height: 8px;">
            `;

            // Calculate progress to next tier
            let progressPercent = 0;
            let nextTier = '';
            let nightsToNext = 0;

            if (stayCount < 5) {
                progressPercent = (stayCount / 5) * 100;
                nextTier = 'Iron';
                nightsToNext = 5 - stayCount;
            } else if (stayCount < 20) {
                progressPercent = ((stayCount - 5) / 15) * 100;
                nextTier = 'Gold';
                nightsToNext = 20 - stayCount;
            } else if (stayCount < 50) {
                progressPercent = ((stayCount - 20) / 30) * 100;
                nextTier = 'Diamond';
                nightsToNext = 50 - stayCount;
            } else {
                progressPercent = 100;
                nextTier = 'Max';
            }

            if (nextTier !== 'Max') {
                modalHtml += `
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: ${progressPercent}%" aria-valuenow="${progressPercent}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">${nightsToNext} more stays to reach ${nextTier} tier</small>
                                </div>
                `;
            } else {
                modalHtml += `
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-success">Maximum tier reached!</small>
                                </div>
                `;
            }

            modalHtml += `
                                <div class="table-responsive">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tier</th>
                                                <th>Nights to Unlock</th>
                                                <th>Discounts & Rewards</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
            `;

            rewardsData.forEach(reward => {
                const isCurrentTier = reward.tier === currentTier;
                const statusIcon = isCurrentTier ? '<i class="cil-check-circle text-success"></i>' : '<i class="cil-circle text-muted"></i>';
                const tierColor = reward.tier === 'Diamond' ? 'text-primary' :
                                 (reward.tier === 'Gold' ? 'text-warning' :
                                 (reward.tier === 'Iron' ? 'text-info' : 'text-secondary'));

                modalHtml += `
                    <tr class="${isCurrentTier ? 'table-primary' : ''}">
                        <td><strong class="${tierColor}">${reward.tier}</strong></td>
                        <td>${reward.nights}</td>
                        <td>${reward.rewards}</td>
                        <td class="text-center">${statusIcon}</td>
                    </tr>
                `;
            });

            modalHtml += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if present
            const existingModal = document.getElementById('rewardsModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Show modal
            const modal = new coreui.Modal(document.getElementById('rewardsModal'));
            modal.show();
        }
    </script>
</body>
</html>