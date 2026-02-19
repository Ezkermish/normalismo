<?php
// dashboard/index.php  (o /dashboard/cerrado.php)
// Requiere sesión de login 
session_start();

// Si no hay sesión, manda al login
if (empty($_SESSION['user'])) {
  header('Location: /normalismo/auth/login.php');
  exit;
}

$user = $_SESSION['user']; // esperado: ['escuela'=>..., 'nomUsuario'=>...]
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Normalismo | Registro cerrado</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      --c-black:#000000;
      --c-wine:#56212f;
      --c-brown:#977e5b;
      --c-sand:#c3b08f;
      --c-light:#d6d1ca;

      --c-guinda:#9f2241;
      --c-cafe:#965f36;
      --c-oro:#bc955b;
      --c-cream:#ddc8a4;
    }

    body{
      min-height: 100vh;
      background:
        radial-gradient(1200px 600px at 20% 10%, rgba(188,149,91,.25), transparent 60%),
        radial-gradient(900px 500px at 90% 20%, rgba(159,34,65,.18), transparent 55%),
        linear-gradient(180deg, #fff, rgba(214,209,202,.55));
      color: #222;
    }

    .badge-wine{
      background: rgba(86,33,47,.08);
      color: var(--c-wine);
      border: 1px solid rgba(86,33,47,.18);
    }

    .card-closed{
      border: 1px solid rgba(86,33,47,.12);
      border-radius: 18px;
      box-shadow: 0 12px 30px rgba(0,0,0,.08);
      backdrop-filter: blur(6px);
      background: rgba(255,255,255,.82);
    }

    .dot{
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: var(--c-oro);
      box-shadow: 0 0 0 6px rgba(188,149,91,.16);
      animation: pulse 1.2s ease-in-out infinite;
    }

    @keyframes pulse{
      0%,100%{ transform: scale(1); opacity: .9; }
      50%{ transform: scale(1.15); opacity: 1; }
    }

    /* Texto animado tipo “máquina de escribir” */
    .typewrap{
      display: inline-block;
      border-right: 3px solid rgba(86,33,47,.55);
      padding-right: 6px;
      white-space: nowrap;
      overflow: hidden;
      max-width: 0;
      animation: typing 2.2s steps(40, end) forwards, caret .8s step-end infinite;
    }

    @keyframes typing{
      from{ max-width: 0; }
      to{ max-width: 60ch; }
    }

    @keyframes caret{
      50%{ border-color: transparent; }
    }

    .fadeup{
      opacity: 0;
      transform: translateY(8px);
      animation: fadeUp .7s ease forwards;
      animation-delay: var(--d, 0s);
    }
    @keyframes fadeUp{
      to{ opacity: 1; transform: translateY(0); }
    }

    .btn-wine{
      background: var(--c-wine);
      color: #fff;
      border: 1px solid rgba(0,0,0,.05);
    }
    .btn-wine:hover{ filter: brightness(0.95); color:#fff; }

    .hint{
      color: rgba(0,0,0,.62);
      font-size: .95rem;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2"
         href="#"
         style="color: var(--c-wine); font-weight: 800; letter-spacing:.2px; text-decoration:none;">
        <span class="dot"></span>
        <span>Normalismo</span>
      </a>

      <div class="ms-auto d-flex align-items-center gap-2">
        <span class="badge badge-wine">CCT: <?= htmlspecialchars((string)($user['escuela'] ?? '')) ?></span>
        <span class="text-secondary small">Escuela Normal <?= htmlspecialchars((string)($user['nomUsuario'] ?? '')) ?></span>
        <a class="btn btn-outline-secondary btn-sm" href="/normalismo/auth/logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-9 col-xl-8">
        <div class="card card-closed p-4 p-md-5">
          <div class="d-flex align-items-start gap-3 mb-3">
            <div class="dot mt-2"></div>
            <div>
              <h1 class="h3 mb-2" style="color: var(--c-wine); font-weight: 900;">
                <span class="typewrap">Registro cerrado</span>
              </h1>
              <p class="mb-0 hint fadeup" style="--d:.3s">
                En este momento, el sistema ya no permite el acceso al módulo de registro.
              </p>
            </div>
          </div>

          <div class="mt-4 fadeup" style="--d:.55s">
            <div class="p-3 rounded-3" style="background: rgba(188,149,91,.10); border: 1px solid rgba(188,149,91,.25);">
              <p class="mb-2" style="font-weight:700; color: var(--c-wine);">
                Motivo:
              </p>
              <p class="mb-0">
                El periodo de registro ha concluido. Si requiere apoyo, comuníquese con el Comité Estatal.
              </p>
            </div>
          </div>

          <div class="mt-4 d-flex flex-wrap gap-2 fadeup" style="--d:.75s">
            <a class="btn btn-wine" href="/normalismo/auth/logout.php">Cerrar sesión</a>
            <a class="btn btn-outline-secondary" href="javascript:location.reload();">Reintentar</a>
          </div>

          <hr class="my-4 fadeup" style="--d:.9s">

          <div class="small text-secondary fadeup" style="--d:1.05s">
            <div><strong>Nota:</strong> Si este mensaje aparece por error, verifique el estatus del registro o contacte al área de TI.</div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Opcional: “rebobina” el texto tipo máquina de escribir al recargar sin parpadeo
    // (No es necesario; lo dejo mínimo para no meter dependencias.)
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>