<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

$message = "";
$vendor_id = $_GET['id'] ?? null;

if (!$vendor_id) { header("Location: manage-vendors.php"); exit; }

// --- FETCH CURRENT DATA ---
$stmt = $conn->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) { header("Location: manage-vendors.php"); exit; }

// --- LOGIC: Handle Update ---
if (isset($_POST['update_vendor'])) {
    $name = cleanInput($_POST['shop_name']);
    $type = $_POST['shop_type'];
    $address = cleanInput($_POST['shop_address']);
    $status = isset($_POST['is_active']) ? 1 : 0;
    $file_name = $vendor['shop_image']; // Default to old image

    // If new image is uploaded
    if (!empty($_FILES["shop_image"]["name"])) {
        $target_dir = "../assets/images/shops/";
        $file_ext = pathinfo($_FILES["shop_image"]["name"], PATHINFO_EXTENSION);
        $file_name = "shop_" . time() . "." . $file_ext;
        move_uploaded_file($_FILES["shop_image"]["tmp_name"], $target_dir . $file_name);
        
        // Optional: Delete old image file here to save space
    }

    $update = $conn->prepare("UPDATE vendors SET shop_name=?, shop_type=?, shop_address=?, shop_image=?, is_active=? WHERE id=?");
    if ($update->execute([$name, $type, $address, $file_name, $status, $vendor_id])) {
        header("Location: manage-vendors.php?msg=updated");
        exit;
    } else {
        $message = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vendor | <?= htmlspecialchars($vendor['shop_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#f8fafc]">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex flex-col min-h-screen lg:ml-72 transition-all">
        <main class="flex-1 p-4 md:p-8 flex items-center justify-center">
            
            <div class="w-full max-w-2xl bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
                
                <div class="relative h-32 bg-slate-900 flex items-center px-8">
                    <div class="absolute inset-0 opacity-20" style="background-image: url('https://www.transparenttextures.com/patterns/carbon-fibre.png');"></div>
                    <div class="relative z-10">
                        <h2 class="text-white text-2xl font-black uppercase italic tracking-tighter">Edit <span class="text-orange-500">Vendor</span></h2>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.2em]">Update profile for ID: #<?= $vendor_id ?></p>
                    </div>
                    <a href="manage-vendors.php" class="ml-auto relative z-10 w-10 h-10 bg-white/10 text-white rounded-full flex items-center justify-center hover:bg-orange-500 transition-all">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </div>

                <form method="POST" enctype="multipart/form-data" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Shop Name</label>
                            <input type="text" name="shop_name" value="<?= htmlspecialchars($vendor['shop_name']) ?>" required 
                                class="w-full mt-1.5 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all font-bold text-xs">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Category</label>
                            <select name="shop_type" class="w-full mt-1.5 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold text-xs cursor-pointer">
                                <option value="hotel" <?= $vendor['shop_type'] == 'hotel' ? 'selected' : '' ?>>Restaurant/Hotel</option>
                                <option value="shop" <?= $vendor['shop_type'] == 'shop' ? 'selected' : '' ?>>General Shop</option>
                                <option value="market" <?= $vendor['shop_type'] == 'market' ? 'selected' : '' ?>>Market</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Status</label>
                            <label class="relative flex items-center mt-2 cursor-pointer">
                                <input type="checkbox" name="is_active" class="sr-only peer" <?= $vendor['is_active'] ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                <span class="ml-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Vendor Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Location Address</label>
                            <textarea name="shop_address" rows="3" required 
                                class="w-full mt-1.5 px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none focus:border-orange-500 transition-all font-bold text-xs resize-none"><?= htmlspecialchars($vendor['shop_address']) ?></textarea>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Shop Cover Photo</label>
                            <div class="mt-2 flex items-center gap-4">
                                <div class="relative w-20 h-20 rounded-xl overflow-hidden border-2 border-slate-100 group">
                                    <img id="preview" src="../assets/images/shops/<?= $vendor['shop_image'] ?>" class="w-full h-full object-cover">
                                    <label class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer transition-all">
                                        <i class="fa-solid fa-camera text-white text-xs"></i>
                                        <input type="file" name="shop_image" class="hidden" onchange="previewImage(this)">
                                    </label>
                                </div>
                                <p class="text-[9px] text-slate-400 leading-tight font-medium">Click image to upload a new one. Leave empty to keep current.</p>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 pt-6 border-t border-slate-50 flex gap-3">
                        <button type="submit" name="update_vendor" class="flex-1 bg-slate-900 text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-orange-600 transition-all shadow-lg shadow-slate-200">
                            Save All Changes
                        </button>
                        <a href="manage-vendors.php" class="px-8 bg-slate-100 text-slate-500 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all text-center">
                            Cancel
                        </a>
                    </div>
                </form>

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