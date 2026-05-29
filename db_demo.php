<?php
session_start();
include 'config/database.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];
$message = '';
$sql_text = '';

// Simple demo functions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['show_students'])) {
        $result = mysqli_query($conn, "SELECT * FROM students LIMIT 10");
        $sql_text = "SELECT * FROM students LIMIT 10";
        $message = "<strong>SELECT Query Result:</strong><br>";
        while($row = mysqli_fetch_assoc($result)) {
            $message .= "• " . $row['student_id'] . " - " . $row['first_name'] . " " . $row['last_name'] . "<br>";
        }
    }
    elseif (isset($_POST['show_attendance'])) {
        $result = mysqli_query($conn, "SELECT s.first_name, s.last_name, a.date, a.status 
                                       FROM students s 
                                       JOIN attendance a ON s.id = a.student_id 
                                       LIMIT 10");
        $sql_text = "SELECT s.first_name, s.last_name, a.date, a.status 
                     FROM students s 
                     JOIN attendance a ON s.id = a.student_id 
                     LIMIT 10";
        $message = "<strong>JOIN Query Result (Students + Attendance):</strong><br>";
        while($row = mysqli_fetch_assoc($result)) {
            $message .= "• " . $row['first_name'] . " " . $row['last_name'] . " - " . $row['status'] . " on " . $row['date'] . "<br>";
        }
    }
    elseif (isset($_POST['group_by'])) {
        $result = mysqli_query($conn, "SELECT class, COUNT(*) as total FROM students GROUP BY class");
        $sql_text = "SELECT class, COUNT(*) as total FROM students GROUP BY class";
        $message = "<strong>GROUP BY Query Result:</strong><br>";
        while($row = mysqli_fetch_assoc($result)) {
            $message .= "• " . $row['class'] . ": " . $row['total'] . " students<br>";
        }
    }
    elseif (isset($_POST['insert_test'])) {
        $test_id = 'TEST' . rand(100, 999);
        $sql_text = "INSERT INTO students (student_id, first_name, last_name, class, status) 
                     VALUES ('$test_id', 'Test', 'Student', 'Test Class', 'active')";
        mysqli_query($conn, $sql_text);
        $message = "<strong>INSERT DML Operation:</strong><br>Added test student with ID: $test_id";
    }
    elseif (isset($_POST['update_test'])) {
        $sql_text = "UPDATE students SET class = 'Updated Class' WHERE student_id LIKE 'TEST%'";
        mysqli_query($conn, $sql_text);
        $message = "<strong>UPDATE DML Operation:</strong><br>Updated test student's class";
    }
    elseif (isset($_POST['delete_test'])) {
        $sql_text = "DELETE FROM students WHERE student_id LIKE 'TEST%'";
        mysqli_query($conn, $sql_text);
        $message = "<strong>DELETE DML Operation:</strong><br>Removed all test students";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Demo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        
        .top-nav {
            background: #1a1a2e;
            color: white;
            padding: 15px 30px;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo { font-size: 20px; font-weight: bold; }
        .logo i { color: #667eea; }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 15px;
        }
        
        .nav-menu li a {
            color: white;
            text-decoration: none;
            padding: 10px;
        }
        
        .nav-menu li a:hover { color: #667eea; }
        .nav-menu li.active a { color: #667eea; }
        
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-avatar {
            width: 35px;
            height: 35px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-btn {
            background: #e53e3e;
            padding: 5px 12px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
        }
        
        .main-container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; color: #1a1a2e; }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card-header {
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .card-header h2 { font-size: 18px; color: #1a1a2e; }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }
        
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #48bb78; color: white; }
        .btn-warning { background: #ed8936; color: white; }
        .btn-danger { background: #e53e3e; color: white; }
        
        .result-box {
            background: #1a1a2e;
            color: #e0e0e0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
            margin-top: 15px;
        }
        
        .sql-box {
            background: #2d2d3d;
            color: #ffd966;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .constraint-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: #888;
            border-top: 1px solid #ddd;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="nav-container">
            <div class="logo"><i class="fas fa-graduation-cap"></i> AttendEase</div>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="mark_attendance.php">Mark Attendance</a></li>
                <li><a href="view_attendance.php">Reports</a></li>
                <li><a href="search.php">Search</a></li>
                <li class="active"><a href="db_demo.php">DB Demo</a></li>
                <?php if($user_role == 'admin'): ?>
                    <li><a href="admin/manage_students.php">Students</a></li>
                    <li><a href="admin/manage_subjects.php">Subjects</a></li>
                    <li><a href="admin/manage_teachers.php">Teachers</a></li>
                <?php endif; ?>
            </ul>
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="main-container">
        <div class="page-header">
            <h1><i class="fas fa-database"></i> Database Concepts Demo</h1>
            <p>Click any button to see SQL queries in action</p>
        </div>
        
        <div class="grid-2">
            <!-- SELECT & JOIN Card -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-search"></i> SELECT & JOIN</h2>
                </div>
                <form method="POST">
                    <div class="btn-group">
                        <button type="submit" name="show_students" class="btn btn-primary">SELECT * FROM students</button>
                        <button type="submit" name="show_attendance" class="btn btn-primary">SELECT with JOIN</button>
                        <button type="submit" name="group_by" class="btn btn-primary">GROUP BY clause</button>
                    </div>
                </form>
            </div>
            
            <!-- DML Card -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-edit"></i> DML (INSERT, UPDATE, DELETE)</h2>
                </div>
                <form method="POST">
                    <div class="btn-group">
                        <button type="submit" name="insert_test" class="btn btn-success">INSERT Test Data</button>
                        <button type="submit" name="update_test" class="btn btn-warning">UPDATE Test Data</button>
                        <button type="submit" name="delete_test" class="btn btn-danger">DELETE Test Data</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Result Display -->
        <?php if($message): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-output"></i> Query Result</h2>
            </div>
            <div class="result-box">
                <?php echo $message; ?>
            </div>
            <?php if($sql_text): ?>
            <div class="sql-box">
                <strong>SQL Executed:</strong><br>
                <?php echo $sql_text; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Database Constraints Card -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-check-circle"></i> Database Constraints & Features</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div>
                    <h3 style="margin-bottom: 10px; font-size: 16px;">DDL Constraints:</h3>
                    <ul class="constraint-list">
                        <li>✓ PRIMARY KEY (id in each table)</li>
                        <li>✓ FOREIGN KEY (attendance.student_id → students.id)</li>
                        <li>✓ UNIQUE (student_id)</li>
                        <li>✓ NOT NULL (first_name, last_name, class)</li>
                        <li>✓ CHECK (status IN ('active', 'inactive'))</li>
                        <li>✓ DEFAULT (status defaults to 'active')</li>
                    </ul>
                </div>
                <div>
                    <h3 style="margin-bottom: 10px; font-size: 16px;">Other Features:</h3>
                    <ul class="constraint-list">
                        <li>✓ INDEXES (idx_student_name, idx_attendance_date)</li>
                        <li>✓ VIEWS (view_active_students)</li>
                        <li>✓ STORED PROCEDURES (GetAttendanceByDate)</li>
                        <li>✓ TRIGGERS (before_student_delete)</li>
                        <li>✓ JOIN Operations (LEFT JOIN)</li>
                        <li>✓ GROUP BY with COUNT()</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>© 2024 Attendance Management System - Complete DBMS Project</p>
        </div>
    </div>
</body>
</html>