<?php
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
require_once __DIR__ . '/../config.php';
header('Location: ' . BASE_PATH . '/admin/login.php');
exit;
