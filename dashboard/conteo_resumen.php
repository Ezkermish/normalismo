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
  <title>Dashboard de conteos | Normalismo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars(url('/assets/css/theme.css')) ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom">
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

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h5 mb-0">Dashboard de conteos</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(url('/dashboard/index.php')) ?>">Volver</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">

      <div class="row g-2 align-items-end">

        <div class="col-lg-3">
          <label class="form-label">Agrupar por</label>
          <select id="group" class="form-select">
            <option value="escuela">Escuela</option>
            <option value="fase">Fase</option>
            <option value="actividad">Actividad</option>
            <option value="escuela_fase" selected>Escuela + Fase</option>
            <option value="escuela_actividad">Escuela + Actividad</option>
            <option value="fase_actividad">Fase + Actividad</option>
            <option value="escuela_fase_actividad">Escuela + Fase + Actividad</option>
          </select>
        </div>

        <div class="col-lg-2">
          <label class="form-label">Participante</label>
          <select id="tipoParticipante" class="form-select">
            <option value="">Ambos</option>
            <option value="ALUMNO">ALUMNO</option>
            <option value="DOCENTE">DOCENTE</option>
          </select>
        </div>

        <div class="col-lg-2">
          <label class="form-label">Estatus</label>
          <select id="estatus" class="form-select">
            <option value="">Todos</option>
            <option value="ACTIVO">ACTIVO</option>
            <option value="DESCARTADO">DESCARTADO</option>
            <option value="AVANZO">AVANZO</option>
          </select>
        </div>

        <div class="col-lg-2">
          <label class="form-label">Fase</label>
          <select id="fase" class="form-select">
            <option value="">Todas</option>
            <option value="INSTITUCIONAL">INSTITUCIONAL</option>
            <option value="REGIONAL">REGIONAL</option>
            <option value="ESTATAL">ESTATAL</option>
          </select>
        </div>

        <div class="col-lg-3">
          <label class="form-label">Tipo de actividad</label>
          <select id="tipoActividad" class="form-select">
            <option value="">Todas</option>
            <!-- Deben coincidir con tu BD -->
            <option value="Académica">Académicas</option>
            <option value="Artístico-Cultu">Artístico-culturales</option>
            <option value="Deportivos">Deportivas</option>
          </select>
        </div>

        <div class="col-lg-6">
          <label class="form-label">Actividad</label>
          <select id="idActividad" class="form-select" disabled>
            <option value="">Todas</option>
          </select>
          <div class="form-text text-secondary">
            Para filtrar por actividad, seleccione primero el tipo de actividad.
          </div>
        </div>

        <div class="col-lg-3">
          <label class="form-label">CCT (opcional)</label>
          <input id="cct" class="form-control" placeholder="Ej. <?= htmlspecialchars($cctSesion) ?>" value="">
          <div class="form-text text-secondary">
            Si se deja vacío, el endpoint puede devolver todas las escuelas (según configuración).
          </div>
        </div>

        <div class="col-lg-3 d-grid">
          <button id="btnConsultar" class="btn btn-guinda" type="button">Consultar</button>
        </div>

      </div>

      <div id="alertBox" class="mt-3"></div>

      <div class="d-flex flex-wrap gap-3 mt-3 text-secondary small">
        <div>Total filas: <span id="rowsCount" class="fw-semibold">0</span></div>
        <div>Total participantes (suma): <span id="sumTotal" class="fw-semibold">0</span></div>
      </div>

    </div>
  </div>

  <div class="card">
    <div class="card-body">

      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr class="text-secondary small" id="theadRow">
              <!-- se llena dinámicamente -->
            </tr>
          </thead>
          <tbody id="tbody">
            <tr><td class="text-secondary">Realice una consulta.</td></tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<script>
  const BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;

  const group = document.getElementById('group');
  const tipoParticipante = document.getElementById('tipoParticipante');
  const estatus = document.getElementById('estatus');
  const fase = document.getElementById('fase');
  const tipoActividad = document.getElementById('tipoActividad');
  const idActividad = document.getElementById('idActividad');
  const cct = document.getElementById('cct');
  const btnConsultar = document.getElementById('btnConsultar');

  const alertBox = document.getElementById('alertBox');
  const tbody = document.getElementById('tbody');
  const theadRow = document.getElementById('theadRow');

  const rowsCount = document.getElementById('rowsCount');
  const sumTotal = document.getElementById('sumTotal');

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
    tbody.innerHTML = `<tr><td class="text-secondary">Cargando…</td></tr>`;
  }

  function setEmpty(){
    tbody.innerHTML = `<tr><td class="text-secondary">Sin resultados.</td></tr>`;
  }

  function buildParams(){
    const p = new URLSearchParams();
    p.set('group', group.value);

    if (tipoParticipante.value) p.set('tipoParticipante', tipoParticipante.value);
    if (estatus.value) p.set('estatus', estatus.value);
    if (fase.value) p.set('fase', fase.value);
    if (tipoActividad.value) p.set('tipoActividad', tipoActividad.value);
    if (idActividad.value) p.set('idActividad', idActividad.value);
    if (cct.value.trim()) p.set('cct', cct.value.trim());

    return p;
  }

  function renderHeaderForGroup(g){
    const cols = [];
    if (g.includes('escuela')) cols.push('CCT');
    if (g.includes('fase')) cols.push('Fase');
    if (g.includes('actividad')) cols.push('Tipo actividad', 'Actividad', 'ID Actividad');
    cols.push('Total');

    theadRow.innerHTML = cols.map(c => `<th>${esc(c)}</th>`).join('');
  }

  function renderRows(g, rows){
    let sum = 0;

    tbody.innerHTML = rows.map(r => {
      const tds = [];

      if (g.includes('escuela')) tds.push(`<td><code>${esc(r.cct ?? '')}</code></td>`);
      if (g.includes('fase')) tds.push(`<td>${esc(r.fase ?? '')}</td>`);
      if (g.includes('actividad')) {
        tds.push(`<td>${esc(r.tipoActividad ?? '')}</td>`);
        tds.push(`<td>${esc(r.actividad ?? '')}</td>`);
        tds.push(`<td><code>${esc(r.idActividad ?? '')}</code></td>`);
      }

      const total = Number(r.total ?? 0);
      sum += isNaN(total) ? 0 : total;
      tds.push(`<td class="text-end fw-semibold">${esc(total)}</td>`);

      return `<tr>${tds.join('')}</tr>`;
    }).join('');

    rowsCount.textContent = String(rows.length);
    sumTotal.textContent = String(sum);
  }

  async function cargarActividades(){
    clearMsg();
    idActividad.disabled = true;
    idActividad.innerHTML = `<option value="">Todas</option>`;

    const tipo = tipoActividad.value;
    if (!tipo) return;

    const res = await fetch(`${BASE_URL}/api/get_actividades.php?tipo=${encodeURIComponent(tipo)}`, {credentials:'same-origin'});
    const text = await res.text();
    let json=null; try{ json=JSON.parse(text);}catch{}

    if (!res.ok || !json || json.ok !== true){
      console.error(res.status, text);
      msg('danger', json?.error || `Error al cargar actividades (HTTP ${res.status}).`);
      return;
    }

    const rows = json.data || [];
    idActividad.innerHTML = `<option value="">Todas</option>` + rows.map(a =>
      `<option value="${esc(a.idActividad)}">${esc(a.descripcion)}</option>`
    ).join('');

    idActividad.disabled = false;
  }

  async function consultar(){
    clearMsg();
    rowsCount.textContent = '0';
    sumTotal.textContent = '0';

    renderHeaderForGroup(group.value);
    setLoading();

    const p = buildParams();
    const url = `${BASE_URL}/api/conteo_resumen.php?${p.toString()}`;

    const res = await fetch(url, {credentials:'same-origin'});
    const text = await res.text();
    let json=null; try{ json=JSON.parse(text);}catch{}

    if (!res.ok || !json || json.ok !== true){
      console.error(res.status, text);
      msg('danger', json?.error || `Error al consultar (HTTP ${res.status}).`);
      tbody.innerHTML = `<tr><td class="text-secondary">Error al consultar.</td></tr>`;
      return;
    }

    const rows = json.data || [];
    if (!rows.length){ setEmpty(); rowsCount.textContent='0'; sumTotal.textContent='0'; return; }

    renderRows(group.value, rows);
  }

  // Eventos
  tipoActividad.addEventListener('change', async ()=>{
    await cargarActividades();
  });

  btnConsultar.addEventListener('click', consultar);

  // Init
  renderHeaderForGroup(group.value);
</script>

</body>
</html>