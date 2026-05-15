
<?php
include 'config/config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$total = 0;
$order_details = "New Order Details:%0A------------------%0A";

if (isset($_POST['place_order'])) {
    // 1. Save to Database (Orders Table)
    foreach ($_SESSION['cart'] as $item) { $total += ($item['price'] * $item['qty']); }
    
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$_SESSION['user_id'], $total]);
    $order_id = $conn->lastInsertId();

    // 2. Format WhatsApp Message
    foreach ($_SESSION['cart'] as $id => $item) {
        $order_details .= "Item: " . $item['name'] . " x" . $item['qty'] . "%0A";
    }
    $order_details .= "Total: LKR " . number_format($total, 2) . "%0A";
    $order_details .= "Track here: " . BASE_URL . "user/dashboard.php";

    // 3. Get Admin Number from Settings
    $settings = $conn->query("SELECT admin_whatsapp FROM site_settings WHERE id = 1")->fetch();
    $wa_link = "https://wa.me/" . $settings['admin_whatsapp'] . "?text=" . $order_details;

    // Clear Cart and Redirect
    unset($_SESSION['cart']);
    echo "<script>window.open('$wa_link', '_blank'); window.location.href='user/dashboard.php';</script>";
}
?>

<div class="container mx-auto p-10">
    <h2 class="text-3xl font-bold mb-6">Review Your Order</h2>
    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
        <?php if(!empty($_SESSION['cart'])): ?>
            <?php foreach($_SESSION['cart'] as $id => $item): ?>
                <div class="flex justify-between border-b py-3">
                    <span><?= $item['name'] ?> (x<?= $item['qty'] ?>)</span>
                    <span class="font-bold">LKR <?= number_format($item['price'] * $item['qty'], 2) ?></span>
                </div>
            <?php endforeach; ?>
            
            <form method="POST">
                <button name="place_order" class="w-full mt-6 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition">
                    Confirm & Order via WhatsApp
                </button>
            </form>
        <?php else: ?>
            <p>Your cart is empty.</p>
        <?php endif; ?>
    </div>
</div>