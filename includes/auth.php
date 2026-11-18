<?php
require_once __DIR__ . '/init.php';

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_PATH . "/login.php");
        exit();
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: " . BASE_PATH . "/user/dashboard.php");
        exit();
    }
}