<?php
// config/app.php
declare(strict_types=1);

define('BASE_URL', '/normalismo');

// ==========================================
// Control de 
// ==========================================

define('REGISTRO_HABILITADO', false); 
// true  = permite registrar
// false = bloquea registro de alumnos y docentes

define('AVANCE_FASES_HABILITADO', false); 
// true: control independiente de avance de fases
// false: bloquea el avance de fase

function url(string $path): string {
  $path = '/' . ltrim($path, '/');
  return BASE_URL . $path;
}
