<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';
require_login();

$title = 'Alumnos | Normalismo';
require __DIR__ . '/../inc/header.php';
?>

<div class="card card-glass p-4 mb-4">
  <h1 class="h4 mb-1">Módulo de Alumnos</h1>
  <div class="small-muted">Seleccione el tipo de actividad para registrar.</div>
</div>

<div class="row g-4">
  <div class="col-12 col-lg-4">
    <a class="text-decoration-none" href="<?= url('alumno/academicas.php') ?>">
      <div class="card card-glass p-4 h-100">
        <h2 class="h5">Actividades académicas</h2>
        <p class="small-muted mb-0">Registro por CURP y asignación de actividad.</p>
      </div>
    </a>
  </div>

  <div class="col-12 col-lg-4">
    <a class="text-decoration-none" href="<?= url('alumno/culturales.php') ?>">
      <div class="card card-glass p-4 h-100">
        <h2 class="h5">Actividades artístico-culturales</h2>
        <p class="small-muted mb-0">Misma lógica que académicas (pendiente de habilitar).</p>
      </div>
    </a>
  </div>

  <div class="col-12 col-lg-4">
    <a class="text-decoration-none" href="<?= url('alumno/deportivas.php') ?>">
      <div class="card card-glass p-4 h-100">
        <h2 class="h5">Actividades deportivas</h2>
        <p class="small-muted mb-0">Flujo con validaciones específicas (pendiente de habilitar).</p>
      </div>
    </a>
  </div>
</div>

<?php require __DIR__ . '/../inc/footer.php'; ?>
