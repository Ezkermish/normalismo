<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/app.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . url('/auth/login.php'));
    exit;
}

$user   = $_SESSION['user'];
$rol    = strtoupper(trim((string)($user['rol'] ?? '')));
$region = trim((string)($user['region'] ?? ''));

if ($rol !== 'REGION') {
    http_response_code(403);
    exit('Acceso restringido al panel regional.');
}

if ($region === '') {
    http_response_code(403);
    exit('El usuario no tiene una región asignada.');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Panel Regional - <?= htmlspecialchars($region) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

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

        body{ background:#f7f7f7; }
        .navbar-brand{ color:var(--c-wine)!important; font-weight:700; }
        .card-kpi{ border-left:5px solid var(--c-guinda); }
        .section-title{ color:var(--c-wine); font-weight:700; }
        .btn-guinda{ background:var(--c-guinda); color:#fff; border:none; }
        .btn-guinda:hover{ background:var(--c-wine); color:#fff; }
        .table thead th{ background:var(--c-wine); color:#fff; vertical-align:middle; }
        .small-muted{ font-size:.9rem; color:#6c757d; }
        .chart-wrap{ position:relative; min-height:360px; }
        .tab-pane{ padding-top:1rem; }
        .report-block{ page-break-inside: avoid; }
        #pdfArea .card { box-shadow: none !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom mb-4">
    <div class="container-fluid px-4">
        <span class="navbar-brand">Panel Regional</span>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="badge text-bg-secondary">Región: <?= htmlspecialchars($region) ?></span>
            <span class="text-secondary small">Usuario: <?= htmlspecialchars((string)($user['nomUsuario'] ?? '')) ?></span>
            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(url('/auth/logout.php')) ?>">Salir</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4" id="pdfArea">

    <div class="card shadow-sm mb-4">
        <div class="card-header section-title">Filtros de consulta</div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="filtroFase" class="form-label">Fase</label>
                    <select id="filtroFase" class="form-select">
                        <option value="">Todas</option>
                        <option value="INSTITUCIONAL">INSTITUCIONAL</option>
                        <option value="REGIONAL">REGIONAL</option>
                        <option value="ESTATAL">ESTATAL</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filtroTipoActividad" class="form-label">Tipo de actividad</label>
                    <select id="filtroTipoActividad" class="form-select">
                        <option value="">Todas</option>
                        <option value="ACADEMICA">ACADEMICA</option>
                        <option value="ARTISTICO-CULTURAL">ARTISTICO-CULTURAL</option>
                        <option value="DEPORTIVA">DEPORTIVA</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtroActividad" class="form-label">Actividad específica</label>
                    <select id="filtroActividad" class="form-select">
                        <option value="">Todas</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtroEscuela" class="form-label">Escuela o CCT</label>
                    <input type="text" id="filtroEscuela" class="form-control" placeholder="Nombre de escuela o CCT">
                </div>

                <div class="col-md-2 d-grid">
                    <button id="btnAplicar" class="btn btn-guinda">Actualizar</button>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <div class="small-muted">
                        Se consideran únicamente participaciones con estatus <strong>ACTIVO</strong>.
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <button id="btnExcel" class="btn btn-success btn-sm">Descargar Excel</button>
                    <button id="btnPdf" class="btn btn-danger btn-sm">Descargar PDF</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4 report-block">
        <div class="col-md-2">
            <div class="card card-kpi shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Alumnos activos</div>
                    <div class="fs-3 fw-bold" id="kpiAlumnos">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-kpi shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Docentes activos</div>
                    <div class="fs-3 fw-bold" id="kpiDocentes">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-kpi shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Total participaciones</div>
                    <div class="fs-3 fw-bold" id="kpiTotal">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-kpi shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Escuelas con registros</div>
                    <div class="fs-3 fw-bold" id="kpiEscuelas">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-kpi shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Tipos de actividad</div>
                    <div class="fs-3 fw-bold" id="kpiTipos">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-kpi shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Actividades distintas</div>
                    <div class="fs-3 fw-bold" id="kpiActividades">0</div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="tabsRegion" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabResumen" type="button">Resumen</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAlumnos" type="button">Alumnos</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDocentes" type="button">Docentes</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEscuelas" type="button">Por escuela</button></li>
    </ul>

    <div class="tab-content bg-white border border-top-0 p-3 mb-4">
        <div class="tab-pane fade show active" id="tabResumen">
            <div class="row g-4 mb-4 report-block">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header section-title">Gráfica por fase</div>
                        <div class="card-body">
                            <div class="chart-wrap">
                                <canvas id="chartFase"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header section-title">Gráfica por actividad</div>
                        <div class="card-body">
                            <div class="chart-wrap">
                                <canvas id="chartActividad"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 report-block">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header section-title">Resumen por fase</div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered table-striped" id="tablaResumenFase">
                                <thead>
                                    <tr>
                                        <th>Fase</th>
                                        <th>Alumnos</th>
                                        <th>Docentes</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header section-title">Resumen por tipo de actividad</div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered table-striped" id="tablaResumenTipo">
                                <thead>
                                    <tr>
                                        <th>Tipo de actividad</th>
                                        <th>Alumnos</th>
                                        <th>Docentes</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header section-title">Top de actividades</div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered table-striped" id="tablaTopActividades">
                                <thead>
                                    <tr>
                                        <th>Tipo de actividad</th>
                                        <th>Actividad</th>
                                        <th>Alumnos</th>
                                        <th>Docentes</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tabAlumnos">
            <div class="card shadow-sm">
                <div class="card-header section-title">Participaciones activas de alumnos</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped" id="tablaAlumnos">
                        <thead>
                            <tr>
                                <th>CCT</th>
                                <th>Escuela</th>
                                <th>Fase</th>
                                <th>Tipo de actividad</th>
                                <th>Actividad</th>
                                <th>Total alumnos</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tabDocentes">
            <div class="card shadow-sm">
                <div class="card-header section-title">Participaciones activas de docentes</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped" id="tablaDocentes">
                        <thead>
                            <tr>
                                <th>CCT</th>
                                <th>Escuela</th>
                                <th>Fase</th>
                                <th>Tipo de actividad</th>
                                <th>Actividad</th>
                                <th>Total docentes</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tabEscuelas">
            <div class="card shadow-sm mb-4">
                <div class="card-header section-title">Resumen por escuela</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped" id="tablaPorEscuela">
                        <thead>
                            <tr>
                                <th>CCT</th>
                                <th>Escuela</th>
                                <th>Alumnos activos</th>
                                <th>Docentes activos</th>
                                <th>Total participaciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header section-title">Escuelas de la región sin registros activos</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped" id="tablaSinRegistros">
                        <thead>
                            <tr>
                                <th>CCT</th>
                                <th>Escuela</th>
                                <th>Región</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>

<script>
const BASE_DATA_URL = 'data.php';
let chartFase = null;
let chartActividad = null;

const state = {
    kpis: {},
    resumenFase: [],
    resumenTipo: [],
    topActividades: [],
    alumnos: [],
    docentes: [],
    porEscuela: [],
    sinRegistros: []
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text ?? '';
    return div.innerHTML;
}

function filtros() {
    return {
        fase: $('#filtroFase').val(),
        tipoActividad: $('#filtroTipoActividad').val(),
        idActividad: $('#filtroActividad').val(),
        escuela: $('#filtroEscuela').val().trim()
    };
}

function buildQuery(params) {
    return new URLSearchParams(params).toString();
}

function llenarTabla(selector, rows, columns) {
    const tbody = $(selector + ' tbody');
    tbody.empty();

    if (!rows || rows.length === 0) {
        tbody.append('<tr><td colspan="' + columns.length + '" class="text-center text-muted">Sin datos</td></tr>');
        return;
    }

    rows.forEach(row => {
        let tr = '<tr>';
        columns.forEach(col => tr += '<td>' + escapeHtml(row[col]) + '</td>');
        tr += '</tr>';
        tbody.append(tr);
    });
}

async function fetchJson(action, extra = {}) {
    const url = BASE_DATA_URL + '?' + buildQuery({ action, ...filtros(), ...extra });
    const resp = await fetch(url);
    return await resp.json();
}

async function cargarActividades() {
    const data = await fetchJson('catalogo_actividades', { tipoActividad: $('#filtroTipoActividad').val() });
    const select = $('#filtroActividad');
    const actual = select.val();

    select.empty().append('<option value="">Todas</option>');
    (data || []).forEach(item => {
        select.append('<option value="' + item.idActividad + '">' + escapeHtml(item.descripcion) + ' [' + escapeHtml(item.tipoActividad) + ']</option>');
    });

    if (actual) select.val(actual);
}

function renderKpis() {
    $('#kpiAlumnos').text(state.kpis.total_alumnos ?? 0);
    $('#kpiDocentes').text(state.kpis.total_docentes ?? 0);
    $('#kpiTotal').text(state.kpis.total_participaciones ?? 0);
    $('#kpiEscuelas').text(state.kpis.total_escuelas ?? 0);
    $('#kpiTipos').text(state.kpis.total_tipos_actividad ?? 0);
    $('#kpiActividades').text(state.kpis.total_actividades ?? 0);
}

function renderCharts() {
    if (chartFase) chartFase.destroy();
    chartFase = new Chart(document.getElementById('chartFase'), {
        type: 'bar',
        data: {
            labels: state.resumenFase.map(x => x.fase),
            datasets: [{ label: 'Participaciones', data: state.resumenFase.map(x => Number(x.total || 0)) }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    if (chartActividad) chartActividad.destroy();
    const top10 = state.topActividades.slice(0, 10);
    chartActividad = new Chart(document.getElementById('chartActividad'), {
        type: 'bar',
        data: {
            labels: top10.map(x => x.descripcion),
            datasets: [{ label: 'Participaciones', data: top10.map(x => Number(x.total || 0)) }]
        },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y' }
    });
}

function renderAllTables() {
    llenarTabla('#tablaResumenFase', state.resumenFase, ['fase', 'alumnos', 'docentes', 'total']);
    llenarTabla('#tablaResumenTipo', state.resumenTipo, ['tipoActividad', 'alumnos', 'docentes', 'total']);
    llenarTabla('#tablaTopActividades', state.topActividades, ['tipoActividad', 'descripcion', 'alumnos', 'docentes', 'total']);
    llenarTabla('#tablaAlumnos', state.alumnos, ['cct', 'escuela', 'fase', 'tipoActividad', 'descripcion', 'total_alumnos']);
    llenarTabla('#tablaDocentes', state.docentes, ['cct', 'escuela', 'fase', 'tipoActividad', 'descripcion', 'total_docentes']);
    llenarTabla('#tablaPorEscuela', state.porEscuela, ['cct', 'escuela', 'alumnos_activos', 'docentes_activos', 'total_participaciones']);
    llenarTabla('#tablaSinRegistros', state.sinRegistros, ['cct', 'escuela', 'region']);
}

async function recargarTodo() {
    const [
        kpis,
        resumenFase,
        resumenTipo,
        topActividades,
        alumnos,
        docentes,
        porEscuela,
        sinRegistros
    ] = await Promise.all([
        fetchJson('kpis'),
        fetchJson('resumen_fase'),
        fetchJson('resumen_tipo'),
        fetchJson('top_actividades'),
        fetchJson('alumnos'),
        fetchJson('docentes'),
        fetchJson('por_escuela'),
        fetchJson('sin_registros')
    ]);

    state.kpis = kpis || {};
    state.resumenFase = resumenFase || [];
    state.resumenTipo = resumenTipo || [];
    state.topActividades = topActividades || [];
    state.alumnos = alumnos || [];
    state.docentes = docentes || [];
    state.porEscuela = porEscuela || [];
    state.sinRegistros = sinRegistros || [];

    renderKpis();
    renderCharts();
    renderAllTables();
}

function exportExcelClient() {
    const wb = XLSX.utils.book_new();

    const resumenSheet = XLSX.utils.json_to_sheet([
        { Indicador: 'Región', Valor: '<?= htmlspecialchars($region, ENT_QUOTES) ?>' },
        { Indicador: 'Total alumnos activos', Valor: state.kpis.total_alumnos ?? 0 },
        { Indicador: 'Total docentes activos', Valor: state.kpis.total_docentes ?? 0 },
        { Indicador: 'Total participaciones', Valor: state.kpis.total_participaciones ?? 0 },
        { Indicador: 'Escuelas con registros', Valor: state.kpis.total_escuelas ?? 0 },
        { Indicador: 'Tipos de actividad', Valor: state.kpis.total_tipos_actividad ?? 0 },
        { Indicador: 'Actividades distintas', Valor: state.kpis.total_actividades ?? 0 }
    ]);
    XLSX.utils.book_append_sheet(wb, resumenSheet, 'Resumen');

    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(state.resumenFase), 'ResumenFase');
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(state.resumenTipo), 'ResumenTipo');
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(state.topActividades), 'TopActividades');
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(state.alumnos), 'Alumnos');
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(state.docentes), 'Docentes');
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(state.porEscuela), 'PorEscuela');
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(state.sinRegistros), 'SinRegistros');

    const nombre = 'reporte_regional_<?= strtolower(preg_replace("/\s+/", "_", $region)) ?>_' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.xlsx';
    XLSX.writeFile(wb, nombre);
}

function exportPdfClient() {
    const el = document.getElementById('pdfArea');
    const nombre = 'reporte_regional_<?= strtolower(preg_replace("/\s+/", "_", $region)) ?>_' + new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '.pdf';

    const opt = {
        margin: 0.3,
        filename: nombre,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' },
        pagebreak: { mode: ['css', 'legacy'] }
    };

    html2pdf().set(opt).from(el).save();
}

$(async function() {
    await cargarActividades();
    await recargarTodo();

    $('#filtroTipoActividad').on('change', async function() {
        await cargarActividades();
    });

    $('#btnAplicar').on('click', async function() {
        await recargarTodo();
    });

    $('#btnExcel').on('click', exportExcelClient);
    $('#btnPdf').on('click', exportPdfClient);
});
</script>
</body>
</html>