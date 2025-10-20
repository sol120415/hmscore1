<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.7/dist/htmx.min.js"></script>
</head>
<body>
    <script src="js/htmx.min.js"></script>

    <form hx-post="debug.php" hx-target="#response" hx-swap="innerHTML">
        
        <input type="text" name="guest_id" placeholder="Guest ID" value="2">
        <input type="text" name="room_id" placeholder="Room ID" value="1">
        <input type="text" name="reservation_type" placeholder="Reservation Type" value="Room">
        <input type="text" name="reservation_date" placeholder="Reservation Date" value="2024-11-15 14:00:00">
        <input type="text" name="reservation_hour_count" placeholder="Reservation Hour Count" value="8">
        <input type="text" name="reservation_days_count" placeholder="Reservation Days Count" value="1">
        <input type="text" name="reservation_status" placeholder="Reservation Status" value="Pending">
        <input type="text" name="check_in_date" placeholder="Check-in Date" value="2024-11-15 14:00:00">
        <input type="text" name="check_out_date" placeholder="Check-out Date" value="2024-11-16 14:00:00">
        <button type="submit">Create Reservation</button>
    </form>

    <div id="response"></div>

     <script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.7/dist/htmx.min.js"></script>
</body>
</html>
