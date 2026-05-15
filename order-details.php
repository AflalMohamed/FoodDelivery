<?php 
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED); 
ini_set('display_errors', 0); 

include 'config/config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: my-orders.php");
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $settings_query = $conn->query("SELECT * FROM site_settings LIMIT 1");
    $site = $settings_query->fetch(PDO::FETCH_ASSOC);

    // Fetch Order and Rider Details (rider name and phone added)
    $stmt = $conn->prepare("
        SELECT o.*, r.name as rider_name, r.phone as rider_phone
        FROM orders o 
        LEFT JOIN users r ON o.rider_id = r.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) { header("Location: my-orders.php"); exit; }

    $items_stmt = $conn->prepare("
        SELECT oi.*, p.food_name 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $items_stmt->execute([$order_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

include 'includes/header.php'; 
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }

    .ui-card {
        background: white;
        border-radius: 1.5rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        max-width: 450px;
        margin: 2rem auto;
    }

    .ui-header {
        background: #f97316;
        padding: 2.5rem 1.5rem;
        text-align: center;
        color: white;
    }

  /* PRINT FIX: Removing Fixed Center Alignment */
    @media print {
        body * { visibility: hidden; background: white !important; }
        .no-print { display: none !important; }
        #printable-receipt { 
            display: block !important; 
            visibility: visible !important;
            position: absolute !important; 
            left: 0 !important; 
            top: 0 !important; 
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        #printable-receipt * { visibility: visible !important; }
    }
    /* PDF Template Hidden on Screen */
    #printable-receipt {
        display: none;
        width: 800px;
        background: white;
    }

    .dashed-divider { border-top: 2px dashed #e2e8f0; margin: 1.5rem 0; }
</style>

<div id="main-ui-content" class="min-h-screen pt-20 pb-12 px-4 no-print">
    <div class="max-w-md mx-auto">
        <div class="flex justify-between items-center mb-6">
            <a href="my-orders.php" class="text-orange-600 font-bold text-xs uppercase no-underline flex items-center gap-2">
                <i class="fa-solid fa-chevron-left"></i> My Orders
            </a>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Live Receipt</span>
        </div>

        <div class="ui-card">
            <div class="ui-header">
                <img src="assets/img/<?= $site['site_logo'] ?>" class="h-12 mx-auto mb-4" onerror="this.src='assets/img/logo.png'">
                <h1 class="text-xl font-black uppercase tracking-tight"><?= htmlspecialchars($site['site_name']) ?></h1>
                <div class="inline-block bg-white/20 px-4 py-1 rounded-full mt-2">
                    <p class="text-[9px] font-black uppercase tracking-widest"><?= str_replace('_', ' ', $order['status']) ?></p>
                </div>
            </div>

            <div class="p-8">
                <div class="flex justify-between mb-8">
                    <div>
                        <p class="text-[9px] text-slate-400 font-bold uppercase">Order #</p>
                        <p class="font-bold text-slate-800">#ORD-<?= $order['id'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] text-slate-400 font-bold uppercase">Date</p>
                        <p class="font-bold text-slate-800"><?= date('d M Y', strtotime($order['order_date'])) ?></p>
                    </div>
                </div>

                <?php if (!empty($order['rider_name'])): ?>
                <div class="bg-orange-50 p-4 rounded-2xl border border-blue-100 mb-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                        <i class="fa-solid fa-motorcycle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[8px] text-orange-600 font-black uppercase tracking-widest">Assigned Rider</p>
                        <h4 class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($order['rider_name']) ?></h4>
                        <a href="tel:<?= $order['rider_phone'] ?>" class="text-[10px] font-bold text-orange-500 underline"><?= $order['rider_phone'] ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="space-y-4 mb-8">
                    <div class="flex gap-3">
                        <i class="fa-solid fa-store text-slate-300 mt-1"></i>
                        <div>
                            <p class="text-[8px] text-slate-400 font-black uppercase">From </p>
                            <p class="text-[11px] font-bold text-slate-700"><?= htmlspecialchars($site['admin_address']) ?></p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <i class="fa-solid fa-location-dot text-orange-500 mt-1"></i>
                        <div>
                            <p class="text-[8px] text-orange-600 font-black uppercase">To Your Doorstep</p>
                            <p class="text-[11px] font-bold text-slate-700"><?= htmlspecialchars($order['address']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 border-t border-slate-50 pt-6">
                    <?php foreach ($items as $item): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600 font-medium"><b class="text-orange-500"><?= $item['quantity'] ?>x</b> <?= htmlspecialchars($item['food_name']) ?></span>
                        <span class="font-bold text-slate-800">LKR <?= number_format($item['price_at_time'] * $item['quantity'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="dashed-divider"></div>

                <div class="space-y-2">
                    <div class="flex justify-between text-xs text-slate-500 font-bold uppercase">
                        <span>Subtotal</span>
                        <span>LKR <?= number_format($order['subtotal'], 2) ?></span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500 font-bold uppercase">
                        <span>Delivery</span>
                        <span>LKR <?= number_format($order['delivery_fee'], 2) ?></span>
                    </div>
                    <div class="flex justify-between items-center mt-6 pt-4 border-t border-slate-100">
                        <span class="text-sm font-black text-slate-900 uppercase">Grand Total</span>
                        <span class="text-2xl font-black text-orange-600">LKR <?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-6">
            <button onclick="window.print()" class="bg-white border-2 border-slate-200 py-4 rounded-2xl font-black text-[10px] uppercase text-slate-600 active:scale-95 transition-all">
                <i class="fa-solid fa-print mr-2"></i> Print View
            </button>
            <button onclick="generatePDF()" class="bg-slate-900 text-white py-4 rounded-2xl font-black text-[10px] uppercase shadow-lg active:scale-95 transition-all">
                <i class="fa-solid fa-download mr-2"></i> Save PDF
            </button>
        </div>
    </div>
</div>

<div id="printable-receipt">
    <div style="background: #f97316; padding: 40px; text-align: center; color: white;">
        <img src="assets/img/<?= $site['site_logo'] ?>" style="height: 60px; margin-bottom: 10px;">
        <h1 style="font-size: 24px; font-weight: 900; margin: 0; text-transform: uppercase;"><?= htmlspecialchars($site['site_name']) ?></h1>
        <p style="text-transform: uppercase; font-weight: bold; font-size: 10px; letter-spacing: 2px; margin-top: 5px;"><?= str_replace('_', ' ', $order['status']) ?></p>
    </div>
    
    <div style="padding: 40px;">
        <table style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <p style="font-size: 10px; color: #f97316; font-weight: 900; text-transform: uppercase; margin: 0;">Restaurant</p>
                    <p style="font-size: 13px; font-weight: 700; margin: 5px 0; color: #1e293b;"><?= htmlspecialchars($site['site_name']) ?></p>
                    <p style="font-size: 11px; color: #64748b; line-height: 1.4;"><?= htmlspecialchars($site['admin_address']) ?></p>
                </td>
                <td style="text-align: right; width: 50%; vertical-align: top;">
                    <p style="font-size: 10px; color: #f97316; font-weight: 900; text-transform: uppercase; margin: 0;">Reference</p>
                    <p style="font-size: 14px; font-weight: 800; margin: 5px 0; color: #1e293b;">#ORD-<?= $order['id'] ?></p>
                    <p style="font-size: 11px; color: #64748b;"><?= date('F d, Y', strtotime($order['order_date'])) ?></p>
                </td>
            </tr>
        </table>

        <?php if (!empty($order['rider_name'])): ?>
        <div style="background: #f1f5f9; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
            <p style="font-size: 9px; color: #64748b; font-weight: bold; text-transform: uppercase; margin: 0;">Delivered By</p>
            <p style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 5px 0;"><?= $order['rider_name'] ?> (<?= $order['rider_phone'] ?>)</p>
        </div>
        <?php endif; ?>

        <div style="border-left: 3px solid #f97316; padding-left: 15px; margin-bottom: 30px;">
            <p style="font-size: 10px; color: #94a3b8; font-weight: bold; text-transform: uppercase; margin: 0;">Delivery Destination</p>
            <p style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 5px 0;"><?= htmlspecialchars($order['address']) ?></p>
        </div>

        <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                    <th style="padding: 10px 0; color: #94a3b8; font-size: 10px; text-transform: uppercase;">Items</th>
                    <th style="text-align: right; padding: 10px 0; color: #94a3b8; font-size: 10px; text-transform: uppercase;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid #f8fafc;">
                        <span style="font-weight: 800; color: #f97316;"><?= $item['quantity'] ?>x</span> &nbsp; <?= htmlspecialchars($item['food_name']) ?>
                    </td>
                    <td style="text-align: right; font-weight: 800; color: #1e293b;">
                        LKR <?= number_format($item['price_at_time'] * $item['quantity'], 2) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 30px; background: #1e293b; padding: 25px; border-radius: 12px; color: white;">
            <table style="width: 100%;">
                <tr style="font-size: 11px; opacity: 0.8;">
                    <td style="padding-bottom: 5px;">Subtotal</td>
                    <td style="text-align: right;">LKR <?= number_format($order['subtotal'], 2) ?></td>
                </tr>
                <tr style="font-size: 11px; opacity: 0.8;">
                    <td style="padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">Delivery Fee</td>
                    <td style="text-align: right; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">LKR <?= number_format($order['delivery_fee'], 2) ?></td>
                </tr>
                <tr>
                    <td style="padding-top: 15px; font-weight: 900; color: #f97316; text-transform: uppercase;">Total Paid</td>
                    <td style="padding-top: 15px; text-align: right; font-size: 24px; font-weight: 900; color: #f97316;">LKR <?= number_format($order['total_amount'], 2) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
function generatePDF() {
    const element = document.getElementById('printable-receipt');
    element.style.display = 'block'; // Make it visible for capture
    
    const opt = {
        margin: 0,
        filename: 'Receipt_ORD-<?= $order['id'] ?>.pdf',
        image: { type: 'jpeg', quality: 1 },
        html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        element.style.display = 'none'; // Hide again
    });
}
</script>

<?php include 'includes/footer.php'; ?>