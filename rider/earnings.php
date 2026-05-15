<?php
require_once '../config/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: ../login.php");
    exit;
}

$rider_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Get Date Filters
$from_date = $_GET['from_date'] ?? date('Y-m-01'); 
$to_date = $_GET['to_date'] ?? date('Y-m-d');    

try {
    $settings = $conn->query("SELECT site_name FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $site_display_name = $settings['site_name'] ?? 'FCA FOOD';

    // 1. Calculate TODAY'S Earnings specifically
    $today_stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(total_amount * 0.10), 0) as earned,
            COUNT(id) as count
        FROM orders 
        WHERE rider_id = ? AND status = 'delivered' 
        AND DATE(order_date) = ?
    ");
    $today_stmt->execute([$rider_id, $today]);
    $today_stats = $today_stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Calculate FILTERED Earnings (based on date picker)
    $stats_stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(total_amount * 0.10), 0) as filtered_earned,
            COUNT(id) as total_deliveries
        FROM orders 
        WHERE rider_id = ? AND status = 'delivered' 
        AND DATE(order_date) BETWEEN ? AND ?
    ");
    $stats_stmt->execute([$rider_id, $from_date, $to_date]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Fetch Statement History
    $stmt = $conn->prepare("
        SELECT id, order_date, total_amount, (total_amount * 0.10) as commission 
        FROM orders 
        WHERE rider_id = ? AND status = 'delivered' 
        AND DATE(order_date) BETWEEN ? AND ?
        ORDER BY order_date DESC
    ");
    $stmt->execute([$rider_id, $from_date, $to_date]);
    $earnings_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Earnings | <?= htmlspecialchars($site_display_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .gradient-orange { background: linear-gradient(135deg, #ff8c00 0%, #ff4500 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        input[type="date"] { background: transparent; border: none; font-weight: 800; color: #1e293b; outline: none; width: 100%; font-size: 0.85rem; }
    </style>
</head>
<body class="pb-28">

    <?php include 'includes/sidebar.php'; ?>

    <div class="gradient-orange pb-32 pt-10 px-6 rounded-b-[3.5rem] shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
        
        <nav class="flex justify-between items-center mb-10 relative z-10">
            <button onclick="toggleSidebar()" class="w-12 h-12 bg-white/20 backdrop-blur-md text-white rounded-2xl flex items-center justify-center border border-white/30 active:scale-90 transition-all">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
            <div class="text-center">
                <h1 class="text-white font-black text-xl italic uppercase tracking-tighter">Finance Hub</h1>
            </div>
            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-orange-600 shadow-xl">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </nav>

        <div class="glass-effect p-6 rounded-[2.5rem] relative z-10">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-white/80 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Today's Earnings</p>
                    <h2 class="text-4xl font-black text-white tracking-tighter">
                        LKR <?= number_format($today_stats['earned'], 2) ?>
                    </h2>
                </div>
                <div class="bg-white/20 px-3 py-1 rounded-full text-white text-[10px] font-black">
                    <?= $today_stats['count'] ?> Orders
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-md mx-auto px-5 -mt-16 relative z-20">
        
        <div class="bg-white p-6 rounded-[2.5rem] shadow-xl border border-slate-50 mb-8">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-1.5 h-4 bg-orange-500 rounded-full"></div>
                <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Filtered Report</h3>
            </div>
            
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h4 class="text-2xl font-black text-slate-900 tracking-tighter">LKR <?= number_format($stats['filtered_earned'], 2) ?></h4>
                    <p class="text-[9px] font-bold text-slate-400 italic"><?= $stats['total_deliveries'] ?> total deliveries in this period</p>
                </div>
                <div class="text-right">
                    <p class="text-[8px] font-black text-orange-500 uppercase"><?= date('M d', strtotime($from_date)) ?> - <?= date('M d', strtotime($to_date)) ?></p>
                </div>
            </div>

            <form method="GET" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 p-3 px-4 rounded-2xl border border-slate-100">
                        <label class="block text-[7px] font-black text-slate-400 uppercase mb-0.5">From</label>
                        <input type="date" name="from_date" value="<?= $from_date ?>">
                    </div>
                    <div class="bg-slate-50 p-3 px-4 rounded-2xl border border-slate-100">
                        <label class="block text-[7px] font-black text-slate-400 uppercase mb-0.5">To</label>
                        <input type="date" name="to_date" value="<?= $to_date ?>">
                    </div>
                </div>
                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-lg active:scale-95 transition-all">
                    Generate Report
                </button>
            </form>
        </div>

        <h3 class="text-xs font-black text-slate-800 uppercase italic tracking-widest mb-5 ml-2 flex items-center gap-2">
            <i class="fa-solid fa-list-ul text-orange-500 text-[10px]"></i> Detailed Statement
        </h3>

        <div class="space-y-4">
            <?php if(!empty($earnings_list)): foreach($earnings_list as $row): ?>
                <div class="bg-white p-5 rounded-[2.2rem] border border-slate-50 shadow-sm flex justify-between items-center hover:border-orange-200 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400">
                            <i class="fa-solid fa-receipt text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-slate-900 italic">#ORD-<?= $row['id'] ?></p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">
                                <?= date('d M', strtotime($row['order_date'])) ?> • <?= date('h:i A', strtotime($row['order_date'])) ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] font-black text-slate-300 uppercase leading-none mb-1">Commission</p>
                        <p class="text-sm font-black text-orange-500 italic tracking-tighter">+<?= number_format($row['commission'], 2) ?></p>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="text-center py-12 bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                    <i class="fa-solid fa-folder-open text-slate-100 text-4xl mb-3"></i>
                    <p class="text-slate-300 font-bold text-[9px] uppercase tracking-widest italic">No records for this period</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-8 text-center pb-10">
            <a href="earnings.php" class="text-[9px] font-black text-slate-400 uppercase tracking-widest hover:text-orange-500 transition-colors underline decoration-dotted underline-offset-4">Reset View</a>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-xl border-t border-slate-100 p-5 flex justify-around items-center z-50 rounded-t-[2.5rem]">
        <a href="index.php" class="text-slate-400 flex flex-col items-center gap-1">
            <i class="fa-solid fa-house-chimney text-xl"></i>
            <span class="text-[8px] font-black uppercase italic">Home</span>
        </a>
        <a href="history.php" class="text-slate-400 flex flex-col items-center gap-1">
            <i class="fa-solid fa-clock-rotate-left text-xl"></i>
            <span class="text-[8px] font-black uppercase italic">History</span>
        </a>
        <a href="earnings.php" class="text-orange-500 flex flex-col items-center gap-1">
            <div class="w-1 h-1 bg-orange-500 rounded-full mb-1"></div>
            <i class="fa-solid fa-wallet text-xl"></i>
            <span class="text-[8px] font-black uppercase italic">Earnings</span>
        </a>
    </div>

</body>
</html>