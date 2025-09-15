<?php
// process_order.php - Handle order submission
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST; // Fallback to form data
}

$errors = [];

// Validate required fields
$required = ['name', 'email', 'flavor', 'spice_level', 'quantity', 'delivery_method'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        $errors[] = "The $field field is required.";
    }
}

// Validate email
if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address.";
}

// Validate quantity
if (!empty($input['quantity']) && (!is_numeric($input['quantity']) || $input['quantity'] < 1 || $input['quantity'] > 100)) {
    $errors[] = "Quantity must be a number between 1 and 100.";
}

// If there are errors, return them
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

// Calculate total price based on flavor and quantity
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

$flavor = $input['flavor'];
$quantity = (int)$input['quantity'];
$deliveryMethod = $input['delivery_method'];

if (!isset($flavorPrices[$flavor])) {
    $errors[] = "Invalid flavor selected.";
}

if (!isset($deliveryFees[$deliveryMethod])) {
    $errors[] = "Invalid delivery method selected.";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid selection', 'errors' => $errors]);
    exit;
}

$productPrice = $flavorPrices[$flavor] * $quantity;
$deliveryFee = $deliveryFees[$deliveryMethod];
$totalPrice = $productPrice + $deliveryFee;

// Save order to database
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, flavor, spice_level, quantity, delivery_method, delivery_address, special_instructions, total_price) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $input['name'],
        $input['email'],
        $input['phone'] ?? '',
        $flavor,
        $input['spice_level'],
        $quantity,
        $deliveryMethod,
        $input['address'] ?? '',
        $input['instructions'] ?? '',
        $totalPrice
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // Send confirmation email (in a real application)
    // sendConfirmationEmail($input['email'], $input['name'], $orderId, $totalPrice);
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully!', 
        'order_id' => $orderId,
        'total' => number_format($totalPrice, 2)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>