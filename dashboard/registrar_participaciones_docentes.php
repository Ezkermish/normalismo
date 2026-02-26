<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/app.php';

if (empty($_SESSION['user'])) {
  header('Location: ' . url('/auth/login.php'));
  exit;
}

if (!REGISTRO_HABILITADO) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'El registro está cerrado.']);
  exit;
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar participaciones (Docentes) | Normalismo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars(url('/assets/css/theme.css')) ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center"
      href="<?= htmlspecialchars(url('/dashboard/index.php')) ?>">
      <img src="<?= htmlspecialchars(url('/assets/img/logo-normalismo.png')) ?>"
       alt="Normalismo"
       style="height:40px; width:auto;">
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <span class="badge badge-wine">CCT: <?= htmlspecialchars($_SESSION['user']['escuela']) ?></span>
      <span class="text-secondary small"><?= htmlspecialchars($_SESSION['user']['nomUsuario']) ?></span>
      <a href="<?= htmlspecialchars(url('/auth/logout.php')) ?>" class="btn btn-outline-secondary btn-sm">Salir</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4">
  <h1 class="h5 mb-3">Registro de participantes (docentes)</h1>

  <div class="row g-3">

    <!-- Panel A: búsqueda docentes -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-body">

          <div class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
              <label class="form-label">Buscar docente</label>
              <input id="q" class="form-control" placeholder="ID Docente, RFC o nombre (mín. 3 caracteres)" autocomplete="off">
              <div class="form-text text-secondary">Sugerencia: pegue el RFC y presione Enter.</div>
            </div>
            <button id="btnBuscar" class="btn btn-guinda" type="button">Buscar</button>
          </div>

          <div id="alertBoxA" class="alert alert-danger d-none mt-3"></div>

          <hr class="my-3">

          <div class="d-flex align-items-center justify-content-between">
            <div class="fw-semibold">Resultados</div>
            <div class="text-secondary small"><span id="resCount">0</span></div>
          </div>

          <div class="table-responsive mt-2">
            <table class="table table-sm align-middle">
              <thead>
                <tr class="text-secondary small">
                  <th>Docente</th>
                  <th>ID</th>
                  <th>RFC</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="tbodyResultados">
                <tr><td colspan="4" class="text-secondary">Realice una búsqueda.</td></tr>
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
            Docentes seleccionados: <span id="selCount" class="fw-semibold">0</span>
          </div>

          <div class="table-responsive" style="max-height:260px; overflow:auto;">
            <table class="table table-sm align-middle mb-2">
              <thead>
                <tr class="text-secondary small">
                  <th>Docente</th>
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
              <option value="Académica">Académicas</option>
              <option value="Artístico-Culturales">Artístico-culturales</option>
              <option value="Deportivas">Deportivas</option>
            </select>
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
            <button id="btnRegistrar" class="btn btn-guinda" disabled>Registrar participación</button>
          </div>

          <div class="mt-2 text-secondary small">
            Se evitarán duplicados del mismo docente en la misma actividad.
          </div>

        </div>
      </div>

      <div id="alertBox"></div>
    </div>

  </div>
</div>

<script>
  const BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;

  const inputQ = document.getElementById('q');
  const btnBuscar = document.getElementById('btnBuscar');
  const alertBoxA = document.getElementById('alertBoxA');
  const resCount = document.getElementById('resCount');
  const tbodyResultados = document.getElementById('tbodyResultados');

  const selCount = document.getElementById('selCount');
  const tbodySeleccion = document.getElementById('tbodySeleccion');
  const btnLimpiar = document.getElementById('btnLimpiar');
  const tipoActividad = document.getElementById('tipoActividad');
  const idActividad = document.getElementById('idActividad');
  const btnRegistrar = document.getElementById('btnRegistrar');
  const alertBox = document.getElementById('alertBox');

  const selected = new Map(); // idDocente -> row

  function esc(s){ return String(s??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;"); }
  function showA(msg){ alertBoxA.textContent = msg; alertBoxA.classList.remove('d-none'); }
  function hideA(){ alertBoxA.textContent = ''; alertBoxA.classList.add('d-none'); }
  function show(type,msg){ alertBox.innerHTML = `<div class="alert alert-${esc(type)}">${esc(msg)}</div>`; }
  function clearMsg(){ alertBox.innerHTML=''; }

  function setResultadosVacio(msg){
    tbodyResultados.innerHTML = `<tr><td colspan="4" class="text-secondary">${esc(msg)}</td></tr>`;
    resCount.textContent = '0';
  }

  function updateRegistrarState(){
    btnRegistrar.disabled = !(selected.size > 0 && !!idActividad.value);
  }

  function renderResultados(rows){
    if (!rows || rows.length===0){ setResultadosVacio('Sin resultados.'); return; }
    resCount.textContent = String(rows.length);

    tbodyResultados.innerHTML = rows.map(r=>{
      const already = selected.has(String(r.idDocente));
      return `
        <tr>
          <td>${esc(r.nombre)}</td>
          <td><code>${esc(r.idDocente)}</code></td>
          <td>${esc(r.rfc)}</td>
          <td class="text-end">
            <button class="btn btn-sm ${already?'btn-outline-secondary':'btn-guinda'}"
                    type="button"
                    data-add="${esc(r.idDocente)}"
                    ${already?'disabled':''}>
              ${already?'Agregado':'Agregar'}
            </button>
          </td>
        </tr>`;
    }).join('');

    tbodyResultados.querySelectorAll('button[data-add]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-add');
        const row = rows.find(x=>String(x.idDocente)===String(id));
        if (row) addToSelection(row);
      });
    });
  }

  function renderSeleccion(){
    selCount.textContent = String(selected.size);
    const rows = Array.from(selected.values());
    if (rows.length===0){
      tbodySeleccion.innerHTML = `<tr><td colspan="2" class="text-secondary">Sin selección.</td></tr>`;
      updateRegistrarState(); return;
    }

    tbodySeleccion.innerHTML = rows.map(r=>`
      <tr>
        <td>${esc(r.nombre)}<br><span class="text-secondary small"><code>${esc(r.idDocente)}</code> • ${esc(r.rfc)}</span></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger" type="button" data-del="${esc(r.idDocente)}">Quitar</button>
        </td>
      </tr>
    `).join('');

    tbodySeleccion.querySelectorAll('button[data-del]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        selected.delete(String(btn.getAttribute('data-del')));
        renderSeleccion();
      });
    });

    updateRegistrarState();
  }

  function addToSelection(row){
    clearMsg(); hideA();
    const id = String(row.idDocente);
    if (selected.has(id)){ show('warning','El docente ya está en la selección.'); return; }
    selected.set(id, row);
    renderSeleccion();
  }

  async function buscar(term){
    clearMsg(); hideA();
    term = (term||'').trim();
    if (term.length < 3){ setResultadosVacio('Escriba al menos 3 caracteres.'); return; }

    const res = await fetch(`${BASE_URL}/api/search_docentes.php?q=${encodeURIComponent(term)}`, {credentials:'same-origin'});
    const text = await res.text();
    let payload=null; try{ payload=JSON.parse(text);}catch{}
    if (!res.ok || !payload || payload.ok!==true){
      console.error(res.status, text);
      showA(payload?.error || `Error en API (HTTP ${res.status}).`);
      setResultadosVacio('Realice una búsqueda.');
      return;
    }
    renderResultados(payload.data);
  }

  async function cargarActividades(tipo){
    clearMsg();
    idActividad.disabled = true;
    idActividad.innerHTML = `<option value="">Cargando…</option>`;
    updateRegistrarState();

    if (!tipo){
      idActividad.innerHTML = `<option value="">Seleccione tipo primero…</option>`;
      return;
    }

    const res = await fetch(`${BASE_URL}/api/get_actividades.php?tipo=${encodeURIComponent(tipo)}`, {credentials:'same-origin'});
    const text = await res.text();
    let payload=null; try{ payload=JSON.parse(text);}catch{}
    if (!res.ok || !payload || payload.ok!==true){
      console.error(res.status, text);
      idActividad.innerHTML = `<option value="">Error al cargar actividades</option>`;
      show('danger', payload?.error || `Error al cargar (HTTP ${res.status}).`);
      return;
    }

    const rows = payload.data || [];
    if (rows.length===0){
      idActividad.innerHTML = `<option value="">Sin actividades para este tipo</option>`;
      return;
    }

    idActividad.innerHTML = `<option value="">Seleccione…</option>` + rows.map(a =>
      `<option value="${esc(a.idActividad)}">${esc(a.descripcion)}</option>`
    ).join('');
    idActividad.disabled = false;
    updateRegistrarState();
  }

  async function registrar(){
    clearMsg(); hideA();
    if (selected.size===0){ show('warning','No hay docentes seleccionados.'); return; }
    if (!idActividad.value){ show('warning','Seleccione una actividad.'); return; }

    btnRegistrar.disabled = true;

    const payload = {
      idActividad: idActividad.value,
      docentes: Array.from(selected.keys())
    };

    const res = await fetch(`${BASE_URL}/api/registrar_participaciones_docentes.php`, {
      method:'POST',
      credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });

    const text = await res.text();
    let json=null; try{ json=JSON.parse(text);}catch{}
    if (!res.ok || !json || json.ok!==true){
      console.error(res.status, text);
      show('danger', json?.error || `Error al registrar (HTTP ${res.status}).`);
      updateRegistrarState();
      return;
    }

    show('success', `Registro completado. Insertados: ${json.inserted ?? 0}. Omitidos: ${json.skipped ?? 0}.`);
    selected.clear();
    renderSeleccion();
    updateRegistrarState();
  }

  // Eventos
  let t=null;
  inputQ.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(()=>buscar(inputQ.value), 250); });
  inputQ.addEventListener('keydown', (e)=>{ if (e.key==='Enter'){ e.preventDefault(); buscar(inputQ.value); }});
  btnBuscar.addEventListener('click', ()=>buscar(inputQ.value));

  btnLimpiar.addEventListener('click', (e)=>{ e.preventDefault(); selected.clear(); renderSeleccion(); clearMsg(); updateRegistrarState(); });

  tipoActividad.addEventListener('change', ()=>cargarActividades(tipoActividad.value));
  idActividad.addEventListener('change', updateRegistrarState);
  btnRegistrar.addEventListener('click', (e)=>{ e.preventDefault(); registrar(); });

  // Init
  setResultadosVacio('Realice una búsqueda.');
  renderSeleccion();
  updateRegistrarState();
</script>

</body>
</html>
