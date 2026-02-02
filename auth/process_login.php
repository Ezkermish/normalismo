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
$passwd    = (string)($_POST['passwd'] ?? '');

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
    SELECT idUsuario, nomUsuario, passwd, escuela
    FROM usuarios
    WHERE nomUsuario = :u
    LIMIT 1
  ');
  $stmt->execute([':u' => $nomUsuario]);
  $user = $stmt->fetch();

  if (!$user) {
    fail('Credenciales inválidas.');
  }

  if (!hash_equals((string)$user['passwd'], $passwd)) {
    fail('Credenciales inválidas.');
  }

  session_regenerate_id(true);

  $_SESSION['user'] = [
    'idUsuario'  => (int)$user['idUsuario'],
    'nomUsuario' => (string)$user['nomUsuario'],
    'escuela'    => (string)$user['escuela'],
  ];

  header('Location: ' . url('/dashboard/index.php'));
  exit;

} catch (Throwable $e) {
  fail('Ocurrió un error al validar el acceso.');
}
