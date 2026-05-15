<?php
// 1. Independent Data Retrieval
if (!isset($conn)) {
    include_once '../config/config.php';
}

try {
    // Fetch Settings (Logo, Site Name, WhatsApp)
    $settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    // Fetch Rider Info
    $rider_name = 'Rider';
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $res = $stmt->fetch();
        $rider_name = $res['name'] ?? 'Rider';
    }
} catch (Exception $e) {
    $settings = ['site_name' => 'FCA FOOD'];
}

// 2. Logo Path Logic
$logo_filename = $settings['site_logo'] ?? '';
$logo_path = "../assets/images/" . $logo_filename;
if (!file_exists($logo_path)) {
    $logo_path = "../assets/img/" . $logo_filename;
}
?>

<div id="overlay" class="fixed inset-0 z-[60] hidden bg-slate-900/60 backdrop-blur-sm transition-all duration-300" onclick="toggleSidebar()"></div>

<aside id="sidebar" class="fixed top-0 left-0 h-full w-72 bg-slate-900 z-[70] shadow-2xl -translate-x-full transition-transform duration-500 ease-[cubic-bezier(0.4,0,0.2,1)]">
    <div class="p-6 h-full flex flex-col">
        
        <div class="flex items-center gap-3 mb-10 px-2 mt-4">
            <div class="flex-shrink-0">
                <?php if (!empty($logo_filename)): ?>
                    <img src="<?= $logo_path ?>" 
                         class="h-10 w-10 object-contain rounded-lg"
                         onerror="this.style.display='none'; document.getElementById('fallback-icon').style.display='flex';">
                <?php endif; ?>
                
                <div id="fallback-icon" style="display: <?= empty($logo_filename) ? 'flex' : 'none' ?>;" class="w-10 h-10 bg-orange-500 rounded-xl items-center justify-center shadow-lg shadow-orange-500/20">
                    <i class="fa-solid fa-utensils text-white text-sm"></i>
                </div>
            </div>
            
            <div class="flex flex-col">
                <span class="text-white font-black text-lg tracking-tighter uppercase italic leading-none">
                    <?= htmlspecialchars($settings['site_name'] ?? 'FCA FOOD') ?>
                </span>
                <span class="text-[8px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1">Delivery System</span>
            </div>
        </div>

        <div class="mb-8 p-4 bg-gradient-to-br from-slate-800/50 to-slate-800/20 rounded-[1.5rem] border border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-orange-500 rounded-xl flex items-center justify-center text-white text-lg font-black shadow-lg shadow-orange-500/20">
                    <?= strtoupper(substr($rider_name, 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <h3 class="text-white font-bold text-[13px] truncate"><?= htmlspecialchars($rider_name) ?></h3>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-wider">Active Now</p>
                    </div>
                </div>
            </div>
        </div>

        <nav class="space-y-1.5 flex-1 overflow-y-auto">
            <?php 
                $current_page = basename($_SERVER['PHP_SELF']); 
                function isActive($page, $current) {
                    return $page === $current ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white';
                }
            ?>

            <a href="index.php" class="flex items-center gap-4 p-4 rounded-2xl transition-all duration-300 group <?= isActive('index.php', $current_page) ?>">
                <i class="fa-solid fa-house-chimney w-5 text-center <?= $current_page == 'index.php' ? '' : 'group-hover:text-orange-500' ?>"></i>
                <span class="text-sm font-bold">Dashboard</span>
            </a>

            <a href="history.php" class="flex items-center gap-4 p-4 rounded-2xl transition-all duration-300 group <?= isActive('history.php', $current_page) ?>">
                <i class="fa-solid fa-receipt w-5 text-center <?= $current_page == 'history.php' ? '' : 'group-hover:text-orange-500' ?>"></i>
                <span class="text-sm font-bold">Task History</span>
            </a>

            <a href="earnings.php" class="flex items-center gap-4 p-4 rounded-2xl transition-all duration-300 group <?= isActive('earnings.php', $current_page) ?>">
                <i class="fa-solid fa-wallet w-5 text-center <?= $current_page == 'earnings.php' ? '' : 'group-hover:text-orange-500' ?>"></i>
                <span class="text-sm font-bold">My Earnings</span>
            </a>
            
            <div class="pt-8 mt-4 border-t border-slate-800/50 px-4">
                <p class="text-[10px] font-black text-slate-600 uppercase tracking-[0.2em] mb-4">Support</p>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $settings['admin_whatsapp'] ?? '') ?>" target="_blank" class="flex items-center gap-4 text-slate-400 hover:text-green-400 transition-colors py-2">
                    <i class="fa-brands fa-whatsapp text-xl"></i>
                    <span class="text-xs font-bold uppercase tracking-wider">Admin Help</span>
                </a>
            </div>
        </nav>

        <div class="pt-4 border-t border-slate-800/50">
            <a href="../logout.php" class="flex items-center justify-between bg-red-500/5 hover:bg-red-500/10 text-red-500 p-4 rounded-2xl transition-all group">
                <span class="text-[10px] font-black uppercase tracking-widest">Sign Out</span>
                <i class="fa-solid fa-arrow-right-from-bracket group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        const isHidden = sidebar.classList.contains('-translate-x-full');
        
        if (isHidden) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.add('opacity-100'), 10);
            document.body.style.overflow = 'hidden';
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.remove('opacity-100');
            setTimeout(() => overlay.classList.add('hidden'), 300);
            document.body.style.overflow = '';
        }
    }
</script>

<style>
    /* Premium thin scrollbar */
    nav::-webkit-scrollbar { width: 3px; }
    nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
</style>