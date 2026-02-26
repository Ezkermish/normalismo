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

/**
 * Parámetros:
 * group: escuela | fase | actividad | escuela_fase | escuela_actividad | fase_actividad | escuela_fase_actividad
 * filtros opcionales:
 *  - cct
 *  - fase
 *  - tipoActividad
 *  - idActividad
 *  - estatus
 *  - tipoParticipante (ALUMNO | DOCENTE)
 */
$group = strtolower(trim((string)($_GET['group'] ?? 'escuela_fase_actividad')));

$cct = trim((string)($_GET['cct'] ?? ''));
$fase = strtoupper(trim((string)($_GET['fase'] ?? '')));
$tipoActividad = trim((string)($_GET['tipoActividad'] ?? ''));
$idActividad = trim((string)($_GET['idActividad'] ?? ''));
$estatus = strtoupper(trim((string)($_GET['estatus'] ?? '')));
$tipoParticipante = strtoupper(trim((string)($_GET['tipoParticipante'] ?? '')));

// Restringir a la escuela del usuario, descomenta:
// $cct = (string)($_SESSION['user']['escuela'] ?? '');

$validGroups = [
  'escuela',
  'fase',
  'actividad',
  'escuela_fase',
  'escuela_actividad',
  'fase_actividad',
  'escuela_fase_actividad',
];
if (!in_array($group, $validGroups, true)) {
  out(400, ['ok'=>false,'error'=>'group inválido.']);
}

$validFase = ['INSTITUCIONAL','REGIONAL','ESTATAL'];
$validEstatus = ['ACTIVO','DESCARTADO','AVANZO'];
$validTP = ['ALUMNO','DOCENTE'];

if ($fase !== '' && !in_array($fase, $validFase, true)) out(400, ['ok'=>false,'error'=>'fase inválida.']);
if ($estatus !== '' && !in_array($estatus, $validEstatus, true)) out(400, ['ok'=>false,'error'=>'estatus inválido.']);
if ($tipoParticipante !== '' && !in_array($tipoParticipante, $validTP, true)) out(400, ['ok'=>false,'error'=>'tipoParticipante inválido.']);
if ($idActividad !== '' && !ctype_digit($idActividad)) out(400, ['ok'=>false,'error'=>'idActividad inválido.']);

try {
  $selectCols = [];
  $groupCols  = [];

  if (strpos($group, 'escuela') !== false) {
  $selectCols[] = "t.cct";
  $groupCols[]  = "t.cct";
    }

    if (strpos($group, 'fase') !== false) {
    $selectCols[] = "t.fase";
    $groupCols[]  = "t.fase";
    }

    if (strpos($group, 'actividad') !== false) {
    $selectCols[] = "t.idActividad";
    $selectCols[] = "a.descripcion AS actividad";
    $selectCols[] = "a.tipoActividad";
    $groupCols[]  = "t.idActividad";
    $groupCols[]  = "a.descripcion";
    $groupCols[]  = "a.tipoActividad";
    }
  if (!$selectCols) {
    $selectCols = ["t.cct", "t.fase", "t.idActividad", "a.descripcion AS actividad", "a.tipoActividad"];
    $groupCols  = ["t.cct", "t.fase", "t.idActividad", "a.descripcion", "a.tipoActividad"];
  }

  // Filtros (outer)
  $where = [];
  $params = [];

  if ($cct !== '') { $where[] = "t.cct = :cct"; $params[':cct'] = $cct; }
  if ($fase !== '') { $where[] = "t.fase = :fase"; $params[':fase'] = $fase; }
  if ($estatus !== '') { $where[] = "t.estatus = :estatus"; $params[':estatus'] = $estatus; }
  if ($tipoParticipante !== '') { $where[] = "t.tipoParticipante = :tp"; $params[':tp'] = $tipoParticipante; }
  if ($idActividad !== '') { $where[] = "t.idActividad = :idAct"; $params[':idAct'] = (int)$idActividad; }
  if ($tipoActividad !== '') { $where[] = "a.tipoActividad = :tipoAct"; $params[':tipoAct'] = $tipoActividad; }

  $sql = "
    SELECT
      " . implode(",\n      ", $selectCols) . ",
      COUNT(*) AS total
    FROM (
      -- ALUMNOS
      SELECT
        al.cct AS cct,
        p.fase,
        p.idActividad,
        p.tipoParticipante,
        p.estatus
      FROM participaciones p
      INNER JOIN alumnos al ON al.idAlumno = p.idAlumno
      WHERE p.tipoParticipante = 'ALUMNO'

      UNION ALL

      -- DOCENTES
      SELECT
        d.escuela AS cct,
        p.fase,
        p.idActividad,
        p.tipoParticipante,
        p.estatus
      FROM participaciones p
      INNER JOIN docentes d ON d.idDocente = p.idDocente
      WHERE p.tipoParticipante = 'DOCENTE'
    ) t
    INNER JOIN actividades a ON a.idActividad = t.idActividad
  ";

  if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
  }

  $sql .= "
    GROUP BY " . implode(", ", $groupCols) . "
    ORDER BY total DESC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
  $stmt->closeCursor();

  out(200, ['ok' => true, 'data' => $rows]);

} catch (Throwable $e) {
  out(500, ['ok' => false, 'error' => 'Error al generar conteo.', 'debug' => $e->getMessage()]);
}