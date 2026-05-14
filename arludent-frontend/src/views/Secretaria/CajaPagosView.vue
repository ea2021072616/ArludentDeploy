<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
            <CreditCard class="w-8 h-8 text-green-600" />
            Caja y Pagos
          </h1>
          <p class="text-gray-600 mt-1">Control financiero y gestión de pagos con SUNAT</p>
        </div>
        <div class="flex items-center space-x-3">
          <Button
            variant="outline"
            @click="mostrarFiltros = !mostrarFiltros"
          >
            <Filter class="w-5 h-5" />
            <span>Filtros</span>
          </Button>
          <Button
            variant="primary"
            @click="abrirModalNuevoPago"
          >
            <Plus class="w-5 h-5" />
            <span>Nuevo Pago</span>
          </Button>
        </div>
      </div>

      <!-- Estadísticas -->
      <div v-if="estadisticas && !cargando" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card class="border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Pagos</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.total_pagos || 0 }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatearMonto(estadisticas.monto_total || 0) }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <TrendingUp class="w-5 h-5 text-green-600" />
            </div>
          </div>
        </Card>

        <Card class="border-l-4 border-orange-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Pendientes</p>
              <p class="text-2xl font-bold text-gray-900">{{ estadisticas.pagos_pendientes || 0 }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatearMonto(estadisticas.monto_pendiente || 0) }}</p>
            </div>
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
              <Clock class="w-5 h-5 text-orange-600" />
            </div>
          </div>
        </Card>

        <Card class="border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Comprobantes</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ estadisticas.pagos_por_tipo_comprobante?.reduce((a, b) => a + (b.tipo !== 'ninguno' ? b.cantidad : 0), 0) || 0 }}
              </p>
              <p class="text-xs text-gray-500 mt-1">Boletas y Facturas</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <FileText class="w-5 h-5 text-blue-600" />
            </div>
          </div>
        </Card>

        <Card class="border-l-4 border-purple-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Efectivo</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ estadisticas.pagos_por_metodo?.find(m => m.metodo === 'efectivo')?.cantidad || 0 }}
              </p>
              <p class="text-xs text-gray-500 mt-1">
                {{ formatearMonto(estadisticas.pagos_por_metodo?.find(m => m.metodo === 'efectivo')?.monto || 0) }}
              </p>
            </div>
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
              <DollarSign class="w-5 h-5 text-purple-600" />
            </div>
          </div>
        </Card>
      </div>

      <!-- Tabs de navegación -->
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="tabActivo = tab.id"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm flex items-center gap-2',
              tabActivo === tab.id
                ? 'border-green-500 text-green-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <component :is="tab.icon" class="w-5 h-5" />
            {{ tab.nombre }}
          </button>
        </nav>
      </div>

      <!-- Loading -->
      <div v-if="cargando" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-green-200 border-t-green-600"></div>
        <p class="text-gray-600 mt-4">Cargando datos...</p>
      </div>

      <!-- Contenido basado en tab -->
      <div v-else class="space-y-6">
        <!-- Tab Todos los Pagos -->
        <div v-if="tabActivo === 'pagos'">
          <Card>
            <div class="p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Listado de Pagos</h3>

              <!-- Tabla de pagos -->
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comprobante</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="pago in pagos" :key="pago.id_pago" class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                          <div class="text-sm font-medium text-gray-900">
                            {{ pago.paciente?.nombre }} {{ pago.paciente?.apellidos }}
                          </div>
                          <div class="text-sm text-gray-500">{{ pago.paciente?.dni }}</div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ new Date(pago.fecha_pago).toLocaleDateString() }}
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-900">
                        {{ pago.concepto || 'Sin concepto' }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ formatearMonto(pago.monto) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ obtenerEtiquetaMetodo(pago.metodo_pago) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', obtenerClaseEstadoPago(pago.estado_pago)]">
                          {{ pago.estado_pago }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div v-if="pago.tiene_comprobante" class="space-y-1">
                          <div class="text-xs text-gray-900">{{ pago.comprobante_completo }}</div>
                          <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', obtenerClaseEstadoSunat(pago.estado_sunat)]">
                            {{ pago.estado_sunat }}
                          </span>
                        </div>
                        <Button
                          v-else
                          size="sm"
                          variant="outline"
                          @click="abrirModalComprobante(pago)"
                        >
                          <FileText class="w-4 h-4 mr-1" />
                          Emitir
                        </Button>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex space-x-2">
                          <!-- Mostrar botón PDF solo si el pago ya tiene comprobante -->
                          <Button
                            v-if="pago.tiene_comprobante"
                            size="sm"
                            variant="outline"
                            @click="generarPDF(pago)"
                            :disabled="generandoPDF === pago.id_pago"
                          >
                            <Download class="w-4 h-4" />
                            <span v-if="generandoPDF === pago.id_pago" class="ml-1">Generando...</span>
                            <span v-else class="ml-1">PDF</span>
                          </Button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <div v-if="pagos.length === 0" class="text-center py-8">
                  <CreditCard class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                  <h3 class="text-lg font-medium text-gray-900 mb-2">No hay pagos registrados</h3>
                  <p class="text-gray-600">Registra el primer pago haciendo clic en "Nuevo Pago"</p>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Tab Pendientes -->
        <div v-if="tabActivo === 'pendientes'">
          <Card>
            <div class="p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Pagos Pendientes</h3>
              <div class="space-y-4">
                <div
                  v-for="pago in pagosPendientes"
                  :key="pago.id_pago"
                  class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50"
                >
                  <div class="flex items-center justify-between">
                    <div>
                      <h4 class="font-medium text-gray-900">
                        {{ pago.paciente?.nombre }} {{ pago.paciente?.apellidos }}
                      </h4>
                      <p class="text-sm text-gray-600">{{ pago.concepto }}</p>
                      <p class="text-sm font-medium text-orange-600">{{ formatearMonto(pago.monto) }}</p>
                      <p class="text-xs text-gray-500">{{ new Date(pago.fecha_pago).toLocaleDateString() }}</p>
                    </div>
                    <div class="flex space-x-2">
                      <Button
                        size="sm"
                        variant="primary"
                        @click="abrirModalComprobante(pago)"
                      >
                        Emitir Comprobante
                      </Button>
                    </div>
                  </div>
                </div>

                <div v-if="pagosPendientes.length === 0" class="text-center py-8">
                  <CheckCircle class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                  <h3 class="text-lg font-medium text-gray-900 mb-2">No hay pagos pendientes</h3>
                  <p class="text-gray-600">Todos los pagos están procesados.</p>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Tab Estadísticas -->
        <div v-if="tabActivo === 'estadisticas'">
          <div class="space-y-6">
            <!-- Gráfico de últimos 7 días -->
            <Card v-if="estadisticas">
              <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Últimos 7 Días</h3>
                <div class="space-y-2">
                  <div
                    v-for="dia in (estadisticas.ultimos_7_dias || [])"
                    :key="dia.fecha"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <span class="text-sm font-medium text-gray-700">{{ dia.fecha }}</span>
                    <div class="flex items-center gap-4">
                      <span class="text-sm text-gray-600">{{ dia.cantidad }} pagos</span>
                      <span class="text-sm font-semibold text-gray-900">{{ formatearMonto(dia.monto) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Métodos de pago -->
            <Card v-if="estadisticas">
              <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Por Método de Pago</h3>
                <div class="space-y-3">
                  <div
                    v-for="metodo in (estadisticas.pagos_por_metodo || [])"
                    :key="metodo.metodo"
                    class="flex items-center justify-between"
                  >
                    <span class="text-sm font-medium text-gray-700">{{ obtenerEtiquetaMetodo(metodo.metodo) }}</span>
                    <div class="flex items-center gap-4">
                      <span class="text-sm text-gray-600">{{ metodo.cantidad }} pagos</span>
                      <span class="text-sm font-semibold text-gray-900">{{ formatearMonto(metodo.monto) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>

      <!-- Modal Nuevo Pago -->
      <Modal
        :show="modalPago"
        @close="cerrarModalPago"
        title="Registrar Nuevo Pago"
        size="lg"
      >
        <form @submit.prevent="guardarPago" class="space-y-6">
          <!-- Búsqueda de paciente -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Buscar Paciente <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="busquedaPaciente"
                @input="buscarPacientes"
                @focus="mostrarResultados = true"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                placeholder="Escribe nombre, apellido o DNI..."
                :disabled="!!pacienteSeleccionado"
                autocomplete="off"
              />

              <!-- Indicador de carga -->
              <div v-if="buscandoPacientes" class="absolute right-3 top-3">
                <div class="animate-spin rounded-full h-5 w-5 border-2 border-green-200 border-t-green-600"></div>
              </div>

              <!-- Lista de resultados (estilo GitHub) -->
              <div
                v-if="pacientesEncontrados.length > 0 && !pacienteSeleccionado && mostrarResultados"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto"
              >
                <div class="p-2 bg-gray-50 border-b border-gray-200 text-xs text-gray-600 flex items-center justify-between">
                  <span>{{ pacientesEncontrados.length }} resultado(s)</span>
                  <button
                    type="button"
                    @click="mostrarResultados = false"
                    class="text-gray-400 hover:text-gray-600"
                  >
                    <X class="w-4 h-4" />
                  </button>
                </div>
                <button
                  v-for="(paciente, index) in pacientesEncontrados"
                  :key="paciente.id_paciente"
                  type="button"
                  @click="seleccionarPaciente(paciente)"
                  class="w-full px-4 py-3 text-left hover:bg-green-50 border-b border-gray-100 last:border-b-0 transition-colors focus:outline-none focus:bg-green-50"
                  :class="{ 'bg-green-50': index === 0 }"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex-1">
                      <div class="font-medium text-gray-900">{{ paciente.nombre }} {{ paciente.apellidos }}</div>
                      <div class="text-sm text-gray-600 flex items-center gap-3 mt-1">
                        <span class="flex items-center gap-1">
                          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                          </svg>
                          DNI: {{ paciente.dni }}
                        </span>
                        <span v-if="paciente.edad" class="flex items-center gap-1">
                          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                          </svg>
                          {{ paciente.edad }} años
                        </span>
                      </div>
                    </div>
                    <div class="ml-3 text-gray-400">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                      </svg>
                    </div>
                  </div>
                </button>
              </div>

              <!-- Sin resultados -->
              <div
                v-if="busquedaPaciente.length >= 2 && pacientesEncontrados.length === 0 && !buscandoPacientes && mostrarResultados"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-center"
              >
                <p class="text-sm text-gray-600">No se encontraron pacientes</p>
                <p class="text-xs text-gray-500 mt-1">Intenta con otro término de búsqueda</p>
              </div>
            </div>            <!-- Paciente seleccionado -->
            <div v-if="pacienteSeleccionado" class="mt-3 p-3 bg-green-50 rounded-lg flex items-center justify-between">
              <div>
                <p class="font-medium text-green-900">{{ pacienteSeleccionado.nombre }} {{ pacienteSeleccionado.apellidos }}</p>
                <p class="text-sm text-green-700">DNI: {{ pacienteSeleccionado.dni }}</p>
              </div>
              <button
                type="button"
                @click="pacienteSeleccionado = null; busquedaPaciente = ''"
                class="text-green-600 hover:text-green-800"
              >
                <X class="w-5 h-5" />
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Monto -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Monto <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="formularioPago.monto"
                type="number"
                step="0.01"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                placeholder="0.00"
                required
              />
            </div>

            <!-- Método de pago -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Método de Pago <span class="text-red-500">*</span>
              </label>
              <select
                v-model="formularioPago.metodo_pago"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                required
              >
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
                <option value="cheque">Cheque</option>
                <option value="yape">Yape</option>
                <option value="plin">Plin</option>
                <option value="otros">Otros</option>
              </select>
            </div>
          </div>

          <!-- Concepto -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Concepto <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formularioPago.concepto"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              placeholder="Descripción del pago..."
              required
            />
          </div>

          <!-- Fecha de Pago -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Fecha de Pago <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formularioPago.fecha_pago"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              required
            />
          </div>

          <!-- Notas -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Notas
            </label>
            <textarea
              v-model="formularioPago.notas"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              placeholder="Notas adicionales..."
            ></textarea>
          </div>

          <!-- Botones -->
          <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
            <Button
              type="button"
              variant="outline"
              @click="cerrarModalPago"
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              variant="primary"
              :disabled="guardandoPago || !pacienteSeleccionado"
            >
              <div v-if="guardandoPago" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                Guardando...
              </div>
              <span v-else>Registrar Pago</span>
            </Button>
          </div>
        </form>
      </Modal>

      <!-- Modal Emitir Comprobante -->
      <Modal
        :show="modalComprobante"
        @close="cerrarModalComprobante"
        title="Emitir Comprobante Electrónico"
        size="lg"
      >
        <form @submit.prevent="emitirComprobante" class="space-y-6">
          <div v-if="pagoParaComprobante">
            <!-- Información del pago -->
            <div class="p-4 bg-gray-50 rounded-lg mb-4">
              <p class="font-medium text-gray-900">
                {{ pagoParaComprobante.paciente?.nombre }} {{ pagoParaComprobante.paciente?.apellidos }}
              </p>
              <p class="text-sm text-gray-600">Monto: {{ formatearMonto(pagoParaComprobante.monto) }}</p>
            </div>

            <!-- Tipo de comprobante -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tipo de Comprobante <span class="text-red-500">*</span>
              </label>
              <select
                v-model="datosComprobante.tipo"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                required
              >
                <option value="boleta">Boleta (DNI/CE)</option>
                <option value="factura">Factura (RUC)</option>
              </select>
            </div>

            <!-- Serie -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Serie
              </label>
              <input
                v-model="datosComprobante.serie"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                placeholder="B001 / F001"
              />
            </div>

            <!-- Campos para Boleta -->
            <div v-if="datosComprobante.tipo === 'boleta'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Tipo de Documento <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="datosComprobante.tipo_documento_cliente"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  required
                >
                  <option value="1">DNI</option>
                  <option value="4">Carnet de Extranjería</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Número de Documento <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="datosComprobante.numero_documento_cliente"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Nombre Completo <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="datosComprobante.nombre_cliente"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  required
                />
              </div>
            </div>

            <!-- Campos para Factura -->
            <div v-if="datosComprobante.tipo === 'factura'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  RUC <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="datosComprobante.ruc_cliente"
                  type="text"
                  maxlength="11"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  placeholder="20XXXXXXXXX"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Razón Social <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="datosComprobante.razon_social_cliente"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Dirección <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="datosComprobante.direccion_cliente"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  required
                />
              </div>
            </div>
          </div>

          <!-- Botones -->
          <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
            <Button
              type="button"
              variant="outline"
              @click="cerrarModalComprobante"
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              variant="primary"
              :disabled="emitiendoComprobante"
            >
              <div v-if="emitiendoComprobante" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                Emitiendo...
              </div>
              <span v-else>Emitir Comprobante</span>
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import Button from '@/components/common/Button.vue'
import Modal from '@/components/common/Modal.vue'
import { useNotification } from '@/composables/useNotification'
import cajaService, {
  type Pago,
  type EstadisticasCaja,
  type PacienteBusqueda,
  type RegistrarPagoData,
  type DatosComprobante,
  type FiltrosPagos,
  type ListaPagosResponse
} from '@/api/cajaService'// Icons
import {
  CreditCard, Filter, Plus, X, TrendingUp, Clock, AlertCircle,
  DollarSign, FileText, BarChart3, CheckCircle, XCircle, Download
} from 'lucide-vue-next'

const router = useRouter()
const { showSuccess, showError } = useNotification()

// Estado principal
const cargando = ref(true)
const mostrarFiltros = ref(false)
const tabActivo = ref('pagos')

// Datos
const estadisticas = ref<EstadisticasCaja | null>(null)
const pagos = ref<Pago[]>([])
const pagosPendientes = computed(() =>
  pagos.value?.filter(p => p.estado_pago === 'pendiente') || []
)

// Modal pago
const modalPago = ref(false)
const guardandoPago = ref(false)
const formularioPago = ref<RegistrarPagoData>({
  id_paciente: 0,
  monto: 0,
  metodo_pago: 'efectivo',
  concepto: '',
  fecha_pago: new Date().toLocaleDateString('en-CA'), // Fecha actual en formato YYYY-MM-DD
  notas: '',
  emitir_comprobante: false,
  datos_comprobante: undefined
})

// Modal comprobante
const modalComprobante = ref(false)
const pagoParaComprobante = ref<Pago | null>(null)
const emitiendoComprobante = ref(false)
const datosComprobante = ref<DatosComprobante>({
  tipo: 'boleta',
  serie: 'B001',
  tipo_documento_cliente: '1',
  numero_documento_cliente: '',
  nombre_cliente: ''
})

// Generación de PDF
const generandoPDF = ref<number | null>(null)

// Búsqueda de pacientes
const busquedaPaciente = ref('')
const pacientesEncontrados = ref<PacienteBusqueda[]>([])
const pacienteSeleccionado = ref<PacienteBusqueda | null>(null)
const buscandoPacientes = ref(false)
const mostrarResultados = ref(false)

// Filtros
const filtros = ref<FiltrosPagos>({
  estado_pago: undefined,
  tipo_comprobante: undefined,
  metodo_pago: undefined,
  fecha_inicio: undefined,
  fecha_fin: undefined
})

// Configuración de tabs
const tabs = [
  { id: 'pagos', nombre: 'Todos los Pagos', icon: CreditCard },
  { id: 'pendientes', nombre: 'Pendientes', icon: Clock },
  { id: 'estadisticas', nombre: 'Estadísticas', icon: BarChart3 }
]

// Métodos principales
const cargarDatos = async () => {
  try {
    cargando.value = true

    // Inicializar con valores por defecto
    estadisticas.value = null
    pagos.value = []

    console.log('🔄 Cargando datos de caja...')

    const [estadisticasData, pagosData] = await Promise.all([
      cajaService.getEstadisticasCaja(filtros.value),
      cajaService.listarPagos(filtros.value)
    ])

    console.log('📊 Estadísticas recibidas:', estadisticasData)
    console.log('💰 Pagos recibidos:', pagosData)

    estadisticas.value = estadisticasData
    // Extraer el array de pagos del objeto {pagos: [...], pagination: {...}}
    pagos.value = pagosData.pagos || []

    console.log('✅ Pagos cargados:', pagos.value.length, 'pagos')

  } catch (error: any) {
    console.error('❌ Error al cargar datos:', error)
    console.error('Error response:', error?.response?.data)

    // Inicializar con valores vacíos en caso de error
    estadisticas.value = null
    pagos.value = []

    const errorMsg = error?.response?.data?.message || 'Error al cargar los datos de caja'
    showError(errorMsg)
  } finally {
    cargando.value = false
  }
}

// Gestión de pagos
const abrirModalNuevoPago = () => {
  resetearFormulario()
  modalPago.value = true
}

const cerrarModalPago = () => {
  modalPago.value = false
  resetearFormulario()
}

const resetearFormulario = () => {
  formularioPago.value = {
    id_paciente: 0,
    monto: 0,
    metodo_pago: 'efectivo',
    concepto: '',
    fecha_pago: new Date().toLocaleDateString('en-CA'),
    notas: '',
    emitir_comprobante: false,
    datos_comprobante: undefined
  }
  busquedaPaciente.value = ''
  pacientesEncontrados.value = []
  pacienteSeleccionado.value = null
  mostrarResultados.value = false
}

const guardarPago = async () => {
  if (!pacienteSeleccionado.value) {
    showError('Debe seleccionar un paciente')
    return
  }

  try {
    guardandoPago.value = true

    formularioPago.value.id_paciente = pacienteSeleccionado.value.id_paciente

    await cajaService.registrarPago(formularioPago.value)
    showSuccess('Pago registrado exitosamente')

    cerrarModalPago()
    await cargarDatos()
  } catch (error) {
    console.error('Error al guardar pago:', error)
    showError('Error al registrar el pago')
  } finally {
    guardandoPago.value = false
  }
}

// Búsqueda de pacientes
let timeoutBusqueda: ReturnType<typeof setTimeout>

const buscarPacientes = async () => {
  if (busquedaPaciente.value.length < 2) {
    pacientesEncontrados.value = []
    return
  }

  if (timeoutBusqueda) {
    clearTimeout(timeoutBusqueda)
  }

  timeoutBusqueda = setTimeout(async () => {
    try {
      buscandoPacientes.value = true
      const resultados = await cajaService.buscarPacientes(busquedaPaciente.value)
      pacientesEncontrados.value = resultados || []
    } catch (error: any) {
      console.error('Error al buscar pacientes:', error)
      pacientesEncontrados.value = []
      const errorMsg = error?.response?.data?.message || 'Error al buscar pacientes'
      showError(errorMsg)
    } finally {
      buscandoPacientes.value = false
    }
  }, 300)
}

const seleccionarPaciente = (paciente: PacienteBusqueda) => {
  pacienteSeleccionado.value = paciente
  busquedaPaciente.value = `${paciente.nombre} ${paciente.apellidos} - ${paciente.dni}`
  pacientesEncontrados.value = []
  mostrarResultados.value = false

  // Pre-llenar datos para comprobante si está activado
  if (formularioPago.value.emitir_comprobante) {
    if (!formularioPago.value.datos_comprobante) {
      formularioPago.value.datos_comprobante = {
        tipo: 'boleta',
        tipo_documento_cliente: '1',
        numero_documento_cliente: paciente.dni,
        nombre_cliente: `${paciente.nombre} ${paciente.apellidos}`
      }
    } else {
      formularioPago.value.datos_comprobante.numero_documento_cliente = paciente.dni
      formularioPago.value.datos_comprobante.nombre_cliente = `${paciente.nombre} ${paciente.apellidos}`
    }
  }
}

// Emitir comprobante para pago existente
const abrirModalComprobante = (pago: Pago) => {
  console.log('🎫 Abriendo modal para emitir comprobante:', pago)

  if (!pago.id_pago) {
    console.error('❌ Error: Pago sin ID', pago)
    showError('Error: No se puede emitir comprobante para un pago sin ID')
    return
  }

  pagoParaComprobante.value = pago

  // Pre-llenar datos del paciente
  datosComprobante.value = {
    tipo: 'boleta',
    serie: 'B001',
    tipo_documento_cliente: '1',
    numero_documento_cliente: pago.paciente?.dni || '',
    nombre_cliente: `${pago.paciente?.nombre || ''} ${pago.paciente?.apellidos || ''}`.trim()
  }

  modalComprobante.value = true
}

const cerrarModalComprobante = () => {
  modalComprobante.value = false
  pagoParaComprobante.value = null
  datosComprobante.value = {
    tipo: 'boleta',
    serie: 'B001',
    tipo_documento_cliente: '1',
    numero_documento_cliente: '',
    nombre_cliente: ''
  }
}

const emitirComprobante = async () => {
  if (!pagoParaComprobante.value) {
    showError('No hay pago seleccionado')
    return
  }

  if (!pagoParaComprobante.value.id_pago) {
    showError('Error: El pago no tiene un ID válido')
    return
  }

  try {
    emitiendoComprobante.value = true

    console.log('📤 Emitiendo comprobante para pago ID:', pagoParaComprobante.value.id_pago)
    console.log('📋 Datos del comprobante:', datosComprobante.value)

    await cajaService.emitirComprobante(
      pagoParaComprobante.value.id_pago,
      datosComprobante.value
    )

    showSuccess('Comprobante emitido exitosamente')
    cerrarModalComprobante()
    await cargarDatos()
  } catch (error: any) {
    console.error('❌ Error al emitir comprobante:', error)
    console.error('Error response:', error?.response?.data)
    const errorMsg = error?.response?.data?.message || 'Error al emitir el comprobante'
    showError(errorMsg)
  } finally {
    emitiendoComprobante.value = false
  }
}

// Filtros
const aplicarFiltros = () => {
  cargarDatos()
}

const limpiarFiltros = () => {
  filtros.value = {
    estado_pago: undefined,
    tipo_comprobante: undefined,
    metodo_pago: undefined,
    fecha_inicio: undefined,
    fecha_fin: undefined
  }
  aplicarFiltros()
}

// Utilidades
const formatearMonto = (monto: number | undefined) => {
  if (monto === undefined || monto === null) return 'S/ 0.00'
  return cajaService.formatearMonto(monto)
}

const obtenerEtiquetaMetodo = (metodo: string) => {
  return cajaService.obtenerEtiquetaMetodo(metodo)
}

const obtenerClaseEstadoPago = (estado: string) => {
  return cajaService.obtenerClaseEstadoPago(estado)
}

const obtenerClaseEstadoSunat = (estado?: string) => {
  return cajaService.obtenerClaseEstadoSunat(estado)
}

const descargarPDF = (url: string) => {
  // Abrir PDF directamente (ya son PDFs reales generados localmente)
  window.open(url, '_blank')
}

const generarPDF = async (pago: Pago) => {
  try {
    generandoPDF.value = pago.id_pago

    // Determinar tipo de comprobante a solicitar (usar el tipo guardado en el pago si existe)
  // Asegurarse de pasar solo 'boleta' o 'factura' al backend
  const tipo = pago.tipo_comprobante === 'factura' ? 'factura' : 'boleta'

    // Solicitar al backend que genere/stream el PDF y devolver blob
    const resp = await cajaService.descargarPDFPago(pago.id_pago, { tipo })

    if (resp && resp.data) {
      const blob = new Blob([resp.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      window.open(url, '_blank')
      showSuccess('PDF generado y abierto')
    } else {
      showError('Error al generar/descargar el PDF')
    }
  } catch (error: any) {
    console.error('Error al generar PDF:', error)
    const errorMsg = error?.response?.data?.message || 'Error al generar el PDF'
    showError(errorMsg)
  } finally {
    generandoPDF.value = null
  }
}

// Lifecycle
onMounted(() => {
  cargarDatos()
})
</script>
