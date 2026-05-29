<?php
include 'config/database.php';

echo "<h2>Testing Database Connection</h2>";

// Test 1: Check connection
if ($conn) {
    echo "✓ Database connected successfully<br><br>";
} else {
    echo "✗ Database connection failed<br>";
}

// Test 2: Show users in database
$result = mysqli_query($conn, "SELECT * FROM users");
echo "<h3>Users in database:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Password</th><th>Role</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['password'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 3: Try to login with admin/admin123
echo "<h3>Test Login:</h3>";
$test_user = 'admin';
$test_pass = 'admin123';
$sql = "SELECT * FROM users WHERE username = '$test_user' AND password = '$test_pass'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    echo "✓ Login test SUCCESSFUL! admin/admin123 works.<br>";
    echo "You should be able to login to the system.";
} else {
    echo "✗ Login test FAILED! admin/admin123 does not work.<br>";
    echo "Check that password is exactly 'admin123' in the table above.";
}
?>