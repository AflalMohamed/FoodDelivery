<?php
include 'config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = htmlspecialchars(strip_tags($_POST['name']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(strip_tags($_POST['phone']));
    $pass  = password_hash($_POST['password'], PASSWORD_BCRYPT); // High Security Hashing

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        echo "Email already registered!";
    } else {
        $sql = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')";
        $insert = $conn->prepare($sql);
        if ($insert->execute([$name, $email, $phone, $pass])) {
            header("Location: login.php?success=account_created");
        }
    }
}
?>