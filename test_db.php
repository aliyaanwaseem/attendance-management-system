<?php
$conn = mysqli_connect('localhost', 'root', '', 'attendance_db');

if ($conn) {
    echo "✅ Database connected on port 3306!";
} else {
    echo "❌ Connection failed: " . mysqli_connect_error();
}
?>