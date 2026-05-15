<?php
// 1. Fetch Dynamic Data safely
try {
    // Standardizing the fetch to avoid array offset errors if DB is empty
    $stmt = $conn->prepare("SELECT site_name, site_logo FROM site_settings WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $sidebar_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $display_name = $sidebar_settings['site_name'] ?? 'Admin';
    $sidebar_logo = $sidebar_settings['site_logo'] ?? 'logo.png';
} catch (PDOException $e) {
    $display_name = 'Admin';
    $sidebar_logo = 'logo.png';
}

/**
 * PATH FIX: 
 * We use a relative path from the root. 
 * If your admin files are in /admin/ and assets are in /assets/
 */
$base_url = "../assets/img/"; 
$logo_url = $base_url . $sidebar_logo;

// Final check: if file physically doesn't exist, use a placeholder
if (empty($sidebar_logo) || !file_exists($_SERVER['DOCUMENT_ROOT'] . "/food/assets/img/" . $sidebar_logo)) {
    // If the check above is too strict for your local setup, keep it simple:
    if(empty($sidebar_logo)) { $logo_url = $base_url . "logo.png"; }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="lg:hidden bg-[#0f172a] text-white p-4 flex justify-between items-center sticky top-0 z-[50] border-b border-white/5 shadow-2xl backdrop-blur-md">
    <div class="flex items-center gap-3">
        <div class="p-1.5 bg-white/5 rounded-lg border border-white/10">
            <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo" class="w-6 h-6 object-contain">
        </div>
        <h2 class="text-xs font-black uppercase tracking-tighter italic">
            <?= htmlspecialchars($display_name) ?> <span class="text-orange-500">Core</span>
        </h2>
    </div>
    <button onclick="toggleSidebar()" class="w-10 h-10 bg-orange-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-orange-600/20 active:scale-90 transition-transform">
        <i class="fa-solid fa-bars-staggered text-sm"></i>
    </button>
</div>

<div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[60] hidden transition-opacity duration-300"></div>

<aside id="adminSidebar" class="fixed left-0 top-0 h-screen w-72 bg-[#0f172a] text-white flex flex-col shadow-2xl z-[70] transition-transform duration-300 ease-in-out -translate-x-full lg:translate-x-0 border-r border-white/5">
    
    <div class="p-8 pb-6">
        <div class="flex items-center gap-4">
            <div class="relative group">
                <div class="absolute -inset-2 bg-orange-600 rounded-xl blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                <div class="relative p-3 bg-white/5 border border-white/10 rounded-2xl backdrop-blur-xl">
                    <img src="<?= htmlspecialchars($logo_url) ?>" class="w-8 h-8 object-contain" alt="Brand Logo" onerror="this.src='../assets/img/logo.png'">
                </div>
            </div>
            <div>
                <h2 class="text-lg font-black text-white uppercase tracking-tighter italic leading-none truncate max-w-[140px]">
                    <?= htmlspecialchars($display_name) ?>
                </h2>
                <p class="text-[8px] text-orange-500 font-black uppercase tracking-[0.3em] mt-1">Management Pro</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-6 py-4 space-y-1 overflow-y-auto custom-scrollbar">
        <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4 ml-3 opacity-60">Main Operations</p>
        
        <a href="index.php" class="sidebar-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Overview</span>
        </a>

        <a href="orders.php" class="sidebar-link <?= $current_page == 'orders.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bolt-lightning text-orange-500"></i>
            <span>Live Orders</span>
        </a>

        <a href="manage-vendors.php" class="sidebar-link <?= $current_page == 'manage-vendors.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-shop"></i>
            <span>Partner Shops</span>
        </a>

        <a href="manage-menu.php" class="sidebar-link <?= $current_page == 'manage-menu.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-utensils"></i>
            <span>Menu Catalog</span>
        </a>

        <a href="register-rider.php" class="sidebar-link <?= $current_page == 'manage-riders.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-motorcycle"></i>
            <span>Fleet Riders</span>
        </a>

        <div class="pt-8 mt-6 border-t border-white/5">
            <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4 ml-3 opacity-60">System Admin</p>
            
            <a href="settings.php" class="sidebar-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gear"></i>
                <span>Global Settings</span>
            </a>
        </div>
    </nav>

    <div class="p-6 bg-slate-900/40 border-t border-white/5">
        <div class="flex items-center gap-4 mb-5 px-1">
            <div class="w-10 h-10 rounded-xl bg-orange-600 flex items-center justify-center font-black text-white shadow-lg shadow-orange-600/20 uppercase">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-black truncate text-white uppercase italic tracking-tight">
                    <?= htmlspecialchars($_SESSION['user_name'] ?? 'Super Admin') ?>
                </p>
                <p class="text-[8px] text-slate-500 font-black uppercase tracking-[0.2em]">Verified Manager</p>
            </div>
        </div>
        
        <a href="../logout.php" class="flex items-center justify-center gap-3 p-3.5 rounded-xl bg-red-500/5 text-red-500 border border-red-500/10 hover:bg-red-500 hover:text-white transition-all duration-300 group">
            <i class="fa-solid fa-power-off text-xs group-hover:rotate-12 transition-transform"></i>
            <span class="font-black text-[9px] uppercase tracking-[0.2em]">End Session</span>
        </a>
    </div>
</aside>

<style>
    .sidebar-link {
        display: flex; align-items: center; gap: 1.2rem; padding: 0.85rem 1.25rem;
        border-radius: 1.1rem; color: #64748b; font-size: 0.75rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.025em; transition: all 0.2s ease;
    }
    .sidebar-link i { width: 20px; text-align: center; font-size: 0.9rem; }
    .sidebar-link:hover { background: rgba(255, 255, 255, 0.03); color: #fff; }
    .sidebar-link.active {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: #fff; box-shadow: 0 10px 25px -8px rgba(234, 88, 12, 0.4);
    }
    .custom-scrollbar::-webkit-scrollbar { width: 3px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.05); border-radius: 10px; }
    #adminSidebar.show { transform: translateX(0) !important; }
</style>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const isOpen = sidebar.classList.toggle('show');
        
        overlay.classList.toggle('hidden', !isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : 'auto';
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            document.getElementById('adminSidebar').classList.remove('show');
            document.getElementById('sidebarOverlay').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    });
</script>