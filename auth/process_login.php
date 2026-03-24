<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

function fail(string $msg): void {
  $_SESSION['login_error'] = $msg;
  header('Location: ' . url('/auth/login.php'));
  exit;
}

$nomUsuario = trim((string)($_POST['nomUsuario'] ?? ''));
$passwd     = (string)($_POST['passwd'] ?? '');

if ($nomUsuario === '' || $passwd === '') {
  fail('Debe capturar usuario y contraseña.');
}

if (mb_strlen($nomUsuario) > 50) {
  fail('Usuario inválido.');
}

if (mb_strlen($passwd) > 15) {
  fail('Contraseña inválida.');
}

try {
  $stmt = $pdo->prepare('
    SELECT idUsuario, nomUsuario, passwd, escuela, rol, region
    FROM usuarios
    WHERE nomUsuario = :u
    LIMIT 1
  ');
  $stmt->execute([':u' => $nomUsuario]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    fail('Credenciales inválidas.');
  }

  if (!hash_equals((string)$user['passwd'], $passwd)) {
    fail('Credenciales inválidas.');
  }

  $rol = strtoupper(trim((string)($user['rol'] ?? '')));
  $escuela = $user['escuela'] ?? null;
  $region = $user['region'] ?? null;

  // Validaciones mínimas de coherencia
  if ($rol === 'ESCUELA' && empty($escuela)) {
    fail('El usuario no tiene una escuela asignada.');
  }

  if ($rol === 'REGION' && empty($region)) {
    fail('El usuario no tiene una región asignada.');
  }

  if (!in_array($rol, ['ADMIN', 'REGION', 'ESCUELA'], true)) {
    fail('El usuario no tiene un rol válido.');
  }

  session_regenerate_id(true);

  $_SESSION['user'] = [
    'idUsuario'  => (int)$user['idUsuario'],
    'nomUsuario' => (string)$user['nomUsuario'],
    'escuela'    => $escuela !== null ? (string)$escuela : null,
    'rol'        => $rol,
    'region'     => $region !== null ? (string)$region : null,
  ];

  if ($rol === 'REGION') {
    header('Location: ' . url('/dashboard/region/index.php'));
    exit;
  }

  if ($rol === 'ADMIN' || $rol === 'ESCUELA') {
    header('Location: ' . url('/dashboard/index.php'));
    exit;
  }

  fail('No fue posible determinar el destino del usuario.');

} catch (Throwable $e) {
  fail('Ocurrió un error al validar el acceso.');
}