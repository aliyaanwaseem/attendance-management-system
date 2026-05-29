<?php
session_start();
include '../config/database.php';

// Check if logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['full_name'];

$message = '';
$message_type = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $check = mysqli_query($conn, "SELECT * FROM subjects WHERE id=$id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM subjects WHERE id=$id");
        $message = "Subject deleted successfully!";
        $message_type = "success";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_code = mysqli_real_escape_string($conn, $_POST['subject_code']);
    $subject_name = mysqli_real_escape_string($conn, $_POST['subject_name']);
    $class = mysqli_real_escape_string($conn, $_POST['class']);
    $credits = mysqli_real_escape_string($conn, $_POST['credits']);
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
    
    if (isset($_POST['edit_id']) && $_POST['edit_id'] != '') {
        $id = $_POST['edit_id'];
        $query = "UPDATE subjects SET 
                  subject_code='$subject_code', 
                  subject_name='$subject_name', 
                  class='$class', 
                  credits='$credits'";
        
        // Only add description if column exists (check if we added it)
        $check_column = mysqli_query($conn, "SHOW COLUMNS FROM subjects LIKE 'description'");
        if (mysqli_num_rows($check_column) > 0) {
            $query .= ", description='$description'";
        }
        
        $query .= " WHERE id=$id";
        $message = "Subject updated successfully!";
        $message_type = "success";
    } else {
        // Check if description column exists
        $check_column = mysqli_query($conn, "SHOW COLUMNS FROM subjects LIKE 'description'");
        if (mysqli_num_rows($check_column) > 0) {
            $query = "INSERT INTO subjects (subject_code, subject_name, class, credits, description) 
                      VALUES ('$subject_code', '$subject_name', '$class', '$credits', '$description')";
        } else {
            $query = "INSERT INTO subjects (subject_code, subject_name, class, credits) 
                      VALUES ('$subject_code', '$subject_name', '$class', '$credits')";
        }
        $message = "Subject added successfully!";
        $message_type = "success";
    }
    mysqli_query($conn, $query);
    header("Location: manage_subjects.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// Get message from URL
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'success';
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM subjects");
$total_row = mysqli_fetch_assoc($total_query);
$total_subjects = $total_row['total'];
$total_pages = ceil($total_subjects / $limit);

// Get subjects with pagination
$subjects = mysqli_query($conn, "SELECT * FROM subjects ORDER BY class, subject_name LIMIT $offset, $limit");

// Get statistics
$stats_query = mysqli_query($conn, "SELECT 
                                    COUNT(*) as total,
                                    SUM(credits) as total_credits,
                                    COUNT(DISTINCT class) as classes,
                                    AVG(credits) as avg_credits
                                    FROM subjects");
$stats = mysqli_fetch_assoc($stats_query);

// Get class distribution
$class_distribution = mysqli_query($conn, "SELECT class, COUNT(*) as count FROM subjects GROUP BY class ORDER BY class");

// Check if description column exists
$has_description = mysqli_query($conn, "SHOW COLUMNS FROM subjects LIKE 'description'");
$description_exists = mysqli_num_rows($has_description) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - Attendance Management System</title>
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
        
        /* Stats Grid */
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
        
        /* Alert Messages */
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
        
        /* Two Columns */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        /* Cards */
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
        
        .card-header h2 {
            font-size: 20px;
            color: #1a1a2e;
        }
        
        .card-header i {
            color: #667eea;
            font-size: 24px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        label i {
            margin-right: 8px;
            color: #667eea;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-primary, .btn-secondary {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
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
        
        /* Class Distribution List */
        .class-list {
            list-style: none;
        }
        
        .class-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
        }
        
        .class-item:last-child {
            border-bottom: none;
        }
        
        .class-name {
            font-weight: 500;
            color: #333;
        }
        
        .class-count {
            background: #667eea20;
            padding: 4px 12px;
            border-radius: 20px;
            color: #667eea;
            font-size: 13px;
            font-weight: 600;
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
        
        /* Credit Badge */
        .credit-badge {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #667eea;
            display: inline-block;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .edit-btn, .delete-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .edit-btn {
            background: #4299e120;
            color: #4299e1;
        }
        
        .edit-btn:hover {
            background: #4299e1;
            color: white;
        }
        
        .delete-btn {
            background: #e53e3e20;
            color: #e53e3e;
        }
        
        .delete-btn:hover {
            background: #e53e3e;
            color: white;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .pagination a {
            background: #f8f9fa;
            color: #667eea;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            padding: 30px;
            text-align: center;
        }
        
        .modal-content h3 {
            margin-bottom: 20px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .data-table th, .data-table td {
                padding: 10px;
                font-size: 11px;
            }
            
            .action-buttons {
                flex-direction: column;
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
                <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="../mark_attendance.php"><i class="fas fa-check-circle"></i> Mark Attendance</a></li>
                <li><a href="../view_attendance.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li><a href="../search.php"><i class="fas fa-search"></i> Search</a></li>
                <?php if($user_role == 'admin'): ?>
                    <li><a href="manage_students.php"><i class="fas fa-users"></i> Students</a></li>
                    <li class="active"><a href="manage_subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
                    <li><a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
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
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
    
    <div class="main-container">
        <div class="page-header">
            <h1><i class="fas fa-book"></i> Subject Management</h1>
            <p>Add, edit, and manage all academic subjects</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-book"></i></div>
                <div class="stat-info">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Subjects</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_credits']; ?></h3>
                    <p>Total Credits</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['avg_credits'], 1); ?></h3>
                    <p>Avg Credits/Subject</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-school"></i></div>
                <div class="stat-info">
                    <h3><?php echo $stats['classes']; ?></h3>
                    <p>Classes</p>
                </div>
            </div>
        </div>
        
        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Two Column Layout -->
        <div class="two-columns">
            <!-- Add/Edit Subject Form -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Add New Subject</h2>
                    <i class="fas fa-book-open"></i>
                </div>
                <form method="POST" action="" id="subjectForm">
                    <input type="hidden" name="edit_id" id="edit_id" value="">
                    <div class="form-group">
                        <label><i class="fas fa-barcode"></i> Subject Code</label>
                        <input type="text" name="subject_code" id="subject_code" required placeholder="e.g., CS101, MATH201">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Subject Name</label>
                        <input type="text" name="subject_name" id="subject_name" required placeholder="e.g., Computer Science, Mathematics">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Class</label>
                        <input type="text" name="class" id="class" required placeholder="e.g., Grade 10, Grade 11">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-star"></i> Credits</label>
                        <input type="number" name="credits" id="credits" required min="1" max="6" value="3">
                    </div>
                    <?php if($description_exists): ?>
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description (Optional)</label>
                        <textarea name="description" id="description" placeholder="Brief description of the subject..."></textarea>
                    </div>
                    <?php endif; ?>
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Subject</button>
                        <button type="button" onclick="resetForm()" class="btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </form>
            </div>
            
            <!-- Class Distribution -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-pie"></i> Class Distribution</h2>
                    <i class="fas fa-chart-bar"></i>
                </div>
                <ul class="class-list">
                    <?php while($class = mysqli_fetch_assoc($class_distribution)): ?>
                    <li class="class-item">
                        <span class="class-name"><i class="fas fa-graduation-cap"></i> <?php echo $class['class']; ?></span>
                        <span class="class-count"><?php echo $class['count']; ?> subject(s)</span>
                    </li>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($class_distribution) == 0): ?>
                    <li class="class-item">
                        <span class="class-name">No subjects added yet</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Subjects List -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Subjects</h2>
                <i class="fas fa-book"></i>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Class</th>
                            <th>Credits</th>
                            <?php if($description_exists): ?>
                            <th>Description</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($subjects) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($subjects)): ?>
                            <tr>
                                <td><strong><?php echo $row['subject_code']; ?></strong></td>
                                <td><?php echo $row['subject_name']; ?></td>
                                <td><?php echo $row['class']; ?></td>
                                <td><span class="credit-badge"><i class="fas fa-star"></i> <?php echo $row['credits']; ?> credits</span></td>
                                <?php if($description_exists): ?>
                                <td><?php echo isset($row['description']) && $row['description'] ? $row['description'] : '-'; ?></td>
                                <?php endif; ?>
                                <td class="action-buttons">
                                    <button onclick="editSubject(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['subject_name']); ?>')" class="delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                 </div>
                            </table>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $description_exists ? '6' : '5'; ?>" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-book" style="font-size: 48px; opacity: 0.3;"></i>
                                    <p style="margin-top: 10px;">No subjects found. Add your first subject!</p>
                                 </div>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                 </div>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>"><i class="fas fa-chevron-left"></i> Previous</a>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>">Next <i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>© 2024 AttendEase - Attendance Management System | All Rights Reserved</p>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #e53e3e; margin-bottom: 20px;"></i>
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete <strong id="deleteSubjectName"></strong>?</p>
            <p style="font-size: 12px; color: #888; margin-top: 10px;">This action cannot be undone.</p>
            <div class="modal-buttons">
                <button onclick="closeModal()" class="btn-secondary">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn-primary" style="background: #e53e3e; text-decoration: none;">Delete</a>
            </div>
        </div>
    </div>
    
    <script>
        function editSubject(subject) {
            document.getElementById('edit_id').value = subject.id;
            document.getElementById('subject_code').value = subject.subject_code;
            document.getElementById('subject_name').value = subject.subject_name;
            document.getElementById('class').value = subject.class;
            document.getElementById('credits').value = subject.credits;
            
            <?php if($description_exists): ?>
            document.getElementById('description').value = subject.description || '';
            <?php endif; ?>
            
            // Change form header
            document.querySelector('.card-header h2').innerHTML = '<i class="fas fa-edit"></i> Edit Subject';
            
            // Scroll to form
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function resetForm() {
            document.getElementById('subjectForm').reset();
            document.getElementById('edit_id').value = '';
            document.querySelector('.card-header h2').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Subject';
        }
        
        let deleteUrl = '';
        
        function confirmDelete(id, subjectName) {
            deleteUrl = '?delete=' + id;
            document.getElementById('deleteSubjectName').innerText = subjectName;
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('confirmDeleteBtn').href = deleteUrl;
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>