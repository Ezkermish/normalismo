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

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
  out(400, ['ok' => false, 'error' => 'JSON inválido.']);
}

$idActividad = $body['idActividad'] ?? null;
$docentes = $body['docentes'] ?? null;

if (!ctype_digit((string)$idActividad)) {
  out(400, ['ok' => false, 'error' => 'idActividad inválido.']);
}

if (!is_array($docentes) || count($docentes) === 0) {
  out(400, ['ok' => false, 'error' => 'Lista de docentes vacía.']);
}

// Normalizar ids
$ids = [];
foreach ($docentes as $id) {
  $id = trim((string)$id);
  if ($id === '' || mb_strlen($id) > 10) continue; // idDocente varchar(10)
  $ids[] = $id;
}
$ids = array_values(array_unique($ids));

if (!$ids) {
  out(400, ['ok' => false, 'error' => 'No hay idDocente válidos.']);
}

$cctUsuario = (string)$_SESSION['user']['escuela'];

try {
  // Validar actividad
  $stmtAct = $pdo->prepare("SELECT idActividad FROM actividades WHERE idActividad = ? LIMIT 1");
  $stmtAct->execute([(int)$idActividad]);
  $act = $stmtAct->fetch();
  $stmtAct->closeCursor();

  if (!$act) {
    out(400, ['ok' => false, 'error' => 'La actividad no existe.']);
  }

  // Validar docentes por escuela
  $stmtDoc = $pdo->prepare("SELECT idDocente FROM docentes WHERE idDocente = ? AND escuela = ? LIMIT 1");

  $validos = [];
  foreach ($ids as $idDoc) {
    $stmtDoc->execute([$idDoc, $cctUsuario]);
    $ok = $stmtDoc->fetchColumn();
    $stmtDoc->closeCursor();

    if ($ok !== false) $validos[] = $idDoc;
  }

  if (!$validos) {
    out(400, ['ok' => false, 'error' => 'Los docentes no pertenecen a su escuela.']);
  }

  $pdo->beginTransaction();

  $stmtDup = $pdo->prepare("
    SELECT 1 FROM participaciones
    WHERE tipoParticipante = 'DOCENTE'
      AND idDocente = ?
      AND idActividad = ?
    LIMIT 1
  ");

  $stmtIns = $pdo->prepare("
    INSERT INTO participaciones
      (tipoParticipante, idDocente, idActividad, fase, estatus)
    VALUES
      ('DOCENTE', ?, ?, 'INSTITUCIONAL', 'ACTIVO')
  ");

  $inserted = 0;
  $skipped = 0;

  foreach ($validos as $idDoc) {
    $stmtDup->execute([$idDoc, (int)$idActividad]);
    $exists = $stmtDup->fetchColumn();
    $stmtDup->closeCursor();

    if ($exists !== false) {
      $skipped++;
      continue;
    }

    $stmtIns->execute([$idDoc, (int)$idActividad]);
    $inserted++;
  }

  $pdo->commit();

  out(200, ['ok' => true, 'inserted' => $inserted, 'skipped' => $skipped]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  out(500, ['ok' => false, 'error' => 'Error al registrar docentes.', 'debug' => $e->getMessage()]);
}
