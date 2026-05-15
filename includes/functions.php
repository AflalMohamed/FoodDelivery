<?php
// includes/functions.php

/**
 * High Security Input Sanitization
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if the logged-in user is an Admin
 */
function isAdmin() {
    // Ensure session is started in config.php
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return true;
    }
    return false;
}
?>