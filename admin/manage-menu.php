<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

$message = "";

// --- DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $img_stmt = $conn->prepare("SELECT food_image FROM products WHERE id = ?");
    $img_stmt->execute([$del_id]);
    $img_name = $img_stmt->fetchColumn();
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$del_id])) {
        if ($img_name && file_exists("../assets/images/products/" . $img_name)) {
            unlink("../assets/images/products/" . $img_name);
        }
        $message = "deleted";
    }
}

// --- ADD PRODUCT LOGIC ---
if (isset($_POST['add_product'])) {
    $vendor_id = (!empty($_POST['vendor_id'])) ? $_POST['vendor_id'] : null;
    $category  = $_POST['category']; 
    $food_name = cleanInput($_POST['food_name']);
    $price     = $_POST['price'];
    $desc      = cleanInput($_POST['description']); // Description included
    
    $target_dir = "../assets/images/products/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_ext = pathinfo($_FILES["food_image"]["name"], PATHINFO_EXTENSION);
    $file_name = "prod_" . time() . "." . $file_ext;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["food_image"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO products (vendor_id, category, food_name, price, description, food_image) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$vendor_id, $category, $food_name, $price, $desc, $file_name])) {
            $message = "success";
        }
    } else { $message = "error"; }
}

$vendors = $conn->query("SELECT id, shop_name FROM vendors ORDER BY shop_name ASC")->fetchAll();
$products = $conn->query("SELECT p.*, v.shop_name FROM products p LEFT JOIN vendors v ON p.vendor_id = v.id ORDER BY p.id DESC")->fetchAll();

$custom_categories = ["Biriyani", "Fast Food", "Drinks", "Snacks", "Desserts", "Other"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .product-row.hidden { display: none; }
    </style>
</head>
<body class="bg-slate-50 antialiased">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex flex-col min-h-screen lg:ml-72 transition-all duration-300">
        
        <main class="flex-1 p-4 md:p-10">
            
            <header class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">Menu <span class="text-orange-500 italic">Manager</span></h1>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">Total: <?= count($products) ?> Items</p>
                </div>
                <div class="relative w-full sm:w-72 md:w-96">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="menuSearch" placeholder="Quick search..." 
                        class="w-full pl-11 pr-4 py-3 md:py-4 bg-white border border-slate-200 rounded-2xl text-xs font-bold shadow-sm outline-none focus:border-orange-500 transition-all">
                </div>
            </header>

            <?php if($message == "success"): ?>
                <div class="bg-emerald-500 text-white px-6 py-4 mb-8 rounded-2xl text-[10px] font-bold uppercase tracking-widest flex items-center gap-3">
                    <i class="fa-solid fa-check-circle"></i> Item Added Successfully
                </div>
            <?php elseif($message == "deleted"): ?>
                <div class="bg-slate-900 text-white px-6 py-4 mb-8 rounded-2xl text-[10px] font-bold uppercase tracking-widest flex items-center gap-3">
                    <i class="fa-solid fa-trash text-orange-500"></i> Item Removed
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                
                <div class="xl:col-span-4 order-1">
                    <div class="bg-white p-6 md:p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/30 sticky top-6">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center text-sm"><i class="fa-solid fa-plus"></i></span>
                            <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">New Listing</h3>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Vendor Shop</label>
                                    <select name="vendor_id" class="w-full mt-1 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold text-xs focus:border-orange-500">
                                        <option value="">General</option>
                                        <?php foreach($vendors as $v): ?>
                                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['shop_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Category</label>
                                    <select name="category" required class="w-full mt-1 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold text-xs focus:border-orange-500">
                                        <?php foreach($custom_categories as $cat): ?>
                                            <option value="<?= $cat ?>"><?= $cat ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Price (LKR)</label>
                                    <input type="number" step="0.01" name="price" placeholder="0.00" required 
                                        class="w-full mt-1 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold text-xs focus:border-orange-500">
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Item Name</label>
                                <input type="text" name="food_name" required class="w-full mt-1 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold text-xs focus:border-orange-500">
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Description</label>
                                <textarea name="description" rows="3" placeholder="Ingredients or notes..." 
                                    class="w-full mt-1 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold text-xs focus:border-orange-500 resize-none"></textarea>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Image</label>
                                <input type="file" name="food_image" required class="w-full mt-1 text-[10px] font-bold text-slate-400">
                            </div>

                            <button type="submit" name="add_product" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-orange-600 transition-all shadow-lg active:scale-95">
                                Add Product
                            </button>
                        </form>
                    </div>
                </div>

                <div class="xl:col-span-8 order-2">
                    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto no-scrollbar">
                            <table class="w-full text-left min-w-[500px]">
                                <thead class="bg-slate-50/80 border-b border-slate-100">
                                    <tr>
                                        <th class="p-5 text-[10px] font-black text-slate-400 uppercase">Product</th>
                                        <th class="p-5 text-[10px] font-black text-slate-400 uppercase text-center">Category</th>
                                        <th class="p-5 text-[10px] font-black text-slate-400 uppercase">Price</th>
                                        <th class="p-5 text-[10px] font-black text-slate-400 uppercase text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <?php foreach($products as $item): ?>
                                    <tr class="product-row hover:bg-slate-50/50 transition-all group" data-search="<?= strtolower($item['food_name'] . ' ' . $item['category']) ?>">
                                        <td class="p-5">
                                            <div class="flex items-center gap-3">
                                                <img src="../assets/images/products/<?= $item['food_image'] ?>" class="w-10 h-10 md:w-12 md:h-12 rounded-xl object-cover shadow-sm ring-2 ring-slate-100 group-hover:ring-orange-200">
                                                <div class="max-w-[120px] md:max-w-none">
                                                    <p class="text-[10px] md:text-[11px] font-black text-slate-800 uppercase truncate"><?= htmlspecialchars($item['food_name']) ?></p>
                                                    <p class="text-[8px] font-bold text-slate-400 uppercase truncate"><?= htmlspecialchars($item['shop_name'] ?? 'Admin') ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-5 text-center">
                                            <span class="px-2 py-1 bg-orange-50 text-orange-600 text-[8px] font-black rounded uppercase"><?= htmlspecialchars($item['category']) ?></span>
                                        </td>
                                        <td class="p-5">
                                            <p class="text-[10px] font-black text-slate-900 whitespace-nowrap"><?= number_format($item['price'], 0) ?> LKR</p>
                                        </td>
                                        <td class="p-5">
                                            <div class="flex justify-end gap-2">
                                                <a href="edit-product.php?id=<?= $item['id'] ?>" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center hover:bg-slate-900 hover:text-white transition-all">
                                                    <i class="fa-solid fa-edit text-[10px]"></i>
                                                </a>
                                                <button onclick="confirmDelete(<?= $item['id'] ?>)" class="w-8 h-8 rounded-lg bg-red-50 text-red-300 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">
                                                    <i class="fa-solid fa-trash text-[10px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Live Search
        const searchInput = document.getElementById('menuSearch');
        const rows = document.querySelectorAll('.product-row');

        searchInput.addEventListener('input', () => {
            const term = searchInput.value.toLowerCase().trim();
            rows.forEach(row => {
                const data = row.getAttribute('data-search');
                row.classList.toggle('hidden', !data.includes(term));
            });
        });

        function confirmDelete(id) {
            if (confirm("Delete this item?")) {
                window.location.href = "manage-menu.php?delete_id=" + id;
            }
        }
    </script>
</body>
</html>