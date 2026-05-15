<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

$message = "";

// 1. Fetch existing settings to prevent "offset on type bool" error
$check_exists = $conn->query("SELECT * FROM site_settings WHERE id = 1");
$current_settings = $check_exists->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['update_settings'])) {
    $site_name    = cleanInput($_POST['site_name']);
    $wa_number    = cleanInput($_POST['admin_whatsapp']);
    $admin_addr   = cleanInput($_POST['admin_address']);
    $fee_local    = cleanInput($_POST['delivery_fee_local']);
    $fee_outside  = cleanInput($_POST['delivery_fee_outside']);
    $header_text  = cleanInput($_POST['header_text']);
    $footer_text  = cleanInput($_POST['footer_text']);

    // Default logo name from DB or fallback
    $logo_name = $current_settings['site_logo'] ?? 'logo.png';

    // Handle File Upload
    if (!empty($_FILES['site_logo']['name'])) {
        $target_dir = "../assets/img/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_extension = pathinfo($_FILES["site_logo"]["name"], PATHINFO_EXTENSION);
        $new_file_name = "logo_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;
        
        if (move_uploaded_file($_FILES["site_logo"]["tmp_name"], $target_file)) {
            $logo_name = $new_file_name;
        }
    }

    if (!$current_settings) {
        // Logic: If table is empty, INSERT row 1
        $stmt = $conn->prepare("INSERT INTO site_settings (id, site_name, site_logo, admin_whatsapp, admin_address, delivery_fee_local, delivery_fee_outside, header_text, footer_text) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)");
    } else {
        // Logic: If row exists, UPDATE
        $stmt = $conn->prepare("UPDATE site_settings SET site_name=?, site_logo=?, admin_whatsapp=?, admin_address=?, delivery_fee_local=?, delivery_fee_outside=?, header_text=?, footer_text=? WHERE id=1");
    }

    if ($stmt->execute([$site_name, $logo_name, $wa_number, $admin_addr, $fee_local, $fee_outside, $header_text, $footer_text])) {
        $message = "success";
        // Refresh data after update
        $current_settings = $conn->query("SELECT * FROM site_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    }
}

// Fallback for form display if DB is still empty
$settings = $current_settings ?: [
    'site_name' => '', 'site_logo' => 'logo.png', 'admin_whatsapp' => '', 
    'admin_address' => '', 'delivery_fee_local' => 0, 'delivery_fee_outside' => 0, 
    'header_text' => '', 'footer_text' => ''
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — Admin Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.01em; }
        .input-glow:focus { box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.08); border-color: #f97316; }
    </style>
</head>
<body class="bg-[#fcfcfd] text-slate-900">

    <?php include 'includes/sidebar.php'; ?>

    <div class="lg:ml-72 transition-all duration-300">
        <main class="max-w-5xl mx-auto p-4 md:p-10">
            
            <div class="h-2 md:h-0"></div>

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 md:mb-12">
                <div>
                    <h1 class="text-xl md:text-2xl font-extrabold tracking-tight text-slate-900">Configuration</h1>
                    <p class="text-slate-500 text-xs md:text-sm font-medium">Global system preferences and branding</p>
                </div>
                <?php if($message == "success"): ?>
                <div class="w-full sm:w-auto bg-emerald-50 text-emerald-600 px-4 py-2 rounded-xl text-xs font-bold border border-emerald-100 flex items-center gap-2">
                    <i class="fa-solid fa-check-circle"></i> Saved successfully
                </div>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-10">
                    
                    <div class="lg:col-span-3 flex lg:flex-col gap-2 overflow-x-auto pb-2 lg:pb-0 no-scrollbar">
                        <button type="button" class="whitespace-nowrap flex items-center gap-3 px-5 py-3 bg-white text-orange-600 border border-slate-100 rounded-xl font-bold text-xs shadow-sm shadow-slate-100">
                            <i class="fa-solid fa-sliders"></i> General
                        </button>
                        <button type="button" class="whitespace-nowrap flex items-center gap-3 px-5 py-3 text-slate-400 hover:bg-slate-50 rounded-xl font-bold text-xs transition-all">
                            <i class="fa-solid fa-truck"></i> Logistics
                        </button>
                    </div>

                    <div class="lg:col-span-9 space-y-6 md:space-y-8">
                        
                        <section class="bg-white border border-slate-100 rounded-2xl md:rounded-3xl p-5 md:p-8 shadow-sm">
                            <div class="flex items-center gap-2 mb-6 md:mb-8">
                                <span class="w-1 h-5 bg-orange-500 rounded-full"></span>
                                <h3 class="font-extrabold text-slate-800 text-sm tracking-tight">Identity & Visuals</h3>
                            </div>

                            <div class="flex flex-col md:flex-row gap-8 md:gap-10">
                                <div class="flex flex-col items-center">
                                    <div class="relative group w-32 h-32 md:w-40 md:h-40 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center p-4 overflow-hidden">
                                        <img id="logoPreview" src="../assets/img/<?= htmlspecialchars($settings['site_logo']) ?>" 
                                            class="max-w-full max-h-full object-contain drop-shadow-sm transition-transform group-hover:scale-105"
                                            onerror="this.src='../assets/img/logo.png'">
                                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <i class="fa-solid fa-camera text-white"></i>
                                        </div>
                                        <input type="file" name="site_logo" onchange="previewImage(event)" class="absolute inset-0 opacity-0 cursor-pointer">
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-bold mt-3 uppercase tracking-widest">Brand Logo</p>
                                </div>

                                <div class="flex-1 space-y-5">
                                    <div>
                                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Platform Name</label>
                                        <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" required 
                                            class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:bg-white input-glow transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Support WhatsApp</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold border-r border-slate-200 pr-3">+94</span>
                                            <input type="text" name="admin_whatsapp" value="<?= htmlspecialchars($settings['admin_whatsapp']) ?>" required 
                                                class="w-full bg-slate-50/50 border border-slate-200 rounded-xl pl-16 pr-4 py-3 text-sm font-semibold outline-none focus:bg-white input-glow transition-all">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="bg-white border border-slate-100 rounded-2xl md:rounded-3xl p-5 md:p-8 shadow-sm">
                            <div class="flex items-center gap-2 mb-6">
                                <span class="w-1 h-5 bg-slate-800 rounded-full"></span>
                                <h3 class="font-extrabold text-slate-800 text-sm tracking-tight">Logistics Control</h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                                <div class="bg-slate-50/50 border border-slate-100 p-4 rounded-xl">
                                    <label class="block text-[9px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Local Fee</label>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xs font-bold text-slate-400">Rs.</span>
                                        <input type="number" step="0.01" name="delivery_fee_local" value="<?= $settings['delivery_fee_local'] ?>" 
                                            class="w-full bg-transparent border-none p-0 text-xl font-extrabold text-slate-800 outline-none focus:ring-0">
                                    </div>
                                </div>

                                <div class="bg-slate-50/50 border border-slate-100 p-4 rounded-xl">
                                    <label class="block text-[9px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Standard Fee</label>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xs font-bold text-slate-400">Rs.</span>
                                        <input type="number" step="0.01" name="delivery_fee_outside" value="<?= $settings['delivery_fee_outside'] ?>" 
                                            class="w-full bg-transparent border-none p-0 text-xl font-extrabold text-slate-800 outline-none focus:ring-0">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="bg-white border border-slate-100 rounded-2xl md:rounded-3xl p-5 md:p-8 shadow-sm">
                            <div class="flex items-center gap-2 mb-6">
                                <span class="w-1 h-5 bg-slate-200 rounded-full"></span>
                                <h3 class="font-extrabold text-slate-800 text-sm tracking-tight">Interface Content</h3>
                            </div>

                            <div class="space-y-5">
                                <div>
                                    <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Office Headquarters</label>
                                    <input type="text" name="admin_address" value="<?= htmlspecialchars($settings['admin_address']) ?>" 
                                        class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:bg-white input-glow transition-all">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Announcements</label>
                                        <input type="text" name="header_text" value="<?= htmlspecialchars($settings['header_text']) ?>" 
                                            class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:bg-white input-glow transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Copyright Tagline</label>
                                        <input type="text" name="footer_text" value="<?= htmlspecialchars($settings['footer_text']) ?>" 
                                            class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:bg-white input-glow transition-all">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="flex justify-end sticky bottom-4 lg:relative lg:bottom-0 z-10">
                            <button type="submit" name="update_settings" 
                                class="w-full md:w-auto bg-slate-900 text-white px-8 py-4 rounded-xl md:rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-orange-600 transition-all shadow-2xl shadow-slate-200 active:scale-95 flex items-center justify-center gap-3">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Sync Configuration
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('logoPreview');
                output.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>