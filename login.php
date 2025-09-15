<?php
// Start session for authentication
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "popcorn_paradise";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Create users table if it doesn't exist
$createUsersTable = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    user_type ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createUsersTable)) {
    die("Error creating users table: " . $conn->error);
}

// Create admin_users table if it doesn't exist
$createAdminTable = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createAdminTable)) {
    die("Error creating admin table: " . $conn->error);
}

// Check if admin user exists, if not create with hashed password
function initializeAdminUser($conn) {
    $checkAdmin = $conn->query("SELECT * FROM admin_users WHERE username = 'admin'");
    
    if ($checkAdmin->num_rows === 0) {
        $hashedPassword = hashPassword('admin123');
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashedPassword);
        
        $username = 'admin';
        $stmt->execute();
    }
}

// Initialize admin user
initializeAdminUser($conn);

// Handle registration
$registration_error = "";
$registration_success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name)) {
        $registration_error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $registration_error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $registration_error = "Password must be at least 8 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = "Invalid email format";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $registration_error = "Username or email already exists";
        } else {
            // Hash password and insert user
            $hashedPassword = hashPassword($password);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashedPassword, $first_name, $last_name);
            
            if ($stmt->execute()) {
                $registration_success = "Registration successful! You can now login.";
            } else {
                $registration_error = "Error creating account: " . $conn->error;
            }
        }
    }
}

// Handle login
$login_error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $login_error = "Username and password are required";
    } else {
        // Check if admin login
        if ($user_type === 'admin') {
            $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (verifyPassword($password, $user['password_hash'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_id'] = $user['id'];
                    header("Location: admin.php");
                    exit;
                } else {
                    $login_error = "Invalid username or password";
                }
            } else {
                $login_error = "Invalid username or password";
            }
        } else {
            // Regular user login
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (verifyPassword($password, $user['password_hash'])) {
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    header("Location: index.php");
                    exit;
                } else {
                    $login_error = "Invalid username or password";
                }
            } else {
                $login_error = "Invalid username or password";
            }
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Popcorn Paradise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f9f5e9 0%, #fff5e1 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            width: 100%;
        }
        
        header {
            background: linear-gradient(135deg, #ff9a3c 0%, #ff6f61 100%);
            color: white;
            padding: 20px 0;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .logo i {
            font-size: 2.5rem;
            color: #ffef3c;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .logo h1 {
            font-size: 2.2rem;
            font-weight: 800;
        }
        
        .tagline {
            text-align: center;
            font-style: italic;
            margin-top: 5px;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .auth-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: white;
            border-radius: 0 0 15px 15px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
            }
        }
        
        .login-section, .register-section {
            padding: 30px;
        }
        
        .login-section {
            background: #f8f9fa;
        }
        
        .register-section {
            background: white;
        }
        
        .section-title {
            color: #ff6f61;
            border-bottom: 2px dashed #ff9a3c;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .user-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .user-type-btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            background: #e9ecef;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .user-type-btn.active {
            background: #ff9a3c;
            color: white;
            border-color: #ff6f61;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #ff9a3c 0%, #ff6f61 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #ff6f61 0%, #ff9a3c 100%);
            transform: translateY(-2px);
        }
        
        .btn-register {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
        }
        
        .btn-register:hover {
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
        }
        
        .error {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8d7da;
            border-radius: 5px;
            text-align: center;
        }
        
        .success {
            color: #28a745;
            margin-bottom: 15px;
            padding: 10px;
            background: #d4edda;
            border-radius: 5px;
            text-align: center;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        
        .form-footer a {
            color: #ff6f61;
            text-decoration: none;
            font-weight: 600;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle i {
            position: absolute;
            right: 15px;
            top: 14px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .name-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 480px) {
            .name-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-popcorn"></i>
                <h1>Popcorn Paradise</h1>
            </div>
            <p class="tagline">Gourmet Popcorn with a Twist of Flavor</p>
        </header>
        
        <div class="auth-container">
            <div class="login-section">
                <h2 class="section-title">Login</h2>
                
                <?php if (!empty($login_error)): ?>
                    <div class="error"><?php echo $login_error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="user_type" id="user_type" value="customer">
                    
                    <div class="user-type-selector">
                        <div class="user-type-btn active" data-type="customer">Customer</div>
                        <div class="user-type-btn" data-type="admin">Admin</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-eye" id="togglePassword"></i>
                    </div>
                    
                    <button type="submit" name="login" class="btn">Login</button>
                    
                    <div class="form-footer">
                        <p>Don't have an account? <a href="#" id="show-register">Register here</a></p>
                    </div>
                </form>
            </div>
            
            <div class="register-section">
                <h2 class="section-title">Create Account</h2>
                
                <?php if (!empty($registration_error)): ?>
                    <div class="error"><?php echo $registration_error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($registration_success)): ?>
                    <div class="success"><?php echo $registration_success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="name-group">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_username">Username</label>
                        <input type="text" id="reg_username" name="username" required>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="reg_password">Password</label>
                        <input type="password" id="reg_password" name="password" required>
                        <i class="fas fa-eye" id="toggleRegPassword"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye" id="toggleConfirmPassword"></i>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-register">Register</button>
                    
                    <div class="form-footer">
                        <p>Already have an account? <a href="#" id="show-login">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleRegPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('reg_password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // User type selection
        document.querySelectorAll('.user-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.user-type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('user_type').value = this.dataset.type;
            });
        });
        
        // Toggle between login and register forms
        document.getElementById('show-register').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.login-section').style.display = 'none';
            document.querySelector('.register-section').style.display = 'block';
        });
        
        document.getElementById('show-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.register-section').style.display = 'none';
            document.querySelector('.login-section').style.display = 'block';
        });
    </script>
</body>
</html>