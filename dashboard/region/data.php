<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$user   = $_SESSION['user'];
$rol    = strtoupper(trim((string)($user['rol'] ?? '')));
$region = trim((string)($user['region'] ?? ''));

if ($rol !== 'REGION') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit;
}

if ($region === '') {
    http_response_code(403);
    echo json_encode(['error' => 'El usuario no tiene región asignada']);
    exit;
}

$action        = trim((string)($_GET['action'] ?? ''));
$fase          = trim((string)($_GET['fase'] ?? ''));
$tipoActividad = trim((string)($_GET['tipoActividad'] ?? ''));
$idActividad   = trim((string)($_GET['idActividad'] ?? ''));
$escuelaFiltro = trim((string)($_GET['escuela'] ?? ''));

function jsonResponse($data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function filtrosAlumno(string $region, string $fase, string $tipoActividad, string $idActividad, string $escuelaFiltro): array {
    $where = [
        "p.tipoParticipante = 'ALUMNO'",
        "p.estatus = 'ACTIVO'",
        "e.region = :region"
    ];
    $params = [':region' => $region];

    if ($fase !== '') {
        $where[] = "p.fase = :fase";
        $params[':fase'] = $fase;
    }

    if ($tipoActividad !== '') {
        $where[] = "a.tipoActividad = :tipoActividad";
        $params[':tipoActividad'] = $tipoActividad;
    }

    if ($idActividad !== '' && ctype_digit($idActividad)) {
        $where[] = "a.idActividad = :idActividad";
        $params[':idActividad'] = (int)$idActividad;
    }

    if ($escuelaFiltro !== '') {
        $where[] = "(e.nombreEscuela LIKE :escuela OR e.cct LIKE :escuela)";
        $params[':escuela'] = '%' . $escuelaFiltro . '%';
    }

    return [$where, $params];
}

function filtrosDocente(string $region, string $fase, string $tipoActividad, string $idActividad, string $escuelaFiltro): array {
    $where = [
        "p.tipoParticipante = 'DOCENTE'",
        "p.estatus = 'ACTIVO'",
        "e.region = :region"
    ];
    $params = [':region' => $region];

    if ($fase !== '') {
        $where[] = "p.fase = :fase";
        $params[':fase'] = $fase;
    }

    if ($tipoActividad !== '') {
        $where[] = "a.tipoActividad = :tipoActividad";
        $params[':tipoActividad'] = $tipoActividad;
    }

    if ($idActividad !== '' && ctype_digit($idActividad)) {
        $where[] = "a.idActividad = :idActividad";
        $params[':idActividad'] = (int)$idActividad;
    }

    if ($escuelaFiltro !== '') {
        $where[] = "(e.nombreEscuela LIKE :escuela OR e.cct LIKE :escuela)";
        $params[':escuela'] = '%' . $escuelaFiltro . '%';
    }

    return [$where, $params];
}

function getCatalogoActividades(PDO $pdo, string $tipoActividad): array {
    $sql = "SELECT idActividad, tipoActividad, descripcion FROM actividades";
    $params = [];

    if ($tipoActividad !== '') {
        $sql .= " WHERE tipoActividad = :tipoActividad";
        $params[':tipoActividad'] = $tipoActividad;
    }

    $sql .= " ORDER BY tipoActividad, descripcion";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAlumnos(PDO $pdo, string $region, string $fase, string $tipoActividad, string $idActividad, string $escuelaFiltro): array {
    [$where, $params] = filtrosAlumno($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);

    $sql = "
        SELECT
            e.cct,
            e.nombreEscuela AS escuela,
            p.fase,
            a.tipoActividad,
            a.descripcion,
            COUNT(*) AS total_alumnos
        FROM participaciones p
        INNER JOIN alumnos al ON al.idAlumno = p.idAlumno
        INNER JOIN escuelas e ON e.cct = al.cct
        INNER JOIN actividades a ON a.idActividad = p.idActividad
        WHERE " . implode(' AND ', $where) . "
        GROUP BY e.cct, e.nombreEscuela, p.fase, a.tipoActividad, a.descripcion
        ORDER BY e.nombreEscuela, p.fase, a.tipoActividad, a.descripcion
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDocentes(PDO $pdo, string $region, string $fase, string $tipoActividad, string $idActividad, string $escuelaFiltro): array {
    [$where, $params] = filtrosDocente($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);

    $sql = "
        SELECT
            e.cct,
            e.nombreEscuela AS escuela,
            p.fase,
            a.tipoActividad,
            a.descripcion,
            COUNT(*) AS total_docentes
        FROM participaciones p
        INNER JOIN docentes d ON d.idDocente = p.idDocente
        INNER JOIN escuelas e ON e.cct = d.escuela
        INNER JOIN actividades a ON a.idActividad = p.idActividad
        WHERE " . implode(' AND ', $where) . "
        GROUP BY e.cct, e.nombreEscuela, p.fase, a.tipoActividad, a.descripcion
        ORDER BY e.nombreEscuela, p.fase, a.tipoActividad, a.descripcion
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getResumenFase(PDO $pdo, string $region, string $fase, string $tipoActividad, string $idActividad, string $escuelaFiltro): array {
    [$wa, $pa] = filtrosAlumno($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);
    [$wd, $pd] = filtrosDocente($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);

    $sql = "
        SELECT
            fases.fase,
            COALESCE(a.alumnos, 0) AS alumnos,
            COALESCE(d.docentes, 0) AS docentes,
            COALESCE(a.alumnos, 0) + COALESCE(d.docentes, 0) AS total
        FROM (
            SELECT 'INSTITUCIONAL' AS fase
            UNION SELECT 'REGIONAL'
            UNION SELECT 'ESTATAL'
        ) fases
        LEFT JOIN (
            SELECT p.fase, COUNT(*) AS alumnos
            FROM participaciones p
            INNER JOIN alumnos al ON al.idAlumno = p.idAlumno
            INNER JOIN escuelas e ON e.cct = al.cct
            INNER JOIN actividades a ON a.idActividad = p.idActividad
            WHERE " . implode(' AND ', $wa) . "
            GROUP BY p.fase
        ) a ON a.fase = fases.fase
        LEFT JOIN (
            SELECT p.fase, COUNT(*) AS docentes
            FROM participaciones p
            INNER JOIN docentes d ON d.idDocente = p.idDocente
            INNER JOIN escuelas e ON e.cct = d.escuela
            INNER JOIN actividades a ON a.idActividad = p.idActividad
            WHERE " . implode(' AND ', $wd) . "
            GROUP BY p.fase
        ) d ON d.fase = fases.fase
        ORDER BY FIELD(fases.fase, 'INSTITUCIONAL', 'REGIONAL', 'ESTATAL')
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($pa, $pd));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getResumenTipo(PDO $pdo, string $region, string $fase, string $tipoActividad, string $idActividad, string $escuelaFiltro): array {
    [$wa, $pa] = filtrosAlumno($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);
    [$wd, $pd] = filtrosDocente($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);

    $sql = "
        SELECT
            x.tipoActividad,
            SUM(x.alumnos) AS alumnos,
            SUM(x.docentes) AS docentes,
            SUM(x.alumnos + x.docentes) AS total
        FROM (
            SELECT
                a.tipoActividad,
                COUNT(*) AS alumnos,
                0 AS docentes
            FROM participaciones p
            INNER JOIN alumnos al ON al.idAlumno = p.idAlumno
            INNER JOIN escuelas e ON e.cct = al.cct
            INNER JOIN actividades a ON a.idActividad = p.idActividad
            WHERE " . implode(' AND ', $wa) . "
            GROUP BY a.tipoActividad

            UNION ALL

            SELECT
                a.tipoActividad,
                0 AS alumnos,
                COUNT(*) AS docentes
            FROM participaciones p
            INNER JOIN docentes d ON d.idDocente = p.idDocente
            INNER JOIN escuelas e ON e.cct = d.escuela
            INNER JOIN actividades a ON a.idActividad = p.idActividad
            WHERE " . implode(' AND ', $wd) . "
            GROUP BY a.tipoActividad
        ) x
        GROUP BY x.tipoActividad
        ORDER BY x.tipoActividad
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($pa, $pd));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopActividades(PDO $pdo, string $region, string $fase, string $tipoActividad, string $idActividad, string $escuelaFiltro): array {
    [$wa, $pa] = filtrosAlumno($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);
    [$wd, $pd] = filtrosDocente($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);

    $sql = "
        SELECT
            x.tipoActividad,
            x.descripcion,
            SUM(x.alumnos) AS alumnos,
            SUM(x.docentes) AS docentes,
            SUM(x.alumnos + x.docentes) AS total
        FROM (
            SELECT
                a.tipoActividad,
                a.descripcion,
                COUNT(*) AS alumnos,
                0 AS docentes
            FROM participaciones p
            INNER JOIN alumnos al ON al.idAlumno = p.idAlumno
            INNER JOIN escuelas e ON e.cct = al.cct
            INNER JOIN actividades a ON a.idActividad = p.idActividad
            WHERE " . implode(' AND ', $wa) . "
            GROUP BY a.tipoActividad, a.descripcion

            UNION ALL

            SELECT
                a.tipoActividad,
                a.descripcion,
                0 AS alumnos,
                COUNT(*) AS docentes
            FROM participaciones p
            INNER JOIN docentes d ON d.idDocente = p.idDocente
            INNER JOIN escuelas e ON e.cct = d.escuela
            INNER JOIN actividades a ON a.idActividad = p.idActividad
            WHERE " . implode(' AND ', $wd) . "
            GROUP BY a.tipoActividad, a.descripcion
        ) x
        GROUP BY x.tipoActividad, x.descripcion
        ORDER BY total DESC, x.tipoActividad, x.descripcion
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($pa, $pd));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPorEscuela(PDO $pdo, string $region, string $fase, string $tipoActividad, string $idActividad, string $escuelaFiltro): array {
    [$wa, $pa] = filtrosAlumno($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);
    [$wd, $pd] = filtrosDocente($region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);

    $sql = "
        SELECT
            e.cct,
            e.nombreEscuela AS escuela,
            COALESCE(a.alumnos_activos, 0) AS alumnos_activos,
            COALESCE(d.docentes_activos, 0) AS docentes_activos,
            COALESCE(a.alumnos_activos, 0) + COALESCE(d.docentes_activos, 0) AS total_participaciones
        FROM escuelas e
        LEFT JOIN (
            SELECT e.cct, COUNT(*) AS alumnos_activos
            FROM participaciones p
            INNER JOIN alumnos al ON al.idAlumno = p.idAlumno
            INNER JOIN escuelas e ON e.cct = al.cct
            INNER JOIN actividades a ON a.idActividad = p.idActividad
            WHERE " . implode(' AND ', $wa) . "
            GROUP BY e.cct
        ) a ON a.cct = e.cct
        LEFT JOIN (
            SELECT e.cct, COUNT(*) AS docentes_activos
            FROM participaciones p
            INNER JOIN docentes d ON d.idDocente = p.idDocente
            INNER JOIN escuelas e ON e.cct = d.escuela
            INNER JOIN actividades a ON a.idActividad = p.idActividad
            WHERE " . implode(' AND ', $wd) . "
            GROUP BY e.cct
        ) d ON d.cct = e.cct
        WHERE e.region = :regionBase
    ";

    $params = array_merge($pa, $pd);
    $params[':regionBase'] = $region;

    if ($escuelaFiltro !== '') {
        $sql .= " AND (e.nombreEscuela LIKE :escuelaBase OR e.cct LIKE :escuelaBase)";
        $params[':escuelaBase'] = '%' . $escuelaFiltro . '%';
    }

    $sql .= " ORDER BY e.nombreEscuela";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSinRegistros(PDO $pdo, string $region, string $escuelaFiltro): array {
    $sql = "
        SELECT
            e.cct,
            e.nombreEscuela AS escuela,
            e.region
        FROM escuelas e
        WHERE e.region = :region
          AND e.cct NOT IN (
              SELECT DISTINCT al.cct
              FROM participaciones p
              INNER JOIN alumnos al ON al.idAlumno = p.idAlumno
              WHERE p.tipoParticipante = 'ALUMNO'
                AND p.estatus = 'ACTIVO'

              UNION

              SELECT DISTINCT d.escuela
              FROM participaciones p
              INNER JOIN docentes d ON d.idDocente = p.idDocente
              WHERE p.tipoParticipante = 'DOCENTE'
                AND p.estatus = 'ACTIVO'
          )
    ";

    $params = [':region' => $region];

    if ($escuelaFiltro !== '') {
        $sql .= " AND (e.nombreEscuela LIKE :escuela OR e.cct LIKE :escuela)";
        $params[':escuela'] = '%' . $escuelaFiltro . '%';
    }

    $sql .= " ORDER BY e.nombreEscuela";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getKpis(PDO $pdo, string $region, string $fase, string $tipoActividad, string $idActividad, string $escuelaFiltro): array {
    $alumnos = getAlumnos($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);
    $docentes = getDocentes($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);
    $porEscuela = getPorEscuela($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);
    $resumenTipo = getResumenTipo($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);
    $topActividades = getTopActividades($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro);

    $totalAlumnos = 0;
    foreach ($alumnos as $row) $totalAlumnos += (int)$row['total_alumnos'];

    $totalDocentes = 0;
    foreach ($docentes as $row) $totalDocentes += (int)$row['total_docentes'];

    $escuelasConRegistros = 0;
    foreach ($porEscuela as $row) {
        if ((int)$row['total_participaciones'] > 0) $escuelasConRegistros++;
    }

    return [
        'total_alumnos' => $totalAlumnos,
        'total_docentes' => $totalDocentes,
        'total_participaciones' => $totalAlumnos + $totalDocentes,
        'total_escuelas' => $escuelasConRegistros,
        'total_tipos_actividad' => count($resumenTipo),
        'total_actividades' => count($topActividades),
    ];
}

try {
    switch ($action) {
        case 'catalogo_actividades':
            jsonResponse(getCatalogoActividades($pdo, $tipoActividad));

        case 'kpis':
            jsonResponse(getKpis($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro));

        case 'resumen_fase':
            jsonResponse(getResumenFase($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro));

        case 'resumen_tipo':
            jsonResponse(getResumenTipo($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro));

        case 'top_actividades':
            jsonResponse(getTopActividades($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro));

        case 'alumnos':
            jsonResponse(getAlumnos($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro));

        case 'docentes':
            jsonResponse(getDocentes($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro));

        case 'por_escuela':
            jsonResponse(getPorEscuela($pdo, $region, $fase, $tipoActividad, $idActividad, $escuelaFiltro));

        case 'sin_registros':
            jsonResponse(getSinRegistros($pdo, $region, $escuelaFiltro));

        default:
            http_response_code(400);
            jsonResponse(['error' => 'Acción no válida']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    jsonResponse([
        'error' => 'Error interno en data.php',
        'detalle' => $e->getMessage()
    ]);
}