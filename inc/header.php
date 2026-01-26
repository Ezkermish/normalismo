<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$user = current_user();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Normalismo', ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('assets/css/styles.css') ?>">
</head>
<body>
<div class="video-hero" aria-hidden="true">
  <video autoplay muted loop playsinline>
    <source src="<?= url('assets/video/fondo.mp4') ?>" type="video/mp4">
  </video>
</div>
<div class="video-overlay" aria-hidden="true"></div>

<nav class="navbar navbar-expand-lg navbar-dark navbar-glass">
  <div class="container-fluid">
    <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#navOffcanvas" aria-controls="navOffcanvas">
      Menú
    </button>

    <span class="navbar-brand mb-0 h1">Normalismo</span>

    <div class="ms-auto d-flex align-items-center gap-2">
      <?php if (!empty($user)): ?>
        <span class="small-muted">Usuario: <?= htmlspecialchars($user['nomUsuario'], ENT_QUOTES, 'UTF-8') ?></span>
        <a class="btn btn-sm btn-outline-light" href="<?= url('logout.php') ?>">Salir</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="navOffcanvas" aria-labelledby="navOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="navOffcanvasLabel">Navegación</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
  </div>
  <div class="offcanvas-body">
    <div class="list-group list-group-flush">
      <a class="list-group-item list-group-item-action bg-transparent text-white" href="<?= url('home.php') ?>">Inicio</a>
      <a class="list-group-item list-group-item-action bg-transparent text-white" href="<?= url('alumno/') ?>">Alumnos</a>
      <a class="list-group-item list-group-item-action bg-transparent text-white disabled" href="#" aria-disabled="true">Docentes (próximamente)</a>
    </div>
    <hr class="border-light opacity-25">
    <div class="small-muted">
      Restricción: solo podrá registrar/actualizar información de su Escuela Normal (CCT: <strong><?= htmlspecialchars($user['escuela'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>).
    </div>
  </div>
</div>

<main class="container py-4">
