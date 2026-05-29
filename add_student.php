<?php
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $class = $_POST['class'];
    
    $query = "INSERT INTO students (student_id, name, class) VALUES ('$student_id', '$name', '$class')";
    if (mysqli_query($conn, $query)) {
        $success = "Student added successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Student</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial; margin: 50px; }
        .form-group { margin: 15px 0; }
        label { display: inline-block; width: 100px; }
        input, select { padding: 8px; width: 250px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Add New Student</h1>
    <a href="index.php">← Back to Home</a>
    
    <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Student ID:</label>
            <input type="text" name="student_id" required placeholder="e.g., STU004">
        </div>
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Class:</label>
            <input type="text" name="class" required placeholder="e.g., Grade 10">
        </div>
        <button type="submit">Add Student</button>
    </form>
</body>
</html>