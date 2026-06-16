<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

$whatsapp_url = ""; 
$success = "";
$error = "";

// --- LOGIC: Handle Rider Assignment ---
if (isset($_POST['assign_rider'])) {
    $order_id = $_POST['order_id'];
    $rider_id = $_POST['rider_id'];

    // Double-check verification barrier: Active riders are allowed to handle delivery dispatches
    $checkRider = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'rider' AND status = 'active' AND is_verified = 1");
    $checkRider->execute([$rider_id]);

    if ($checkRider->rowCount() > 0) {
        $stmt = $conn->prepare("UPDATE orders SET rider_id = ?, status = 'assigned' WHERE id = ?");
        if ($stmt->execute([$rider_id, $order_id])) {
            
            $riderStmt = $conn->prepare("SELECT name AS rider_name, phone FROM users WHERE id = ? AND role = 'rider'");
            $riderStmt->execute([$rider_id]);
            $riderData = $riderStmt->fetch();

            if ($riderData && !empty($riderData['phone'])) {
                $r_name = $riderData['rider_name'];
                $raw_phone = preg_replace('/[^0-9]/', '', $riderData['phone']);
                $clean_phone = (str_starts_with($raw_phone, '0')) ? '94'.ltrim($raw_phone, '0') : $raw_phone;
                
                $msg_body = "🔔 *NEW ORDER ASSIGNED*\nHello $r_name, Order #$order_id is assigned to you. Drive safe!";
                $whatsapp_url = "https://wa.me/$clean_phone?text=" . rawurlencode($msg_body);
                $success = "Assigned to $r_name!";
            }
        }
    } else {
        $error = "Cannot assign order! This rider profile is currently pending activation or deactivated.";
    }
}

// --- LOGIC: Handle Order Deletion ---
if (isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    try {
        $conn->beginTransaction();
        $conn->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        if ($stmt->execute([$order_id])) {
            $conn->commit();
            $success = "Order #$order_id deleted successfully!";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error deleting order.";
    }
}

// --- FETCH DATA ---
try {
    $stmtOrders = $conn->query("SELECT o.*, u.name as customer, DATE(o.created_at) as order_date FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
} catch (PDOException $e) {
    $stmtOrders = $conn->query("SELECT o.*, u.name as customer, 'N/A' as order_date FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
}
$orders = $stmtOrders->fetchAll();

// CORE BUG FIX: Fetch only fully verified and active riders for order assignment
$riders = $conn->query("SELECT id, name FROM users WHERE role = 'rider' AND status = 'active' AND is_verified = 1")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Dispatch | FCA Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .hidden-row { display: none !important; }
        @media (max-width: 768px) { .desktop-table { display: none; } }
        @media (min-width: 769px) { .mobile-cards { display: none; } }
    </style>
</head>
<body class="text-slate-900">

    <?php include 'includes/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen">
        <main class="p-4 md:p-8">
            <header class="mb-8">
                <h1 class="text-2xl font-extrabold tracking-tight">Order <span class="text-orange-500">Dispatch</span></h1>
                
                <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-400 rounded-r-2xl">
                    <div class="flex items-center">
                        <i class="fa-solid fa-circle-info text-amber-500 mr-3"></i>
                        <p class="text-[11px] font-bold text-amber-800 uppercase tracking-tight">
                            “Please delete only unwanted orders listed here. Orders placed through WhatsApp should not be removed.”
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                    <div class="md:col-span-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 mb-1 block">Search</label>
                        <input type="text" id="orderSearch" onkeyup="runFilters()" placeholder="Customer or ID..." 
                               class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 outline-none focus:ring-2 focus:ring-orange-500/20 text-xs font-bold transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 mb-1 block">From Date</label>
                        <input type="date" id="fromDate" onchange="runFilters()" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-xs font-bold">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 mb-1 block">To Date</label>
                        <input type="date" id="toDate" onchange="runFilters()" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-xs font-bold">
                    </div>
                    <button onclick="resetFilters()" class="w-full py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase hover:bg-orange-500 transition-all">
                        <i class="fa-solid fa-filter-circle-xmark mr-2"></i> Reset
                    </button>
                </div>
            </header>

            <?php if($success): ?>
                <div class="bg-emerald-500 text-white p-4 mb-6 rounded-2xl flex items-center justify-between shadow-lg">
                    <span class="text-xs font-black uppercase italic tracking-tighter"><i class="fa-solid fa-check-double mr-2"></i><?= $success ?></span>
                    <?php if($whatsapp_url): ?>
                        <a href="<?= $whatsapp_url ?>" target="_blank" class="bg-white text-emerald-600 px-4 py-2 rounded-lg text-[10px] font-black uppercase">Open WhatsApp</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-500 text-white p-4 mb-6 rounded-2xl flex items-center justify-between shadow-lg">
                    <span class="text-xs font-black uppercase italic tracking-tighter"><i class="fa-solid fa-triangle-exclamation mr-2"></i><?= $error ?></span>
                </div>
            <?php endif; ?>

            <div class="desktop-table bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-widest">
                        <tr>
                            <th class="p-6">Order Info</th>
                            <th class="p-6">Customer</th>
                            <th class="p-6 text-center">Status</th>
                            <th class="p-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="orderBody">
                        <?php foreach($orders as $o): ?>
                        <tr class="order-row border-b border-slate-50 hover:bg-slate-50/50 transition-all" 
                            data-search="<?= strtolower($o['id'].' '.$o['customer']) ?>" 
                            data-date="<?= $o['order_date'] ?>">
                            <td class="p-6">
                                <p class="text-[10px] font-black text-orange-500">#<?= $o['id'] ?></p>
                                <p class="text-xs font-extrabold text-slate-800">LKR <?= number_format($o['total_amount'], 2) ?></p>
                                <p class="text-[9px] font-bold text-slate-300"><?= $o['order_date'] ?></p>
                            </td>
                            <td class="p-6 text-xs font-bold text-slate-600 italic"><?= htmlspecialchars($o['customer']) ?></td>
                            <td class="p-6 text-center">
                                <span class="px-3 py-1 rounded-full text-[8px] font-black uppercase <?= $o['status'] == 'pending' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' ?>">
                                    <?= $o['status'] ?>
                                </span>
                            </td>
                            <td class="p-6 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <?php if($o['status'] == 'pending'): ?>
                                        <form method="POST" class="flex items-center gap-2">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="rider_id" required class="text-[10px] font-bold bg-slate-50 border-none rounded-xl px-3 py-2.5 outline-none">
                                                <option value="">Rider</option>
                                                <?php foreach($riders as $r): ?>
                                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_rider" class="bg-slate-900 text-white w-9 h-9 rounded-xl hover:bg-emerald-500 transition-all flex items-center justify-center">
                                                <i class="fa-solid fa-paper-plane text-[10px]"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-[9px] font-black text-emerald-500 uppercase italic">Ready <i class="fa-solid fa-circle-check"></i></span>
                                    <?php endif; ?>

                                    <form method="POST" onsubmit="return confirm('Intha order-ai permanenta-aa delete panna thiruppi edukka mudiyaathu. Ok-va?');">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button type="submit" name="delete_order" class="bg-red-50 text-red-500 w-9 h-9 rounded-xl hover:bg-red-500 hover:text-white transition-all flex items-center justify-center">
                                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mobile-cards space-y-4">
                <?php foreach($orders as $o): ?>
                <div class="order-card bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm" 
                     data-search="<?= strtolower($o['id'].' '.$o['customer']) ?>" 
                     data-date="<?= $o['order_date'] ?>">
                    <div class="flex justify-between mb-4">
                        <span class="text-[10px] font-black text-slate-300 italic">#<?= $o['id'] ?> • <?= $o['order_date'] ?></span>
                        <div class="flex gap-2">
                             <span class="text-[9px] font-black uppercase text-orange-500"><?= $o['status'] ?></span>
                             <form method="POST" onsubmit="return confirm('Order-ai delete panna Ok-va?');">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <button type="submit" name="delete_order" class="text-red-400"><i class="fa-solid fa-trash-can text-[10px]"></i></button>
                             </form>
                        </div>
                    </div>
                    <h3 class="font-black text-slate-900 text-lg mb-2">LKR <?= number_format($o['total_amount'], 2) ?></h3>
                    <p class="text-[10px] font-bold text-slate-400 mb-6 uppercase">Customer: <span class="text-slate-700"><?= htmlspecialchars($o['customer']) ?></span></p>
                    
                    <?php if($o['status'] == 'pending'): ?>
                        <form method="POST" class="flex gap-2">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="rider_id" required class="flex-1 text-[10px] font-bold bg-slate-50 border-none rounded-xl px-4 py-3.5 outline-none">
                                <option value="">Select Rider</option>
                                <?php foreach($riders as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_rider" class="bg-orange-500 text-white px-5 rounded-xl"><i class="fa-solid fa-paper-plane"></i></button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script>
        function runFilters() {
            const s = document.getElementById('orderSearch').value.toLowerCase();
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            
            document.querySelectorAll('.order-row, .order-card').forEach(el => {
                const rowDate = el.getAttribute('data-date');
                const textMatch = el.getAttribute('data-search').includes(s);
                
                let dateMatch = true;
                if (from && to) {
                    dateMatch = (rowDate >= from && rowDate <= to);
                } else if (from) {
                    dateMatch = (rowDate >= from);
                } else if (to) {
                    dateMatch = (rowDate <= to);
                }

                const isVisible = textMatch && dateMatch;
                
                if(el.tagName === 'TR') {
                    el.classList.toggle('hidden-row', !isVisible);
                } else {
                    el.style.display = isVisible ? 'block' : 'none';
                }
            });
        }

        function resetFilters() {
            document.getElementById('orderSearch').value = "";
            document.getElementById('fromDate').value = "";
            document.getElementById('toDate').value = "";
            runFilters();
        }

        <?php if($whatsapp_url): ?>
            window.open('<?= $whatsapp_url ?>', '_blank');
        <?php endif; ?>
    </script>
</body>
</html>