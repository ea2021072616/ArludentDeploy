<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header Mejorado -->
      <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg p-6 text-white">
        <button @click="volver" class="text-blue-100 hover:text-white mb-3 flex items-center transition-colors group">
          <ArrowLeft class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" />
          Volver a Historial Clínico
        </button>
        <div class="flex justify-between items-start">
          <div>
            <h1 class="text-3xl font-bold mb-2">Historial Clínico</h1>
            <div v-if="historial" class="flex items-center space-x-4">
              <div class="flex items-center space-x-2">
                <User class="w-5 h-5 text-blue-200" />
                <span class="text-lg font-medium">
                  {{ historial.paciente.nombres }} {{ historial.paciente.apellidos }}
                </span>
              </div>
              <div class="flex items-center space-x-2 bg-white/20 px-3 py-1 rounded-lg">
                <FileText class="w-4 h-4 text-blue-200" />
                <span class="text-sm font-mono">{{ historial.codigo_historial }}</span>
              </div>
            </div>
          </div>
          <div v-if="historial" class="flex space-x-2">
            <Button variant="secondary" class="bg-white text-blue-600 hover:bg-blue-50" size="sm" @click="imprimirHistorial">
              <Printer class="w-4 h-4 mr-2" />
              Imprimir
            </Button>
          </div>
        </div>
      </div>

      <!-- Tabs Mejorados -->
      <Card v-if="historial" class="overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50">
          <nav class="flex space-x-1 p-2">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="tabActivo = tab.id"
              :class="[
                'flex-1 py-3 px-4 rounded-lg font-medium text-sm transition-all duration-200 flex items-center justify-center space-x-2',
                tabActivo === tab.id
                  ? 'bg-white text-blue-600 shadow-md ring-2 ring-blue-500/20 transform scale-105'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'
              ]"
            >
              <component :is="tab.icon" :class="['w-5 h-5', tabActivo === tab.id ? 'text-blue-600' : 'text-gray-400']" />
              <span>{{ tab.label }}</span>
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
          <!-- TAB 1: Información General -->
          <div v-if="tabActivo === 'info'" class="space-y-6">

            <!-- 🩵 FASE I. DATOS PERSONALES -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
              <div class="bg-gradient-to-r from-blue-50 to-cyan-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="bg-cyan-600 p-2 rounded-lg">
                      <User class="w-5 h-5 text-white" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">FASE I. DATOS PERSONALES</h3>
                  </div>
                  <Button v-if="!editandoPaciente" variant="primary" size="sm" @click="editandoPaciente = true">
                    <Edit2 class="w-4 h-4 mr-1" />
                    Editar
                  </Button>
                  <div v-else class="flex space-x-2">
                    <Button
                      @click="guardarDatosPaciente"
                      :disabled="guardandoPaciente"
                      :loading="guardandoPaciente"
                      variant="primary"
                      size="sm"
                    >
                      <Save class="w-4 h-4 mr-1" />
                      {{ guardandoPaciente ? 'Guardando...' : 'Guardar' }}
                    </Button>
                    <Button
                      @click="cancelarEdicionPaciente"
                      :disabled="guardandoPaciente"
                      variant="secondary"
                      size="sm"
                    >
                      Cancelar
                    </Button>
                  </div>
                </div>
              </div>

              <div v-if="!editandoPaciente" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nombres</label>
                    <p class="text-gray-900 font-medium">{{ historial.paciente.nombres }}</p>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Apellidos</label>
                    <p class="text-gray-900 font-medium">{{ historial.paciente.apellidos }}</p>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">DNI</label>
                    <p class="text-gray-900 font-medium">{{ historial.paciente.dni || 'No especificado' }}</p>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha de Nacimiento</label>
                    <p class="text-gray-900 font-medium">{{ formatDate(historial.paciente.fecha_nacimiento) || 'No especificado' }}</p>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Sexo</label>
                    <p class="text-gray-900 font-medium">{{ historial.paciente.sexo || 'No especificado' }}</p>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Domicilio</label>
                    <p class="text-gray-900 font-medium">{{ historial.paciente.domicilio || 'No especificado' }}</p>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Persona Responsable</label>
                    <p class="text-gray-900 font-medium">{{ historial.paciente.persona_responsable || 'No especificado' }}</p>
                  </div>
                  <div class="space-y-1 bg-blue-50 p-3 rounded-lg border border-blue-200">
                    <label class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Teléfono Principal (Paciente)</label>
                    <p class="text-blue-900 font-semibold">{{ historial.paciente.telefono || 'No especificado' }}</p>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Teléfono de Contacto (Responsable)</label>
                    <p class="text-gray-900 font-medium">{{ historial.paciente.telefono_responsable || 'No especificado' }}</p>
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Grupo Sanguíneo</label>
                    <p class="text-gray-900 font-medium">{{ historial.paciente.grupo_sanguineo || 'No especificado' }}</p>
                  </div>
                  <div class="md:col-span-3 space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Alergias</label>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                      <p class="text-red-900 font-medium">{{ historial.paciente.alergias || 'No especificado' }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Formulario Editar Paciente -->
              <div v-else class="bg-blue-50 p-4 rounded-lg space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <InputField v-model="formPaciente.nombres" label="Nombres" />
                  <InputField v-model="formPaciente.apellidos" label="Apellidos" />
                  <InputField v-model="formPaciente.dni" label="DNI" />
                  <InputField v-model="formPaciente.fecha_nacimiento" label="Fecha Nacimiento" type="date" />
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                    <select v-model="formPaciente.sexo" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                      <option value="">Seleccionar</option>
                      <option value="M">Masculino</option>
                      <option value="F">Femenino</option>
                      <option value="Otro">Otro</option>
                    </select>
                  </div>
                  <InputField v-model="formPaciente.domicilio" label="Domicilio" class="md:col-span-2" />
                  <InputField v-model="formPaciente.persona_responsable" label="Persona Responsable" />
                  <div class="bg-blue-100 p-3 rounded-lg border-2 border-blue-300">
                    <InputField v-model="formPaciente.telefono" label="Teléfono Principal (Paciente)" />
                  </div>
                  <InputField v-model="formPaciente.telefono_responsable" label="Teléfono de Contacto (Responsable)" />
                  <InputField v-model="formPaciente.grupo_sanguineo" label="Grupo Sanguíneo" />
                  <InputField v-model="formPaciente.alergias" label="Alergias" class="md:col-span-3" type="textarea" rows="2" />
                </div>
                <div class="flex justify-end space-x-2">
                  <Button variant="secondary" @click="cancelarEdicionPaciente">Cancelar</Button>
                  <Button variant="primary" @click="guardarDatosPaciente" :loading="guardandoPaciente">
                    <Save class="w-4 h-4 mr-1" />
                    Guardar Cambios
                  </Button>
                </div>
              </div>
            </div>

            <!-- 🩶 FASE II. MOTIVO DE CONSULTA E HISTORIA DE LA ENFERMEDAD -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
              <div class="bg-gradient-to-r from-gray-50 to-slate-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="bg-gray-600 p-2 rounded-lg">
                      <FileText class="w-5 h-5 text-white" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">FASE II. MOTIVO DE CONSULTA E HISTORIA DE LA ENFERMEDAD</h3>
                  </div>
                  <Button
                    v-if="!editandoFaseII"
                    @click="editandoFaseII = true"
                    variant="primary"
                    size="sm"
                  >
                    <Edit2 class="w-4 h-4 mr-1" />
                    Editar
                  </Button>
                  <div v-else class="flex space-x-2">
                    <Button
                      @click="guardarFaseII"
                      :disabled="guardandoHistorial"
                      :loading="guardandoHistorial"
                      variant="primary"
                      size="sm"
                    >
                      <Save class="w-4 h-4 mr-1" />
                      {{ guardandoHistorial ? 'Guardando...' : 'Guardar' }}
                    </Button>
                    <Button
                      @click="cancelarEdicionFaseII"
                      :disabled="guardandoHistorial"
                      variant="secondary"
                      size="sm"
                    >
                      Cancelar
                    </Button>
                  </div>
                </div>
              </div>

              <div class="p-6">
                <!-- Modo Vista -->
                <div v-if="!editandoFaseII" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="bg-blue-50 p-4 rounded-xl border border-blue-200 md:col-span-2">
                    <label class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-2 block">Motivo de Consulta</label>
                    <p class="text-gray-900 font-medium">{{ historial.motivo_consulta || 'No especificado' }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 block">Síntoma Principal</label>
                    <p class="text-gray-900 font-medium">{{ historial.sintoma_principal || 'No especificado' }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 block">Tiempo y Forma de Inicio</label>
                    <p class="text-gray-900 font-medium">{{ historial.tiempo_inicio_sintomas || 'No especificado' }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 md:col-span-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 block">Tratamiento Recibido Previamente</label>
                    <p class="text-gray-900 font-medium">{{ historial.tratamiento_previo || 'No especificado' }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 md:col-span-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 block">Enfermedades Actuales</label>
                    <p class="text-gray-900 font-medium">{{ historial.enfermedades_actuales || 'No especificado' }}</p>
                  </div>
                </div>

                <!-- Modo Edición -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Síntoma Principal</label>
                    <textarea
                      v-model="formHistorial.sintoma_principal"
                      rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Describa el síntoma principal"
                    ></textarea>
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tiempo y Forma de Inicio de Síntomas</label>
                    <input
                      v-model="formHistorial.tiempo_inicio_sintomas"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Ej: Hace 2 semanas, de forma gradual"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tratamiento Recibido Previamente</label>
                    <textarea
                      v-model="formHistorial.tratamiento_previo"
                      rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Describa tratamientos previos relacionados con el motivo de consulta"
                    ></textarea>
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Enfermedades Actuales</label>
                    <textarea
                      v-model="formHistorial.enfermedades_actuales"
                      rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Liste enfermedades actuales del paciente"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- 💜 FASE III. ANTECEDENTES MÉDICOS Y ODONTOLÓGICOS -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
              <div class="bg-gradient-to-r from-purple-50 to-violet-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="bg-purple-600 p-2 rounded-lg">
                      <ClipboardList class="w-5 h-5 text-white" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">FASE III. ANTECEDENTES MÉDICOS Y ODONTOLÓGICOS</h3>
                  </div>
                  <Button
                    v-if="!editandoFaseIII"
                    @click="editandoFaseIII = true"
                    variant="primary"
                    size="sm"
                  >
                    <Edit2 class="w-4 h-4 mr-1" />
                    Editar
                  </Button>
                  <div v-else class="flex space-x-2">
                    <Button
                      @click="guardarFaseIII"
                      :disabled="guardandoHistorial"
                      :loading="guardandoHistorial"
                      variant="primary"
                      size="sm"
                    >
                      <Save class="w-4 h-4 mr-1" />
                      {{ guardandoHistorial ? 'Guardando...' : 'Guardar' }}
                    </Button>
                    <Button
                      @click="cancelarEdicionFaseIII"
                      :disabled="guardandoHistorial"
                      variant="secondary"
                      size="sm"
                    >
                      Cancelar
                    </Button>
                  </div>
                </div>
              </div>

              <div class="p-6">
                <!-- Modo Vista -->
                <div v-if="!editandoFaseIII" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="bg-blue-50 p-4 rounded-xl border border-blue-200">
                    <label class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-2 block">¿Está bajo tratamiento médico actualmente?</label>
                    <p class="text-gray-900 font-semibold">{{ historial.bajo_tratamiento_medico ? 'Sí' : 'No' }}</p>
                    <p v-if="historial.bajo_tratamiento_medico && historial.detalle_tratamiento_actual" class="text-sm text-gray-700 mt-2 italic">
                      <span class="font-semibold">Detalle:</span> {{ historial.detalle_tratamiento_actual }}
                    </p>
                  </div>
                  <div class="bg-red-50 p-4 rounded-xl border border-red-200">
                    <label class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-2 block">Alergias del Paciente</label>
                    <p class="text-red-900 font-semibold">{{ historial.alergias_paciente || 'No especificado' }}</p>
                  </div>
                  <div class="bg-amber-50 p-4 rounded-xl border border-amber-200 md:col-span-2">
                    <label class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-2 block">¿Ha tenido intervenciones quirúrgicas previas?</label>
                    <p class="text-gray-900 font-semibold">{{ historial.intervenciones_quirurgicas_previas ? 'Sí' : 'No' }}</p>
                    <p v-if="historial.intervenciones_quirurgicas_previas && historial.detalle_intervenciones" class="text-sm text-gray-700 mt-2 italic">
                      <span class="font-semibold">Detalle:</span> {{ historial.detalle_intervenciones }}
                    </p>
                  </div>

                  <div class="bg-purple-50 p-4 rounded-xl border border-purple-200 md:col-span-2">
                    <label class="text-xs font-semibold text-purple-700 uppercase tracking-wide mb-3 block">Antecedentes Odontológicos</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                      <div class="flex items-center space-x-2 bg-white p-3 rounded-lg border border-purple-100">
                        <input type="checkbox" :checked="historial.hemorragia_post_tratamiento" disabled class="rounded text-purple-600" />
                        <span class="text-sm text-gray-700 font-medium">¿Ha experimentado hemorragia después de tratamiento dental?</span>
                      </div>
                      <div class="flex items-center space-x-2 bg-white p-3 rounded-lg border border-purple-100">
                        <input type="checkbox" :checked="historial.problema_anestesia" disabled class="rounded text-purple-600" />
                        <span class="text-sm text-gray-700 font-medium">¿Ha tenido problemas con la anestesia dental?</span>
                      </div>
                      <div class="flex items-center space-x-2 bg-white p-3 rounded-lg border border-purple-100">
                        <input type="checkbox" :checked="historial.dificultad_abrir_masticar" disabled class="rounded text-purple-600" />
                        <span class="text-sm text-gray-700 font-medium">¿Tiene dificultad para abrir la boca o masticar?</span>
                      </div>
                      <div class="flex items-center space-x-2 bg-white p-3 rounded-lg border border-purple-100">
                        <input type="checkbox" :checked="historial.sensibilidad_dental" disabled class="rounded text-purple-600" />
                        <span class="text-sm text-gray-700 font-medium">¿Presenta sensibilidad dental?</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Modo Edición -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="md:col-span-2">
                    <label class="flex items-center space-x-2 text-sm font-medium text-gray-700 mb-2">
                      <input
                        v-model="formHistorial.bajo_tratamiento_medico"
                        type="checkbox"
                        class="rounded text-blue-600 focus:ring-2 focus:ring-blue-500"
                      />
                      <span>¿Está bajo tratamiento médico actualmente?</span>
                    </label>
                    <textarea
                      v-if="formHistorial.bajo_tratamiento_medico"
                      v-model="formHistorial.detalle_tratamiento_actual"
                      rows="2"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent mt-2"
                      placeholder="Especifique el tratamiento médico actual"
                    ></textarea>
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alergias del Paciente</label>
                    <textarea
                      v-model="formHistorial.alergias_paciente"
                      rows="3"
                      class="w-full px-4 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent bg-red-50"
                      placeholder="Liste las alergias del paciente (medicamentos, alimentos, etc.)"
                    ></textarea>
                  </div>

                  <div class="md:col-span-2">
                    <label class="flex items-center space-x-2 text-sm font-medium text-gray-700 mb-2">
                      <input
                        v-model="formHistorial.intervenciones_quirurgicas_previas"
                        type="checkbox"
                        class="rounded text-amber-600 focus:ring-2 focus:ring-amber-500"
                      />
                      <span>¿Ha tenido intervenciones quirúrgicas previas?</span>
                    </label>
                    <textarea
                      v-if="formHistorial.intervenciones_quirurgicas_previas"
                      v-model="formHistorial.detalle_intervenciones"
                      rows="2"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent mt-2"
                      placeholder="Describa las intervenciones quirúrgicas previas"
                    ></textarea>
                  </div>

                  <div class="md:col-span-2 bg-purple-50 p-4 rounded-lg border border-purple-200">
                    <label class="block text-sm font-semibold text-purple-700 mb-3">Antecedentes Odontológicos</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                      <label class="flex items-center space-x-2 bg-white p-3 rounded-lg border border-purple-100 cursor-pointer hover:bg-purple-50">
                        <input
                          v-model="formHistorial.hemorragia_post_tratamiento"
                          type="checkbox"
                          class="rounded text-purple-600 focus:ring-2 focus:ring-purple-500"
                        />
                        <span class="text-sm text-gray-700">¿Ha experimentado hemorragia después de tratamiento dental?</span>
                      </label>
                      <label class="flex items-center space-x-2 bg-white p-3 rounded-lg border border-purple-100 cursor-pointer hover:bg-purple-50">
                        <input
                          v-model="formHistorial.problema_anestesia"
                          type="checkbox"
                          class="rounded text-purple-600 focus:ring-2 focus:ring-purple-500"
                        />
                        <span class="text-sm text-gray-700">¿Ha tenido problemas con la anestesia dental?</span>
                      </label>
                      <label class="flex items-center space-x-2 bg-white p-3 rounded-lg border border-purple-100 cursor-pointer hover:bg-purple-50">
                        <input
                          v-model="formHistorial.dificultad_abrir_masticar"
                          type="checkbox"
                          class="rounded text-purple-600 focus:ring-2 focus:ring-purple-500"
                        />
                        <span class="text-sm text-gray-700">¿Tiene dificultad para abrir la boca o masticar?</span>
                      </label>
                      <label class="flex items-center space-x-2 bg-white p-3 rounded-lg border border-purple-100 cursor-pointer hover:bg-purple-50">
                        <input
                          v-model="formHistorial.sensibilidad_dental"
                          type="checkbox"
                          class="rounded text-purple-600 focus:ring-2 focus:ring-purple-500"
                        />
                        <span class="text-sm text-gray-700">¿Presenta sensibilidad dental?</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- TAB 2: Odontograma -->
          <div v-if="tabActivo === 'odontograma'" class="space-y-6">
            <!-- Odontograma para médicos/admin (interactivo) -->
            <OdontogramaInteractivo
              v-if="historial && (esRolMedico || esRolAdmin)"
              :idHistorial="historial.id_historial"
              :piezasDentales="historial.odontograma || []"
              @actualizado="cargarHistorial"
            />

            <!-- Odontograma para pacientes (solo visualización) -->
            <OdontogramaVisualizacion
              v-else-if="historial"
              :piezasDentales="historial.odontograma || []"
            />

            <!-- Diagnósticos y Evaluación (debajo del odontograma) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-6">
              <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 flex justify-between items-center border-b border-gray-200">
                <div class="flex items-center space-x-3">
                  <div class="bg-indigo-600 p-2 rounded-lg">
                    <FileText class="w-5 h-5 text-white" />
                  </div>
                  <h3 class="text-lg font-bold text-gray-900">Diagnóstico y Evaluación Dental</h3>
                </div>
                <Button
                  v-if="!modoEdicion && (esRolMedico || esRolAdmin)"
                  variant="secondary"
                  size="sm"
                  @click="modoEdicion = true"
                >
                  <Edit class="w-4 h-4 mr-2" />
                  Editar
                </Button>
              </div>

              <!-- Modo Visualización -->
              <div v-if="!modoEdicion" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="bg-blue-50 p-4 rounded-xl border border-blue-200">
                    <label class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-2 block">Diagnóstico Presuntivo</label>
                    <p class="text-gray-900 font-medium">{{ historial.diagnostico_presuntivo || 'No especificado' }}</p>
                  </div>

                  <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-200">
                    <label class="text-xs font-semibold text-indigo-700 uppercase tracking-wide mb-2 block">Diagnóstico Principal</label>
                    <p class="text-gray-900 font-medium">{{ historial.diagnostico_principal || 'No especificado' }}</p>
                  </div>

                  <div class="bg-green-50 p-4 rounded-xl border border-green-200">
                    <label class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-2 block">Higiene Bucal</label>
                    <p class="text-gray-900 font-semibold">
                      <span v-if="historial.higiene_bucal === 'Bueno'" class="text-green-700">✓ Bueno</span>
                      <span v-else-if="historial.higiene_bucal === 'Regular'" class="text-yellow-700">◐ Regular</span>
                      <span v-else-if="historial.higiene_bucal === 'Malo'" class="text-red-700">✗ Malo</span>
                      <span v-else class="text-gray-500">No especificado</span>
                    </p>
                  </div>
                </div>
              </div>

              <!-- Modo Edición -->
              <div v-else class="p-6 bg-blue-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Diagnóstico Presuntivo</label>
                    <textarea
                      v-model="formHistorial.diagnostico_presuntivo"
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="Diagnóstico inicial basado en la observación del odontograma..."
                    ></textarea>
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Diagnóstico Principal</label>
                    <textarea
                      v-model="formHistorial.diagnostico_principal"
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="Diagnóstico definitivo después del análisis completo..."
                    ></textarea>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Higiene Bucal</label>
                    <select
                      v-model="formHistorial.higiene_bucal"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Seleccionar</option>
                      <option value="Bueno">Bueno</option>
                      <option value="Regular">Regular</option>
                      <option value="Malo">Malo</option>
                    </select>
                  </div>
                </div>

                <div class="flex justify-end space-x-2 mt-6">
                  <Button variant="secondary" @click="cancelarEdicionHistorial">
                    Cancelar
                  </Button>
                  <Button variant="primary" @click="guardarHistorial" :loading="guardandoHistorial">
                    <Save class="w-4 h-4 mr-1" />
                    Guardar Cambios
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 3: Tratamientos -->
          <div v-if="tabActivo === 'tratamientos'" class="space-y-4">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-900">Tratamientos Registrados</h3>
              <Button variant="primary" size="sm" @click="abrirModalNuevoTratamiento">
                <Plus class="w-4 h-4 mr-1" />
                Agregar Tratamiento
              </Button>
            </div>

            <div v-if="tratamientosOrdenados && tratamientosOrdenados.length > 0" class="space-y-3">
              <div v-for="tratamiento in tratamientosOrdenados" :key="tratamiento.id"
                   class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">

                <!-- Header del Tratamiento -->
                <div class="p-4 cursor-pointer" @click="toggleTratamiento(tratamiento.id)">
                  <div class="flex justify-between items-start">
                    <div class="flex-1">
                      <div class="flex items-center space-x-2">
                        <h4 class="font-semibold text-gray-900">{{ tratamiento.tratamiento.nombre }}</h4>
                        <!-- Indicador de seguimientos -->
                        <span v-if="tratamiento.seguimientos && tratamiento.seguimientos.length > 0"
                              class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">
                          {{ tratamiento.seguimientos.length }} {{ tratamiento.seguimientos.length === 1 ? 'control' : 'controles' }}
                        </span>
                      </div>
                      <p v-if="tratamiento.descripcion" class="text-sm text-gray-600 mt-1">{{ tratamiento.descripcion }}</p>
                      <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                        <span v-if="tratamiento.fecha_inicio">
                          <Calendar class="w-4 h-4 inline mr-1" />
                          Inicio: {{ formatDate(tratamiento.fecha_inicio) }}
                        </span>
                        <span v-if="tratamiento.fecha_fin">
                          <Calendar class="w-4 h-4 inline mr-1" />
                          Fin: {{ formatDate(tratamiento.fecha_fin) }}
                        </span>
                        <span v-if="tratamiento.precio" class="text-green-600 font-medium">
                          S/ {{ tratamiento.precio }}
                        </span>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
                      <span :class="[
                        'px-3 py-1 text-xs font-semibold rounded-full',
                        {
                          'bg-yellow-100 text-yellow-800': tratamiento.estado === 'sugerido',
                          'bg-blue-100 text-blue-800': tratamiento.estado === 'en_curso',
                          'bg-green-100 text-green-800': tratamiento.estado === 'completado',
                          'bg-red-100 text-red-800': tratamiento.estado === 'cancelado'
                        }
                      ]">
                        {{ estadoTratamientoLabel(tratamiento.estado) }}
                      </span>
                      <!-- Icono expandir/colapsar -->
                      <svg
                        :class="['w-5 h-5 text-gray-400 transition-transform', tratamientoExpandido === tratamiento.id ? 'rotate-180' : '']"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </div>
                  </div>
                </div>

                <!-- Seguimientos (expandible) -->
                <div v-if="tratamientoExpandido === tratamiento.id" class="border-t border-gray-200 bg-gray-50 p-4">
                  <div class="flex justify-between items-center mb-4">
                    <h5 class="text-sm font-semibold text-gray-700">Historial de Controles</h5>
                    <Button variant="secondary" size="sm" @click.stop="agregarSeguimiento(tratamiento.id)">
                      <Plus class="w-3 h-3 mr-1" />
                      Agregar Control
                    </Button>
                  </div>

                  <!-- Lista de seguimientos -->
                  <div v-if="tratamiento.seguimientos && tratamiento.seguimientos.length > 0" class="space-y-3">
                    <div v-for="(seguimiento, index) in tratamiento.seguimientos" :key="seguimiento.id_seguimiento"
                         class="bg-white rounded-lg p-3 border border-gray-200">
                      <div class="flex items-start space-x-3">
                        <!-- Timeline dot -->
                        <div class="flex flex-col items-center">
                          <div class="w-3 h-3 rounded-full bg-blue-500 ring-4 ring-blue-100"></div>
                          <div v-if="index < tratamiento.seguimientos.length - 1" class="w-0.5 h-full bg-gray-300 mt-1"></div>
                        </div>

                        <!-- Contenido del seguimiento -->
                        <div class="flex-1">
                          <div class="flex justify-between items-start mb-1">
                            <span class="text-sm font-medium text-gray-900">
                              Control {{ tratamiento.seguimientos.length - index }}
                            </span>
                            <span class="text-xs text-gray-500">
                              {{ formatDate(seguimiento.fecha_registro) }}
                            </span>
                          </div>
                          <p v-if="seguimiento.descripcion" class="text-sm text-gray-600 mb-2">
                            {{ seguimiento.descripcion }}
                          </p>
                          <div class="flex items-center space-x-4 text-xs text-gray-500">
                            <span v-if="seguimiento.duracion_restante !== null" class="flex items-center">
                              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>
                              Quedan {{ seguimiento.duracion_restante }} {{ seguimiento.duracion_restante === 1 ? 'mes' : 'meses' }}
                            </span>
                            <span v-if="seguimiento.registradoPor" class="flex items-center">
                              <User class="w-4 h-4 mr-1" />
                              Dr. {{ seguimiento.registradoPor.nombres }} {{ seguimiento.registradoPor.apellidos }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Sin seguimientos -->
                  <div v-else class="text-center py-6">
                    <ClipboardList class="w-12 h-12 text-gray-300 mx-auto mb-2" />
                    <p class="text-sm text-gray-500">No hay controles registrados</p>
                    <p class="text-xs text-gray-400">Agrega el primer control de este tratamiento</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
              <Stethoscope class="w-16 h-16 text-gray-400 mx-auto mb-4" />
              <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay tratamientos registrados</h3>
              <p class="text-gray-600">Agrega el primer tratamiento para este paciente</p>
            </div>
          </div>

          <!-- TAB 4: Consultas y Controles -->
          <div v-if="tabActivo === 'consultas'" class="space-y-4">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-900">Historial de Consultas y Controles</h3>
              <div class="flex space-x-2">
                <Button variant="primary" size="sm" @click="abrirModalNuevaConsulta">
                  <Plus class="w-4 h-4 mr-1" />
                  Nueva Consulta
                </Button>
              </div>
            </div>

            <div v-if="consultasOrdenadas && consultasOrdenadas.length > 0" class="space-y-4">
              <div v-for="detalle in consultasOrdenadas" :key="detalle.id_detalle"
                   class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-start mb-3">
                  <div class="flex items-center space-x-2">
                    <Calendar class="w-5 h-5 text-gray-400" />
                    <span class="font-semibold text-gray-900">{{ formatDate(detalle.fecha) }}</span>
                  </div>
                  <span v-if="detalle.realizadoPor" class="text-sm text-gray-500">
                    Dr. {{ detalle.realizadoPor.nombres }} {{ detalle.realizadoPor.apellidos }}
                  </span>
                </div>
                <div v-if="detalle.diagnostico" class="mb-2">
                  <label class="text-sm font-medium text-gray-600">Diagnóstico:</label>
                  <p class="text-gray-900">{{ detalle.diagnostico }}</p>
                </div>
                <div v-if="detalle.procedimiento_realizado" class="mb-2">
                  <label class="text-sm font-medium text-gray-600">Procedimiento:</label>
                  <p class="text-gray-900">{{ detalle.procedimiento_realizado }}</p>
                </div>
                <div v-if="detalle.notas_medicas">
                  <label class="text-sm font-medium text-gray-600">Notas Médicas:</label>
                  <p class="text-gray-700">{{ detalle.notas_medicas }}</p>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
              <ClipboardList class="w-16 h-16 text-gray-400 mx-auto mb-4" />
              <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay consultas o controles registrados</h3>
              <p class="text-gray-600">Agrega detalles de consultas, controles y notas médicas</p>
            </div>
          </div>

          <!-- TAB 5: Prescripciones -->
          <div v-if="tabActivo === 'prescripciones'" class="space-y-4">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-900">Prescripciones Médicas</h3>
              <Button variant="primary" size="sm" @click="abrirModalNuevaPrescripcion">
                <Plus class="w-4 h-4 mr-1" />
                Nueva Prescripción
              </Button>
            </div>

            <div v-if="prescripcionesOrdenadas && prescripcionesOrdenadas.length > 0" class="space-y-3">
              <div v-for="prescripcion in prescripcionesOrdenadas" :key="prescripcion.id_prescripcion"
                   class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-semibold text-gray-900">{{ prescripcion.medicamento }}</h4>
                  <span class="text-sm text-gray-500">{{ formatDate(prescripcion.fecha_prescripcion) }}</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                  <div v-if="prescripcion.dosis">
                    <span class="font-medium text-gray-600">Dosis:</span>
                    <p class="text-gray-900">{{ prescripcion.dosis }}</p>
                  </div>
                  <div v-if="prescripcion.frecuencia">
                    <span class="font-medium text-gray-600">Frecuencia:</span>
                    <p class="text-gray-900">{{ prescripcion.frecuencia }}</p>
                  </div>
                  <div v-if="prescripcion.duracion">
                    <span class="font-medium text-gray-600">Duración:</span>
                    <p class="text-gray-900">{{ prescripcion.duracion }}</p>
                  </div>
                </div>
                <div v-if="prescripcion.indicaciones" class="mt-2">
                  <span class="font-medium text-gray-600 text-sm">Indicaciones:</span>
                  <p class="text-gray-700 text-sm mt-1">{{ prescripcion.indicaciones }}</p>
                </div>
                <div v-if="prescripcion.prescritoPor" class="mt-2 text-sm text-gray-500">
                  Prescrito por: Dr. {{ prescripcion.prescritoPor.nombres }} {{ prescripcion.prescritoPor.apellidos }}
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
              <Pill class="w-16 h-16 text-gray-400 mx-auto mb-4" />
              <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay prescripciones</h3>
              <p class="text-gray-600">Registra medicamentos recetados al paciente</p>
            </div>
          </div>
        </div>
      </Card>

      <!-- Loading -->
      <Card v-else-if="loading">
        <div class="flex justify-center py-12">
          <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Cargando historial...</p>
          </div>
        </div>
      </Card>

      <!-- Error -->
      <Card v-else>
        <div class="text-center py-12">
          <p class="text-gray-600">No se pudo cargar el historial clínico</p>
        </div>
      </Card>
    </div>

    <!-- Modal: Nueva Consulta / Control -->
    <Modal
      :show="mostrarModalConsulta"
      title="Nueva Consulta / Nota Médica"
      size="2xl"
      @close="mostrarModalConsulta = false"
    >
      <div class="space-y-5">
        <!-- Banner informativo -->
        <div class="flex items-start space-x-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
          <ClipboardList class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" />
          <div>
            <p class="font-medium text-blue-900">Registro de Consulta Médica</p>
            <p class="text-sm text-blue-700 mt-1">
              Documente los detalles de la consulta, diagnóstico y procedimientos realizados.
            </p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Fecha de Consulta *
          </label>
          <input
            v-model="formConsulta.fecha"
            type="date"
            required
            :max="new Date().toISOString().split('T')[0]"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
          />
          <p class="text-xs text-gray-500 mt-1">
            Solo se permiten fechas hasta hoy
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Diagnóstico
          </label>
          <textarea
            v-model="formConsulta.diagnostico"
            rows="3"
            maxlength="500"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
            placeholder="Describa el diagnóstico de la consulta..."
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">
            {{ formConsulta.diagnostico?.length || 0 }}/500 caracteres
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Procedimiento Realizado
          </label>
          <textarea
            v-model="formConsulta.procedimiento_realizado"
            rows="3"
            maxlength="500"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
            placeholder="Describa los procedimientos realizados durante la consulta..."
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">
            {{ formConsulta.procedimiento_realizado?.length || 0 }}/500 caracteres
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Notas Médicas
          </label>
          <textarea
            v-model="formConsulta.notas_medicas"
            rows="4"
            maxlength="1000"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
            placeholder="Observaciones, notas adicionales e indicaciones para el paciente..."
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">
            {{ formConsulta.notas_medicas?.length || 0 }}/1000 caracteres
          </p>
        </div>
      </div>

      <template #footer>
        <Button
          variant="secondary"
          @click="mostrarModalConsulta = false"
          :disabled="guardandoConsulta"
        >
          Cancelar
        </Button>
        <Button
          variant="primary"
          @click="guardarConsulta"
          :disabled="guardandoConsulta || !formConsulta.fecha"
          :loading="guardandoConsulta"
        >
          {{ guardandoConsulta ? 'Guardando...' : 'Guardar Consulta' }}
        </Button>
      </template>
    </Modal>

    <!-- Modal: Nuevo Tratamiento -->
    <Modal
      :show="mostrarModalTratamiento"
      title="Nuevo Tratamiento"
      size="2xl"
      @close="mostrarModalTratamiento = false"
    >
      <div class="space-y-5">
        <!-- Banner informativo -->
        <div class="flex items-start space-x-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
          <Stethoscope class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" />
          <div>
            <p class="font-medium text-blue-900">Registro de Tratamiento</p>
            <p class="text-sm text-blue-700 mt-1">
              Seleccione el tratamiento del catálogo y configure los detalles específicos.
            </p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tratamiento *
          </label>
          <select
            v-model="formTratamiento.id_tratamiento"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
          >
            <option :value="null">Seleccione un tratamiento del catálogo</option>
            <option v-for="trat in catalogoTratamientos" :key="trat.id_tratamiento" :value="trat.id_tratamiento">
              {{ trat.nombre }} {{ trat.precio ? `- S/ ${trat.precio}` : '' }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Descripción Adicional
          </label>
          <textarea
            v-model="formTratamiento.descripcion"
            rows="3"
            maxlength="500"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
            placeholder="Detalles específicos del tratamiento para este paciente..."
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">
            {{ formTratamiento.descripcion?.length || 0 }}/500 caracteres
          </p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Fecha Inicio
            </label>
            <input
              v-model="formTratamiento.fecha_inicio"
              type="date"
              :min="new Date().toISOString().split('T')[0]"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Fecha Fin Estimada
            </label>
            <input
              v-model="formTratamiento.fecha_fin"
              type="date"
              :min="formTratamiento.fecha_inicio || new Date().toISOString().split('T')[0]"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Estado *
            </label>
            <select
              v-model="formTratamiento.estado"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            >
              <option value="sugerido">Sugerido</option>
              <option value="en_curso">En Curso</option>
              <option value="completado">Completado</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Precio (S/)
            </label>
            <input
              v-model.number="formTratamiento.precio"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            />
          </div>
        </div>
      </div>

      <template #footer>
        <Button
          variant="secondary"
          @click="mostrarModalTratamiento = false"
          :disabled="guardandoTratamiento"
        >
          Cancelar
        </Button>
        <Button
          variant="primary"
          @click="guardarTratamiento"
          :disabled="guardandoTratamiento || !formTratamiento.id_tratamiento"
          :loading="guardandoTratamiento"
        >
          {{ guardandoTratamiento ? 'Guardando...' : 'Guardar Tratamiento' }}
        </Button>
      </template>
    </Modal>

    <!-- Modal: Nueva Prescripción -->
    <Modal
      :show="mostrarModalPrescripcion"
      title="Nueva Prescripción Médica"
      size="2xl"
      @close="mostrarModalPrescripcion = false"
    >
      <div class="space-y-5">
        <!-- Banner informativo -->
        <div class="flex items-start space-x-3 p-4 bg-purple-50 rounded-lg border border-purple-200">
          <Pill class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" />
          <div>
            <p class="font-medium text-purple-900">Prescripción de Medicamento</p>
            <p class="text-sm text-purple-700 mt-1">
              Registre los detalles del medicamento recetado y las indicaciones para el paciente.
            </p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Medicamento *
          </label>
          <input
            v-model="formPrescripcion.medicamento"
            type="text"
            required
            maxlength="100"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
            placeholder="Nombre del medicamento recetado..."
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Dosis
            </label>
            <input
              v-model="formPrescripcion.dosis"
              type="text"
              maxlength="100"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
              placeholder="Ej: 500mg, 10ml"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Frecuencia
            </label>
            <input
              v-model="formPrescripcion.frecuencia"
              type="text"
              maxlength="100"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
              placeholder="Ej: Cada 8 horas"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Duración del Tratamiento
            </label>
            <input
              v-model="formPrescripcion.duracion"
              type="text"
              maxlength="100"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
              placeholder="Ej: 7 días, 2 semanas"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Fecha de Prescripción *
            </label>
            <input
              v-model="formPrescripcion.fecha_prescripcion"
              type="date"
              required
              :max="new Date().toISOString().split('T')[0]"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Indicaciones para el Paciente
          </label>
          <textarea
            v-model="formPrescripcion.indicaciones"
            rows="4"
            maxlength="1000"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors resize-none"
            placeholder="Instrucciones de uso, advertencias, recomendaciones especiales..."
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">
            {{ formPrescripcion.indicaciones?.length || 0 }}/1000 caracteres
          </p>
        </div>
      </div>

      <template #footer>
        <Button
          variant="secondary"
          @click="mostrarModalPrescripcion = false"
          :disabled="guardandoPrescripcion"
        >
          Cancelar
        </Button>
        <Button
          variant="primary"
          @click="guardarPrescripcion"
          :disabled="guardandoPrescripcion || !formPrescripcion.medicamento || !formPrescripcion.fecha_prescripcion"
          :loading="guardandoPrescripcion"
        >
          {{ guardandoPrescripcion ? 'Guardando...' : 'Guardar Prescripción' }}
        </Button>
      </template>
    </Modal>

    <!-- Modal: Nuevo Control / Evolución -->
    <ModalAgregarControl
      :show="mostrarModalControl"
      :id-historial="historial?.id_historial || null"
      @close="mostrarModalControl = false"
      @guardado="guardarControl"
    />

    <!-- Modal: Nuevo Seguimiento de Tratamiento -->
    <ModalAgregarSeguimiento
      :show="mostrarModalSeguimiento"
      :id-historial="historial?.id_historial || null"
      :id-tratamiento-historial="tratamientoSeleccionado"
      @close="mostrarModalSeguimiento = false; tratamientoSeleccionado = null"
      @guardado="guardarSeguimiento"
    />
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  ArrowLeft, Edit, Edit2, Save, Printer, Plus, Calendar,
  Stethoscope, ClipboardList, Pill, User, FileText, Activity,
  AlertCircle, CheckCircle
} from 'lucide-vue-next'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/common/Button.vue'
import InputField from '@/components/common/InputField.vue'
import OdontogramaInteractivo from '@/components/historial/OdontogramaInteractivo.vue'
import OdontogramaVisualizacion from '@/components/historial/OdontogramaVisualizacion.vue'
import ModalAgregarControl from '@/components/historial/ModalAgregarControl.vue'
import ModalAgregarSeguimiento from '@/components/historial/ModalAgregarSeguimiento.vue'
import historialClinicoService, { type HistorialClinico } from '@/api/historialClinicoService'
import { actualizarPaciente } from '@/api/pacienteService'
import { useAuthStore } from '@/stores/authStore'
import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// Interfaces para seguimientos
interface Medico {
  id_medico: number
  nombres: string
  apellidos: string
  especialidad?: string
}

interface SeguimientoTratamiento {
  id_seguimiento: number
  id_historial: number
  id_tratamiento_historial: number
  fecha_registro: string
  descripcion: string | null
  duracion_restante: number | null
  registrado_por: number | null
  registradoPor?: Medico
}

// Estado
const loading = ref(false)
const historial = ref<HistorialClinico | null>(null)
const tabActivo = ref('info')
const modoEdicion = ref(false)
const editandoPaciente = ref(false)
const editandoFaseII = ref(false)
const editandoFaseIII = ref(false)
const guardandoPaciente = ref(false)
const guardandoHistorial = ref(false)
const tratamientoExpandido = ref<number | null>(null) // Para controlar qué tratamiento está expandido

// Modales
const mostrarModalConsulta = ref(false)
const mostrarModalControl = ref(false)
const mostrarModalSeguimiento = ref(false)
const mostrarModalTratamiento = ref(false)
const mostrarModalPrescripcion = ref(false)
const guardandoConsulta = ref(false)
const guardandoControl = ref(false)
const guardandoSeguimiento = ref(false)
const guardandoTratamiento = ref(false)
const guardandoPrescripcion = ref(false)

// ID del tratamiento seleccionado para seguimiento
const tratamientoSeleccionado = ref<number | null>(null)

// Computed properties para roles
const esRolMedico = computed(() => {
  return authStore.user?.roles?.some((rol: any) => rol.nombre_rol === 'medico') || false
})

const esRolAdmin = computed(() => {
  return authStore.user?.roles?.some((rol: any) => rol.nombre_rol === 'admin') || false
})

const esRolPaciente = computed(() => {
  return authStore.user?.roles?.some((rol: any) => rol.nombre_rol === 'paciente') || false
})

// Tabs
const tabs = [
  { id: 'info', label: 'Información General', icon: User },
  { id: 'odontograma', label: 'Odontograma', icon: Activity },
  { id: 'tratamientos', label: 'Tratamientos', icon: Stethoscope },
  { id: 'consultas', label: 'Consultas y Controles', icon: ClipboardList },
  { id: 'prescripciones', label: 'Prescripciones', icon: Pill },
]

// Formularios
const formPaciente = reactive<{
  nombres: string
  apellidos: string
  dni: string
  fecha_nacimiento: string
  sexo: 'M' | 'F' | 'Otro' | ''
  domicilio: string
  persona_responsable: string
  telefono: string
  telefono_responsable: string
  grupo_sanguineo: string
  alergias: string
}>({
  nombres: '',
  apellidos: '',
  dni: '',
  fecha_nacimiento: '',
  sexo: '',
  domicilio: '',
  persona_responsable: '',
  telefono: '',
  telefono_responsable: '',
  grupo_sanguineo: '',
  alergias: ''
})

const formHistorial = reactive<{
  motivo_consulta: string
  diagnostico_presuntivo: string
  diagnostico_principal: string
  higiene_bucal: 'Bueno' | 'Regular' | 'Malo' | ''
  // FASE II - Motivo de Consulta e Historia
  sintoma_principal: string
  tiempo_inicio_sintomas: string
  tratamiento_previo: string
  enfermedades_actuales: string
  bajo_tratamiento_medico: boolean
  detalle_tratamiento_actual: string
  alergias_paciente: string
  // FASE III - Antecedentes
  intervenciones_quirurgicas_previas: boolean
  detalle_intervenciones: string
  hemorragia_post_tratamiento: boolean
  problema_anestesia: boolean
  dificultad_abrir_masticar: boolean
  sensibilidad_dental: boolean
}>({
  motivo_consulta: '',
  diagnostico_presuntivo: '',
  diagnostico_principal: '',
  higiene_bucal: '',
  // FASE II
  sintoma_principal: '',
  tiempo_inicio_sintomas: '',
  tratamiento_previo: '',
  enfermedades_actuales: '',
  bajo_tratamiento_medico: false,
  detalle_tratamiento_actual: '',
  alergias_paciente: '',
  // FASE III
  intervenciones_quirurgicas_previas: false,
  detalle_intervenciones: '',
  hemorragia_post_tratamiento: false,
  problema_anestesia: false,
  dificultad_abrir_masticar: false,
  sensibilidad_dental: false
})

const formConsulta = reactive<{
  fecha: string
  diagnostico: string
  procedimiento_realizado: string
  notas_medicas: string
}>({
  fecha: new Date().toISOString().substring(0, 10),
  diagnostico: '',
  procedimiento_realizado: '',
  notas_medicas: ''
})

const formTratamiento = reactive<{
  id_tratamiento: number | null
  descripcion: string
  fecha_inicio: string
  fecha_fin: string
  estado: 'sugerido' | 'en_curso' | 'completado' | 'cancelado'
  precio: number | null
}>({
  id_tratamiento: null,
  descripcion: '',
  fecha_inicio: '',
  fecha_fin: '',
  estado: 'sugerido',
  precio: null
})

const formPrescripcion = reactive<{
  medicamento: string
  dosis: string
  frecuencia: string
  duracion: string
  indicaciones: string
  fecha_prescripcion: string
}>({
  medicamento: '',
  dosis: '',
  frecuencia: '',
  duracion: '',
  indicaciones: '',
  fecha_prescripcion: new Date().toISOString().substring(0, 10)
})

const catalogoTratamientos = ref<Array<{
  id_tratamiento: number
  nombre: string
  descripcion?: string
  precio?: number
  categoria?: string
}>>([])

// Computed - Ordenar listados de más reciente a más antiguo
const tratamientosOrdenados = computed(() => {
  if (!historial.value?.tratamientos) return []
  return [...historial.value.tratamientos].sort((a, b) => {
    const fechaA = new Date(a.fecha_inicio || 0).getTime()
    const fechaB = new Date(b.fecha_inicio || 0).getTime()
    return fechaB - fechaA // Más reciente primero
  })
})

const consultasOrdenadas = computed(() => {
  if (!historial.value?.detalles) return []
  return [...historial.value.detalles].sort((a, b) => {
    const fechaA = new Date(a.fecha || 0).getTime()
    const fechaB = new Date(b.fecha || 0).getTime()
    return fechaB - fechaA // Más reciente primero
  })
})

const prescripcionesOrdenadas = computed(() => {
  if (!historial.value?.prescripciones) return []
  return [...historial.value.prescripciones].sort((a, b) => {
    const fechaA = new Date(a.fecha_prescripcion || 0).getTime()
    const fechaB = new Date(b.fecha_prescripcion || 0).getTime()
    return fechaB - fechaA // Más reciente primero
  })
})

// Funciones
const cargarHistorial = async () => {
  loading.value = true
  try {
    const id = Number(route.params.id)
    const data = await historialClinicoService.obtenerHistorial(id)
    historial.value = data.historial

    // Cargar datos en formularios
    if (historial.value) {
      Object.assign(formPaciente, {
        nombres: historial.value.paciente.nombres,
        apellidos: historial.value.paciente.apellidos,
        dni: historial.value.paciente.dni || '',
        fecha_nacimiento: historial.value.paciente.fecha_nacimiento || '',
        sexo: historial.value.paciente.sexo || '',
        domicilio: historial.value.paciente.domicilio || '',
        persona_responsable: historial.value.paciente.persona_responsable || '',
        telefono: historial.value.paciente.telefono || '',
        telefono_responsable: historial.value.paciente.telefono_responsable || '',
        grupo_sanguineo: historial.value.paciente.grupo_sanguineo || '',
        alergias: historial.value.paciente.alergias || ''
      })

      Object.assign(formHistorial, {
        motivo_consulta: historial.value.motivo_consulta || '',
        diagnostico_presuntivo: historial.value.diagnostico_presuntivo || '',
        diagnostico_principal: historial.value.diagnostico_principal || '',
        higiene_bucal: historial.value.higiene_bucal || '',
        // FASE II - Motivo de consulta e historia de la enfermedad
        sintoma_principal: historial.value.sintoma_principal || '',
        tiempo_inicio_sintomas: historial.value.tiempo_inicio_sintomas || '',
        tratamiento_previo: historial.value.tratamiento_previo || '',
        enfermedades_actuales: historial.value.enfermedades_actuales || '',
        // FASE III - Antecedentes médicos y odontológicos
        bajo_tratamiento_medico: historial.value.bajo_tratamiento_medico || false,
        detalle_tratamiento_actual: historial.value.detalle_tratamiento_actual || '',
        alergias_paciente: historial.value.alergias_paciente || '',
        intervenciones_quirurgicas_previas: historial.value.intervenciones_quirurgicas_previas || false,
        detalle_intervenciones: historial.value.detalle_intervenciones || '',
        hemorragia_post_tratamiento: historial.value.hemorragia_post_tratamiento || false,
        problema_anestesia: historial.value.problema_anestesia || false,
        dificultad_abrir_masticar: historial.value.dificultad_abrir_masticar || false,
        sensibilidad_dental: historial.value.sensibilidad_dental || false
      })
    }
  } catch (error) {
    console.error('Error al cargar historial:', error)
    Swal.fire('Error', 'No se pudo cargar el historial clínico', 'error')
  } finally {
    loading.value = false
  }
}

const guardarDatosPaciente = async () => {
  if (!historial.value) return

  guardandoPaciente.value = true
  try {
    // Convertir sexo vacío a undefined
    const datosActualizados = {
      ...formPaciente,
      sexo: formPaciente.sexo || undefined
    }
    await actualizarPaciente(historial.value.paciente.id_paciente, datosActualizados)
    Swal.fire('Éxito', 'Datos del paciente actualizados', 'success')
    editandoPaciente.value = false
    await cargarHistorial()
  } catch (error) {
    console.error('Error al actualizar paciente:', error)
    Swal.fire('Error', 'No se pudieron actualizar los datos', 'error')
  } finally {
    guardandoPaciente.value = false
  }
}

const cancelarEdicionPaciente = () => {
  editandoPaciente.value = false
  // Recargar datos originales
  if (historial.value) {
    Object.assign(formPaciente, {
      nombres: historial.value.paciente.nombres,
      apellidos: historial.value.paciente.apellidos,
      dni: historial.value.paciente.dni || '',
      fecha_nacimiento: historial.value.paciente.fecha_nacimiento || '',
      sexo: historial.value.paciente.sexo || '',
      domicilio: historial.value.paciente.domicilio || '',
      persona_responsable: historial.value.paciente.persona_responsable || '',
      telefono: historial.value.paciente.telefono || '',
      telefono_responsable: historial.value.paciente.telefono_responsable || '',
      grupo_sanguineo: historial.value.paciente.grupo_sanguineo || '',
      alergias: historial.value.paciente.alergias || ''
    })
  }
}

const guardarFaseII = async () => {
  if (!historial.value) return

  guardandoHistorial.value = true
  try {
    const datosActualizados = {
      sintoma_principal: formHistorial.sintoma_principal,
      tiempo_inicio_sintomas: formHistorial.tiempo_inicio_sintomas,
      tratamiento_previo: formHistorial.tratamiento_previo,
      enfermedades_actuales: formHistorial.enfermedades_actuales
    }
    await historialClinicoService.actualizarHistorial(historial.value.id_historial, datosActualizados)
    Swal.fire('Éxito', 'Motivo de consulta e historia actualizada', 'success')
    editandoFaseII.value = false
    await cargarHistorial()
  } catch (error) {
    console.error('Error al actualizar FASE II:', error)
    Swal.fire('Error', 'No se pudo actualizar la información', 'error')
  } finally {
    guardandoHistorial.value = false
  }
}

const cancelarEdicionFaseII = () => {
  editandoFaseII.value = false
  if (historial.value) {
    formHistorial.sintoma_principal = historial.value.sintoma_principal || ''
    formHistorial.tiempo_inicio_sintomas = historial.value.tiempo_inicio_sintomas || ''
    formHistorial.tratamiento_previo = historial.value.tratamiento_previo || ''
    formHistorial.enfermedades_actuales = historial.value.enfermedades_actuales || ''
  }
}

const guardarFaseIII = async () => {
  if (!historial.value) return

  guardandoHistorial.value = true
  try {
    const datosActualizados = {
      bajo_tratamiento_medico: formHistorial.bajo_tratamiento_medico,
      detalle_tratamiento_actual: formHistorial.detalle_tratamiento_actual,
      alergias_paciente: formHistorial.alergias_paciente,
      intervenciones_quirurgicas_previas: formHistorial.intervenciones_quirurgicas_previas,
      detalle_intervenciones: formHistorial.detalle_intervenciones,
      hemorragia_post_tratamiento: formHistorial.hemorragia_post_tratamiento,
      problema_anestesia: formHistorial.problema_anestesia,
      dificultad_abrir_masticar: formHistorial.dificultad_abrir_masticar,
      sensibilidad_dental: formHistorial.sensibilidad_dental
    }
    await historialClinicoService.actualizarHistorial(historial.value.id_historial, datosActualizados)
    Swal.fire('Éxito', 'Antecedentes médicos actualizados', 'success')
    editandoFaseIII.value = false
    await cargarHistorial()
  } catch (error) {
    console.error('Error al actualizar FASE III:', error)
    Swal.fire('Error', 'No se pudo actualizar la información', 'error')
  } finally {
    guardandoHistorial.value = false
  }
}

const cancelarEdicionFaseIII = () => {
  editandoFaseIII.value = false
  if (historial.value) {
    formHistorial.bajo_tratamiento_medico = historial.value.bajo_tratamiento_medico || false
    formHistorial.detalle_tratamiento_actual = historial.value.detalle_tratamiento_actual || ''
    formHistorial.alergias_paciente = historial.value.alergias_paciente || ''
    formHistorial.intervenciones_quirurgicas_previas = historial.value.intervenciones_quirurgicas_previas || false
    formHistorial.detalle_intervenciones = historial.value.detalle_intervenciones || ''
    formHistorial.hemorragia_post_tratamiento = historial.value.hemorragia_post_tratamiento || false
    formHistorial.problema_anestesia = historial.value.problema_anestesia || false
    formHistorial.dificultad_abrir_masticar = historial.value.dificultad_abrir_masticar || false
    formHistorial.sensibilidad_dental = historial.value.sensibilidad_dental || false
  }
}

const guardarHistorial = async () => {
  if (!historial.value) return

  guardandoHistorial.value = true
  try {
    // Convertir higiene_bucal vacío a null/undefined
    const datosActualizados = {
      ...formHistorial,
      higiene_bucal: (formHistorial.higiene_bucal || null) as 'Bueno' | 'Regular' | 'Malo' | null
    }
    await historialClinicoService.actualizarHistorial(historial.value.id_historial, datosActualizados)
    Swal.fire('Éxito', 'Historial clínico actualizado', 'success')
    modoEdicion.value = false
    await cargarHistorial()
  } catch (error) {
    console.error('Error al actualizar historial:', error)
    Swal.fire('Error', 'No se pudo actualizar el historial', 'error')
  } finally {
    guardandoHistorial.value = false
  }
}

const cancelarEdicionHistorial = () => {
  modoEdicion.value = false
  // Recargar datos originales
  if (historial.value) {
    Object.assign(formHistorial, {
      motivo_consulta: historial.value.motivo_consulta || '',
      diagnostico_presuntivo: historial.value.diagnostico_presuntivo || '',
      diagnostico_principal: historial.value.diagnostico_principal || '',
      higiene_bucal: historial.value.higiene_bucal || ''
    })
  }
}

const volver = () => {
  router.push('/historial-clinico')
}

const imprimirHistorial = () => {
  window.print()
}

const cargarCatalogoTratamientos = async () => {
  try {
    const data = await historialClinicoService.obtenerCatalogoTratamientos()
    catalogoTratamientos.value = data.tratamientos
  } catch (error) {
    console.error('Error al cargar catálogo de tratamientos:', error)
  }
}

const abrirModalNuevoTratamiento = async () => {
  await cargarCatalogoTratamientos()
  // Resetear formulario
  Object.assign(formTratamiento, {
    id_tratamiento: null,
    descripcion: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'sugerido',
    precio: null
  })
  mostrarModalTratamiento.value = true
}

const abrirModalNuevaConsulta = () => {
  // Resetear formulario
  Object.assign(formConsulta, {
    fecha: new Date().toISOString().substring(0, 10),
    diagnostico: '',
    procedimiento_realizado: '',
    notas_medicas: ''
  })
  mostrarModalConsulta.value = true
}

const abrirModalNuevoControl = () => {
  mostrarModalControl.value = true
}

const guardarControl = async (datos: any) => {
  if (!historial.value) return

  guardandoControl.value = true
  try {
    await historialClinicoService.agregarDetalle(historial.value.id_historial, datos)
    Swal.fire('Éxito', 'Control de evolución registrado correctamente', 'success')
    mostrarModalControl.value = false
    await cargarHistorial()
  } catch (error) {
    console.error('Error al guardar control:', error)
    Swal.fire('Error', 'No se pudo guardar el control', 'error')
  } finally {
    guardandoControl.value = false
  }
}

const abrirModalNuevaPrescripcion = () => {
  // Resetear formulario
  Object.assign(formPrescripcion, {
    medicamento: '',
    dosis: '',
    frecuencia: '',
    duracion: '',
    indicaciones: '',
    fecha_prescripcion: new Date().toISOString().substring(0, 10)
  })
  mostrarModalPrescripcion.value = true
}

const guardarConsulta = async () => {
  if (!historial.value) return

  guardandoConsulta.value = true
  try {
    await historialClinicoService.agregarDetalle(historial.value.id_historial, {
      fecha: formConsulta.fecha,
      diagnostico: formConsulta.diagnostico || undefined,
      procedimiento_realizado: formConsulta.procedimiento_realizado || undefined,
      notas_medicas: formConsulta.notas_medicas || undefined
    })
    Swal.fire('Éxito', 'Consulta agregada correctamente', 'success')
    mostrarModalConsulta.value = false
    await cargarHistorial()
  } catch (error) {
    console.error('Error al guardar consulta:', error)
    Swal.fire('Error', 'No se pudo guardar la consulta', 'error')
  } finally {
    guardandoConsulta.value = false
  }
}

const guardarTratamiento = async () => {
  if (!historial.value || !formTratamiento.id_tratamiento) {
    Swal.fire('Error', 'Debe seleccionar un tratamiento', 'error')
    return
  }

  guardandoTratamiento.value = true
  try {
    await historialClinicoService.agregarTratamiento(historial.value.id_historial, {
      id_tratamiento: formTratamiento.id_tratamiento,
      descripcion: formTratamiento.descripcion || undefined,
      fecha_inicio: formTratamiento.fecha_inicio || undefined,
      fecha_fin: formTratamiento.fecha_fin || undefined,
      estado: formTratamiento.estado,
      precio: formTratamiento.precio || undefined
    })
    Swal.fire('Éxito', 'Tratamiento agregado correctamente', 'success')
    mostrarModalTratamiento.value = false
    await cargarHistorial()
  } catch (error) {
    console.error('Error al guardar tratamiento:', error)
    Swal.fire('Error', 'No se pudo guardar el tratamiento', 'error')
  } finally {
    guardandoTratamiento.value = false
  }
}

const guardarPrescripcion = async () => {
  if (!historial.value || !formPrescripcion.medicamento) {
    Swal.fire('Error', 'Debe especificar el medicamento', 'error')
    return
  }

  guardandoPrescripcion.value = true
  try {
    await historialClinicoService.agregarPrescripcion(historial.value.id_historial, {
      medicamento: formPrescripcion.medicamento,
      dosis: formPrescripcion.dosis || undefined,
      frecuencia: formPrescripcion.frecuencia || undefined,
      duracion: formPrescripcion.duracion || undefined,
      indicaciones: formPrescripcion.indicaciones || undefined,
      fecha_prescripcion: formPrescripcion.fecha_prescripcion
    })
    Swal.fire('Éxito', 'Prescripción agregada correctamente', 'success')
    mostrarModalPrescripcion.value = false
    await cargarHistorial()
  } catch (error) {
    console.error('Error al guardar prescripción:', error)
    Swal.fire('Error', 'No se pudo guardar la prescripción', 'error')
  } finally {
    guardandoPrescripcion.value = false
  }
}

const estadoTratamientoLabel = (estado: string) => {
  const labels: Record<string, string> = {
    sugerido: 'Sugerido',
    en_curso: 'En Curso',
    completado: 'Completado',
    cancelado: 'Cancelado'
  }
  return labels[estado] || estado
}

const formatDate = (date: string | null | undefined) => {
  if (!date) return 'No especificado'
  return new Date(date).toLocaleDateString('es-PE', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Funciones para seguimientos
const toggleTratamiento = (tratamientoId: number) => {
  if (tratamientoExpandido.value === tratamientoId) {
    tratamientoExpandido.value = null
  } else {
    tratamientoExpandido.value = tratamientoId
  }
}

const agregarSeguimiento = (tratamientoId: number) => {
  tratamientoSeleccionado.value = tratamientoId
  mostrarModalSeguimiento.value = true
}

const guardarSeguimiento = async (datos: any) => {
  if (!historial.value || !tratamientoSeleccionado.value) return

  guardandoSeguimiento.value = true
  try {
    await historialClinicoService.agregarSeguimiento(
      historial.value.id_historial,
      tratamientoSeleccionado.value,
      datos
    )
    Swal.fire('Éxito', 'Seguimiento registrado correctamente', 'success')
    mostrarModalSeguimiento.value = false
    tratamientoSeleccionado.value = null
    await cargarHistorial()
  } catch (error) {
    console.error('Error al guardar seguimiento:', error)
    Swal.fire('Error', 'No se pudo guardar el seguimiento', 'error')
  } finally {
    guardandoSeguimiento.value = false
  }
}

onMounted(() => {
  cargarHistorial()
})
</script>
