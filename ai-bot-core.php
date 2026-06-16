<?php
include 'config/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['response' => "🔒 Please login to your profile workspace to access chatbot parameters."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$user_msg = $data['message'] ?? '';

if (empty(trim($user_msg))) {
    echo json_encode(['response' => "🤖 Hello! I am your AI assistant. How can I help you with our menu today?"]);
    exit;
}

try {
    // 1. Fetch live menu items dynamically from products table
    $menu_stmt = $conn->query("SELECT p.food_name, p.price, p.category, p.description, v.shop_name FROM products p LEFT JOIN vendors v ON p.vendor_id = v.id");
    $all_menu = $menu_stmt->fetchAll(PDO::FETCH_ASSOC);

    $menu_context = "";
    foreach($all_menu as $item) {
        $menu_context .= "- {$item['food_name']} (Category: {$item['category']}, Shop: {$item['shop_name']}) - LKR " . number_format($item['price'], 2) . ". Description: {$item['description']}\n";
    }

    // 2. Fetch Top Fast Moving / Best Selling items dynamically based on metrics analysis
    $fast_stmt = $conn->query("
        SELECT p.food_name, COUNT(oi.id) as sales_count 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        GROUP BY p.id 
        ORDER BY sales_count DESC 
        LIMIT 3
    ");
    $fast_moving = $fast_stmt->fetchAll(PDO::FETCH_ASSOC);

    $fast_context = "";
    foreach($fast_moving as $f_item) {
        $fast_context .= "- {$f_item['food_name']} (Highly Trending / Most Ordered Right Now!)\n";
    }

    // 3. Fetch latest order of the current logged-in customer
    $order_stmt = $conn->prepare("SELECT id, total_amount, status FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $order_stmt->execute([$user_id]);
    $last_order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    $order_context = "No previous tracking details found.";
    if ($last_order) {
        $order_context = "Order ID: #ORD-{$last_order['id']}, Total: LKR " . number_format($last_order['total_amount'],2) . ", Status: " . strtoupper($last_order['status']);
    }

    // 4. Construct System Instructions for Gemini Model Execution
    $system_instruction = "You are an intelligent, friendly AI Food Assistant for our online food ordering platform. 
Use the following real-time database context strictly to answer user queries conversationally:

[AVAILABLE MENU HIGHLIGHTS]
$menu_context

[DYNAMIC FAST MOVING FOOD / TOP SELLERS]
$fast_context

[CURRENT LOGGED IN CUSTOMER LAST ORDER TRACKING DATA]
$order_context

GUIDELINES:
1. Talk like a friendly human assistant. Do not show raw data arrays or codes.
2. If the user asks for 'biriyani menu', pull only the items under Category: Biriyani from the provided menu list and present them beautifully.
3. If the user asks for 'fast moving food' or 'trending item', highlight the products explicitly listed in the Fast Moving section.
4. Keep answers sweet, short, and conversion-focused. Always recommend users to click 'Add' or click 'WhatsApp icon' to process checkout instantly.";

    // 5. Free Google Gemini API Request Setup (Fixed to stable endpoint v1beta)
    // ⚠️ PASTE YOUR COPIED GEMINI API KEY HERE BELOW:
    $api_key = "";
    
    // ✨ FIX: Updated to gemini-2.5-flash standard production routing rules
    $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

    $full_prompt = $system_instruction . "\n\nUser Question: " . $user_msg;

    $post_fields = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $full_prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $gemini_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
    
    // Bypass local development certificate blocks
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    $api_response = curl_exec($ch);
    
    if(curl_errno($ch)){
        throw new Exception("cURL Exception: " . curl_error($ch));
    }
    curl_close($ch);

    $result = json_decode($api_response, true);
    
    // Catch explicit system validation alerts directly from API engine
    if (isset($result['error'])) {
        echo json_encode(['response' => "⚠️ Gemini API Error [{$result['error']['code']}]: {$result['error']['message']}"]);
        exit;
    }

    // Extraction framework for updated Gemini json structural tree
    $reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if ($reply === null) {
        echo json_encode(['response' => "🤖 Error parsing Gemini Response layer. Raw Response: " . json_encode($result)]);
        exit;
    }

    echo json_encode(['response' => $reply]);

} catch (Exception $e) {
    echo json_encode(['response' => "⚠️ Free AI Engine Fault: " . $e->getMessage()]);
}