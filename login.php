<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($_SESSION['user'])) {
  header('Location: ' . url('home.php'));
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  if ($username === '' || $password === '') {
    $error = 'Debe capturar usuario y contraseña.';
  } else if (login($username, $password)) {
    header('Location: ' . url('home.php'));
    exit;
  } else {
    $error = 'Credenciales inválidas.';
  }
}

$title = 'Acceso | Normalismo';
require __DIR__ . '/inc/header.php';
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-6 col-lg-5">
    <div class="card card-glass p-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="brand-badge">
          <span class="fw-semibold">Normalismo</span>
          <span class="small-muted">ENPEM</span>
        </div>
        <span class="small-muted">Acceso</span>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input class="form-control" name="username" maxlength="15" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input class="form-control" type="password" name="password" maxlength="64" required>
        </div>
        <button class="btn btn-normalismo w-100">Ingresar</button>
      </form>

      <div class="small-muted mt-3">
        Seguridad: se utilizan consultas preparadas (mitiga SQL Injection) y se escapan salidas (mitiga XSS).
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
