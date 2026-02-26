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

$idParticipacion = trim((string)($body['idParticipacion'] ?? ''));
$accion = strtoupper(trim((string)($body['accion'] ?? '')));
$comentario = trim((string)($body['comentario'] ?? ''));

if (!ctype_digit($idParticipacion)) {
  out(400, ['ok' => false, 'error' => 'idParticipacion inválido.']);
}

$validAcc = ['AVANZAR','DESCARTAR','REACTIVAR'];
if (!in_array($accion, $validAcc, true)) {
  out(400, ['ok' => false, 'error' => 'Acción inválida.']);
}

$cct = (string)($_SESSION['user']['escuela'] ?? '');

function nextPhase(string $fase): string {
  return match($fase) {
    'INSTITUCIONAL' => 'REGIONAL',
    'REGIONAL' => 'ESTATAL',
    default => 'ESTATAL'
  };
}

try {
  // 1) Obtener participación y validar pertenencia a la escuela (por join)
  $stmt = $pdo->prepare("
    SELECT
      p.idParticipacion, p.tipoParticipante, p.idAlumno, p.idDocente,
      p.idActividad, p.fase, p.estatus
    FROM participaciones p
    WHERE p.idParticipacion = ?
    LIMIT 1
  ");
  $stmt->execute([(int)$idParticipacion]);
  $p = $stmt->fetch();
  $stmt->closeCursor();

  if (!$p) {
    out(404, ['ok' => false, 'error' => 'Participación no encontrada.']);
  }

  // Validar que pertenezca a la escuela del usuario
  if ($p['tipoParticipante'] === 'ALUMNO') {
    $stmtOwn = $pdo->prepare("SELECT 1 FROM alumnos WHERE idAlumno = ? AND cct = ? LIMIT 1");
    $stmtOwn->execute([(string)$p['idAlumno'], $cct]);
    $ok = $stmtOwn->fetchColumn();
    $stmtOwn->closeCursor();
    if ($ok === false) out(403, ['ok' => false, 'error' => 'Sin permisos para modificar este registro.']);
  } else {
    $stmtOwn = $pdo->prepare("SELECT 1 FROM docentes WHERE idDocente = ? AND escuela = ? LIMIT 1");
    $stmtOwn->execute([(string)$p['idDocente'], $cct]);
    $ok = $stmtOwn->fetchColumn();
    $stmtOwn->closeCursor();
    if ($ok === false) out(403, ['ok' => false, 'error' => 'Sin permisos para modificar este registro.']);
  }

  $pdo->beginTransaction();

  // 2) Aplicar acción
  if ($accion === 'DESCARTAR') {
    $stmtUp = $pdo->prepare("UPDATE participaciones SET estatus='DESCARTADO', comentario=? WHERE idParticipacion=?");
    $stmtUp->execute([$comentario ?: null, (int)$idParticipacion]);
    $pdo->commit();
    out(200, ['ok' => true, 'message' => 'Participación descartada.']);
  }

  if ($accion === 'REACTIVAR') {
    $stmtUp = $pdo->prepare("UPDATE participaciones SET estatus='ACTIVO', comentario=? WHERE idParticipacion=?");
    $stmtUp->execute([$comentario ?: null, (int)$idParticipacion]);
    $pdo->commit();
    out(200, ['ok' => true, 'message' => 'Participación reactivada.']);
  }

  // AVANZAR
  $faseActual = (string)$p['fase'];
  if ($faseActual === 'ESTATAL') {
    $pdo->rollBack();
    out(400, ['ok' => false, 'error' => 'No es posible avanzar: ya está en ESTATAL.']);
  }

  $faseNueva = nextPhase($faseActual);

  // (Opcional) prevenir “duplicado” por si existieran registros históricos (misma persona/actividad) en la fase destino
  if ($p['tipoParticipante'] === 'ALUMNO') {
    $stmtDup = $pdo->prepare("
      SELECT 1 FROM participaciones
      WHERE tipoParticipante='ALUMNO'
        AND idAlumno=?
        AND idActividad=?
        AND fase=?
        AND idParticipacion<>?
      LIMIT 1
    ");
    $stmtDup->execute([(string)$p['idAlumno'], (int)$p['idActividad'], $faseNueva, (int)$idParticipacion]);
    $dup = $stmtDup->fetchColumn();
    $stmtDup->closeCursor();
    if ($dup !== false) {
      $pdo->rollBack();
      out(409, ['ok' => false, 'error' => 'Ya existe un registro del alumno en la fase destino para esta actividad.']);
    }
  } else {
    $stmtDup = $pdo->prepare("
      SELECT 1 FROM participaciones
      WHERE tipoParticipante='DOCENTE'
        AND idDocente=?
        AND idActividad=?
        AND fase=?
        AND idParticipacion<>?
      LIMIT 1
    ");
    $stmtDup->execute([(string)$p['idDocente'], (int)$p['idActividad'], $faseNueva, (int)$idParticipacion]);
    $dup = $stmtDup->fetchColumn();
    $stmtDup->closeCursor();
    if ($dup !== false) {
      $pdo->rollBack();
      out(409, ['ok' => false, 'error' => 'Ya existe un registro del docente en la fase destino para esta actividad.']);
    }
  }

  $stmtUp = $pdo->prepare("
    UPDATE participaciones
    SET fase=?, estatus='ACTIVO', comentario=?
    WHERE idParticipacion=?
  ");
  $stmtUp->execute([$faseNueva, $comentario ?: null, (int)$idParticipacion]);

  $pdo->commit();
  out(200, ['ok' => true, 'message' => "Avanzó a fase {$faseNueva}."]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  out(500, ['ok' => false, 'error' => 'Error al actualizar participación.']);
}