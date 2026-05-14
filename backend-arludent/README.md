# 🦷 API Arludent - Sistema de Gestión Odontológica

API RESTful completa para gestión de consultorios odontológicos, desarrollada con Laravel 11+ y autenticación JWT.

---

## 📋 Tabla de Contenidos

- [Características](#características)
- [Requisitos del Sistema](#requisitos-del-sistema)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Módulos](#módulos)
- [Autenticación](#autenticación)
- [Documentación API](#documentación-api)
- [Pruebas](#pruebas)
- [Despliegue](#despliegue)

---

## ✨ Características

### 🔐 Autenticación Completa
- ✅ Registro de usuarios con verificación por correo
- ✅ Inicio de sesión con JWT (JSON Web Tokens)
- ✅ Autenticación de dos factores (MFA) con Google Authenticator
- ✅ Recuperación de contraseña por correo electrónico
- ✅ Gestión de perfil de usuario
- ✅ Refresh y logout de tokens
- ✅ Rate limiting en endpoints críticos

### 🏥 Gestión Clínica
- 📋 Gestión de pacientes y médicos
- 📅 Sistema de citas médicas
- 📝 Historiales clínicos completos
- 🦷 Odontogramas digitales
- 💊 Prescripciones médicas
- 💰 Presupuestos y pagos
- 📄 Consentimientos informados

### 🛡️ Seguridad
- 🔒 Encriptación de contraseñas con bcrypt
- 🔑 Tokens JWT con expiración configurable
- 🚦 Rate limiting configurable
- 🔍 Auditoría de acciones mediante observers
- ✉️ Verificación obligatoria de correo electrónico
- 🌐 Protección CORS configurable

### 📚 Documentación
- 📖 Swagger/OpenAPI integrado
- 🌐 Endpoints documentados en español
- 📝 Ejemplos de uso completos

---

## 🖥️ Requisitos del Sistema

```bash
PHP >= 8.2
Composer >= 2.0
MySQL >= 8.0
Node.js >= 18.x (opcional, para assets)
```

### Extensiones PHP Requeridas
```
- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Fileinfo
```

---

## 🚀 Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/tu-usuario/api-arludent.git
cd api-arludent
```

### 2. Instalar Dependencias

```bash
composer install
```

### 3. Configurar Variables de Entorno

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 4. Configurar Base de Datos

Edita el archivo `.env` con tus credenciales:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=consultorio
DB_USERNAME=root
DB_PASSWORD=tu_password
```

### 5. Ejecutar Migraciones y Seeders

```bash
php artisan migrate --seed
```

Esto creará:
- ✅ Todas las tablas necesarias
- ✅ Roles por defecto (admin, medico, secretaria, paciente)
- ✅ Usuarios de prueba

### 6. Generar Documentación Swagger

```bash
php artisan l5-swagger:generate
```

### 7. Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

La API estará disponible en: `http://localhost:8000`

---

## ⚙️ Configuración

### Configuración de Correo Electrónico

Para desarrollo, usa Mailhog:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=soporte@arludent.com
MAIL_FROM_NAME="Arludent"
```

Para producción (ejemplo con Gmail):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@gmail.com
MAIL_FROM_NAME="Arludent"
```

### Configuración JWT

```env
JWT_SECRET=tu-secreto-generado
JWT_TTL=60                    # Duración del token en minutos
JWT_REFRESH_TTL=20160         # Duración del refresh token (14 días)
JWT_ALGO=HS256
```

### Configuración CORS

Edita `config/cors.php` para producción:

```php
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
```

### Rate Limiting

Configurable en `.env`:

```env
THROTTLE_LOGIN=5              # Intentos de login por minuto
THROTTLE_REGISTER=3           # Registros por minuto
```

---

## 📁 Estructura del Proyecto

```
api-arludent/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/          # Controladores de autenticación
│   │   │   ├── Usuarios/      # Gestión de usuarios
│   │   │   ├── Clinica/       # Módulos clínicos
│   │   │   └── Sistema/       # Auditoría y logs
│   │   ├── Requests/          # Form Requests con validaciones
│   │   └── Middleware/        # Middlewares personalizados
│   ├── Models/                # Modelos Eloquent
│   ├── Services/              # Lógica de negocio
│   │   ├── AuthService.php
│   │   ├── VerificationService.php
│   │   ├── MFAService.php
│   │   ├── PasswordResetService.php
│   │   └── MailService.php
│   └── Observers/             # Observers para auditoría
├── routes/
│   ├── api.php                # Rutas principales
│   ├── auth.php               # Rutas de autenticación
│   ├── usuarios.php           # Rutas de usuarios
│   ├── clinica.php            # Rutas clínicas
│   └── sistema.php            # Rutas de sistema
├── database/
│   ├── migrations/            # Migraciones agrupadas
│   └── seeders/               # Seeders iniciales
├── resources/
│   └── views/
│       └── emails/            # Plantillas de correo
├── config/
│   ├── jwt.php                # Configuración JWT
│   └── cors.php               # Configuración CORS
└── storage/
    └── api-docs/              # Documentación Swagger
```

---

## 🧩 Módulos

### 1. Autenticación (`Auth`)

Módulo completo de autenticación con todas las características de seguridad.

**Controladores:**
- `RegisterController` - Registro de usuarios
- `LoginController` - Inicio de sesión
- `VerificationController` - Verificación de correo
- `PasswordResetController` - Recuperación de contraseña
- `ProfileController` - Gestión de perfil
- `MFAController` - Autenticación de dos factores

**Servicios:**
- `AuthService` - Lógica central de autenticación
- `VerificationService` - Generación y validación de tokens
- `MFAService` - Gestión de TOTP (Google Authenticator)
- `PasswordResetService` - Recuperación de contraseñas
- `MailService` - Envío de correos electrónicos

### 2. Usuarios (`Usuarios`)

Gestión completa de usuarios y roles.

**Características:**
- Perfil de usuario (ver/editar)
- Asignación de roles
- Historial de actividad
- Configuración de cuenta

### 3. Clínica (`Clinica`)

Módulos para gestión odontológica.

**Entidades:**
- Pacientes
- Médicos
- Citas
- Historiales clínicos
- Odontogramas
- Tratamientos
- Prescripciones
- Presupuestos
- Pagos

### 4. Sistema (`Sistema`)

Módulos de auditoría e IA.

**Características:**
- Log de actividades
- Interacciones con IA
- Reportes del sistema

---

## 🔐 Autenticación

### Flujo Completo de Autenticación

```mermaid
graph TD
    A[Registro] --> B[Envío de Email]
    B --> C[Verificación de Correo]
    C --> D[Cuenta Activada]
    D --> E[Login]
    E --> F{MFA Activado?}
    F -->|No| G[Token JWT]
    F -->|Sí| H[Validar Código MFA]
    H --> G
```

### 1. Registro de Usuario

**Endpoint:** `POST /api/auth/register`

**Payload:**
```json
{
  "username": "juan.perez",
  "correo": "juan.perez@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "telefono": "+51987654321"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Usuario registrado exitosamente. Verifica tu correo.",
  "data": {
    "usuario": {
      "id_usuario": 1,
      "username": "juan.perez",
      "correo": "juan.perez@example.com",
      "estado": "pendiente"
    }
  }
}
```

### 2. Verificación de Correo

**Endpoint:** `POST /api/auth/verify-email`

**Payload:**
```json
{
  "token": "abc123def456..."
}
```

### 3. Inicio de Sesión

**Endpoint:** `POST /api/auth/login`

**Payload:**
```json
{
  "correo": "juan.perez@example.com",
  "password": "Password123!"
}
```

**Respuesta (sin MFA):**
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "usuario": {
      "id_usuario": 1,
      "username": "juan.perez",
      "correo": "juan.perez@example.com"
    }
  }
}
```

**Respuesta (con MFA):**
```json
{
  "success": true,
  "message": "Se requiere código MFA",
  "data": {
    "mfa_required": true,
    "temp_token": "temp_xyz123..."
  }
}
```

### 4. Validación MFA

**Endpoint:** `POST /api/auth/mfa/verify`

**Headers:**
```
Authorization: Bearer temp_xyz123...
```

**Payload:**
```json
{
  "code": "123456"
}
```

### 5. Activar MFA

**Endpoint:** `POST /api/auth/mfa/enable`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Respuesta:**
```json
{
  "success": true,
  "message": "MFA activado exitosamente",
  "data": {
    "secret": "JBSWY3DPEHPK3PXP",
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUh..."
  }
}
```

### 6. Recuperación de Contraseña

**Paso 1:** Solicitar reset
```
POST /api/auth/password/forgot
{
  "correo": "juan.perez@example.com"
}
```

**Paso 2:** Resetear contraseña
```
POST /api/auth/password/reset
{
  "token": "reset_token_123",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

### 7. Perfil de Usuario

**Ver perfil:**
```
GET /api/usuarios/perfil
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Actualizar perfil:**
```
PUT /api/usuarios/perfil
{
  "username": "juan.perez.updated",
  "telefono": "+51999888777"
}
```

### 8. Logout

**Endpoint:** `POST /api/auth/logout`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### 9. Refresh Token

**Endpoint:** `POST /api/auth/refresh`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## 📖 Documentación API

### Swagger/OpenAPI

La documentación interactiva está disponible en:

```
http://localhost:8000/api/documentacion
```

### Regenerar Documentación

```bash
php artisan l5-swagger:generate
```

### Tags de Documentación

- **Autenticación** - Endpoints de auth
- **Usuarios** - Gestión de usuarios
- **Clínica** - Módulos clínicos
- **Sistema** - Auditoría y logs

---

## 🧪 Pruebas

### Ejecutar Pruebas

```bash
# Todas las pruebas
php artisan test

# Con cobertura
php artisan test --coverage

# Pruebas específicas
php artisan test --filter=AuthTest
```

### Estructura de Pruebas

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── RegisterTest.php
│   │   ├── LoginTest.php
│   │   ├── VerificationTest.php
│   │   ├── MFATest.php
│   │   └── PasswordResetTest.php
│   └── Usuarios/
│       └── ProfileTest.php
└── Unit/
    ├── Services/
    │   ├── AuthServiceTest.php
    │   └── MFAServiceTest.php
    └── Models/
        └── UsuarioTest.php
```

---

## 🚀 Despliegue

### Preparación para Producción

1. **Optimizar configuración:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **Optimizar autoload:**
```bash
composer install --optimize-autoloader --no-dev
```

3. **Configurar variables de entorno:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.arludent.com

# JWT en producción
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Correo en producción
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
# ... configuración real

# CORS
FRONTEND_URL=https://arludent.com
```

4. **Configurar SSL/HTTPS**

5. **Configurar cron jobs:**
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Despliegue en Servidor

**Opción 1: Apache**
```apache
<VirtualHost *:80>
    ServerName api.arludent.com
    DocumentRoot /var/www/api-arludent/public

    <Directory /var/www/api-arludent/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Opción 2: Nginx**
```nginx
server {
    listen 80;
    server_name api.arludent.com;
    root /var/www/api-arludent/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 📝 Usuarios de Prueba

Después de ejecutar los seeders, tendrás estos usuarios disponibles:

| Rol | Correo | Contraseña | Estado |
|-----|--------|------------|--------|
| Admin | admin@arludent.com | Password123! | Verificado |
| Médico | medico@arludent.com | Password123! | Verificado |
| Secretaria | secretaria@arludent.com | Password123! | Verificado |
| Paciente | paciente@arludent.com | Password123! | Verificado |

---

## 🔧 Comandos Útiles

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ver rutas
php artisan route:list

# Crear migración
php artisan make:migration nombre_migracion

# Crear modelo
php artisan make:model NombreModelo

# Crear controlador
php artisan make:controller NombreController

# Crear seeder
php artisan make:seeder NombreSeeder

# Rollback migraciones
php artisan migrate:rollback

# Refrescar BD
php artisan migrate:fresh --seed
```

---

## 🐛 Troubleshooting

### Error: "JWT secret not set"
```bash
php artisan jwt:secret
```

### Error: "Class 'Tymon\JWTAuth\...' not found"
```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

### Error de permisos en storage
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Error de migración
```bash
php artisan migrate:fresh --seed
```

---

## 📄 Licencia

Este proyecto es privado y propietario de Arludent.

---

## 👥 Equipo de Desarrollo

- **Backend Lead:** [Tu Nombre]
- **Arquitectura:** Laravel 11
- **Base de Datos:** MySQL 8+
- **Autenticación:** JWT + MFA

---

## 📞 Soporte

Para preguntas o soporte:
- Email: soporte@arludent.com
- Documentación: http://localhost:8000/api/documentacion

---

**🦷 Arludent - Transformando la Gestión Odontológica** 
