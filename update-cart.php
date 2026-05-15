<?php
session_start();

// Request method POST-a nu check panrom
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : null;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : null;

    if ($id) {
        // REMOVE LOGIC
        if ($action === 'remove') {
            unset($_SESSION['cart'][$id]);
            echo json_encode(['status' => 'success', 'message' => 'Item removed']);
            exit;
        }

        // UPDATE QUANTITY LOGIC
        if ($qty && $qty > 0) {
            $_SESSION['cart'][$id] = $qty;
            echo json_encode(['status' => 'success', 'message' => 'Qty updated']);
            exit;
        }
    }
}

// CLEAR CART LOGIC (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    unset($_SESSION['cart']);
    echo json_encode(['status' => 'success']);
    exit;
}
?>