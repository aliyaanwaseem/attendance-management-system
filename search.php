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

$search_results = [];
$search_term = '';
$search_type = isset($_GET['type']) ? $_GET['type'] : 'students';
$message = '';

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    
    if ($search_type == 'students') {
        $query = "SELECT * FROM students WHERE 
                  student_id LIKE '%$search_term%' OR 
                  first_name LIKE '%$search_term%' OR 
                  last_name LIKE '%$search_term%' OR 
                  email LIKE '%$search_term%' OR 
                  phone LIKE '%$search_term%' OR 
                  class LIKE '%$search_term%'
                  ORDER BY class, first_name";
        $search_results = mysqli_query($conn, $query);
        $message = "Found " . mysqli_num_rows($search_results) . " student(s) matching '" . htmlspecialchars($search_term) . "'";
    } 
    elseif ($search_type == 'attendance') {
        $query = "SELECT a.*, s.first_name, s.last_name, s.student_id, s.class 
                  FROM attendance a 
                  JOIN students s ON a.student_id = s.id 
                  WHERE s.first_name LIKE '%$search_term%' 
                  OR s.last_name LIKE '%$search_term%' 
                  OR s.student_id LIKE '%$search_term%'
                  ORDER BY a.date DESC";
        $search_results = mysqli_query($conn, $query);
        $message = "Found " . mysqli_num_rows($search_results) . " attendance record(s) matching '" . htmlspecialchars($search_term) . "'";
    }
    elseif ($search_type == 'subjects') {
        $query = "SELECT * FROM subjects WHERE 
                  subject_code LIKE '%$search_term%' OR 
                  subject_name LIKE '%$search_term%' OR 
                  class LIKE '%$search_term%'
                  ORDER BY class, subject_name";
        $search_results = mysqli_query($conn, $query);
        $message = "Found " . mysqli_num_rows($search_results) . " subject(s) matching '" . htmlspecialchars($search_term) . "'";
    }
}

// Get recent searches or popular items for dashboard
$recent_students = mysqli_query($conn, "SELECT * FROM students ORDER BY created_at DESC LIMIT 5");
$popular_subjects = mysqli_query($conn, "SELECT * FROM subjects ORDER BY id LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Attendance Management System</title>
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
        
        /* Search Box */
        .search-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .search-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .search-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .search-tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .search-tab.active {
            color: #667eea;
        }
        
        .search-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #667eea;
        }
        
        .search-tab:hover {
            color: #667eea;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .search-input-wrapper {
            flex: 1;
            position: relative;
        }
        
        .search-input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .search-input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-btn {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .search-info {
            text-align: center;
            color: #666;
            font-size: 13px;
        }
        
        /* Alert Message */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-info {
            background: #4299e120;
            color: #4299e1;
            border-left: 4px solid #4299e1;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        /* Results Card */
        .results-card {
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
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        /* Quick Links Card */
        .quick-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .quick-card h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #1a1a2e;
        }
        
        .quick-card h3 i {
            color: #667eea;
            margin-right: 10px;
        }
        
        .quick-list {
            list-style: none;
        }
        
        .quick-list li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .quick-list li:last-child {
            border-bottom: none;
        }
        
        .quick-list .item-name {
            font-weight: 500;
            color: #333;
        }
        
        .quick-list .item-class {
            font-size: 12px;
            color: #888;
        }
        
        .quick-link {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
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
            
            .search-box {
                flex-direction: column;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table th, .data-table td {
                padding: 10px;
                font-size: 11px;
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
                <li><a href="view_attendance.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li class="active"><a href="search.php"><i class="fas fa-search"></i> Search</a></li>
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
            <h1><i class="fas fa-search"></i> Search & Find</h1>
            <p>Quickly find students, attendance records, and subjects</p>
        </div>
        
        <!-- Search Card -->
        <div class="search-card">
            <div class="search-container">
                <div class="search-tabs">
                    <button class="search-tab <?php echo $search_type == 'students' ? 'active' : ''; ?>" data-type="students">
                        <i class="fas fa-user-graduate"></i> Students
                    </button>
                    <button class="search-tab <?php echo $search_type == 'attendance' ? 'active' : ''; ?>" data-type="attendance">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </button>
                    <button class="search-tab <?php echo $search_type == 'subjects' ? 'active' : ''; ?>" data-type="subjects">
                        <i class="fas fa-book"></i> Subjects
                    </button>
                </div>
                
                <form method="GET" action="" id="searchForm">
                    <input type="hidden" name="type" id="searchType" value="<?php echo $search_type; ?>">
                    <div class="search-box">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="search-input" 
                                   placeholder="Search by name, ID, class, subject..." 
                                   value="<?php echo htmlspecialchars($search_term); ?>" autocomplete="off">
                        </div>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="search-info">
                        <i class="fas fa-info-circle"></i> 
                        <?php if($search_type == 'students'): ?>
                            Search by Student ID, Name, Email, Phone, or Class
                        <?php elseif($search_type == 'attendance'): ?>
                            Search by Student Name, ID
                        <?php else: ?>
                            Search by Subject Code, Name, or Class
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Search Results -->
        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
        <div class="results-card">
            <div class="card-header">
                <h2><i class="fas fa-search"></i> Search Results</h2>
                <i class="fas fa-list"></i>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($search_type == 'students' && mysqli_num_rows($search_results) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($search_results)): ?>
                            <tr>
                                <td><?php echo $row['student_id']; ?></td>
                                <td><strong><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></strong></td>
                                <td><?php echo $row['class']; ?></td>
                                <td><?php echo $row['section'] ?: '-'; ?></td>
                                <td><?php echo $row['email'] ?: '-'; ?></div>
                                <td><?php echo $row['phone'] ?: '-'; ?></div>
                                <td>
                                    <a href="mark_attendance.php?class=<?php echo urlencode($row['class']); ?>" style="color: #667eea;">
                                        <i class="fas fa-check-circle"></i> Mark
                                    </a>
                                 </div>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                     </div>
                </div>
            <?php elseif($search_type == 'attendance' && mysqli_num_rows($search_results) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($search_results)): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></div>
                                <td><?php echo $row['student_id']; ?></div>
                                <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></div>
                                <td><?php echo $row['class']; ?></div>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                 </div>
                                <td><?php echo $row['remarks'] ?: '-'; ?></div>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                     </div>
                </div>
            <?php elseif($search_type == 'subjects' && mysqli_num_rows($search_results) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Class</th>
                                <th>Credits</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($search_results)): ?>
                            <tr>
                                <td><?php echo $row['subject_code']; ?></div>
                                <td><strong><?php echo $row['subject_name']; ?></strong></div>
                                <td><?php echo $row['class']; ?></div>
                                <td><?php echo $row['credits']; ?></div>
                                <td>
                                    <a href="mark_attendance.php?class=<?php echo urlencode($row['class']); ?>" style="color: #667eea;">
                                        <i class="fas fa-check-circle"></i> Mark
                                    </a>
                                 </div>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                     </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Results Found</h3>
                    <p>Try different search terms or adjust your filters.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Quick Access Dashboard (shown when no search) -->
        <?php if(!isset($_GET['search']) || empty($_GET['search'])): ?>
        <div class="dashboard-grid">
            <!-- Recent Students -->
            <div class="quick-card">
                <h3><i class="fas fa-user-plus"></i> Recently Added Students</h3>
                <ul class="quick-list">
                    <?php while($student = mysqli_fetch_assoc($recent_students)): ?>
                    <li>
                        <div>
                            <div class="item-name"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></div>
                            <div class="item-class"><?php echo $student['student_id']; ?> | <?php echo $student['class']; ?></div>
                        </div>
                        <a href="mark_attendance.php?class=<?php echo urlencode($student['class']); ?>" class="quick-link">
                            Mark <i class="fas fa-arrow-right"></i>
                        </a>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            
            <!-- Popular Subjects -->
            <div class="quick-card">
                <h3><i class="fas fa-star"></i> Available Subjects</h3>
                <ul class="quick-list">
                    <?php while($subject = mysqli_fetch_assoc($popular_subjects)): ?>
                    <li>
                        <div>
                            <div class="item-name"><?php echo $subject['subject_name']; ?></div>
                            <div class="item-class"><?php echo $subject['subject_code']; ?> | <?php echo $subject['class']; ?></div>
                        </div>
                        <a href="mark_attendance.php?class=<?php echo urlencode($subject['class']); ?>" class="quick-link">
                            Mark <i class="fas fa-arrow-right"></i>
                        </a>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            
            <!-- Quick Tips -->
            <div class="quick-card">
                <h3><i class="fas fa-lightbulb"></i> Search Tips</h3>
                <ul class="quick-list">
                    <li>
                        <div>
                            <div class="item-name"><i class="fas fa-user"></i> Search by Student Name</div>
                            <div class="item-class">Example: "John" or "Smith"</div>
                        </div>
                    </li>
                    <li>
                        <div>
                            <div class="item-name"><i class="fas fa-id-card"></i> Search by Student ID</div>
                            <div class="item-class">Example: "STU001"</div>
                        </div>
                    </li>
                    <li>
                        <div>
                            <div class="item-name"><i class="fas fa-book"></i> Search by Subject</div>
                            <div class="item-class">Example: "Mathematics" or "CS101"</div>
                        </div>
                    </li>
                    <li>
                        <div>
                            <div class="item-name"><i class="fas fa-calendar"></i> Search by Class</div>
                            <div class="item-class">Example: "Grade 10"</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>© 2024 AttendEase - Attendance Management System | All Rights Reserved</p>
        </div>
    </div>
    
    <script>
        // Tab switching functionality
        document.querySelectorAll('.search-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const type = this.dataset.type;
                document.getElementById('searchType').value = type;
                
                // Update active class
                document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Update search info text
                const searchInfo = document.querySelector('.search-info');
                if (type === 'students') {
                    searchInfo.innerHTML = '<i class="fas fa-info-circle"></i> Search by Student ID, Name, Email, Phone, or Class';
                } else if (type === 'attendance') {
                    searchInfo.innerHTML = '<i class="fas fa-info-circle"></i> Search by Student Name or ID';
                } else {
                    searchInfo.innerHTML = '<i class="fas fa-info-circle"></i> Search by Subject Code, Name, or Class';
                }
            });
        });
        
        // Auto-submit on enter key
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('searchForm').submit();
            }
        });
    </script>
</body>
</html>