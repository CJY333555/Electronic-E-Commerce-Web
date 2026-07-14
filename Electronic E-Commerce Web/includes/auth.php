<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

function clean($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Returns the profile picture path for the current logged-in user
// Falls back to default-user.png if none set
function getProfilePic($picField = null, $base = '') {
    if ($picField && file_exists($_SERVER['DOCUMENT_ROOT'] . '/Electronic E-Commerce Web/' . $picField)) {
        return $base . $picField;
    }
    return $base . 'assets/images/default-user.png';
}
