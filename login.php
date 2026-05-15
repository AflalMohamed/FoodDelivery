<?php
/**
 * Login Controller
 * Authored by Aflal
 */
require_once 'config/config.php';
require_once 'includes/functions.php';

// session_start() is already called in config.php

// Fetch Site Settings
$settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$site_name = $settings['site_name'] ?? 'FCA FOOD';
$site_logo = $settings['site_logo'] ?? '';

$error = "";
$success_msg = "";

// Handle Alerts from Redirections
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'registered') {
        $success_msg = "Registration successful! Please check your email to verify.";
    } elseif ($_GET['success'] == 'verified') {
        $success_msg = "Account verified! You can now login.";
    }
}

// standard Login Logic
if (isset($_POST['login'])) {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];

    // 1. Fetch User
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        
        // 2. Security Check: Email Verification
        // If the database shows 0, they cannot enter.
        if (isset($user['is_verified']) && $user['is_verified'] == 0) {
            $error = "Please verify your email address before logging in!";
        } else {
            // 3. Login Success - Regenerate Session for Security
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'] ?? 'user'; // Default to 'user' if null

            // 4. Role-Based Redirection
            $role = $_SESSION['role'];
            if ($role === 'admin') {
                header("Location: admin/index.php");
            } elseif ($role === 'rider') {
                header("Location: rider/index.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }
    } else {
        $error = "Invalid email or password!";
    }
}

// Google Login URL
$google_login_url = "google_auth.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login | <?= htmlspecialchars($site_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #fbfcfe; }
        .animate-fade { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-[440px] animate-fade">
        
        <div class="flex flex-col items-center mb-8 text-center">
            <?php if ($site_logo): ?>
                <img src="assets/img/<?= $site_logo ?>" alt="Logo" class="h-16 w-auto mb-4 drop-shadow-xl">
            <?php else: ?>
                <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-200 mb-4">
                    <i class="fa-solid fa-fire-flame-curved text-white text-2xl"></i>
                </div>
            <?php endif; ?>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight italic"><?= $site_name ?></h2>
            <p class="text-slate-400 text-sm font-semibold mt-1 tracking-wide">Enter your details to continue</p>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-[0_25px_70px_rgba(0,0,0,0.04)] p-8 md:p-10 border border-slate-100">
            
            <?php if($success_msg): ?>
                <div class="bg-green-50 text-green-600 p-4 rounded-2xl text-xs font-bold mb-6 flex items-center gap-3 border border-green-100 ring-4 ring-green-500/5">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                    <?= $success_msg ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-2xl text-xs font-bold mb-6 flex items-center gap-3 border border-red-100 ring-4 ring-red-500/5">
                    <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-2 tracking-widest">Email Address</label>
                    <div class="relative mt-1">
                        <i class="fa-solid fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                        <input type="email" name="email" placeholder="name@example.com" required 
                            class="w-full p-4 pl-12 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all text-sm font-semibold text-slate-700">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center px-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Password</label>
                        <a href="forgot-password.php" class="text-[10px] font-black text-orange-600 uppercase hover:underline">Forgot?</a>
                    </div>
                    <div class="relative mt-1">
                        <i class="fa-solid fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                        <input type="password" name="password" placeholder="••••••••" required 
                            class="w-full p-4 pl-12 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all text-sm font-semibold text-slate-700">
                    </div>
                </div>
                
                <button name="login" class="w-full bg-slate-900 text-white font-black py-4 rounded-2xl hover:bg-orange-600 hover:shadow-2xl hover:shadow-orange-500/30 transition-all transform active:scale-95 mt-2 flex items-center justify-center gap-2">
                    Sign In <i class="fa-solid fa-arrow-right-long text-xs"></i>
                </button>
            </form>

            <div class="relative my-8 text-center">
                <span class="bg-white px-4 text-slate-300 text-[10px] font-black uppercase relative z-10">Or login with</span>
                <div class="absolute top-1/2 w-full h-[1px] bg-slate-100"></div>
            </div>

            <a href="<?= $google_login_url ?>" class="w-full bg-white border border-slate-200 text-slate-700 font-bold py-4 rounded-2xl flex items-center justify-center gap-3 hover:bg-slate-50 transition-all transform active:scale-95 shadow-sm">
                <img src="https://www.svgrepo.com/show/355037/google.svg" class="w-5 h-5" alt="Google Logo">
                <span class="text-sm">Google Account</span>
            </a>

            <p class="text-center text-sm text-slate-400 font-semibold mt-8">
                New user? <a href="register.php" class="text-orange-600 font-black hover:underline underline-offset-4 decoration-2">Create Account</a>
            </p>
        </div>

        <p class="text-center text-[10px] text-slate-300 font-bold uppercase tracking-[0.2em] mt-8">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?> &bull; All Rights Reserved.
        </p>
    </div>

</body>
</html>