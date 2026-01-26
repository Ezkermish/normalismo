<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../inc/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Método no permitido.', 405);
csrf_validate();

$user = current_user();
$escuela = (string)($user['escuela'] ?? '');
$idAlumno = trim((string)($_POST['idAlumno'] ?? ''));
if ($idAlumno === '') json_error('ID requerido.');

$sql = "UPDATE alumnos
        SET actividadAcademica = NULL, faseEscolar = NULL
        WHERE idAlumno = ? AND cct = ?";
$stmt = db()->prepare($sql);
$stmt->bind_param('ss', $idAlumno, $escuela);
$stmt->execute();

json_ok();
