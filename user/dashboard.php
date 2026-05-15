<?php
include '../config/config.php';
include '../includes/header.php'; // Ensure path is correct based on folder

// Security: Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch User's Order History
$stmt = $conn->prepare("
    SELECT o.*, r.name as rider_name 
    FROM orders o 
    LEFT JOIN users r ON o.rider_id = r.id 
    WHERE o.user_id = ? 
    ORDER BY o.order_date DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<div class="container mx-auto py-10 px-4 min-h-screen">
    <div class="mb-8" data-aos="fade-right">
        <h1 class="text-3xl font-bold">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
        <p class="text-gray-500">Track your recent food orders and history below.</p>
    </div>

    <?php if (count($orders) > 0): ?>
        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($orders as $order): ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100" data-aos="fade-up">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <span class="text-sm text-gray-400 font-mono">ORDER #<?= $order['id'] ?></span>
                            <h3 class="text-lg font-bold">Total: LKR <?= number_format($order['total_amount'], 2) ?></h3>
                            <p class="text-sm text-gray-500"><?= date('M d, Y - h:i A', strtotime($order['order_date'])) ?></p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <?php if($order['rider_name']): ?>
                                <span class="bg-blue-50 text-blue-600 px-4 py-2 rounded-full text-sm font-medium">
                                    🚴 Rider: <?= htmlspecialchars($order['rider_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="relative pt-4">
                        <div class="flex mb-2 items-center justify-between text-xs font-semibold uppercase">
                            <div class="<?= $order['status'] != 'cancelled' ? 'text-orange-600' : 'text-gray-400' ?>">Placed</div>
                            <div class="<?= in_array($order['status'], ['accepted', 'assigned', 'out_for_delivery', 'delivered']) ? 'text-orange-600' : 'text-gray-400' ?>">Accepted</div>
                            <div class="<?= in_array($order['status'], ['assigned', 'out_for_delivery', 'delivered']) ? 'text-orange-600' : 'text-gray-400' ?>">On the Way</div>
                            <div class="<?= $order['status'] == 'delivered' ? 'text-green-600' : 'text-gray-400' ?>">Delivered</div>
                        </div>
                        
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-100">
                            <?php
                                $progress = '0%';
                                if($order['status'] == 'accepted') $progress = '33%';
                                if($order['status'] == 'assigned' || $order['status'] == 'out_for_delivery') $progress = '66%';
                                if($order['status'] == 'delivered') $progress = '100%';
                                if($order['status'] == 'cancelled') $progress = '0%';
                            ?>
                            <div style="width:<?= $progress ?>" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-orange-500 transition-all duration-1000"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-20 bg-white rounded-2xl border border-dashed border-gray-300">
            <p class="text-gray-400">You haven't ordered anything yet. Start exploring shops!</p>
            <a href="<?= BASE_URL ?>index.php" class="mt-4 inline-block bg-orange-500 text-white px-6 py-2 rounded-full font-bold">Order Food</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>