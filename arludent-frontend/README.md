# 🦷 Arludent Frontend

Frontend del Sistema de Gestión Odontológica Arludent, desarrollado con Vue 3 + TypeScript + Vite.

---

## 📋 Características

### 🔐 Autenticación Completa
- ✅ Login con JWT
- ✅ Registro de usuarios con verificación por correo
- ✅ Recuperación de contraseña
- ✅ Autenticación de dos factores (MFA) con Google Authenticator
- ✅ Gestión de perfil de usuario
- ✅ Cambio de contraseña

### 🏥 Módulos Clínicos (En desarrollo)
- 📋 Gestión de pacientes
- 📅 Sistema de citas
- 📝 Historiales clínicos
- 💊 Tratamientos

### 🛡️ Seguridad
- 🔒 Tokens JWT con renovación automática
- 🔑 MFA opcional con QR code
- ✉️ Verificación de correo electrónico
- 🚦 Guards de navegación por roles

---

## 🖥️ Requisitos del Sistema

```bash
Node.js >= 20.19.0 || >= 22.12.0
npm >= 10.x
```

---

## 🚀 Instalación

### 1. Instalar dependencias

```bash
npm install
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

Edita el archivo `.env`:

```env
VITE_API_URL=http://127.0.0.1:8000/api
VITE_APP_NAME=Arludent
VITE_TOKEN_EXPIRATION=3600000
VITE_DEBUG=true
```

### 3. Ejecutar en modo desarrollo

```bash
npm run dev
```

La aplicación estará disponible en: **http://localhost:5173**

---

## 📦 Scripts Disponibles

```bash
npm run dev          # Desarrollo
npm run build        # Compilar para producción
npm run preview      # Vista previa de producción
npm run type-check   # Verificar tipos
npm run lint         # Lint
npm run format       # Format código
```

---

## 🔐 Rutas Principales

### Públicas
- `/login` - Inicio de sesión
- `/register` - Registro
- `/mfa` - Verificación MFA
- `/email/verify/:token` - Verificación de correo
- `/forgot-password` - Recuperar contraseña
- `/reset-password/:token` - Restablecer contraseña

### Protegidas
- `/dashboard` - Panel principal
- `/perfil` - Perfil de usuario
- `/pacientes` - Gestión de pacientes (Admin/Médico)
- `/citas` - Gestión de citas
- `/historial` - Historial clínico
- `/usuarios` - Gestión de usuarios (Admin)
- `/auditoria` - Auditoría (Admin)

---

## 🛠️ Tecnologías

- Vue 3 + TypeScript + Vite
- Pinia (State Management)
- Vue Router 4
- Axios
- TailwindCSS
- SweetAlert2
- Lucide Icons
- QRCode

---

## 🧪 Usuarios de Prueba

| Rol | Email | Contraseña |
|-----|-------|------------|
| Admin | admin@arludent.com | Admin123! |
| Médico | medico@arludent.com | Medico123! |
| Paciente | paciente@arludent.com | Paciente123! |

---

## 📄 Licencia

Proyecto privado - Todos los derechos reservados

---

**¡Gracias por usar Arludent! 🦷**

