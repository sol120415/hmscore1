<?php
    include_once 'db.php';

    $stmt = $conn->prepare("SELECT * FROM guests");
    $stmt->execute();
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($guests as $guest) {
        echo "<p>" . htmlspecialchars($guest['first_name']) . ", Name: " . htmlspecialchars($guest['name']) . "</p>";
    }