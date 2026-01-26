<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';

$user = current_user();
$escuela = (string)($user['escuela'] ?? '');

$curp = strtoupper(trim((string)($_GET['curp'] ?? '')));
if ($curp === '' || strlen($curp) !== 18) {
  json_error('CURP inválida.');
}

$sql = "SELECT idAlumno, apPaterno, apMaterno, nombre, curp, matricula
        FROM alumnos
        WHERE curp = ? AND cct = ?
        LIMIT 1";
$stmt = db()->prepare($sql);
$stmt->bind_param('ss', $curp, $escuela);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
  json_error('El alumno no está inscrito en la escuela o la CURP ingresada no es correcta.', 404);
}

json_ok(['alumno' => $row]);
