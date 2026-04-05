<?php
session_start();
?>

<div class="container-fluid">
    <h1 class="mt-4">Informes de Devoluciones</h1>
    <p>Genera reportes detallados sobre las devoluciones por período y distribuidor.</p>

    <div class="card shadow mb-4">
        <div class="card-body">
            <h5 class="card-title">Filtros de Informe</h5>
            <form id="report-form" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="distribuidor-reporte" class="form-label">Distribuidor:</label>
                        <input type="text" id="distribuidor-reporte" class="form-control" placeholder="TODOS">
                    </div>
                    <div class="col-md-4">
                        <label for="fecha-desde-reporte" class="form-label">Fecha Desde:</label>
                        <input type="date" id="fecha-desde-reporte" class="form-control" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="fecha-hasta-reporte" class="form-label">Fecha Hasta:</label>
                        <input type="date" id="fecha-hasta-reporte" class="form-control" required>
                    </div>
                    
                    
                    
                    <div class="col-12 col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </button>
                    </div>
                </div>
            </form>

            <hr>
        </div>
    </div>
</div>