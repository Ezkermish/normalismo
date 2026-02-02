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

// Leer JSON
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
  out(400, ['ok' => false, 'error' => 'JSON inválido.']);
}

$idActividad = $body['idActividad'] ?? null;
$alumnos = $body['alumnos'] ?? null;

if (!ctype_digit((string)$idActividad)) {
  out(400, ['ok' => false, 'error' => 'idActividad inválido.']);
}

if (!is_array($alumnos) || count($alumnos) === 0) {
  out(400, ['ok' => false, 'error' => 'Lista de alumnos vacía.']);
}

// Normalizar ids
$ids = [];
foreach ($alumnos as $id) {
  $id = trim((string)$id);
  if ($id === '' || mb_strlen($id) > 10) continue;
  $ids[] = $id;
}
$ids = array_values(array_unique($ids));

if (!$ids) {
  out(400, ['ok' => false, 'error' => 'No hay alumnos válidos.']);
}

$cctUsuario = (string)$_SESSION['user']['escuela'];

try {
  // Validar actividad
  $stmtAct = $pdo->prepare(
    "SELECT idActividad FROM actividades WHERE idActividad = ? LIMIT 1"
  );
  $stmtAct->execute([(int)$idActividad]);
  $act = $stmtAct->fetch();
  $stmtAct->closeCursor();

  if (!$act) {
    out(400, ['ok' => false, 'error' => 'La actividad no existe.']);
  }

  // Validar alumnos por escuela
  $stmtAl = $pdo->prepare(
    "SELECT idAlumno FROM alumnos WHERE idAlumno = ? AND cct = ? LIMIT 1"
  );

  $validos = [];
  foreach ($ids as $idAl) {
    $stmtAl->execute([$idAl, $cctUsuario]);
    $ok = $stmtAl->fetchColumn();
    $stmtAl->closeCursor();

    if ($ok !== false) {
      $validos[] = $idAl;
    }
  }

  if (!$validos) {
    out(400, ['ok' => false, 'error' => 'Los alumnos no pertenecen a su escuela.']);
  }

  $pdo->beginTransaction();

  // Evitar duplicados
  $stmtDup = $pdo->prepare(
    "SELECT 1 FROM participaciones
     WHERE tipoParticipante = 'ALUMNO'
       AND idAlumno = ?
       AND idActividad = ?
     LIMIT 1"
  );

  $stmtIns = $pdo->prepare(
    "INSERT INTO participaciones
      (tipoParticipante, idAlumno, idActividad, fase, estatus)
     VALUES
      ('ALUMNO', ?, ?, 'INSTITUCIONAL', 'ACTIVO')"
  );

  $inserted = 0;
  $skipped = 0;

  foreach ($validos as $idAl) {
    $stmtDup->execute([$idAl, (int)$idActividad]);
    $exists = $stmtDup->fetchColumn();
    $stmtDup->closeCursor();

    if ($exists !== false) {
      $skipped++;
      continue;
    }

    $stmtIns->execute([$idAl, (int)$idActividad]);
    $inserted++;
  }

  $pdo->commit();

  out(200, ['ok' => true, 'inserted' => $inserted, 'skipped' => $skipped]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  out(500, ['ok' => false, 'error' => 'Error al registrar.', 'debug' => $e->getMessage()]);
}
