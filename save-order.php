<?php
include 'config/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Return JSON response for the JavaScript fetch call
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the incoming JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Basic validation to ensure cart data exists
    if (!$data || empty($data['items'])) {
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty or data is invalid.']);
        exit;
    }

    try {
        // Start a transaction so that if one part fails, nothing is saved
        $conn->beginTransaction();

        // Detect logged-in user or set as NULL for guest checkout
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        // 1. Insert into 'orders' table
        $sql_order = "INSERT INTO orders (user_id, phone, address, subtotal, delivery_fee, total_amount, status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmt = $conn->prepare($sql_order);
        $stmt->execute([
            $user_id,
            $data['phone'],
            $data['address'],
            $data['subtotal'],
            $data['delivery'],
            $data['total']
        ]);
        
        // Retrieve the ID of the order we just created
        $order_id = $conn->lastInsertId();

        // 2. Insert into 'order_items' table (Mapped to your DESCRIBE results)
        // Table Columns: order_id, product_id, quantity, price_at_time
        $sql_items = "INSERT INTO order_items (order_id, product_id, quantity, price_at_time) 
                      VALUES (?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_items);
        
        foreach ($data['items'] as $item) {
            $stmt_item->execute([
                $order_id,
                $item['id'],    // Matches 'product_id'
                $item['qty'],   // Matches 'quantity'
                $item['price']  // Matches 'price_at_time'
            ]);
        }

        // Permanently save the data to the database
        $conn->commit();
        
        // Clear the shopping cart session upon success
        unset($_SESSION['cart']);

        echo json_encode(['status' => 'success', 'order_id' => $order_id]);

    } catch (Exception $e) {
        // Cancel all database changes if an error occurs
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        echo json_encode([
            'status' => 'error', 
            'message' => 'Database Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
}
?>