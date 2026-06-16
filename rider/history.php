<?php
require_once '../config/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: ../login.php");
    exit;
}

$rider_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';

try {
    $settings_stmt = $conn->query("SELECT site_name FROM site_settings LIMIT 1");
    $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
    $site_display_name = $settings['site_name'] ?? 'FCA FOOD';

    $rider_info = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $rider_info->execute([$rider_id]);
    $rider = $rider_info->fetch(PDO::FETCH_ASSOC);

    // Search Query Logic - FIXED to aggregate food items from relational schema
    $query = "SELECT o.*, u.name as db_customer_name,
                     (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.food_name) SEPARATOR ', ') 
                      FROM order_items oi 
                      INNER JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = o.id) as ordered_items
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.rider_id = ? AND o.status = 'delivered'";
    
    if (!empty($search)) {
        $query .= " AND (o.id LIKE ? OR u.name LIKE ?)";
        $stmt = $conn->prepare($query . " ORDER BY o.order_date DESC");
        $stmt->execute([$rider_id, "%$search%", "%$search%"]);
    } else {
        $stmt = $conn->prepare($query . " ORDER BY o.order_date DESC");
        $stmt->execute([$rider_id]);
    }
    
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>History | <?= htmlspecialchars($site_display_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fbfcfe; }
        .gradient-orange { background: linear-gradient(135deg, #ff8c00 0%, #ff4500 100%); }
    </style>
</head>
<body class="pb-24">

    <?php include 'includes/sidebar.php'; ?>

    <div class="gradient-orange pb-32 pt-8 px-6 rounded-b-[3rem] shadow-2xl">
        <nav class="flex justify-between items-center mb-8">
            <button onclick="toggleSidebar()" class="w-12 h-12 bg-white/20 backdrop-blur-md text-white rounded-2xl flex items-center justify-center border border-white/30 active:scale-90 transition-all">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
            <div class="text-center">
                <p class="text-white/70 text-[10px] font-bold uppercase tracking-widest">Delivery Records</p>
                <h1 class="text-white font-black text-xl italic tracking-tighter uppercase">History</h1>
            </div>
            <a href="history.php" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center font-black text-orange-600 shadow-lg">
                <i class="fa-solid fa-rotate-right"></i>
            </a>
        </nav>

        <div class="bg-white p-2 rounded-[2rem] shadow-xl">
            <form action="" method="GET" class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search Order ID or Name..." 
                       class="w-full bg-transparent py-4 pl-12 pr-4 rounded-3xl outline-none font-bold text-sm text-slate-800">
                <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-orange-500"></i>
            </form>
        </div>
    </div>

    <div class="max-w-md mx-auto px-5 -mt-16">
        
        <div class="flex justify-between items-end mb-6 px-2">
            <div>
                <h2 class="text-xl font-black text-slate-800 tracking-tight">Completed</h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase italic mt-1"><?= count($history) ?> Tasks Finished</p>
            </div>
        </div>

        <div class="space-y-6">
            <?php if(!empty($history)): foreach($history as $h): ?>
                <div class="bg-white rounded-[2.8rem] border border-slate-50 shadow-lg p-6 relative group active:scale-95 transition-all">
                    
                    <div class="flex justify-between items-center mb-5">
                        <span class="text-[10px] font-black text-slate-300 uppercase italic">#ORD-<?= $h['id'] ?></span>
                        <div class="px-4 py-1.5 rounded-full text-[8px] font-black uppercase tracking-widest bg-green-50 text-green-600 border border-green-100">
                            Delivered
                        </div>
                    </div>

                    <div class="mb-4 flex items-center gap-4 bg-slate-50 p-4 rounded-3xl">
                        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-orange-500 shadow-md border border-slate-100">
                            <i class="fa-solid fa-user-check text-xl"></i>
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <p class="text-[8px] font-black text-slate-400 uppercase leading-none mb-1">Customer</p>
                            <h3 class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($h['db_customer_name'] ?? 'Guest') ?></h3>
                        </div>
                    </div>

                    <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-100 mb-4">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-2 tracking-wider">
                            <i class="fa-solid fa-utensils mr-1 text-orange-500"></i> Delivered Items
                        </p>
                        <p class="text-xs font-bold text-slate-700 leading-relaxed">
                            <?= !empty($h['ordered_items']) ? htmlspecialchars($h['ordered_items']) : '<span class="text-slate-400 italic">No item logs</span>' ?>
                        </p>
                    </div>

                    <div class="space-y-3 mb-6 px-2">
                        <div class="flex items-center gap-3 text-slate-500">
                            <i class="fa-solid fa-calendar-check text-[10px] text-orange-500"></i>
                            <p class="text-[11px] font-bold"><?= date('D, M d, Y | h:i A', strtotime($h['order_date'])) ?></p>
                        </div>
                        <div class="flex items-start gap-3 text-slate-500">
                            <i class="fa-solid fa-map-pin text-[10px] text-orange-500 mt-1"></i>
                            <p class="text-[11px] font-medium leading-relaxed"><?= htmlspecialchars($h['address']) ?></p>
                        </div>
                    </div>

                    <div class="bg-slate-900 rounded-3xl p-5 flex justify-between items-center shadow-lg shadow-slate-200">
                        <div>
                            <p class="text-[8px] font-bold text-slate-500 uppercase mb-1">Your Earning (10%)</p>
                            <h4 class="text-xl font-black text-orange-400 italic tracking-tighter">LKR <?= number_format($h['total_amount'] * 0.10, 2) ?></h4>
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] font-bold text-slate-500 uppercase mb-1">Bill Amount</p>
                            <p class="text-md font-black text-white tracking-tighter">LKR <?= number_format($h['total_amount'], 0) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="text-center py-16 bg-white rounded-[3rem] border-2 border-dashed border-slate-100 shadow-inner">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                        <i class="fa-solid fa-box-open text-3xl"></i>
                    </div>
                    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest italic">No history found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-lg border-t border-slate-100 p-4 flex justify-around items-center z-50">
        <a href="index.php" class="text-slate-400 flex flex-col items-center gap-1">
            <i class="fa-solid fa-house-chimney text-lg"></i>
            <span class="text-[8px] font-black uppercase italic">Home</span>
        </a>
        <a href="history.php" class="text-orange-500 flex flex-col items-center gap-1">
            <i class="fa-solid fa-clock-rotate-left text-lg"></i>
            <span class="text-[8px] font-black uppercase italic">History</span>
        </a>
        <a href="earnings.php" class="text-slate-400 flex flex-col items-center gap-1">
            <i class="fa-solid fa-wallet text-lg"></i>
            <span class="text-[8px] font-black uppercase italic">Earnings</span>
        </a>
    </div>

</body>
</html>