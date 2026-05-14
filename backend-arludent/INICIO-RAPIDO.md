# 🚀 API Arludent - Inicio Rápido

## ✅ Servidor Iniciado

El servidor está corriendo en: **http://127.0.0.1:8000**

---

## 📖 Documentación Swagger/OpenAPI

Accede a la documentación interactiva de la API:

👉 **http://127.0.0.1:8000/api/documentacion**

---

## 🔐 Usuarios de Prueba

Después de ejecutar los seeders, tienes estos usuarios disponibles:

| Rol | Email | Contraseña | Estado |
|-----|-------|------------|--------|
| **Admin** | admin@arludent.com | Admin123! | ✅ Verificado |
| **Médico** | medico@arludent.com | Medico123! | ✅ Verificado |
| **Externo** | externo@arludent.com | Externo123! | ✅ Verificado (Sin historial) |
| **Paciente** | paciente@arludent.com | Paciente123! | ✅ Verificado (Sin historial) |
> **Nota:** Los usuarios se registran como **"externos"** hasta que un médico cree su historial clínico.
> Una vez creado el historial, automáticamente se convierten en **"pacientes"**.

**Usuarios externos adicionales de prueba:**
- `externo1@test.com` / `Password123!`
- `externo2@test.com` / `Password123!`
- `externo3@test.com` / `Password123!`
- `externo4@test.com` / `Password123!`
- `externo5@test.com` / `Password123!`

---

## 🧪 Probar la API

### 1️⃣ Login (Obtener Token JWT)

```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "correo": "admin@arludent.com",
    "password": "Admin123!"
  }'
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id_usuario": 1,
      "username": "admin",
      "correo": "admin@arludent.com",
      "estado": "activo",
      "roles": [...]
    }
  }
}
```

### 2️⃣ Ver Perfil (Usando Token)

```bash
curl -X GET http://127.0.0.1:8000/api/usuarios/perfil \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json"
```

### 3️⃣ Registrar Nuevo Usuario

```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "nuevo.usuario",
    "correo": "nuevo@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "telefono": "+51987654321"
  }'
```

---

## 🗂️ Endpoints Disponibles

### **Autenticación** (`/api/auth/`)
- ✅ `POST /auth/register` - Registrar usuario
- ✅ `POST /auth/login` - Iniciar sesión
- ✅ `POST /auth/logout` - Cerrar sesión
- ✅ `POST /auth/refresh` - Refrescar token
- ✅ `GET /auth/me` - Usuario actual
- ✅ `POST /auth/verificar-correo` - Verificar email
- ✅ `POST /auth/reenviar-verificacion` - Reenviar verificación
- ✅ `POST /auth/recuperar-password` - Solicitar recuperación
- ✅ `POST /auth/restablecer-password` - Restablecer contraseña
- ✅ `POST /auth/cambiar-password` - Cambiar contraseña

### **MFA (Autenticación de Dos Factores)** (`/api/auth/mfa/`)
- ✅ `POST /auth/mfa/generar` - Generar QR para MFA
- ✅ `POST /auth/mfa/activar` - Activar MFA
- ✅ `POST /auth/mfa/verificar-login` - Verificar código en login
- ✅ `POST /auth/mfa/desactivar` - Desactivar MFA

### **Usuarios** (`/api/usuarios/`)
- ✅ `GET /usuarios/perfil` - Ver perfil
- 🚧 `PUT /usuarios/perfil` - Actualizar perfil (en desarrollo)
- 🚧 `GET /usuarios` - Listar usuarios (admin) (en desarrollo)

### **Clínica** (🚧 Por implementar)
- Pacientes
- Médicos
- Citas
- Historiales Clínicos
- Odontogramas
- Tratamientos
- Pagos

---

## 📊 Base de Datos

### Tablas Creadas (30 en total)

**Autenticación y Usuarios:**
- `usuarios` - Usuarios del sistema
- `roles` - Roles (admin, medico, paciente, externo)
- `roles_usuarios` - Relación usuarios-roles
- `password_reset_tokens` - Tokens de recuperación

**Módulo Clínico:**
- `pacientes` - Datos de pacientes
- `medicos` - Datos de médicos
- `citas` - Citas médicas
- `disponibilidad_medico` - Horarios disponibles
- `historial_clinico` - Historiales clínicos
- `detalle_historial` - Detalles de historiales
- `anamnesis` - Anamnesis de pacientes
- `odontograma` - Odontogramas digitales
- `tratamientos` - Catálogo de tratamientos
- `tratamientos_historial` - Tratamientos aplicados
- `seguimiento_tratamiento` - Seguimiento
- `prescripciones` - Recetas médicas
- `presupuestos` - Presupuestos
- `presupuesto_items` - Items de presupuestos
- `documentos_clinicos` - Documentos adjuntos
- `consentimientos` - Consentimientos informados
- `pagos` - Registro de pagos

**Sistema y Auditoría:**
- `log_actividad` - Auditoría de acciones
- `interacciones_ia` - Registro de IA
- `notificaciones` - Sistema de notificaciones
- `calificaciones` - Calificaciones de servicio

---

## 🛠️ Comandos Útiles

```bash
# Ver estado de migraciones
php artisan migrate:status

# Listar todas las rutas
php artisan route:list

# Ver información de la BD
php artisan db:show

# Regenerar documentación Swagger
php artisan l5-swagger:generate

# Limpiar cachés
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ejecutar migraciones desde cero
php artisan migrate:fresh --seed
```

---

## 🔧 Configuración Actual

### Variables de Entorno (`.env`)
```env
APP_NAME="API Arludent"
APP_URL=http://localhost:8000
APP_LOCALE=es
APP_TIMEZONE=America/Lima

DB_CONNECTION=mysql
DB_DATABASE=consultorio
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=XBSx7ZkhQOMBkVSsoyfmuEmd9fsbB9QHqU2e2VyFdMqmxEZJXt6cTBzooCkcAolU
JWT_TTL=60

GOOGLE2FA_ENABLED=true
```

---

## 📝 Siguiente Paso: Desarrollo

### Controladores pendientes:
1. **PacienteController** - CRUD de pacientes
2. **MedicoController** - CRUD de médicos
3. **CitaController** - Gestión de citas
4. **HistorialClinicoController** - Historiales
5. **OdontogramaController** - Odontogramas
6. **TratamientoController** - Tratamientos
7. **PagoController** - Pagos y facturación

---

## 🎉 ¡Todo Listo!

El proyecto está completamente configurado y funcional. 

**Accede a la documentación:** http://127.0.0.1:8000/api/documentacion

**¡Comienza a desarrollar!** 🚀
