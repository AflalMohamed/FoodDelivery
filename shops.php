<?php 
include 'config/config.php'; 
include 'includes/header.php'; 

// Search for specific shops
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM vendors WHERE is_active = 1";
if(!empty($search)) {
    $query .= " AND (shop_name LIKE ? OR shop_type LIKE ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $conn->query($query);
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fdfdfd; }

    /* Header Styling */
    .hero-title { font-size: 2.5rem; font-weight: 900; letter-spacing: -1px; line-height: 1.1; }
    
    /* Search Bar Professional Look */
    .search-wrapper {
        background: white;
        padding: 6px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        border: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        transition: 0.3s;
    }
    .search-wrapper:focus-within { border-color: #f97316; box-shadow: 0 10px 30px rgba(249, 115, 22, 0.1); }
    .search-wrapper input { border: none; outline: none; padding: 0 15px; flex: 1; font-size: 0.9rem; font-weight: 600; color: #1e293b; }
    .search-btn { background: #0f172a; color: white; border: none; padding: 12px 25px; border-radius: 16px; font-weight: 800; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; cursor: pointer; transition: 0.3s; }
    .search-btn:hover { background: #f97316; transform: scale(1.02); }

    /* Shop Card Refinement */
    .shop-card {
        background: white;
        border-radius: 2.5rem;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: all 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
    }
    .shop-card:hover { transform: translateY(-10px); box-shadow: 0 30px 60px rgba(0,0,0,0.08); border-color: #f97316; }

    .image-container { position: relative; height: 240px; overflow: hidden; }
    .image-container img { width: 100%; height: 100%; object-fit: cover; transition: 0.8s; }
    .shop-card:hover .image-container img { transform: scale(1.1); }

    /* Gradient Overlay */
    .img-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(to bottom, transparent 40%, rgba(0,0,0,0.6) 100%);
        opacity: 0.6; transition: 0.3s;
    }
    .shop-card:hover .img-overlay { opacity: 0.9; }

    .shop-badge {
        position: absolute; top: 20px; right: 20px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(5px);
        color: #f97316;
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 0.65rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
        z-index: 10;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .shop-info { padding: 25px 30px; }
    .shop-name { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 5px; transition: 0.3s; }
    .shop-card:hover .shop-name { color: #f97316; }
    .shop-address { color: #64748b; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; }
    
    .explore-btn {
        margin-top: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #0f172a;
        letter-spacing: 1px;
    }
    .explore-btn i { font-size: 0.9rem; transition: 0.3s; }
    .shop-card:hover .explore-btn i { transform: translateX(5px); color: #f97316; }

    @media (max-width: 768px) {
        .hero-title { font-size: 2rem; text-align: center; }
        .hero-subtitle { text-align: center; }
    }
</style>

<div class="min-h-screen py-16 px-6">
    <div class="max-w-7xl mx-auto">
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-16 gap-8">
            <div data-aos="fade-right">
                <h1 class="hero-title uppercase italic">
                    Quality <span class="text-orange-600">Partners</span>
                </h1>
                <p class="hero-subtitle text-slate-500 font-semibold mt-2">Discover premium restaurants and local flavors.</p>
            </div>

            <div class="w-full md:w-[450px]" data-aos="fade-left">
                <form action="shops.php" method="GET" class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass text-slate-300 ml-4"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Find a restaurant or cuisine...">
                    <button class="search-btn">Search</button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if($stmt->rowCount() > 0): ?>
                <?php while($shop = $stmt->fetch()): ?>
                    <a href="shop-details.php?id=<?= $shop['id'] ?>" class="shop-card" data-aos="fade-up">
                        <div class="shop-badge"><?= $shop['shop_type'] ?></div>
                        
                        <div class="image-container">
                            <img src="assets/images/shops/<?= $shop['shop_image'] ?>" alt="<?= $shop['shop_name'] ?>">
                            <div class="img-overlay"></div>
                        </div>

                        <div class="shop-info">
                            <h3 class="shop-name"><?= htmlspecialchars($shop['shop_name']) ?></h3>
                            <p class="shop-address">
                                <i class="fa-solid fa-location-dot text-orange-500"></i>
                                <?= htmlspecialchars($shop['shop_address']) ?>
                            </p>
                            
                            <div class="explore-btn">
                                View Menu <i class="fa-solid fa-arrow-right-long"></i>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full py-20 text-center" data-aos="zoom-in">
                    <i class="fa-solid fa-store-slash text-slate-200 text-6xl mb-4"></i>
                    <h3 class="text-xl font-bold text-slate-400 italic uppercase">No partners found for "<?= htmlspecialchars($search) ?>"</h3>
                    <a href="shops.php" class="text-orange-600 font-black text-xs uppercase mt-4 block underline underline-offset-4">Show All Shops</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true,
        easing: 'ease-in-out'
    });
</script>

<?php include 'includes/footer.php'; ?>