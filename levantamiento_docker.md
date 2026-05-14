# 🐳 Levantamiento de Arludent en Docker — Paso a Paso

> **Fecha:** 2026-05-13  
> **Sistema:** Arludent — Sistema de Gestión Odontológica  
> **Entorno:** Windows con Docker Desktop (Docker 29.3.1, Compose v5.1.0)

---

## 📋 Resumen de la Arquitectura

El sistema se compone de **4 servicios** orquestados con Docker Compose:

| Servicio | Imagen | Puerto | Descripción |
|---|---|---|---|
| **db** | `mysql:8.0` | `:3308 → 3306` | Base de datos MySQL |
| **backend** | `arludent-backend` (custom) | `:8080 → 80` | Laravel 12 + PHP 8.4 + Nginx + Supervisor |
| **frontend** | `arludent-frontend` (custom) | `:3000 → 80` | Vue 3 + Vite + Nginx |
| **agent** | `arludent-agent` (custom) | `:8001 → 8001` | Python 3.12 + FastAPI (Agente IA) |

---

## 🔧 Paso 1: Verificar Prerrequisitos

Se verificó que Docker y Docker Compose estén instalados:

```powershell
docker --version
# Docker version 29.3.1, build c2be9cc

docker-compose --version
# Docker Compose version v5.1.0
```

✅ **Ambas herramientas disponibles.**

---

## 📄 Paso 2: Crear Archivo `.env`

Se copió el template de variables de entorno:

```powershell
Copy-Item .env.docker .env
```

El archivo `.env.docker` contiene las configuraciones predefinidas:

| Variable | Valor |
|---|---|
| `FRONTEND_PORT` | `3000` |
| `BACKEND_PORT` | `8080` |
| `AGENT_PORT` | `8001` |
| `DB_PORT_EXTERNAL` | `3308` |
| `DB_DATABASE` | `consultorio` |
| `DB_PASSWORD` | `arludent2024` |
| `APP_KEY` | `base64:Iqnmq/...` (preconfigurada) |
| `MAIL_USERNAME` | `erickhc1331@gmail.com` |

---

## 🚀 Paso 3: Ejecutar `docker-compose up -d --build`

```powershell
docker-compose up -d --build
```

Este comando realiza las siguientes acciones:

1. **Descarga imágenes base**: `mysql:8.0`, `php:8.4-fpm`, `node:22-alpine`, `nginx:alpine`, `python:3.12-slim`, `composer:2`
2. **Construye 3 imágenes custom** (backend, frontend, agent)
3. **Crea la red** `arludent-network` (bridge)
4. **Crea volúmenes** `mysql_data` y `backend_storage`
5. **Inicia los contenedores** en el orden correcto según `depends_on`

---

## ✅ Paso 4: Verificación Final

Después de aplicar las correcciones, todos los servicios levantaron correctamente:

```
NAME                IMAGE               STATUS                 PORTS
arludent-agent      arludent-agent      Up (health: starting)  0.0.0.0:8001→8001/tcp
arludent-backend    arludent-backend    Up                     0.0.0.0:8080→80/tcp
arludent-db         mysql:8.0           Up (healthy)           0.0.0.0:3308→3306/tcp
arludent-frontend   arludent-frontend   Up                     0.0.0.0:3000→80/tcp
```

### Logs del Backend (Inicialización exitosa):

```
============================================
  Arludent Backend - Inicialización Docker
============================================
[1/6] Esperando a que MySQL esté disponible...
  ✓ MySQL está disponible.
[2/6] Verificando APP_KEY...
  ✓ APP_KEY ya configurada.
[3/6] Ejecutando migraciones...
  ✓ Migraciones completadas.
[4/6] Verificando seeders...
  Base de datos vacía, ejecutando seeders...
  ✓ Seeders completados.
[5/6] Optimizando caché...
  ✓ Caché optimizada.
[6/6] Configurando permisos...
  ✓ Permisos configurados.

============================================
  ✓ Backend listo. Iniciando servicios...
============================================

INFO spawned: 'nginx' with pid 29
INFO spawned: 'php-fpm' with pid 30
INFO spawned: 'queue-worker' with pid 31
INFO success: nginx entered RUNNING state
INFO success: php-fpm entered RUNNING state
INFO success: queue-worker entered RUNNING state
```

### Logs del Agente IA:

```
🚀 Arludent AI Microservice iniciando...
📍 Entorno: production
🤖 Modelo: gpt-4o-mini
Application startup complete.
Uvicorn running on http://0.0.0.0:8001
```

### Logs del Frontend:

```
Nginx workers activos (10 workers)
Escuchando en puerto 80 (mapeado a 3000 del host)
```

---

## 🌐 URLs de Acceso

| Servicio | URL |
|---|---|
| **Frontend** | [http://localhost:3000](http://localhost:3000) |
| **Backend API** | [http://localhost:8080](http://localhost:8080) |
| **Agente IA** | [http://localhost:8001](http://localhost:8001) |
| **MySQL** | `localhost:3308` (usuario: `root`, password: `arludent2024`) |

---

##  Comandos Útiles

```bash
# Ver estado de los contenedores
docker-compose ps

# Ver logs en tiempo real
docker-compose logs -f

# Ver logs de un servicio específico
docker-compose logs -f backend

# Parar todo
docker-compose down

# Parar y eliminar volúmenes (¡borra la BD!)
docker-compose down -v

# Reconstruir un servicio específico
docker-compose up -d --build backend
```
