<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

$order_id = $_GET['id'] ?? null;
if (!$order_id) { header("Location: orders.php"); exit; }

$message = "";

// 1. Handle Status Update
if (isset($_POST['update_status'])) {
    $new_status = cleanInput($_POST['order_status']);
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $order_id])) {
        $message = "Status updated successfully!";
    }
}

// 2. Fetch Order, Customer, and Rider Data
try {
    $order_query = $conn->prepare("
        SELECT o.*, 
               u.name as customer_name, u.email as customer_email,
               r.name as rider_name, r.phone as rider_phone
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN users r ON o.rider_id = r.id
        WHERE o.id = ?
    ");
    $order_query->execute([$order_id]);
    $order = $order_query->fetch(PDO::FETCH_ASSOC);

    if (!$order) { die("Order not found."); }

    // 3. Fetch Order Items
    $items_query = $conn->prepare("
        SELECT oi.*, p.food_name, p.food_image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $items_query->execute([$order_id]);
    $items = $items_query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

function getStatusColor($status) {
    return match(strtolower($status)) {
        'pending'   => 'bg-orange-100 text-orange-600 border-orange-200',
        'assigned'  => 'bg-blue-100 text-blue-600 border-blue-200',
        'delivered' => 'bg-emerald-100 text-emerald-600 border-emerald-200',
        'cancelled' => 'bg-rose-100 text-rose-600 border-rose-200',
        default     => 'bg-slate-100 text-slate-600 border-slate-200',
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Details #<?= $order_id ?> | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#f8fafc]">

    <?php include 'includes/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen">
        <main class="max-w-5xl mx-auto p-6 md:p-12">
            
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12" data-aos="fade-down">
                <div>
                    <h1 class="text-4xl font-extrabold text-slate-900 tracking-tighter">
                        #ORD-<span class="text-orange-600 italic"><?= $order['id'] ?></span>
                    </h1>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mt-2">
                        Placed: <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-6 py-3 rounded-2xl border text-[10px] font-black uppercase tracking-[0.2em] shadow-sm <?= getStatusColor($order['status']) ?>">
                        <?= $order['status'] ?>
                    </span>
                </div>
            </header>

            <?php if($message): ?>
                <div class="mb-8 p-4 bg-emerald-50 border border-emerald-100 text-emerald-600 text-xs font-bold rounded-2xl">
                    <i class="fa-solid fa-circle-check mr-2"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <div class="lg:col-span-8 space-y-8" data-aos="fade-right">
                    <div class="bg-white border border-slate-100 rounded-[2.5rem] overflow-hidden shadow-sm">
                        <div class="px-8 py-6 bg-slate-50 border-b border-slate-100">
                            <h3 class="font-black text-slate-800 uppercase text-[10px] tracking-widest">Items Ordered</h3>
                        </div>
                        
                        <div class="divide-y divide-slate-50 px-8">
                            <?php foreach($items as $item): ?>
                            <div class="py-6 flex items-center gap-6">
                                <div class="w-20 h-20 rounded-3xl overflow-hidden bg-slate-100 flex-shrink-0 border border-slate-200">
                                    <img src="../assets/img/<?= htmlspecialchars($item['food_image']) ?>" 
                                         class="w-full h-full object-cover" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($item['food_name']) ?>&background=f1f5f9&color=cbd5e1'">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-base font-black text-slate-800 truncate"><?= $item['food_name'] ?></h4>
                                    <p class="text-xs text-slate-400 font-bold mt-1 uppercase">
                                        LKR <?= number_format($item['price_at_time'], 2) ?> × <?= $item['quantity'] ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-base font-black text-slate-900">LKR <?= number_format($item['quantity'] * $item['price_at_time'], 2) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="p-8 bg-slate-900 text-white space-y-4">
                            <div class="flex justify-between text-[10px] font-black uppercase tracking-widest opacity-60">
                                <span>Subtotal</span>
                                <span>LKR <?= number_format($order['subtotal'], 2) ?></span>
                            </div>
                            <div class="flex justify-between text-[10px] font-black uppercase tracking-widest opacity-60">
                                <span>Delivery Fee</span>
                                <span>LKR <?= number_format($order['delivery_fee'], 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-4 border-t border-white/10">
                                <span class="font-black uppercase tracking-[0.3em] text-[10px] text-orange-500">Total Amount</span>
                                <span class="text-3xl font-black tracking-tighter">LKR <?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-[2.5rem] p-8 shadow-sm">
                        <h3 class="font-black text-slate-800 uppercase text-[10px] tracking-widest mb-6">Delivery Address</h3>
                        <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 flex gap-4">
                            <i class="fa-solid fa-map-location-dot text-slate-300 mt-1"></i>
                            <p class="text-sm text-slate-600 leading-relaxed font-bold">
                                <?= nl2br(htmlspecialchars($order['address'])) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4 space-y-8" data-aos="fade-left">
                    
                    <div class="bg-white border-2 border-slate-900 rounded-[2.5rem] p-8 shadow-xl">
                        <h3 class="font-black text-slate-900 uppercase text-[10px] tracking-widest mb-6">Action Center</h3>
                        <form method="POST" class="space-y-4">
                            <select name="order_status" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 text-xs font-black text-slate-800 outline-none focus:border-orange-500 transition-colors">
                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="assigned" <?= $order['status'] == 'assigned' ? 'selected' : '' ?>>Assigned</option>
                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="w-full bg-slate-900 text-white font-black py-4 rounded-2xl text-[10px] uppercase tracking-widest hover:bg-orange-600 transition-all active:scale-95 shadow-lg shadow-slate-200">
                                Update Order
                            </button>
                        </form>
                    </div>

                    <div class="bg-blue-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-blue-100">
                        <h3 class="font-black uppercase text-[9px] tracking-widest mb-6 opacity-70">Delivery Rider</h3>
                        <?php if($order['rider_name']): ?>
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-xl">
                                    <i class="fa-solid fa-person-biking"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-sm"><?= htmlspecialchars($order['rider_name']) ?></h4>
                                    <p class="text-[10px] font-bold opacity-60 uppercase tracking-tighter">Assigned Personnel</p>
                                </div>
                            </div>
                            <a href="tel:<?= $order['rider_phone'] ?>" class="flex items-center justify-center gap-2 w-full bg-white text-blue-600 font-black py-3 rounded-xl text-[10px] uppercase tracking-widest hover:bg-blue-50 transition-colors">
                                <i class="fa-solid fa-phone"></i> Call Rider
                            </a>
                        <?php else: ?>
                            <div class="py-4 px-6 border border-white/20 rounded-2xl bg-white/10 text-center">
                                <p class="text-[10px] font-black uppercase tracking-widest opacity-80">No Rider Assigned</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-[2.5rem] p-8 shadow-sm">
                        <h3 class="font-black text-slate-400 uppercase text-[9px] tracking-widest mb-8">Customer Details</h3>
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-14 h-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl font-black">
                                <?= strtoupper(substr($order['customer_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div>
                                <h4 class="font-black text-slate-800 text-sm"><?= htmlspecialchars($order['customer_name']) ?></h4>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Verified User</p>
                            </div>
                        </div>
                        <a href="tel:<?= $order['phone'] ?>" class="flex items-center justify-center gap-2 w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-black text-slate-700 hover:bg-orange-50 hover:text-orange-600 transition-all">
                            <i class="fa-solid fa-mobile-screen-button"></i> <?= $order['phone'] ?>
                        </a>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init({ duration: 800, once: true });</script>
</body>
</html>