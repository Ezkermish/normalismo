<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_login();

$title = 'Actividades académicas | Normalismo';
require __DIR__ . '/../inc/header.php';
?>

<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
  <div>
    <h1 class="h4 mb-1">Registro de actividades académicas</h1>
    <div class="small-muted">Captura CURP, valida pertenencia a tu Escuela Normal y asigna actividad (tipoActividad = Académica).</div>
  </div>
  <button class="btn btn-normalismo" data-bs-toggle="modal" data-bs-target="#modalCurp">Realizar registro</button>
</div>

<div id="alertHost"></div>

<div class="card card-glass p-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="small-muted">Registros recientes (faseEscolar = 1)</div>
    <button class="btn btn-sm btn-outline-light" id="btnRefresh">Actualizar</button>
  </div>
  <div class="table-responsive">
    <table class="table table-dark table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Alumno</th>
          <th>Actividad académica</th>
          <th style="width:120px;"></th>
        </tr>
      </thead>
      <tbody id="tblBody">
        <tr><td colspan="3" class="small-muted">Cargando…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal CURP -->
<div class="modal fade" id="modalCurp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content card-glass">
      <div class="modal-header border-0">
        <h5 class="modal-title">Buscar alumno por CURP</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">CURP (18 caracteres)</label>
        <input class="form-control" maxlength="18" minlength="18" data-curp-input id="curp" placeholder="Ej. ABCD010203HDFXXX09" required>
        <div class="small-muted mt-2">Las letras se convierten a mayúsculas automáticamente.</div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-normalismo" id="btnBuscar">Registrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Registro -->
<div class="modal fade" id="modalRegistro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content card-glass">
      <div class="modal-header border-0">
        <h5 class="modal-title">Asignación de actividad</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="alumnoDatos" class="mb-3"></div>

        <label class="form-label">Actividad académica</label>
        <select class="form-select" id="selActividad"></select>

        <input type="hidden" id="hidIdAlumno">
        <input type="hidden" id="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-normalismo" id="btnGuardar">Registrar</button>
      </div>
    </div>
  </div>
</div>

<script>
(async () => {
  "use strict";

  const modalCurpEl = document.getElementById('modalCurp');
  const modalRegistroEl = document.getElementById('modalRegistro');
  const modalCurp = new bootstrap.Modal(modalCurpEl);
  const modalRegistro = new bootstrap.Modal(modalRegistroEl);

  const curp = document.getElementById('curp');
  const btnBuscar = document.getElementById('btnBuscar');
  const btnGuardar = document.getElementById('btnGuardar');
  const selActividad = document.getElementById('selActividad');
  const alumnoDatos = document.getElementById('alumnoDatos');
  const hidIdAlumno = document.getElementById('hidIdAlumno');
  const csrfToken = document.getElementById('csrf_token').value;
  const tblBody = document.getElementById('tblBody');

  async function fetchJSON(url, options){
    const res = await fetch(url, options);
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false){
      throw new Error(data.message || 'Ocurrió un error.');
    }
    return data;
  }

  async function cargarActividades(){
    const data = await fetchJSON('<?= url('api/actividades.php') ?>?tipo=Acad%C3%A9mica');
    selActividad.innerHTML = '';
    data.items.forEach(it => {
      const opt = document.createElement('option');
      opt.value = it.descripcion;
      opt.textContent = it.descripcion;
      selActividad.appendChild(opt);
    });
  }

  async function cargarTabla(){
    const data = await fetchJSON('<?= url('api/lista_academica.php') ?>');
    tblBody.innerHTML = '';
    if (!data.items.length){
      tblBody.innerHTML = `<tr><td colspan="3" class="small-muted">Sin registros.</td></tr>`;
      return;
    }
    data.items.forEach(it => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${escapeHtml(it.nombreCompleto)}</td>
        <td>${escapeHtml(it.actividadAcademica || '')}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-warning" data-del="${escapeHtml(it.idAlumno)}">Eliminar</button>
        </td>
      `;
      tblBody.appendChild(tr);
    });
  }

  function escapeHtml(str){
    return String(str)
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;")
      .replaceAll('"',"&quot;")
      .replaceAll("'","&#039;");
  }

  document.getElementById('btnRefresh').addEventListener('click', cargarTabla);

  tblBody.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button[data-del]');
    if (!btn) return;
    const idAlumno = btn.getAttribute('data-del');
    if (!confirm('¿Desea eliminar el registro?')) return;

    try{
      await fetchJSON('<?= url('api/delete_registro_academico.php') ?>', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({idAlumno, csrf_token: csrfToken})
      });
      normalismoAlert('alertHost', 'Registro eliminado correctamente.', 'success', 3500);
      await cargarTabla();
    }catch(e){
      normalismoAlert('alertHost', e.message, 'danger', 5000);
    }
  });

  btnBuscar.addEventListener('click', async () => {
    const value = (curp.value || '').trim().toUpperCase();
    if (value.length !== 18){
      normalismoAlert('alertHost', 'Debe capturar una CURP válida de 18 caracteres.', 'warning', 5000);
      return;
    }
    try{
      const data = await fetchJSON('<?= url('api/alumno_lookup.php') ?>?curp=' + encodeURIComponent(value));
      // llena datos
      const n = data.alumno;
      hidIdAlumno.value = n.idAlumno;
      alumnoDatos.innerHTML = `
        <div class="card card-glass p-3">
          <div class="small-muted mb-2">Elige la actividad académica en la que deseas inscribir a:</div>
          <div class="fs-5 fw-semibold">${escapeHtml(n.nombre)} ${escapeHtml(n.apPaterno)} ${escapeHtml(n.apMaterno)}</div>
          <div class="small-muted">CURP: ${escapeHtml(n.curp)} | Matrícula: ${escapeHtml(n.matricula)}</div>
        </div>
      `;
      await cargarActividades();
      modalCurp.hide();
      modalRegistro.show();
    }catch(e){
      normalismoAlert('alertHost', e.message || 'Alumno no encontrado.', 'danger', 5000);
    }
  });

  btnGuardar.addEventListener('click', async () => {
    const idAlumno = hidIdAlumno.value;
    const actividad = selActividad.value;
    try{
      await fetchJSON('<?= url('api/registro_academico.php') ?>', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({idAlumno, actividad, csrf_token: csrfToken})
      });
      modalRegistro.hide();
      normalismoAlert('alertHost', 'Registro realizado correctamente.', 'success', 3500);
      await cargarTabla();
    }catch(e){
      normalismoAlert('alertHost', e.message, 'danger', 5000);
    }
  });

  // init
  await cargarTabla();

})();
</script>

<?php require __DIR__ . '/../inc/footer.php'; ?>
