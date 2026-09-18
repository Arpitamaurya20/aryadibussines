<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// 🔐 Auth check FIRST
if (
    empty($_SESSION['UserType']) ||
    empty($_SESSION['pb_username'])
) {
    $_SESSION['SESSION_EXPIRED'] = true;
    header("Location: /admin/login.php");
    exit;
}
