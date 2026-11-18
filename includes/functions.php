<?php
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . BASE_PATH . $url);
    exit();
}

function get_categories($db) {
    $stmt = $db->query("SELECT DISTINCT category FROM items ORDER BY category ASC");
    return $stmt->fetchAll();
}