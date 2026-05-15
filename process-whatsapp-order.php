<?php
include 'config/config.php';

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    // 1. Fetch Product & Vendor Details
    $stmt = $conn->prepare("SELECT p.*, v.id as v_id, v.shop_name FROM products p JOIN vendors v ON p.vendor_id = v.id WHERE p.id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        // Settings for Delivery Fee (You can also fetch this from your settings table)
        $delivery_fee = 100.00; 
        $total_amount = $product['price'] + $delivery_fee;
        
        try {
            // 2. Log Order to Database (Matching your existing columns)
            $insert = $conn->prepare("INSERT INTO orders (vendor_id, subtotal, delivery_fee, total_amount, status, order_date) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $insert->execute([
                $product['v_id'],
                $product['price'],
                $delivery_fee,
                $total_amount
            ]);
        } catch (PDOException $e) {
            // Silent catch to ensure redirect still happens
        }

        // 3. Fetch Admin WhatsApp Number
        $settings_res = $conn->query("SELECT admin_whatsapp FROM site_settings WHERE id = 1");
        $settings = $settings_res->fetch();
        $admin_wa = $settings['admin_whatsapp'] ?? '94703720960';

        // 4. Build the Professional WhatsApp Message (Formatted as requested)
        $msg = "*New Order - TownFood*\n";
        $msg .= "--------------------------\n";
        $msg .= "📞 *Phone:* [Customer Phone]\n"; // Customer will fill this or you can pull from session
        $msg .= "📍 *Address:* [Enter Address]\n";
        $msg .= "🏘️ *Area:* Inside Town\n";
        $msg .= "--------------------------\n\n";
        
        $msg .= "• *" . $product['food_name'] . "*\n";
        $msg .= "  Qty: 1 | LKR " . number_format($product['price'], 2) . "\n";
        $msg .= "  [Shop: " . $product['shop_name'] . "]\n\n";
        
        $msg .= "--------------------------\n";
        $msg .= "Subtotal: LKR " . number_format($product['price'], 0) . "\n";
        $msg .= "Delivery: LKR " . number_format($delivery_fee, 0) . "\n";
        $msg .= "*Total: LKR " . number_format($total_amount, 0) . "*";

        // 5. Redirect to WhatsApp
        header("Location: https://wa.me/" . $admin_wa . "?text=" . urlencode($msg));
        exit();
    }
}

header("Location: index.php");
exit();