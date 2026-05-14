<template>
  <div class="citas-container">
    <div class="citas-header">
      <h3>{{ paciente?.nombre_completo }}</h3>
      <span class="badge-total">{{ total }} cita(s)</span>
    </div>

    <div class="citas-grid">
      <div
        v-for="(cita, index) in citas"
        :key="cita.id_cita"
        class="cita-card"
        :class="{ 'selected': selectedCita === cita.id_cita }"
        @click="selectCita(cita)"
      >
        <!-- Header de la tarjeta -->
        <div class="card-header">
          <div class="opcion-numero">Opción {{ index + 1 }}</div>
          <span class="badge-estado" :class="`estado-${cita.estado}`">
            {{ formatEstado(cita.estado) }}
          </span>
        </div>

        <!-- Cuerpo de la tarjeta -->
        <div class="card-body">
          <div class="info-row">
            <span class="icon">📅</span>
            <div class="info-content">
              <span class="label">Fecha</span>
              <span class="value">{{ formatFecha(cita.fecha_hora) }}</span>
            </div>
          </div>

          <div class="info-row">
            <span class="icon">🕐</span>
            <div class="info-content">
              <span class="label">Hora</span>
              <span class="value">{{ formatHora(cita.fecha_hora) }}</span>
            </div>
          </div>

          <div class="info-row">
            <span class="icon">👨‍⚕️</span>
            <div class="info-content">
              <span class="label">Doctor(a)</span>
              <span class="value">{{ cita.medico.nombre }}</span>
            </div>
          </div>

          <div class="info-row">
            <span class="icon">🏥</span>
            <div class="info-content">
              <span class="label">Especialidad</span>
              <span class="value">{{ cita.medico.especialidad }}</span>
            </div>
          </div>

          <div class="info-row">
            <span class="icon">📋</span>
            <div class="info-content">
              <span class="label">Motivo</span>
              <span class="value">{{ cita.motivo }}</span>
            </div>
          </div>
        </div>

        <!-- Botón de acción -->
        <button
          v-if="accion === 'cancelar'"
          class="btn-accion btn-cancelar"
          @click.stop="confirmarCancelacion(cita)"
        >
          <span class="btn-icon">❌</span>
          Cancelar esta cita
        </button>
      </div>
    </div>

    <!-- Instrucciones alternativas -->
    <div class="instrucciones">
      <p>💡 También puedes escribir el número de la opción (1, 2, 3...)</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Cita {
  id_cita: number
  fecha_hora: string
  medico: {
    nombre: string
    especialidad: string
  }
  motivo: string
  estado: string
}

interface Props {
  citas: Cita[]
  paciente?: {
    nombre_completo: string
  }
  total: number
  accion: string
  dni: string
}

const props = defineProps<Props>()
const emit = defineEmits(['cancelar', 'seleccionar'])

const selectedCita = ref<number | null>(null)

const formatFecha = (fechaHora: string): string => {
  try {
    const [fecha] = fechaHora.split(' ')
    if (!fecha) return fechaHora
    const [year, month, day] = fecha.split('-')
    if (!year || !month || !day) return fechaHora
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
    return `${day} ${meses[parseInt(month) - 1]} ${year}`
  } catch {
    return fechaHora
  }
}

const formatHora = (fechaHora: string): string => {
  try {
    const [, hora] = fechaHora.split(' ')
    if (!hora) return ''
    const [h, m] = hora.split(':')
    if (!h || !m) return hora
    const hour = parseInt(h)
    const ampm = hour >= 12 ? 'PM' : 'AM'
    const hour12 = hour % 12 || 12
    return `${hour12}:${m} ${ampm}`
  } catch {
    return fechaHora
  }
}

const formatEstado = (estado: string): string => {
  const estados: Record<string, string> = {
    'pendiente': 'Pendiente',
    'confirmado': 'Confirmado',
    'en_espera': 'En Espera',
    'completado': 'Completado',
    'cancelado': 'Cancelado'
  }
  return estados[estado] || estado
}

const selectCita = (cita: Cita) => {
  selectedCita.value = cita.id_cita
  emit('seleccionar', cita)
}

const confirmarCancelacion = (cita: Cita) => {
  if (confirm(`¿Estás seguro que deseas cancelar la cita del ${formatFecha(cita.fecha_hora)} a las ${formatHora(cita.fecha_hora)}?`)) {
    emit('cancelar', {
      dni: props.dni,
      id_cita: cita.id_cita
    })
  }
}
</script>

<style scoped>
.citas-container {
  padding: 1rem;
  background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
  border-radius: 12px;
  margin: 0.5rem 0;
}

.citas-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e0e6ed;
}

.citas-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.2rem;
  font-weight: 600;
}

.badge-total {
  background: #667eea;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
}

.citas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.cita-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: pointer;
  border: 2px solid transparent;
}

.cita-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
  border-color: #667eea;
}

.cita-card.selected {
  border-color: #667eea;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.opcion-numero {
  font-weight: 600;
  font-size: 0.9rem;
}

.badge-estado {
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.estado-pendiente {
  background: #ffeaa7;
  color: #2d3436;
}

.estado-confirmado {
  background: #55efc4;
  color: #00b894;
}

.estado-en_espera {
  background: #a29bfe;
  color: #6c5ce7;
}

.card-body {
  padding: 1rem;
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.info-row:last-child {
  margin-bottom: 0;
}

.info-row .icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.info-content {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  flex: 1;
}

.info-content .label {
  font-size: 0.75rem;
  color: #95a5a6;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-content .value {
  font-size: 0.95rem;
  color: #2c3e50;
  font-weight: 500;
}

.btn-accion {
  width: 100%;
  padding: 0.75rem;
  border: none;
  border-radius: 0 0 12px 12px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-cancelar {
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
  color: white;
}

.btn-cancelar:hover {
  background: linear-gradient(135deg, #ee5a6f 0%, #ff6b6b 100%);
  transform: scale(1.02);
}

.btn-icon {
  font-size: 1.1rem;
}

.instrucciones {
  text-align: center;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px dashed #dfe6e9;
}

.instrucciones p {
  margin: 0;
  color: #636e72;
  font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 768px) {
  .citas-grid {
    grid-template-columns: 1fr;
  }

  .citas-container {
    padding: 0.75rem;
  }
}
</style>
