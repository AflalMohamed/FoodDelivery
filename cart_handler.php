<?php
include 'config/config.php';

if (isset($_POST['product_id'])) {
    $p_id = $_POST['product_id'];
    $qty = $_POST['quantity'] ?? 1;

    // Fetch product details from DB
    $stmt = $conn->prepare("SELECT id, food_name, price FROM products WHERE id = ?");
    $stmt->execute([$p_id]);
    $product = $stmt->fetch();

    // Store in session
    $_SESSION['cart'][$p_id] = [
        'name'  => $product['food_name'],
        'price' => $product['price'],
        'qty'   => $qty
    ];
    
    header("Location: checkout.php");
}
?>