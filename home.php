<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_login();

$title = 'Inicio | Normalismo';
require __DIR__ . '/inc/header.php';
?>

<div class="row g-4 align-items-stretch">
  <div class="col-12">
    <div class="card card-glass p-4">
      <h1 class="h3 mb-2">Sitio del Normalismo</h1>
      <p class="small-muted mb-0">
        Primera versión funcional del portal: acceso por credenciales, navegación retraíble y módulos por tipo de participante.
        El usuario solo puede operar registros asociados a su Escuela Normal (CCT de sesión).
      </p>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <a href="<?= url('docente/') ?>" class="text-decoration-none">
      <div class="card card-glass p-4 h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h2 class="h4 mb-2">Docentes</h2>
            <p class="small-muted mb-0">Módulo en construcción (alta, asignación y seguimiento).</p>
          </div>
          <span class="badge text-bg-secondary">Próximamente</span>
        </div>
        <div class="mt-3">
          <img src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=1200&q=60" class="img-fluid rounded-4" alt="Docentes">
        </div>
      </div>
    </a>
  </div>

  <div class="col-12 col-lg-6">
    <a href="<?= url('alumno/') ?>" class="text-decoration-none">
      <div class="card card-glass p-4 h-100">
        <h2 class="h4 mb-2">Alumnos</h2>
        <p class="small-muted mb-0">Registro de actividades académicas, artístico-culturales y deportivas.</p>
        <div class="mt-3">
          <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1200&q=60" class="img-fluid rounded-4" alt="Alumnos">
        </div>
      </div>
    </a>
  </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
