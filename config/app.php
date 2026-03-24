<?php
// config/app.php
declare(strict_types=1);

define('BASE_URL', '/normalismo');

// ==========================================
// Control de Registro y avance de fases
// ==========================================

define('REGISTRO_HABILITADO', true); 
// true  = permite registrar
// false = bloquea registro de alumnos y docentes

define('AVANCE_FASES_HABILITADO', true); 
// true: control independiente de avance de fases
// false: bloquea el avance de fase

// ==========================================
// MENSAJE GLOBAL DEL SISTEMA
// ==========================================

define('MENSAJE_GLOBAL_ACTIVO', false);

define('MENSAJE_GLOBAL_TEXTO', 
  'Aviso importante: El avance de Fase de INSTITUCIONAL a REGIONAL se cerrará el 4 de marzo de 2026 a las 17:00 horas.'
);

function url(string $path): string {
  $path = '/' . ltrim($path, '/');
  return BASE_URL . $path;
}
