<?php
// config/app.php
declare(strict_types=1);

define('BASE_URL', '/normalismo');

function url(string $path): string {
  $path = '/' . ltrim($path, '/');
  return BASE_URL . $path;
}
