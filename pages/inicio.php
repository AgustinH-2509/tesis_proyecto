<?php
session_start();
?>

<div class="container mt-4">
    <h1>Bienvenido al Sistema</h1>
    <p>Selecciona una opción del menú lateral o haz clic en uno de los accesos directos a continuación:</p>

    <div class="row mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Historial de Devoluciones</h5>
                    <p class="card-text">Revisa el historial de todas las devoluciones registradas en el sistema.</p>
                    <a href="#" class="btn btn-primary" data-content-id="historial.php">Ir a Historial</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Control de Devoluciones</h5>
                    <p class="card-text">Gestiona y revisa el estado de las devoluciones del laboratorio.</p>
                    <a href="#" class="btn btn-primary" data-content-id="control_devoluciones.php">Ir a Control</a>
                </div>
            </div>
        </div>
    </div>
</div>
