<?php 
include 'config/config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Fetch site settings
$settings_res = $conn->query("SELECT * FROM site_settings WHERE id = 1");
$settings = $settings_res->fetch();

$site_name = $settings['site_name'] ?? 'Premium Delivery';
$admin_wa = $settings['admin_whatsapp'] ?? '94703720960';
$fee_local = $settings['delivery_fee_local'] ?? 100;
$fee_outside = $settings['delivery_fee_outside'] ?? 150;

$is_logged_in = isset($_SESSION['user_id']) ? 'true' : 'false';

// Get Cart Count for initial load
$cart_count = 0;
if(isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $qty) { $cart_count += (int)$qty; }
}

include 'includes/header.php'; 

// 2. Logic (Index: Biriyani items and filters)
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['category'] ?? '';

$base_query = "SELECT p.*, v.shop_name FROM products p LEFT JOIN vendors v ON p.vendor_id = v.id";
$params = [];
$where_clauses = [];

if(!empty($search)) {
    $where_clauses[] = "(p.food_name LIKE ? OR v.shop_name LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm);
    $limit = " LIMIT 24";
} elseif(!empty($cat_filter)) {
    $where_clauses[] = "p.category = ?";
    $params[] = $cat_filter;
    $limit = " LIMIT 24";
} else {
    $where_clauses[] = "p.category = 'Biriyani'";
    $limit = " LIMIT 6";
}

$final_sql = $base_query;
if(count($where_clauses) > 0) { $final_sql .= " WHERE " . implode(" AND ", $where_clauses); }
$final_sql .= " ORDER BY p.id DESC " . $limit;

$stmt = $conn->prepare($final_sql);
$stmt->execute($params);

$categories = ['Biriyani', 'Fast Food', 'Drinks', 'Snacks', 'Desserts'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        :root { --primary: #f97316; --dark: #0f172a; }
        
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; color: var(--dark); overflow-x: hidden; margin: 0; }
        .max-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .no-scrollbar::-webkit-scrollbar { display: none; }

        /* HERO BURGER ANIMATION */
        .hero-floating-img { animation: heroFloat 6s ease-in-out infinite; }
        @keyframes heroFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }

        .img-aspect { position: relative; width: 100%; padding-top: 100%; border-radius: 1.8rem; overflow: hidden; background: #f8fafc; }
        .img-aspect img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }

        .btn-add-cart {
            background-color: var(--dark); color: #fff; padding: 12px; border-radius: 1.2rem;
            font-size: 10px; font-weight: 800; text-transform: uppercase; border: none;
            flex-grow: 1; cursor: pointer; transition: 0.3s;
        }
        .btn-add-cart:hover { background-color: var(--primary); transform: translateY(-2px); }

        .btn-wa {
            background-color: #f0fdf4; color: #16a34a; padding: 0 15px; border-radius: 1.2rem;
            border: 1px solid #dcfce7; display: flex; align-items: center; justify-content: center; transition: 0.3s;
        }
        .btn-wa:hover { background-color: #16a34a; color: #fff; }

        .feature-card {
            background: #fff; padding: 40px 30px; border-radius: 2.5rem; text-align: center;
            border: 1px solid #f1f5f9; transition: 0.4s;
        }
        .feature-card:hover { border-color: var(--primary); transform: translateY(-10px); }

        .mobile-cart-float {
            position: fixed; bottom: 25px; right: 20px; z-index: 99;
            background: var(--dark); color: white; width: 65px; height: 65px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); transition: 0.3s;
        }

        #imagePreviewModal, #orderModal { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.9); backdrop-filter: blur(8px); align-items: center; justify-content: center; }
        
        /* Modal Body Styling */
        .modal-body {
            background: white; width: 100%; max-width: 400px; border-radius: 2.5rem;
            padding: 30px; animation: modalSlide 0.3s ease-out;
        }
        @keyframes modalSlide { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

<a href="cart.php" class="mobile-cart-float" id="cart-container">
    <i class="fa-solid fa-bag-shopping text-2xl"></i>
    <span class="absolute top-0 right-0 bg-orange-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black border-2 border-white" id="floating-badge" style="<?= ($cart_count > 0) ? '' : 'display:none;' ?>">
        <?= $cart_count ?>
    </span>
</a>

<section class="pt-8 pb-12 lg:pt-16 lg:pb-20">
    <div class="max-container flex flex-col lg:flex-row items-center gap-10">
        <div class="w-full lg:w-3/5 text-center lg:text-left order-2 lg:order-1" data-aos="fade-right">
            <div class="inline-block px-4 py-1 rounded-full bg-orange-50 border border-orange-100 mb-6">
                <span class="text-orange-600 text-[10px] font-black uppercase tracking-widest italic">Fastest Delivery in Town</span>
            </div>
            <h1 class="text-5xl lg:text-7xl font-extrabold text-slate-900 mb-8 tracking-tighter">
                Good Food, <br><span class="text-orange-500 italic">Great Moments.</span>
            </h1>
            
            <form action="index.php" method="GET" class="relative max-w-lg mx-auto lg:mx-0 mb-10">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search dishes or shops..." 
                       class="w-full pl-6 pr-32 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-4 focus:ring-orange-500/10 text-sm">
                <button type="submit" class="absolute right-2 top-2 bg-slate-900 text-white px-6 py-2.5 rounded-xl font-bold text-xs uppercase">Find</button>
            </form>

            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-2">
                <a href="index.php" class="whitespace-nowrap px-6 py-2.5 rounded-full text-[10px] font-black uppercase transition-all <?= (empty($cat_filter) && empty($search)) ? 'bg-orange-600 text-white' : 'bg-slate-100 text-slate-500' ?>">Home</a>
                <?php foreach($categories as $cat): ?>
                    <a href="index.php?category=<?= $cat ?>" 
                       class="whitespace-nowrap px-6 py-2.5 rounded-full text-[10px] font-black uppercase transition-all <?= ($cat_filter == $cat) ? 'bg-orange-600 text-white' : 'bg-slate-100 text-slate-500' ?>">
                        <?= $cat ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="w-full lg:w-2/5 flex justify-center order-1 lg:order-2" data-aos="zoom-in">
            <div class="relative w-4/5 lg:w-full">
                <div class="absolute -inset-6 bg-orange-400/10 rounded-full blur-3xl"></div>
                <img src="https://img.freepik.com/free-photo/delicious-burger-with-fresh-ingredients_23-2150857908.jpg" 
                     class="hero-floating-img relative rounded-[3rem] shadow-2xl w-full aspect-square object-cover border-[8px] border-white z-10" alt="Hero">
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-slate-50 rounded-t-[3rem] lg:rounded-t-[5rem]">
    <div class="max-container">
        <div class="flex items-end justify-between mb-10">
            <div>
                <h2 class="text-2xl lg:text-3xl font-black uppercase tracking-tighter">
                    <?= !empty($cat_filter) ? $cat_filter : (!empty($search) ? 'Results' : 'Featured Items') ?>
                </h2>
                <div class="w-10 h-1.5 bg-orange-500 mt-2 rounded-full"></div>
            </div>
            <a href="all-products.php" class="text-orange-600 font-bold text-[10px] uppercase tracking-widest">See All &rarr;</a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 lg:gap-6">
            <?php if($stmt->rowCount() > 0): ?>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="bg-white rounded-[2rem] border border-slate-200/50 p-3 flex flex-col group hover:shadow-xl transition-all duration-300" data-aos="fade-up">
                        <div class="img-aspect cursor-zoom-in" onclick="openPreview('assets/images/products/<?= $row['food_image'] ?>')">
                            <img src="assets/images/products/<?= $row['food_image'] ?>" class="group-hover:scale-110">
                            <div class="absolute bottom-3 right-3 bg-white/95 px-3 py-1.5 rounded-xl shadow-sm">
                                <span class="font-black text-xs text-slate-900">LKR <?= number_format($row['price'], 0) ?></span>
                            </div>
                        </div>

                        <div class="p-3 flex flex-col flex-grow">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[8px] font-black text-orange-500 uppercase"><?= htmlspecialchars($row['category']) ?></span>
                                <span class="text-[8px] font-bold text-slate-300 italic truncate max-w-[80px]"><?= htmlspecialchars($row['shop_name']) ?></span>
                            </div>
                            <h3 class="text-sm font-extrabold text-slate-900 mb-1 truncate"><?= htmlspecialchars($row['food_name']) ?></h3>
                            <p class="text-[10px] text-slate-400 leading-tight mb-4 line-clamp-2"><?= htmlspecialchars($row['description'] ?: 'Fresh and tasty.') ?></p>
                            
                            <div class="flex gap-2 mt-auto">
                                <button onclick="addToCart(<?= $row['id'] ?>)" class="btn-add-cart">Add</button>
                                <button onclick="handleOrderClick(<?= htmlspecialchars(json_encode($row)) ?>)" class="btn-wa">
                                    <i class="fa-brands fa-whatsapp text-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full py-20 text-center">
                    <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">No products found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-container">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="w-16 h-16 bg-orange-100 text-orange-600 rounded-3xl flex items-center justify-center mx-auto mb-6 text-2xl"><i class="fa-solid fa-bolt"></i></div>
                <h3 class="text-sm font-black uppercase mb-3 text-slate-900">Fast Delivery</h3>
                <p class="text-slate-500 text-[11px] leading-relaxed">Hot and fresh food delivered within 30 mins.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-3xl flex items-center justify-center mx-auto mb-6 text-2xl"><i class="fa-solid fa-shield-heart"></i></div>
                <h3 class="text-sm font-black uppercase mb-3 text-slate-900">Hygienic</h3>
                <p class="text-slate-500 text-[11px] leading-relaxed">We maintain the highest safety and health standards.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-3xl flex items-center justify-center mx-auto mb-6 text-2xl"><i class="fa-brands fa-whatsapp"></i></div>
                <h3 class="text-sm font-black uppercase mb-3 text-slate-900">WhatsApp Support</h3>
                <p class="text-slate-500 text-[11px] leading-relaxed">Easy ordering and tracking through WhatsApp.</p>
            </div>
        </div>
    </div>
</section>

<div id="imagePreviewModal" onclick="closePreview()">
    <button class="absolute top-10 right-8 text-white text-4xl">&times;</button>
    <img id="previewImg" class="max-w-[90%] max-h-[85vh] rounded-2xl border-4 border-white/20 shadow-2xl" onclick="event.stopPropagation()">
</div>

<div id="orderModal">
    <div class="modal-body">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-black uppercase italic">Quick <span class="text-orange-500">Order</span></h2>
            <button onclick="closeModal()" class="text-slate-300 text-4xl">&times;</button>
        </div>
        
        <div id="modalItemDetails" class="bg-slate-50 p-4 rounded-2xl mb-6 flex items-center gap-4 border border-slate-100">
            </div>

        <div class="space-y-4">
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 ml-1">WhatsApp Number</label>
                <input type="text" id="modal-phone" placeholder="07x xxx xxxx" class="w-full p-4 mt-1 rounded-xl bg-slate-100 border-none outline-none text-sm font-bold focus:ring-2 focus:ring-orange-500/20 transition-all">
            </div>
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Delivery Address</label>
                <textarea id="modal-address" rows="2" placeholder="Street, City, Landmarks..." class="w-full p-4 mt-1 rounded-xl bg-slate-100 border-none outline-none text-sm font-bold focus:ring-2 focus:ring-orange-500/20 transition-all"></textarea>
            </div>
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Delivery Area</label>
                <select id="modal-location" class="w-full p-4 mt-1 rounded-xl bg-slate-100 text-sm font-bold outline-none cursor-pointer">
                    <option value="<?= $fee_local ?>" data-label="Inside Town">Inside Town (LKR <?= $fee_local ?>)</option>
                    <option value="<?= $fee_outside ?>" data-label="Outside Town">Outside Town (LKR <?= $fee_outside ?>)</option>
                </select>
            </div>
        </div>

        <button onclick="processQuickOrder()" id="modal-submit-btn" class="w-full bg-slate-900 text-white py-4 rounded-xl mt-8 font-black uppercase text-[11px] tracking-widest hover:bg-orange-600 transition-all active:scale-95">
            Confirm Order via WhatsApp
        </button>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration: 800, once: true });

let selectedProduct = null;
const isLoggedIn = <?= $is_logged_in ?>;

function openPreview(src) {
    document.getElementById('previewImg').src = src;
    document.getElementById('imagePreviewModal').style.display = 'flex';
}
function closePreview() {
    document.getElementById('imagePreviewModal').style.display = 'none';
}

function handleOrderClick(product) {
    if (!isLoggedIn) { window.location.href = 'login.php'; return; }
    selectedProduct = product;
    document.getElementById('modalItemDetails').innerHTML = `
        <img src="assets/images/products/${product.food_image}" class="w-14 h-14 rounded-xl object-cover shadow-sm">
        <div>
            <p class="font-black text-sm text-slate-900">${product.food_name}</p>
            <p class="text-xs text-orange-500 font-black uppercase">LKR ${product.price}</p>
        </div>`;
    document.getElementById('orderModal').style.display = 'flex';
}

function closeModal() { document.getElementById('orderModal').style.display = 'none'; }

function processQuickOrder() {
    const phone = document.getElementById('modal-phone').value.trim();
    const address = document.getElementById('modal-address').value.trim();
    const locSelect = document.getElementById('modal-location');
    const delivery = parseFloat(locSelect.value);
    const areaLabel = locSelect.options[locSelect.selectedIndex].getAttribute('data-label');
    const btn = document.getElementById('modal-submit-btn');

    if (!phone || !address) { alert("Please fill in your Phone and Address!"); return; }
    
    btn.disabled = true; 
    btn.innerText = 'Connecting...';

    const subtotal = parseFloat(selectedProduct.price);
    const total = subtotal + delivery;

    fetch('save-order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ phone, address, subtotal, delivery, total, items: [{id: selectedProduct.id, qty: 1, price: subtotal}] })
    })
    .then(res => res.json()).then(data => {
        if(data.status === 'success') {
            // PROFESSIONAL WHATSAPP MESSAGE FORMAT
            let msg = `*--- NEW ORDER ---*%0A`;
            msg += `*Site:* <?= $site_name ?>%0A%0A`;
            msg += `👤 *Customer:*%0A`;
            msg += `📞 WhatsApp: ${phone}%0A`;
            msg += `📍 Address: ${address}%0A`;
            msg += `🗺️ Area: ${areaLabel}%0A%0A`;
            msg += `🛒 *Item Details:*%0A`;
            msg += `• *${selectedProduct.food_name}*%0A`;
            msg += `  Qty: 1 x LKR ${subtotal.toLocaleString()}%0A`;
            msg += `  Shop: ${selectedProduct.shop_name || 'Admin'}%0A%0A`;
            msg += `💳 *Billing:*%0A`;
            msg += `Subtotal: LKR ${subtotal.toLocaleString()}.00%0A`;
            msg += `Delivery: LKR ${delivery.toLocaleString()}.00%0A`;
            msg += `*TOTAL: LKR ${total.toLocaleString()}.00*%0A%0A`;
            msg += `_Sent from Website Quick Checkout_`;

            window.open(`https://wa.me/<?= $admin_wa ?>?text=${msg}`, '_blank');
            location.reload();
        } else { 
            alert(data.message); 
            btn.disabled = false; 
            btn.innerText = "Confirm Order"; 
        }
    });
}

function addToCart(id) {
    fetch('cart-logic.php?action=add&id=' + id)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') { 
            const badge = document.getElementById('floating-badge');
            if(badge) { badge.innerText = data.cart_count; badge.style.display = 'flex'; }
            alert('Added to Bag!'); 
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>