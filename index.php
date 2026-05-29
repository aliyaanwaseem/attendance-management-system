<?php
session_start();
include 'config/database.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Query to check username, password, AND role
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password' AND role = '$role'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username, password, or role";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Background Icons Container */
        .bg-icons {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        /* Individual Icon Styles */
        .bg-icon {
            position: absolute;
            opacity: 0.12;
            animation: float 20s infinite ease-in-out;
        }
        
        /* Books */
        .book-1 { top: 8%; left: 3%; font-size: 85px; animation-delay: 0s; }
        .book-2 { top: 72%; left: 12%; font-size: 55px; animation-delay: 2s; }
        .book-3 { top: 35%; right: 5%; font-size: 75px; animation-delay: 4s; }
        .book-4 { bottom: 12%; right: 18%; font-size: 60px; animation-delay: 1s; }
        .book-5 { top: 55%; left: 20%; font-size: 45px; animation-delay: 3.5s; }
        
        /* Brains */
        .brain-1 { top: 18%; right: 12%; font-size: 70px; animation-delay: 3s; }
        .brain-2 { bottom: 28%; left: 8%; font-size: 60px; animation-delay: 5s; }
        .brain-3 { top: 62%; right: 22%; font-size: 50px; animation-delay: 1.5s; }
        .brain-4 { top: 45%; left: 35%; font-size: 40px; animation-delay: 4s; }
        
        /* Formulas */
        .formula-1 { top: 12%; left: 18%; font-size: 38px; animation-delay: 2.5s; font-family: monospace; }
        .formula-2 { top: 48%; left: 4%; font-size: 42px; animation-delay: 4.5s; font-family: monospace; }
        .formula-3 { bottom: 22%; right: 8%; font-size: 40px; animation-delay: 3.5s; font-family: monospace; }
        .formula-4 { top: 82%; left: 28%; font-size: 35px; animation-delay: 1s; font-family: monospace; }
        .formula-5 { top: 28%; right: 32%; font-size: 48px; animation-delay: 5.5s; font-family: monospace; }
        .formula-6 { bottom: 55%; right: 45%; font-size: 36px; animation-delay: 2s; font-family: monospace; }
        
        /* Additional educational icons */
        .atom-1 { top: 78%; right: 35%; font-size: 55px; animation-delay: 2.2s; }
        .pencil-1 { top: 88%; left: 40%; font-size: 48px; animation-delay: 3.2s; }
        .graduation-1 { top: 5%; right: 28%; font-size: 65px; animation-delay: 4.2s; }
        .microscope-1 { bottom: 15%; left: 45%; font-size: 52px; animation-delay: 1.8s; }
        
        /* Floating Animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            25% {
                transform: translateY(-25px) rotate(3deg);
            }
            50% {
                transform: translateY(15px) rotate(-3deg);
            }
            75% {
                transform: translateY(-10px) rotate(2deg);
            }
        }
        
        .login-container {
            position: relative;
            z-index: 10;
            max-width: 400px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 40px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }
        
        .form-group:hover label {
            color: #667eea;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            transition: all 0.3s ease;
        }
        
        input:hover, select:hover {
            border-color: #667eea;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: scale(1.02);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: #fee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border-left: 3px solid #c62828;
            animation: shake 0.3s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 11px;
            color: #aaa;
            transition: color 0.3s ease;
        }
        
        .footer:hover {
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Background Educational Icons -->
    <div class="bg-icons">
        <!-- Books -->
        <div class="bg-icon book-1">📚</div>
        <div class="bg-icon book-2">📖</div>
        <div class="bg-icon book-3">📕</div>
        <div class="bg-icon book-4">📘</div>
        <div class="bg-icon book-5">📙</div>
        
        <!-- Brains -->
        <div class="bg-icon brain-1">🧠</div>
        <div class="bg-icon brain-2">🧠</div>
        <div class="bg-icon brain-3">🧠</div>
        <div class="bg-icon brain-4">🧠</div>
        
        <!-- Formulas -->
        <div class="bg-icon formula-1">E = mc²</div>
        <div class="bg-icon formula-2">πr²</div>
        <div class="bg-icon formula-3">a² + b² = c²</div>
        <div class="bg-icon formula-4">F = ma</div>
        <div class="bg-icon formula-5">PV = nRT</div>
        <div class="bg-icon formula-6">∫ f(x)dx</div>
        
        <!-- Additional educational symbols -->
        <div class="bg-icon atom-1">⚛️</div>
        <div class="bg-icon pencil-1">✏️</div>
        <div class="bg-icon graduation-1">🎓</div>
        <div class="bg-icon microscope-1">🔬</div>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <h1>Attendance Management System</h1>
            <p>Sign in to continue</p>
        </div>
        
        <?php if($error): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="off" placeholder="Enter username">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter password">
            </div>
            
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="">Select role</option>
                    <option value="admin">Administrator</option>
                    <option value="teacher">Teacher</option>
                </select>
            </div>
            
            <button type="submit">Sign In</button>
        </form>
        
        <div class="footer">
            © 2024 Attendance Management System
        </div>
    </div>
</body>
</html>