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
                    // Create new channel
                    $stmt = $conn->prepare("INSERT INTO channels (channel_name, channel_type, contact_email, contact_phone, commission_rate, base_url, api_key, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['channel_name'],
                        $_POST['channel_type'],
                        $_POST['contact_email'] ?: null,
                        $_POST['contact_phone'] ?: null,
                        $_POST['commission_rate'] ?: 0.00,
                        $_POST['base_url'] ?: null,
                        $_POST['api_key'] ?: null,
                        $_POST['status'],
                        $_POST['notes'] ?: null
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Channel created successfully']);
                    break;

                case 'update':
                    // Update channel
                    $stmt = $conn->prepare("UPDATE channels SET channel_name=?, channel_type=?, contact_email=?, contact_phone=?, commission_rate=?, base_url=?, api_key=?, status=?, notes=? WHERE id=?");
                    $stmt->execute([
                        $_POST['channel_name'],
                        $_POST['channel_type'],
                        $_POST['contact_email'] ?: null,
                        $_POST['contact_phone'] ?: null,
                        $_POST['commission_rate'] ?: 0.00,
                        $_POST['base_url'] ?: null,
                        $_POST['api_key'] ?: null,
                        $_POST['status'],
                        $_POST['notes'] ?: null,
                        $_POST['id']
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Channel updated successfully']);
                    break;

                case 'delete':
                    // Delete channel
                    $stmt = $conn->prepare("DELETE FROM channels WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    echo json_encode(['success' => true, 'message' => 'Channel deleted successfully']);
                    break;

                case 'get':
                    // Get channel data for editing
                    $stmt = $conn->prepare("SELECT * FROM channels WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $channel = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($channel);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get all channels for display
$stmt = $conn->query("SELECT * FROM channels ORDER BY created_at DESC");
$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get channel statistics
$stats = $conn->query("
    SELECT
        COUNT(*) as total_channels,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_channels,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_channels,
        AVG(commission_rate) as avg_commission
    FROM channels
")->fetch(PDO::FETCH_ASSOC);

// Get recent bookings
$recentBookings = $conn->query("
    SELECT cb.*, c.channel_name
    FROM channel_bookings cb
    JOIN channels c ON cb.channel_id = c.id
    ORDER BY cb.booking_date DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Channels - Hotel Management System</title>

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
        .channel-card {
            cursor: pointer;
        }
        .channel-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .channel-actions {
            display: none;
        }
        .channel-card:hover .channel-actions {
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
                <?php include 'channelstitle.html'; ?>
                </div>
                <div>
                    <small class="text-muted d-block">Total</small>
                    <span class="fw-bold text-primary"><?php echo $stats['total_channels']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Active</small>
                    <span class="fw-bold text-success"><?php echo $stats['active_channels']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Pending</small>
                    <span class="fw-bold text-warning"><?php echo $stats['pending_channels']; ?></span>
                </div>
                <div>
                    <small class="text-muted d-block">Avg Commission</small>
                    <span class="fw-bold text-info"><?php echo number_format($stats['avg_commission'], 1); ?>%</span>
                </div>
            </div>
        </div>

        <!-- Channels -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Channels</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" onclick="generateReport()">
                        <i class="cil-file-pdf me-1"></i>Report
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openCreateChannelModal()">
                        <i class="cil-plus me-1"></i>Add Channel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="channelsContainer">
                    <?php foreach ($channels as $channel): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 channel-card" style="border-left: 4px solid <?php
                            echo $channel['status'] === 'Active' ? '#198754' :
                                 ($channel['status'] === 'Pending' ? '#fd7e14' :
                                 ($channel['status'] === 'Inactive' ? '#6c757d' : '#dc3545'));
                        ?>;">
                            <div class="card-body">
                                <div class="channel-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($channel['channel_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($channel['channel_type']); ?> • <?php echo number_format($channel['commission_rate'], 1); ?>% commission
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-<?php
                                                echo $channel['status'] === 'Active' ? 'success' :
                                                     ($channel['status'] === 'Pending' ? 'warning' :
                                                     ($channel['status'] === 'Inactive' ? 'secondary' : 'danger'));
                                            ?>">
                                                <?php echo htmlspecialchars($channel['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="channel-actions justify-content-center">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="editChannel(<?php echo $channel['id']; ?>)" title="Edit">
                                        <i class="cil-pencil me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteChannel(<?php echo $channel['id']; ?>, '<?php echo htmlspecialchars($channel['channel_name']); ?>')" title="Remove">
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

        <!-- Recent Bookings -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Bookings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Channel</th>
                                <th>Reference</th>
                                <th>Guest</th>
                                <th>Check-in</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['channel_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                                <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                <td>₱<?php echo number_format($booking['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $booking['booking_status'] === 'Confirmed' ? 'success' :
                                             ($booking['booking_status'] === 'Completed' ? 'primary' :
                                             ($booking['booking_status'] === 'Cancelled' ? 'danger' : 'warning'));
                                    ?>">
                                        <?php echo htmlspecialchars($booking['booking_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Channel Modal -->
    <div class="modal fade" id="channelModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Channel</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="channelForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="channelId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="channel_name" class="form-label">Channel Name *</label>
                                <input type="text" class="form-control" id="channel_name" name="channel_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="channel_type" class="form-label">Channel Type *</label>
                                <select class="form-select" id="channel_type" name="channel_type" required>
                                    <option value="OTA">OTA</option>
                                    <option value="Direct">Direct</option>
                                    <option value="GDS">GDS</option>
                                    <option value="Wholesale">Wholesale</option>
                                    <option value="Corporate">Corporate</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact_email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                                <input type="number" class="form-control" id="commission_rate" name="commission_rate" step="0.01" min="0" max="100">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Disabled">Disabled</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="base_url" class="form-label">Base URL</label>
                            <input type="url" class="form-control" id="base_url" name="base_url">
                        </div>

                        <div class="mb-3">
                            <label for="api_key" class="form-label">API Key</label>
                            <input type="text" class="form-control" id="api_key" name="api_key">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitChannelForm()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HTMX Response Target -->
    <div id="htmx-response" class="d-none"></div>

    <script>
        function openCreateChannelModal() {
            document.getElementById('modalTitle').textContent = 'Add Channel';
            document.getElementById('formAction').value = 'create';
            document.getElementById('channelId').value = '';
            document.getElementById('channelForm').reset();
            new coreui.Modal(document.getElementById('channelModal')).show();
        }

        function editChannel(id) {
            document.getElementById('modalTitle').textContent = 'Edit Channel';
            document.getElementById('formAction').value = 'update';

            // Fetch channel data
            fetch('channels.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true'
                },
                body: 'action=get&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('channelId').value = data.id;
                document.getElementById('channel_name').value = data.channel_name;
                document.getElementById('channel_type').value = data.channel_type;
                document.getElementById('contact_email').value = data.contact_email || '';
                document.getElementById('contact_phone').value = data.contact_phone || '';
                document.getElementById('commission_rate').value = data.commission_rate;
                document.getElementById('status').value = data.status;
                document.getElementById('base_url').value = data.base_url || '';
                document.getElementById('api_key').value = data.api_key || '';
                document.getElementById('notes').value = data.notes || '';

                new coreui.Modal(document.getElementById('channelModal')).show();
            });
        }

        function deleteChannel(id, name) {
            if (confirm('Are you sure you want to delete the channel "' + name + '"? This action cannot be undone.')) {
                fetch('channels.php', {
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

        function submitChannelForm() {
            const form = document.getElementById('channelForm');
            const formData = new FormData(form);

            fetch('channels.php', {
                method: 'POST',
                headers: {
                    'HX-Request': 'true'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new coreui.Modal(document.getElementById('channelModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function generateReport() {
            window.open('generate_report.php?page=channels&type=pdf', '_blank');
        }
    </script>
</body>
</html>