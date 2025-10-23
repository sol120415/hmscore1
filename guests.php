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
        COUNT(CASE WHEN nationality = 'Filipino' THEN 1 END) as filipino_guests,
        COUNT(CASE WHEN nationality != 'Filipino' THEN 1 END) as non_filipino_guests,
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
    <script src="js/app-modal.js?v=<?php echo @filemtime('js/app-modal.js'); ?>"></script>

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
                <div class="flex-grow-1 text-start">
                    <h2>Guests</h2>
                </div>
                <div>
                    <small class="text-muted d-block">Total</small>
                    <span class="fw-bold text-primary"><?php echo $stats['total_guests']; ?></span>
                </div>

                <div>
                    <small class="text-muted d-block">Filipino</small>
                    <span class="fw-bold text-success"><?php echo $stats['filipino_guests']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Passports</small>
                    <span class="fw-bold text-info"><?php echo $stats['passport_guests']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Non-Filipino</small>
                    <span class="fw-bold text-danger"><?php echo $stats['non_filipino_guests']; ?></span>
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
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#guestModal" onclick="openCreateModal()">
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
        <div class="modal-dialog" style="max-width: 60vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Guest</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="guestForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="guestId">

                        <div class="row g-3 align-items-start">
                            <div class="col-lg-7">
                                <div class="rounded-3 border p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">First Name *</label>
                                            <input type="text" class="form-control form-control-sm" id="first_name" name="first_name" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Last Name *</label>
                                            <input type="text" class="form-control form-control-sm" id="last_name" name="last_name" required>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">Email *</label>
                                            <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Phone</label>
                                            <input type="tel" class="form-control form-control-sm" id="phone" name="phone" inputmode="numeric" maxlength="11" oninput="this.value=this.value.replace(/\D/g,'').slice(0,11)" onkeypress="return /[0-9]/.test(event.key)">
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Address</label>
                                        <textarea class="form-control form-control-sm" id="address" name="address" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="rounded-3 border p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">City</label>
                                            <input type="text" class="form-control form-control-sm" id="city" name="city">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Country</label>
                                            <input type="text" class="form-control form-control-sm" id="country" name="country">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">ID Type *</label>
                                            <select class="form-select form-select-sm" id="id_type" name="id_type" required>
                                                <option value="Passport">Passport</option>
                                                <option value="Driver License">Driver License</option>
                                                <option value="National ID">National ID</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">ID Number *</label>
                                            <input type="text" class="form-control form-control-sm" id="id_number" name="id_number" required>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">Date of Birth *</label>
                                            <input type="date" class="form-control form-control-sm" id="date_of_birth" name="date_of_birth" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Nationality</label>
                                            <input type="text" class="form-control form-control-sm" id="nationality" name="nationality">
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                     
                                    
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label small">Notes</label>
                                        <textarea class="form-control form-control-sm" id="notes" name="notes" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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

                new bootstrap.Modal(document.getElementById('guestModal')).show();
            });
        }

        function deleteGuest(id, name) {
            AppModal.confirm('Are you sure you want to delete the guest "' + name + '"? This action cannot be undone.','localhost says').then(function(yes){ if(!yes) return; 
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
            });
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
                    new bootstrap.Modal(document.getElementById('guestModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function archiveGuest(id, name) {
            AppModal.confirm('Are you sure you want to archive the guest "' + name + '"? They will be moved to the archived list.','localhost says').then(function(yes){ if(!yes) return; 
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
            });
        }

        function restoreGuest(id, name) {
            AppModal.confirm('Are you sure you want to restore the guest "' + name + '"? They will be moved back to active guests.','localhost says').then(function(yes){ if(!yes) return; 
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
            });
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
                                <h5 class="modal-title">
                                    <i class="cil-gift text-warning me-2"></i>Loyalty Rewards - ${guestName}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
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
                                        <div class="progress-bar" role="progressbar" style="width: ${progressPercent}%; background-color: #198754 !important;" aria-valuenow="${progressPercent}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">${nightsToNext} more stays to reach ${nextTier} tier</small>
                                </div>
                `;
            } else {
                modalHtml += `
                                        <div class="progress-bar" role="progressbar" style="width: 100%; background-color: #198754 !important;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small style="color: #198754 !important;">Maximum tier reached!</small>
                                </div>
                `;
            }

            modalHtml += `
                                <div class="row g-3">
            `;

            rewardsData.forEach(reward => {
                const isCurrentTier = reward.tier === currentTier;
                // Force colors inline to avoid conflicts (Regular = colorless, black text)
                let headerBg = '#ffffff';
                let headerText = '#000000';
                let borderClr = '#e5e7eb';
                if (reward.tier === 'Iron') { headerBg = '#6c757d'; headerText = '#ffffff'; borderClr = '#6c757d'; }
                if (reward.tier === 'Gold') { headerBg = '#ffc107'; headerText = '#212529'; borderClr = '#ffc107'; }
                if (reward.tier === 'Diamond') { headerBg = '#0d6efd'; headerText = '#ffffff'; borderClr = '#0d6efd'; }
                // Highlight current tier in green
                const currentBorder = isCurrentTier ? '#198754' : borderClr;
                const borderWidth = isCurrentTier ? '3px' : '2px';
                const currentBadge = isCurrentTier ? '<span class="badge" style="background-color:#198754 !important; color:#ffffff !important;">Current</span>' : '';
                const nightsText = reward.nights > 0 ? `${reward.nights} nights to unlock` : '&nbsp;';
                const bodyTextColor = reward.tier === 'Regular' ? '#000000' : '#212529';
                const nightsTextColor = reward.tier === 'Regular' ? '#000000' : '#6c757d';
                const headerTitleColor = reward.tier === 'Regular' ? '#000000' : headerText;

                modalHtml += `
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100" style="border: ${borderWidth} solid ${currentBorder} !important; background:#ffffff !important;">
                            <div class="card-header text-center" style="background: ${headerBg} !important; color: ${headerText} !important;">
                                <h6 class="mb-0" style="color:${headerTitleColor} !important;">${reward.tier}</h6>
                                ${currentBadge}
                            </div>
                            <div class="card-body text-center" style="background:#ffffff !important;">
                                <div class="small" style="color:${nightsTextColor} !important; margin-bottom: .5rem;">${nightsText}</div>
                                <div class="small" style="min-height:48px; color:${bodyTextColor} !important;">
                                    ${reward.rewards.split('; ').map(item => `<div>${item}</div>`).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            modalHtml += `
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            const modal = new bootstrap.Modal(document.getElementById('rewardsModal'));
            modal.show();
        }
    </script>
</body>
</html>