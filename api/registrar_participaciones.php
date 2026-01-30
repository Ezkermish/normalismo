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

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Payload inválido.']);
  exit;
}

$idActividad = (int)($data['idActividad'] ?? 0);
$ids = $data['idAlumnos'] ?? [];

if ($idActividad <= 0 || !is_array($ids) || count($ids) === 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Datos incompletos.']);
  exit;
}

if (count($ids) > 500) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Demasiados alumnos en un solo registro (máx. 500).']);
  exit;
}

// Normaliza IDs (varchar(10))
$ids = array_values(array_unique(array_map(fn($v) => substr(trim((string)$v), 0, 10), $ids)));
$ids = array_values(array_filter($ids, fn($v) => $v !== ''));

try {
  // Validar actividad
  $stAct = $pdo->prepare("SELECT idActividad FROM actividades WHERE idActividad = :id LIMIT 1");
  $stAct->execute([':id' => $idActividad]);
  if (!$stAct->fetch()) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'La actividad seleccionada no existe.']);
    exit;
  }

  // Validar alumnos de la escuela del usuario
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $stAl = $pdo->prepare("SELECT idAlumno FROM alumnos WHERE cct = ? AND idAlumno IN ($placeholders)");
  $stAl->execute(array_merge([$userCct], $ids));
  $validIds = $stAl->fetchAll(PDO::FETCH_COLUMN);

  $validIds = array_map('strval', $validIds);
  $validSet = array_flip($validIds);

  $invalid = array_values(array_filter($ids, fn($v) => !isset($validSet[$v])));
  if (count($validIds) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No se encontraron alumnos válidos para su escuela.', 'invalid' => $invalid]);
    exit;
  }

  $pdo->beginTransaction();

  $stExists = $pdo->prepare("
    SELECT idAlumno
    FROM participaciones
    WHERE tipoParticipante = 'ALUMNO'
      AND idActividad = :idAct
      AND idAlumno = :idAl
    LIMIT 1
  ");

  $stIns = $pdo->prepare("
    INSERT INTO participaciones
      (tipoParticipante, idAlumno, idActividad, fase, estatus, comentario, created_at, updated_at)
    VALUES
      ('ALUMNO', :idAl, :idAct, 'INSTITUCIONAL', 'ACTIVO', '', NOW(), NOW())
  ");

  $inserted = 0;
  $skipped = 0;
  $added = [];
  $already = [];

  foreach ($validIds as $idAl) {
    $stExists->execute([':idAct' => $idActividad, ':idAl' => $idAl]);
    if ($stExists->fetch()) {
      $skipped++;
      $already[] = $idAl;
      continue;
    }
    $stIns->execute([':idAl' => $idAl, ':idAct' => $idActividad]);
    $inserted++;
    $added[] = $idAl;
  }

  $pdo->commit();

  echo json_encode([
    'ok' => true,
    'inserted' => $inserted,
    'skipped' => $skipped,
    'added' => $added,
    'already' => $already,
    'invalid' => $invalid,
  ]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Error al registrar participaciones.']);
}
