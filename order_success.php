<?php
session_start();
if (!isset($_SESSION['transaction_id'])) {
    header("Location: index.php");
    exit;
}

$transaction_id = $_SESSION['transaction_id'];
unset($_SESSION['transaction_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - Popcorn Paradise</title>
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
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .success-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        
        .success-icon {
            font-size: 4rem;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        p {
            margin-bottom: 20px;
            color: #6c757d;
        }
        
        .transaction-id {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 1.1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, #ff9a3c 0%, #ff6f61 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.4);
        }
        
        .info-box {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1>Payment Successful!</h1>
            <p>Thank you for your order. Your payment has been processed successfully.</p>
            
            <div class="transaction-id">
                Transaction ID: <?php echo htmlspecialchars($transaction_id); ?>
            </div>
            
            <p>You will receive an email confirmation shortly.</p>
            
            <a href="index.php" class="btn">Continue Shopping</a>
            
            <div class="info-box">
                <h3><i class="fas fa-info-circle"></i> What's Next?</h3>
                <p>Your order will be prepared and shipped within 24 hours. You'll receive tracking information once your order ships.</p>
            </div>
        </div>
    </div>
</body>
</html>