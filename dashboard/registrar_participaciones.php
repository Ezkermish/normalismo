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
  <title>Registrar participaciones | Normalismo</title>
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
      <span class="badge badge-wine">
        CCT: <?= htmlspecialchars($_SESSION['user']['escuela']) ?>
      </span>
      <span class="text-secondary small">
        <?= htmlspecialchars($_SESSION['user']['nomUsuario']) ?>
      </span>
      <a href="<?= htmlspecialchars(url('/auth/logout.php')) ?>" class="btn btn-outline-secondary btn-sm">
        Salir
      </a>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4">
  <h1 class="h5 mb-3">Registro de participantes (alumnos)</h1>

  <div class="row g-3">

    <!-- ========================= -->
    <!-- Panel A: búsqueda alumnos -->
    <!-- ========================= -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-body">

          <div class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
              <label class="form-label">Buscar alumno</label>
              <input id="q" class="form-control"
                     placeholder="CURP, matrícula o nombre (mín. 3 caracteres)"
                     autocomplete="off">
              <div class="form-text text-secondary">
                Sugerencia: pegue o escanee la CURP y presione Enter.
              </div>
            </div>
            <button id="btnBuscar" class="btn btn-guinda" type="button">
              Buscar
            </button>
          </div>

          <div id="alertAlumno" class="alert alert-danger d-none mt-3"></div>

          <hr class="my-3">

          <div class="d-flex align-items-center justify-content-between">
            <div class="fw-semibold">Resultados</div>
            <div class="text-secondary small">
              <span id="resCount">0</span>
            </div>
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
                  <td colspan="4" class="text-secondary">
                    Realice una búsqueda.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>

    <!-- ========================= -->
    <!-- Panel B: selección + alta -->
    <!-- ========================= -->
    <div class="col-lg-5">
      <div class="card mb-3">
        <div class="card-body">

          <div class="fw-semibold mb-2">Selección actual</div>
          <div class="text-secondary small mb-2">
            Alumnos seleccionados:
            <span id="selCount" class="fw-semibold">0</span>
          </div>

          <div class="table-responsive" style="max-height:260px; overflow:auto;">
            <table class="table table-sm align-middle mb-2">
              <thead>
                <tr class="text-secondary small">
                  <th>Alumno</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="tbodySeleccion">
                <tr>
                  <td colspan="2" class="text-secondary">Sin selección.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex gap-2 mb-2">
            <button id="btnLimpiar" class="btn btn-outline-secondary btn-sm">
              Limpiar selección
            </button>
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

<!-- ========================= -->
<!-- JS -->
<!-- ========================= -->
<script>
  const BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
</script>

<script>
  // ==========
  // Referencias UI (Panel A)
  // ==========
  const inputQ = document.getElementById('q');
  const btnBuscar = document.getElementById('btnBuscar');
  const alertAlumno = document.getElementById('alertAlumno');
  const resCount = document.getElementById('resCount');
  const tbodyResultados = document.getElementById('tbodyResultados');

  // ==========
  // Referencias UI (Panel B)
  // ==========
  const selCount = document.getElementById('selCount');
  const tbodySeleccion = document.getElementById('tbodySeleccion');
  const btnLimpiar = document.getElementById('btnLimpiar');
  const tipoActividad = document.getElementById('tipoActividad');
  const idActividad = document.getElementById('idActividad');
  const btnRegistrar = document.getElementById('btnRegistrar');
  const alertBox = document.getElementById('alertBox');

  // ==========
  // Estado
  // ==========
  const selected = new Map(); // key: idAlumno (string) -> alumno

  // ==========
  // Helpers UI
  // ==========
  function escapeHtml(str) {
    return String(str ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function showAlertAlumno(msg) {
    if (!alertAlumno) return;
    alertAlumno.textContent = msg;
    alertAlumno.classList.remove('d-none');
  }
  function hideAlertAlumno() {
    if (!alertAlumno) return;
    alertAlumno.textContent = '';
    alertAlumno.classList.add('d-none');
  }

  function showAlert(type, msg) {
    // type: 'success' | 'danger' | 'warning' | 'info'
    alertBox.innerHTML = `
      <div class="alert alert-${escapeHtml(type)}" role="alert">
        ${escapeHtml(msg)}
      </div>
    `;
  }
  function clearAlert() {
    alertBox.innerHTML = '';
  }

  function setResultadosVacio(msg) {
    tbodyResultados.innerHTML = `<tr><td colspan="4" class="text-secondary">${escapeHtml(msg)}</td></tr>`;
    resCount.textContent = '0';
  }

  function updateRegistrarState() {
    const hasSeleccion = selected.size > 0;
    const hasActividad = !!idActividad.value;
    btnRegistrar.disabled = !(hasSeleccion && hasActividad);
  }

  // ==========
  // Render: Panel A (resultados)
  // ==========
  function renderResultados(rows) {
    if (!rows || rows.length === 0) {
      setResultadosVacio('Sin resultados.');
      return;
    }

    resCount.textContent = String(rows.length);

    tbodyResultados.innerHTML = rows.map(r => {
      const alumno = `${r.apPaterno ?? ''} ${r.apMaterno ?? ''} ${r.nombre ?? ''}`.trim();
      const already = selected.has(String(r.idAlumno));
      return `
        <tr>
          <td>${escapeHtml(alumno)}</td>
          <td><code>${escapeHtml(r.curp)}</code></td>
          <td>${escapeHtml(r.matricula)}</td>
          <td class="text-end">
            <button class="btn btn-sm ${already ? 'btn-outline-secondary' : 'btn-guinda'}"
                    type="button"
                    data-add="${escapeHtml(r.idAlumno)}"
                    ${already ? 'disabled' : ''}>
              ${already ? 'Agregado' : 'Agregar'}
            </button>
          </td>
        </tr>
      `;
    }).join('');

    tbodyResultados.querySelectorAll('button[data-add]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-add');
        const alumno = rows.find(x => String(x.idAlumno) === String(id));
        if (alumno) addToSelection(alumno);
      });
    });
  }

  // ==========
  // Render: Panel B (selección)
  // ==========
  function renderSeleccion() {
    selCount.textContent = String(selected.size);

    const rows = Array.from(selected.values());
    if (rows.length === 0) {
      tbodySeleccion.innerHTML = `<tr><td class="text-secondary" colspan="2">Sin selección.</td></tr>`;
      updateRegistrarState();
      return;
    }

    tbodySeleccion.innerHTML = rows.map(r => {
      const alumno = `${r.apPaterno ?? ''} ${r.apMaterno ?? ''} ${r.nombre ?? ''}`.trim();
      return `
        <tr>
          <td>${escapeHtml(alumno)}<br><span class="text-secondary small"><code>${escapeHtml(r.curp ?? '')}</code></span></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-danger" type="button" data-del="${escapeHtml(r.idAlumno)}">Quitar</button>
          </td>
        </tr>
      `;
    }).join('');

    tbodySeleccion.querySelectorAll('button[data-del]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-del');
        selected.delete(String(id));
        renderSeleccion();

      });
    });

    updateRegistrarState();
  }

  function addToSelection(alumno) {
    clearAlert();

    const id = String(alumno.idAlumno);
    if (selected.has(id)) {
      showAlert('warning', 'El alumno ya está en la selección.');
      return;
    }

    selected.set(id, alumno);
    renderSeleccion();

    const btn = tbodyResultados.querySelector(`button[data-add="${CSS.escape(id)}"]`);
    if (btn) {
      btn.classList.remove('btn-guinda');
      btn.classList.add('btn-outline-secondary');
      btn.textContent = 'Agregado';
      btn.disabled = true;
    }
  }

  // ==========
  // API: buscar alumnos
  // ==========
  async function buscarAlumnos(term) {
    hideAlertAlumno();
    clearAlert();

    term = (term || '').trim();

    if (term.length < 3) {
      setResultadosVacio('Escriba al menos 3 caracteres.');
      return;
    }

    const url = `${BASE_URL}/api/search_alumnos.php?q=${encodeURIComponent(term)}`;

    const res = await fetch(url, { credentials: 'same-origin' });
    const text = await res.text();
    let payload = null;
    try { payload = JSON.parse(text); } catch { /* noop */ }

    if (!res.ok || !payload || payload.ok !== true) {
      console.error('API error:', res.status, text);
      showAlertAlumno(payload?.error || `Error en API (HTTP ${res.status}).`);
      setResultadosVacio('Realice una búsqueda.');
      return;
    }

    renderResultados(payload.data);
  }

  // ==========
  // API: cargar actividades por tipo
  // ==========
  async function cargarActividades(tipo) {
    clearAlert();

    idActividad.disabled = true;
    idActividad.innerHTML = `<option value="">Cargando…</option>`;
    updateRegistrarState();

    if (!tipo) {
      idActividad.innerHTML = `<option value="">Seleccione tipo primero…</option>`;
      return;
    }

    const url = `${BASE_URL}/api/get_actividades.php?tipo=${encodeURIComponent(tipo)}`;
    const res = await fetch(url, { credentials: 'same-origin' });
    const text = await res.text();
    let payload = null;
    try { payload = JSON.parse(text); } catch { /* noop */ }

    if (!res.ok || !payload || payload.ok !== true) {
      console.error('API error:', res.status, text);
      idActividad.innerHTML = `<option value="">Error al cargar actividades</option>`;
      showAlert('danger', payload?.error || `Error al cargar actividades (HTTP ${res.status}).`);
      return;
    }

    const rows = payload.data || [];
    if (rows.length === 0) {
      idActividad.innerHTML = `<option value="">Sin actividades para este tipo</option>`;
      return;
    }

    idActividad.innerHTML = `<option value="">Seleccione…</option>` + rows.map(a =>
      `<option value="${escapeHtml(a.idActividad)}">${escapeHtml(a.descripcion)}</option>`
    ).join('');

    idActividad.disabled = false;
    updateRegistrarState();
  }

  // ==========
  // API: registrar participaciones (lote)
  // ==========
  async function registrarParticipaciones() {
    clearAlert();

    if (selected.size === 0) {
      showAlert('warning', 'No hay alumnos seleccionados.');
      return;
    }
    if (!idActividad.value) {
      showAlert('warning', 'Seleccione una actividad.');
      return;
    }

    btnRegistrar.disabled = true;

    const payload = {
      idActividad: idActividad.value,
      alumnos: Array.from(selected.keys()) // idAlumno[]
    };

    try {
      const res = await fetch(`${BASE_URL}/api/registrar_participaciones.php`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const text = await res.text();
      let json = null;
      try { json = JSON.parse(text); } catch { /* noop */ }

      if (!res.ok || !json || json.ok !== true) {
        console.error('API error:', res.status, text);
        showAlert('danger', json?.error || `Error al registrar (HTTP ${res.status}).`);
        updateRegistrarState();
        return;
      }

      // Esperado: json = { ok:true, inserted:n, skipped:n, ... }
      const ins = json.inserted ?? 0;
      const skip = json.skipped ?? 0;

      showAlert('success', `Registro completado. Insertados: ${ins}. Omitidos (duplicados): ${skip}.`);

      // Limpia selección tras registrar
      selected.clear();
      renderSeleccion();
      updateRegistrarState();

      // limpiar búsqueda no resultó
      // inputQ.value = '';
      // setResultadosVacio('Realice una búsqueda.');

    } catch (e) {
      console.error(e);
      showAlert('danger', 'Error de red o servidor al registrar.');
      updateRegistrarState();
    }
  }

  // ==========
  // Eventos UI
  // ==========
  // Debounce en input búsqueda
  let t = null;
  inputQ.addEventListener('input', () => {
    clearTimeout(t);
    t = setTimeout(() => buscarAlumnos(inputQ.value), 250);
  });

  inputQ.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      buscarAlumnos(inputQ.value);
    }
  });

  btnBuscar.addEventListener('click', () => buscarAlumnos(inputQ.value));

  btnLimpiar.addEventListener('click', (e) => {
    e.preventDefault();
    selected.clear();
    renderSeleccion();
    clearAlert();
    updateRegistrarState();
  });

  tipoActividad.addEventListener('change', () => cargarActividades(tipoActividad.value));

  idActividad.addEventListener('change', () => updateRegistrarState());

  btnRegistrar.addEventListener('click', (e) => {
    e.preventDefault();
    registrarParticipaciones();
  });

  // ==========
  // Inicialización
  // ==========
  setResultadosVacio('Realice una búsqueda.');
  renderSeleccion();
  updateRegistrarState();
</script>

</body>
</html>
