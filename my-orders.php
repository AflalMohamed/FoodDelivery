<?php 
include 'config/config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get Filter inputs
$filter_month = $_GET['month'] ?? '';
$filter_year = $_GET['year'] ?? '';

// --- HANDLE ORDER CANCELLATION ENGINE WITH POP-UP SAFARI/CHROME FIX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $cancel_order_id = intval($_POST['order_id']);
    
    try {
        // Fetch order details along with individual item details for exact admin receipt breakdown mapping
        $chk_stmt = $conn->prepare("
            SELECT o.status, o.total_amount, o.delivery_fee, o.subtotal, o.address, o.phone, u.name as customer_name
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ? AND o.user_id = ?
        ");
        $chk_stmt->execute([$cancel_order_id, $user_id]);
        $order_to_cancel = $chk_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order_to_cancel && $order_to_cancel['status'] === 'pending') {
            
            // Fetch items inside this order to display inside the text template array loop
            $items_stmt = $conn->prepare("
                SELECT oi.quantity, p.food_name 
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $items_stmt->execute([$cancel_order_id]);
            $order_items_list = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

            $update_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            if ($update_stmt->execute([$cancel_order_id])) {
                
                // Fetch admin_whatsapp dynamically based on your schema structure
                $meta_stmt = $conn->query("SELECT site_name, admin_whatsapp FROM site_settings LIMIT 1");
                $meta = $meta_stmt->fetch(PDO::FETCH_ASSOC);
                
                $admin_phone = !empty($meta['admin_whatsapp']) ? preg_replace('/[^0-9]/', '', $meta['admin_whatsapp']) : '94771234567';
                $site_name = $meta['site_name'] ?? 'FCA FOOD';
                
                // --- ADMIN ORDER PATTERN BREAKDOWN FOR CANCELLATION ---
                $wa_msg = "❌ *ORDER CANCELLED* ❌\n\n";
                $wa_msg .= "An order has been cancelled by the customer. Please review the details below:\n\n";
                $wa_msg .= "🆔 *Order ID:* #ORD-" . $cancel_order_id . "\n";
                $wa_msg .= "👤 *Customer:* " . $order_to_cancel['customer_name'] . "\n";
                $wa_msg .= "📞 *Phone:* " . $order_to_cancel['phone'] . "\n";
                $wa_msg .= "📍 *Delivery Address:* " . $order_to_cancel['address'] . "\n\n";
                
                $wa_msg .= "📦 *Cancelled Items:* \n";
                foreach ($order_items_list as $item) {
                    $wa_msg .= " - " . $item['quantity'] . "x " . $item['food_name'] . "\n";
                }
                
                $wa_msg .= "\n----------------------------------\n";
                $wa_msg .= "💵 *Subtotal:* LKR " . number_format($order_to_cancel['subtotal'], 2) . "\n";
                $wa_msg .= "🚴 *Delivery Fee:* LKR " . number_format($order_to_cancel['delivery_fee'], 2) . "\n";
                $wa_msg .= "💰 *Total Amount Saved/Refund:* LKR " . number_format($order_to_cancel['total_amount'], 2) . "\n";
                $wa_msg .= "----------------------------------\n\n";
                $wa_msg .= "🛑 *Status:* Cancelled (Dashboard Sync Done)";
                
                $encoded_msg = rawurlencode($wa_msg);
                $whatsapp_url = "https://wa.me/" . $admin_phone . "?text=" . $encoded_msg;
                
                // FIXED REDIRECT: window.open moolama popup block aahurathaala direct href-layee redirect panrom
                echo "<script>
                    alert('Order #ORD-{$cancel_order_id} has been cancelled! Redirecting to WhatsApp to notify Admin...');
                    window.location.href = '{$whatsapp_url}';
                </script>";
                exit;
            }
        } else {
            echo "<script>alert('Error: You can only cancel orders that are still Pending!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Error execution failed: " . addslashes($e->getMessage()) . "');</script>";
    }
}

try {
    // 1. Fetch distinct months and years for the filter dropdown
    $date_query = "SELECT DISTINCT YEAR(order_date) as y, MONTH(order_date) as m FROM orders WHERE user_id = ? ORDER BY y DESC, m DESC";
    $date_stmt = $conn->prepare($date_query);
    $date_stmt->execute([$user_id]);
    $available_dates = $date_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Build the main query with filters
    $query = "
        SELECT 
            o.id as order_id,
            o.total_amount,
            o.status,
            o.order_date,
            o.rider_id,
            oi.quantity,
            oi.price_at_time,
            p.food_name,
            p.food_image,
            r.name as rider_name,
            r.phone as rider_phone
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN users r ON o.rider_id = r.id
        WHERE o.user_id = ?
    ";

    $params = [$user_id];
    if ($filter_month != '') {
        $query .= " AND MONTH(o.order_date) = ?";
        $params[] = $filter_month;
    }
    if ($filter_year != '') {
        $query .= " AND YEAR(o.order_date) = ?";
        $params[] = $filter_year;
    }

    $query .= " ORDER BY o.order_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    foreach ($results as $row) {
        $oid = $row['order_id'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'order_id' => $row['order_id'],
                'total_amount' => $row['total_amount'],
                'status' => $row['status'],
                'order_date' => $row['order_date'],
                'rider_id' => $row['rider_id'],
                'rider_name' => $row['rider_name'],
                'rider_phone' => $row['rider_phone'],
                'items' => []
            ];
        }
        $orders[$oid]['items'][] = [
            'name' => $row['food_name'],
            'image' => $row['food_image'],
            'qty' => $row['quantity'],
            'price' => $row['price_at_time']
        ];
    }
} catch (PDOException $e) {
    $orders = [];
}

include 'includes/header.php'; 
?>

<style>
.order-card { border-radius:18px; transition: 0.3s; }
.order-header { cursor:pointer; }
.order-body { max-height:0; overflow:hidden; transition: max-height 0.4s ease-out; }
.order-card.active .order-body { max-height:1200px; }
.dot {
    width:26px; height:26px; border-radius:50%; border:3px solid #eee;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; font-weight:bold; background:white; position: relative; z-index: 2;
}
.dot.active { background:#f97316; border-color:#f97316; color:white; }
.filter-select {
    appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 1em;
}
</style>

<div class="min-h-screen flex flex-col bg-[#fff7ed] pb-10">
    <main class="flex-1 max-w-2xl mx-auto w-full p-4">

        <h1 class="text-3xl font-black mb-6 text-slate-900 italic">MY <span class="text-orange-600">ORDERS.</span></h1>

        <form method="GET" class="bg-white p-4 rounded-2xl shadow-sm mb-6 border border-orange-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Filter by Date</p>
            <div class="flex gap-2">
                <select name="month" class="filter-select flex-1 p-3 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-orange-400 outline-none">
                    <option value="">All Months</option>
                    <?php 
                    for($m=1; $m<=12; $m++) {
                        $monthName = date('F', mktime(0,0,0,$m, 1));
                        $sel = ($filter_month == $m) ? 'selected' : '';
                        echo "<option value='$m' $sel>$monthName</option>";
                    }
                    ?>
                </select>

                <select name="year" class="filter-select flex-1 p-3 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-orange-400 outline-none">
                    <option value="">All Years</option>
                    <?php 
                    $currentY = date('Y');
                    for($y=$currentY; $y>=($currentY-5); $y--) {
                        $sel = ($filter_year == $y) ? 'selected' : '';
                        echo "<option value='$y' $sel>$y</option>";
                    }
                    ?>
                </select>

                <button type="submit" class="bg-orange-600 text-white px-5 rounded-xl font-bold text-sm">Apply</button>
                <?php if($filter_month || $filter_year): ?>
                    <a href="my-orders.php" class="bg-gray-100 text-gray-500 px-4 flex items-center rounded-xl"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="relative mb-6">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="searchInput" placeholder="Search food name or Order ID..." 
                   class="w-full pl-12 p-4 rounded-2xl border-none shadow-sm focus:ring-2 focus:ring-orange-400 outline-none">
        </div>

        <?php if ($orders): ?>
            <?php foreach ($orders as $order): 
                $steps = ['pending','assigned','out_for_delivery','delivered'];
                $key = array_search($order['status'], $steps);
                $progress = ($key !== false) ? ($key/(count($steps)-1))*100 : 0;
                $mainItem = $order['items'][0];
                $imagePath = "assets/images/products/" . ($mainItem['image'] ?? '');
                if (empty($mainItem['image']) || !file_exists($imagePath)) { $imagePath = "assets/images/products/default-food.png"; }
            ?>

            <div class="order-card bg-white mb-5 shadow-sm border border-orange-50 order-item overflow-hidden">
                <div class="order-header p-4 flex justify-between items-center bg-white hover:bg-orange-50/30">
                    <div class="flex gap-4 items-center">
                        <img src="<?= $imagePath ?>" class="w-16 h-16 rounded-2xl object-cover border border-orange-50">
                        <div>
                            <h2 class="font-bold text-slate-800 leading-tight">
                                <?= htmlspecialchars($mainItem['name']) ?>
                                <?php if(count($order['items']) > 1): ?>
                                    <span class="text-xs text-orange-400 font-medium">+<?= count($order['items'])-1 ?> more</span>
                                <?php endif; ?>
                            </h2>
                            <p class="text-[11px] text-gray-400 mt-1 font-mono">
                                #<?= $order['order_id'] ?> • <?= date('M d, Y', strtotime($order['order_date'])) ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-black text-slate-900">LKR <?= number_format($order['total_amount'], 0) ?></p>
                        
                        <?php if($order['status'] === 'cancelled'): ?>
                            <span class="text-[9px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold uppercase">Cancelled</span>
                        <?php else: ?>
                            <span class="text-[9px] bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full font-bold uppercase"><?= $order['status'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-body px-4 pb-4">
                    
                    <?php if($order['status'] !== 'cancelled'): ?>
                    <div class="mt-4 mb-8 px-4">
                        <div class="flex justify-between relative">
                            <div class="absolute top-3 left-0 right-0 h-0.5 bg-gray-100"></div>
                            <div class="absolute top-3 left-0 h-0.5 bg-orange-500 transition-all duration-700" style="width:<?= $progress ?>%"></div>
                            <?php foreach ($steps as $i => $step): ?>
                                <div class="flex flex-col items-center">
                                    <div class="dot <?= ($i <= $key) ? 'active' : '' ?>">
                                        <?php if($i < $key): ?><i class="fa-solid fa-check text-[8px]"></i><?php else: ?><?= $i+1 ?><?php endif; ?>
                                    </div>
                                    <span class="text-[8px] font-black mt-2 uppercase <?= ($i <= $key) ? 'text-orange-600' : 'text-gray-300' ?>"><?= str_replace('_',' ',$step) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mt-4 mb-4 bg-red-50 border border-red-100 text-red-700 rounded-xl p-4 text-xs font-bold text-center">
                        ⚠️ This order has been cancelled and removed from the active delivery pipeline.
                    </div>
                    <?php endif; ?>

                    <a href="order-details.php?id=<?= $order['order_id'] ?>" 
                       class="block w-full text-center bg-slate-900 text-white font-bold py-4 rounded-2xl hover:bg-orange-600 transition-all text-sm mb-3 shadow-lg shadow-slate-200">
                        VIEW FULL RECEIPT
                    </a>

                    <?php if ($order['status'] === 'pending'): ?>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? It will instantly update the admin dashboard and sync with WhatsApp.');" class="mb-3">
                            <input type="hidden" name="action" value="cancel_order">
                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                            <button type="submit" class="w-full text-center bg-red-600 text-white font-black py-4 rounded-2xl hover:bg-red-700 transition-all text-sm uppercase tracking-wider shadow-lg shadow-red-100">
                                <i class="fa-solid fa-rectangle-xmark mr-1"></i> Cancel Order
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($order['rider_id'] && $order['status'] != 'delivered' && $order['status'] != 'cancelled'): ?>
                    <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center"><i class="fa-solid fa-motorcycle"></i></div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase leading-none">Rider</p>
                                <p class="font-bold text-sm text-slate-800"><?= htmlspecialchars($order['rider_name']) ?></p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="tel:<?= $order['rider_phone'] ?>" class="w-10 h-10 bg-white border border-gray-200 flex items-center justify-center rounded-xl text-slate-600 shadow-sm"><i class="fa-solid fa-phone text-xs"></i></a>
                            <a href="https://wa.me/<?= $order['rider_phone'] ?>" class="w-10 h-10 bg-green-500 flex items-center justify-center rounded-xl text-white shadow-sm"><i class="fa-brands fa-whatsapp text-sm"></i></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-20 bg-white rounded-[2rem] border border-dashed border-gray-200">
                <div class="text-5xl mb-4">🏜️</div>
                <h3 class="font-bold text-slate-800">No orders found</h3>
                <p class="text-gray-400 text-sm">Try changing your filters or browse the menu.</p>
                <a href="index.php" class="inline-block mt-6 bg-orange-600 text-white px-10 py-3 rounded-xl font-bold shadow-lg shadow-orange-200">Order Now</a>
            </div>
        <?php endif; ?>

    </main>
</div>

<script>
// Toggle Accordion
document.querySelectorAll('.order-header').forEach(header => {
    header.addEventListener('click', () => {
        const card = header.parentElement;
        const wasActive = card.classList.contains('active');
        document.querySelectorAll('.order-card').forEach(c => c.classList.remove('active'));
        if (!wasActive) card.classList.add('active');
    });
});

// Real-time Search
document.getElementById('searchInput').addEventListener('keyup', function () {
    let filter = this.value.toLowerCase();
    document.querySelectorAll('.order-item').forEach(item => {
        item.style.display = item.innerText.toLowerCase().includes(filter) ? "block" : "none";
    });
});
</script>

<?php include 'includes/footer.php'; ?>