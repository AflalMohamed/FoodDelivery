<?php
require_once '../config/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Rider Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: ../login.php");
    exit;
}

$rider_id = $_SESSION['user_id'];
$today = date('Y-m-d');

try {
    $settings_stmt = $conn->query("SELECT site_name FROM site_settings LIMIT 1");
    $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
    $site_name = $settings['site_name'] ?? 'FCA FOOD';

    // 2. Fetch User Profile
    $u_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $u_stmt->execute([$rider_id]);
    $user = $u_stmt->fetch();

    // 3. STATS: Today's Completed Orders
    $today_stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM orders 
        WHERE rider_id = ? AND status = 'delivered' AND DATE(order_date) = ?
    ");
    $today_stmt->execute([$rider_id, $today]);
    $today_count = $today_stmt->fetch()['total'] ?? 0;

    // 4. STATS: Lifetime Completed Orders
    $lifetime_stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM orders 
        WHERE rider_id = ? AND status = 'delivered'
    ");
    $lifetime_stmt->execute([$rider_id]);
    $lifetime_count = $lifetime_stmt->fetch()['total'] ?? 0;

    // 5. FETCH ASSIGNED: Query using 'p.food_name' (BUG FIXED)
    $new_tasks_stmt = $conn->prepare("
        SELECT o.*, u.name as customer, u.phone as customer_phone, v.shop_name,
               (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.food_name) SEPARATOR ', ') 
                FROM order_items oi 
                INNER JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = o.id) as ordered_items
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN vendors v ON o.vendor_id = v.id
        WHERE o.rider_id = ? AND o.status = 'assigned'
        ORDER BY o.order_date DESC
    ");
    $new_tasks_stmt->execute([$rider_id]);
    $new_tasks = $new_tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. FETCH ACTIVE: Query using 'p.food_name' (BUG FIXED)
    $active_stmt = $conn->prepare("
        SELECT o.*, u.name as customer, u.phone as customer_phone, v.shop_name,
               (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.food_name) SEPARATOR ', ') 
                FROM order_items oi 
                INNER JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = o.id) as ordered_items
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN vendors v ON o.vendor_id = v.id
        WHERE o.rider_id = ? AND o.status = 'out_for_delivery'
        ORDER BY o.order_date DESC
    ");
    $active_stmt->execute([$rider_id]);
    $active_tasks = $active_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Rider Dashboard | <?= $site_name ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fbfcfe; }
        .orange-gradient { background: linear-gradient(135deg, #ff8c00 0%, #ff4500 100%); }
        .task-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .status-pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body class="pb-32">

    <?php include 'includes/sidebar.php'; ?>

    <div class="orange-gradient pt-12 pb-32 px-6 rounded-b-[4rem] shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
        
        <div class="flex justify-between items-center relative z-10 mb-8">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-white border border-white/30">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <div>
                    <p class="text-white/80 text-[10px] font-black uppercase tracking-[0.2em] mb-0.5">Active Session</p>
                    <h1 class="text-white text-xl font-black tracking-tight">Hi, <?= explode(' ', $user['name'])[0] ?>!</h1>
                </div>
            </div>
            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-orange-600 shadow-xl">
                <i class="fa-solid fa-motorcycle text-xl"></i>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 relative z-10">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-[2rem] flex items-center gap-4">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-orange-600 shadow-lg">
                    <i class="fa-solid fa-calendar-check text-xs"></i>
                </div>
                <div>
                    <p class="text-[8px] text-white/70 font-black uppercase tracking-widest mb-1">Today</p>
                    <p class="text-white font-black text-lg leading-none"><?= $today_count ?></p>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-[2rem] flex items-center gap-4">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-orange-600 shadow-lg">
                    <i class="fa-solid fa-medal text-xs"></i>
                </div>
                <div>
                    <p class="text-[8px] text-white/70 font-black uppercase tracking-widest mb-1">Lifetime</p>
                    <p class="text-white font-black text-lg leading-none"><?= $lifetime_count ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-md mx-auto px-5 -mt-10 relative z-20">
        
        <div class="mb-10">
            <div class="flex items-center justify-between mb-5 px-2">
                <h2 class="text-lg font-black text-slate-800 tracking-tight">New Assignments</h2>
                <span class="bg-orange-600 text-white text-[10px] px-3 py-1 rounded-lg font-black"><?= count($new_tasks) ?> NEW</span>
            </div>

            <?php if(!empty($new_tasks)): ?>
                <div class="space-y-5">
                    <?php foreach($new_tasks as $task): ?>
                        <div class="task-card bg-white rounded-[2.8rem] p-7 shadow-xl shadow-orange-100/40 border border-slate-50">
                            <div class="flex justify-between items-center mb-4">
                                <span class="bg-orange-50 text-orange-600 text-[9px] font-black px-3 py-1 rounded-full uppercase border border-orange-100">Pending</span>
                                <span class="text-slate-300 font-bold text-[10px]">#<?= $task['id'] ?></span>
                            </div>
                            
                            <h3 class="text-xl font-black text-slate-900 mb-1"><?= htmlspecialchars($task['shop_name'] ?? 'Restaurant') ?></h3>
                            <p class="text-xs font-bold text-orange-500 mb-4">LKR <?= number_format($task['total_amount'], 2) ?></p>
                            
                            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 mb-4">
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-2 tracking-wider"><i class="fa-solid fa-utensils mr-1"></i> Order Items</p>
                                <p class="text-xs font-bold text-slate-700 leading-relaxed">
                                    <?= !empty($task['ordered_items']) ? htmlspecialchars($task['ordered_items']) : '<span class="text-slate-400 italic">No items found</span>' ?>
                                </p>
                            </div>

                            <div class="space-y-3 mb-6">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400 shrink-0 mt-1">
                                        <i class="fa-solid fa-location-dot text-sm"></i>
                                    </div>
                                    <p class="text-slate-600 text-xs font-semibold leading-relaxed pt-1">
                                        <?= htmlspecialchars($task['address']) ?>
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center text-orange-500 shrink-0">
                                        <i class="fa-solid fa-phone text-xs"></i>
                                    </div>
                                    <a href="tel:<?= $task['customer_phone'] ?>" class="text-orange-600 text-sm font-black tracking-wide">
                                        <?= htmlspecialchars($task['customer_phone']) ?>
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 bg-slate-50/80 p-4 rounded-3xl mb-6 border border-slate-100">
                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-orange-500 shadow-sm">
                                    <i class="fa-solid fa-user-check text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[8px] font-black text-slate-400 uppercase leading-none mb-1">Customer Name</p>
                                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($task['customer']) ?></p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <a href="update-status.php?id=<?= $task['id'] ?>&status=out_for_delivery" class="flex-grow orange-gradient text-white text-center py-4 rounded-2xl font-black text-[11px] uppercase tracking-widest shadow-lg shadow-orange-200">
                                    Accept Pickup
                                </a>
                                <a href="tel:<?= $task['customer_phone'] ?>" class="w-14 bg-slate-900 text-white flex items-center justify-center rounded-2xl">
                                    <i class="fa-solid fa-phone-flip"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-[3rem] p-12 text-center border-2 border-dashed border-slate-100">
                    <i class="fa-solid fa-box-open text-slate-100 text-5xl mb-4"></i>
                    <p class="text-slate-400 font-bold text-[9px] uppercase tracking-widest">No orders assigned right now</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if(!empty($active_tasks)): ?>
        <div class="mb-10">
            <h2 class="text-lg font-black text-slate-800 tracking-tight mb-5 px-2">Out for Delivery</h2>
            <div class="space-y-5">
                <?php foreach($active_tasks as $active): ?>
                    <div class="bg-slate-900 rounded-[2.8rem] p-7 shadow-2xl relative overflow-hidden">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-500 rounded-full status-pulse"></span>
                                <p class="text-orange-500 text-[9px] font-black uppercase tracking-[0.2em]">Live Tracking</p>
                            </div>
                            <span class="text-white/40 font-bold text-[10px]">#<?= $active['id'] ?></span>
                        </div>
                        
                        <h3 class="text-white text-xl font-black mb-1 uppercase tracking-tight"><?= htmlspecialchars($active['customer']) ?></h3>
                        <p class="text-sm font-black text-orange-500 mb-4">LKR <?= number_format($active['total_amount'], 2) ?></p>

                        <div class="bg-white/5 p-4 rounded-2xl border border-white/10 mb-6">
                            <p class="text-[9px] font-black text-slate-400 uppercase mb-2 tracking-wider"><i class="fa-solid fa-utensils mr-1"></i> Carrying Items</p>
                            <p class="text-xs font-bold text-slate-200 leading-relaxed">
                                <?= !empty($active['ordered_items']) ? htmlspecialchars($active['ordered_items']) : '<span class="text-slate-500 italic">No items found</span>' ?>
                            </p>
                        </div>
                        
                        <div class="space-y-4 mb-8">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-map-location-dot text-white/40 text-lg mt-1"></i>
                                <p class="text-slate-400 text-xs leading-relaxed"><?= htmlspecialchars($active['address']) ?></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-mobile-screen-button text-orange-500 text-lg"></i>
                                <a href="tel:<?= $active['customer_phone'] ?>" class="text-white font-bold text-sm tracking-widest underline decoration-orange-500 underline-offset-4">
                                    <?= htmlspecialchars($active['customer_phone']) ?>
                                </a>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <a href="update-status.php?id=<?= $active['id'] ?>&status=delivered" class="flex-grow bg-white text-slate-900 text-center py-4 rounded-2xl font-black text-[11px] uppercase tracking-widest shadow-lg">
                                Delivered Successfully
                            </a>
                            <a href="https://wa.me/<?= $active['customer_phone'] ?>" class="w-14 bg-green-500 text-white flex items-center justify-center rounded-2xl">
                                <i class="fa-brands fa-whatsapp text-2xl"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-xl border-t border-slate-50 p-5 flex justify-around items-center z-50 rounded-t-[2.5rem] shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">
        <a href="index.php" class="text-orange-600 flex flex-col items-center gap-1">
            <i class="fa-solid fa-house-chimney text-xl"></i>
            <span class="text-[8px] font-black uppercase">Home</span>
        </a>
        <a href="history.php" class="text-slate-300 flex flex-col items-center gap-1">
            <i class="fa-solid fa-receipt text-xl"></i>
            <span class="text-[8px] font-black uppercase">History</span>
        </a>
        <a href="earnings.php" class="text-slate-300 flex flex-col items-center gap-1">
            <i class="fa-solid fa-wallet text-xl"></i>
            <span class="text-[8px] font-black uppercase">Earnings</span>
        </a>
    </div>

</body>
</html>