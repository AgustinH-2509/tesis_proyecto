/**
 * DIAGRAMA DE FLUJO - Sistema de JSON Temporal
 * ============================================
 * 
 * NUEVO SISTEMA: JSON Temporal Unificado
 * =======================================
 *
 *  GUARDADO DE DEVOLUCIÓN:
 *  ┌─────────────────────┐
 *  │ Frontend (Tabla)    │
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ Enviar a Backend    │
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ 1. Crear JSON       │
 *  │    Temporal         │
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ 2. Procesar JSON    │
 *  │    → Guardar en BD  │
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ 3. Eliminar JSON    │
 *  │    Temporal         │
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ 4. Respuesta Éxito  │
 *  │    (Modal Bootstrap)│
 *  └─────────────────────┘
 *
 *
 *  OBTENER DETALLE DE DEVOLUCIÓN:
 *  ┌─────────────────────┐
 *  │ Solicitar Detalle   │
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ 1. Consultar BD     │
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ 2. Crear JSON       │
 *  │    Temporal desde BD│
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ 3. Procesar JSON    │
 *  │    → Formato Estándar│
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ 4. Eliminar JSON    │
 *  │    Temporal         │
 *  └─────────┬───────────┘
 *            ↓
 *  ┌─────────────────────┐
 *  │ 5. Enviar Respuesta │
 *  │    al Frontend      │
 *  └─────────────────────┘
 *
 *
 * ESTRUCTURA DE ARCHIVOS SIMPLIFICADA
 * ====================================
 *
 * htdocs/
 * ├── temp/                    ← Archivos temporales
 * │   ├── temp_devolucion_*.json (se eliminan automáticamente)
 * │   ├── temp_detalle_*.json   (se eliminan automáticamente)
 * │   ├── debug_detalle.log     (logs temporales)
 * │   └── debug_error.log       (logs de errores)
 * ├── administrador/
 * │   ├── conexion.php          ← Mejorado (sin die() en AJAX)
 * │   ├── controlador.php
 * │   ├── agregar_distribuidor.php
 * │   └── logout_on_close.php
 * ├── ajax/
 * │   ├── control_devolucion/
 * │   │   ├── obtener_detalle_control.php  ← JSON temporal
 * │   │   ├── obtener_devoluciones_control.php
 * │   │   ├── obtener_motivos_rechazo.php
 * │   │   ├── finalizar_devolucion.php
 * │   │   └── update_product_status.php
 * │   ├── guardar_devolucion.php           ← JSON temporal
 * │   ├── guardar_tabla_json.php
 * │   ├── exportar_devolucion.php
 * │   ├── obtener_devolucion.php
 * │   └── obtener_historial.php
 * ├── css/
 * │   └── style.css
 * ├── pages/
 * │   ├── nueva_devolucion.php             ← Modal de éxito
 * │   ├── control_devoluciones.php
 * │   ├── distribuidores.php
 * │   ├── historial.php
 * │   ├── informes.php
 * │   ├── inicio.php
 * │   └── ver_devolucion.php
 * ├── scripts/
 * │   ├── nueva_devolucion.js              ← Modal Bootstrap
 * │   ├── control_devoluciones.js
 * │   ├── guardar_tabla_json.js
 * │   ├── historial.js
 * │   ├── modal_distribuidor.js
 * │   └── codes.js
 * ├── cerrar_sesion.php
 * ├── index.php
 * └── login.php
 *
 *
 * VENTAJAS DEL NUEVO SISTEMA
 * ==========================
 *
 * 1. CONSISTENCIA TOTAL:
 *    ✓ Mismo formato JSON para guardado y lectura
 *    ✓ Una sola lógica de procesamiento
 *    ✓ Frontend siempre recibe estructura uniforme
 *
 * 2. LIMPIEZA AUTOMÁTICA:
 *    ✓ No se acumulan archivos JSON permanentes
 *    ✓ Archivos temporales se eliminan automáticamente
 *    ✓ Solo la BD contiene datos persistentes
 *
 * 3. MEJOR UX:
 *    ✓ Modal elegante en lugar de alert() básico
 *    ✓ Respuestas JSON siempre válidas
 *    ✓ Manejo robusto de errores
 *
 * 4. MANTENIBILIDAD:
 *    ✓ Código unificado y reutilizable
 *    ✓ Estructura de proyecto simplificada
 *    ✓ Fácil debugging con logs temporales
 *
 *
 * FLUJO TÉCNICO DETALLADO
 * ========================
 *
 * GUARDADO:
 * Frontend → POST → guardar_devolucion.php:
 *   1. Recibe datos del frontend
 *   2. Convierte a estructura JSON estándar
 *   3. Crea archivo temp/temp_devolucion_[timestamp].json
 *   4. Lee JSON temporal → INSERT en BD
 *   5. Elimina archivo temporal
 *   6. Retorna JSON success/error
 *   7. Frontend muestra modal de éxito
 *
 * LECTURA:
 * Frontend → GET → obtener_detalle_control.php:
 *   1. Recibe ID de devolución
 *   2. SELECT desde BD
 *   3. Convierte datos BD → JSON estándar temporal
 *   4. Procesa JSON temporal → formato frontend
 *   5. Elimina archivo temporal
 *   6. Retorna datos formateados
 *   7. Frontend muestra detalle
 *
 *
 * ESTRUCTURA JSON ESTÁNDAR TEMPORAL
 * ==================================
 *
 * {
 *   "fecha_registro": "2025-11-28T09:30:00.000Z",
 *   "timestamp": 1732789800000,
 *   "total_filas": 2,
 *   "datos": [
 *     {
 *       "numero_fila": 1,
 *       "columna_1": "123",        // ID/Código producto
 *       "columna_2": "10",         // Cantidad
 *       "columna_3": "2.5",        // KG
 *       "columna_4": "Normal",     // Estado
 *       "columna_5": "25/12/2025", // Vencimiento
 *       "columna_6": "",           // Campo extra
 *       "observaciones": "Texto",
 *       "id": "123",
 *       "motivoId": "4"
 *     }
 *   ],
 *   "tipo": "devolucion",
 *   "distribuidor_codigo": "7095",
 *   "numero_devolucion_raw": 1,
 *   "numero_devolucion": "7095-001"
 * }
 *
 *
 * ELIMINACIONES REALIZADAS
 * =========================
 *
 * ✅ Carpeta ajax/tests/ (completa)
 * ✅ Archivos de prueba y backup:
 *    - ajax/control_devolucion/prueba
 *    - ajax/control_devolucion/test_simple.php
 *    - ajax/control_devolucion/obtener_detalle_control_backup.php
 *    - ajax/control_devolucion/obtener_detalle_control_standalone.php
 *    - ajax/control_devolucion/conexion_segura.php
 * ✅ Carpeta registros_json/ (completa)
 *    - Archivos JSON históricos ya no necesarios
 *    - Sistema ahora usa solo JSON temporal
 *
 */
