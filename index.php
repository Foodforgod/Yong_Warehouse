<?php
require_once 'includes/init.php';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/user/dashboard.php');
    }
} else {
    redirect('/login.php');
}