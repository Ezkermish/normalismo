<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';

$tipo = (string)($_GET['tipo'] ?? '');
$tipo = trim($tipo);
if ($tipo === '') json_error('Tipo de actividad requerido.');

$sql = "SELECT idActividad, descripcion
        FROM actividades
        WHERE tipoActividad = ?
        ORDER BY descripcion ASC";
$stmt = db()->prepare($sql);
$stmt->bind_param('s', $tipo);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
  $items[] = $row;
}

json_ok(['items' => $items]);
