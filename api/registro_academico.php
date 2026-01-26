<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../inc/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Método no permitido.', 405);
csrf_validate();

$user = current_user();
$escuela = (string)($user['escuela'] ?? '');

$idAlumno = trim((string)($_POST['idAlumno'] ?? ''));
$actividad = trim((string)($_POST['actividad'] ?? ''));

if ($idAlumno === '' || $actividad === '') json_error('Datos incompletos.');

db()->begin_transaction();

try {
  // valida pertenencia a escuela
  $sqlCheck = "SELECT idAlumno FROM alumnos WHERE idAlumno = ? AND cct = ? LIMIT 1";
  $stmt = db()->prepare($sqlCheck);
  $stmt->bind_param('ss', $idAlumno, $escuela);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res->fetch_assoc()) {
    throw new RuntimeException('El alumno no pertenece a su Escuela Normal.');
  }

  $sql = "UPDATE alumnos
          SET actividadAcademica = ?, faseEscolar = 1
          WHERE idAlumno = ? AND cct = ?";
  $stmt = db()->prepare($sql);
  $stmt->bind_param('sss', $actividad, $idAlumno, $escuela);
  $stmt->execute();

  db()->commit();
  json_ok();
} catch (Throwable $e) {
  db()->rollback();
  json_error($e->getMessage(), 400);
}
