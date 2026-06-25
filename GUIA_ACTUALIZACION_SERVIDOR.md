# Guía de Actualización y Mantenimiento del Servidor (DigitalOcean)

Esta guía explica paso a paso cómo aplicar cambios de código (hacer pull) en tu Droplet de DigitalOcean y cómo ejecutar comandos de mantenimiento de la base de datos (como refrescar migraciones y seeders) dentro del entorno Dockerizado.

## 1. Conectarse al Servidor por SSH

Para realizar cualquier actualización, primero debes acceder a tu máquina virtual en DigitalOcean usando la terminal de comandos (PowerShell, CMD, Git Bash o Terminal) de tu computadora local:

```bash
ssh root@174.138.52.116
```
*(Si usas una llave SSH configurada en tu máquina, entrarás directamente sin necesidad de contraseña).*

## 2. Aplicar cambios de código (Git Pull) y Reconstruir

Una vez que estás conectado dentro del servidor, debes dirigirte a la carpeta donde está alojado el código fuente de tu proyecto, que en tu caso es `/opt/arludent`.

**Paso A:** Moverse a la carpeta del proyecto.
```bash
cd /opt/arludent
```

**Paso B:** Descargar los últimos cambios que subiste a GitHub.
```bash
git pull origin main
```

**Paso C:** Reconstruir los contenedores. 
Como el servidor utiliza Docker, siempre que agregues nuevas dependencias (en `package.json` o `composer.json`) o hagas cambios en el código, es muy recomendable indicarle a Docker que vuelva a compilar las imágenes para aplicar los cambios en vivo.
```bash
docker compose up -d --build
```
*(Esto construirá las nuevas imágenes y volverá a levantar los servicios sin interrumpir el funcionamiento general por mucho tiempo).*

## 3. Refrescar la Base de Datos y Seeders en el Servidor

Cuando trabajas en tu entorno local sin Docker, normalmente usarías el comando `php artisan migrate:refresh --seed`. 

En tu servidor de DigitalOcean, la aplicación Laravel no corre suelta en el sistema operativo, sino que corre **adentro de un contenedor de Docker**. Por lo tanto, debes indicarle a Docker que ejecute ese comando `artisan` dentro del contenedor específico del backend.

Asegurándote de estar en la ruta del proyecto (`/opt/arludent`), ejecuta:

```bash
docker compose exec backend php artisan migrate:refresh --seed
```

### Desglose del comando:
- **`docker compose exec`**: Le dice a Docker "ejecuta el siguiente comando adentro de un contenedor que está en ejecución".
- **`backend`**: Es el nombre del servicio/contenedor donde está tu API de Laravel.
- **`php artisan migrate:refresh --seed`**: Es el comando nativo de Laravel que tú ya conoces, que vacía la BD, vuelve a correr las migraciones y carga los Seeders.

> [!WARNING]
> **PRECAUCIÓN EN PRODUCCIÓN:**
> Recuerda que `migrate:refresh` borra **TODAS** las tablas y la información real almacenada en la base de datos. Si ya tienes pacientes, agendas y doctores reales cargados en tu servidor, este comando borrará todo y lo reemplazará únicamente con la data falsa de los *Seeders*. Úsalo solo si estás seguro de querer limpiar la base de datos por completo.
