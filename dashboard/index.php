<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/app.php';

if (empty($_SESSION['user'])) {
  header('Location: ' . url('/auth/login.php'));
  exit;
}

$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard | Normalismo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars(url('/assets/css/theme.css')) ?>" rel="stylesheet">
</head>

</head>
<body>
 <nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center"
      href="<?= htmlspecialchars(url('/dashboard/index.php')) ?>">
      <img src="<?= htmlspecialchars(url('/assets/img/logo-normalismo.png')) ?>"
       alt="Normalismo"
       style="height:40px; width:auto;">
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="badge badge-wine">CCT: <?= htmlspecialchars((string)$user['escuela']) ?></span>
      <span class="text-secondary small">Escuela Normal <?= htmlspecialchars((string)$user['nomUsuario']) ?></span>
      <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(url('/auth/logout.php')) ?>">Salir</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h1 class="h5 mb-1">Panel de control</h1>
          <p class="text-secondary mb-3">
            Seleccione una opción para iniciar la captura y seguimiento.
          </p>

          <?php if (!REGISTRO_HABILITADO || !AVANCE_FASES_HABILITADO): ?>
            <div class="mb-3">
              <span class="badge badge-wine px-3 py-2" style="background:#9f2241;">
                ⚠ 
                <?php if (!REGISTRO_HABILITADO): ?>
                  Registro cerrado
                <?php endif; ?>

                <?php if (!REGISTRO_HABILITADO && !AVANCE_FASES_HABILITADO): ?>
                  ·
                <?php endif; ?>

                <?php if (!AVANCE_FASES_HABILITADO): ?>
                  Avance de fases deshabilitado
                <?php endif; ?>
              </span>
            </div>
          <?php endif; ?>

          <div class="d-flex flex-wrap gap-2">

            <?php if (REGISTRO_HABILITADO): ?>
              <a class="btn btn-guinda"
                href="<?= htmlspecialchars(url('/dashboard/registrar_participaciones.php')) ?>">
                Registrar Alumnos
              </a>
            <?php else: ?>
              <button class="btn btn-guinda" disabled>
                Registrar Alumnos
              </button>
            <?php endif; ?>


            <?php if (REGISTRO_HABILITADO): ?>
              <a class="btn btn-guinda"
                href="<?= htmlspecialchars(url('/dashboard/registrar_participaciones_docentes.php')) ?>">
                Registrar Docentes
              </a>
            <?php else: ?>
              <button class="btn btn-guinda" disabled>
                Registrar Docentes
              </button>
            <?php endif; ?>


            <?php if (AVANCE_FASES_HABILITADO): ?>
              <a class="btn btn-guinda"
                href="<?= htmlspecialchars(url('/dashboard/avance_fases.php')) ?>">
                Avance de fases
              </a>
            <?php else: ?>
              <button class="btn btn-guinda" disabled>
                Avance de fases
              </button>
            <?php endif; ?>

            <!-- <a class="btn btn-guinda" href="<?= htmlspecialchars(url('/dashboard/conteo_resumen.php')) ?>">
              Dashboard de conteos
            </a> -->

            <a class="btn btn-guinda" href="<?= htmlspecialchars(url('/dashboard/listado_participantes.php')) ?>">
              Listado por actividad (ACTIVO)
            </a>

          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <div class="text-secondary small">Sesión de la Escuela Normal</div>
          <div class="fw-semibold"><?= htmlspecialchars((string)$user['nomUsuario']) ?></div>
          <div class="text-secondary small">Escuela (CCT)</div>
          <div class="fw-semibold"><?= htmlspecialchars((string)$user['escuela']) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
