<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/app.php';

if (!empty($_SESSION['user'])) {
  header('Location: ' . url('/dashboard/index.php'));
  exit;
}

$err = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acceso | Normalismo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars(url('/assets/css/theme.css')) ?>" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars(url('/assets/css/video-bg.css')) ?>">
</head>
<body>
   <video id="bg-video" autoplay muted loop playsinline preload="auto">
    <source src="<?= htmlspecialchars(url('/assets/video/dashboard-bg.mp4')) ?>" type="video/mp4">
   </video>
  <div id="bg-overlay"></div>
  <div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="row w-100" style="max-width: 980px;">
      <div class="col-lg-6 mb-3">
        <div class="p-4">
          <div class="login-info-box">
            <img
              src="<?= htmlspecialchars(url('/assets/img/logo-dgen_TI.png')) ?>"
              alt="ENPEM – Escuelas Normales Públicas del Estado de México"
              class="login-logo mb-3">
              <br>
            <span class="badge badge-wine mb-3">ENPEM • Normalismo</span>

            <h1 class="h3 mb-2 text-wine">Ingreso al sistema</h1>

            <p class="text-secondary mb-4">
              Acceda con sus credenciales para registrar y dar seguimiento a participaciones por fase.
            </p>

            <ul class="text-secondary small">
              <li>Control por escuela (CCT)</li>
              <li>Registro de actividades y avance institucional / regional / estatal</li>
              <li>Dashboard por escuela y estatus</li>
            </ul>

          </div>

        </div>
      </div>

      <div class="col-lg-6">
        <div class="card">
          <div class="card-body p-4 p-md-5">
            <h2 class="h5 mb-3">Credenciales</h2>

            <?php if ($err): ?>
              <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?>
              </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars(url('/auth/process_login.php')) ?>" autocomplete="off" novalidate>
              <div class="mb-3">
                <label for="nomUsuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="nomUsuario" name="nomUsuario" required maxlength="50">
              </div>

              <div class="mb-3">
                <label for="passwd" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="passwd" name="passwd" required maxlength="15">
                <div class="form-text text-secondary">
                  Nota: asegúrese de introducir de manera literal sus credenciales.
                </div>
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-guinda btn-lg">Ingresar</button>
              </div>

              <div class="mt-3 text-secondary small">
                ¿Problemas de acceso? Contacte al área de TI la DGEN.
              </div>
            </form>

          </div>
        </div>
        <div class="text-center mt-3 small text-secondary">
          © <?= date('Y') ?> DGEN - TI • Normalismo
        </div>
      </div>
    </div>
  </div>
</body>
</html>
