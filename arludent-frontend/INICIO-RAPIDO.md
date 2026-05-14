# 🚀 INICIO RÁPIDO - ARLUDENT FRONTEND

## ✅ Todo está listo para usar

El proyecto frontend de Arludent está completamente configurado y sin errores.

---

## 📋 Pasos para Iniciar

### 1️⃣ Verificar que el Backend esté corriendo

```bash
# En otra terminal
cd c:\Users\erick\Downloads\Arludent\backend-arludent
php artisan serve
```

El backend debe estar en: **http://127.0.0.1:8000**

---

### 2️⃣ Iniciar el Frontend

```bash
cd c:\Users\erick\Downloads\Arludent\arludent-frontend
npm run dev
```

El frontend estará en: **http://localhost:5173**

---

## 🧪 Probar la Aplicación

### Usuarios de Prueba (del backend)

| Rol | Email | Contraseña |
|-----|-------|------------|
| **Admin** | admin@arludent.com | Admin123! |
| **Médico** | medico@arludent.com | Medico123! |
| **Paciente** | paciente@arludent.com | Paciente123! |

### Flujo de Prueba Completo

#### 1. **Registro de Nuevo Usuario**
- Ve a: http://localhost:5173/register
- Completa el formulario con tus datos
- Recibirás un mensaje de verificación de correo
- **Nota:** Para desarrollo, revisa los logs del backend para obtener el token de verificación

#### 2. **Login**
- Ve a: http://localhost:5173/login
- Ingresa correo y contraseña
- Si es exitoso, serás redirigido al dashboard

#### 3. **Explorar Dashboard**
- Verás estadísticas básicas
- Navega por el menú lateral (Sidebar)
- Accede a tu perfil

#### 4. **Gestionar Perfil**
- Ve a: http://localhost:5173/perfil
- Actualiza tu nombre de usuario y teléfono
- Cambia tu contraseña
- **Habilita MFA (Autenticación de 2 Factores)**:
  - Click en "Habilitar" en la sección de 2FA
  - Escanea el código QR con Google Authenticator
  - Ingresa el código de 6 dígitos para confirmar
  - ¡Guarda los códigos de respaldo!

#### 5. **Probar MFA**
- Cierra sesión
- Inicia sesión nuevamente
- Ahora te pedirá el código de Google Authenticator
- Ingresa el código y accede

#### 6. **Recuperación de Contraseña**
- Ve a: http://localhost:5173/forgot-password
- Ingresa tu correo
- Revisa los logs del backend para obtener el token
- Usa el token en: http://localhost:5173/reset-password/{TOKEN}

---

## 🎯 Funcionalidades Disponibles

### ✅ Completamente Funcionales
- [x] Login / Logout
- [x] Registro de usuarios
- [x] Verificación de correo electrónico
- [x] Recuperación de contraseña
- [x] Cambio de contraseña
- [x] MFA (Autenticación de 2 factores)
- [x] Perfil de usuario
- [x] Dashboard con estadísticas
- [x] Navegación por roles
- [x] Sidebar responsivo
- [x] Notificaciones elegantes (SweetAlert2)
- [x] Validaciones de formularios

### 🚧 En Desarrollo (Estructura lista)
- [ ] Módulo de Pacientes
- [ ] Módulo de Citas
- [ ] Módulo de Historial Clínico
- [ ] Módulo de Usuarios (Admin)
- [ ] Módulo de Auditoría (Admin)

---

## 🔧 Comandos Útiles

```bash
# Desarrollo
npm run dev

# Compilar (verificar que no hay errores)
npm run build

# Verificar tipos de TypeScript
npm run type-check

# Lint (verificar código)
npm run lint

# Formatear código
npm run format

# Vista previa de producción
npm run preview
```

---

## 🎨 Características del Diseño

### Colores
- **Primary**: Azul (#2563eb) - Botones principales, links
- **Dental**: Turquesa (#14b8a6) - Tema dental
- **Success**: Verde - Confirmaciones
- **Danger**: Rojo - Errores
- **Warning**: Amarillo - Advertencias

### Responsive
- ✅ Mobile (< 768px) - Sidebar colapsable
- ✅ Tablet (768px - 1023px)
- ✅ Desktop (>= 1024px)

---

## 🔐 Seguridad Implementada

1. **JWT con Renovación Automática**
   - El token se renueva automáticamente antes de expirar
   - Si falla, redirige al login

2. **MFA Opcional**
   - Compatible con Google Authenticator, Authy, etc.
   - Códigos de respaldo en caso de pérdida

3. **Guards de Navegación**
   - Rutas protegidas por autenticación
   - Rutas protegidas por roles (admin, medico)

4. **Validaciones Robustas**
   - Email válido
   - Contraseñas seguras (8+ caracteres, mayúsculas, minúsculas, números)
   - Confirmación de contraseña
   - Mensajes claros en español

---

## 📱 Rutas Principales

### Públicas (no requieren login)
```
/                          → Redirige a /login
/login                     → Inicio de sesión
/register                  → Registro
/mfa                       → Verificación MFA
/email/verify/:token       → Verificar correo
/forgot-password           → Recuperar contraseña
/reset-password/:token     → Restablecer contraseña
```

### Protegidas (requieren login)
```
/dashboard                 → Panel principal (todos)
/perfil                    → Perfil de usuario (todos)
/pacientes                 → Gestión de pacientes (admin, medico)
/citas                     → Gestión de citas (todos)
/historial                 → Historial clínico (todos)
/usuarios                  → Gestión de usuarios (admin)
/auditoria                 → Auditoría (admin)
```

---

## 🐛 Solución de Problemas

### Error: "Cannot connect to API"
- ✅ Verifica que el backend esté corriendo en http://127.0.0.1:8000
- ✅ Verifica el archivo `.env` que tenga `VITE_API_URL=http://127.0.0.1:8000/api`

### Error: "CORS Policy"
- ✅ En el backend, verifica `config/cors.php`:
  ```php
  'allowed_origins' => ['http://localhost:5173'],
  ```

### Error: "Token expired"
- ✅ El token se renueva automáticamente
- ✅ Si falla, serás redirigido al login

### Error: "MFA code invalid"
- ✅ Verifica que la hora de tu sistema esté sincronizada
- ✅ Los códigos TOTP dependen del tiempo exacto

### Sidebar no se ve en mobile
- ✅ Click en el icono de menú (☰) en la esquina superior izquierda

---

## 📦 Archivos Importantes

```
arludent-frontend/
├── .env                          → Variables de entorno
├── src/
│   ├── api/                      → Servicios de API
│   │   ├── axios.ts             → Cliente HTTP con interceptores
│   │   ├── authService.ts       → Servicios de autenticación
│   │   └── userService.ts       → Servicios de usuario
│   │
│   ├── stores/                   → Pinia Stores (estado global)
│   │   ├── authStore.ts         → Estado de autenticación
│   │   └── userStore.ts         → Estado de usuario
│   │
│   ├── composables/              → Hooks reutilizables
│   │   ├── useAuth.ts
│   │   ├── useMFA.ts
│   │   ├── useNotification.ts
│   │   └── useValidation.ts
│   │
│   ├── views/                    → Páginas
│   │   ├── Auth/                → Login, Register, etc.
│   │   ├── Usuario/             → Perfil
│   │   └── DashboardView.vue
│   │
│   └── router/index.ts          → Configuración de rutas
│
├── PROYECTO-COMPLETADO.md       → Documentación completa
└── README.md                    → Guía rápida
```

---

## 🎓 Próximos Pasos

1. **Explorar el código**
   - Revisa `src/stores/authStore.ts` para entender la gestión de autenticación
   - Revisa `src/api/axios.ts` para ver los interceptores
   - Revisa `src/router/index.ts` para ver los guards de navegación

2. **Implementar módulos clínicos**
   - Pacientes: CRUD completo con búsqueda
   - Citas: Calendario interactivo
   - Historial: Formularios clínicos

3. **Agregar funcionalidades**
   - Notificaciones en tiempo real
   - Chat de soporte
   - Reportes y gráficos

---

## ✨ Todo Funciona Correctamente

- ✅ Sin errores de TypeScript
- ✅ Sin errores de compilación
- ✅ Sin errores de lint
- ✅ Todas las dependencias instaladas
- ✅ Configuración completa
- ✅ Listo para desarrollo

---

## 🎉 ¡A Codear!

Abre tu navegador en **http://localhost:5173** y comienza a explorar el sistema.

**¿Preguntas?** Revisa:
- `README.md` - Guía básica
- `PROYECTO-COMPLETADO.md` - Documentación completa
- Este archivo - Inicio rápido

---

**🦷 Arludent - Sistema de Gestión Odontológica**

*Desarrollado con ❤️ usando Vue 3 + TypeScript + Vite*
