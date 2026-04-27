# Sistema de Devoluciones - Versión Final

Sistema completo para gestión de devoluciones con funcionalidades de creación, control y procesamiento. Nombre loco

## 🚀 Estructura del Proyecto

```
htdocs/
├── index.php                          # Página principal del sistema
├── login.php                          # Sistema de autenticación
├── cerrar_sesion.php                  # Logout
├── test_sistema_completo.php          # 🔧 ARCHIVO DE TEST PRINCIPAL
├── verificar_sistema.php              # Verificación del estado del sistema
│
├── administrador/                     # Configuración y conexiones
│   ├── conexion_auto.php             # Conexión automática a BD (Docker/Local)
│   ├── conexion_docker.php           # Conexión específica para Docker
│   ├── conexion.php                  # Conexión manual
│   ├── controlador.php               # Controlador principal
│   ├── agregar_distribuidor.php      # Gestión de distribuidores
│   └── logout_on_close.php           # Logout automático
│
├── ajax/                              # Endpoints del sistema
│   ├── obtener_devolucion.php        # Genera números de devolución
│   ├── obtener_productos.php         # Búsqueda de productos
│   ├── guardar_devolucion.php        # Guarda nuevas devoluciones
│   ├── obtener_historial.php         # Historial de devoluciones
│   ├── exportar_devolucion.php       # Exportación de datos
│   ├── verificar_productos.php       # Verificación de productos
│   └── control_devolucion/           # Endpoints para control
│       ├── obtener_devoluciones_control.php     # Lista devoluciones
│       ├── obtener_detalle_control.php          # Detalle de devolución
│       ├── obtener_detalle_control_simple.php   # Versión simplificada
│       ├── obtener_motivos_rechazo.php          # Catálogo de motivos
│       ├── update_product_status.php            # Actualizar estados
│       ├── delete_decision.php                  # Eliminar decisiones
│       └── finalizar_devolucion.php             # Finalizar devolución
│
├── pages/                             # Páginas del sistema
│   ├── inicio.php                    # Dashboard principal
│   ├── nueva_devolucion.php          # Formulario nueva devolución
│   ├── control_devoluciones.php      # Control y procesamiento
│   ├── historial.php                 # Historial de devoluciones
│   ├── informes.php                  # Informes y reportes
│   ├── ver_devolucion.php            # Vista detalle de devolución
│   └── distribuidores.php            # Gestión de distribuidores
│
├── scripts/                           # JavaScript del sistema
│   ├── codes.js                      # Controlador principal JS
│   ├── nueva_devolucion.js           # Funcionalidad nueva devolución
│   ├── control_devoluciones_simple.js # Control con modales elegantes
│   ├── historial.js                  # Funcionalidad historial
│   └── modal_distribuidor.js         # Gestión de distribuidores
│
├── css/
│   └── style.css                     # Estilos del sistema
│
├── database/
│   └── init.sql                      # Script inicial de BD
│
└── Docker/
    ├── docker-compose.yml            # Configuración Docker
    ├── Dockerfile                    # Imagen PHP-Apache
    └── README_DOCKER.md              # Instrucciones Docker
```

## 🎯 Funcionalidades Principales

### ✅ Nueva Devolución
- Selección de distribuidor con autocompletado
- Generación automática de números únicos
- Búsqueda inteligente de productos
- Validaciones completas de datos
- Modales de confirmación elegantes
- Guardado transaccional seguro

### ✅ Control de Devoluciones
- Lista de devoluciones pendientes
- Vista detallada de productos
- Aceptar/Rechazar productos individualmente
- Rechazos parciales con motivos específicos
- Sistema de observaciones
- Finalización automática de estados
- Registro automático de cantidades restantes

### ✅ Características Técnicas
- **Base de Datos:** MySQL 8.0 con transacciones
- **Backend:** PHP 8.1 con validaciones robustas
- **Frontend:** Bootstrap 5 + JavaScript ES6
- **Docker:** Configuración completa lista para producción
- **Modales:** Sistema elegante de confirmaciones
- **Responsive:** Adaptable a todos los dispositivos

## 🔧 Archivo de Test

El archivo `test_sistema_completo.php` permite verificar:

1. **Conexión a Base de Datos**
2. **Estructura de Tablas Completa**
3. **Funcionamiento de Endpoints**
4. **Accesos Directos al Sistema**
5. **Tests Interactivos en Tiempo Real**

### Para usar el test:
```bash
http://localhost/test_sistema_completo.php
```

## 🚀 Instalación Rápida

### Con Docker (Recomendado):
```bash
cd htdocs
docker-compose up -d
```

### Manual:
1. Importar `database/init.sql` en MySQL
2. Configurar conexión en `administrador/conexion.php`
3. Configurar servidor web apuntando a `htdocs/`

## 📋 Estados del Sistema

- **Estado 1:** Pendiente (Nueva)
- **Estado 5:** Aprobada (Completa)
- **Estado 6:** Rechazada (Completa)  
- **Estado 8:** Rechazada Parcialmente

## 🎨 Tecnologías

- **PHP 8.1+**
- **MySQL 8.0+**
- **Bootstrap 5.0.2**
- **Bootstrap Icons 1.13.1**
- **JavaScript ES6 Modules**
- **Docker & Docker Compose**

## 👨‍💻 Desarrollo

Sistema desarrollado con arquitectura modular, separación de responsabilidades y mejores prácticas de desarrollo web.

**Última actualización:** Diciembre 2024
**Versión:** Final - Lista para Producción