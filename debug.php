<?php
require 'db.php';

$datas = $_POST;

if (!empty($datas)) {
    try {
        $id = bin2hex(random_bytes(25));
        $stmt = $conn->prepare("INSERT INTO reservations (id, guest_id, room_id, reservation_type, reservation_date, reservation_hour_count, reservation_days_count, reservation_status, check_in_date, check_out_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $datas['guest_id'],
            $datas['room_id'],
            $datas['reservation_type'],
            $datas['reservation_date'],
            $datas['reservation_hour_count'],
            $datas['reservation_days_count'],
            $datas['reservation_status'],
            $datas['check_in_date'],
            $datas['check_out_date']
        ]);
        echo "<p>Reservation inserted successfully. ID: $id</p>";
    } catch (PDOException $e) {
        echo "<p>Error inserting reservation: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>No POST data received.</p>";
}

foreach ($datas as $key => $value) {
    echo "<p>$key: $value</p>";
}
?>