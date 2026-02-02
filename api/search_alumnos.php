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
  out(401, ['ok' => false, 'error' => 'No autenticado']);
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '' || mb_strlen($q) < 3) {
  out(200, ['ok' => true, 'data' => []]);
}

$escuela = (string)$_SESSION['user']['escuela'];

$sql = "
  SELECT
    idAlumno,
    curp,
    matricula,
    nombre,
    apPaterno,
    apMaterno,
    cct
  FROM alumnos
  WHERE cct = :cct
    AND (
      curp LIKE :like1
      OR matricula LIKE :like2
      OR CONCAT(apPaterno,' ',apMaterno,' ',nombre) LIKE :like3
      OR CONCAT(nombre,' ',apPaterno,' ',apMaterno) LIKE :like4
    )
  ORDER BY apPaterno, apMaterno, nombre
  LIMIT 20
";

try {
  $stmt = $pdo->prepare($sql);
  $like = '%' . $q . '%';

  $stmt->execute([
    ':cct'   => $escuela,
    ':like1' => $like,
    ':like2' => $like,
    ':like3' => $like,
    ':like4' => $like,
  ]);

  $rows = $stmt->fetchAll();
  out(200, ['ok' => true, 'data' => $rows]);

} catch (Throwable $e) {
  out(500, ['ok' => false, 'error' => 'Error al consultar alumnos.']);
}
