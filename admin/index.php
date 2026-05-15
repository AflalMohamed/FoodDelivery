<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

// --- 1. Core Analytics ---
$pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$totalRevenue = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status = 'delivered'")->fetchColumn() ?? 0;
$totalRiders = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'rider'")->fetchColumn();
$activeShops = $conn->query("SELECT COUNT(*) FROM vendors WHERE is_active = 1")->fetchColumn();

// --- 2. Top Products (Sales Volume) ---
$topProducts = $conn->query("
    SELECT p.food_name, COUNT(oi.product_id) as total_sold 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    GROUP BY p.id 
    ORDER BY total_sold DESC 
    LIMIT 3
")->fetchAll();

// --- 3. Top Customers (Order Frequency) ---
$topCustomers = $conn->query("
    SELECT u.name, COUNT(o.id) as order_count 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    GROUP BY u.id 
    ORDER BY order_count DESC 
    LIMIT 3
")->fetchAll();

// --- 4. Rider of the Month (Performance Leader) ---
$topRider = $conn->query("
    SELECT u.name, COUNT(o.id) as completed_tasks 
    FROM orders o 
    JOIN users u ON o.rider_id = u.id 
    WHERE o.status = 'delivered' 
    AND MONTH(o.order_date) = MONTH(CURRENT_DATE())
    AND YEAR(o.order_date) = YEAR(CURRENT_DATE())
    GROUP BY u.id 
    ORDER BY completed_tasks DESC 
    LIMIT 1
")->fetch();

// --- 5. Global Activity Log ---
$recentOrders = $conn->query("
    SELECT o.id, u.name as customer, o.total_amount, o.status, o.order_date 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC 
    LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin HQ | System Overview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.02em; }
        .stats-grid { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .leader-gradient { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }
        .orange-glow:hover { box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.2); }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-900">

    <?php include 'includes/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen transition-all duration-300">
        <main class="p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto">
            
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12" data-aos="fade-down">
                <div>
                    <h1 class="text-4xl font-extrabold tracking-tighter text-slate-900">
                        Operational <span class="text-orange-500">HQ</span>
                    </h1>
                    <div class="flex items-center gap-3 mt-2">
                        <div class="flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider">System Live</span>
                        </div>
                        <p class="text-slate-400 text-xs font-medium italic">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
                    </div>
                </div>
                
                <div class="flex items-center bg-white shadow-sm border border-slate-100 p-2 rounded-2xl">
                    <div class="bg-orange-500 text-white w-10 h-10 rounded-xl flex items-center justify-center shadow-lg shadow-orange-100">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="px-4">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Server Date</p>
                        <p class="text-sm font-bold text-slate-700 mt-1"><?= date('D, d M Y') ?></p>
                    </div>
                </div>
            </header>

            <div class="grid stats-grid gap-6 mb-10">
                <div class="bg-white p-7 rounded-[2rem] border border-slate-100 shadow-sm orange-glow transition-all" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center text-xl">
                            <i class="fa-solid fa-fire-burner"></i>
                        </div>
                        <span class="text-[10px] font-black text-orange-400 bg-orange-50 px-2 py-1 rounded-lg uppercase">Action Required</span>
                    </div>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Pending Orders</p>
                    <h3 id="pending-count" class="text-4xl font-extrabold text-slate-900 mt-1"><?= $pendingOrders ?></h3>
                </div>

                <div class="bg-white p-7 rounded-[2rem] border border-slate-100 shadow-sm transition-all" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center text-xl mb-4">
                        <i class="fa-solid fa-vault"></i>
                    </div>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Total Revenue</p>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-1">LKR <?= number_format($totalRevenue, 0) ?></h3>
                </div>

                <div class="bg-white p-7 rounded-[2rem] border border-slate-100 shadow-sm transition-all" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-xl mb-4">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Active Fleet</p>
                    <h3 class="text-4xl font-extrabold text-slate-900 mt-1"><?= $totalRiders ?></h3>
                </div>

                <div class="leader-gradient p-7 rounded-[2rem] shadow-2xl text-white relative overflow-hidden transition-all hover:scale-[1.02]" data-aos="fade-up" data-aos-delay="400">
                    <div class="relative z-10">
                        <div class="w-12 h-12 bg-orange-500 text-white rounded-2xl flex items-center justify-center text-xl mb-4 shadow-lg">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Champion Rider</p>
                        <h3 class="text-xl font-bold mt-1 truncate"><?= $topRider ? $topRider['name'] : 'N/A' ?></h3>
                        <p class="text-[10px] text-orange-400 font-black mt-1"><?= $topRider ? $topRider['completed_tasks'].' DELIVERIES THIS MONTH' : 'NO DATA' ?></p>
                    </div>
                    <i class="fa-solid fa-medal absolute -right-4 -bottom-4 text-8xl text-white/5 rotate-12"></i>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100" data-aos="fade-right">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-8 flex items-center gap-3">
                        <span class="w-1.5 h-6 bg-orange-500 rounded-full"></span> Top Products
                    </h3>
                    <div class="space-y-5">
                        <?php foreach($topProducts as $idx => $prod): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50/50 rounded-2xl hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-4">
                                <span class="text-xs font-black text-slate-300">0<?= $idx+1 ?></span>
                                <p class="text-sm font-bold text-slate-700"><?= $prod['food_name'] ?></p>
                            </div>
                            <span class="text-[10px] font-black px-3 py-1 bg-white border border-slate-100 text-slate-500 rounded-lg shadow-sm"><?= $prod['total_sold'] ?> Sold</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100" data-aos="fade-up">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-8 flex items-center gap-3">
                        <span class="w-1.5 h-6 bg-blue-500 rounded-full"></span> VIP Customers
                    </h3>
                    <div class="space-y-5">
                        <?php foreach($topCustomers as $idx => $cust): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50/50 rounded-2xl hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-xs font-black text-slate-400 shadow-sm">
                                    <?= strtoupper(substr($cust['name'], 0, 1)) ?>
                                </div>
                                <p class="text-sm font-bold text-slate-700"><?= $cust['name'] ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-slate-900"><?= $cust['order_count'] ?> Orders</p>
                                <p class="text-[8px] font-bold text-emerald-500 uppercase">Loyal Member</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden" data-aos="fade-left">
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Recent Activity</h3>
                        <a href="orders.php" class="text-[10px] font-black text-orange-500 hover:underline">View All</a>
                    </div>
                    <div class="p-2">
                        <?php foreach($recentOrders as $order): ?>
                        <div class="group flex items-center justify-between p-5 hover:bg-slate-50 rounded-3xl transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-2 h-2 rounded-full <?= ($order['status'] == 'pending') ? 'bg-orange-400' : 'bg-emerald-400' ?>"></div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 uppercase">#<?= $order['id'] ?> <?= htmlspecialchars($order['customer']) ?></p>
                                    <p class="text-[9px] text-slate-400 font-bold"><?= date('h:i A, d M', strtotime($order['order_date'])) ?></p>
                                </div>
                            </div>
                            <p class="text-[10px] font-black text-slate-900 group-hover:text-orange-500 transition-colors">LKR <?= number_format($order['total_amount']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });

        function checkNewOrders() {
            fetch('check_new_orders.php')
                .then(response => response.json())
                .then(data => {
                    if (data.new_order) {
                        const countEl = document.getElementById('pending-count');
                        if (countEl) countEl.innerText = parseInt(countEl.innerText) + 1;

                        Swal.fire({
                            title: 'System Alert: New Order! 🛍️',
                            text: 'Order #' + data.order_id + ' requires your attention.',
                            icon: 'info',
                            confirmButtonColor: '#f97316',
                            confirmButtonText: 'Review Order',
                            backdrop: `rgba(15, 23, 42, 0.4)`
                        }).then((result) => {
                            if (result.isConfirmed) window.location.href = 'order-details.php?id=' + data.order_id;
                        });
                    }
                })
                .catch(err => console.error('Poller Error:', err));
        }

        setInterval(checkNewOrders, 5000);
    </script>
</body>
</html>