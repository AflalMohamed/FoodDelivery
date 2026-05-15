<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** * 1. DYNAMIC BRANDING FETCH
 */
try {
    // Database connection ulla header-aa irunthaal settings fetch pannum
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT site_name, site_logo FROM site_settings WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!isset($settings) || !$settings) {
        $settings = ['site_name' => 'Premium Delivery', 'site_logo' => ''];
    }
} catch (PDOException $e) {
    $settings = ['site_name' => 'Delivery App', 'site_logo' => ''];
}

// 2. Calculate Cart Count Safely for Initial Load
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += (int)$qty;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($settings['site_name']) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .nav-glass {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .brand-gradient {
            background: linear-gradient(135deg, #f97316 0%, #e11d48 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Mobile Menu Animation */
        #mobile-menu {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 0;
            overflow: hidden;
            opacity: 0;
        }
        #mobile-menu.active {
            max-height: 600px;
            opacity: 1;
            padding-bottom: 2rem;
            border-top: 1px solid #f1f5f9;
        }

        /* Dynamic Pop Animation for Cart Badge */
        .cart-pop { animation: cartPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes cartPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.4); background-color: #e11d48; }
            100% { transform: scale(1); }
        }

        /* Toast Notification Styling */
        #global-toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-150%);
            background: #0f172a;
            color: white;
            padding: 12px 24px;
            border-radius: 16px;
            z-index: 1000;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            transition: 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        #global-toast.show { transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body class="bg-slate-50">

    <div id="global-toast">
        <div class="bg-emerald-500 rounded-full p-1 text-[10px]">
            <i class="fa-solid fa-check"></i>
        </div>
        Added to bag successfully!
    </div>

    <nav class="nav-glass sticky top-0 z-[100] border-b border-slate-100 shadow-sm">
        <div class="container mx-auto px-4 lg:px-12">
            <div class="flex justify-between items-center h-20">
                
                <a href="index.php" class="flex items-center gap-2 md:gap-3 group active:scale-95 transition-transform">
                    <?php if (!empty($settings['site_logo'])): ?>
                        <img src="assets/img/<?= htmlspecialchars($settings['site_logo']) ?>" 
                             alt="Logo" class="h-8 md:h-10 w-auto object-contain transition-transform group-hover:scale-105">
                    <?php else: ?>
                        <div class="bg-gradient-to-br from-orange-500 to-rose-600 p-2 rounded-xl shadow-lg shadow-orange-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                    <?php endif; ?>

                    <span class="brand-gradient text-lg md:text-2xl font-extrabold tracking-tighter uppercase italic leading-none">
                        <?= htmlspecialchars($settings['site_name']) ?>
                    </span>
                </a>

                <div class="hidden md:flex items-center gap-8 font-bold text-[10px] text-slate-500 uppercase tracking-[0.15em]">
                    <a href="index.php" class="hover:text-orange-600 transition-colors">Home</a>
                    <a href="shops.php" class="hover:text-orange-600 transition-colors">Shops</a>
                    <a href="my-orders.php" class="hover:text-orange-600 transition-colors">My Orders</a>
                </div>

                <div class="flex items-center gap-2 md:gap-6">
                    <a href="cart.php" class="relative p-2.5 bg-slate-50 rounded-xl border border-slate-100 text-slate-800 hover:text-orange-600 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:h-6 md:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span id="cart-count-header" class="absolute -top-1 -right-1 bg-orange-600 text-white text-[9px] font-black w-5 h-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm transition-all <?= ($cart_count > 0) ? '' : 'hidden' ?>">
                            <?= (int)$cart_count ?>
                        </span>
                    </a>

                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="hidden md:flex items-center gap-4 border-l border-slate-100 pl-6">
                            <span class="text-[10px] font-black text-slate-700 uppercase">
                                Hi, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>
                            </span>
                            <a href="logout.php" class="text-[10px] font-bold text-rose-500 uppercase tracking-widest hover:underline">Logout</a>
                        </div>
                    <?php else: ?>
                        <div class="hidden md:flex items-center gap-4">
                            <a href="login.php" class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Login</a>
                            <a href="register.php" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-600 transition-all shadow-md active:scale-95">Join</a>
                        </div>
                    <?php endif; ?>

                    <button id="mobile-menu-btn" class="md:hidden p-1.5 text-slate-900 focus:outline-none">
                        <svg id="menu-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobile-menu" class="md:hidden bg-white border-t border-slate-50 px-6">
            <div class="py-6 space-y-5">
                <a href="index.php" class="block text-lg font-bold text-slate-800 border-b border-slate-50 pb-2">Home</a>
                <a href="shops.php" class="block text-lg font-bold text-slate-800 border-b border-slate-50 pb-2">Shops</a>
                <a href="my-orders.php" class="block text-lg font-bold text-slate-800 border-b border-slate-50 pb-2">My Orders</a>
                
                <div class="pt-4 flex flex-col gap-4">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center justify-between bg-slate-50 p-4 rounded-2xl">
                            <span class="font-bold text-slate-500 text-sm italic">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            <a href="logout.php" class="text-rose-600 font-black uppercase text-xs">Logout</a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="w-full text-center py-3.5 font-black text-slate-800 uppercase text-xs border border-slate-200 rounded-2xl">Sign In</a>
                        <a href="register.php" class="w-full text-center py-3.5 bg-slate-900 text-white font-black uppercase text-xs rounded-2xl shadow-lg">Create Account</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // --- 1. Mobile Menu Logic ---
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        const icon = document.getElementById('menu-icon');

        if(btn && menu) {
            btn.addEventListener('click', () => { 
                menu.classList.toggle('active');
                icon.style.transform = menu.classList.contains('active') ? 'rotate(90deg)' : 'rotate(0deg)';
            });
        }

        // --- 2. GLOBAL REAL-TIME UPDATER ---
        // Intha function moolama index.php la irunthu count update aagum
        window.updateGlobalCartCount = function(count) {
            // Header badge and Floating badge (if exists) update
            const badges = document.querySelectorAll('#cart-count-header, #cart-counter, #floating-badge');
            
            badges.forEach(badge => {
                if (badge) {
                    badge.innerText = count;
                    badge.style.display = count > 0 ? 'flex' : 'none';
                    
                    // Trigger Pop Animation
                    badge.classList.remove('cart-pop');
                    void badge.offsetWidth; // Trigger reflow
                    badge.classList.add('cart-pop');
                }
            });

            // Show success toast
            const toast = document.getElementById('global-toast');
            if(toast) {
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2500);
            }
        };
    </script>
</body>
</html>