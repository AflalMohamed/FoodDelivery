<?php
// Output buffering start - to prevent accidental white spaces before JSON
ob_start();

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include your database connection
include 'config/config.php';

// Initialize default response
$response = [
    'status' => 'error', 
    'cart_count' => 0, 
    'grand_total' => "0.00"
];

// Initialize cart if empty
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if action and id are provided
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id']; // Cast to integer for safety
    $action = $_GET['action'];

    // 1. Handle the Cart Operations
    switch ($action) {
        case 'add':
        case 'increase':
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]++;
            } else {
                $_SESSION['cart'][$id] = 1;
            }
            break;

        case 'decrease':
            if (isset($_SESSION['cart'][$id])) {
                if ($_SESSION['cart'][$id] > 1) {
                    $_SESSION['cart'][$id]--;
                } else {
                    unset($_SESSION['cart'][$id]);
                }
            }
            break;

        case 'remove':
            if (isset($_SESSION['cart'][$id])) {
                unset($_SESSION['cart'][$id]);
            }
            break;
    }

    // 2. Calculate Real-Time Totals
    $total_qty = array_sum($_SESSION['cart']);
    $grand_total = 0;

    if (!empty($_SESSION['cart'])) {
        // Fetch only the prices for products currently in the cart
        $cart_ids = array_keys($_SESSION['cart']);
        
        // Secure way to query multiple IDs using PDO
        $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
        $stmt = $conn->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
        $stmt->execute($cart_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $p) {
            $qty = $_SESSION['cart'][$p['id']];
            $grand_total += ($p['price'] * $qty);
        }
    }

    // 3. Prepare the Success Response
    $response = [
        'status' => 'success',
        'cart_count' => $total_qty,
        'grand_total' => number_format($grand_total, 2)
    ];
}

// Clear any whitespace/errors and return JSON
if (ob_get_length()) ob_clean();
header('Content-Type: application/json');
echo json_encode($response);
exit;