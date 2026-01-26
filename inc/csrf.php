<?php
declare(strict_types=1);

function csrf_token(): string {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function csrf_validate(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $token = $_POST['csrf_token'] ?? '';
  if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(400);
    exit('Solicitud inválida (CSRF).');
  }
}
