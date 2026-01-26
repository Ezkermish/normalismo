<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';

$user = current_user();
$escuela = (string)($user['escuela'] ?? '');

$sql = "SELECT idAlumno,
               CONCAT(nombre,' ',apPaterno,' ',apMaterno) AS nombreCompleto,
               actividadAcademica
        FROM alumnos
        WHERE cct = ? AND faseEscolar = 1 AND actividadAcademica IS NOT NULL AND actividadAcademica <> ''
        ORDER BY nombre ASC
        LIMIT 200";
$stmt = db()->prepare($sql);
$stmt->bind_param('s', $escuela);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
  $items[] = $row;
}

json_ok(['items' => $items]);
