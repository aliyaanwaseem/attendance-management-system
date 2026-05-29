<?php
include 'config/database.php';

echo "<h2>Database Check</h2>";

$tables = ['users', 'students', 'subjects', 'attendance'];

foreach($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if(mysqli_num_rows($result) > 0) {
        echo "✓ Table '$table' exists<br>";
    } else {
        echo "✗ Table '$table' MISSING<br>";
    }
}
?>