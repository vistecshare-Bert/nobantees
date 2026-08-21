<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/helpers.php';
if (empty($_SESSION['ban_user'])) {
    header('Location: index.php');
    exit;
}
