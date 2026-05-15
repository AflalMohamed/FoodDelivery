<?php 
include_once '../config/config.php';
include_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isAdmin()) { header("Location: ../login.php"); exit; }

$rider_id = $_GET['id'] ?? null;
if (!$rider_id) { header("Location: manage-riders.php"); exit; }

// Fetch Rider Details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'rider'");
$stmt->execute([$rider_id]);
$rider = $stmt->fetch();

if (!$rider) { header("Location: manage-riders.php"); exit; }

$message = "";

if (isset($_POST['update_rider'])) {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    
    // Update logic: Check if password needs changing
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, password=? WHERE id=?");
        $params = [$name, $email, $phone, $password, $rider_id];
    } else {
        $update = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
        $params = [$name, $email, $phone, $rider_id];
    }

    if ($update->execute($params)) {
        header("Location: manage-riders.php?msg=updated");
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
    <title>Edit Rider | <?= htmlspecialchars($rider['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#f8fafc]">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex flex-col min-h-screen lg:ml-72 transition-all">
        <main class="flex-1 p-4 md:p-8 flex items-center justify-center">
            
            <div class="w-full max-w-2xl bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">
                
                <div class="p-8 bg-slate-900 flex items-center justify-between">
                    <div>
                        <h2 class="text-white text-2xl font-black italic tracking-tighter uppercase">Edit <span class="text-orange-500">Rider Info</span></h2>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">System ID: #<?= $rider_id ?></p>
                    </div>
                    <a href="manage-riders.php" class="w-10 h-10 bg-white/10 text-white rounded-full flex items-center justify-center hover:bg-orange-500 transition-all">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </div>

                <form method="POST" class="p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Full Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($rider['name']) ?>" required 
                                class="w-full mt-1.5 px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-orange-500/10 focus:border-orange-500 transition-all font-bold text-sm">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($rider['email']) ?>" required 
                                class="w-full mt-1.5 px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-orange-500/10 focus:border-orange-500 transition-all font-bold text-sm">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Phone Number</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($rider['phone']) ?>" required 
                                class="w-full mt-1.5 px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-orange-500/10 focus:border-orange-500 transition-all font-bold text-sm">
                        </div>

                        <div class="col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Update Password <span class="lowercase text-slate-400 font-medium font-sans">(leave blank to keep current)</span></label>
                            <input type="password" name="password" placeholder="••••••••"
                                class="w-full mt-1.5 px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-orange-500/10 focus:border-orange-500 transition-all font-bold text-sm">
                        </div>
                    </div>

                    <div class="pt-4 flex flex-col sm:flex-row gap-3">
                        <button type="submit" name="update_rider" class="flex-1 bg-slate-900 text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-orange-600 transition-all shadow-lg shadow-slate-100">
                            Save Changes
                        </button>
                        <a href="register-rider.php" class="px-8 bg-slate-100 text-slate-500 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all text-center">
                            Discard
                        </a>
                    </div>
                </form>

            </div>
        </main>
    </div>

</body>
</html>