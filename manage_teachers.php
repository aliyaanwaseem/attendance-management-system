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

$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];

$message = '';
$message_type = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $check = mysqli_query($conn, "SELECT * FROM users WHERE id=$id AND role='teacher'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM users WHERE id=$id");
        $message = "Teacher deleted successfully!";
        $message_type = "success";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $role = 'teacher';
    
    if (isset($_POST['edit_id']) && $_POST['edit_id'] != '') {
        $id = $_POST['edit_id'];
        if (!empty($password)) {
            $query = "UPDATE users SET 
                      username='$username', 
                      password='$password', 
                      full_name='$full_name', 
                      email='$email', 
                      phone='$phone' 
                      WHERE id=$id";
        } else {
            $query = "UPDATE users SET 
                      username='$username', 
                      full_name='$full_name', 
                      email='$email', 
                      phone='$phone' 
                      WHERE id=$id";
        }
        $message = "Teacher updated successfully!";
        $message_type = "success";
    } else {
        $query = "INSERT INTO users (username, password, full_name, email, phone, role) 
                  VALUES ('$username', '$password', '$full_name', '$email', '$phone', '$role')";
        $message = "Teacher added successfully!";
        $message_type = "success";
    }
    mysqli_query($conn, $query);
    header("Location: manage_teachers.php?msg=" . urlencode($message) . "&type=" . $message_type);
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
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='teacher'");
$total_row = mysqli_fetch_assoc($total_query);
$total_teachers = $total_row['total'];
$total_pages = ceil($total_teachers / $limit);

// Get teachers with pagination
$teachers = mysqli_query($conn, "SELECT * FROM users WHERE role='teacher' ORDER BY full_name LIMIT $offset, $limit");

// Get statistics
$stats_query = mysqli_query($conn, "SELECT 
                                    COUNT(*) as total,
                                    SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teachers
                                    FROM users");
$stats = mysqli_fetch_assoc($stats_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Attendance Management System</title>
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
        
        /* Cards */
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
        
        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
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
        
        input {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        input:focus {
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
        
        /* Role Badge */
        .role-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            background: #667eea20;
            color: #667eea;
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
        
        /* Info Box */
        .info-box {
            background: #667eea10;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
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
            
            .form-row {
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
                    <li><a href="manage_subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
                    <li class="active"><a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
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
            <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Management</h1>
            <p>Add, edit, and manage all teacher accounts</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-info">
                    <h3><?php echo $stats['teachers']; ?></h3>
                    <p>Total Teachers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_teachers; ?></h3>
                    <p>Active Teachers</p>
                </div>
            </div>
        </div>
        
        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Teacher Form -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-plus"></i> Add New Teacher</h2>
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <form method="POST" action="" id="teacherForm">
                <input type="hidden" name="edit_id" id="edit_id" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="username" id="username" required placeholder="e.g., teacher2">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-circle"></i> Full Name</label>
                        <input type="text" name="full_name" id="full_name" required placeholder="Enter full name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" id="email" placeholder="teacher@example.com">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone</label>
                        <input type="text" name="phone" id="phone" placeholder="+92 XXX XXXXXXX">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" id="password" placeholder="Enter password">
                        <small style="font-size: 11px; color: #888;">Leave blank to keep current password when editing</small>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Teacher</button>
                    <button type="button" onclick="resetForm()" class="btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                </div>
            </form>
        </div>
        
        <!-- Teachers List -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Teachers</h2>
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <span>Showing <strong><?php echo mysqli_num_rows($teachers); ?></strong> teachers. Default password for new teachers is what you set.</span>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($teachers) > 0): ?>
                            <?php 
                            $serial = $offset + 1;
                            while($row = mysqli_fetch_assoc($teachers)): 
                            ?>
                            <tr>
                                <td><?php echo $serial++; ?> </div>
                                <td><strong><?php echo $row['username']; ?></strong></div>
                                <td><?php echo $row['full_name']; ?></div>
                                <td><?php echo $row['email'] ?: '-'; ?></div>
                                <td><?php echo $row['phone'] ?: '-'; ?></div>
                                <td><span class="role-badge"><i class="fas fa-chalkboard-teacher"></i> Teacher</span></div>
                                <td class="action-buttons">
                                    <button onclick="editTeacher(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['full_name']); ?>')" class="delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                 </div>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-chalkboard-teacher" style="font-size: 48px; opacity: 0.3;"></i>
                                    <p style="margin-top: 10px;">No teachers found. Add your first teacher!</p>
                                 </div>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
            <p>Are you sure you want to delete teacher <strong id="deleteTeacherName"></strong>?</p>
            <p style="font-size: 12px; color: #888; margin-top: 10px;">This action cannot be undone.</p>
            <div class="modal-buttons">
                <button onclick="closeModal()" class="btn-secondary">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn-primary" style="background: #e53e3e; text-decoration: none;">Delete</a>
            </div>
        </div>
    </div>
    
    <script>
        function editTeacher(teacher) {
            document.getElementById('edit_id').value = teacher.id;
            document.getElementById('username').value = teacher.username;
            document.getElementById('full_name').value = teacher.full_name;
            document.getElementById('email').value = teacher.email || '';
            document.getElementById('phone').value = teacher.phone || '';
            document.getElementById('password').placeholder = 'Leave blank to keep current password';
            document.getElementById('password').value = '';
            
            // Change form header
            document.querySelector('.card-header h2').innerHTML = '<i class="fas fa-edit"></i> Edit Teacher';
            
            // Scroll to form
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function resetForm() {
            document.getElementById('teacherForm').reset();
            document.getElementById('edit_id').value = '';
            document.getElementById('password').placeholder = 'Enter password';
            document.querySelector('.card-header h2').innerHTML = '<i class="fas fa-user-plus"></i> Add New Teacher';
        }
        
        let deleteUrl = '';
        
        function confirmDelete(id, teacherName) {
            deleteUrl = '?delete=' + id;
            document.getElementById('deleteTeacherName').innerText = teacherName;
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