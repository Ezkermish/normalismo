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
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand" href="<?= htmlspecialchars(url('/dashboard/index.php')) ?>" style="color: var(--c-wine); font-weight: 700;">Normalismo</a>
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

          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-guinda" href="<?= htmlspecialchars(url('/dashboard/registrar_participaciones.php')) ?>">
              Registrar participantes
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
