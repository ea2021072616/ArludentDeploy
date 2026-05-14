<template>
  <div class="landing-page">
    <!-- Navbar Flotante -->
    <nav class="navbar" :class="{ 'scrolled': isScrolled }">
      <div class="navbar-container">
        <div class="logo">
          <img
            src="/images/logo-arludent.png"
            alt="Arludent - Odontología Integral"
            class="logo-image"
          />
          <div class="logo-text">
            <span class="logo-brand">ARLUDENT</span>
            <span class="logo-subtitle">ODONTOLOGÍA</span>
          </div>
        </div>

        <div class="nav-buttons">
          <button @click="navigateTo('/login')" class="btn-login" type="button">
            Iniciar Sesión
          </button>
          <button @click="navigateTo('/register')" class="btn-register" type="button">
            Registrarse
          </button>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-background">
        <div class="golden-gradient"></div>
        <div class="floating-particles">
          <div v-for="i in 15" :key="i" class="particle" :style="getParticleStyle(i)"></div>
        </div>
      </div>

      <div class="hero-container">
        <div class="hero-content">
          <!-- Badge Decorativo -->
          <div class="hero-badge">
            <Sparkles :size="16" />
            <span>Salud Dental de Excelencia</span>
          </div>

          <!-- Título Principal -->
          <h1 class="hero-title">
            Una nueva experiencia,<br>
            <span class="gradient-text">un nuevo comienzo.</span>
          </h1>

          <!-- Descripción -->
          <p class="hero-description">
            Resuelva sus problemas dentales de manera fácil y eficiente con tecnología
            de vanguardia y un equipo altamente especializado.
          </p>

          <!-- Credenciales en Grid -->
          <div class="credentials-grid">
            <div class="credential-card">
              <div class="credential-icon">
                <Award :size="24" />
              </div>
              <div class="credential-info">
                <span class="credential-number">+2</span>
                <span class="credential-label">Años de Experiencia</span>
              </div>
            </div>

            <div class="credential-card">
              <div class="credential-icon">
                <Users :size="24" />
              </div>
              <div class="credential-info">
                <span class="credential-number">100+</span>
                <span class="credential-label">Pacientes Satisfechos</span>
              </div>
            </div>

            <div class="credential-card">
              <div class="credential-icon">
                <Shield :size="24" />
              </div>
              <div class="credential-info">
                <span class="credential-number">100%</span>
                <span class="credential-label">Certificado</span>
              </div>
            </div>
          </div>

          <!-- CTA Buttons -->
          <div class="cta-buttons">
            <button @click="navigateTo('/register')" class="btn-primary" type="button">
              <Calendar :size="20" />
              Agendar Cita Ahora
            </button>
            <button @click="scrollToSection('servicios')" class="btn-secondary" type="button">
              <ArrowDown :size="20" />
              Conocer Más
            </button>
          </div>
        </div>

        <!-- Hero Image -->
        <div class="hero-image-wrapper">
          <div class="image-decoration decoration-1"></div>
          <div class="image-decoration decoration-2"></div>
          <div class="hero-image-container">
            <img
              src="/images/doctora.jpg"
              alt="Dra. Jacky - Arludent Odontología"
              class="hero-image"
            />

            <!-- Floating Badge -->
            <div class="floating-badge">
              <Star :size="20" class="star-icon" />
              <div>
                <div class="badge-title">Excelencia</div>
                <div class="badge-subtitle">COP 47483</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Scroll Indicator -->
      <div class="scroll-indicator">
        <ChevronDown :size="32" />
      </div>
    </section>

    <!-- Sección: Quien es la Dra. Jacky -->
    <section class="doctor-section" id="doctora">
      <div class="section-container">
        <div class="doctor-grid">
          <div class="doctor-image-wrapper">
            <div class="image-frame">
              <img
                src="/images/trabajando.jpg"
                alt="Dra. Jacky - Cirujana Dentista"
                class="doctor-photo"
              />
              <div class="frame-decoration"></div>
            </div>
          </div>

          <div class="doctor-content">
            <div class="section-badge">
              <Heart :size="16" />
              <span>Conoce a tu Odontóloga</span>
            </div>

            <h2 class="section-title">¿QUIÉN ES LA DRA. JACKY?</h2>

            <p class="doctor-intro">
              ¡Hola! Soy Jacky, tu odontóloga de confianza ✨
            </p>

            <div class="doctor-credentials">
              <div class="credential-item">
                <div class="credential-icon-badge">
                  <GraduationCap :size="20" />
                </div>
                <div>
                  <div class="credential-title">Cirujana Dentista</div>
                  <div class="credential-subtitle">COP 47483</div>
                </div>
              </div>

              <div class="credential-item">
                <div class="credential-icon-badge">
                  <BookOpen :size="20" />
                </div>
                <div>
                  <div class="credential-title">Magíster en Salud Pública</div>
                  <div class="credential-subtitle">Especialización Avanzada</div>
                </div>
              </div>
            </div>

            <div class="doctor-description">
              <p>
                Soy una profesional comprometida con brindar la mejor atención odontológica,
                combinando experiencia clínica con conocimientos especializados en salud pública
                para ofrecer un cuidado integral y personalizado.
              </p>

              <p>
                Mi enfoque se centra en crear un ambiente cómodo y de confianza,
                donde cada paciente reciba el tratamiento más adecuado utilizando
                las técnicas más modernas y seguras.
              </p>

              <p>
                Estoy aquí para ayudarte a lograr y mantener una sonrisa saludable,
                brindándote la atención de calidad que mereces con calidez y profesionalismo.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Sección: Áreas de Atención -->
    <section class="areas-section" id="areas">
      <div class="section-container">
        <div class="section-header">
          <div class="section-badge">
            <Stethoscope :size="16" />
            <span>Especialidades</span>
          </div>
          <h2 class="section-title">Áreas de Atención</h2>
          <p class="section-subtitle">
            Servicios especializados para todas tus necesidades dentales
          </p>
        </div>

        <div class="areas-grid">
          <div class="area-card">
            <div class="area-icon-wrapper">
              <Activity :size="32" />
            </div>
            <h3 class="area-title">Odontología General</h3>
            <p class="area-description">
              Atención dental completa para prevenir y resolver problemas de salud bucal con tratamientos especializados.
            </p>
          </div>

          <div class="area-card">
            <div class="area-icon-wrapper">
              <Sparkles :size="32" />
            </div>
            <h3 class="area-title">Odontología Estética</h3>
            <p class="area-description">
              Mejora la apariencia de tu sonrisa con tratamientos estéticos modernos y resultados naturales.
            </p>
          </div>

          <div class="area-card">
            <div class="area-icon-wrapper">
              <Microscope :size="32" />
            </div>
            <h3 class="area-title">Odontología Digital</h3>
            <p class="area-description">
              Tecnología de vanguardia para diagnósticos precisos y tratamientos más efectivos y cómodos.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Sección: Servicios -->
    <section class="services-section" id="servicios">
      <div class="section-container">
        <div class="section-header">
          <div class="section-badge">
            <Zap :size="16" />
            <span>Tecnología Avanzada</span>
          </div>
          <h2 class="section-title">Nuestros Servicios</h2>
          <p class="section-subtitle">
            Soluciones dentales con tecnología de última generación
          </p>
        </div>

        <div class="services-grid">
          <div class="service-card">
            <div class="service-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
              <Calendar :size="28" />
            </div>
            <h3 class="service-title">Gestión de Citas Inteligente</h3>
            <p class="service-description">
              Sistema avanzado de agendamiento online con confirmación automática y recordatorios.
            </p>
            <div class="service-features">
              <div class="feature-item">
                <Check :size="16" />
                <span>Agenda 24/7</span>
              </div>
              <div class="feature-item">
                <Check :size="16" />
                <span>Recordatorios SMS</span>
              </div>
              <div class="feature-item">
                <Check :size="16" />
                <span>Fácil reprogramación</span>
              </div>
            </div>
          </div>

          <div class="service-card">
            <div class="service-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
              <Shield :size="28" />
            </div>
            <h3 class="service-title">Especialistas Certificados</h3>
            <p class="service-description">
              Equipo médico altamente calificado con formación continua y experiencia comprobada.
            </p>
            <div class="service-features">
              <div class="feature-item">
                <Check :size="16" />
                <span>Certificación COP</span>
              </div>
              <div class="feature-item">
                <Check :size="16" />
                <span>Formación continua</span>
              </div>
              <div class="feature-item">
                <Check :size="16" />
                <span>Ética profesional</span>
              </div>
            </div>
          </div>

          <div class="service-card">
            <div class="service-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
              <FileText :size="28" />
            </div>
            <h3 class="service-title">Historial Clínico Digital</h3>
            <p class="service-description">
              Accede a tu información médica completa de forma segura desde cualquier dispositivo.
            </p>
            <div class="service-features">
              <div class="feature-item">
                <Check :size="16" />
                <span>Acceso seguro</span>
              </div>
              <div class="feature-item">
                <Check :size="16" />
                <span>Siempre disponible</span>
              </div>
              <div class="feature-item">
                <Check :size="16" />
                <span>Respaldo en la nube</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Sección: Instalaciones -->
    <section class="clinic-section" id="clinica">
      <div class="section-container">
        <div class="clinic-grid">
          <div class="clinic-content">
            <div class="section-badge">
              <Building :size="16" />
              <span>Nuestras Instalaciones</span>
            </div>

            <h2 class="section-title">Instalaciones de Primera Clase</h2>

            <p class="clinic-description">
              Contamos con instalaciones modernas y equipamiento de última generación
              para brindarte la mejor experiencia en atención odontológica.
            </p>

            <div class="clinic-features">
              <div class="feature-card">
                <Building :size="20" />
                <span>Instalaciones modernas y equipadas</span>
              </div>
              <div class="feature-card">
                <Zap :size="20" />
                <span>Tecnología de última generación</span>
              </div>
              <div class="feature-card">
                <Shield :size="20" />
                <span>Protocolos de seguridad e higiene</span>
              </div>
              <div class="feature-card">
                <Heart :size="20" />
                <span>Atención personalizada y cálida</span>
              </div>
            </div>

          </div>

          <div class="clinic-images">
            <img
              src="https://images.unsplash.com/photo-1629909613654-28e377c37b09?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
              alt="Instalaciones Arludent"
              class="clinic-image main"
            />
            <img
              src="https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
              alt="Tecnología moderna"
              class="clinic-image secondary"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- Sección: CTA Final -->
    <section class="final-cta-section">
      <div class="cta-container">
        <div class="cta-content">
          <div class="cta-icon">
            <Heart :size="64" />
          </div>

          <h2 class="cta-title">
            ¿Listo para tu Próxima Cita?
          </h2>

          <p class="cta-description">
            Agenda tu consulta hoy y experimenta la diferencia de una atención dental
            de excelencia con tecnología de vanguardia.
          </p>

          <div class="cta-buttons-final">
            <button @click="navigateTo('/register')" class="btn-cta-primary" type="button">
              <Rocket :size="20" />
              Comenzar Ahora
            </button>
            <button @click="navigateTo('/login')" class="btn-cta-secondary" type="button">
              Ya tengo cuenta
            </button>
          </div>

          <div class="cta-features">
            <div class="cta-feature">
              <Clock :size="20" />
              <span>Atención Rápida</span>
            </div>
            <div class="cta-feature">
              <Shield :size="20" />
              <span>100% Seguro</span>
            </div>
            <div class="cta-feature">
              <Heart :size="20" />
              <span>Trato Personalizado</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="footer-container">
        <!-- Footer Grid -->
        <div class="footer-grid">
          <!-- Brand Section -->
          <div class="footer-brand">
            <div class="footer-logo">
              <img
                src="/images/logo-arludent.png"
                alt="Arludent - Odontología Integral"
                class="footer-logo-image"
              />
            </div>
            <p class="footer-description">
              Cuidando tu sonrisa con excelencia y profesionalismo. Tu salud dental es nuestra prioridad.
            </p>
            <!-- Social Links -->
            <div class="footer-social">
              <a href="https://www.facebook.com/profile.php?id=100089879383651" target="_blank" rel="noopener" class="social-link" title="Facebook">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              </a>
              <a href="https://www.instagram.com/arludent_tacna/" target="_blank" rel="noopener" class="social-link" title="Instagram">
                <Instagram :size="20" />
              </a>
              <a href="https://www.tiktok.com/@arludent_tacna" target="_blank" rel="noopener" class="social-link" title="TikTok">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                </svg>
              </a>
            </div>
          </div>

          <!-- Contact Info -->
          <div class="footer-section">
            <h3 class="footer-title">Contacto</h3>
            <div class="footer-contact">
              <div class="contact-item">
                <MapPin :size="18" />
                <span>Sinchi Roca #306, Tacna, Perú</span>
              </div>
              <div class="contact-item">
                <Phone :size="18" />
                <a href="tel:+51949805092">+51 949 805 092</a>
              </div>
              <div class="contact-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <a href="https://wa.me/51949805092" target="_blank" rel="noopener">WhatsApp: +51 949 805 092</a>
              </div>
              <div class="contact-item">
                <Mail :size="18" />
                <a href="mailto:arludenttacna@gmail.com">arludenttacna@gmail.com</a>
              </div>
            </div>
          </div>

          <!-- Hours -->
          <div class="footer-section">
            <h3 class="footer-title">Horarios de Atención</h3>
            <div class="footer-hours">
              <div class="hours-item">
                <span class="day">Lunes - Viernes</span>
                <span class="time">8:00 AM - 8:00 PM</span>
              </div>
              <div class="hours-item">
                <span class="day">Sábados</span>
                <span class="time">9:00 AM - 2:00 PM</span>
              </div>
              <div class="hours-item">
                <span class="day">Domingos</span>
                <span class="time">Cerrado</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Sección de mapa centrada y más grande -->
        <div class="footer-map-container">
          <h3 class="footer-title text-center">Nuestra Ubicación</h3>
          <div class="footer-map">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3797.6827887986283!2d-70.23905!3d-17.9990631!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTfCsDU5JzU2LjYiUyA3MMKwMTQnMjAuNiJX!5e0!3m2!1ses!2spe!4v1234567890"
              width="100%"
              height="450"
              style="border:0;"
              allowfullscreen
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
          </div>
        </div>

        <div class="footer-bottom">
          <p>&copy; 2025 Arludent Odontología. Todos los derechos reservados.</p>
          <p class="footer-credit">Dra. Jacky - COP 47483 | Mag. Salud Pública</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  UserCircle, UserPlus, Sparkles, Award, Users, Shield,
  Calendar, ArrowDown, Star, Heart, GraduationCap, BookOpen,
  Stethoscope, Activity, Microscope, Zap,
  Check, Building, Clock, Rocket, ChevronDown, FileText,
  MapPin, Phone, Mail, Instagram
} from 'lucide-vue-next'

const router = useRouter()
const isScrolled = ref(false)

// Métodos
const navigateTo = (path: string) => {
  router.push(path)
}

const scrollToSection = (id: string) => {
  const element = document.getElementById(id)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}

const getParticleStyle = (index: number) => {
  const size = Math.random() * 4 + 2
  const duration = Math.random() * 20 + 10
  const delay = Math.random() * 5
  const left = Math.random() * 100
  const top = Math.random() * 100

  return {
    width: `${size}px`,
    height: `${size}px`,
    left: `${left}%`,
    top: `${top}%`,
    animationDuration: `${duration}s`,
    animationDelay: `${delay}s`
  }
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
/* Variables de Color - Temática Dorada Elegante */
:root {
  --gold-primary: #D4AF37;
  --gold-light: #F4E4C1;
  --gold-dark: #B8860B;
  --bronze: #8B7355;
  --white: #FFFFFF;
  --black: #1A1A1A;
  --gray-50: #FAFAFA;
  --gray-100: #F5F5F5;
  --gray-200: #E5E5E5;
  --gray-600: #6B7280;
  --gray-700: #374151;
  --gray-900: #111827;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.landing-page {
  width: 100%;
  overflow-x: hidden;
  background: var(--white);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ========== NAVBAR ========== */
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  padding: 1.5rem 0;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(5px);
}

.navbar.scrolled {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  padding: 1rem 0;
}

.navbar-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-image {
  height: 50px;
  width: auto;
  transition: transform 0.3s ease;
}

.logo-image:hover {
  transform: scale(1.05);
}

.logo-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.logo-brand {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--gold-dark);
  letter-spacing: 0.5px;
}

.logo-subtitle {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--gray-600);
  letter-spacing: 1.5px;
  text-transform: uppercase;
}

.nav-buttons {
  display: flex;
  gap: 1rem;
}

.btn-login, .btn-register {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-login {
  background: transparent !important;
  color: #111827 !important;
  border: 2px solid #374151 !important;
}

.btn-login:hover {
  background: #111827 !important;
  color: white !important;
  transform: translateY(-2px);
}

.btn-register {
  background: #D4AF37 !important;
  color: #111827 !important;
  box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
  border: 2px solid #D4AF37 !important;
}

.btn-register:hover {
  background: #B8860B !important;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
}

/* ========== HERO SECTION ========== */
.hero-section {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  padding: 8rem 0 4rem;
  overflow: hidden;
}

.hero-background {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.golden-gradient {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 20% 20%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(139, 115, 85, 0.08) 0%, transparent 50%),
    linear-gradient(135deg, #FAFAFA 0%, #FFFFFF 100%);
}

.floating-particles {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.particle {
  position: absolute;
  background: radial-gradient(circle, var(--gold-primary) 0%, transparent 70%);
  border-radius: 50%;
  opacity: 0.3;
  animation: float-particle linear infinite;
}

@keyframes float-particle {
  0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
  10% { opacity: 0.3; }
  90% { opacity: 0.3; }
  100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
}

.hero-container {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.hero-content {
  animation: fadeInUp 1s ease;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(212, 175, 55, 0.1);
  border: 1px solid rgba(212, 175, 55, 0.3);
  border-radius: 50px;
  color: var(--gold-dark);
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 1.5rem;
}

.hero-title {
  font-size: clamp(2.5rem, 5vw, 4rem);
  font-weight: 400;
  color: var(--gray-900) !important;
  line-height: 1.2;
  margin-bottom: 1.5rem;
}

.gradient-text {
  background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 600;
}

.hero-description {
  font-size: 1.2rem;
  color: var(--gray-600) !important;
  line-height: 1.7;
  margin-bottom: 2.5rem;
  max-width: 500px;
}

.credentials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
  margin-bottom: 2.5rem;
}

.credential-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: rgba(212, 175, 55, 0.05);
  border: 1px solid rgba(212, 175, 55, 0.1);
  border-radius: 12px;
  transition: all 0.3s ease;
}

.credential-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(212, 175, 55, 0.15);
}

.credential-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: linear-gradient(135deg, #D4AF37, #B8860B) !important;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #FFFFFF !important;
  flex-shrink: 0;
}

.credential-info {
  display: flex;
  flex-direction: column;
}

.credential-number {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--gold-dark);
}

.credential-label {
  font-size: 0.75rem;
  color: var(--gray-600);
  line-height: 1.2;
}

.cta-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-primary, .btn-secondary {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 2rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #D4AF37, #B8860B) !important;
  color: #FFFFFF !important;
  box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
}

.btn-secondary {
  background: #FFFFFF !important;
  color: #8B7355 !important;
  border: 2px solid #8B7355 !important;
}

.btn-secondary:hover {
  background: #8B7355 !important;
  color: #FFFFFF !important;
  transform: translateY(-3px);
}

/* Hero Image */
.hero-image-wrapper {
  position: relative;
  animation: fadeInRight 1s ease 0.3s both;
}

@keyframes fadeInRight {
  from { opacity: 0; transform: translateX(50px); }
  to { opacity: 1; transform: translateX(0); }
}

.image-decoration {
  position: absolute;
  border-radius: 20px;
  z-index: 0;
}

.decoration-1 {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(139, 115, 85, 0.1));
  top: -20px;
  left: -20px;
  animation: float 6s ease-in-out infinite;
}

.decoration-2 {
  width: 100%;
  height: 100%;
  background: linear-gradient(225deg, rgba(212, 175, 55, 0.15), transparent);
  bottom: -20px;
  right: -20px;
  animation: float 6s ease-in-out infinite 3s;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

.hero-image-container {
  position: relative;
  z-index: 1;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.hero-image {
  width: 100%;
  height: auto;
  display: block;
  transition: transform 0.5s ease;
}

.hero-image-container:hover .hero-image {
  transform: scale(1.05);
}

.floating-badge {
  position: absolute;
  bottom: 30px;
  left: 30px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  padding: 1rem 1.5rem;
  border-radius: 15px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  gap: 1rem;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.star-icon {
  color: #F59E0B;
  fill: #F59E0B;
}

.badge-title {
  font-weight: 700;
  color: var(--gold-dark);
  font-size: 1rem;
}

.badge-subtitle {
  font-size: 0.85rem;
  color: var(--gray-600);
}

.scroll-indicator {
  position: absolute;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%);
  color: var(--gold-primary);
  z-index: 2;
  animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateX(-50%) translateY(0); }
  50% { transform: translateX(-50%) translateY(-10px); }
}

/* ========== DOCTOR SECTION ========== */
.doctor-section {
  padding: 6rem 0;
  background: var(--gray-50);
}

.section-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.doctor-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.doctor-image-wrapper {
  position: relative;
}

.image-frame {
  position: relative;
  border-radius: 20px;
  overflow: hidden;
}

.doctor-photo {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.frame-decoration {
  position: absolute;
  inset: -10px;
  border: 3px solid var(--gold-primary);
  border-radius: 20px;
  opacity: 0.3;
  z-index: -1;
}

.section-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(212, 175, 55, 0.1);
  border: 1px solid rgba(212, 175, 55, 0.3);
  border-radius: 50px;
  color: var(--gold-dark);
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 600;
  color: var(--gray-900) !important;
  margin-bottom: 1.5rem;
  letter-spacing: -0.5px;
}

.doctor-intro {
  font-size: 1.3rem;
  color: var(--gray-700);
  margin-bottom: 2rem;
  font-weight: 500;
}

.doctor-credentials {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
  background: rgba(212, 175, 55, 0.05);
  padding: 1.5rem;
  border-radius: 15px;
  border: 1px solid rgba(212, 175, 55, 0.1);
}

.credential-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.credential-icon-badge {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: linear-gradient(135deg, #D4AF37, #B8860B) !important;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #FFFFFF !important;
  flex-shrink: 0;
}

.credential-title {
  font-weight: 600;
  color: var(--gold-dark);
  font-size: 1rem;
}

.credential-subtitle {
  font-size: 0.85rem;
  color: var(--gray-600);
}

.doctor-description {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.doctor-description p {
  color: var(--gray-700);
  line-height: 1.8;
  font-size: 1rem;
}

/* ========== AREAS SECTION ========== */
.areas-section {
  padding: 6rem 0;
  background: white;
}

.section-header {
  text-align: center;
  margin-bottom: 4rem;
}

.section-subtitle {
  font-size: 1.1rem;
  color: var(--gray-600);
  max-width: 600px;
  margin: 0 auto;
}

.areas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.area-card {
  position: relative;
  padding: 2.5rem;
  background: white;
  border: 2px solid rgba(212, 175, 55, 0.1);
  border-radius: 20px;
  text-align: center;
  transition: all 0.4s ease;
  overflow: hidden;
}

.area-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.1), transparent);
  transition: left 0.5s ease;
}

.area-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 60px rgba(212, 175, 55, 0.2);
  border-color: var(--gold-primary);
}

.area-card:hover::before {
  left: 100%;
}

.area-icon-wrapper {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  border-radius: 20px;
  background: linear-gradient(135deg, #D4AF37, #B8860B) !important;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #FFFFFF !important;
  box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
}

.area-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-900);
  margin-bottom: 1rem;
}

.area-description {
  color: var(--gray-600);
  line-height: 1.7;
}

/* ========== SERVICES SECTION ========== */
.services-section {
  padding: 6rem 0;
  background: var(--gray-50);
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 2rem;
}

.service-card {
  background: white;
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}

.service-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
}

.service-icon {
  width: 70px;
  height: 70px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #FFFFFF !important;
  margin-bottom: 1.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.service-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: var(--gray-900);
  margin-bottom: 1rem;
}

.service-description {
  color: var(--gray-600);
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.service-features {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--gray-700);
  font-size: 0.9rem;
}

.feature-item svg {
  color: var(--gold-primary);
  flex-shrink: 0;
}

/* ========== CLINIC SECTION ========== */
.clinic-section {
  padding: 6rem 0;
  background: white;
}

.clinic-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.clinic-description {
  color: var(--gray-700);
  line-height: 1.8;
  font-size: 1.1rem;
  margin-bottom: 2rem;
}

.clinic-features {
  display: grid;
  gap: 1rem;
  margin-bottom: 2rem;
}

.feature-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: rgba(212, 175, 55, 0.05);
  border: 1px solid rgba(212, 175, 55, 0.1);
  border-radius: 12px;
  color: var(--gray-700);
  font-weight: 500;
}

.feature-card svg {
  color: var(--gold-primary);
  flex-shrink: 0;
}

.btn-clinic {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 2rem;
  background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
  color: white;
  border: none;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
}

.btn-clinic:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
}

.clinic-images {
  position: relative;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.clinic-image {
  width: 100%;
  height: 250px;
  object-fit: cover;
  border-radius: 15px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
}

.clinic-image:hover {
  transform: scale(1.05);
}

.clinic-image.main {
  grid-column: 1 / -1;
  height: 350px;
}

/* ========== FINAL CTA ========== */
.final-cta-section {
  padding: 6rem 0;
  background: #1A1A1A !important;
  position: relative;
  overflow: hidden;
}

.final-cta-section::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 30% 50%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 70% 50%, rgba(212, 175, 55, 0.05) 0%, transparent 50%);
}

.cta-container {
  position: relative;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.cta-content {
  text-align: center;
}

.cta-icon {
  margin-bottom: 2rem;
  animation: bounce 2s ease-in-out infinite;
  color: #D4AF37 !important;
}

.cta-title {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: #FFFFFF !important;
}

.cta-description {
  font-size: 1.2rem;
  line-height: 1.8;
  max-width: 700px;
  margin: 0 auto 2.5rem;
  color: rgba(255, 255, 255, 0.9) !important;
}

.cta-buttons-final {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 3rem;
}

.btn-cta-primary, .btn-cta-secondary {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.25rem 2.5rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-cta-primary {
  background: #D4AF37 !important;
  color: #1A1A1A !important;
  box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
  border: 2px solid #D4AF37 !important;
}

.btn-cta-primary:hover {
  background: #B8860B !important;
  transform: translateY(-3px);
  box-shadow: 0 15px 40px rgba(212, 175, 55, 0.5);
}

.btn-cta-secondary {
  background: transparent !important;
  color: #FFFFFF !important;
  border: 2px solid rgba(255, 255, 255, 0.7) !important;
}

.btn-cta-secondary:hover {
  background: rgba(255, 255, 255, 0.1) !important;
  border-color: white !important;
  transform: translateY(-3px);
}

.cta-features {
  display: flex;
  justify-content: center;
  gap: 3rem;
  flex-wrap: wrap;
}

.cta-feature {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: rgba(255, 255, 255, 0.9) !important;
  font-weight: 500;
  font-size: 1rem;
}

.cta-feature svg {
  color: #D4AF37 !important;
}

/* ========== FOOTER ========== */
.footer {
  background: #1A1A1A !important;
  color: #FFFFFF !important;
  padding: 4rem 0 2rem;
}

.footer-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.footer-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 3rem;
  margin-bottom: 2rem;
}

.footer-brand {
  max-width: 300px;
}

.footer-logo {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
}

.footer-logo-image {
  height: 80px;
  width: auto;
  filter: brightness(0) invert(1);
  opacity: 0.9;
  transition: opacity 0.3s ease;
}

.footer-logo-image:hover {
  opacity: 1;
}

.footer-description {
  color: rgba(255, 255, 255, 0.8) !important;
  line-height: 1.7;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
}

.footer-social {
  display: flex;
  gap: 1rem;
}

.social-link {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: rgba(212, 175, 55, 0.1);
  border: 2px solid rgba(212, 175, 55, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #D4AF37 !important;
  transition: all 0.3s ease;
}

.social-link:hover {
  background: #D4AF37 !important;
  border-color: #D4AF37;
  color: #1A1A1A !important;
  transform: translateY(-3px);
}

.footer-section {
  display: flex;
  flex-direction: column;
}

.footer-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #D4AF37 !important;
  margin-bottom: 1.5rem;
  letter-spacing: 0.5px;
}

.footer-contact {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: rgba(255, 255, 255, 0.8) !important;
  font-size: 0.95rem;
  line-height: 1.5;
}

.contact-item svg {
  color: #D4AF37 !important;
  flex-shrink: 0;
}

.contact-item a {
  color: rgba(255, 255, 255, 0.8) !important;
  text-decoration: none;
  transition: color 0.3s ease;
}

.contact-item a:hover {
  color: #D4AF37 !important;
}

.footer-hours {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.hours-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.hours-item .day {
  color: rgba(255, 255, 255, 0.9) !important;
  font-weight: 500;
  font-size: 0.95rem;
}

.hours-item .time {
  color: rgba(255, 255, 255, 0.7) !important;
  font-size: 0.9rem;
}

.footer-map-container {
  max-width: 1000px;
  margin: 3rem auto;
  padding: 0 2rem;
}

.footer-map-container .footer-title {
  margin-bottom: 2rem;
  font-size: 1.8rem;
  color: #D4AF37 !important;
  text-align: center;
}

.footer-map {
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
  border: 3px solid rgba(212, 175, 55, 0.4);
  transition: all 0.3s ease;
}

.footer-map:hover {
  border-color: rgba(212, 175, 55, 0.7);
  box-shadow: 0 20px 60px rgba(212, 175, 55, 0.2);
  transform: translateY(-2px);
}

.footer-bottom {
  text-align: center;
  padding-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.7) !important;
  font-size: 0.9rem;
}

.footer-credit {
  margin-top: 0.5rem;
  color: #F4E4C1 !important;
  font-weight: 500;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 768px) {
  .navbar-container {
    padding: 0 1rem;
  }

  .nav-buttons {
    gap: 0.5rem;
  }

  .btn-login, .btn-register {
    padding: 0.6rem 1rem;
    font-size: 0.85rem;
  }

  .hero-container,
  .doctor-grid,
  .clinic-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }

  .hero-title {
    font-size: 2rem;
  }

  .hero-description {
    font-size: 1rem;
  }

  .credentials-grid {
    grid-template-columns: 1fr;
  }

  .cta-buttons {
    flex-direction: column;
  }

  .btn-primary, .btn-secondary {
    width: 100%;
    justify-content: center;
  }

  .hero-image-wrapper {
    order: -1;
  }

  .section-title {
    font-size: 2rem;
  }

  .areas-grid,
  .services-grid {
    grid-template-columns: 1fr;
  }

  .clinic-images {
    grid-template-columns: 1fr;
  }

  .clinic-image.main {
    grid-column: 1;
  }

  .cta-title {
    font-size: 2rem;
  }

  .cta-features {
    gap: 1.5rem;
  }
}
</style>
