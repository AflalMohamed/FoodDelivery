<?php 
include 'config/config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_GET['id']) || empty($_GET['id'])) { header("Location: shops.php"); exit; }
$shop_id = $_GET['id'];

// Fetch Shop & Settings
$stmt = $conn->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$shop_id]);
$shop = $stmt->fetch();
if (!$shop) { header("Location: shops.php"); exit; }

$settings = $conn->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$admin_wa = $settings['admin_whatsapp'] ?? '94703720960';
$fee_local = $settings['delivery_fee_local'] ?? 100;
$fee_outside = $settings['delivery_fee_outside'] ?? 150;
$is_logged_in = isset($_SESSION['user_id']) ? 1 : 0;

// Search Logic
$search = $_GET['q'] ?? '';
$sql = "SELECT * FROM products WHERE vendor_id = ?";
if (!empty($search)) {
    $products = $conn->prepare($sql . " AND food_name LIKE ?");
    $products->execute([$shop_id, "%$search%"]);
} else {
    $products = $conn->prepare($sql);
    $products->execute([$shop_id]);
}

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    
    :root { --primary: #f97316; --dark: #0f172a; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; color: var(--dark); margin: 0; padding: 0; }

    /* Fix: Vendor Image Animation (Ken Burns) */
    .shop-banner { height: 380px; position: relative; overflow: hidden; background: #000; }
    
    /* Animation Keyframes */
    @keyframes kenBurnsEffect {
        0% { transform: scale(1) translateY(0); }
        50% { transform: scale(1.1) translateY(-10px); }
        100% { transform: scale(1) translateY(0); }
    }

    .shop-banner img.animate-bg { 
        width: 100%; height: 100%; object-fit: cover; opacity: 0.7; 
        animation: kenBurnsEffect 15s infinite ease-in-out;
        display: block;
    }
    
    /* Minimal Foggy/Overlay Effect */
    .banner-overlay { 
        position: absolute; inset: 0; 
        background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0) 60%, #ffffff 100%); 
        z-index: 2;
    }

    /* High Visibility White Text */
    .banner-content { 
        position: absolute; inset: 0; display: flex; flex-direction: column; 
        align-items: center; justify-content: center; text-align: center; 
        padding: 20px; z-index: 10; 
    }
    .vendor-title { 
        color: #ffffff !important; font-size: 3.5rem; font-weight: 900; 
        text-transform: uppercase; font-style: italic; letter-spacing: -2px; 
        line-height: 1; text-shadow: 2px 4px 20px rgba(0,0,0,0.8); 
    }
    .vendor-loc { 
        color: #ffffff !important; font-size: 0.85rem; font-weight: 800; 
        text-transform: uppercase; letter-spacing: 2px; margin-top: 10px; 
        text-shadow: 1px 2px 10px rgba(0,0,0,0.8); opacity: 0.95;
    }

    @media (max-width: 768px) { .vendor-title { font-size: 2.5rem; } }

    /* Search & Layout */
    .search-container { max-width: 550px; margin: -30px auto 0; position: relative; z-index: 100; padding: 0 20px; }
    .search-box { 
        background: white; padding: 5px; border-radius: 100px; display: flex; 
        box-shadow: 0 15px 40px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; 
    }
    .search-box input { border: none; outline: none; padding: 0 20px; flex: 1; font-size: 0.9rem; font-weight: 600; }
    .search-btn { background: var(--dark); color: white; border: none; padding: 12px 28px; border-radius: 100px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; cursor: pointer; }

    /* Product Grid */
    .product-grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); margin-top: 50px; }
    .product-card {
        background: #fff; border: 1px solid #f1f5f9; border-radius: 1.5rem;
        padding: 1rem; display: flex; gap: 1rem; align-items: center; transition: 0.3s;
    }
    .product-card:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: 0 15px 30px rgba(0,0,0,0.05); }

    .img-view-trigger { width: 115px; height: 115px; flex-shrink: 0; border-radius: 1.2rem; overflow: hidden; cursor: pointer; position: relative; }
    .img-view-trigger img { width: 100%; height: 100%; object-fit: cover; }
    .img-view-trigger::after {
        content: '\f00e'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
        position: absolute; inset: 0; background: rgba(0,0,0,0.4); color: white;
        display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s;
    }
    .img-view-trigger:hover::after { opacity: 1; }

    /* Modal Styling */
    #fullViewModal { display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(0,0,0,0.9); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(8px); }
    #fullViewModal img { max-width: 90%; max-height: 80%; border-radius: 1rem; box-shadow: 0 0 40px rgba(0,0,0,0.5); }

    .wa-btn { background: #f0fdf4; color: #16a34a; width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
    .add-btn { background: var(--dark); color: white; border: none; padding: 10px 22px; border-radius: 12px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }

    .modal-overlay { position: fixed; inset: 0; z-index: 1500; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(10px); display: none; align-items: center; justify-content: center; padding: 15px; }
    .modal-card { background: white; width: 100%; max-width: 420px; border-radius: 2.5rem; padding: 35px; }
    .form-input { width: 100%; background: #f8fafc; border: 2px solid #f1f5f9; padding: 15px; border-radius: 1.2rem; margin-bottom: 12px; font-weight: 600; outline: none; }
</style>

<div class="pb-24">
    <div class="shop-banner">
        <img src="assets/images/shops/<?= $shop['shop_image'] ?>" alt="Shop" class="animate-bg">
        <div class="banner-overlay"></div>
        <div class="banner-content" data-aos="fade-down">
            <span class="bg-orange-600 text-[10px] font-black uppercase px-4 py-1.5 rounded-full text-white mb-4 tracking-[0.2em] shadow-lg"><?= $shop['shop_type'] ?></span>
            <h1 class="vendor-title"><?= $shop['shop_name'] ?></h1>
            <p class="vendor-loc"><i class="fa-solid fa-location-dot text-orange-500 mr-2"></i><?= $shop['shop_address'] ?></p>
        </div>
    </div>

    <div class="search-container" data-aos="fade-up">
        <form action="" method="GET" class="search-box">
            <input type="hidden" name="id" value="<?= $shop_id ?>">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search inside <?= $shop['shop_name'] ?>...">
            <button class="search-btn">Find</button>
        </form>
    </div>

    <div class="max-w-7xl mx-auto px-6">
        <div class="product-grid">
            <?php if($products->rowCount() > 0): ?>
                <?php while($item = $products->fetch()): ?>
                    <div class="product-card" data-aos="fade-up">
                        <div class="img-view-trigger" onclick="showFullImage('assets/images/products/<?= $item['food_image'] ?>')">
                            <img src="assets/images/products/<?= $item['food_image'] ?>" alt="Food">
                        </div>
                        <div class="flex-grow min-w-0">
                            <h3 class="font-black text-sm uppercase italic text-slate-800 truncate"><?= $item['food_name'] ?></h3>
                            <p class="text-[10px] text-slate-500 line-clamp-2 mb-3"><?= htmlspecialchars($item['description'] ?: 'Deliciously made just for you.') ?></p>
                            <div class="flex items-center justify-between">
                                <span class="font-black text-base text-slate-900">LKR <?= number_format($item['price'], 0) ?></span>
                                <div class="flex gap-2">
                                    <button onclick='initQuickOrder(<?= json_encode(["id"=>$item["id"],"name"=>$item["food_name"],"price"=>$item["price"],"img"=>$item["food_image"],"shop"=>$shop["shop_name"]]) ?>)' class="wa-btn">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </button>
                                    <button onclick="addToBag(<?= $item['id'] ?>)" class="add-btn">Add</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full py-24 text-center opacity-20 font-black uppercase text-xs">Menu is empty</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="fullViewModal" onclick="this.style.display='none'">
    <img id="modalFullImg" src="">
</div>

<div id="checkoutModal" class="modal-overlay" onclick="closeCheckout()">
    <div class="modal-card" onclick="event.stopPropagation()" data-aos="zoom-in">
        <h2 class="text-2xl font-black italic uppercase mb-6">Quick <span class="text-orange-500">Order</span></h2>
        <div id="checkoutItem" class="flex items-center gap-4 mb-6 p-4 bg-slate-50 rounded-3xl border border-slate-100"></div>
        <input type="text" id="wa-phone" placeholder="WhatsApp Number" class="form-input">
        <textarea id="wa-address" rows="2" placeholder="Delivery Address" class="form-input"></textarea>
        <select id="wa-area" class="form-input">
            <option value="<?= $fee_local ?>">Inside Town (+LKR <?= $fee_local ?>)</option>
            <option value="<?= $fee_outside ?>">Outside Town (+LKR <?= $fee_outside ?>)</option>
        </select>
        <button onclick="triggerWA()" class="w-full bg-slate-900 text-white py-4 rounded-2xl mt-4 font-black uppercase text-xs shadow-xl active:scale-95">Send Order via WhatsApp</button>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });

    function showFullImage(src) {
        document.getElementById('fullViewModal').style.display = 'flex';
        document.getElementById('modalFullImg').src = src;
    }

    let activeData = null;

    function initQuickOrder(item) {
        if (!<?= $is_logged_in ?>) { window.location.href='login.php'; return; }
        activeData = item;
        document.getElementById('checkoutItem').innerHTML = `
            <img src="assets/images/products/${item.img}" class="w-16 h-16 rounded-2xl object-cover">
            <div>
                <p class="font-black text-slate-900 uppercase text-xs italic">${item.name}</p>
                <p class="text-orange-600 font-extrabold text-base">LKR ${parseFloat(item.price).toLocaleString()}</p>
            </div>
        `;
        document.getElementById('checkoutModal').style.display = 'flex';
    }

    function closeCheckout() { document.getElementById('checkoutModal').style.display = 'none'; }

    function triggerWA() {
        const phone = document.getElementById('wa-phone').value.trim();
        const address = document.getElementById('wa-address').value.trim();
        const areaField = document.getElementById('wa-area');
        const delivery = parseFloat(areaField.value);
        const areaName = areaField.options[areaField.selectedIndex].text.split(' (')[0];

        if (!phone || !address) return alert("Fill everything!");

        const subtotal = parseFloat(activeData.price);
        const total = subtotal + delivery;

        const waText = `*--- NEW ORDER ---*%0A` +
            `*Site:* FCA Food Delivery%0A%0A` +
            `👤 *Customer:*%0A` +
            `📱 WhatsApp: ${phone}%0A` +
            `📍 Address: ${address}%0A` +
            `🚩 Area: ${areaName}%0A%0A` +
            `📦 *Item Details:*%0A` +
            `• *${activeData.name}*%0A` +
            `  Qty: 1 x LKR ${subtotal.toLocaleString()}%0A` +
            `  Shop: ${activeData.shop}%0A%0A` +
            `💳 *Billing:*%0A` +
            `Subtotal: LKR ${subtotal.toLocaleString()}.00%0A` +
            `Delivery: LKR ${delivery.toLocaleString()}.00%0A` +
            `*TOTAL: LKR ${total.toLocaleString()}.00*%0A%0A` +
            `_Sent from Website Quick Checkout_`;

        fetch('save-order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ phone, address, subtotal, delivery, total, items: [{id: activeData.id, qty: 1, price: subtotal}] })
        }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                window.open(`https://wa.me/<?= $admin_wa ?>?text=${waText}`, '_blank');
                location.reload();
            }
        });
    }

    function addToBag(id) {
        fetch('cart-logic.php?action=add&id=' + id).then(res => res.json()).then(data => {
            if(data.status === 'success') location.reload();
        });
    }
</script>

<?php include 'includes/footer.php'; ?>