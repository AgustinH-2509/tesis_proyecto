<?php
// Iniciar sesión para obtener información del usuario
session_start();

// Manejo de errores robusto
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en pantalla para mantener JSON limpio

// Función para log de errores con debugging (reducido)
$debug_steps = [];
function logError($message, $data = null) {
    global $debug_steps;
    $debug_steps[] = [
        'time' => date('Y-m-d H:i:s'),
        'message' => $message,
        'data' => $data
    ];
    // Solo log críticos en archivo
    if (strpos(strtolower($message), 'error') !== false || strpos(strtolower($message), 'exception') !== false) {
        $logFile = __DIR__ . '/../temp/guardar_error.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logEntry = date('Y-m-d H:i:s') . " - " . $message;
        if ($data !== null) {
            $logEntry .= " - Data: " . substr(print_r($data, true), 0, 200);
        }
        error_log($logEntry . "\n", 3, $logFile);
    }
}

// Capturar cualquier salida no deseada
ob_start();

try {
    logError('=== INICIO GUARDAR DEVOLUCION ===');
    include '../administrador/conexion_auto.php';
    logError('Conexion.php incluido exitosamente');
} catch (Exception $e) {
    ob_clean();
    logError("Error en conexión: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// Limpiar cualquier salida previa
$output = ob_get_clean();
if (!empty($output)) {
    logError("Salida inesperada del include: " . $output);
}

header('Content-Type: application/json');

// Función para crear JSON temporal (método alternativo sin carpeta temp)
function crearJSONTemporal($data) {
    $timestamp = round(microtime(true) * 1000);
    $nombreArchivo = "temp_devolucion_{$timestamp}.json";
    
    // Intentar usar carpeta temp, si no existe usar carpeta actual
    $tempDir = __DIR__ . '/../temp/';
    if (!is_dir($tempDir)) {
        if (!@mkdir($tempDir, 0755, true)) {
            // Si no se puede crear temp, usar directorio actual
            $tempDir = __DIR__ . '/';
            $nombreArchivo = ".temp_devolucion_{$timestamp}.json"; // Archivo oculto
        }
    }
    
    $rutaArchivo = $tempDir . $nombreArchivo;
    
    if (file_put_contents($rutaArchivo, json_encode($data, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('No se pudo crear el archivo JSON temporal');
    }
    
    return $rutaArchivo;
}

// Función para eliminar JSON temporal
function eliminarJSONTemporal($ruta) {
    if (file_exists($ruta)) {
        unlink($ruta);
    }
}

// Función para procesar JSON temporal y guardar en BD
function procesarJSONTemporal($rutaJSON, $conn) {
    $jsonData = json_decode(file_get_contents($rutaJSON), true);
    if (!$jsonData) {
        throw new Exception('Error al leer JSON temporal');
    }
    
    return procesarDevolucionDesdeJSON($jsonData, $conn);
}

// Función para procesar devolución desde estructura JSON estándar
function procesarDevolucionDesdeJSON($jsonData, $conn) {
    $distribuidor_codigo = $jsonData['distribuidor_codigo'];
    $numero_devolucion = $jsonData['numero_devolucion_raw'];
    $fecha_registro = date('Y-m-d', strtotime($jsonData['fecha_registro']));
    $estado = 1;
    $usuario_ingresa = isset($_SESSION['id']) ? $_SESSION['id'] : NULL;
    
    // Insertar encabezado de devolución
    $stmt_encabezado = $conn->prepare("INSERT INTO devoluciones (distribuidor_codigo, distribuidor_numero, fecha_ingresa, estado, usuario_ingresa) VALUES (?, ?, ?, ?, ?)");
    if ($stmt_encabezado === false) {
        throw new Exception("Error en la preparación del encabezado: " . $conn->error);
    }
    $stmt_encabezado->bind_param("sisis", $distribuidor_codigo, $numero_devolucion, $fecha_registro, $estado, $usuario_ingresa);
    if (!$stmt_encabezado->execute()) {
        throw new Exception("Error al ejecutar la inserción del encabezado: " . $stmt_encabezado->error);
    }
    $id_devolucion = $conn->insert_id;
    $stmt_encabezado->close();

    // Preparar inserción de detalles
    $stmt_detalle = $conn->prepare("INSERT INTO devoluciones_detalle (devolucion, producto_cod, cantidad, kg, motivos_devolucion, vencimiento, observaciones, rechazo) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
    if ($stmt_detalle === false) {
        throw new Exception("Error en la preparación del detalle: " . $conn->error);
    }

    foreach ($jsonData['datos'] as $producto) {
        // CORREGIDO: Usar 'id' que contiene el ID único, no 'columna_1' que contiene código
        $prod_id = $producto['id'] ?? $producto['columna_1'];
        $cantidad = $producto['columna_2'] ?? 0;
        $kg = $producto['columna_3'] ?? '';
        $motivo = $producto['motivoId'] ?? '';
        $vencimiento_raw = $producto['columna_5'] ?? '';
        $observaciones = $producto['observaciones'] ?? '';

        // Convertir fecha de vencimiento
        $fecha_venc_db = null;
        if (!empty($vencimiento_raw)) {
            $d = DateTime::createFromFormat('d/m/Y', $vencimiento_raw);
            if ($d !== false) {
                $fecha_venc_db = $d->format('Y-m-d');
            } else {
                $fecha_venc_db = date('Y-m-d', strtotime($vencimiento_raw));
            }
        }

        // Obtener motivo ID
        $motivo_id_db = null;
        if ($motivo !== '' && $motivo !== null) {
            if (is_numeric($motivo)) {
                $motivo_id_db = (int)$motivo;
            } else {
                // Buscar por nombre en devoluciones_motivos
                $stmt_mot = $conn->prepare("SELECT id FROM devoluciones_motivos WHERE motivos = ? LIMIT 1");
                if ($stmt_mot) {
                    $stmt_mot->bind_param('s', $motivo);
                    $stmt_mot->execute();
                    $res_m = $stmt_mot->get_result()->fetch_assoc();
                    $stmt_mot->close();
                    if ($res_m && isset($res_m['id'])) {
                        $motivo_id_db = (int)$res_m['id'];
                    }
                }
            }
        }

        $stmt_detalle->bind_param(
            "iisisssi",
            $id_devolucion,
            $prod_id,
            $cantidad,
            $kg,
            $motivo_id_db,
            $fecha_venc_db,
            $observaciones
        );

        if (!$stmt_detalle->execute()) {
            throw new Exception("Error al insertar el detalle: " . $stmt_detalle->error);
        }
    }
    $stmt_detalle->close();
    
    return $id_devolucion;
}

$response = ['success' => false, 'message' => 'Error al procesar la solicitud.'];

try {
    logError('Leyendo datos de entrada');
    
    // Manejar diferentes formas de recibir los datos
    $input_raw = '';
    $data = null;
    
    // Opción 1: Datos enviados como JSON directo (sistema real con fetch)
    $php_input = file_get_contents('php://input');
    if (!empty($php_input)) {
        $input_raw = $php_input;
        logError('Datos recibidos vía php://input (JSON directo), longitud: ' . strlen($input_raw));
        
        $data = json_decode($input_raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            logError('JSON directo parseado exitosamente');
        } else {
            logError('Error parseando JSON directo: ' . json_last_error_msg());
            $data = null;
        }
    }
    
    // Opción 2: Datos enviados como POST tablaData (tests con jQuery)
    if ($data === null && isset($_POST['tablaData'])) {
        $input_raw = $_POST['tablaData'];
        logError('Datos recibidos vía POST[tablaData], longitud: ' . strlen($input_raw));
        
        $data = json_decode($input_raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            logError('JSON de tablaData parseado exitosamente');
        } else {
            logError('Error parseando JSON de tablaData: ' . json_last_error_msg());
        }
    }
    
    // Verificar que se obtuvieron datos válidos
    if ($data === null) {
        $preview = substr($input_raw, 0, 200);
        throw new Exception('No se pudieron decodificar los datos JSON. Recibido: ' . $preview);
    }
    
    if (!is_array($data)) {
        throw new Exception('Los datos recibidos no son un array válido.');
    }
    
    logError('JSON decodificado exitosamente', [
        'keys' => array_keys($data),
        'has_datos' => isset($data['datos']),
        'has_datos_tabla' => isset($data['datos_tabla']),
        'datos_count' => is_array($data['datos'] ?? null) ? count($data['datos']) : 'no-array',
        'datos_tabla_count' => is_array($data['datos_tabla'] ?? null) ? count($data['datos_tabla']) : 'no-array',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'no-content-type',
        'estructura_detectada' => isset($data['datos_tabla']) ? 'sistema_real' : (isset($data['datos']) ? 'sistema_test' : 'desconocida')
    ]);

    // Extraer datos del JSON recibido - manejar ambas estructuras
    $distribuidor_codigo = $data['distribuidor_codigo'] ?? null;
    $numero_devolucion_raw = $data['numero_devolucion_raw'] ?? null;
    
    // El sistema real puede enviar 'datos_tabla' o 'datos'
    $productos_datos = null;
    if (isset($data['datos_tabla']) && is_array($data['datos_tabla'])) {
        $productos_datos = $data['datos_tabla']; // Sistema real
        logError('Usando datos_tabla del sistema real');
    } elseif (isset($data['datos']) && is_array($data['datos'])) {
        $productos_datos = $data['datos']; // Sistema de test
        logError('Usando datos del sistema de test');
    }
    
    $tipo = $data['tipo'] ?? 'devolucion';
    
    logError('Datos extraídos del JSON frontend', [
        'distribuidor_codigo' => $distribuidor_codigo,
        'numero_devolucion_raw' => $numero_devolucion_raw,
        'productos_datos_type' => gettype($productos_datos),
        'productos_count' => is_array($productos_datos) ? count($productos_datos) : 'not_array',
        'productos_sample' => is_array($productos_datos) && count($productos_datos) > 0 ? $productos_datos[0] : null,
        'tipo' => $tipo
    ]);

    if (empty($distribuidor_codigo)) {
        logError('ERROR: Código de distribuidor vacío', ['received' => $distribuidor_codigo]);
        throw new Exception('Código de distribuidor requerido.');
    }
    if (empty($numero_devolucion_raw)) {
        logError('ERROR: Número de devolución vacío', ['received' => $numero_devolucion_raw]);
        throw new Exception('Número de devolución requerido.');
    }
    if (!is_array($productos_datos)) {
        logError('ERROR: productos_datos no es array', [
            'type' => gettype($productos_datos),
            'value' => $productos_datos,
            'available_keys' => array_keys($data)
        ]);
        throw new Exception('Lista de productos debe ser un array.');
    }
    if (count($productos_datos) === 0) {
        logError('ERROR: productos_datos está vacío', ['count' => count($productos_datos)]);
        throw new Exception('Lista de productos requerida y no puede estar vacía.');
    }

    // Verificar conexión a BD
    logError('Verificando conexión BD');
    if (isset($connection_failed) && $connection_failed) {
        throw new Exception('Error de conexión: flag de conexión fallida activado');
    }
    if (!isset($conn)) {
        throw new Exception('Variable conn no definida');
    }
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    logError('Conexión BD verificada exitosamente');

    // Verificar que no estamos ya en una transacción
    if ($conn->autocommit) {
        logError('Iniciando transacción');
        $conn->begin_transaction();
    } else {
        logError('Ya en transacción, continuando...');
    }
    
    // Procesar directamente desde el JSON del frontend
    $fecha_registro = date('Y-m-d');
    $estado = 1;
    $usuario_ingresa = isset($_SESSION['id']) ? $_SESSION['id'] : NULL;
    
    // Insertar encabezado de devolución
    $stmt_encabezado = $conn->prepare("INSERT INTO devoluciones (distribuidor_codigo, distribuidor_numero, fecha_ingresa, estado, usuario_ingresa) VALUES (?, ?, ?, ?, ?)");
    if ($stmt_encabezado === false) {
        throw new Exception("Error en la preparación del encabezado: " . $conn->error);
    }
    $stmt_encabezado->bind_param("sisis", $distribuidor_codigo, $numero_devolucion_raw, $fecha_registro, $estado, $usuario_ingresa);
    if (!$stmt_encabezado->execute()) {
        throw new Exception("Error al ejecutar la inserción del encabezado: " . $stmt_encabezado->error);
    }
    $id_devolucion = $conn->insert_id;
    $stmt_encabezado->close();
    
    logError('Encabezado insertado exitosamente', ['id_devolucion' => $id_devolucion]);

    // Preparar inserción de detalles
    $stmt_detalle = $conn->prepare("INSERT INTO devoluciones_detalle (devolucion, producto_cod, cantidad, kg, motivos_devolucion, vencimiento, observaciones, rechazo) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
    if ($stmt_detalle === false) {
        throw new Exception("Error en la preparación del detalle: " . $conn->error);
    }

    foreach ($productos_datos as $producto) {
        // CORREGIDO: Usar 'id' que contiene el ID único, no 'columna_1' que contiene código
        $prod_id = $producto['id'] ?? $producto['columna_1'];
        $cantidad = $producto['columna_2'] ?? 0;
        $kg = $producto['columna_3'] ?? '';
        $motivo = $producto['motivoId'] ?? '';
        $vencimiento_raw = $producto['columna_5'] ?? '';
        $observaciones = $producto['observaciones'] ?? '';

        // VALIDAR QUE EL PRODUCTO EXISTE
        $stmt_check = $conn->prepare("SELECT iD FROM productos WHERE iD = ? LIMIT 1");
        if (!$stmt_check) {
            throw new Exception("Error preparando validación de producto: " . $conn->error);
        }
        $stmt_check->bind_param('i', $prod_id);
        $stmt_check->execute();
        $producto_existe = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        
        if (!$producto_existe) {
            throw new Exception("El producto con ID {$prod_id} no existe en la base de datos.");
        }
        
        logError('Producto validado', ['prod_id' => $prod_id, 'existe' => !!$producto_existe]);

        // Convertir fecha de vencimiento
        $fecha_venc_db = null;
        if (!empty($vencimiento_raw)) {
            $d = DateTime::createFromFormat('d/m/Y', $vencimiento_raw);
            if ($d !== false) {
                $fecha_venc_db = $d->format('Y-m-d');
            } else {
                $fecha_venc_db = date('Y-m-d', strtotime($vencimiento_raw));
            }
        }

        // Obtener motivo ID
        $motivo_id_db = null;
        if ($motivo !== '' && $motivo !== null) {
            if (is_numeric($motivo)) {
                $motivo_id_db = (int)$motivo;
            } else {
                // Buscar por nombre en devoluciones_motivos
                $stmt_mot = $conn->prepare("SELECT id FROM devoluciones_motivos WHERE motivos = ? LIMIT 1");
                if ($stmt_mot) {
                    $stmt_mot->bind_param('s', $motivo);
                    $stmt_mot->execute();
                    $res_m = $stmt_mot->get_result()->fetch_assoc();
                    $stmt_mot->close();
                    if ($res_m && isset($res_m['id'])) {
                        $motivo_id_db = (int)$res_m['id'];
                    }
                }
            }
        }

        // Asegurar tipos correctos para la BD
        $id_devolucion = (int)$id_devolucion;
        $prod_id = (int)$prod_id;
        $cantidad = (int)$cantidad;
        $kg = is_numeric($kg) ? (float)$kg : 0;
        $motivo_id_db = $motivo_id_db !== null ? (int)$motivo_id_db : null;

        logError('Preparando inserción de detalle', [
            'id_devolucion' => $id_devolucion,
            'prod_id' => $prod_id,
            'cantidad' => $cantidad,
            'kg' => $kg,
            'motivo_id_db' => $motivo_id_db,
            'fecha_venc_db' => $fecha_venc_db,
            'observaciones' => $observaciones,
            'tipos' => [
                'id_devolucion' => gettype($id_devolucion),
                'prod_id' => gettype($prod_id),
                'cantidad' => gettype($cantidad),
                'kg' => gettype($kg),
                'motivo_id_db' => gettype($motivo_id_db),
                'fecha_venc_db' => gettype($fecha_venc_db),
                'observaciones' => gettype($observaciones)
            ]
        ]);

        $stmt_detalle->bind_param(
            "iiidiss",  // Tipos: int,int,int,decimal,int,string,string
            $id_devolucion,
            $prod_id,
            $cantidad,
            $kg,
            $motivo_id_db,
            $fecha_venc_db,
            $observaciones
        );

        if (!$stmt_detalle->execute()) {
            throw new Exception("Error al insertar el detalle: " . $stmt_detalle->error);
        }
    }
    $stmt_detalle->close();
    
    logError('Todos los detalles insertados exitosamente');
    
    // Confirmar transacción
    $conn->commit();
    logError('Transacción confirmada exitosamente');
    $response = [
        'success' => true,
        'message' => 'Devolución guardada correctamente.',
        'id_devolucion' => $id_devolucion,
        'debug_steps' => $debug_steps
    ];

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conn)) {
        $conn->rollback();
        logError('Rollback ejecutado');
    }
    
    logError('ERROR PRINCIPAL', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => substr($e->getTraceAsString(), 0, 500)
    ]);
    
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_details' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'trace_preview' => substr($e->getTraceAsString(), 0, 200)
        ],
        'debug_steps' => $debug_steps
    ];
}

// Cerrar conexión si está disponible
if (isset($conn)) {
    $conn->close();
}

// Headers de debug para console
header('X-Debug-Steps: ' . count($debug_steps));
header('X-Debug-Last: ' . end($debug_steps)['message']);

echo json_encode($response);
?>