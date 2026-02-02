<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/db.php';

function out(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

if (empty($_SESSION['user'])) {
  out(401, ['ok' => false, 'error' => 'No autenticado.']);
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '' || mb_strlen($q) < 3) {
  out(200, ['ok' => true, 'data' => []]);
}

$cct = (string)$_SESSION['user']['escuela'];

try {
  $sql = "
    SELECT idDocente, escuela, nombre, rfc
    FROM docentes
    WHERE escuela = :escuela
      AND (
        idDocente LIKE :l1
        OR rfc LIKE :l2
        OR nombre LIKE :l3
      )
    ORDER BY nombre
    LIMIT 20
  ";

  $stmt = $pdo->prepare($sql);
  $like = '%' . $q . '%';
  $stmt->execute([
    ':escuela' => $cct,
    ':l1' => $like,
    ':l2' => $like,
    ':l3' => $like,
  ]);
  $rows = $stmt->fetchAll();

  out(200, ['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
  out(500, ['ok' => false, 'error' => 'Error al consultar docentes.']);
}
