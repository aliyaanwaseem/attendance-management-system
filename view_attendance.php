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

// Get filter values
$selected_class = isset($_GET['class']) ? $_GET['class'] : '';
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$selected_status = isset($_GET['status']) ? $_GET['status'] : '';

// Get unique classes for filter
$classes = mysqli_query($conn, "SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class");

// Build query based on filters
$query = "SELECT s.student_id, s.first_name, s.last_name, s.class, s.section, 
          a.date, a.status, a.remarks 
          FROM students s 
          LEFT JOIN attendance a ON s.id = a.student_id 
          WHERE 1=1";

if ($selected_class) {
    $query .= " AND s.class = '$selected_class'";
}
if ($selected_date) {
    $query .= " AND a.date = '$selected_date'";
}
if ($selected_status) {
    $query .= " AND a.status = '$selected_status'";
}

$query .= " ORDER BY a.date DESC, s.class, s.first_name";
$results = mysqli_query($conn, $query);

// Get statistics for reports
$stats_query = "SELECT 
                    COUNT(DISTINCT s.id) as total_students,
                    COUNT(a.id) as total_records,
                    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN a.status = 'Excused' THEN 1 ELSE 0 END) as excused_count
                FROM students s
                LEFT JOIN attendance a ON s.id = a.student_id";
                
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Attendance Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Top Navigation Bar */
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
        
        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        /* Page Header */
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .stat-icon i {
            font-size: 24px;
            color: #667eea;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #1a1a2e;
        }
        
        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Card Styles */
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
        
        /* Filter Form */
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        
        .filter-group label i {
            margin-right: 5px;
        }
        
        .filter-group select, .filter-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .filter-group select:focus, .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-primary, .btn-secondary {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        /* Chart Container */
        .chart-container {
            max-width: 400px;
            margin: 0 auto;
        }
        
        canvas {
            max-height: 300px;
        }
        
        /* Two Columns */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        /* Legend */
        .legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }
        
        /* Table Styles */
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
            font-size: 13px;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        /* Status Badges */
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
        
        .status-excused {
            background: #4299e120;
            color: #4299e1;
        }
        
        /* Empty State */
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
        
        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        
        .export-btn {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .export-pdf {
            background: #e53e3e;
            color: white;
        }
        
        .export-excel {
            background: #48bb78;
            color: white;
        }
        
        .export-print {
            background: #667eea;
            color: white;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Footer */
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
            
            .two-columns {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-buttons {
                flex-wrap: wrap;
            }
        }
        
        @media print {
            .top-nav, .filter-form, .export-buttons, .footer {
                display: none;
            }
            .main-container {
                padding: 0;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <div class="top-nav">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>AttendEase</span>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="mark_attendance.php"><i class="fas fa-check-circle"></i> Mark Attendance</a></li>
                <li class="active"><a href="view_attendance.php"><i class="fas fa-chart-line"></i> Reports</a></li>
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
            <h1><i class="fas fa-chart-line"></i> Attendance Reports</h1>
            <p>View and analyze attendance records with detailed filters</p>
        </div>
        
        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-number"><?php echo $stats['total_records']; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number" style="color: #48bb78;"><?php echo $stats['present_count']; ?></div>
                <div class="stat-label">Present</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-number" style="color: #e53e3e;"><?php echo $stats['absent_count']; ?></div>
                <div class="stat-label">Absent</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-number" style="color: #ed8936;"><?php echo $stats['late_count']; ?></div>
                <div class="stat-label">Late</div>
            </div>
        </div>
        
        <!-- Chart and Filters -->
        <div class="two-columns">
            <!-- Chart Card -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-pie"></i> Attendance Overview</h2>
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
                <div class="legend">
                    <div class="legend-item"><div class="legend-color" style="background: #48bb78;"></div><span>Present (<?php echo $stats['present_count']; ?>)</span></div>
                    <div class="legend-item"><div class="legend-color" style="background: #e53e3e;"></div><span>Absent (<?php echo $stats['absent_count']; ?>)</span></div>
                    <div class="legend-item"><div class="legend-color" style="background: #ed8936;"></div><span>Late (<?php echo $stats['late_count']; ?>)</span></div>
                    <div class="legend-item"><div class="legend-color" style="background: #4299e1;"></div><span>Excused (<?php echo $stats['excused_count']; ?>)</span></div>
                </div>
            </div>
            
            <!-- Filter Card -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-filter"></i> Filter Reports</h2>
                    <i class="fas fa-sliders-h"></i>
                </div>
                <form method="GET" action="">
                    <div class="filter-form">
                        <div class="filter-group">
                            <label><i class="fas fa-graduation-cap"></i> Class</label>
                            <select name="class">
                                <option value="">All Classes</option>
                                <?php while($class = mysqli_fetch_assoc($classes)): ?>
                                    <option value="<?php echo $class['class']; ?>" <?php echo ($selected_class == $class['class']) ? 'selected' : ''; ?>>
                                        <?php echo $class['class']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> Date</label>
                            <input type="date" name="date" value="<?php echo $selected_date; ?>">
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-flag-checkered"></i> Status</label>
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="Present" <?php echo ($selected_status == 'Present') ? 'selected' : ''; ?>>Present</option>
                                <option value="Absent" <?php echo ($selected_status == 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                <option value="Late" <?php echo ($selected_status == 'Late') ? 'selected' : ''; ?>>Late</option>
                                <option value="Excused" <?php echo ($selected_status == 'Excused') ? 'selected' : ''; ?>>Excused</option>
                            </select>
                        </div>
                        <div class="filter-buttons">
                            <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Apply</button>
                            <a href="view_attendance.php" class="btn-secondary"><i class="fas fa-undo"></i> Reset</a>
                        </div>
                    </div>
                </form>
                
                <div class="export-buttons" style="margin-top: 20px;">
                    <button onclick="window.print()" class="export-btn export-print"><i class="fas fa-print"></i> Print Report</button>
                    <button onclick="exportToExcel()" class="export-btn export-excel"><i class="fas fa-file-excel"></i> Export Excel</button>
                </div>
            </div>
        </div>
        
        <!-- Results Table -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-table"></i> Attendance Records</h2>
                <span style="font-size: 13px; color: #666;">
                    <i class="fas fa-list"></i> <?php echo mysqli_num_rows($results); ?> records found
                </span>
            </div>
            
            <?php if(mysqli_num_rows($results) > 0): ?>
            <div class="table-container">
                <table class="data-table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($results)): ?>
                        <tr>
                            <td><?php echo $row['student_id']; ?></td>
                            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></div>
                            <td><?php echo $row['class'] . ($row['section'] ? ' - ' . $row['section'] : ''); ?></div>
                            <td><?php echo $row['date'] ? date('M d, Y', strtotime($row['date'])) : '-'; ?></div>
                            <td>
                                <?php if($row['status']): ?>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">Not marked</span>
                                <?php endif; ?>
                            </div>
                            <td><?php echo $row['remarks'] ?: '-'; ?></div>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chart-line"></i>
                <h3>No Records Found</h3>
                <p>Try adjusting your filters or mark some attendance first.</p>
                <a href="mark_attendance.php" style="display: inline-block; margin-top: 15px; color: #667eea;">Mark Attendance →</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>© 2024 AttendEase - Attendance Management System | All Rights Reserved</p>
        </div>
    </div>
    
    <script>
        // Initialize Chart
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Excused'],
                datasets: [{
                    data: [
                        <?php echo $stats['present_count']; ?>,
                        <?php echo $stats['absent_count']; ?>,
                        <?php echo $stats['late_count']; ?>,
                        <?php echo $stats['excused_count']; ?>
                    ],
                    backgroundColor: ['#48bb78', '#e53e3e', '#ed8936', '#4299e1'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Export to Excel function
        function exportToExcel() {
            let table = document.getElementById('attendanceTable');
            let html = table.outerHTML;
            let url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            let downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.download = 'attendance_report.xls';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>