<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/app.php';

if (empty($_SESSION['user'])) {
  header('Location: ' . url('/auth/login.php'));
  exit;
}

$cctSesion = (string)($_SESSION['user']['escuela'] ?? '');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Listado de participantes (Activo) | Normalismo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars(url('/assets/css/theme.css')) ?>" rel="stylesheet">
  <style>
    /* Estilo de impresión: oculta barra y filtros */
    @media print {
      .no-print { display: none !important; }
      body { background: #fff !important; }
      .card { border: none !important; box-shadow: none !important; }
      .page-title { margin-bottom: 10px !important; }
    }
    .actividad-title{
      border-left: 6px solid var(--c-wine);
      padding-left: .75rem;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom no-print">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="<?= htmlspecialchars(url('/dashboard/index.php')) ?>" style="color: var(--c-wine);">
      Normalismo
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <span class="badge badge-wine">CCT: <?= htmlspecialchars($cctSesion) ?></span>
      <span class="text-secondary small"><?= htmlspecialchars($_SESSION['user']['nomUsuario'] ?? '') ?></span>
      <a href="<?= htmlspecialchars(url('/auth/logout.php')) ?>" class="btn btn-outline-secondary btn-sm">Salir</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4">

  <div class="d-flex align-items-center justify-content-between mb-3 no-print">
    <h1 class="h5 mb-0">Listado de participantes (Estatus: ACTIVO)</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(url('/dashboard/index.php')) ?>">Volver</a>
      <button id="btnPrintTop" class="btn btn-outline-secondary btn-sm" type="button" onclick="window.print()">Imprimir</button>
    </div>
  </div>

  <div class="card mb-3 no-print">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Fase</label>
          <select id="fase" class="form-select">
            <option value="INSTITUCIONAL">INSTITUCIONAL</option>
            <option value="REGIONAL">REGIONAL</option>
            <option value="ESTATAL">ESTATAL</option>
          </select>
          <div class="form-text text-secondary">
            El listado incluye únicamente estatus <code>ACTIVO</code>.
          </div>
        </div>

        <div class="col-md-4 d-grid">
          <button id="btnConsultar" class="btn btn-guinda" type="button">Consultar</button>
        </div>

        <div class="col-md-4 text-end text-secondary small">
          Actividades con participantes: <span id="actCount" class="fw-semibold">0</span><br>
          Total participantes: <span id="paxCount" class="fw-semibold">0</span>
        </div>
      </div>

      <div id="alertBox" class="mt-3"></div>
    </div>
  </div>

  <!-- Encabezado que SÍ imprime -->
  <div class="d-none d-print-block page-title">
    <h2 style="margin:0;">Listado de participantes (ACTIVO)</h2>
    <div style="margin-top:4px;">
      <strong>CCT:</strong> <?= htmlspecialchars($cctSesion) ?> &nbsp;|&nbsp;
      <strong>Fase:</strong> <span id="printFase">INSTITUCIONAL</span>
    </div>
    <hr>
  </div>

  <div class="card">
    <div class="card-body">
      <div id="content">
        <div class="text-secondary">Seleccione una fase y presione “Consultar”.</div>
      </div>
    </div>
  </div>

</div>

<script>
  const BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;

  const fase = document.getElementById('fase');
  const btnConsultar = document.getElementById('btnConsultar');
  const alertBox = document.getElementById('alertBox');
  const content = document.getElementById('content');
  const actCount = document.getElementById('actCount');
  const paxCount = document.getElementById('paxCount');
  const printFase = document.getElementById('printFase');

  function esc(s){
    return String(s ?? '')
      .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'","&#039;");
  }

  function msg(type, text){
    alertBox.innerHTML = `<div class="alert alert-${esc(type)} mb-0">${esc(text)}</div>`;
  }
  function clearMsg(){ alertBox.innerHTML = ''; }

  function setLoading(){
    content.innerHTML = `<div class="text-secondary">Cargando…</div>`;
  }

  function render(grouped){
    const keys = Object.keys(grouped || {});
    actCount.textContent = String(keys.length);

    let totalPax = 0;
    keys.forEach(k => totalPax += (grouped[k]?.length || 0));
    paxCount.textContent = String(totalPax);

    if (!keys.length){
      content.innerHTML = `<div class="text-secondary">Sin participantes activos para la fase seleccionada.</div>`;
      return;
    }

    content.innerHTML = keys.map(act => {
      const nombres = grouped[act] || [];
      const items = nombres.map(n => `<li>${esc(n)}</li>`).join('');
      return `
        <div class="mb-4">
          <h5 class="actividad-title mb-2">${esc(act)}</h5>
          <ol class="mb-0">
            ${items || '<li class="text-secondary">Sin participantes</li>'}
          </ol>
        </div>
      `;
    }).join('');
  }

  async function consultar(){
    clearMsg();
    actCount.textContent = '0';
    paxCount.textContent = '0';
    printFase.textContent = fase.value;

    setLoading();

    const url = `${BASE_URL}/api/listado_participantes_por_actividad.php?fase=${encodeURIComponent(fase.value)}`;
    const res = await fetch(url, {credentials:'same-origin'});
    const text = await res.text();

    let json = null;
    try { json = JSON.parse(text); } catch {}

    if (!res.ok || !json || json.ok !== true){
      console.error(res.status, text);
      msg('danger', json?.error || `Error al consultar (HTTP ${res.status}).`);
      content.innerHTML = `<div class="text-secondary">No fue posible cargar el listado.</div>`;
      return;
    }

    render(json.data);
  }

  btnConsultar.addEventListener('click', consultar);

  // UX: si cambian fase, actualiza fase de impresión
  fase.addEventListener('change', ()=>{ printFase.textContent = fase.value; });

</script>

</body>
</html>