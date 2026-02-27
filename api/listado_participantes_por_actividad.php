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

$cct  = (string)($_SESSION['user']['escuela'] ?? '');
$fase = strtoupper(trim((string)($_GET['fase'] ?? '')));

$validFase = ['INSTITUCIONAL','REGIONAL','ESTATAL'];
if (!in_array($fase, $validFase, true)) {
  out(400, ['ok'=>false,'error'=>'fase inválida. Use INSTITUCIONAL, REGIONAL o ESTATAL.']);
}

try {
  
  $sql = "
    SELECT
      x.actividad,
      x.tipoActividad,
      x.participante
    FROM (
      -- ALUMNOS
      SELECT
        act.descripcion AS actividad,
        act.tipoActividad,
        CONCAT_WS(' ', al.nombre, al.apPaterno, al.apMaterno) AS participante
      FROM participaciones p
      INNER JOIN actividades act ON act.idActividad = p.idActividad
      INNER JOIN alumnos al ON al.idAlumno = p.idAlumno
      WHERE p.tipoParticipante = 'ALUMNO'
        AND al.cct = ?
        AND p.fase = ?
        AND p.estatus = 'ACTIVO'

      UNION ALL

      -- DOCENTES
      SELECT
        act.descripcion AS actividad,
        act.tipoActividad,
        d.nombre AS participante
      FROM participaciones p
      INNER JOIN actividades act ON act.idActividad = p.idActividad
      INNER JOIN docentes d ON d.idDocente = p.idDocente
      WHERE p.tipoParticipante = 'DOCENTE'
        AND d.escuela = ?
        AND p.fase = ?
        AND p.estatus = 'ACTIVO'
    ) x
    ORDER BY x.tipoActividad, x.actividad, x.participante
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([$cct, $fase, $cct, $fase]);
  $rows = $stmt->fetchAll();
  $stmt->closeCursor();

  
  $grouped = [];
  foreach ($rows as $r) {
    $act = (string)$r['actividad'];
    if (!isset($grouped[$act])) $grouped[$act] = [];
    $grouped[$act][] = (string)$r['participante'];
  }

  out(200, [
    'ok' => true,
    'fase' => $fase,
    'cct' => $cct,
    'data' => $grouped
  ]);

} catch (Throwable $e) {
  out(500, ['ok' => false, 'error' => 'Error al generar listado.', 'debug' => $e->getMessage()]);
}