<?php
require_once '../config/config.php';

// செஷன் ஆரம்பிக்கப்பட்டுள்ளதா என்பதை உறுதிப்படுத்தவும்
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// ரைடர் லொகின் சரிபார்ப்பு
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    exit("Unauthorized Access! Please login as a rider.");
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $order_id = (int)$_GET['id'];
    $status = $_GET['status']; 
    $rider_id = $_SESSION['user_id'];

    // அனுமதிக்கப்பட்ட ஸ்டேட்டஸ்கள் மட்டும் (Security Check)
    $allowed_statuses = ['out_for_delivery', 'delivered'];
    
    if (in_array($status, $allowed_statuses)) {
        try {
            // ஆர்டர் ஸ்டேட்டஸை அப்டேட் செய்தல்
            // இது குறிப்பிட்ட ரைடருக்குரிய ஆர்டரை மட்டுமே அப்டேட் செய்யும்
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND rider_id = ?");
            
            if ($stmt->execute([$status, $order_id, $rider_id])) {
                // அப்டேட் வெற்றி பெற்றால் இன்டெக்ஸ் பக்கத்திற்கு செல்லும்
                header("Location: index.php?update=success");
                exit;
            } else {
                header("Location: index.php?update=failed");
                exit;
            }
        } catch (PDOException $e) {
            header("Location: index.php?update=error");
            exit;
        }
    } else {
        // தவறான ஸ்டேட்டஸ் அனுப்பப்பட்டால்
        header("Location: index.php?update=invalid_status");
        exit;
    }
} else {
    // ஐடி அல்லது ஸ்டேட்டஸ் இல்லையென்றால்
    header("Location: index.php");
    exit;
}