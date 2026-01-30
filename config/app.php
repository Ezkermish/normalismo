<?php
// config/app.php
declare(strict_types=1);

/**
 * Ajusta BASE_URL si el proyecto NO está en /normalismo.
 * Ejemplos:
 * - En localhost: http://localhost/normalismo  -> BASE_URL = '/normalismo'
 * - En producción con subcarpeta: https://dominio.tld/normalismo -> BASE_URL = '/normalismo'
 */
define('BASE_URL', '/normalismo');

function url(string $path): string {
  $path = '/' . ltrim($path, '/');
  return BASE_URL . $path;
}
