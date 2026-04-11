<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_PATH . '/admin/login.php');
    exit;
}

function getCurrentAdmin(): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: [];
}

function getSetting(string $key, string $default = ''): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function countUnread(): int {
    $db = getDB();
    return (int) $db->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
}
