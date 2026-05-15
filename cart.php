<?php 
include 'config/config.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Settings Fetch
$settings = $conn->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$admin_phone = $settings['admin_whatsapp'] ?? '94775571573';
$fee_local   = (float)($settings['delivery_fee_local'] ?? 100.00);
$fee_outside = (float)($settings['delivery_fee_outside'] ?? 150.00);

$is_logged_in = isset($_SESSION['user_id']);
$cart_items = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $stmt = $conn->query("SELECT p.*, v.shop_name FROM products p JOIN vendors v ON p.vendor_id = v.id WHERE p.id IN ($ids)");
    while ($row = $stmt->fetch()) {
        $row['qty'] = $_SESSION['cart'][$row['id']];
        $row['item_total'] = $row['price'] * $row['qty'];
        $subtotal += $row['item_total'];
        $cart_items[] = $row;
    }
}

$initial_grand_total = $subtotal + $fee_local;
include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    .cart-item-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: white;
        border-radius: 18px;
        border: 1px solid #f1f5f9;
        margin-bottom: 12px;
    }
    .thumb { width: 65px; height: 65px; border-radius: 12px; object-fit: cover; flex-shrink: 0; }
    .qty-btn { width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: bold; font-size: 14px; }
    .btn-trash { color: #94a3b8; padding: 10px; font-size: 18px; transition: 0.2s; }
    .btn-trash:hover { color: #ef4444; }
    .text-title { font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.2; }
    .text-shop { font-size: 9px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px; }
</style>

<div id="order-loader" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-2xl shadow-xl text-center">
        <div class="w-10 h-10 border-4 border-orange-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
        <p class="text-xs font-black text-slate-700 uppercase tracking-widest">Processing Order...</p>
    </div>
</div>

<div class="bg-slate-50 min-h-screen pb-24">
    <div class="container mx-auto py-6 px-4">
        <h1 class="text-xl font-black text-slate-900 uppercase italic mb-6">Your <span class="text-orange-600">Bag</span></h1>

        <?php if (!empty($cart_items)): ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item-card" id="item-row-<?= $item['id'] ?>">
                    <img src="assets/images/products/<?= $item['food_image'] ?>" class="thumb">
                    <div class="flex-1 min-w-0">
                        <p class="text-shop" data-shop-name="<?= htmlspecialchars($item['shop_name']) ?>"><?= htmlspecialchars($item['shop_name']) ?></p>
                        <h3 class="text-title product-title truncate"><?= htmlspecialchars($item['food_name']) ?></h3>
                        <p class="text-orange-600 font-bold text-xs">LKR <span class="item-price"><?= number_format($item['price'], 2, '.', '') ?></span></p>
                        
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-50">
                            <div class="flex items-center gap-4">
                                <button onclick="updateQty(<?= $item['id'] ?>, -1)" class="qty-btn">−</button>
                                <span class="text-xs font-black text-slate-700" id="qty-<?= $item['id'] ?>"><?= $item['qty'] ?></span>
                                <button onclick="updateQty(<?= $item['id'] ?>, 1)" class="qty-btn">+</button>
                            </div>
                            <button onclick="confirmRemove(<?= $item['id'] ?>)" class="btn-trash"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="lg:col-span-4">
                <div class="bg-slate-900 rounded-[2rem] p-6 text-white shadow-xl lg:sticky lg:top-6">
                    <h2 class="text-[10px] font-black uppercase text-slate-500 mb-4 pb-2 border-b border-white/10">Delivery Info</h2>
                    <div class="space-y-3 mb-6">
                        <input type="text" id="phone" placeholder="WhatsApp Number" class="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-xs text-white outline-none focus:border-orange-500" <?= !$is_logged_in ? 'disabled' : '' ?>>
                        <textarea id="address" placeholder="Full Address" class="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-xs text-white outline-none focus:border-orange-500" rows="2" <?= !$is_logged_in ? 'disabled' : '' ?>></textarea>
                        <select id="location" onchange="calculateFinal()" class="w-full bg-slate-800 border border-white/10 p-3 rounded-xl text-xs text-white" <?= !$is_logged_in ? 'disabled' : '' ?>>
                            <option value="<?= $fee_local ?>">Inside Town</option>
                            <option value="<?= $fee_outside ?>">Outside Town</option>
                        </select>
                    </div>

                    <div class="space-y-2 mb-6 pt-4 border-t border-white/10">
                        <div class="flex justify-between text-[11px] font-bold text-slate-400 uppercase"><span>Subtotal</span> <span class="text-white">LKR <span id="sub-display"><?= number_format($subtotal, 2) ?></span></span></div>
                        <div class="flex justify-between text-[11px] font-bold text-slate-400 uppercase"><span>Delivery</span> <span class="text-white">LKR <span id="delivery-display"><?= number_format($fee_local, 2) ?></span></span></div>
                        <div class="flex justify-between text-lg font-black text-orange-500 pt-2"><span>Total</span> <span>LKR <span id="grand-display"><?= number_format($initial_grand_total, 2) ?></span></span></div>
                    </div>

                    <?php if ($is_logged_in): ?>
                        <button onclick="submitOrder()" class="w-full bg-orange-600 hover:bg-orange-500 py-4 rounded-xl text-xs font-black uppercase tracking-widest transition-all">Confirm Order</button>
                    <?php else: ?>
                        <a href="login.php" class="block w-full bg-slate-800 text-center py-4 rounded-xl text-xs font-black uppercase">Login to Checkout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-20 bg-white rounded-[2rem] border border-slate-100"><h2 class="text-sm font-bold text-slate-300 uppercase">Bag is empty</h2><a href="index.php" class="mt-4 inline-block text-orange-600 font-black text-xs uppercase">Menu</a></div>
        <?php endif; ?>
    </div>
</div>

<script>
function calculateFinal() {
    const sub = parseFloat(document.getElementById('sub-display').innerText.replace(/,/g, ''));
    const delivery = parseFloat(document.getElementById('location').value);
    document.getElementById('delivery-display').innerText = delivery.toFixed(2);
    document.getElementById('grand-display').innerText = (sub + delivery).toLocaleString('en-US', {minimumFractionDigits: 2});
}

function recalcUI() {
    let sub = 0;
    document.querySelectorAll('[id^="item-row-"]').forEach(row => {
        const p = parseFloat(row.querySelector('.item-price').innerText.replace(/,/g, ''));
        const q = parseInt(row.querySelector('[id^="qty-"]').innerText);
        sub += (p * q);
    });
    document.getElementById('sub-display').innerText = sub.toLocaleString('en-US', {minimumFractionDigits: 2});
    calculateFinal();
}

function updateQty(id, change) {
    const qtySpan = document.getElementById('qty-' + id);
    let newVal = parseInt(qtySpan.innerText) + change;
    if (newVal < 1) return;
    fetch('update-cart.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `id=${id}&qty=${newVal}` })
    .then(() => { qtySpan.innerText = newVal; recalcUI(); });
}

function confirmRemove(id) {
    if(!confirm("Remove Item?")) return;
    document.getElementById('order-loader').classList.remove('hidden');
    fetch('update-cart.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `id=${id}&action=remove` })
    .then(res => res.json()).then(() => location.reload());
}

async function submitOrder() {
    const phone = document.getElementById('phone').value.trim();
    const address = document.getElementById('address').value.trim();
    const locationSelect = document.getElementById('location');
    const area = locationSelect.options[locationSelect.selectedIndex].text;
    const loader = document.getElementById('order-loader');

    if(!phone || !address) { alert("Please enter phone and address!"); return; }

    loader.classList.remove('hidden');

    const subtotal = document.getElementById('sub-display').innerText;
    const delivery = document.getElementById('delivery-display').innerText;
    const grandTotal = document.getElementById('grand-display').innerText;

    let items = [];
    let waItems = "";

    document.querySelectorAll('[id^="item-row-"]').forEach(row => {
        const id = row.id.replace('item-row-', '');
        const name = row.querySelector('.product-title').innerText;
        const qty = row.querySelector('[id^="qty-"]').innerText;
        const price = row.querySelector('.item-price').innerText;
        const shop = row.querySelector('.text-shop').dataset.shopName;
        
        waItems += `%0A• *${name}*%0A  Qty: ${qty} | LKR ${parseFloat(price * qty).toLocaleString()}%0A  [Shop: ${shop}]%0A`;
        items.push({ id, name, qty, price });
    });

    try {
        const response = await fetch('save-order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                phone, 
                address, 
                subtotal: parseFloat(subtotal.replace(/,/g, '')), 
                delivery: parseFloat(delivery.replace(/,/g, '')), 
                total: parseFloat(grandTotal.replace(/,/g, '')), 
                items 
            })
        });

        const data = await response.json();

        if(data.status === 'success') {
            const message = `*New Order - TownFood*%0A--------------------------%0A📱 Phone: ${phone}%0A📍 Address: ${address}%0A📍 Area: ${area}%0A--------------------------%0A${waItems}%0A--------------------------%0ASubtotal: LKR ${subtotal}%0ADelivery: LKR ${delivery}%0A*Total: LKR ${grandTotal}*`;
            
            // 1. Open WhatsApp
            window.open(`https://wa.me/<?= $admin_phone ?>?text=${message}`, '_blank');
            
            // 2. Clear Cart & Redirect to Home
            window.location.href = 'index.php?order=success';
        } else {
            throw new Error(data.message);
        }
    } catch (err) {
        loader.classList.add('hidden');
        alert("Error: " + err.message);
    }
}
</script>

<?php include 'includes/footer.php'; ?>