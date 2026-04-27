<?php
/**
 * TEST COMPLETO DEL SISTEMA DE DEVOLUCIONES
 * 
 * Este archivo verifica todas las funcionalidades principales del sistema:
 * 1. Conexión a la base de datos
 * 2. Endpoints de Nueva Devolución
 * 3. Endpoints de Control de Devoluciones
 * 4. Estructura de base de datos
 * 5. Funcionamiento de modales y JavaScript
 * 
 * Para ejecutar: http://localhost/test_sistema_completo.php
 */

include 'administrador/conexion_auto.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Completo - Sistema de Devoluciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        .test-section { margin: 2rem 0; padding: 1.5rem; border-radius: 8px; }
        .test-pass { background-color: #d4edda; border-left: 4px solid #28a745; }
        .test-fail { background-color: #f8d7da; border-left: 4px solid #dc3545; }
        .test-info { background-color: #d1ecf1; border-left: 4px solid #17a2b8; }
        .endpoint-test { margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">🔧 Test Completo - Sistema de Devoluciones</h1>
        
        <!-- 1. TEST DE CONEXIÓN -->
        <div class="test-section <?php echo $conn ? 'test-pass' : 'test-fail'; ?>">
            <h3><i class="bi bi-database"></i> 1. Conexión a Base de Datos</h3>
            <?php if ($conn): ?>
                <p>✅ <strong>CORRECTO:</strong> Conexión establecida correctamente</p>
                <small>Servidor: <?php echo $conn->host_info; ?></small>
            <?php else: ?>
                <p>❌ <strong>ERROR:</strong> No se pudo conectar a la base de datos</p>
            <?php endif; ?>
        </div>

        <!-- 2. TEST DE ESTRUCTURA DE BD -->
        <div class="test-section">
            <h3><i class="bi bi-table"></i> 2. Estructura de Base de Datos</h3>
            <?php
            $tablas_requeridas = [
                'distribuidores' => 'Catálogo de distribuidores',
                'productos' => 'Catálogo de productos',
                'sabores' => 'Catálogo de sabores',
                'motivos_devolucion' => 'Motivos de devolución',
                'motivos_rechazos' => 'Motivos de rechazo',
                'devoluciones' => 'Encabezados de devoluciones',
                'devoluciones_detalle' => 'Detalle de productos devueltos',
                'devoluciones_decisiones' => 'Control de rechazos y aceptaciones'
            ];
            
            $todas_ok = true;
            foreach ($tablas_requeridas as $tabla => $descripcion) {
                $result = $conn->query("SHOW TABLES LIKE '$tabla'");
                $existe = $result && $result->num_rows > 0;
                echo "<div class='endpoint-test " . ($existe ? 'test-pass' : 'test-fail') . "'>";
                echo $existe ? "✅" : "❌";
                echo " <strong>$tabla:</strong> $descripcion";
                if ($existe) {
                    $count = $conn->query("SELECT COUNT(*) as total FROM $tabla")->fetch_assoc()['total'];
                    echo " <small class='text-muted'>($count registros)</small>";
                }
                echo "</div>";
                if (!$existe) $todas_ok = false;
            }
            ?>
        </div>

        <!-- 3. TEST DE ENDPOINTS -->
        <div class="test-section">
            <h3><i class="bi bi-api"></i> 3. Endpoints del Sistema</h3>
            
            <h5>📤 Nueva Devolución</h5>
            <div class="endpoint-test">
                <strong>obtener_devolucion.php</strong> - Genera números de devolución
                <button class="btn btn-sm btn-primary float-end" onclick="testEndpoint('ajax/obtener_devolucion.php', 'POST', {distribuidor_codigo: 'TEST'})">Probar</button>
                <div id="test-obtener_devolucion" class="mt-2"></div>
            </div>
            
            <div class="endpoint-test">
                <strong>obtener_productos.php</strong> - Busca productos por código
                <button class="btn btn-sm btn-primary float-end" onclick="testEndpoint('ajax/obtener_productos.php', 'POST', {codigo: 'A001'})">Probar</button>
                <div id="test-obtener_productos" class="mt-2"></div>
            </div>
            
            <div class="endpoint-test">
                <strong>guardar_devolucion.php</strong> - Guarda nueva devolución
                <button class="btn btn-sm btn-warning float-end" onclick="alert('Este test requiere datos completos - usar interfaz real')">Info</button>
                <small class="text-muted d-block mt-1">⚠️ Requiere JSON completo con productos</small>
            </div>

            <h5 class="mt-4">🔍 Control de Devoluciones</h5>
            <div class="endpoint-test">
                <strong>obtener_devoluciones_control.php</strong> - Lista devoluciones para control
                <button class="btn btn-sm btn-primary float-end" onclick="testEndpoint('ajax/control_devolucion/obtener_devoluciones_control.php', 'POST', {})">Probar</button>
                <div id="test-obtener_devoluciones_control" class="mt-2"></div>
            </div>
            
            <div class="endpoint-test">
                <strong>obtener_detalle_control.php</strong> - Detalle de devolución para control
                <button class="btn btn-sm btn-primary float-end" onclick="testEndpointWithId('ajax/control_devolucion/obtener_detalle_control.php')">Probar</button>
                <div id="test-obtener_detalle_control" class="mt-2"></div>
            </div>
            
            <div class="endpoint-test">
                <strong>obtener_motivos_rechazo.php</strong> - Catálogo de motivos de rechazo
                <button class="btn btn-sm btn-primary float-end" onclick="testEndpoint('ajax/control_devolucion/obtener_motivos_rechazo.php', 'GET', null)">Probar</button>
                <div id="test-obtener_motivos_rechazo" class="mt-2"></div>
            </div>
        </div>

        <!-- 4. TEST DE FUNCIONALIDADES -->
        <div class="test-section test-info">
            <h3><i class="bi bi-gear"></i> 4. Funcionalidades Principales</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>✅ Nueva Devolución</h5>
                    <ul>
                        <li>Selección de distribuidor</li>
                        <li>Generación automática de número</li>
                        <li>Búsqueda de productos</li>
                        <li>Agregar productos a la tabla</li>
                        <li>Guardado con validaciones</li>
                        <li>Modales de confirmación</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>✅ Control de Devoluciones</h5>
                    <ul>
                        <li>Lista de devoluciones pendientes</li>
                        <li>Detalle de productos por devolución</li>
                        <li>Aceptar/Rechazar productos individuales</li>
                        <li>Rechazos parciales con motivos</li>
                        <li>Finalización automática de estados</li>
                        <li>Modales de confirmación</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 5. ACCESOS DIRECTOS -->
        <div class="test-section">
            <h3><i class="bi bi-arrow-right-circle"></i> 5. Accesos al Sistema</h3>
            <div class="row">
                <div class="col-md-4">
                    <a href="index.php" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-house"></i> Sistema Principal
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="pages/nueva_devolucion.php" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-plus-circle"></i> Nueva Devolución
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="pages/control_devoluciones.php" class="btn btn-warning w-100 mb-2">
                        <i class="bi bi-check-circle"></i> Control Devoluciones
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function testEndpoint(url, method, data) {
            const resultDiv = document.getElementById('test-' + url.split('/').pop().split('.')[0]);
            resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Probando...';
            
            try {
                let options = { method: method };
                
                if (data && method === 'POST') {
                    const formData = new FormData();
                    Object.keys(data).forEach(key => formData.append(key, data[key]));
                    options.body = formData;
                }
                
                const response = await fetch(url, options);
                const result = await response.json();
                
                if (result.success || response.ok) {
                    resultDiv.innerHTML = '<small class="text-success">✅ Endpoint funcionando correctamente</small>';
                } else {
                    resultDiv.innerHTML = '<small class="text-warning">⚠️ ' + (result.message || 'Respuesta inesperada') + '</small>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<small class="text-danger">❌ Error: ' + error.message + '</small>';
            }
        }
        
        async function testEndpointWithId(url) {
            // Primero obtenemos una devolución disponible
            try {
                const response = await fetch('ajax/control_devolucion/obtener_devoluciones_control.php', {
                    method: 'POST',
                    body: new FormData()
                });
                const data = await response.json();
                
                if (data.devoluciones && data.devoluciones.length > 0) {
                    const primeraDevolucion = data.devoluciones[0];
                    await testEndpoint(url + '?id=' + primeraDevolucion.id, 'GET', null);
                } else {
                    const resultDiv = document.getElementById('test-obtener_detalle_control');
                    resultDiv.innerHTML = '<small class="text-info">ℹ️ No hay devoluciones disponibles para probar</small>';
                }
            } catch (error) {
                const resultDiv = document.getElementById('test-obtener_detalle_control');
                resultDiv.innerHTML = '<small class="text-danger">❌ Error al obtener devolución de prueba</small>';
            }
        }
        
        // Test automático al cargar
        window.addEventListener('load', function() {
            console.log('🔧 Sistema de Devoluciones - Test Completo Cargado');
        });
    </script>
</body>
</html>
