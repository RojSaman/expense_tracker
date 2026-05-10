<?php
// ─────────────────────────────────────────────
//  db.php  –  shared database + session setup
// ─────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $conn;
$conn = new mysqli("127.0.0.1", "root", "", "expense_tracker");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// ── Helpers ──────────────────────────────────
function redirect($url) {
    header("Location: $url");
    exit;
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function currentUser() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'name' => $_SESSION['user_name'] ?? '',
    ];
}
?>