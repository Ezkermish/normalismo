<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'No autenticado.']);
  exit;
}

$tipo = trim((string)($_GET['tipo'] ?? ''));

if ($tipo === '' || mb_strlen($tipo) > 20) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Tipo inválido.']);
  exit;
}

try {
  $stmt = $pdo->prepare("
    SELECT idActividad, tipoActividad, descripcion, numParticipantes
    FROM actividades
    WHERE tipoActividad = :t
    ORDER BY descripcion
  ");
  $stmt->execute([':t' => $tipo]);

  $items = [];
  while ($row = $stmt->fetch()) {
    $items[] = [
      'idActividad' => (int)$row['idActividad'],
      'tipoActividad' => (string)$row['tipoActividad'],
      'descripcion' => (string)$row['descripcion'],
      'numParticipantes' => (int)($row['numParticipantes'] ?? 0),
    ];
  }

  echo json_encode(['ok' => true, 'items' => $items]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Error al consultar actividades.']);
}
