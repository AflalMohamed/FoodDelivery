<?php 
include 'config/config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Fetch site settings
$settings_res = $conn->query("SELECT * FROM site_settings WHERE id = 1");
$settings = $settings_res->fetch();

$admin_wa = $settings['admin_whatsapp'] ?? '94703720960';
$fee_local = $settings['delivery_fee_local'] ?? 100;
$fee_outside = $settings['delivery_fee_outside'] ?? 150;

// 2. Status & Cart Data
$is_logged_in = isset($_SESSION['user_id']) ? 'true' : 'false';
$cart_count = 0;
if(isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $qty) { $cart_count += $qty; }
}

include 'includes/header.php'; 

// 3. Logic: All Products + Category Filter + Search
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['category'] ?? '';

$query_str = "SELECT p.*, v.shop_name FROM products p LEFT JOIN vendors v ON p.vendor_id = v.id";
$params = [];
$conditions = [];

if(!empty($search)) {
    $conditions[] = "(p.food_name LIKE ? OR v.shop_name LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm);
}

if(!empty($cat_filter)) {
    $conditions[] = "p.category = ?";
    $params[] = $cat_filter;
}

if(count($conditions) > 0) { $query_str .= " WHERE " . implode(" AND ", $conditions); }
$query_str .= " ORDER BY p.id DESC"; 

$stmt = $conn->prepare($query_str);
$stmt->execute($params);

$cat_stmt = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
$all_categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfdfe; color: #1e293b; -webkit-tap-highlight-color: transparent; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        
        /* 5 Column Grid for Desktop */
        .responsive-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        @media (min-width: 768px) { .responsive-grid { grid-template-columns: repeat(3, 1fr); gap: 20px; } }
        @media (min-width: 1024px) { .responsive-grid { grid-template-columns: repeat(5, 1fr); gap: 20px; } }

        /* Bigger Product Card */
        .p-card { 
            background: #fff; border-radius: 1.5rem; 
            border: 1px solid #f1f5f9; padding: 10px; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; flex-direction: column; height: 100%;
        }
        .p-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); }

        .img-container { position: relative; width: 100%; padding-top: 100%; overflow: hidden; border-radius: 1.25rem; background: #f8fafc; }
        .img-container img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        
        /* Floating Cart */
        .mobile-cart { position: fixed; bottom: 30px; right: 20px; width: 60px; height: 60px; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; border-radius: 50%; z-index: 999; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); }

        /* Lightbox Preview */
        #lightbox { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.9); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px; }
        #lightbox img { max-width: 100%; max-height: 80vh; border-radius: 1rem; box-shadow: 0 0 50px rgba(0,0,0,0.5); }
    </style>
</head>
<body class="overflow-x-hidden">

<?php if($cart_count > 0): ?>
<a href="cart.php" class="mobile-cart lg:flex">
    <i class="fa-solid fa-bag-shopping text-2xl"></i>
    <span class="absolute -top-1 -right-1 bg-orange-600 text-white text-[11px] font-bold px-2 py-0.5 rounded-full border-2 border-white"><?= $cart_count ?></span>
</a>
<?php endif; ?>

<header class="bg-white pt-10 pb-6 rounded-b-[3rem] shadow-sm border-b border-slate-50">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-2xl lg:text-4xl font-black uppercase tracking-tighter italic mb-8">
            Town <span class="text-orange-500">Food</span> Menu
        </h1>

        <form action="all-products.php" method="GET" class="relative max-w-lg mx-auto mb-8">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search for your favorite food..." 
                   class="w-full pl-6 pr-24 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:border-orange-500 text-sm shadow-inner">
            <button type="submit" class="absolute right-2 top-2 bg-slate-900 text-white px-6 py-2 rounded-xl font-bold text-xs uppercase tracking-widest">Find</button>
        </form>

        <div class="flex gap-3 overflow-x-auto no-scrollbar pb-2 lg:justify-center">
            <a href="all-products.php" class="whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase border <?= empty($cat_filter) ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-400 border-slate-100 shadow-sm' ?>">All Items</a>
            <?php foreach($all_categories as $cat): ?>
                <a href="all-products.php?category=<?= urlencode($cat) ?>" 
                   class="whitespace-nowrap px-6 py-3 rounded-full text-xs font-black uppercase border <?= ($cat_filter == $cat) ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-400 border-slate-100 shadow-sm' ?>">
                    <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</header>

<main class="max-w-[1400px] mx-auto px-4 lg:px-8 py-10">
    <div class="responsive-grid">
        <?php if($stmt->rowCount() > 0): ?>
            <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="p-card">
                    <div class="img-container group cursor-zoom-in" onclick="openPreview('assets/images/products/<?= $row['food_image'] ?>')">
                        <img src="assets/images/products/<?= $row['food_image'] ?>" alt="<?= $row['food_name'] ?>" loading="lazy">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <i class="fa-solid fa-expand text-white text-2xl"></i>
                        </div>
                        <div class="absolute top-2 right-2 bg-white/95 backdrop-blur px-2.5 py-1 rounded-xl shadow-sm border border-slate-100">
                            <span class="text-slate-900 font-black text-xs">LKR <?= number_format($row['price'], 0) ?></span>
                        </div>
                    </div>

                    <div class="pt-4 flex flex-col flex-grow">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-[9px] font-black text-orange-500 uppercase tracking-widest bg-orange-50 px-2 py-0.5 rounded-md"><?= htmlspecialchars($row['category']) ?></span>
                            <span class="text-[9px] font-bold text-slate-400 italic">By <?= htmlspecialchars($row['shop_name'] ?? 'Admin') ?></span>
                        </div>
                        
                        <h3 class="text-sm lg:text-base font-extrabold text-slate-800 line-clamp-1 mb-1"><?= htmlspecialchars($row['food_name']) ?></h3>
                        <p class="text-[11px] text-slate-400 leading-relaxed mb-4 line-clamp-2"><?= htmlspecialchars($row['description'] ?: 'Delicious and fresh food delivered to your door.') ?></p>
                        
                        <div class="mt-auto flex items-center gap-2">
                            <button onclick="addToCart(<?= $row['id'] ?>)" 
                                    class="flex-grow bg-slate-900 text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest active:scale-95 transition-all">
                                Add To Bag
                            </button>
                            <button onclick="openWhatsAppOrder(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                    class="bg-emerald-50 text-emerald-600 w-11 h-11 flex items-center justify-center rounded-xl border border-emerald-100 active:scale-95 transition-all">
                                <i class="fa-brands fa-whatsapp text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full py-24 text-center opacity-30">
                <i class="fa-solid fa-bowl-food text-6xl mb-4"></i>
                <p class="text-sm font-black uppercase tracking-widest">No Items Found</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<div id="lightbox" onclick="closePreview()">
    <button class="absolute top-6 right-6 text-white text-4xl">&times;</button>
    <img id="preview-img" src="" onclick="event.stopPropagation()">
</div>

<div id="quick-order-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4 bg-slate-900/90 backdrop-blur-sm">
    <div class="bg-white w-full max-w-xs rounded-[2.5rem] p-8 shadow-2xl">
        <h2 class="text-xs font-black uppercase tracking-widest mb-6">Confirm <span class="text-orange-500">Order</span></h2>
        <div id="modal-product-info" class="bg-slate-50 p-4 rounded-2xl mb-6 flex items-center gap-4 border border-slate-100"></div>
        <div class="space-y-3">
            <input type="text" id="wa-phone" placeholder="WhatsApp Number" class="w-full p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs font-bold outline-none focus:border-orange-500">
            <textarea id="wa-address" rows="2" placeholder="Full Address" class="w-full p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs font-bold outline-none focus:border-orange-500"></textarea>
            <select id="wa-location" class="w-full p-4 rounded-xl bg-slate-50 text-xs font-black outline-none border border-slate-100">
                <option value="<?= $fee_local ?>" data-label="Inside Town">Inside Town (+<?= $fee_local ?>)</option>
                <option value="<?= $fee_outside ?>" data-label="Outside Town">Outside Town (+<?= $fee_outside ?>)</option>
            </select>
        </div>
        <button onclick="processOrder()" id="order-btn" class="w-full bg-orange-600 text-white py-4 rounded-xl mt-8 font-black uppercase text-[10px] tracking-widest shadow-lg active:scale-95">Send To WhatsApp</button>
    </div>
</div>

<script>
let selectedItem = null;
const userLoggedIn = <?= $is_logged_in ?>;

// Lightbox Functions
function openPreview(src) {
    document.getElementById('preview-img').src = src;
    document.getElementById('lightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closePreview() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function addToCart(id) {
    fetch('cart-logic.php?action=add&id=' + id).then(res => res.json()).then(data => {
        if(data.status === 'success') { location.reload(); }
    });
}

function openWhatsAppOrder(item) {
    if (!userLoggedIn) { window.location.href = 'login.php'; return; }
    selectedItem = item;
    document.getElementById('modal-product-info').innerHTML = `
        <img src="assets/images/products/${item.food_image}" class="w-12 h-12 rounded-xl object-cover">
        <div><p class="font-bold text-xs">${item.food_name}</p><p class="text-[10px] text-orange-600 font-black">LKR ${parseFloat(item.price).toLocaleString()}</p></div>
    `;
    document.getElementById('quick-order-modal').classList.remove('hidden');
}

function processOrder() {
    const ph = document.getElementById('wa-phone').value.trim();
    const ad = document.getElementById('wa-address').value.trim();
    const locSelect = document.getElementById('wa-location');
    const fee = parseFloat(locSelect.value);
    const areaLabel = locSelect.options[locSelect.selectedIndex].getAttribute('data-label');
    
    if (!ph || !ad) { alert("Fill all fields"); return; }
    
    const subtotal = parseFloat(selectedItem.price);
    const total = subtotal + fee;

    fetch('save-order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            phone: ph, address: ad, subtotal: subtotal,
            delivery: fee, total: total, items: [{id: selectedItem.id, qty: 1, price: subtotal}]
        })
    }).then(res => res.json()).then(data => {
        if(data.status === 'success') {
            let msg = `*New Order*%0A📞: ${ph}%0A📍: ${ad}%0A🗺️: ${areaLabel}%0A🍔: ${selectedItem.food_name}%0A💰: *LKR ${total.toLocaleString()}*`;
            window.open(`https://wa.me/<?= $admin_wa ?>?text=${msg}`, '_blank');
            location.reload();
        }
    });
}

function closeModal() { document.getElementById('quick-order-modal').classList.add('hidden'); }
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>