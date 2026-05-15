<?php
include 'config/config.php';

$msg = "";
$status = "";
$show_form = false;

// 1. URL-il irunthu Token matrum Email-ai edukka
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    $current_time = date("Y-m-d H:i:s");

    // 2. Token DB-il irukkiraatha matrum expiry aagiducha nu check pannavum
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expiry > ?");
    $stmt->execute([$email, $token, $current_time]);
    $reset_request = $stmt->fetch();

    if ($reset_request) {
        $show_form = true; // Token valid, form-ai kaamikkalaam
    } else {
        $msg = "Invalid or expired reset link. Please request a new one.";
        $status = "error";
    }
} else {
    header("Location: forgot-password.php");
    exit();
}

// 3. Password-ai Update pannum logic
if (isset($_POST['update_password'])) {
    $new_pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $msg = "Passwords do not match!";
        $status = "error";
        $show_form = true;
    } else {
        // Secure Hashing
        $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);

        // User table-il password-ai update pannavum
        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        if ($update->execute([$hashed_password, $email])) {
            
            // Token-ai use panniyachu, so ippo reset table-ilirunthu delete pannavum
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->execute([$email]);

            $msg = "Password updated successfully! You can login now.";
            $status = "success";
            $show_form = false; // Form-ai maraikkavum
        } else {
            $msg = "Something went wrong. Try again.";
            $status = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Premium Delivery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md bg-white rounded-[2.5rem] p-10 shadow-2xl shadow-slate-200/50 border border-slate-100">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-orange-50 text-orange-600 rounded-3xl flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-lock-open"></i>
            </div>
            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">New <span class="text-orange-500">Password</span></h2>
        </div>

        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-center <?= $status == 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' ?>">
                <?= $msg ?>
            </div>
            <?php if($status == 'success'): ?>
                <a href="login.php" class="block w-full bg-slate-900 text-white text-center py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest">Go to Login</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if($show_form): ?>
        <form method="POST" class="space-y-4">
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase text-slate-400 ml-4 tracking-widest">Enter New Password</label>
                <div class="relative">
                    <i class="fa-solid fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="password" name="password" required placeholder="••••••••" 
                           class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-4 focus:ring-orange-500/10 transition-all text-sm font-bold">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase text-slate-400 ml-4 tracking-widest">Confirm Password</label>
                <div class="relative">
                    <i class="fa-solid fa-check-double absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="password" name="confirm_password" required placeholder="••••••••" 
                           class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-4 focus:ring-orange-500/10 transition-all text-sm font-bold">
                </div>
            </div>

            <button type="submit" name="update_password" 
                    class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-xl shadow-slate-200 active:scale-95 transition-all hover:bg-orange-600">
                Update Password
            </button>
        </form>
        <?php endif; ?>

    </div>

</body>
</html>