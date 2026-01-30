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
  <title>Registrar participantes | Normalismo</title>
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
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="h5 mb-1">Registrar participantes (por lote)</h1>
      <div class="text-secondary small">
        Busque alumnos por CURP, matrícula o nombre; agréguelo(s) a la selección y registre su participación.
      </div>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(url('/dashboard/index.php')) ?>">Volver</a>
  </div>

  <div class="row g-3">
    <!-- Panel A: búsqueda -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-body">
          <div class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
              <label class="form-label">Buscar alumno</label>
              <input id="q" class="form-control" placeholder="CURP, matrícula o nombre (mín. 2 caracteres)">
              <div class="form-text text-secondary">
                Sugerencia: pegue/escanee la CURP y presione Enter.
              </div>
            </div>
            <button id="btnBuscar" class="btn btn-guinda">Buscar</button>
          </div>

          <hr class="my-3">

          <div class="d-flex align-items-center justify-content-between">
            <div class="fw-semibold">Resultados</div>
            <div class="text-secondary small" id="resCount">0</div>
          </div>

          <div class="table-responsive mt-2">
            <table class="table table-sm align-middle">
              <thead>
                <tr class="text-secondary small">
                  <th>Alumno</th>
                  <th>CURP</th>
                  <th>Matrícula</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="tbodyResultados">
                <tr>
                  <td colspan="4" class="text-secondary">Realice una búsqueda.</td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>

    <!-- Panel B: selección + registro -->
    <div class="col-lg-5">
      <div class="card mb-3">
        <div class="card-body">
          <div class="fw-semibold mb-2">Selección actual</div>
          <div class="text-secondary small mb-2">
            Alumnos seleccionados: <span id="selCount" class="fw-semibold">0</span>
          </div>

          <div class="table-responsive" style="max-height: 260px; overflow:auto;">
            <table class="table table-sm align-middle mb-2">
              <thead>
                <tr class="text-secondary small">
                  <th>Alumno</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="tbodySeleccion">
                <tr><td class="text-secondary" colspan="2">Sin selección.</td></tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex gap-2">
            <button id="btnLimpiar" class="btn btn-outline-secondary btn-sm">Limpiar selección</button>
          </div>

          <hr class="my-3">

          <div class="fw-semibold mb-2">Configurar participación</div>

          <div class="mb-3">
            <label class="form-label">Tipo de actividad</label>
            <select id="tipoActividad" class="form-select">
              <option value="">Seleccione…</option>
              <option value="ACADEMICAS">Académicas</option>
              <option value="ARTISTICO-CULTURALES">Artístico-culturales</option>
              <option value="DEPORTIVAS">Deportivas</option>
            </select>
            <div class="form-text text-secondary">
              El valor debe coincidir con <code>actividades.tipoActividad</code>.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Actividad</label>
            <select id="idActividad" class="form-select" disabled>
              <option value="">Seleccione tipo primero…</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Fase</label>
            <input class="form-control" value="INSTITUCIONAL" disabled>
          </div>

          <div class="mb-3">
            <label class="form-label">Estatus</label>
            <input class="form-control" value="ACTIVO" disabled>
          </div>

          <div class="d-grid">
            <button id="btnRegistrar" class="btn btn-guinda" disabled>
              Registrar participación
            </button>
          </div>

          <div class="mt-2 text-secondary small">
            Se evitarán duplicados del mismo alumno en la misma actividad.
          </div>

        </div>
      </div>

      <div id="alertBox"></div>
    </div>
  </div>
</div>

<script>
const BASE = <?= json_encode(url('')) ?>; // "/normalismo"
const state = { resultados: [], seleccion: new Map() };

const $q = document.getElementById('q');
const $btnBuscar = document.getElementById('btnBuscar');
const $tbodyResultados = document.getElementById('tbodyResultados');
const $resCount = document.getElementById('resCount');

const $tbodySeleccion = document.getElementById('tbodySeleccion');
const $selCount = document.getElementById('selCount');
const $btnLimpiar = document.getElementById('btnLimpiar');

const $tipoActividad = document.getElementById('tipoActividad');
const $idActividad = document.getElementById('idActividad');
const $btnRegistrar = document.getElementById('btnRegistrar');

const $alertBox = document.getElementById('alertBox');

function escapeHtml(str) {
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function showAlert(type, msg) {
  $alertBox.innerHTML = `
    <div class="alert alert-${type} mt-2" role="alert">
      ${escapeHtml(msg)}
    </div>
  `;
}
function clearAlert(){ $alertBox.innerHTML=''; }

function renderResultados(items){
  $resCount.textContent = String(items.length);
  if (!items.length){
    $tbodyResultados.innerHTML = `<tr><td colspan="4" class="text-secondary">Sin resultados.</td></tr>`;
    return;
  }
  $tbodyResultados.innerHTML = items.map(it => {
    const disabled = state.seleccion.has(it.idAlumno) ? 'disabled' : '';
    return `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(it.nombreCompleto)}</div>
          <div class="text-secondary small">${escapeHtml(it.licenciatura || '')}</div>
        </td>
        <td class="small">${escapeHtml(it.curp || '')}</td>
        <td class="small">${escapeHtml(it.matricula || '')}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-secondary" data-add="${escapeHtml(it.idAlumno)}" ${disabled}>Agregar</button>
        </td>
      </tr>`;
  }).join('');

  document.querySelectorAll('[data-add]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-add');
      const item = items.find(x => x.idAlumno === id);
      if (item) addToSeleccion(item);
    });
  });
}

function renderSeleccion(){
  $selCount.textContent = String(state.seleccion.size);
  if (!state.seleccion.size){
    $tbodySeleccion.innerHTML = `<tr><td class="text-secondary" colspan="2">Sin selección.</td></tr>`;
  } else {
    const arr = Array.from(state.seleccion.values());
    $tbodySeleccion.innerHTML = arr.map(it => `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(it.nombreCompleto)}</div>
          <div class="text-secondary small">${escapeHtml(it.curp || '')}</div>
        </td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger" data-del="${escapeHtml(it.idAlumno)}">Quitar</button>
        </td>
      </tr>
    `).join('');
    document.querySelectorAll('[data-del]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-del');
        state.seleccion.delete(id);
        renderSeleccion();
        renderResultados(state.resultados);
        updateRegistrarEnabled();
      });
    });
  }
  updateRegistrarEnabled();
}

function addToSeleccion(item){
  state.seleccion.set(item.idAlumno, item);
  renderSeleccion();
  renderResultados(state.resultados);
  updateRegistrarEnabled();
}

function updateRegistrarEnabled(){
  const hasSel = state.seleccion.size > 0;
  const actOk = !!$idActividad.value;
  $btnRegistrar.disabled = !(hasSel && actOk);
}

async function buscar(){
  clearAlert();
  const q = $q.value.trim();
  if (q.length < 2){ showAlert('warning','Capture al menos 2 caracteres para buscar.'); return; }

  $btnBuscar.disabled = true;
  $btnBuscar.textContent = 'Buscando…';

  try{
    const resp = await fetch(`${BASE}/api/search_alumnos.php?q=${encodeURIComponent(q)}`, { credentials:'same-origin' });
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error || 'Error de búsqueda');
    state.resultados = data.items || [];
    renderResultados(state.resultados);
  } catch(e){
    showAlert('danger', e.message || 'Error inesperado.');
  } finally{
    $btnBuscar.disabled = false;
    $btnBuscar.textContent = 'Buscar';
  }
}

async function cargarActividades(){
  clearAlert();
  const tipo = $tipoActividad.value;
  $idActividad.disabled = true;
  $idActividad.innerHTML = `<option value="">Cargando…</option>`;
  updateRegistrarEnabled();

  if (!tipo){
    $idActividad.innerHTML = `<option value="">Seleccione tipo primero…</option>`;
    return;
  }

  try{
    const resp = await fetch(`${BASE}/api/get_actividades.php?tipo=${encodeURIComponent(tipo)}`, { credentials:'same-origin' });
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error || 'Error al cargar actividades');

    const items = data.items || [];
    if (!items.length){
      $idActividad.innerHTML = `<option value="">No hay actividades para este tipo.</option>`;
      return;
    }

    $idActividad.innerHTML = `<option value="">Seleccione…</option>` + items.map(a =>
      `<option value="${a.idActividad}">${escapeHtml(a.descripcion)}</option>`
    ).join('');

    $idActividad.disabled = false;
  } catch(e){
    showAlert('danger', e.message || 'Error inesperado.');
    $idActividad.innerHTML = `<option value="">Error al cargar.</option>`;
  } finally{
    updateRegistrarEnabled();
  }
}

async function registrar(){
  clearAlert();
  const idActividad = parseInt($idActividad.value, 10);
  if (!idActividad){ showAlert('warning','Seleccione una actividad.'); return; }
  if (state.seleccion.size === 0){ showAlert('warning','Seleccione al menos un alumno.'); return; }

  const idAlumnos = Array.from(state.seleccion.keys());

  $btnRegistrar.disabled = true;
  $btnRegistrar.textContent = 'Registrando…';

  try{
    const resp = await fetch(`${BASE}/api/registrar_participaciones.php`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body: JSON.stringify({ idActividad, idAlumnos })
    });
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error || 'No se pudo registrar');

    const msg = `Registro completado. Insertados: ${data.inserted}. Omitidos por duplicado: ${data.skipped}.` +
      (data.invalid?.length ? ` No válidos (otra escuela/no existen): ${data.invalid.length}.` : '');
    showAlert('success', msg);

    state.seleccion.clear();
    renderSeleccion();
    renderResultados(state.resultados);

    $q.value = '';
    $q.focus();
  } catch(e){
    showAlert('danger', e.message || 'Error inesperado.');
  } finally{
    $btnRegistrar.textContent = 'Registrar participación';
    updateRegistrarEnabled();
  }
}

// eventos
$btnBuscar.addEventListener('click', buscar);
$q.addEventListener('keydown', (ev)=>{ if(ev.key==='Enter'){ ev.preventDefault(); buscar(); }});
$btnLimpiar.addEventListener('click', ()=>{ state.seleccion.clear(); renderSeleccion(); renderResultados(state.resultados); $q.focus(); });

$tipoActividad.addEventListener('change', cargarActividades);
$idActividad.addEventListener('change', updateRegistrarEnabled);
$btnRegistrar.addEventListener('click', registrar);

// init
renderSeleccion();
renderResultados([]);
</script>

</body>
</html>
