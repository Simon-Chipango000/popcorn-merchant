<?php
// orders.php - Admin page to view orders (protected)
require_once 'config.php';

// Simple authentication (in a real app, use proper authentication)
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching orders: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Popcorn Paradise</title>
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
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Orders Management</h1>
    <p><a href="login.php">Logout</a></p>
    
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?><br><?= htmlspecialchars($order['customer_email']) ?></td>
                        <td><?= ucfirst($order['flavor']) ?></td>
                        <td><?= ucfirst($order['spice_level']) ?></td>
                        <td><?= $order['quantity'] ?></td>
                        <td><?= ucfirst($order['delivery_method']) ?></td>
                        <td>$<?= number_format($order['total_price'], 2) ?></td>
                        <td><?= $order['order_date'] ?></td>
                        <td><?= ucfirst($order['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>
</body>
</html>