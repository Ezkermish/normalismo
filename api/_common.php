<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/config.php';

require_login();

header('Content-Type: application/json; charset=utf-8');

function json_ok(array $payload = []): void {
  echo json_encode(['ok' => true] + $payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function json_error(string $message, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
  exit;
}
