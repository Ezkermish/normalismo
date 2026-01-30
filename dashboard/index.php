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
      <span class="text-secondary small"><?= htmlspecialchars((string)$user['nomUsuario']) ?></span>
      <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(url('/auth/logout.php')) ?>">Salir</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="card">
    <div class="card-body">
      <h1 class="h5 mb-1">Acceso correcto</h1>
      <p class="text-secondary mb-0">
        En la siguiente fase conectaremos el registro de participaciones y el avance por fases.
      </p>
    </div>
  </div>
</div>
</body>
</html>
