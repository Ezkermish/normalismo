<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/app.php';

if (empty($_SESSION['user'])) {
  header('Location: ' . url('/auth/login.php'));
  exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Avance de fases | Normalismo</title>
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
      <span class="badge badge-wine">CCT: <?= htmlspecialchars($_SESSION['user']['escuela']) ?></span>
      <span class="text-secondary small"><?= htmlspecialchars($_SESSION['user']['nomUsuario']) ?></span>
      <a href="<?= htmlspecialchars(url('/auth/logout.php')) ?>" class="btn btn-outline-secondary btn-sm">Salir</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h5 mb-0">Avance de fases</h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(url('/dashboard/index.php')) ?>">Volver</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Tipo de participante</label>
          <select id="tipoParticipante" class="form-select">
            <option value="ALUMNO">Alumnos</option>
            <option value="DOCENTE">Docentes</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Tipo de actividad</label>
          <select id="tipoActividad" class="form-select">
            <option value="">Seleccione…</option>
            <!-- Deben coincidir con tu BD -->
            <option value="Académica">Académicas</option>
            <option value="Artístico-Cultu">Artístico-culturales</option>
            <option value="Deportivos">Deportivas</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Actividad</label>
          <select id="idActividad" class="form-select" disabled>
            <option value="">Seleccione tipo primero…</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Fase</label>
          <select id="fase" class="form-select">
            <option value="INSTITUCIONAL">INSTITUCIONAL</option>
            <option value="REGIONAL">REGIONAL</option>
            <option value="ESTATAL">ESTATAL</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Estatus</label>
          <select id="estatus" class="form-select">
            <option value="">Todos</option>
            <option value="ACTIVO">ACTIVO</option>
            <option value="DESCARTADO">DESCARTADO</option>
            <option value="AVANZO">AVANZO</option>
          </select>
        </div>

        <div class="col-md-3">
          <button id="btnConsultar" class="btn btn-guinda w-100" type="button">Consultar</button>
        </div>

        <div class="col-md-6 text-end text-secondary small">
          Resultados: <span id="count">0</span>
        </div>
      </div>

      <div id="alertBox" class="mt-3"></div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr class="text-secondary small">
              <th>Participante</th>
              <th>Identificador</th>
              <th>Fase</th>
              <th>Estatus</th>
              <th>Comentario</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody id="tbody">
            <tr><td colspan="6" class="text-secondary">Realice una consulta.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="text-secondary small">
        “Avanzar” mueve la participación a la siguiente fase y la deja en estatus <code>ACTIVO</code>.
      </div>
    </div>
  </div>
</div>

<script>
  const BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;

  const tipoParticipante = document.getElementById('tipoParticipante');
  const tipoActividad = document.getElementById('tipoActividad');
  const idActividad = document.getElementById('idActividad');
  const fase = document.getElementById('fase');
  const estatus = document.getElementById('estatus');
  const btnConsultar = document.getElementById('btnConsultar');
  const tbody = document.getElementById('tbody');
  const count = document.getElementById('count');
  const alertBox = document.getElementById('alertBox');

  function esc(s){
    return String(s ?? '')
      .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'","&#039;");
  }

  function msg(type, text){
    alertBox.innerHTML = `<div class="alert alert-${esc(type)} mb-0">${esc(text)}</div>`;
  }
  function clearMsg(){ alertBox.innerHTML = ''; }

  async function cargarActividades(tipo){
    clearMsg();
    idActividad.disabled = true;
    idActividad.innerHTML = `<option value="">Cargando…</option>`;

    if (!tipo){
      idActividad.innerHTML = `<option value="">Seleccione tipo primero…</option>`;
      return;
    }

    const res = await fetch(`${BASE_URL}/api/get_actividades.php?tipo=${encodeURIComponent(tipo)}`, {credentials:'same-origin'});
    const text = await res.text();
    let json=null; try{ json=JSON.parse(text);}catch{}

    if (!res.ok || !json || json.ok !== true){
      console.error(res.status, text);
      idActividad.innerHTML = `<option value="">Error al cargar actividades</option>`;
      msg('danger', json?.error || `Error al cargar (HTTP ${res.status}).`);
      return;
    }

    const rows = json.data || [];
    if (rows.length === 0){
      idActividad.innerHTML = `<option value="">Sin actividades para este tipo</option>`;
      return;
    }

    idActividad.innerHTML = `<option value="">Seleccione…</option>` + rows.map(a =>
      `<option value="${esc(a.idActividad)}">${esc(a.descripcion)}</option>`
    ).join('');

    idActividad.disabled = false;
  }

  async function consultar(){
    clearMsg();
    count.textContent = '0';

    if (!idActividad.value){
      msg('warning','Seleccione una actividad.');
      return;
    }

    tbody.innerHTML = `<tr><td colspan="6" class="text-secondary">Cargando…</td></tr>`;

    const params = new URLSearchParams({
      tipoParticipante: tipoParticipante.value,
      idActividad: idActividad.value,
      fase: fase.value
    });

    if (estatus.value) params.set('estatus', estatus.value);

    const res = await fetch(`${BASE_URL}/api/list_participaciones.php?${params.toString()}`, {credentials:'same-origin'});
    const text = await res.text();
    let json=null; try{ json=JSON.parse(text);}catch{}

    if (!res.ok || !json || json.ok !== true){
      console.error(res.status, text);
      tbody.innerHTML = `<tr><td colspan="6" class="text-secondary">Error al consultar.</td></tr>`;
      msg('danger', json?.error || `Error al consultar (HTTP ${res.status}).`);
      return;
    }

    const rows = json.data || [];
    count.textContent = String(rows.length);

    if (rows.length === 0){
      tbody.innerHTML = `<tr><td colspan="6" class="text-secondary">Sin resultados.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r => {
      const canAdvance = (r.fase !== 'ESTATAL') && (r.estatus !== 'DESCARTADO');
      return `
        <tr>
          <td>${esc(r.nombre)}</td>
          <td><code>${esc(r.identificador)}</code></td>
          <td><span class="badge bg-light text-dark border">${esc(r.fase)}</span></td>
          <td>${esc(r.estatus)}</td>
          <td class="text-secondary small">${esc(r.comentario || '')}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-outline-danger" data-act="DESCARTAR" data-id="${esc(r.idParticipacion)}">Descartar</button>
              <button class="btn btn-outline-secondary" data-act="REACTIVAR" data-id="${esc(r.idParticipacion)}">Reactivar</button>
              <button class="btn btn-guinda" ${canAdvance ? '' : 'disabled'} data-act="AVANZAR" data-id="${esc(r.idParticipacion)}">Avanzar</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    tbody.querySelectorAll('button[data-act]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const accion = btn.getAttribute('data-act');
        const idParticipacion = btn.getAttribute('data-id');

        let comentario = '';
        if (accion === 'DESCARTAR'){
          comentario = prompt('Comentario (opcional) para descarte:', '') ?? '';
        }
        if (accion === 'AVANZAR'){
          comentario = prompt('Comentario (opcional) para avance:', '') ?? '';
        }

        await actualizar(idParticipacion, accion, comentario);
      });
    });
  }

  async function actualizar(idParticipacion, accion, comentario){
    clearMsg();

    const payload = { idParticipacion, accion, comentario };

    const res = await fetch(`${BASE_URL}/api/actualizar_participacion.php`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });

    const text = await res.text();
    let json=null; try{ json=JSON.parse(text);}catch{}

    if (!res.ok || !json || json.ok !== true){
      console.error(res.status, text);
      msg('danger', json?.error || `Error al actualizar (HTTP ${res.status}).`);
      return;
    }

    msg('success', json.message || 'Actualización realizada.');
    await consultar();
  }

  tipoActividad.addEventListener('change', () => cargarActividades(tipoActividad.value));
  btnConsultar.addEventListener('click', consultar);

  // Init
  tbody.innerHTML = `<tr><td colspan="6" class="text-secondary">Realice una consulta.</td></tr>`;
</script>

</body>
</html>