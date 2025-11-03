<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB connection
$host = "localhost";
$user = "root";
$pass = "@2004Keren14";
$db   = "schooluniformdb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Auth helper
function require_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: auth/login.php");
        exit;
    }
}
