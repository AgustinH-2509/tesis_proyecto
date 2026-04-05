# 🐳 Sistema de Devoluciones - Docker

Este proyecto incluye configuración completa de Docker para ejecutar el sistema de devoluciones.

## 📋 Requisitos

- Docker Desktop instalado
- Docker Compose

## 🚀 Instrucciones de Uso

### 1. Construir y ejecutar los contenedores

```bash
# Ir al directorio del proyecto
cd c:\Users\Hernandeza\Documents\GitHub\htdocs

# Construir y ejecutar
docker-compose up -d --build
```

### 2. Acceder a la aplicación

- **Aplicación principal**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081
- **Base de datos**: localhost:3307

### 3. Credenciales

**Aplicación:**
- Usuario: `admin`
- Contraseña: `admin123`

**Base de datos:**
- Host: `db` (interno) / `localhost:3307` (externo)
- Usuario: `root`
- Contraseña: `root123`
- Base de datos: `sistema_devoluciones`

**PHPMyAdmin:**
- Usuario: `root`
- Contraseña: `root123`

## 📁 Estructura de Contenedores

- **web**: Servidor Apache con PHP 8.1
- **db**: MySQL 8.0 con datos inicializados
- **phpmyadmin**: Interfaz web para gestión de BD

## 🔧 Comandos Útiles

```bash
# Ver logs
docker-compose logs -f

# Detener contenedores
docker-compose down

# Reiniciar contenedores
docker-compose restart

# Acceder al contenedor web
docker exec -it sistema_devoluciones_web bash

# Acceder al contenedor de base de datos
docker exec -it sistema_devoluciones_db mysql -u root -p
```

## 🗄️ Base de Datos

La base de datos se inicializa automáticamente con:
- Tablas necesarias para el sistema
- Datos de ejemplo (productos, distribuidores, motivos)
- Usuario específico para la aplicación

## 🔄 Actualizar Configuración

Para usar Docker en lugar de tu servidor local, cambia en tus archivos PHP:

```php
// Cambiar de:
include 'administrador/conexion.php';

// A:
include 'administrador/conexion_docker.php';
```

## 🐛 Solución de Problemas

**Puerto ocupado:**
```bash
# Cambiar puerto en docker-compose.yml
ports:
  - "8080:80"  # Cambiar 8080 por otro puerto
```

**Problemas de permisos:**
```bash
# Reconstruir contenedores
docker-compose down
docker-compose up -d --build
```