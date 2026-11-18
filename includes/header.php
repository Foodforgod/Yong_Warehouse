<?php
require_once __DIR__ . '/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/nav.php'; ?>
    <div class="main-content">
        <header class="top-header">
            <div class="header-title">Warehouse Management System</div>
            <div class="user-info">
                <span>👤 <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></strong> (<em><?php echo ucfirst($_SESSION['role'] ?? ''); ?></em>)</span>
            </div>
        </header>
        <div class="content-body">