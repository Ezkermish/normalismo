<?php
declare(strict_types=1);

/**
 * Configuración de BD:
 * Recomendado: definir variables de entorno en su hosting/cPanel:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS
 */
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'enpem_normalismo';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// Ruta base de la aplicación (para entornos con múltiples proyectos)
$APP_BASE_URL = getenv('APP_BASE_URL') ?: '/normalismo';
define('BASE_URL', rtrim($APP_BASE_URL, '/'));

function url(string $path = ''): string {
  $base = BASE_URL;
  $path = ltrim($path, '/');
  return $path === '' ? $base . '/' : $base . '/' . $path;
}

function db(): mysqli {
  static $conn = null;
  global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;

  if ($conn instanceof mysqli) return $conn;

  $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  $conn->set_charset('utf8mb4');
  return $conn;
}
