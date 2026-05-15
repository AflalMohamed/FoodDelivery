<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

$message = "";
$product_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$product_id) { header("Location: manage-menu.php"); exit; }

// --- GET PRODUCT DATA ---
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) { header("Location: manage-menu.php"); exit; }

// --- UPDATE LOGIC ---
if (isset($_POST['update_product'])) {
    $vendor_id = (!empty($_POST['vendor_id'])) ? $_POST['vendor_id'] : null;
    $category  = $_POST['category']; 
    $food_name = cleanInput($_POST['food_name']);
    $price     = $_POST['price'];
    $desc      = cleanInput($_POST['description']);
    $file_name = $product['food_image']; // Default to old image

    // Image Upload if provided
    if (!empty($_FILES["food_image"]["name"])) {
        $target_dir = "../assets/images/products/";
        $file_ext = pathinfo($_FILES["food_image"]["name"], PATHINFO_EXTENSION);
        $file_name = "prod_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["food_image"]["tmp_name"], $target_file)) {
            // Delete old image
            if (file_exists($target_dir . $product['food_image'])) {
                unlink($target_dir . $product['food_image']);
            }
        }
    }

    $update_stmt = $conn->prepare("UPDATE products SET vendor_id = ?, category = ?, food_name = ?, price = ?, description = ?, food_image = ? WHERE id = ?");
    if ($update_stmt->execute([$vendor_id, $category, $food_name, $price, $desc, $file_name, $product_id])) {
        $message = "success";
        // Refresh data
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
    } else {
        $message = "error";
    }
}

$vendors = $conn->query("SELECT id, shop_name FROM vendors ORDER BY shop_name ASC")->fetchAll();
$custom_categories = ["Biriyani", "Fast Food", "Drinks", "Snacks", "Desserts", "Other"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 antialiased">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex flex-col min-h-screen lg:ml-72 transition-all duration-300">
        <main class="flex-1 p-4 md:p-10 flex flex-col items-center justify-center">
            
            <div class="w-full max-w-2xl">
                <a href="manage-menu.php" class="inline-flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 hover:text-orange-500 transition-all">
                    <i class="fa-solid fa-arrow-left"></i> Back to Menu
                </a>

                <div class="bg-white p-6 md:p-10 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/30">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 bg-orange-500 text-white rounded-xl flex items-center justify-center text-sm shadow-lg shadow-orange-200"><i class="fa-solid fa-pen-to-square"></i></span>
                            <div>
                                <h3 class="text-lg font-black text-slate-800 tracking-tight">Edit Menu Item</h3>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">ID: #<?= $product['id'] ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if($message == "success"): ?>
                        <div class="bg-emerald-500 text-white px-6 py-4 mb-8 rounded-2xl text-[10px] font-bold uppercase tracking-widest flex items-center gap-3 animate-bounce">
                            <i class="fa-solid fa-check-circle"></i> Changes Saved Successfully
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Item Name</label>
                                <input type="text" name="food_name" value="<?= htmlspecialchars($product['food_name']) ?>" required 
                                    class="w-full mt-1.5 px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold text-xs focus:border-orange-500 transition-all">
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Category</label>
                                <select name="category" required class="w-full mt-1.5 px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold text-xs focus:border-orange-500">
                                    <?php foreach($custom_categories as $cat): ?>
                                        <option value="<?= $cat ?>" <?= ($product['category'] == $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Price (LKR)</label>
                                <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required 
                                    class="w-full mt-1.5 px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold text-xs focus:border-orange-500">
                            </div>

                            <div class="md:col-span-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Vendor Shop</label>
                                <select name="vendor_id" class="w-full mt-1.5 px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold text-xs focus:border-orange-500">
                                    <option value="">General / Admin</option>
                                    <?php foreach($vendors as $v): ?>
                                        <option value="<?= $v['id'] ?>" <?= ($product['vendor_id'] == $v['id']) ? 'selected' : '' ?>><?= htmlspecialchars($v['shop_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Description</label>
                                <textarea name="description" rows="3" class="w-full mt-1.5 px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold text-xs focus:border-orange-500 resize-none"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>

                            <div class="md:col-span-2 flex items-center gap-6 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="relative group">
                                    <img src="../assets/images/products/<?= $product['food_image'] ?>" id="preview" class="w-20 h-20 rounded-xl object-cover shadow-md ring-4 ring-white">
                                    <div class="absolute inset-0 bg-black/40 rounded-xl opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all cursor-pointer">
                                        <i class="fa-solid fa-camera text-white text-xs"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Update Photo</label>
                                    <input type="file" name="food_image" onchange="previewImage(this)" class="w-full mt-1 text-[10px] font-bold text-slate-400">
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="update_product" class="w-full bg-slate-900 text-white py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-orange-600 transition-all shadow-xl active:scale-95">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>