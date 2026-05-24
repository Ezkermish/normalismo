<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gracias por participar | Normalismo</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(url('/assets/css/video-bg.css')) ?>">
  <style>
    :root {
      --wine: #56212f;
      --gold: #bc955b;
      --ink: #20161a;
    }

    body {
      min-height: 100vh;
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      color: #fff;
      overflow-x: hidden;
    }

    .home-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
      position: relative;
      z-index: 1;
      text-align: center;
      box-sizing: border-box;
    }

    .thank-you-panel {
      width: min(920px, 100%);
      padding: clamp(28px, 5vw, 56px);
      border: 1px solid rgba(255, 255, 255, 0.35);
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.84);
      box-shadow: 0 18px 44px rgba(0, 0, 0, 0.28);
      backdrop-filter: blur(7px);
      -webkit-backdrop-filter: blur(7px);
      color: var(--ink);
    }

    .home-logo {
      display: block;
      width: min(320px, 78vw);
      height: auto;
      margin: 0 auto clamp(28px, 4vw, 44px);
    }

    .thanks {
      margin: 0 0 14px;
      color: var(--wine);
      font-size: clamp(2.5rem, 8vw, 5.5rem);
      line-height: 0.95;
      font-weight: 800;
      letter-spacing: 0;
      text-transform: uppercase;
    }

    .participation {
      margin: 0 0 24px;
      color: var(--gold);
      font-size: clamp(1.35rem, 3.5vw, 2.4rem);
      line-height: 1.15;
      font-weight: 700;
    }

    .message {
      max-width: 760px;
      margin: 0 auto;
      color: var(--wine);
      font-size: clamp(1.6rem, 4vw, 3rem);
      line-height: 1.18;
      font-weight: 700;
    }

    @media (max-width: 560px) {
      .home-wrapper {
        padding: 20px 14px;
      }

      .thank-you-panel {
        padding: 28px 18px;
      }
    }
  </style>
</head>
<body>
  <video id="bg-video" autoplay muted loop playsinline preload="auto">
    <source src="<?= htmlspecialchars(url('/assets/video/dashboard-bg.mp4')) ?>" type="video/mp4">
  </video>
  <div id="bg-overlay"></div>

  <main class="home-wrapper">
    <section class="thank-you-panel" aria-label="Mensaje de agradecimiento">
      <img
        src="<?= htmlspecialchars(url('/assets/img/logo-normalismo.png')) ?>"
        alt="Normalismo en el Estado de México"
        class="home-logo">
      <h1 class="thanks">GRACIAS</h1>
      <p class="participation">por participar</p>
      <p class="message">El normalimos en el Estado de México nos une</p>
    </section>
  </main>
</body>
</html>
