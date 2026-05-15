<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

$message = "";

// --- LOGIC: Handle Adding New Vendor ---
if (isset($_POST['add_vendor'])) {
    $name = cleanInput($_POST['shop_name']);
    $type = $_POST['shop_type'];
    $address = cleanInput($_POST['shop_address']);
    
    $target_dir = "../assets/images/shops/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_ext = pathinfo($_FILES["shop_image"]["name"], PATHINFO_EXTENSION);
    $file_name = "shop_" . time() . "." . $file_ext;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["shop_image"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO vendors (shop_name, shop_type, shop_address, shop_image, is_active) VALUES (?, ?, ?, ?, 1)");
        if ($stmt->execute([$name, $type, $address, $file_name])) {
            $message = "success";
        }
    } else { $message = "error"; }
}

$vendors = $conn->query("SELECT * FROM vendors ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendors | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; scroll-behavior: smooth; }
        .vendor-card.hidden { display: none; }
    </style>
</head>
<body class="bg-[#f8fafc]">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex flex-col min-h-screen lg:ml-72 transition-all">
        <main class="flex-1 p-4 md:p-6 lg:p-8">
            
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Vendor <span class="text-orange-500">Hub</span></h1>
                    <p class="text-slate-500 text-[11px] font-bold uppercase tracking-wider">Directory & Onboarding</p>
                </div>

                <div class="relative w-full md:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="vendorSearch" placeholder="Search shops or locations..." 
                        class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-orange-500/20 outline-none transition-all">
                </div>
            </header>

            <?php if($message == "success"): ?>
                <div class="bg-emerald-500 text-white px-4 py-2 mb-6 rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-emerald-200">
                    <i class="fa-solid fa-check-double"></i> Vendor profile created!
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                
                <div class="xl:col-span-3">
                    <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm sticky top-6">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4">New Entry</h3>
                        <form method="POST" enctype="multipart/form-data" class="space-y-3">
                            <div>
                                <input type="text" name="shop_name" placeholder="Shop Name" required 
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-100 rounded-xl outline-none focus:border-orange-500 transition-all font-bold text-[11px]">
                            </div>
                            <div>
                                <select name="shop_type" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold text-[11px] cursor-pointer">
                                    <option value="hotel">Restaurant/Hotel</option>
                                    <option value="shop">General Shop</option>
                                    <option value="market">Market</option>
                                </select>
                            </div>
                            <div>
                                <textarea name="shop_address" rows="2" placeholder="Address..." required 
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-100 rounded-xl outline-none focus:border-orange-500 transition-all font-bold text-[11px] resize-none"></textarea>
                            </div>
                            <div>
                                <label class="group relative flex items-center justify-center w-full py-2.5 bg-orange-50 border border-dashed border-orange-200 rounded-xl cursor-pointer hover:bg-orange-100 transition-all">
                                    <span class="text-[10px] font-black text-orange-600 uppercase tracking-widest flex items-center gap-2">
                                        <i class="fa-solid fa-camera"></i> <span id="file-label">Cover Photo</span>
                                    </span>
                                    <input type="file" name="shop_image" required class="hidden" onchange="document.getElementById('file-label').innerText = this.files[0].name">
                                </label>
                            </div>
                            <button type="submit" name="add_vendor" class="w-full bg-slate-900 text-white py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-orange-600 transition-all shadow-md shadow-slate-200">
                                Create Vendor
                            </button>
                        </form>
                    </div>
                </div>

                <div class="xl:col-span-9 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="vendorGrid">
                    <?php foreach($vendors as $shop): ?>
                    <div class="vendor-card group bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-xl hover:shadow-slate-200 transition-all duration-300" 
                         data-name="<?= strtolower($shop['shop_name']) ?>" 
                         data-location="<?= strtolower($shop['shop_address']) ?>">
                        
                        <div class="relative h-28 overflow-hidden">
                            <img src="../assets/images/shops/<?= $shop['shop_image'] ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            <div class="absolute top-2 left-2">
                                <span class="px-2 py-0.5 bg-orange-500 text-white rounded-lg text-[8px] font-black uppercase tracking-tighter">
                                    <?= $shop['shop_type'] ?>
                                </span>
                            </div>
                            <div class="absolute bottom-2 left-3">
                                <h4 class="text-white text-sm font-black uppercase italic tracking-tighter truncate w-40">
                                    <?= htmlspecialchars($shop['shop_name']) ?>
                                </h4>
                            </div>
                        </div>
                        
                        <div class="p-3">
                            <div class="flex items-start gap-1.5 min-h-[32px]">
                                <i class="fa-solid fa-location-arrow text-slate-300 text-[9px] mt-0.5"></i>
                                <p class="text-[10px] text-slate-400 font-bold leading-tight line-clamp-2">
                                    <?= htmlspecialchars($shop['shop_address']) ?>
                                </p>
                            </div>
                            
                            <div class="mt-3 flex items-center justify-between pt-3 border-t border-slate-50">
                                <div class="flex gap-1">
                                    <a href="edit-vendor.php?id=<?= $shop['id'] ?>" class="w-7 h-7 bg-slate-50 text-slate-400 rounded-lg flex items-center justify-center hover:bg-slate-900 hover:text-white transition-all">
                                        <i class="fa-solid fa-pen text-[9px]"></i>
                                    </a>
                                    <button class="w-7 h-7 bg-red-50 text-red-400 rounded-lg flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">
                                        <i class="fa-solid fa-trash text-[9px]"></i>
                                    </button>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[8px] font-black text-slate-300 uppercase"><?= $shop['is_active'] ? 'Live' : 'Off' ?></span>
                                    <div class="w-2 h-2 rounded-full <?= $shop['is_active'] ? 'bg-emerald-500' : 'bg-red-500' ?> animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Real-time Search Logic
        const searchInput = document.getElementById('vendorSearch');
        const vendorCards = document.querySelectorAll('.vendor-card');

        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            vendorCards.forEach(card => {
                const name = card.dataset.name;
                const location = card.dataset.location;
                if (name.includes(term) || location.includes(term)) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>