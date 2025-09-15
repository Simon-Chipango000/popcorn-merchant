<?php
// Start session
session_start();

// Simulate payment processing (in a real application, use a payment processor like Stripe)
function processPayment($paymentData) {
    // Validate card details
    $cardNumber = str_replace(' ', '', $paymentData['card_number']);
    $expiry = explode('/', $paymentData['expiry']);
    $cvv = $paymentData['cvv'];
    
    if (strlen($cardNumber) !== 16 || !is_numeric($cardNumber)) {
        return ['success' => false, 'message' => 'Invalid card number'];
    }
    
    if (count($expiry) !== 2 || !is_numeric(trim($expiry[0])) || !is_numeric(trim($expiry[1]))) {
        return ['success' => false, 'message' => 'Invalid expiry date'];
    }
    
    $month = trim($expiry[0]);
    $year = trim($expiry[1]);
    
    if ($month < 1 || $month > 12) {
        return ['success' => false, 'message' => 'Invalid expiry month'];
    }
    
    // Check if card is not expired
    $currentYear = date('y');
    $currentMonth = date('m');
    
    if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
        return ['success' => false, 'message' => 'Card has expired'];
    }
    
    if (strlen($cvv) < 3 || strlen($cvv) > 4 || !is_numeric($cvv)) {
        return ['success' => false, 'message' => 'Invalid CVV'];
    }
    
    // Simulate payment processing with 90% success rate
    $success = rand(1, 10) !== 10; // 90% success rate
    
    if ($success) {
        // Generate a fake transaction ID
        $transactionId = 'txn_' . strtoupper(bin2hex(random_bytes(8)));
        return ['success' => true, 'transaction_id' => $transactionId];
    } else {
        return ['success' => false, 'message' => 'Payment declined by bank'];
    }
}

// Handle form submission
$payment_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $paymentData = [
        'card_number' => $_POST['card_number'],
        'expiry' => $_POST['expiry'],
        'cvv' => $_POST['cvv'],
        'cardholder_name' => $_POST['cardholder_name']
    ];
    
    $payment_result = processPayment($paymentData);
    
    if ($payment_result['success']) {
        // In a real application, you would save the order to the database here
        // and redirect to a success page
        $_SESSION['transaction_id'] = $payment_result['transaction_id'];
        header("Location: order_success.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - Popcorn Paradise</title>
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
            max-width: 800px;
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
        
        .payment-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: white;
            border-radius: 0 0 15px 15px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 768px) {
            .payment-container {
                grid-template-columns: 1fr;
            }
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 30px;
        }
        
        .payment-form {
            padding: 30px;
            background: white;
        }
        
        .section-title {
            color: #ff6f61;
            border-bottom: 2px dashed #ff9a3c;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .order-details {
            margin-bottom: 30px;
        }
        
        .order-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #ddd;
        }
        
        .order-total {
            font-size: 1.3rem;
            font-weight: 800;
            color: #ff6f61;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 8px;
            color: #2e7d32;
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
        
        .card-input {
            position: relative;
        }
        
        .card-input i {
            position: absolute;
            right: 15px;
            top: 14px;
            color: #6c757d;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
            transform: translateY(-2px);
        }
        
        .error {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8d7da;
            border-radius: 5px;
            text-align: center;
        }
        
        .card-icons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .card-icon {
            width: 50px;
            height: 30px;
            background: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #6c757d;
            border: 1px solid #ddd;
        }
        
        .encryption-note {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
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
            <p class="tagline">Secure Payment Gateway</p>
        </header>
        
        <div class="payment-container">
            <div class="order-summary">
                <h2 class="section-title">Order Summary</h2>
                
                <div class="order-details">
                    <div class="order-row">
                        <span>Product:</span>
                        <span>Classic Butter Popcorn</span>
                    </div>
                    
                    <div class="order-row">
                        <span>Quantity:</span>
                        <span>2 bags</span>
                    </div>
                    
                    <div class="order-row">
                        <span>Delivery:</span>
                        <span>Standard Delivery</span>
                    </div>
                    
                    <div class="order-row">
                        <span>Subtotal:</span>
                        <span>$9.98</span>
                    </div>
                    
                    <div class="order-row">
                        <span>Delivery Fee:</span>
                        <span>$2.99</span>
                    </div>
                    
                    <div class="order-total">
                        <span>Total:</span>
                        <span>$12.97</span>
                    </div>
                </div>
                
                <div class="security-badge">
                    <i class="fas fa-shield-alt fa-2x"></i>
                    <div>
                        <h3>Secure Payment</h3>
                        <p>Your payment information is encrypted</p>
                    </div>
                </div>
                
                <div class="card-icons">
                    <div class="card-icon">VISA</div>
                    <div class="card-icon">MC</div>
                    <div class="card-icon">AMEX</div>
                    <div class="card-icon">DISCOVER</div>
                </div>
            </div>
            
            <div class="payment-form">
                <h2 class="section-title">Payment Details</h2>
                
                <?php if ($payment_result && !$payment_result['success']): ?>
                    <div class="error"><?php echo $payment_result['message']; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" id="payment-form">
                    <div class="form-group">
                        <label for="cardholder_name">Cardholder Name</label>
                        <input type="text" id="cardholder_name" name="cardholder_name" placeholder="John Doe" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <div class="card-input">
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required maxlength="19">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry">Expiry Date</label>
                            <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required maxlength="5">
                        </div>
                        
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <div class="card-input">
                                <input type="text" id="cvv" name="cvv" placeholder="123" required maxlength="4">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="process_payment" class="btn">
                        <i class="fas fa-lock"></i> Pay Now - $12.97
                    </button>
                    
                    <p class="encryption-note">
                        <i class="fas fa-shield-alt"></i> All transactions are secure and encrypted
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Format card number with spaces
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let matches = value.match(/\d{4,16}/g);
            let match = matches ? matches[0] : '';
            let parts = [];
            
            for (let i = 0; i < match.length; i += 4) {
                parts.push(match.substring(i, i + 4));
            }
            
            if (parts.length) {
                e.target.value = parts.join(' ');
            } else {
                e.target.value = value;
            }
        });
        
        // Format expiry date
        document.getElementById('expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 2) {
                e.target.value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
        });
        
        // Only allow numbers in CVV field
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
        
        // Form validation
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            let cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
            let expiry = document.getElementById('expiry').value;
            let cvv = document.getElementById('cvv').value;
            
            if (cardNumber.length !== 16) {
                e.preventDefault();
                alert('Please enter a valid 16-digit card number');
                return false;
            }
            
            if (!expiry.includes('/') || expiry.length !== 5) {
                e.preventDefault();
                alert('Please enter a valid expiry date in MM/YY format');
                return false;
            }
            
            if (cvv.length < 3 || cvv.length > 4) {
                e.preventDefault();
                alert('Please enter a valid CVV (3-4 digits)');
                return false;
            }
        });
    </script>
</body>
</html>