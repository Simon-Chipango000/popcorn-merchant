<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Your MySQL username
$password = ""; // Your MySQL password
$dbname = "popcorn_paradise";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create orders table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20),
    flavor VARCHAR(50) NOT NULL,
    spice_level VARCHAR(20) NOT NULL,
    quantity INT(11) NOT NULL,
    delivery_method VARCHAR(50) NOT NULL,
    delivery_address TEXT,
    special_instructions TEXT,
    total_price DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("Error creating table: " . $conn->error);
}

// Process form submission
$orderSuccess = false;
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $flavor = $_POST['flavor'];
    $spice_level = $_POST['spice_level'];
    $quantity = $_POST['quantity'];
    $delivery_method = $_POST['delivery_method'];
    $address = $_POST['address'];
    $instructions = $_POST['instructions'];
    
    // Calculate total price
    $flavorPrices = [
        'classic' => 4.99,
        'cheesy' => 5.99,
        'caramel' => 5.49,
        'spicy' => 6.49
    ];
    
    $deliveryFees = [
        'pickup' => 0,
        'standard' => 2.99,
        'express' => 5.99
    ];
    
    $productPrice = $flavorPrices[$flavor] * $quantity;
    $deliveryFee = $deliveryFees[$delivery_method];
    $totalPrice = $productPrice + $deliveryFee;
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, flavor, spice_level, quantity, delivery_method, delivery_address, special_instructions, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssisssd", $name, $email, $phone, $flavor, $spice_level, $quantity, $delivery_method, $address, $instructions, $totalPrice);
    
    if ($stmt->execute()) {
        $orderSuccess = true;
        $orderId = $stmt->insert_id;
    } else {
        $errorMessage = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Popcorn Paradise - Gourmet Popcorn Shop</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'roboto', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient( to rigth, 135deg, #e6c259ff 10%, #f3b63dff 100%);
            color: #333;
            line-height: 1.6;
        }
        
        .r {

            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #ff9a3c 0%, #ff6f61 100%);
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
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .logo h1 {
            font-size: 2.8rem;
            font-weight: 800;
        }
        
        .tagline {
            text-align: center;
            font-style: italic;
            margin-top: 5px;
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 900px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
        
        .flavor-selection, .order-summary {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .section-title {
            color: #ff6f61;
            border-bottom: 2px dashed #ff9a3c;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .flavor-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .flavor-card {
            background: #fffaf0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid transparent;
        }
        
        .flavor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .flavor-card.selected {
            border-color: #ff6f61;
            background: #fff0e6;
            position: relative;
        }
        
        .flavor-card.selected::after {
            content: '✓';
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ff6f61;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .flavor-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #ff9a3c;
        }
        
        .flavor-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .flavor-price {
            color: #ff6f61;
            font-weight: 700;
        }
        
        .spice-level {
            margin: 25px 0;
        }
        
        .spice-title {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .spice-indicators {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }
        
        .spice-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #f0f0f0;
        }
        
        .spice-indicator.active {
            background: #ff6f61;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 25px 0;
        }
        
        .qty-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ff9a3c;
            color: white;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-display {
            font-size: 1.5rem;
            font-weight: 700;
            min-width: 50px;
            text-align: center;
        }
        
        .delivery-options {
            margin: 25px 0;
        }
        
        .delivery-option {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            background: #f9f9f9;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .delivery-option:hover {
            background: #fff0e6;
        }
        
        .delivery-option.selected {
            background: #fff0e6;
            border: 2px solid #ff9a3c;
        }
        
        .order-details {
            margin-top: 20px;
        }
        
        .order-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #ddd;
        }
        
        .order-total {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ff6f61;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff9a3c 0%, #ff6f61 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.4);
        }
        
        .checkout-btn:active {
            transform: translateY(0);
        }
        
        footer {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            color: #777;
            border-top: 1px solid #eee;
        }
        
        /* Checkout Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 600px;
            animation: modalFade 0.3s;
        }
        
        @keyframes modalFade {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .required {
            color: #ff6f61;
        }
        
        #checkout-form {
            margin-top: 20px;
        }
        
        #submit-order {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
        }
        
        #submit-order:hover {
            background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%);
        }
        
        .notification {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* jQuery UI Slider customization */
        .ui-slider {
            height: 8px;
            background: #f0f0f0;
            border-radius: 10px;
            margin: 15px 0;
        }
        
        .ui-slider-handle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #ff6f61;
            border: 2px solid white;
            outline: none;
            top: -6px;
        }
        
        .ui-slider-range {
            background: #ff9a3c;
            border-radius: 10px;
        }
         .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
        
    </style>
</head>
<body style=" background-image: url('popcorn2.jpg'); 
            background-repeat: no-repeat; 
            background-size: cover; 
            background-attachment: fixed; ">
    <header style=" background-image: url('popcorn3.png'); 
            background-repeat: no-repeat; 
            background-size: cover; 
            background-attachment: fixed; ">
        <div class="container">
            <div class="logo">
                <i class="fas fa-popcorn"></i>
                <h1 style="color:red">Popcorn Paradise</h1>
            </div>
            <p class="tagline"style="color:black"> Popcorn with a Twist of Flavor</p>
        </div>
         <div class="admin-header">
                <h2  style="color:brown">Welcome, <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'user'; ?>!</h2>
                <a href="login.php" class="btn btn-logout">Logout</a>
            </div>
    </header>
    
    <div class="container">
        <?php if ($orderSuccess): ?>
            <div class="notification success">
                <strong>Success!</strong> Your order has been placed successfully! 
            </div>
        <?php elseif (!empty($errorMessage)): ?>
            <div class="notification error">
                <strong>Error:</strong> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="main-content">
            <div class="flavor-selection">
                <h2 class="section-title">Create Your Popcorn</h2>
                
                <div class="flavor-options">
                    <div class="flavor-card" data-flavor="classic" data-price="4.99">
                        <div class="flavor-icon">
                            <i class="fas fa-popcorn"></i>
                        </div>
                        <div class="flavor-name">Classic Butter</div>
                        <div class="flavor-price">K4.99</div>
                    </div>
                    
                    <div class="flavor-card" data-flavor="cheesy" data-price="5.99">
                        <div class="flavor-icon">
                            <i class="fas fa-cheese"></i>
                        </div>
                        <div class="flavor-name">Cheesy Delight</div>
                        <div class="flavor-price">K5.99</div>
                    </div>
                    
                    <div class="flavor-card" data-flavor="caramel" data-price="5.49">
                        <div class="flavor-icon">
                            <i class="fas fa-candy-cane"></i>
                        </div>
                        <div class="flavor-name">Caramel Bliss</div>
                        <div class="flavor-price">K5.49</div>
                    </div>
                    
                    <div class="flavor-card" data-flavor="spicy" data-price="6.49">
                        <div class="flavor-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="flavor-name">Spicy Fiesta</div>
                        <div class="flavor-price">K6.49</div>
                    </div>
                </div>
                
                <div class="spice-level">
                    <div class="spice-title">
                        <h3>Spice Level</h3>
                        <span id="spice-value">Mild</span>
                    </div>
                    <div id="spice-slider"></div>
                    <div class="spice-indicators">
                        <div class="spice-indicator active" data-level="1"></div>
                        <div class="spice-indicator" data-level="2"></div>
                        <div class="spice-indicator" data-level="3"></div>
                        <div class="spice-indicator" data-level="4"></div>
                        <div class="spice-indicator" data-level="5"></div>
                    </div>
                </div>
                
                <div class="quantity-control">
                    <h3>Quantity:</h3>
                    <button class="qty-btn" id="decrease-qty">-</button>
                    <div class="qty-display" id="quantity">1</div>
                    <button class="qty-btn" id="increase-qty">+</button>
                    <span>(Bags)</span>
                </div>
                
                <div class="delivery-options">
                    <h3>Delivery Method</h3>
                    
                    <div class="delivery-option" data-method="pickup" data-fee="0">
                        <input type="radio" name="delivery" id="pickup">
                        <label for="pickup">Pickup (Free)</label>
                    </div>
                    
                    <div class="delivery-option" data-method="standard" data-fee="2.99">
                        <input type="radio" name="delivery" id="standard">
                        <label for="standard">Standard Delivery (K2.99)</label>
                    </div>
                    
                    <div class="delivery-option" data-method="express" data-fee="5.99">
                        <input type="radio" name="delivery" id="express">
                        <label for="express">Express Delivery (K5.99)</label>
                    </div>
                </div>
            </div>
            
            <div class="order-summary">
                <h2 class="section-title">Your Order</h2>
                
                <div class="order-details">
                    <div class="order-row">
                        <span>Product:</span>
                        <span id="summary-flavor">None selected</span>
                    </div>
                    
                    <div class="order-row">
                        <span>Spice Level:</span>
                        <span id="summary-spice">Mild</span>
                    </div>
                    
                    <div class="order-row">
                        <span>Quantity:</span>
                        <span id="summary-quantity">1</span>
                    </div>
                    
                    <div class="order-row">
                        <span>Delivery:</span>
                        <span id="summary-delivery">Not selected</span>
                    </div>
                    
                    <div class="order-row">
                        <span>Product Price:</span>
                        <span id="summary-price">K0.00</span>
                    </div>
                    
                    <div class="order-row">
                        <span>Delivery Fee:</span>
                        <span id="summary-delivery-fee">K0.00</span>
                    </div>
                    
                    <div class="order-total">
                        <span>Total:</span>
                        <span id="summary-total">K0.00</span>
                    </div>
                </div>
                
                <button class="checkout-btn" id="checkout-button">Checkout Now</button>
            </div>
        </div>
    </div>
    
    <!-- Checkout Modal -->
    <div id="checkout-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="section-title">Complete Your Order</h2>
            
            <form id="checkout-form" method="POST" action="">
                <input type="hidden" name="flavor" id="form-flavor">
                <input type="hidden" name="spice_level" id="form-spice-level">
                <input type="hidden" name="quantity" id="form-quantity">
                <input type="hidden" name="delivery_method" id="form-delivery-method">
                
                <div class="form-group">
                    <label for="name">Full Name <span class="required" style="color:red">*</span></label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address <span class="required"style="color:red">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone">
                </div>
                
                <div class="form-group" id="address-field">
                    <label for="address">Delivery Address</label>
                    <textarea id="address" name="address"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="instructions">Special Instructions</label>
                    <textarea id="instructions" name="instructions" title="Any special request or notes..." placeholder="Any special requests or notes..."></textarea>
                </div>
                
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="order-details">
                        <div class="order-row">
                            <span>Product:</span>
                            
                            <span id="modal-flavor"></span>
                        </div>
                        
                        <div class="order-row">
                            <span>Spice Level:</span>
                            <span id="modal-spice"></span>
                        </div>
                        
                        <div class="order-row">
                            <span>Quantity:</span>
                            <span id="modal-quantity"></span>
                        </div>
                        
                        <div class="order-row">
                            <span>Delivery:</span>
                            <span id="modal-delivery"></span>
                        </div>
                        
                        <div class="order-total">
                            <span>Total:</span>
                            <span id="modal-total"></span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" id="submit-order">Place Order</button>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="    r">
            <p style="color:black">© 2025 Popcorn Paradise - All rights reserved</p>
            <p  style="color:black">Made with <i class="fas fa-heart" style="color: #ff6f61;"></i> for popcorn lovers</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize variables
            let selectedFlavor = null;
            let selectedSpice = 1;
            let quantity = 1;
            let deliveryMethod = null;
            let deliveryFee = 0;
            
            // Initialize spice slider
            $("#spice-slider").slider({
                range: "min",
                value: 1,
                min: 1,
                max: 5,
                step: 1,
                slide: function(event, ui) {
                    updateSpiceLevel(ui.value);
                }
            });
            
            // Flavor selection
            $(".flavor-card").click(function() {
                $(".flavor-card").removeClass("selected");
                $(this).addClass("selected");
                selectedFlavor = $(this).data("flavor");
                updateOrderSummary();
            });
            
            // Spice level update function
            function updateSpiceLevel(level) {
                selectedSpice = level;
                $("#spice-value").text(getSpiceText(level));
                
                // Update spice indicators
                $(".spice-indicator").removeClass("active");
                $(`.spice-indicator[data-level="${level}"]`).addClass("active");
                
                updateOrderSummary();
            }
            
            // Convert spice level number to text
            function getSpiceText(level) {
                const levels = ["None", "Mild", "Medium", "Hot", "Extra Hot", "Extreme"];
                return levels[level];
            }
            
            // Quantity controls
            $("#increase-qty").click(function() {
                quantity++;
                $("#quantity").text(quantity);
                updateOrderSummary();
            });
            
            $("#decrease-qty").click(function() {
                if (quantity > 1) {
                    quantity--;
                    $("#quantity").text(quantity);
                    updateOrderSummary();
                }
            });
            
            // Delivery option selection
            $(".delivery-option").click(function() {
                $(".delivery-option").removeClass("selected");
                $(this).addClass("selected");
                deliveryMethod = $(this).data("method");
                deliveryFee = $(this).data("fee");
                
                // Show/hide address field based on delivery method
                if (deliveryMethod === 'pickup') {
                    $("#address-field").hide();
                } else {
                    $("#address-field").show();
                }
                
                updateOrderSummary();
            });
            
            // Update order summary
            function updateOrderSummary() {
                // Update flavor
                if (selectedFlavor) {
                    $("#summary-flavor").text($(`.flavor-card[data-flavor="${selectedFlavor}"] .flavor-name`).text());
                } else {
                    $("#summary-flavor").text("None selected");
                }
                
                // Update spice level
                $("#summary-spice").text(getSpiceText(selectedSpice));
                
                // Update quantity
                $("#summary-quantity").text(quantity);
                
                // Update delivery method
                if (deliveryMethod) {
                    $("#summary-delivery").text($(`.delivery-option[data-method="${deliveryMethod}"] label`).text());
                } else {
                    $("#summary-delivery").text("Not selected");
                }
                
                // Calculate prices
                const productPrice = selectedFlavor ? $(`.flavor-card[data-flavor="${selectedFlavor}"]`).data("price") : 0;
                const totalProductPrice = productPrice * quantity;
                const total = totalProductPrice + deliveryFee;
                
                // Update prices in summary
                $("#summary-price").text(`$${totalProductPrice.toFixed(2)}`);
                $("#summary-delivery-fee").text(`$${deliveryFee.toFixed(2)}`);
                $("#summary-total").text(`$${total.toFixed(2)}`);
            }
            
            // Checkout button - show modal
            $("#checkout-button").click(function() {
                if (!selectedFlavor) {
                    alert("Please select a flavor first!");
                    return;
                }
                
                if (!deliveryMethod) {
                    alert("Please select a delivery method!");
                    return;
                }
                
                // Update modal summary
                $("#modal-flavor").text($(`.flavor-card[data-flavor="${selectedFlavor}"] .flavor-name`).text());
                $("#modal-spice").text(getSpiceText(selectedSpice));
                $("#modal-quantity").text(quantity);
                $("#modal-delivery").text($(`.delivery-option[data-method="${deliveryMethod}"] label`).text());
                $("#modal-total").text($("#summary-total").text());
                
                // Set hidden form values
                $("#form-flavor").val(selectedFlavor);
                $("#form-spice-level").val(getSpiceText(selectedSpice));
                $("#form-quantity").val(quantity);
                $("#form-delivery-method").val(deliveryMethod);
                
                // Show modal
                $("#checkout-modal").show();
            });
            
            // Close modal
            $(".close").click(function() {
                $("#checkout-modal").hide();
            });
            
            // Click outside modal to close
            $(window).click(function(event) {
                if ($(event.target).is("#checkout-modal")) {
                    $("#checkout-modal").hide();
                }
            });
            
            // Initialize the order summary
            updateOrderSummary();
        });
    </script>
</body>
</html>