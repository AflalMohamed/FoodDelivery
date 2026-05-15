<?php 
try {
    $settings_query = $conn->query("SELECT * FROM site_settings WHERE id = 1 LIMIT 1");
    $settings = $settings_query->fetch(PDO::FETCH_ASSOC);

    $site_name = $settings['site_name'] ?? 'FoodExpress';
    $site_logo = $settings['site_logo'] ?? 'logo.png';
    $wa_number = $settings['admin_whatsapp'] ?? '94700000000';
    $location  = $settings['admin_address'] ?? 'Colombo, Sri Lanka';
} catch (PDOException $e) {
    $site_name = 'FoodExpress';
    $site_logo = 'logo.png';
    $wa_number = '94700000000';
    $location = 'Colombo, Sri Lanka';
}
?>

<footer class="relative bg-black text-white border-t border-white/10 overflow-hidden">

    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-orange-500/5 blur-[120px]"></div>

    <div class="container mx-auto px-6 py-16 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">

            <div data-aos="fade-up" class="space-y-6">
                <div class="flex items-center gap-3">
                    <img src="assets/img/<?= htmlspecialchars($site_logo) ?>" class="h-10 w-auto rounded-lg" alt="logo">
                    <div>
                        <h1 class="text-xl font-black uppercase tracking-tight italic"><?= htmlspecialchars($site_name) ?></h1>
                        <p class="text-[10px] text-orange-500 font-bold uppercase tracking-widest">Premium Delivery</p>
                    </div>
                </div>

                <p class="text-xs text-slate-400 leading-relaxed max-w-xs">
                    Order your favorite meals and get fast delivery to your doorstep with our high-speed service network.
                </p>

                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/5 border border-white/10 rounded-full">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
                        Developed by <a href="https://www.aflal.site" target="_blank" class="text-white hover:text-orange-500 transition">Aflal</a>
                    </span>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="#" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 border border-white/10 hover:bg-orange-500 hover:text-black transition-all duration-300">
                        <i class="fa-brands fa-facebook-f text-sm"></i>
                    </a>
                    <a href="#" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 border border-white/10 hover:bg-orange-500 hover:text-black transition-all duration-300">
                        <i class="fa-brands fa-instagram text-sm"></i>
                    </a>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa_number) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 border border-white/10 hover:bg-emerald-500 hover:text-black transition-all duration-300">
                        <i class="fa-brands fa-whatsapp text-sm"></i>
                    </a>
                </div>
            </div>

            <div data-aos="fade-up" data-aos-delay="100" class="grid grid-cols-2 gap-4">
                <div>
                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 mb-6">Explore</h4>
                    <ul class="space-y-3 text-xs font-bold text-slate-400">
                        <li><a class="hover:text-orange-500 transition" href="index.php">Home Feed</a></li>
                        <li><a class="hover:text-orange-500 transition" href="shops.php">All Vendors</a></li>
                        <li><a class="hover:text-orange-500 transition" href="my-orders.php">Track Orders</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 mb-6">Company</h4>
                    <ul class="space-y-3 text-xs font-bold text-slate-400">
                        <li><a class="hover:text-orange-500 transition" href="#">Privacy Policy</a></li>
                        <li><a class="hover:text-orange-500 transition" href="#">Terms of Use</a></li>
                        <li><a class="hover:text-orange-500 transition" href="#">Contact Us</a></li>
                    </ul>
                </div>
            </div>

            <div data-aos="fade-up" data-aos-delay="200" class="space-y-6">
                <div class="p-6 bg-white/5 border border-white/10 rounded-[2rem]">
                    <div class="flex items-start gap-3 mb-4">
                        <i class="fa-solid fa-location-dot text-orange-500 mt-1"></i>
                        <p class="text-[11px] font-medium text-slate-300 leading-tight">
                            <?= htmlspecialchars($location) ?>
                        </p>
                    </div>

                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa_number) ?>"
                       class="flex items-center justify-center gap-2 w-full bg-orange-500 hover:bg-white hover:text-black text-black font-black uppercase tracking-widest text-[10px] py-4 rounded-2xl transition-all duration-500 shadow-lg shadow-orange-500/20">
                       Support Center <i class="fa-brands fa-whatsapp text-sm"></i>
                    </a>
                    <p class="text-[9px] text-center text-slate-500 mt-3 font-bold uppercase tracking-tighter">Active Support 24/7</p>
                </div>
            </div>
        </div>

        <div class="mt-16 pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest">
                © <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All Rights Reserved.
            </p>
            <div class="flex gap-6">
                <span class="text-[9px] font-bold text-slate-700 uppercase italic">Secure Checkout <i class="fa-solid fa-shield-halved ml-1"></i></span>
            </div>
        </div>
    </div>
</footer>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    if (typeof AOS !== "undefined") {
        AOS.init({
            duration: 1000,
            once: true,
            offset: 50
        });
    }
});
</script>