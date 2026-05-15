<?php
include 'config/config.php';
// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

$msg = "";
$status = "";

// Dynamic URL detection (Ippo folder name thappa varaathu)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$current_path = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
$auto_base_url = $protocol . "://" . $host . $current_path;

if (isset($_POST['reset_request'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $token = bin2hex(random_bytes(32)); 
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Delete old tokens
        $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->execute([$email]);

        // Store new token
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expiry]);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'aflaltest@gmail.com';
            $mail->Password   = 'vvskkurxcywvqipu'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('noreply@delivery.com', 'Premium Delivery');
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            
            // Auto-detected Link
            $reset_link = $auto_base_url . "reset-password.php?token=$token&email=$email";
            
            $mail->Body = "
                <div style='font-family: sans-serif; padding: 20px; border: 1px solid #f1f5f9; border-radius: 15px; max-width: 500px;'>
                    <h2 style='color: #f97316;'>Reset Password</h2>
                    <p style='color: #475569;'>Click the button below to reset your password. This link is valid for 1 hour.</p>
                    <a href='$reset_link' style='display: inline-block; padding: 14px 28px; background-color: #0f172a; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 14px;'>Reset My Password</a>
                    <hr style='margin-top: 25px; border: none; border-top: 1px solid #eee;'>
                    <p style='font-size: 11px; color: #94a3b8;'>If you didn't request this, please ignore this email.</p>
                </div>";
            
            $mail->send();
            $msg = "Success! Please check your email inbox.";
            $status = "success";
        } catch (Exception $e) {
            $msg = "Error: Email could not be sent.";
            $status = "error";
        }
    } else {
        $msg = "This email is not registered with us.";
        $status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Premium Delivery</title>
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
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter">Account <span class="text-orange-500 italic">Recovery</span></h2>
        </div>

        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-center <?= $status == 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase text-slate-400 ml-4 tracking-widest">Email Address</label>
                <div class="relative">
                    <i class="fa-solid fa-at absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                    <input type="email" name="email" required placeholder="Enter your email" 
                           class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-4 focus:ring-orange-500/10 transition-all text-sm font-bold">
                </div>
            </div>

            <button type="submit" name="reset_request" 
                    class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-xl shadow-slate-200 active:scale-95 transition-all hover:bg-orange-600">
                Get Reset Link
            </button>
        </form>

        <div class="mt-8 text-center border-t border-slate-50 pt-6">
            <a href="login.php" class="text-[10px] font-black uppercase text-slate-400 hover:text-dark transition-all tracking-widest">
                <i class="fa-solid fa-arrow-left-long mr-2"></i> Back to login
            </a>
        </div>
    </div>

</body>
</html>