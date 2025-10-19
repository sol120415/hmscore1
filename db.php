<?php
// Database configuration
$host = 'localhost';
$dbname = 'hmscore1';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password (empty)

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Enable prepared statements emulation (optional)
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

} catch(PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>