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

// Get statistics
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE status='active'"))['count'];
$total_subjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM subjects"))['count'];
$total_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='teacher'"))['count'];
$today_attendance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE date=CURDATE()"))['count'];
$present_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE date=CURDATE() AND status='Present'"))['count'];
$absent_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE date=CURDATE() AND status='Absent'"))['count'];
$late_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE date=CURDATE() AND status='Late'"))['count'];

// Get recent activities
$recent_attendance = mysqli_query($conn, "SELECT a.*, s.first_name, s.last_name, s.student_id, s.class 
                                       FROM attendance a 
                                       JOIN students s ON a.student_id = s.id 
                                       ORDER BY a.date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Attendance Management System</title>
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
            overflow-x: hidden;
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
            font-size: 18px;
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
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
        }
        
        .welcome-section h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            opacity: 0.9;
        }
        
        .date-time {
            position: absolute;
            right: 30px;
            top: 30px;
            text-align: right;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .stat-info h3 {
            font-size: 28px;
            font-weight: bold;
            color: #1a1a2e;
        }
        
        .stat-info p {
            color: #666;
            font-size: 13px;
        }
        
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .card-header h3 {
            font-size: 18px;
            color: #1a1a2e;
        }
        
        .card-header i {
            color: #667eea;
            font-size: 22px;
        }
        
        .view-all {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
        }
        
        .attendance-summary {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-count {
            font-size: 32px;
            font-weight: bold;
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .present { color: #48bb78; }
        .absent { color: #e53e3e; }
        .late { color: #ed8936; }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 12px 0;
            color: #666;
            font-weight: 600;
            font-size: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .data-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
            font-size: 13px;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-present {
            background: #48bb7820;
            color: #48bb78;
        }
        
        .status-absent {
            background: #e53e3e20;
            color: #e53e3e;
        }
        
        .status-late {
            background: #ed893620;
            color: #ed8936;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .action-btn {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }
        
        .action-btn:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            transform: translateY(-3px);
        }
        
        .action-btn:hover i,
        .action-btn:hover span {
            color: white;
        }
        
        .action-btn i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
            display: block;
        }
        
        .action-btn span {
            font-size: 13px;
            color: #333;
            font-weight: 500;
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
            .nav-container {
                flex-direction: column;
            }
            .nav-menu {
                justify-content: center;
            }
            .user-info {
                margin-bottom: 15px;
            }
            .two-columns {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .welcome-section h1 {
                font-size: 20px;
            }
            .date-time {
                position: relative;
                right: auto;
                top: auto;
                margin-top: 10px;
                text-align: left;
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
                <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="mark_attendance.php"><i class="fas fa-check-circle"></i> Mark Attendance</a></li>
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
        <div class="welcome-section">
            <div class="date-time">
                <i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?><br>
                <i class="far fa-clock"></i> <?php echo date('h:i A'); ?>
            </div>
            <h1>Welcome back, <?php echo $user_name; ?>!</h1>
            <p>Here's what's happening with your attendance system today.</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-book"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_subjects; ?></h3>
                    <p>Total Subjects</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_teachers; ?></h3>
                    <p>Total Teachers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo $today_attendance; ?></h3>
                    <p>Today's Records</p>
                </div>
            </div>
        </div>
        
        <div class="two-columns">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Today's Attendance Summary</h3>
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="attendance-summary">
                    <div class="summary-item">
                        <div class="summary-count present"><?php echo $present_today; ?></div>
                        <div class="summary-label">Present</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-count absent"><?php echo $absent_today; ?></div>
                        <div class="summary-label">Absent</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-count late"><?php echo $late_today; ?></div>
                        <div class="summary-label">Late</div>
                    </div>
                </div>
                <div style="height: 100px; background: linear-gradient(90deg, #48bb78 <?php echo ($total_students > 0 ? ($present_today/$total_students)*100 : 0); ?>%, #e53e3e <?php echo ($total_students > 0 ? ($absent_today/$total_students)*100 : 0); ?>%, #ed8936 <?php echo ($total_students > 0 ? ($late_today/$total_students)*100 : 0); ?>%); border-radius: 10px;"></div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <i class="fas fa-cog"></i>
                </div>
                <div class="quick-actions">
                    <a href="mark_attendance.php" class="action-btn">
                        <i class="fas fa-check-circle"></i>
                        <span>Mark Attendance</span>
                    </a>
                    <a href="view_attendance.php" class="action-btn">
                        <i class="fas fa-chart-line"></i>
                        <span>View Reports</span>
                    </a>
                    <a href="search.php" class="action-btn">
                        <i class="fas fa-search"></i>
                        <span>Search Students</span>
                    </a>
                    <?php if($user_role == 'admin'): ?>
                    <a href="admin/manage_students.php" class="action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Student</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Recent Attendance Activity</h3>
                <a href="view_attendance.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Class</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($recent_attendance)): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                        <td><?php echo $row['student_id']; ?></td>
                        <td><?php echo $row['class']; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($recent_attendance) == 0): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No attendance records yet</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>© 2024 AttendEase - Attendance Management System | All Rights Reserved</p>
        </div>
    </div>
</body>
</html>