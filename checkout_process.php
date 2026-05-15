<?php
// Collect Cart Items
$order_summary = "New Order from " . $_SESSION['user_name'] . ":%0A";
foreach($_SESSION['cart'] as $item) {
    $order_summary .= "- " . $item['name'] . " (x" . $item['qty'] . ")%0A";
}
$order_summary .= "Total: LKR " . $_SESSION['total_price'];

// Fetch Admin WhatsApp from Site Settings
$settings = $conn->query("SELECT admin_whatsapp FROM site_settings WHERE id = 1")->fetch();
$wa_link = "https://wa.me/" . $settings['admin_whatsapp'] . "?text=" . $order_summary;

// Redirect to WhatsApp
header("Location: " . $wa_link);
?>