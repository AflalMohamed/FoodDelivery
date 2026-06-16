<?php
/**
 * Secure User Email Verification Handler
 * Authored and Fixed by Aflal
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';

// Fetch Site Settings for the UI
$settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$site_name = $settings['site_name'] ?? 'FCA FOOD';

$message = "";
$status = "error"; // Default status

if (isset($_GET['code']) && !empty($_GET['code'])) {
    $code = trim($_GET['code']);

    try {
        // 1. Check if the code exists and account is not already verified
        $stmt = $conn->prepare("SELECT id FROM users WHERE verification_code = ? AND is_verified = 0 LIMIT 1");
        $stmt->execute([$code]);

        if ($stmt->rowCount() > 0) {
            // 2. Update user to verified 
            // Pro Tip: Code-ai NULL panrathuku bathila empty string '' maathina strict NOT NULL tables-laiyum crash aagathu
            $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = '' WHERE verification_code = ?");
            
            if ($update->execute([$code])) {
                $message = "Account verified successfully! You can now login.";
                $status = "success";
            } else {
                $message = "Something went wrong during the update. Please contact support.";
            }
        } else {
            // Check if user is already verified
            $check_already = $conn->prepare("SELECT id FROM users WHERE verification_code = '' AND is_verified = 1 LIMIT 1");
            // If checking fails, give invalid link message
            $message = "This verification link is invalid, expired, or your account is already verified.";
        }
    } catch (PDOException $e) {
        // If database structure crashes, it shows the exact error safely
        $message = "Database Error: " . $e->getMessage();
    }
} else {
    // Redirect to login if code parameter is missing
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account | <?= htmlspecialchars($site_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#fbfcfe] min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-[2.5rem] p-10 shadow-[0_20px_60px_rgba(0,0,0,0.03)] text-center border border-slate-50">
        
        <div class="w-20 h-20 <?= $status === 'success' ? 'bg-green-50 text-green-500' : 'bg-red-50 text-red-500' ?> rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
            <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
        </div>
        
        <h2 class="text-2xl font-black text-slate-900 mb-2 tracking-tight">
            <?= $status === 'success' ? 'Great News!' : 'Verification Failed' ?>
        </h2>
        <p class="text-slate-500 font-medium mb-8 text-sm leading-relaxed"><?= htmlspecialchars($message) ?></p>

        <a href="login.php" class="inline-block bg-slate-900 text-white font-black px-10 py-4 rounded-2xl hover:bg-orange-600 transition-all transform active:scale-95 shadow-xl shadow-slate-200 w-full">
            Back to Login
        </a>
    </div>
</body>
</html>