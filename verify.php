<?php
require_once 'config/config.php';

// Fetch Site Settings for the UI
$settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$site_name = $settings['site_name'] ?? 'FCA FOOD';

$message = "";
$status = "error"; // Default status

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // 1. Check if the code exists and account is not already verified
    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_code = ? AND is_verified = 0");
    $stmt->execute([$code]);

    if ($stmt->rowCount() > 0) {
        // 2. Update user to verified
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE verification_code = ?");
        if ($update->execute([$code])) {
            $message = "Account verified successfully! You can now login.";
            $status = "success";
        } else {
            $message = "Something went wrong. Please try again later.";
        }
    } else {
        $message = "This link is invalid or has already been used.";
    }
} else {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account | <?= $site_name ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#fbfcfe] min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-[2.5rem] p-10 shadow-xl text-center border border-slate-50">
        <div class="w-20 h-20 <?= $status === 'success' ? 'bg-green-50 text-green-500' : 'bg-red-50 text-red-500' ?> rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
            <i class="fa-solid <?= $status === 'success' ? 'fa-check-circle' : 'fa-circle-xmark' ?>"></i>
        </div>
        
        <h2 class="text-2xl font-black text-slate-900 mb-2">
            <?= $status === 'success' ? 'Great News!' : 'Verification Failed' ?>
        </h2>
        <p class="text-slate-500 font-medium mb-8"><?= $message ?></p>

        <a href="login.php" class="inline-block bg-slate-900 text-white font-black px-10 py-4 rounded-2xl hover:bg-orange-600 transition-all active:scale-95 shadow-lg shadow-slate-200">
            Back to Login
        </a>
    </div>
</body>
</html>