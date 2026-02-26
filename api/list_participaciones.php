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

$tipoParticipante = strtoupper(trim((string)($_GET['tipoParticipante'] ?? '')));
$idActividad = trim((string)($_GET['idActividad'] ?? ''));
$fase = strtoupper(trim((string)($_GET['fase'] ?? '')));
$estatus = strtoupper(trim((string)($_GET['estatus'] ?? '')));

$validTipo = ['ALUMNO','DOCENTE'];
$validFase = ['INSTITUCIONAL','REGIONAL','ESTATAL'];
$validEstatus = ['ACTIVO','DESCARTADO','AVANZO'];

if (!in_array($tipoParticipante, $validTipo, true)) {
  out(400, ['ok' => false, 'error' => 'tipoParticipante inválido.']);
}
if (!ctype_digit($idActividad)) {
  out(400, ['ok' => false, 'error' => 'idActividad inválido.']);
}
if (!in_array($fase, $validFase, true)) {
  out(400, ['ok' => false, 'error' => 'fase inválida.']);
}
if ($estatus !== '' && !in_array($estatus, $validEstatus, true)) {
  out(400, ['ok' => false, 'error' => 'estatus inválido.']);
}

$cct = (string)($_SESSION['user']['escuela'] ?? '');

try {
  if ($tipoParticipante === 'ALUMNO') {
    $sql = "
      SELECT
        p.idParticipacion,
        p.idAlumno AS identificador,
        CONCAT(a.apPaterno,' ',a.apMaterno,' ',a.nombre) AS nombre,
        p.fase, p.estatus, p.comentario
      FROM participaciones p
      INNER JOIN alumnos a ON a.idAlumno = p.idAlumno
      WHERE p.tipoParticipante = 'ALUMNO'
        AND p.idActividad = :idActividad
        AND p.fase = :fase
        AND a.cct = :cct
    ";
    $params = [':idActividad' => (int)$idActividad, ':fase' => $fase, ':cct' => $cct];

  } else {
    $sql = "
      SELECT
        p.idParticipacion,
        p.idDocente AS identificador,
        d.nombre AS nombre,
        p.fase, p.estatus, p.comentario
      FROM participaciones p
      INNER JOIN docentes d ON d.idDocente = p.idDocente
      WHERE p.tipoParticipante = 'DOCENTE'
        AND p.idActividad = :idActividad
        AND p.fase = :fase
        AND d.escuela = :cct
    ";
    $params = [':idActividad' => (int)$idActividad, ':fase' => $fase, ':cct' => $cct];
  }

  if ($estatus !== '') {
    $sql .= " AND p.estatus = :estatus";
    $params[':estatus'] = $estatus;
  }

  $sql .= " ORDER BY nombre";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  out(200, ['ok' => true, 'data' => $rows]);

} catch (Throwable $e) {
  out(500, ['ok' => false, 'error' => 'Error al consultar participaciones.']);
}