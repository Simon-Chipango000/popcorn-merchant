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

// Check if admin user exists, if not create with hashed password
function initializeAdminUser($conn) {
    $checkAdmin = $conn->query("SELECT * FROM admin_users WHERE username = 'admin'");
    
    if ($checkAdmin->num_rows === 0) {
        $hashedPassword = hashPassword('popcorn123');
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashedPassword);
        
        $username = 'admin';
        $stmt->execute();
    }
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

// Initialize admin user
initializeAdminUser($conn);

// Handle login
$login_error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $input_password = $_POST['password'];
    
    // Get user from database
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (verifyPassword($input_password, $user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_id'] = $user['id'];
        } else {
            $login_error = "Invalid username or password";
        }
    } else {
        $login_error = "Invalid username or password";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Check if user is logged in
$logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Get orders from database
$orders = [];
$total_revenue = 0;
$popular_flavors = [];
$delivery_stats = [];

if ($logged_in) {
    $sql = "SELECT * FROM orders ORDER BY order_date DESC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $orders[] = $row;
            $total_revenue += $row['total_price'];
            
            // Count flavors for statistics
            if (!isset($popular_flavors[$row['flavor']])) {
                $popular_flavors[$row['flavor']] = 0;
            }
            $popular_flavors[$row['flavor']] += $row['quantity'];
            
            // Count delivery methods for statistics
            if (!isset($delivery_stats[$row['delivery_method']])) {
                $delivery_stats[$row['delivery_method']] = 0;
            }
            $delivery_stats[$row['delivery_method']]++;
        }
    }
    
    // Get flavor data for management
    $flavors = [];
    $flavor_result = $conn->query("SELECT * FROM flavors ORDER BY name");
    if ($flavor_result->num_rows > 0) {
        while($row = $flavor_result->fetch_assoc()) {
            $flavors[] = $row;
        }
    } else {
        // Insert default flavors if none exist
        $default_flavors = [
            ['name' => 'classic', 'price' => 4.99, 'display_name' => 'Classic Butter', 'available' => 1],
            ['name' => 'cheesy', 'price' => 5.99, 'display_name' => 'Cheesy Delight', 'available' => 1],
            ['name' => 'caramel', 'price' => 5.49, 'display_name' => 'Caramel Bliss', 'available' => 1],
            ['name' => 'spicy', 'price' => 6.49, 'display_name' => 'Spicy Fiesta', 'available' => 1]
        ];
        
        foreach ($default_flavors as $flavor) {
            $stmt = $conn->prepare("INSERT INTO flavors (name, price, display_name, available) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdss", $flavor['name'], $flavor['price'], $flavor['display_name'], $flavor['available']);
            $stmt->execute();
        }
        
        // Reload flavors
        $flavor_result = $conn->query("SELECT * FROM flavors ORDER BY name");
        while($row = $flavor_result->fetch_assoc()) {
            $flavors[] = $row;
        }
    }
}

// Handle status update
if ($logged_in && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $update_message = "Order status updated successfully!";
        // Refresh the page to show updated status
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $update_error = "Error updating order status: " . $conn->error;
    }
}

// Handle flavor management
if ($logged_in && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_flavor'])) {
    $flavor_id = $_POST['flavor_id'];
    $price = $_POST['price'];
    $available = isset($_POST['available']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE flavors SET price = ?, available = ? WHERE id = ?");
    $stmt->bind_param("dii", $price, $available, $flavor_id);
    
    if ($stmt->execute()) {
        $flavor_message = "Flavor updated successfully!";
        // Refresh the page to show updated flavors
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $flavor_error = "Error updating flavor: " . $conn->error;
    }
}

// Handle order deletion
if ($logged_in && isset($_GET['delete_order'])) {
    $order_id = $_GET['delete_order'];
    
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        $delete_message = "Order deleted successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $delete_error = "Error deleting order: " . $conn->error;
    }
}

// Handle password change
if ($logged_in && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "All fields are required";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } elseif (strlen($new_password) < 8) {
        $password_error = "New password must be at least 8 characters long";
    } else {
        // Get current password hash
        $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Verify current password
        if (verifyPassword($current_password, $user['password_hash'])) {
            // Update password
            $new_password_hash = hashPassword($new_password);
            $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password_hash, $_SESSION['admin_id']);
            
            if ($stmt->execute()) {
                $password_message = "Password changed successfully!";
            } else {
                $password_error = "Error updating password: " . $conn->error;
            }
        } else {
            $password_error = "Current password is incorrect";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Popcorn Paradise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #2c3e50 0%, #4a6580 100%);
            color: white;
            padding: 20px 0;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
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
        }
        
        .logo h1 {
            font-size: 2.2rem;
            font-weight: 800;
        }
        
        .admin-title {
            text-align: center;
            margin-top: 5px;
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        
        .login-title {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #2c3e50 0%, #4a6580 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #4a6580 0%, #2c3e50 100%);
            transform: translateY(-2px);
        }
        
        .btn-logout {
            background: linear-gradient(135deg, #ff6f61 0%, #ff9a3c 100%);
        }
        
        .btn-logout:hover {
            background: linear-gradient(135deg, #e05a50 0%, #e58936 100%);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        }
        
        .error {
            color: #ff6f61;
            margin-bottom: 15px;
            text-align: center;
            padding: 10px;
            background: #f8d7da;
            border-radius: 5px;
        }
        
        .success {
            color: #4CAF50;
            margin-bottom: 15px;
            text-align: center;
            padding: 10px;
            background: #d4edda;
            border-radius: 5px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .stat-card p {
            color: #7f8c8d;
            font-weight: 600;
        }
        
        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .chart-title {
            text-align: center;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .orders-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 700;
            color: #2c3e50;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .action-form {
            display: flex;
            gap: 10px;
        }
        
        select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .flavor-management {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .flavor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .flavor-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .flavor-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .flavor-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .flavor-price {
            color: #ff6f61;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .flavor-form {
            margin-top: 10px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 992px) {
            .chart-container {
                grid-template-columns: 1fr;
            }
            
            .orders-table {
                overflow-x: auto;
            }
            
            table {
                min-width: 1000px;
            }
        }
        
        .flavor-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 5px;
        }
        
        .flavor-classic {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .flavor-cheesy {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .flavor-caramel {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .flavor-spicy {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tabs {
            display: flex;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .tab {
            padding: 15px 20px;
            cursor: pointer;
            background: #f8f9fa;
            transition: all 0.3s;
            text-align: center;
            flex: 1;
        }
        
        .tab.active {
            background: #2c3e50;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .password-management {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .password-title {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
    </style>
</head>
<body style=" background-image: url('popcorn.jpg'); 
            background-repeat: no-repeat; 
            background-size: cover; 
            background-attachment: fixed; ">
    <header style=" background-image: url('popcorn3.jpg'); 
            background-repeat: no-repeat; 
            background-size: cover; 
            background-attachment: fixed; ">
        <div class="container">
            <div class="logo">
                <i class="fas fa-popcorn"></i>
                <h1 style="color:brown">Popcorn Paradise Admin</h1>
            </div>
            <p class="admin-title"style="color:red"> Management System</p>
        </div>
    </header>
    
    <div class="container">
        <?php if (!$logged_in): ?>
            <!-- Login Form -->
            <div class="login-container">
                <h2 class="login-title">Admin Login</h2>
                
                <?php if (!empty($login_error)): ?>
                    <div class="error"><?php echo $login_error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn">Login</button>
                </form>
                <p style="margin-top: 15px; text-align: center; font-size: 0.9rem;">
                  
                </p>
            </div>
            
        <?php else: ?>
            <!-- Admin Dashboard -->
            <div class="admin-header">
                <h2>Welcome, <?php echo isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin'; ?>!</h2>
                <a href="login.php" class="btn btn-logout">Logout</a>
            </div>
            
            <?php if (isset($update_message)): ?>
                <div class="success"><?php echo $update_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($update_error)): ?>
                <div class="error"><?php echo $update_error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($flavor_message)): ?>
                <div class="success"><?php echo $flavor_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($flavor_error)): ?>
                <div class="error"><?php echo $flavor_error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($delete_message)): ?>
                <div class="success"><?php echo $delete_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($delete_error)): ?>
                <div class="error"><?php echo $delete_error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($password_message)): ?>
                <div class="success"><?php echo $password_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($password_error)): ?>
                <div class="error"><?php echo $password_error; ?></div>
            <?php endif; ?>
            
            <!-- Tab Navigation -->
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" data-tab="dashboard">Dashboard</div>
                    <div class="tab" data-tab="orders">Order Management</div>
                    <div class="tab" data-tab="flavors">Flavor Management</div>
                    <div class="tab" data-tab="password">Change Password</div>
                </div>
            </div>
            
            <!-- Dashboard Tab -->
            <div class="tab-content active" id="dashboard">
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <i class="fas fa-shopping-bag" style="color: #4a6580;"></i>
                        <h3><?php echo count($orders); ?></h3>
                        <p>Total Orders</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-clock" style="color: #ff9a3c;"></i>
                        <h3><?php echo count(array_filter($orders, function($order) { 
                            return $order['status'] == 'pending'; 
                        })); ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-truck" style="color: #2c3e50;"></i>
                        <h3><?php echo count(array_filter($orders, function($order) { 
                            return $order['status'] == 'shipped'; 
                        })); ?></h3>
                        <p>Shipped Orders</p>
                    </div>
                    
                    <div class="stat-card">
                        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="currentColor" class="bi bi-cash" viewBox="0 0 16 16">
  <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
  <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V6a2 2 0 0 1-2-2z"/>
</svg>
                        
                        <h3>K<?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="chart-container">
                    <div class="chart-box">
                        <h3 class="chart-title">Popular Flavors</h3>
                        <canvas id="flavorChart"></canvas>
                    </div>
                    
                    <div class="chart-box">
                        <h3 class="chart-title">Delivery Methods</h3>
                        <canvas id="deliveryChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Orders Tab -->
            <div class="tab-content" id="orders">
                <!-- Orders Table -->
                <div class="orders-table">
                    <h2 style="padding: 20px 20px 0;">Order Management</h2>
                    
                    <?php if (count($orders) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Flavor</th>
                                    <th>Spice Level</th>
                                    <th>Quantity</th>
                                    <th>Delivery Method</th>
                                    <th>Total Price</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($order['customer_email']); ?><br>
                                            <?php echo htmlspecialchars($order['customer_phone']); ?>
                                            <?php if (!empty($order['delivery_address'])): ?>
                                                <br><br>
                                                <strong>Address:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($order['special_instructions'])): ?>
                                                <br><br>
                                                <strong>Instructions:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="flavor-badge flavor-<?php echo $order['flavor']; ?>">
                                                <?php echo ucfirst($order['flavor']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo ucfirst($order['spice_level']); ?></td>
                                        <td><?php echo $order['quantity']; ?></td>
                                        <td><?php echo ucfirst($order['delivery_method']); ?></td>
                                        <td>K<?php echo number_format($order['total_price'], 2); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <span class="status status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="action-form">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="status">
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn">Update</button>
                                            </form>
                                            <a href="?delete_order=<?php echo $order['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this order?')" style="margin-top: 10px; display: block; text-align: center;">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px;"></i>
                            <h3>No orders found</h3>
                            <p>There are no orders in the database yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Flavors Tab -->
            <div class="tab-content" id="flavors">
                <div class="flavor-management">
                    <h2>Flavor Management</h2>
                    <p>Update prices and availability of popcorn flavors</p>
                    
                    <div class="flavor-grid">
                        <?php foreach ($flavors as $flavor): ?>
                            <div class="flavor-card">
                                <div class="flavor-icon">
                                    <?php if ($flavor['name'] == 'classic'): ?>
                                        <i class="fas fa-popcorn"></i>
                                    <?php elseif ($flavor['name'] == 'cheesy'): ?>
                                        <i class="fas fa-cheese"></i>
                                    <?php elseif ($flavor['name'] == 'caramel'): ?>
                                        <i class="fas fa-candy-cane"></i>
                                    <?php elseif ($flavor['name'] == 'spicy'): ?>
                                        <i class="fas fa-fire"></i>
                                    <?php else: ?>
                                        <i class="fas fa-popcorn"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flavor-name"><?php echo $flavor['display_name']; ?></div>
                                <div class="flavor-price">K<?php echo number_format($flavor['price'], 2); ?></div>
                                
                                <form method="POST" class="flavor-form">
                                    <input type="hidden" name="flavor_id" value="<?php echo $flavor['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label>Price (K)</label>
                                        <input type="number" name="price" step="0.01" min="0" value="<?php echo $flavor['price']; ?>" class="form-control" required>
                                    </div>
                                    
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="available" id="available_<?php echo $flavor['id']; ?>" value="1" <?php echo $flavor['available'] ? 'checked' : ''; ?>>
                                        <label for="available_<?php echo $flavor['id']; ?>">Available</label>
                                    </div>
                                    
                                    <button type="submit" name="update_flavor" class="btn">Update</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Password Tab -->
            <div class="tab-content" id="password">
                <div class="password-management">
                    <h2 class="password-title">Change Password</h2>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                            <small style="color: #6c757d;">Must be at least 8 characters long</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-success">Change Password</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

   

    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });
        
 // Charts
        <?php if ($logged_in): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Flavor Chart
            const flavorCtx = document.getElementById('flavorChart').getContext('2d');
            const flavorChart = new Chart(flavorCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($key) { return "'" . ucfirst($key) . "'"; }, array_keys($popular_flavors))); ?>],
                    datasets: [{
                        label: 'Quantity Sold',
                        data: [<?php echo implode(',', array_values($popular_flavors)); ?>],
                        backgroundColor: [
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 159, 64, 0.7)'
                        ],
                        borderColor: [
                            'rgba(255, 206, 86, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Delivery Chart
            const deliveryCtx = document.getElementById('deliveryChart').getContext('2d');
            const deliveryChart = new Chart(deliveryCtx, {
                type: 'pie',
                data: {
                    labels: [<?php echo implode(',', array_map(function($key) { return "'" . ucfirst($key) . "'"; }, array_keys($delivery_stats))); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_values($delivery_stats)); ?>],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true
                }
            });
        });
        <?php endif; ?>
    </script>
    </body>
</html>