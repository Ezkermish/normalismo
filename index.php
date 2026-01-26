<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($_SESSION['user'])) {
  header('Location: ' . url('home.php'));
  exit;
}
header('Location: ' . url('login.php'));
exit;
