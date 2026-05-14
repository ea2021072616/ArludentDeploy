# Manual de Despliegue Local — Arludent

**Sistema de Gestión Odontológica**  
**Versión:** 1.0  
**Última actualización:** Mayo 2026  
**Destinado a:** Equipo de desarrollo

---

## Tabla de Contenidos

1. [Prerrequisitos](#1-prerrequisitos)
2. [Estructura del Proyecto](#2-estructura-del-proyecto)
3. [Arquitectura de los Servicios](#3-arquitectura-de-los-servicios)
4. [Pasos para el Despliegue](#4-pasos-para-el-despliegue)
   - 4.1 [Obtener el código fuente](#41-obtener-el-código-fuente)
   - 4.2 [Configurar las variables de entorno](#42-configurar-las-variables-de-entorno)
   - 4.3 [Construir y levantar los servicios](#43-construir-y-levantar-los-servicios)
   - 4.4 [Verificar que todo funciona](#44-verificar-que-todo-funciona)
5. [Acceso al Sistema](#5-acceso-al-sistema)
6. [¿Qué ocurre internamente al levantar?](#6-qué-ocurre-internamente-al-levantar)
7. [Comandos Útiles del Día a Día](#7-comandos-útiles-del-día-a-día)
8. [Solución de Problemas Frecuentes](#8-solución-de-problemas-frecuentes)
9. [Detener y Limpiar el Entorno](#9-detener-y-limpiar-el-entorno)

---

## 1. Prerrequisitos

Antes de comenzar, asegúrate de tener instalado **únicamente** lo siguiente:

| Herramienta | Versión Mínima | Descarga |
|---|---|---|
| **Docker Desktop** | 4.x o superior | [https://www.docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop) |

> **Nota:** Docker Desktop para Windows ya incluye **Docker Engine** y **Docker Compose**. No necesitas instalar nada más (ni PHP, ni Node.js, ni MySQL, ni Python). Todo se ejecuta dentro de contenedores.

### Verificar la instalación

Abre una terminal (PowerShell o CMD) y ejecuta:

```powershell
docker --version
```

Deberías ver algo como:

```
Docker version 29.x.x, build xxxxxxx
```

Luego verifica Docker Compose:

```powershell
docker compose version
```

Deberías ver algo como:

```
Docker Compose version v5.x.x
```

> **Importante:** Asegúrate de que Docker Desktop esté **abierto y ejecutándose** (ícono de la ballena en la barra de tareas) antes de continuar.

---

## 2. Estructura del Proyecto

El proyecto tiene la siguiente estructura de carpetas:

```
Arludent/                          ← Raíz del proyecto
├── docker-compose.yml             ← Orquestador de los 4 servicios
├── .env.docker                    ← Template de variables de entorno
├── .env                           ← Variables de entorno activas (lo crearás tú)
│
├── backend-arludent/              ← Backend (Laravel 12 + PHP 8.4)
│   ├── Dockerfile                 ← Imagen: PHP-FPM + Nginx + Supervisor
│   ├── docker/
│   │   ├── nginx/default.conf     ← Configuración del servidor web
│   │   ├── supervisor/supervisord.conf  ← Gestión de procesos
│   │   └── entrypoint.sh          ← Script de inicialización automática
│   ├── app/                       ← Código fuente Laravel
│   ├── composer.json
│   └── ...
│
├── arludent-frontend/             ← Frontend (Vue 3 + Vite)
│   ├── Dockerfile                 ← Imagen: Node build + Nginx Alpine
│   ├── docker/
│   │   └── nginx/default.conf     ← SPA fallback + proxy API
│   ├── src/                       ← Código fuente Vue
│   ├── package.json
│   └── ...
│
└── agenteIA-arludent/             ← Agente de IA (Python + FastAPI)
    ├── Dockerfile                 ← Imagen: Python 3.12 slim
    ├── main.py
    ├── requirements.txt
    └── ...
```

---

## 3. Arquitectura de los Servicios

El sistema se despliega como **4 contenedores Docker** que se comunican entre sí a través de una red interna:

```
┌─────────────────────────────────────────────────────────────┐
│                    Docker Network (arludent-network)         │
│                                                             │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐  │
│  │   Frontend   │    │   Backend    │    │  Agente IA   │  │
│  │  Vue 3 +     │───▶│  Laravel +   │◀───│  Python +    │  │
│  │  Nginx       │    │  PHP-FPM +   │    │  FastAPI     │  │
│  │              │    │  Nginx       │    │              │  │
│  │  Puerto:3000 │    │  Puerto:8080 │    │  Puerto:8001 │  │
│  └──────────────┘    └──────┬───────┘    └──────────────┘  │
│                             │                               │
│                      ┌──────▼───────┐                       │
│                      │   MySQL 8.0  │                       │
│                      │              │                       │
│                      │  Puerto:3307 │                       │
│                      └──────────────┘                       │
└─────────────────────────────────────────────────────────────┘

         El usuario accede desde: http://localhost:3000
```

| Servicio | Tecnología | Puerto Local | Función |
|---|---|---|---|
| **db** | MySQL 8.0 | `3307` | Base de datos relacional |
| **backend** | PHP 8.4 + Laravel 12 + Nginx | `8080` | API REST, lógica de negocio |
| **frontend** | Vue 3 + Vite + Nginx | `3000` | Interfaz de usuario (SPA) |
| **agent** | Python 3.12 + FastAPI | `8001` | Microservicio de inteligencia artificial |

---

## 4. Pasos para el Despliegue

### 4.1 Obtener el código fuente

Descarga o clona el proyecto en tu máquina. Asegúrate de que la carpeta raíz contenga los 3 subdirectorios (`backend-arludent`, `arludent-frontend`, `agenteIA-arludent`) y el archivo `docker-compose.yml`.

### 4.2 Configurar las variables de entorno

**a)** Abre una terminal y navega hasta la carpeta raíz del proyecto:

```powershell
cd C:\ruta\donde\descargaste\Arludent
```

**b)** Copia el archivo de template para crear tu `.env`:

```powershell
copy .env.docker .env
```

**c)** Abre el archivo `.env` con un editor de texto:

```powershell
notepad .env
```

**d)** Revisa y modifica las siguientes variables **solo si es necesario**:

| Variable | Valor por defecto | ¿Cuándo modificar? |
|---|---|---|
| `FRONTEND_PORT` | `3000` | Si el puerto 3000 ya está ocupado en tu máquina |
| `BACKEND_PORT` | `8080` | Si el puerto 8080 ya está ocupado |
| `AGENT_PORT` | `8001` | Si el puerto 8001 ya está ocupado |
| `DB_PORT_EXTERNAL` | `3307` | Si el puerto 3307 ya está ocupado |
| `DB_PASSWORD` | `arludent2024` | Si deseas una contraseña diferente para MySQL |
| `MAIL_PASSWORD` | *(preconfigurado)* | Credencial de Gmail para envío de correos |
| `OPENAI_API_KEY` | `sk-tu-api-key-aqui` | **Obligatorio** si deseas usar el agente IA. Reemplaza con tu API Key real de OpenAI |

> **Nota:** Para un despliegue básico de demostración, puedes dejar todos los valores por defecto. El sistema funcionará completo excepto el agente IA (que requiere una API Key válida de OpenAI) y el envío de correos (que requiere credenciales válidas de Gmail).

**e)** Guarda el archivo y cierra el editor.

### 4.3 Construir y levantar los servicios

Desde la misma terminal, en la carpeta raíz del proyecto, ejecuta:

```powershell
docker-compose up -d --build
```

**Desglose del comando:**
- `docker-compose up` → Crea e inicia los contenedores
- `-d` → Modo "detached" (en segundo plano, libera la terminal)
- `--build` → Fuerza la reconstrucción de las imágenes desde los Dockerfiles

> **⏱ Tiempo estimado:** La primera vez tardará entre **3 y 10 minutos** dependiendo de tu conexión a internet, ya que debe descargar las imágenes base (MySQL, PHP, Node, Python, Nginx) y compilar los Dockerfiles. Las ejecuciones posteriores serán mucho más rápidas gracias al caché de Docker.

Verás una salida similar a esta conforme avanza:

```
 Image mysql:8.0        Pulling
 Image arludent-backend  Building
 Image arludent-frontend Building
 Image arludent-agent    Building
 ...
 Container arludent-db       Created
 Container arludent-backend  Created
 Container arludent-frontend Created
 Container arludent-agent    Created
 Container arludent-db       Started
 Container arludent-db       Healthy
 Container arludent-backend  Started
 Container arludent-frontend Started
 Container arludent-agent    Started
```

### 4.4 Verificar que todo funciona

**a)** Verifica el estado de los contenedores:

```powershell
docker-compose ps
```

Deberías ver los 4 servicios con estado **Up**:

```
NAME                IMAGE               STATUS           PORTS
arludent-db         mysql:8.0           Up (healthy)     0.0.0.0:3307→3306/tcp
arludent-backend    arludent-backend    Up               0.0.0.0:8080→80/tcp
arludent-frontend   arludent-frontend   Up               0.0.0.0:3000→80/tcp
arludent-agent      arludent-agent      Up               0.0.0.0:8001→8001/tcp
```

**b)** Verifica los logs del backend para confirmar que la inicialización fue exitosa:

```powershell
docker-compose logs backend
```

Deberías ver este flujo completo:

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
```

**c)** Abre tu navegador y accede a:

```
http://localhost:3000
```

Deberías ver la pantalla de login de Arludent.

---

## 5. Acceso al Sistema

Una vez levantado, puedes acceder a los servicios desde tu navegador:

| Servicio | URL | Descripción |
|---|---|---|
| **Aplicación web** | [http://localhost:3000](http://localhost:3000) | Interfaz principal del sistema |
| **API Backend** | [http://localhost:8080/api](http://localhost:8080/api) | Endpoints REST de Laravel |
| **Agente IA (Health)** | [http://localhost:8001/health](http://localhost:8001/health) | Verificación del microservicio IA |

### Credenciales por defecto (datos de prueba)

Los seeders generan automáticamente usuarios de prueba. Consulta con el equipo las credenciales del usuario administrador creado por el seeder.

### Conexión directa a la base de datos

Si necesitas conectarte a MySQL con un cliente como DBeaver, MySQL Workbench u otro:

| Parámetro | Valor |
|---|---|
| **Host** | `localhost` |
| **Puerto** | `3307` |
| **Base de datos** | `consultorio` |
| **Usuario** | `root` |
| **Contraseña** | `arludent2024` |

---

## 6. ¿Qué ocurre internamente al levantar?

Es importante entender qué sucede automáticamente cuando ejecutas `docker-compose up`:

### Orden de arranque

```
1. db (MySQL)          → Se inicia primero
2. db healthcheck      → Docker espera hasta que MySQL responda "pong"
3. backend (Laravel)   → Se inicia cuando db está "healthy"
4. frontend (Vue)      → Se inicia cuando backend está creado
5. agent (FastAPI)     → Se inicia cuando backend está creado
```

### Inicialización automática del Backend

El script `entrypoint.sh` ejecuta automáticamente estos 6 pasos cada vez que el backend inicia:

| Paso | Acción | Detalle |
|---|---|---|
| **[1/6]** | Espera a MySQL | Hace ping cada 2 segundos hasta que MySQL responda (máx. 30 intentos) |
| **[2/6]** | Verifica APP_KEY | Si no existe la clave de encriptación de Laravel, la genera |
| **[3/6]** | Migraciones | Ejecuta `php artisan migrate --force` para crear/actualizar tablas |
| **[4/6]** | Seeders | Si la base de datos está vacía, ejecuta los seeders con datos de demostración |
| **[5/6]** | Optimiza caché | Cachea configuración, rutas y vistas de Blade |
| **[6/6]** | Permisos | Configura permisos de escritura en `storage/` y `bootstrap/cache/` |

> **Nota:** Los seeders **solo se ejecutan si la base de datos está vacía** (0 usuarios). Si ya tienes datos, no se sobreescriben.

### Construcción de las imágenes Docker

Cada servicio tiene su propio `Dockerfile` que define cómo se construye la imagen:

**Backend** (`backend-arludent/Dockerfile`):
- Imagen base: `php:8.4-fpm`
- Instala extensiones PHP: pdo_mysql, mbstring, gd, intl, zip, etc.
- Instala Composer y las dependencias de Laravel
- Incluye Nginx y Supervisor para manejar múltiples procesos

**Frontend** (`arludent-frontend/Dockerfile`):
- **Etapa 1 (build):** Imagen `node:22-alpine`, instala dependencias con `npm ci` y compila con `npm run build-only`
- **Etapa 2 (producción):** Imagen `nginx:alpine`, copia los archivos compilados y sirve la SPA

**Agente IA** (`agenteIA-arludent/Dockerfile`):
- Imagen base: `python:3.12-slim`
- Instala dependencias de `requirements.txt`
- Ejecuta Uvicorn con FastAPI

---

## 7. Comandos Útiles del Día a Día

Todos los comandos se ejecutan desde la **carpeta raíz** del proyecto (donde está `docker-compose.yml`).

### Ver estado de los contenedores

```powershell
docker-compose ps
```

### Ver logs en tiempo real (todos los servicios)

```powershell
docker-compose logs -f
```

Presiona `Ctrl+C` para dejar de ver los logs.

### Ver logs de un servicio específico

```powershell
docker-compose logs -f backend
docker-compose logs -f frontend
docker-compose logs -f agent
docker-compose logs -f db
```

### Reiniciar un servicio específico

```powershell
docker-compose restart backend
```

### Reconstruir y reiniciar un servicio (después de cambios en el código)

```powershell
docker-compose up -d --build backend
```

### Ejecutar un comando dentro del backend (ejemplo: artisan)

```powershell
docker-compose exec backend php artisan migrate:status
docker-compose exec backend php artisan tinker
docker-compose exec backend php artisan route:list
```

### Acceder a la terminal de un contenedor

```powershell
docker-compose exec backend bash
docker-compose exec db mysql -u root -parludent2024 consultorio
```

---

## 8. Solución de Problemas Frecuentes

### "Error: port is already allocated"

**Causa:** Otro programa ya está usando el puerto (3000, 8080, 8001 o 3307).

**Solución:** Edita el archivo `.env` y cambia el puerto afectado:
```env
FRONTEND_PORT=3001
BACKEND_PORT=8081
```
Luego reinicia:
```powershell
docker-compose down
docker-compose up -d
```

### El backend se reinicia constantemente

**Causa:** No puede conectar a MySQL.

**Solución:** Verifica que el contenedor `arludent-db` esté `healthy`:
```powershell
docker-compose ps db
```
Si no lo está, revisa sus logs:
```powershell
docker-compose logs db
```

### El agente IA no inicia (Restarting)

**Causa:** Falta la variable `OPENAI_API_KEY` o `SECRET_KEY`.

**Solución:** Verifica que en tu `.env` tengas una API Key válida de OpenAI:
```env
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxx
```
Luego reinicia el agente:
```powershell
docker-compose restart agent
```

> Si no necesitas el agente IA, puedes ignorar este error. Los demás servicios funcionan de forma independiente.

### "Cannot connect to the Docker daemon"

**Causa:** Docker Desktop no está ejecutándose.

**Solución:** Abre Docker Desktop desde el menú de inicio y espera a que esté listo (ícono verde en la barra de tareas).

### Los cambios en el código no se reflejan

**Causa:** Las imágenes Docker se construyen una sola vez. Si cambias el código fuente, necesitas reconstruir.

**Solución:**
```powershell
docker-compose up -d --build
```

### La base de datos perdió sus datos

**Causa:** Se ejecutó `docker-compose down -v` que elimina los volúmenes.

**Solución:** Usa siempre `docker-compose down` (sin `-v`) para preservar los datos. Si necesitas recrear la base desde cero, el entrypoint ejecutará migraciones y seeders automáticamente al reiniciar.

---

## 9. Detener y Limpiar el Entorno

### Detener los servicios (conserva los datos)

```powershell
docker-compose down
```

Esto detiene y elimina los contenedores, pero **conserva** los volúmenes (datos de MySQL y archivos de storage del backend).

### Detener y eliminar todo (incluidos los datos)

```powershell
docker-compose down -v
```

> **⚠ Cuidado:** Esto elimina la base de datos y todos los archivos subidos. La próxima vez que levantes, el entrypoint recreará las tablas y ejecutará los seeders desde cero.

### Volver a levantar después de detener

```powershell
docker-compose up -d
```

No necesitas `--build` a menos que hayas modificado el código fuente. Docker usará las imágenes que ya construyó.

---

## Resumen Rápido

Para quien tiene prisa, estos son los **3 comandos** necesarios:

```powershell
# 1. Ir a la carpeta del proyecto
cd C:\ruta\al\proyecto\Arludent

# 2. Crear el archivo de configuración
copy .env.docker .env

# 3. Levantar todo
docker-compose up -d --build
```

Espera ~5 minutos y abre [http://localhost:3000](http://localhost:3000) en tu navegador. ¡Listo!
