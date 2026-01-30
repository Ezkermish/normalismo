<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/app.php';

if (!empty($_SESSION['user'])) {
  header('Location: ' . url('/dashboard/index.php'));
  exit;
}
header('Location: ' . url('/auth/login.php'));
exit;
