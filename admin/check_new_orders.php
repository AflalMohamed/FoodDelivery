<?php
require_once '../config/config.php';

$stmt = $conn->prepare("SELECT id FROM orders WHERE status = 'pending' AND admin_notified = 0 LIMIT 1");
$stmt->execute();
$order = $stmt->fetch();

if ($order) {
    // Mark as notified so it doesn't pop up again
    $conn->prepare("UPDATE orders SET admin_notified = 1 WHERE id = ?")->execute([$order['id']]);
    echo json_encode(['new_order' => true, 'order_id' => $order['id']]);
} else {
    echo json_encode(['new_order' => false]);
}