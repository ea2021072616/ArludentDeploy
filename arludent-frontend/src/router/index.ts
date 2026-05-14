import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ============ RUTAS PÚBLICAS (AUTH) ============
    {
      path: '/',
      name: 'landing',
      component: () => import('@/views/LandingPage.vue'),
      meta: { requiresGuest: true, layout: 'empty' }
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/Auth/LoginView.vue'),
      meta: { requiresGuest: true, layout: 'auth' }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/Auth/RegisterView.vue'),
      meta: { requiresGuest: true, layout: 'auth' }
    },
    {
      path: '/mfa',
      name: 'mfa',
      component: () => import('@/views/Auth/MfaView.vue'),
      meta: { layout: 'auth' }
    },
    {
      path: '/email/verify',
      name: 'email-verify',
      component: () => import('@/views/Auth/EmailVerificationView.vue'),
      meta: { layout: 'auth' }
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/views/Auth/ForgotPasswordView.vue'),
      meta: { requiresGuest: true, layout: 'auth' }
    },
    {
      path: '/reset-password/:token',
      name: 'reset-password',
      component: () => import('@/views/Auth/ResetPasswordView.vue'),
      meta: { requiresGuest: true, layout: 'auth' }
    },

    // ============ RUTAS PÚBLICAS (SEGUIMIENTO PACIENTE) ============
    {
      path: '/seguimiento/:token',
      name: 'seguimiento-publico',
      component: () => import('@/views/SeguimientoPublicoView.vue'),
      meta: { layout: 'empty' }
    },

    // ============ RUTAS PROTEGIDAS ============

    // Dashboard general (redirige según rol)
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/Shared/DashboardView.vue'),
      meta: { requiresAuth: true },
      beforeEnter: (to, from, next) => {
        const authStore = useAuthStore()
        if (authStore.hasRole('admin')) return next({ name: 'admin-dashboard' })
        if (authStore.hasRole('medico')) return next({ name: 'medico-dashboard' })
        if (authStore.hasRole('secretaria')) return next({ name: 'secretaria-dashboard' })
        if (authStore.hasRole('paciente') || authStore.hasRole('usuario')) return next({ name: 'paciente-dashboard' })
        return next()
      }
    },

    // ============ DASHBOARDS ============
    {
      path: '/admin/dashboard',
      name: 'admin-dashboard',
      component: () => import('@/views/Dashboards/AdminDashboardView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },
    {
      path: '/medico/dashboard',
      name: 'medico-dashboard',
      component: () => import('@/views/Dashboards/MedicoDashboardView.vue'),
      meta: { requiresAuth: true, roles: ['medico'] }
    },
    {
      path: '/secretaria/dashboard',
      name: 'secretaria-dashboard',
      component: () => import('@/views/Dashboards/SecretariaDashboardNew.vue'),
      meta: { requiresAuth: true, roles: ['secretaria'] }
    },
    {
      path: '/paciente/dashboard',
      name: 'paciente-dashboard',
      component: () => import('@/views/Dashboards/PacienteDashboardView.vue'),
      meta: { requiresAuth: true, roles: ['paciente', 'usuario'] }
    },

    // ============ PERFIL ============
    {
      path: '/perfil',
      name: 'perfil',
      component: () => import('@/views/Perfil/PerfilView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/medico/perfil-profesional',
      name: 'medico-perfil-profesional',
      component: () => import('@/views/Perfil/PerfilProfesionalView.vue'),
      meta: { requiresAuth: true, roles: ['medico'] }
    },

    // ============ DISPONIBILIDAD MÉDICA ============
    {
      path: '/medico/disponibilidad',
      name: 'medico-disponibilidad',
      component: () => import('@/views/Medico/DisponibilidadMedico.vue'),
      meta: { requiresAuth: true, roles: ['medico'] }
    },

    // ============ GESTIÓN DE USUARIOS ============
    {
      path: '/admin/usuarios',
      name: 'admin-usuarios',
      component: () => import('@/views/Usuarios/UsuariosView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },
    {
      path: '/admin/usuarios/crear',
      name: 'admin-usuarios-crear',
      component: () => import('@/views/Usuarios/CrearUsuarioView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },
    {
      path: '/admin/usuarios/:id/editar',
      name: 'admin-usuarios-editar',
      component: () => import('@/views/Usuarios/EditarUsuarioView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },
    {
      path: '/admin/perfiles-doctores',
      name: 'admin-perfiles-doctores',
      component: () => import('@/views/Usuarios/PerfilesDoctoresView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },

    // ============ CATÁLOGO DE TRATAMIENTOS ============
    {
      path: '/admin/tratamientos',
      name: 'admin-tratamientos',
      component: () => import('@/views/Tratamientos/TratamientosView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },
    {
      path: '/admin/tratamientos/crear',
      name: 'admin-tratamientos-crear',
      component: () => import('@/views/Tratamientos/CrearTratamientoView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },
    {
      path: '/admin/tratamientos/:id/editar',
      name: 'admin-tratamientos-editar',
      component: () => import('@/views/Tratamientos/EditarTratamientoView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },

    // ============ GESTIÓN DE PACIENTES ============
    {
      path: '/pacientes',
      name: 'pacientes',
      component: () => import('@/views/Paciente/PacientesView.vue'),
      meta: { requiresAuth: true, roles: ['medico'] }
    },

    // ============ GESTIÓN DE CITAS ============
    {
      path: '/citas',
      name: 'citas',
      component: () => import('@/views/Citas/CitasView.vue'),
      meta: { requiresAuth: true, roles: ['medico'] }
    },

    // ============ MIS CITAS (PACIENTE) ============
    {
      path: '/mis-citas/listado',
      name: 'paciente-mis-citas-listado',
      component: () => import('@/views/Citas/MisCitasListadoView.vue'),
      meta: { requiresAuth: true, roles: ['paciente', 'usuario'] }
    },
    {
      path: '/mis-pagos',
      name: 'paciente-mis-pagos',
      component: () => import('@/views/Paciente/MisPagosView.vue'),
      meta: { requiresAuth: true, roles: ['paciente', 'usuario'] }
    },
    {
      path: '/mis-citas/calendario',
      name: 'paciente-mis-citas-calendario',
      component: () => import('@/views/Citas/MisCitasCalendarioView.vue'),
      meta: { requiresAuth: true, roles: ['paciente', 'usuario'] }
    },

    // ============ MIS CITAS (MÉDICO) ============
    {
      path: '/medico/mis-citas/listado',
      name: 'medico-mis-citas-listado',
      component: () => import('@/views/Citas/MisCitasMedicoListadoView.vue'),
      meta: { requiresAuth: true, roles: ['medico'] }
    },
    {
      path: '/medico/mis-citas/calendario',
      name: 'medico-mis-citas-calendario',
      component: () => import('@/views/Citas/MisCitasMedicoCalendarioView.vue'),
      meta: { requiresAuth: true, roles: ['medico'] }
    },

    // ============ SECRETARÍA ============
    {
      path: '/secretaria/agenda',
      name: 'secretaria-agenda',
      component: () => import('@/views/Secretaria/SecretariaAgendaView.vue'),
      meta: { requiresAuth: true, roles: ['secretaria'] }
    },
    {
      path: '/secretaria/agenda/nueva-cita',
      name: 'secretaria-nueva-cita',
      component: () => import('@/views/Secretaria/SecretariaNuevaCitaView.vue'),
      meta: { requiresAuth: true, roles: ['secretaria'] }
    },
    {
      path: '/secretaria/agenda/calendario',
      name: 'secretaria-calendario',
      component: () => import('@/views/Secretaria/SecretariaCalendarioView.vue'),
      meta: { requiresAuth: true, roles: ['secretaria'] }
    },
    {
      path: '/secretaria/checkin',
      name: 'secretaria-checkin',
      component: () => import('@/views/Secretaria/CheckinColaView.vue'),
      meta: { requiresAuth: true, roles: ['secretaria'] }
    },
    {
      path: '/secretaria/pacientes',
      name: 'secretaria-pacientes',
      component: () => import('@/views/Secretaria/PacientesSecretariaView.vue'),
      meta: { requiresAuth: true, roles: ['secretaria'] }
    },
    {
      path: '/secretaria/seguimiento',
      name: 'secretaria-seguimiento',
      component: () => import('@/views/Secretaria/SeguimientoPostView.vue'),
      meta: { requiresAuth: true, roles: ['secretaria'] }
    },
    {
      path: '/secretaria/caja',
      name: 'secretaria-caja',
      component: () => import('@/views/Secretaria/CajaPagosView.vue'),
      meta: { requiresAuth: true, roles: ['secretaria'] }
    },

    // ============ HISTORIAL CLÍNICO ============
    {
      path: '/historial',
      name: 'historial',
      component: () => import('@/views/HistorialClinico/HistorialView.vue'),
      meta: { requiresAuth: true, roles: ['medico'] }
    },
    {
      path: '/historial-clinico/:id',
      name: 'ver-historial',
      component: () => import('@/views/HistorialClinico/VerHistorialView.vue'),
      meta: { requiresAuth: true, roles: ['medico'] }
    },
    {
      path: '/mi-historial',
      name: 'mi-historial',
      component: () => import('@/views/Paciente/MiHistorialView.vue'),
      meta: { requiresAuth: true, roles: ['paciente', 'usuario'] }
    },

    // ============ AUDITORÍA DEL SISTEMA ============
    {
      path: '/admin/auditoria',
      name: 'admin-auditoria',
      component: () => import('@/views/Auditoria/AuditoriaView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },

    // ============ REPORTES E INDICADORES ============
    {
      path: '/admin/reportes',
      name: 'admin-reportes',
      component: () => import('@/views/Reportes/ReportesView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },
    {
      path: '/admin/indicadores',
      name: 'admin-indicadores',
      component: () => import('@/views/Reportes/IndicadoresView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] }
    },

    // ============ 404 ============
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/Shared/NotFoundView.vue'),
      meta: { layout: 'auth' }
    }
  ]
})

// ============ GUARDS DE NAVEGACIÓN ============

/**
 * Guard global - Verificar autenticación
 */
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Inicializar auth store si hay token
  if (authStore.token && !authStore.user) {
    try {
      await authStore.initialize()
    } catch (error) {
      console.error('Error al inicializar auth:', error)
    }
  }

  // Rutas que requieren autenticación
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  // Rutas solo para invitados (no autenticados)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  // Verificar roles específicos
  if (to.meta.roles && Array.isArray(to.meta.roles)) {
    const hasRequiredRole = to.meta.roles.some((role: string) =>
      authStore.hasRole(role)
    )

    if (!hasRequiredRole) {
      return next({ name: 'dashboard' })
    }
  }

  next()
})

export default router
