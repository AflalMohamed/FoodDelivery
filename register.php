<?php
/**
 * User Registration with Email Verification
 * Authored by Aflal
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Session and Dependencies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'includes/functions.php';

// Load PHPMailer via Composer
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    die("Composer autoloader not found. Please run 'composer install' in the project folder.");
}

// These 'use' statements MUST be at the top level of the file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. Fetch Site Settings
$settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$site_name = $settings['site_name'] ?? 'FCA FOOD';
$site_logo = $settings['site_logo'] ?? '';

$error = "";
$success = "";

// 3. Handle Registration Form
if (isset($_POST['register'])) {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $v_code = bin2hex(random_bytes(16)); 

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $error = "This email is already registered!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, verification_code, is_verified) VALUES (?, ?, ?, ?, 'user', ?, 0)");
        
        if ($stmt->execute([$name, $email, $phone, $password, $v_code])) {
            
            /** * IMPORTANT: If 'new PHPMailer' fails, use the fully qualified name 
             * like: new \PHPMailer\PHPMailer\PHPMailer(true)
             */
            $mail = new PHPMailer(true);

            try {
                // SMTP Server Settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = 'aflaltest@gmail.com'; // CHANGE TO YOUR GMAIL
                $mail->Password   = 'vvskkurxcywvqipu'; // YOUR 16-DIGIT APP PASSWORD
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('your-email@gmail.com', $site_name);
                $mail->addAddress($email, $name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Account - ' . $site_name;

                $verify_link = BASE_URL . "verify.php?code=" . $v_code;

                $mail->Body = "
                    <div style='font-family: sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #eee; border-radius: 20px; overflow: hidden;'>
                        <div style='background: #111827; padding: 40px; text-align: center;'>
                            <h2 style='color: white; margin: 0;'>Welcome to $site_name</h2>
                        </div>
                        <div style='padding: 40px; text-align: center; color: #374151;'>
                            <p>Hi <b>$name</b>, please click the button below to verify your email.</p>
                            <a href='$verify_link' style='display: inline-block; background: #ea580c; color: white; padding: 15px 35px; text-decoration: none; border-radius: 12px; font-weight: 800; margin-top: 20px;'>Verify Account</a>
                        </div>
                    </div>";

                $mail->send();
                $success = "Registration successful! Please check your email inbox.";
            } catch (Exception $e) {
                $error = "Account created, but email failed. Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join <?= htmlspecialchars($site_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#fbfcfe] min-h-screen flex items-center justify-center p-4">

    <div class="bg-white rounded-[2.5rem] shadow-[0_20px_60px_rgba(0,0,0,0.04)] flex max-w-4xl w-full overflow-hidden border border-slate-50">
        
        <div class="hidden md:block w-1/2 bg-cover bg-center relative" style="background-image: url('https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=800&q=80');">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 to-transparent flex flex-col justify-end p-12">
                <h3 class="text-white text-3xl font-black leading-tight">Fresh Food,<br>Delivered Daily.</h3>
                <p class="text-white/70 mt-2 font-medium">Join the family today.</p>
            </div>
        </div>

        <div class="w-full md:w-1/2 p-8 md:p-12">
            <div class="mb-8">
                <?php if($site_logo): ?>
                    <img src="assets/img/<?= $site_logo ?>" class="h-10 w-auto mb-4" alt="Logo">
                <?php else: ?>
                    <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center text-white mb-4">
                        <i class="fa-solid fa-fire-flame-curved"></i>
                    </div>
                <?php endif; ?>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">Create Account</h2>
            </div>

            <?php if($error): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-2xl text-xs font-bold mb-6 flex items-center gap-3 border border-red-100">
                    <i class="fa-solid fa-circle-exclamation text-lg"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="bg-green-50 text-green-600 p-4 rounded-2xl text-xs font-bold mb-6 flex items-center gap-3 border border-green-100">
                    <i class="fa-solid fa-circle-check text-lg"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="relative group">
                    <i class="fa-solid fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                    <input type="text" name="name" placeholder="Full Name" required 
                        class="w-full p-4 pl-12 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:bg-white focus:border-orange-500 transition-all">
                </div>

                <div class="relative group">
                    <i class="fa-solid fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                    <input type="email" name="email" placeholder="Email Address" required 
                        class="w-full p-4 pl-12 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:bg-white focus:border-orange-500 transition-all">
                </div>

                <div class="relative group">
                    <i class="fa-solid fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                    <input type="text" name="phone" placeholder="Phone Number" required 
                        class="w-full p-4 pl-12 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:bg-white focus:border-orange-500 transition-all">
                </div>

                <div class="relative group">
                    <i class="fa-solid fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                    <input type="password" name="password" placeholder="Password" required 
                        class="w-full p-4 pl-12 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:bg-white focus:border-orange-500 transition-all">
                </div>
                
                <button name="register" class="w-full bg-slate-900 text-white font-black py-4 rounded-2xl hover:bg-orange-600 transition-all transform active:scale-95 shadow-xl shadow-slate-200 mt-2 flex items-center justify-center gap-2">
                    Register Now <i class="fa-solid fa-arrow-right-long text-xs"></i>
                </button>
            </form>

            <p class="mt-8 text-sm text-center text-slate-400 font-semibold">
                Member? <a href="login.php" class="text-orange-600 font-black hover:underline decoration-2">Sign In</a>
            </p>
        </div>
    </div>
</body>
</html>