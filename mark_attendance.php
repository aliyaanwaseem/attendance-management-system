<?php
session_start();
include 'config/database.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['full_name'];

$today = date('Y-m-d');
$message = '';
$message_type = '';

// Get unique classes from students
$classes_query = mysqli_query($conn, "SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class");
$classes = [];
while($row = mysqli_fetch_assoc($classes_query)) {
    $classes[] = $row['class'];
}

$selected_class = isset($_POST['class']) ? $_POST['class'] : (isset($_GET['class']) ? $_GET['class'] : '');
$selected_date = isset($_POST['date']) ? $_POST['date'] : $today;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['class']) && isset($_POST['attendance'])) {
    $class = $_POST['class'];
    $date = $_POST['date'];
    
    foreach ($_POST['attendance'] as $student_id => $status) {
        $remarks = isset($_POST['remarks'][$student_id]) ? $_POST['remarks'][$student_id] : '';
        
        $check = "SELECT * FROM attendance WHERE student_id='$student_id' AND date='$date'";
        $result = mysqli_query($conn, $check);
        
        if (mysqli_num_rows($result) > 0) {
            $update = "UPDATE attendance SET status='$status', remarks='$remarks', marked_by='$user_id' WHERE student_id='$student_id' AND date='$date'";
            mysqli_query($conn, $update);
        } else {
            $insert = "INSERT INTO attendance (student_id, date, status, remarks, marked_by) VALUES ('$student_id', '$date', '$status', '$remarks', '$user_id')";
            mysqli_query($conn, $insert);
        }
    }
    $message = "Attendance saved successfully for " . date('F j, Y', strtotime($date));
    $message_type = "success";
}

// Get students based on selected class - ORDER BY student_id ASC (fixed)
$students = [];
if ($selected_class) {
    $students = mysqli_query($conn, "SELECT * FROM students WHERE class='$selected_class' AND status='active' ORDER BY student_id ASC");
    
    $existing = mysqli_query($conn, "SELECT * FROM attendance WHERE date='$selected_date'");
    $attendance_data = [];
    $remarks_data = [];
    while($row = mysqli_fetch_assoc($existing)) {
        $attendance_data[$row['student_id']] = $row['status'];
        $remarks_data[$row['student_id']] = $row['remarks'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Attendance Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        
        .top-nav {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 0 30px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: bold;
            padding: 15px 0;
        }
        
        .logo i {
            font-size: 28px;
            color: #667eea;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .nav-menu li a {
            color: #e0e0e0;
            text-decoration: none;
            padding: 20px 15px;
            display: block;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .nav-menu li a i {
            margin-right: 8px;
        }
        
        .nav-menu li a:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .nav-menu li.active a {
            color: #667eea;
            border-bottom: 3px solid #667eea;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .user-details {
            text-align: right;
        }
        
        .user-name {
            font-size: 14px;
            font-weight: 600;
        }
        
        .user-role {
            font-size: 11px;
            opacity: 0.7;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #e53e3e;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #666;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .card-header h2 {
            font-size: 20px;
            color: #1a1a2e;
        }
        
        .card-header i {
            color: #667eea;
            font-size: 24px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        label i {
            margin-right: 8px;
            color: #667eea;
        }
        
        select, input[type="date"] {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            transition: all 0.3s ease;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button i {
            margin-right: 8px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #48bb7820;
            color: #48bb78;
            border-left: 4px solid #48bb78;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 15px;
            background: #f8f9fa;
            color: #1a1a2e;
            font-weight: 600;
            font-size: 13px;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f2f5;
            font-size: 14px;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            font-weight: 600;
            cursor: pointer;
        }
        
        .status-present {
            background: #48bb7820;
            color: #48bb78;
            border-color: #48bb78;
        }
        
        .status-absent {
            background: #e53e3e20;
            color: #e53e3e;
            border-color: #e53e3e;
        }
        
        .status-late {
            background: #ed893620;
            color: #ed8936;
            border-color: #ed8936;
        }
        
        .status-excused {
            background: #4299e120;
            color: #4299e1;
            border-color: #4299e1;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .save-btn {
            margin-top: 20px;
            width: 100%;
            padding: 15px;
            font-size: 16px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #888;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .info-box {
            background: #667eea10;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: #888;
            font-size: 12px;
            border-top: 1px solid #e0e0e0;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            .data-table th, .data-table td {
                padding: 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>AttendEase</span>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="active"><a href="mark_attendance.php"><i class="fas fa-check-circle"></i> Mark Attendance</a></li>
                <li><a href="view_attendance.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li><a href="search.php"><i class="fas fa-search"></i> Search</a></li>
                <?php if($user_role == 'admin'): ?>
                    <li><a href="admin/manage_students.php"><i class="fas fa-users"></i> Students</a></li>
                    <li><a href="admin/manage_subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
                    <li><a href="admin/manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                <?php endif; ?>
            </ul>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?php echo $user_name; ?></div>
                    <div class="user-role"><?php echo ucfirst($user_role); ?></div>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
    
    <div class="main-container">
        <div class="page-header">
            <h1><i class="fas fa-check-circle"></i> Mark Attendance</h1>
            <p>Record student attendance by selecting a class and date</p>
        </div>
        
        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Select Class & Date</h2>
                <i class="fas fa-calendar-alt"></i>
            </div>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Select Class</label>
                        <select name="class" required onchange="this.form.submit()">
                            <option value="">-- Select a Class --</option>
                            <?php foreach($classes as $class): ?>
                                <option value="<?php echo $class; ?>" <?php echo ($selected_class == $class) ? 'selected' : ''; ?>>
                                    <?php echo $class; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if($selected_class): ?>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Select Date</label>
                        <input type="date" name="date" value="<?php echo $selected_date; ?>" required>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if($selected_class && mysqli_num_rows($students) > 0): ?>
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <span>Class: <strong><?php echo $selected_class; ?></strong> | Total Students: <strong><?php echo mysqli_num_rows($students); ?></strong> | Date: <strong><?php echo date('F j, Y', strtotime($selected_date)); ?></strong></span>
                </div>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if($selected_class && mysqli_num_rows($students) > 0): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Student Attendance List</h2>
                <i class="fas fa-<?php echo $selected_date == $today ? 'check-circle' : 'history'; ?>"></i>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="class" value="<?php echo $selected_class; ?>">
                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            while($student = mysqli_fetch_assoc($students)): 
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><strong><?php echo $student['student_id']; ?></strong></td>
                                <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                <td>
                                    <select name="attendance[<?php echo $student['id']; ?>]" class="status-select 
                                        <?php 
                                            $current_status = isset($attendance_data[$student['id']]) ? $attendance_data[$student['id']] : '';
                                            if($current_status == 'Present') echo 'status-present';
                                            elseif($current_status == 'Absent') echo 'status-absent';
                                            elseif($current_status == 'Late') echo 'status-late';
                                            elseif($current_status == 'Excused') echo 'status-excused';
                                        ?>">
                                        <option value="Present" <?php echo (isset($attendance_data[$student['id']]) && $attendance_data[$student['id']] == 'Present') ? 'selected' : ''; ?>>✅ Present</option>
                                        <option value="Absent" <?php echo (isset($attendance_data[$student['id']]) && $attendance_data[$student['id']] == 'Absent') ? 'selected' : ''; ?>>❌ Absent</option>
                                        <option value="Late" <?php echo (isset($attendance_data[$student['id']]) && $attendance_data[$student['id']] == 'Late') ? 'selected' : ''; ?>>⏰ Late</option>
                                        <option value="Excused" <?php echo (isset($attendance_data[$student['id']]) && $attendance_data[$student['id']] == 'Excused') ? 'selected' : ''; ?>>📝 Excused</option>
                                    </select>
                                 </div>
                                 </div>
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?php echo $student['id']; ?>]" placeholder="Optional remarks..." value="<?php echo isset($remarks_data[$student['id']]) ? htmlspecialchars($remarks_data[$student['id']]) : ''; ?>">
                                 </div>
                                 </div>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                     </div>
                </div>
                
                <button type="submit" class="save-btn">
                    <i class="fas fa-save"></i> Save Attendance
                </button>
            </form>
        </div>
        <?php elseif($selected_class && mysqli_num_rows($students) == 0): ?>
        <div class="card">
            <div class="empty-state">
                <i class="fas fa-user-graduate"></i>
                <h3>No Students Found</h3>
                <p>No students are enrolled in <?php echo $selected_class; ?> class yet.</p>
                <a href="admin/manage_students.php" style="display: inline-block; margin-top: 15px; color: #667eea;">Add Students →</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>© 2024 AttendEase - Attendance Management System | All Rights Reserved</p>
        </div>
    </div>
</body>
</html>