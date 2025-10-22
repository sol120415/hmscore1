<?php
include_once 'db.php';
require_once 'google_oauth_config.php';

// Verify ID token posted from Google One Tap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idToken = $_POST['credential'] ?? '';
    if (!$idToken) {
        http_response_code(400);
        echo 'Missing credential';
        exit;
    }

    // Verify token using Google's tokeninfo endpoint (server-side simple validation)
    $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
    $resp = @file_get_contents($verifyUrl);
    if ($resp === false) {
        http_response_code(400);
        echo 'Failed to verify token';
        exit;
    }
    $payload = json_decode($resp, true);
    if (!is_array($payload) || ($payload['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
        http_response_code(400);
        echo 'Invalid token';
        exit;
    }

    $email = $payload['email'] ?? '';
    if (!$email) {
        http_response_code(400);
        echo 'No email in token';
        exit;
    }

    // Create/find user
    $stmt = $conn->prepare('SELECT id, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Do NOT auto-create users. Require existing email in DB.
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No account is associated with this Gmail.']);
        exit;
    }

    // Log user in
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['verified'] = true;

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo 'Method not allowed';
?>


