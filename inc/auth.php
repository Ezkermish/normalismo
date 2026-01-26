<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function require_login(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['user'])) {
    header('Location: ' . url('login.php'));
    exit;
  }
}

function current_user(): array {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  return $_SESSION['user'] ?? [];
}

function login(string $username, string $password): bool {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $sql = "SELECT idUsuario, nomUsuario, passwd, escuela FROM usuarios WHERE nomUsuario = ? LIMIT 1";
  $stmt = db()->prepare($sql);
  $stmt->bind_param('s', $username);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  if (!$row) return false;

  $stored = (string)$row['passwd'];

  // Compatibilidad: si el campo almacenara un hash (password_hash), se valida con password_verify.
  // Si fuera texto plano (por el tamaño del campo), se compara directo.
  $ok = false;
  if (preg_match('/^\$2y\$\d{2}\$/', $stored) || str_starts_with($stored, '$argon2')) {
    $ok = password_verify($password, $stored);
  } else {
    $ok = hash_equals($stored, $password);
  }
  if (!$ok) return false;

  $_SESSION['user'] = [
    'idUsuario' => (int)$row['idUsuario'],
    'nomUsuario' => (string)$row['nomUsuario'],
    'escuela' => (string)$row['escuela'],
  ];
  return true;
}

function logout(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
  }
  session_destroy();
}
