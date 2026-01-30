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

$userCct = (string)($_SESSION['user']['escuela'] ?? '');
$q = trim((string)($_GET['q'] ?? ''));

if ($q === '' || mb_strlen($q) < 2) {
  echo json_encode(['ok' => true, 'items' => []]);
  exit;
}

if (mb_strlen($q) > 50) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Consulta inválida.']);
  exit;
}

try {
  $like = '%' . $q . '%';

  $sql = "
    SELECT idAlumno, curp, matricula, apPaterno, apMaterno, nombre, licenciatura, cct
    FROM alumnos
    WHERE cct = :cct
      AND (
        curp = :exactCurp
        OR matricula = :exactMat
        OR CONCAT_WS(' ', apPaterno, apMaterno, nombre) LIKE :likeName
        OR apPaterno LIKE :likeName
        OR apMaterno LIKE :likeName
        OR nombre LIKE :likeName
      )
    ORDER BY apPaterno, apMaterno, nombre
    LIMIT 20
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':cct' => $userCct,
    ':exactCurp' => strtoupper($q),
    ':exactMat' => $q,
    ':likeName' => $like,
  ]);

  $items = [];
  while ($row = $stmt->fetch()) {
    $items[] = [
      'idAlumno' => (string)$row['idAlumno'],
      'curp' => (string)$row['curp'],
      'matricula' => (string)$row['matricula'],
      'nombreCompleto' => trim($row['apPaterno'].' '.$row['apMaterno'].' '.$row['nombre']),
      'licenciatura' => (string)($row['licenciatura'] ?? ''),
      'cct' => (string)$row['cct'],
    ];
  }

  echo json_encode(['ok' => true, 'items' => $items]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Error al consultar alumnos.']);
}
