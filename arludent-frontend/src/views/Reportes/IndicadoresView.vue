<template>
  <DashboardLayout>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
        <BarChart3 class="w-8 h-8 text-blue-600" />
        Indicadores y KPIs
      </h1>
      <p class="text-sm text-gray-600">Métricas clave del desempeño del negocio</p>
    </div>

    <!-- Filtros -->
    <Card class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2">Fecha Inicio</label>
          <input v-model="filtros.fecha_inicio" type="date" class="w-full px-3 py-2 border rounded-lg" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Fecha Fin</label>
          <input v-model="filtros.fecha_fin" type="date" class="w-full px-3 py-2 border rounded-lg" />
        </div>
        <div class="flex items-end gap-2">
          <button
            @click="cargarDatos"
            :disabled="cargando"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <RefreshCw :class="{ 'animate-spin': cargando }" class="w-4 h-4 inline mr-2" />
            {{ cargando ? 'Cargando...' : 'Actualizar' }}
          </button>
        </div>
      </div>
    </Card>

    <!-- Loading State -->
    <div v-if="cargando && !kpis" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-primary-200 border-t-primary-600 mb-4"></div>
      <p class="text-gray-600">Cargando indicadores...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error && !kpis">
      <Card class="mb-6 border-red-200 bg-red-50">
        <div class="flex items-center gap-3 text-red-800">
          <AlertCircle class="w-5 h-5" />
          <div>
            <p class="font-semibold">Error al cargar los datos</p>
            <p class="text-sm">{{ error }}</p>
          </div>
        </div>
      </Card>
    </div>

    <!-- Contenido Principal -->
    <div v-else-if="kpis">
      <!-- KPIs Principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <Card class="bg-gradient-to-br from-green-50 to-white border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Ingresos Totales</p>
              <p class="text-2xl font-bold text-green-600">S/ {{ Number(kpis.kpis.total_ingresos || 0).toFixed(2) }}</p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
              <DollarSign class="w-8 h-8 text-green-600" />
            </div>
          </div>
        </Card>
        <Card class="bg-gradient-to-br from-blue-50 to-white border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Total Citas</p>
              <p class="text-2xl font-bold text-blue-600">{{ kpis.kpis.total_citas }}</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
              <Calendar class="w-8 h-8 text-blue-600" />
            </div>
          </div>
        </Card>
        <Card class="bg-gradient-to-br from-cyan-50 to-white border-l-4 border-cyan-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Nuevos Pacientes</p>
              <p class="text-2xl font-bold text-cyan-600">{{ kpis.kpis.nuevos_pacientes }}</p>
            </div>
            <div class="bg-cyan-100 p-3 rounded-full">
              <UserPlus class="w-8 h-8 text-cyan-600" />
            </div>
          </div>
        </Card>
        <Card class="bg-gradient-to-br from-purple-50 to-white border-l-4 border-purple-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Tratamientos Realizados</p>
              <p class="text-2xl font-bold text-purple-600">{{ kpis.kpis.tratamientos_realizados }}</p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
              <Activity class="w-8 h-8 text-purple-600" />
            </div>
          </div>
        </Card>
        <Card class="bg-gradient-to-br from-emerald-50 to-white border-l-4 border-emerald-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Tasa de Asistencia</p>
              <p class="text-2xl font-bold text-emerald-600">{{ kpis.kpis.tasa_asistencia }}%</p>
            </div>
            <div class="bg-emerald-100 p-3 rounded-full">
              <TrendingUp class="w-8 h-8 text-emerald-600" />
            </div>
          </div>
        </Card>
        <Card class="bg-gradient-to-br from-orange-50 to-white border-l-4 border-orange-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Ticket Promedio</p>
              <p class="text-2xl font-bold text-orange-600">S/ {{ Number(kpis.kpis.ticket_promedio || 0).toFixed(2) }}</p>
            </div>
            <div class="bg-orange-100 p-3 rounded-full">
              <DollarSign class="w-8 h-8 text-orange-600" />
            </div>
          </div>
        </Card>
      </div>


      <!-- Sección: Gráficos Principales -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

        <!-- Gráfico: Top 10 Tratamientos (Columna Grande) -->
        <Card v-if="tratamientos" class="xl:col-span-2">
          <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            <Target class="w-5 h-5 text-purple-600" />
            Top 10 Tratamientos por Ingresos
          </h3>
          <div class="space-y-3">
            <div v-for="(item, index) in tratamientos.tratamientos.slice(0, 10)" :key="item.id_tratamiento">
              <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                  <span
                    class="text-xs font-bold w-7 h-7 flex items-center justify-center rounded-full flex-shrink-0"
                    :class="[
                      index === 0 ? 'bg-purple-600 text-white' :
                      index === 1 ? 'bg-blue-600 text-white' :
                      index === 2 ? 'bg-green-600 text-white' :
                      'bg-gray-400 text-white'
                    ]"
                  >
                    {{ index + 1 }}
                  </span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate">{{ item.tratamiento.nombre }}</p>
                    <p class="text-xs text-gray-500">
                      <span class="px-2 py-0.5 bg-gray-100 rounded-full">{{ item.tratamiento.categoria }}</span>
                    </p>
                  </div>
                </div>
                <div class="text-right flex-shrink-0 ml-4">
                  <p class="text-sm font-bold text-green-600">S/ {{ Number(item.ingresos_totales || 0).toLocaleString('es-PE') }}</p>
                  <p class="text-xs text-gray-500">{{ item.cantidad }} {{ item.cantidad === 1 ? 'vez' : 'veces' }}</p>
                </div>
              </div>
              <!-- Barra de progreso -->
              <div class="relative w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                <div
                  class="absolute h-full rounded-full transition-all duration-700 ease-out"
                  :class="[
                    index === 0 ? 'bg-gradient-to-r from-purple-500 to-purple-600' :
                    index === 1 ? 'bg-gradient-to-r from-blue-500 to-blue-600' :
                    index === 2 ? 'bg-gradient-to-r from-green-500 to-green-600' :
                    index === 3 ? 'bg-gradient-to-r from-yellow-400 to-yellow-500' :
                    'bg-gradient-to-r from-gray-300 to-gray-400'
                  ]"
                  :style="{
                    width: `${Math.max((item.ingresos_totales / tratamientos.tratamientos[0].ingresos_totales) * 100, 5)}%`
                  }"
                ></div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Gráfico: Resumen de Crecimiento (Columna Pequeña) -->
        <Card v-if="tendencias && tendencias.periodo_actual !== undefined" class="xl:col-span-1">
          <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            <TrendingUp class="w-5 h-5 text-green-600" />
            Resumen Financiero
          </h3>

          <div class="space-y-4">
            <!-- Período Actual -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-xl border-2 border-blue-200">
              <p class="text-xs text-blue-700 font-semibold mb-1">PERÍODO ACTUAL</p>
              <p class="text-3xl font-black text-blue-900">S/ {{ (Number(tendencias.periodo_actual || 0) / 1000).toFixed(1) }}k</p>
              <p class="text-xs text-blue-600 mt-1">{{ new Date().toLocaleDateString('es-PE', { month: 'long', year: 'numeric' }) }}</p>
            </div>

            <!-- Período Anterior -->
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
              <p class="text-xs text-gray-600 font-semibold mb-1">PERÍODO ANTERIOR</p>
              <p class="text-2xl font-bold text-gray-900">S/ {{ (Number(tendencias.periodo_anterior || 0) / 1000).toFixed(1) }}k</p>
            </div>

            <!-- Crecimiento -->
            <div
              class="p-4 rounded-xl border-2"
              :class="
                (tendencias.crecimiento_porcentual || 0) >= 0
                  ? 'bg-gradient-to-br from-green-50 to-green-100 border-green-300'
                  : 'bg-gradient-to-br from-red-50 to-red-100 border-red-300'
              "
            >
              <p class="text-xs font-semibold mb-2" :class="(tendencias.crecimiento_porcentual || 0) >= 0 ? 'text-green-700' : 'text-red-700'">
                {{ (tendencias.crecimiento_porcentual || 0) >= 0 ? '📈 CRECIMIENTO' : '📉 DECRECIMIENTO' }}
              </p>
              <div class="flex items-center gap-2">
                <div
                  class="w-12 h-12 rounded-full flex items-center justify-center"
                  :class="(tendencias.crecimiento_porcentual || 0) >= 0 ? 'bg-green-500' : 'bg-red-500'"
                >
                  <TrendingUp
                    class="w-6 h-6 text-white"
                    :class="{ 'rotate-180': (tendencias.crecimiento_porcentual || 0) < 0 }"
                  />
                </div>
                <div>
                  <p
                    class="text-3xl font-black"
                    :class="(tendencias.crecimiento_porcentual || 0) >= 0 ? 'text-green-900' : 'text-red-900'"
                  >
                    {{ (tendencias.crecimiento_porcentual || 0) >= 0 ? '+' : '' }}{{ Number(tendencias.crecimiento_porcentual || 0).toFixed(1) }}%
                  </p>
                  <p class="text-xs" :class="(tendencias.crecimiento_porcentual || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                    vs. mes anterior
                  </p>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Grid 2 Columnas: Desempeño Médicos + Tendencias de Ingresos -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Sección: Desempeño Médicos -->
        <Card v-if="citasMedico">
          <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            <Users class="w-5 h-5 text-blue-600" />
            Desempeño por Médico
          </h3>
          <div class="grid grid-cols-1 gap-4 max-h-[600px] overflow-y-auto">
            <div
              v-for="(item, index) in citasMedico.por_medico"
              :key="item.id_medico"
              class="relative bg-gradient-to-br from-white to-gray-50 border-2 rounded-xl p-4 hover:shadow-lg transition-all"
              :class="
                item.tasa_completitud >= 80 ? 'border-green-300' :
                item.tasa_completitud >= 60 ? 'border-yellow-300' : 'border-red-300'
              "
            >
              <!-- Badge de posición -->
              <div
                class="absolute -top-3 -left-3 w-10 h-10 rounded-full flex items-center justify-center font-bold text-white shadow-lg"
                :class="
                  index === 0 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' :
                  index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-500' :
                  index === 2 ? 'bg-gradient-to-br from-orange-400 to-orange-600' :
                  'bg-gradient-to-br from-blue-400 to-blue-600'
                "
              >
                {{ index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : index + 1 }}
              </div>

              <!-- Info del médico -->
              <div class="mb-3">
                <h4 class="font-bold text-gray-900 text-base">{{ item.medico.nombres }} {{ item.medico.apellidos }}</h4>
                <p class="text-xs text-gray-500 font-medium">{{ item.medico.especialidad }}</p>
              </div>

              <!-- Stats -->
              <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="bg-blue-50 rounded-lg p-2 text-center">
                  <Calendar class="w-4 h-4 text-blue-600 mx-auto mb-1" />
                  <p class="text-xl font-bold text-blue-900">{{ item.total_citas }}</p>
                  <p class="text-xs text-blue-600">Total Citas</p>
                </div>
                <div class="bg-green-50 rounded-lg p-2 text-center">
                  <Activity class="w-4 h-4 text-green-600 mx-auto mb-1" />
                  <p class="text-xl font-bold text-green-900">{{ item.completadas }}</p>
                  <p class="text-xs text-green-600">Completadas</p>
                </div>
              </div>

              <!-- Barra de completitud -->
              <div class="mb-2">
                <div class="flex justify-between items-center mb-1">
                  <span class="text-xs text-gray-600 font-medium">Tasa de Completitud</span>
                  <span
                    class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="
                      item.tasa_completitud >= 80 ? 'bg-green-100 text-green-800' :
                      item.tasa_completitud >= 60 ? 'bg-yellow-100 text-yellow-800' :
                      'bg-red-100 text-red-800'
                    "
                  >
                    {{ item.tasa_completitud }}%
                  </span>
                </div>
                <div class="relative w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    class="absolute h-full rounded-full transition-all duration-700"
                    :class="
                      item.tasa_completitud >= 80 ? 'bg-gradient-to-r from-green-400 to-green-600' :
                      item.tasa_completitud >= 60 ? 'bg-gradient-to-r from-yellow-400 to-yellow-600' :
                      'bg-gradient-to-r from-red-400 to-red-600'
                    "
                    :style="{ width: `${item.tasa_completitud}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Gráfico: Tendencias de Ingresos (Barras Mejoradas) -->
        <Card v-if="tendencias && tendencias.periodo_actual !== undefined">
          <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            <BarChart3 class="w-5 h-5 text-indigo-600" />
            Tendencias de Ingresos - Últimos 12 Meses
          </h3>

          <!-- Gráfico de Barras Mejorado -->
          <div class="bg-gradient-to-b from-gray-50 to-white p-6 rounded-xl border border-gray-200 max-h-[600px] overflow-y-auto">
            <div class="flex items-end justify-between h-72 gap-1">
            <div
              v-for="(item, index) in tendencias.tendencias.slice(-12)"
              :key="item.periodo"
              class="flex-1 flex flex-col items-center gap-2 group cursor-pointer"
            >
              <!-- Barra -->
              <div class="w-full flex flex-col items-center relative">
                <!-- Valor encima -->
                <span class="text-[10px] font-bold text-gray-700 mb-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  S/ {{ (item.total / 1000).toFixed(1) }}k
                </span>

                <!-- Barra con gradiente -->
                <div
                  class="w-full rounded-t-lg transition-all duration-700 ease-out relative overflow-hidden group-hover:scale-105"
                  :style="{
                    height: `${Math.max((item.total / Math.max(...tendencias.tendencias.map(t => t.total))) * 240, 30)}px`,
                    minHeight: '30px'
                  }"
                >
                  <!-- Gradiente de fondo -->
                  <div
                    class="absolute inset-0"
                    :class="
                      index === tendencias.tendencias.slice(-12).length - 1
                        ? 'bg-gradient-to-t from-indigo-600 via-indigo-500 to-indigo-400'
                        : index === tendencias.tendencias.slice(-12).length - 2
                          ? 'bg-gradient-to-t from-blue-500 via-blue-400 to-blue-300'
                          : 'bg-gradient-to-t from-gray-400 via-gray-300 to-gray-200'
                    "
                  ></div>

                  <!-- Efecto de brillo -->
                  <div class="absolute inset-0 bg-gradient-to-b from-white/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </div>

                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-20 animate-fadeIn">
                  <div class="bg-gray-900 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap shadow-xl">
                    <p class="font-bold text-sm mb-1">{{ item.periodo }}</p>
                    <div class="space-y-0.5">
                      <p>💰 Total: <span class="font-bold">S/ {{ Number(item.total || 0).toLocaleString('es-PE', { minimumFractionDigits: 2 }) }}</span></p>
                      <p>📅 Citas: <span class="font-bold">{{ item.cantidad || 0 }}</span></p>
                      <p>📊 Promedio: <span class="font-bold">S/ {{ Number(item.promedio || 0).toFixed(2) }}</span></p>
                    </div>
                    <!-- Flecha del tooltip -->
                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                      <div class="w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Etiqueta del mes -->
              <span
                class="text-[10px] text-gray-600 font-semibold mt-1"
                :class="
                  index === tendencias.tendencias.slice(-12).length - 1
                    ? 'text-indigo-700 font-bold'
                    : ''
                "
              >
                {{ item.periodo.split('-')[1] }}/{{ item.periodo.split('-')[0].slice(-2) }}
              </span>
            </div>
          </div>

          <!-- Leyenda -->
          <div class="mt-6 flex items-center justify-center gap-4 text-xs">
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 rounded bg-gradient-to-t from-indigo-600 to-indigo-400"></div>
              <span class="text-gray-600">Mes Actual</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 rounded bg-gradient-to-t from-blue-500 to-blue-300"></div>
              <span class="text-gray-600">Mes Anterior</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 rounded bg-gradient-to-t from-gray-400 to-gray-200"></div>
              <span class="text-gray-600">Meses Previos</span>
            </div>
          </div>
        </div>
      </Card>
      </div>

      <!-- Gráfico: Satisfacción de Pacientes (Detallado) -->
      <Card v-if="satisfaccion" class="mb-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold flex items-center gap-2">
              <Star class="w-5 h-5 text-yellow-500 fill-yellow-500" />
              Satisfacción de Pacientes
            </h3>
            <p class="text-sm text-gray-500 mt-1">Análisis detallado de calificaciones y comentarios</p>
          </div>
          <div class="text-right">
            <p class="text-3xl font-black text-yellow-600">{{ Number(satisfaccion.promedio_general || 0).toFixed(1) }}</p>
            <div class="flex items-center gap-1 justify-end">
              <Star v-for="i in 5" :key="i"
                :class="i <= Math.round(satisfaccion.promedio_general) ? 'text-yellow-500 fill-yellow-500' : 'text-gray-300'"
                class="w-4 h-4"
              />
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ satisfaccion.total_calificaciones }} calificaciones</p>
          </div>
        </div>

        <!-- Grid: Distribución + Nivel de Satisfacción -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

          <!-- Distribución por Estrellas (2 columnas) -->
          <div class="lg:col-span-2">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <BarChart3 class="w-4 h-4 text-purple-600" />
              Distribución por Calificación
            </h4>
            <div class="space-y-3">
              <div v-for="dist in satisfaccion.distribucion" :key="dist.puntuacion" class="flex items-center gap-3">
                <!-- Estrellas -->
                <div class="flex items-center gap-1 w-24 flex-shrink-0">
                  <Star v-for="i in dist.puntuacion" :key="i"
                    class="w-4 h-4 text-yellow-500 fill-yellow-500"
                  />
                  <Star v-for="i in (5 - dist.puntuacion)" :key="'empty-' + i"
                    class="w-4 h-4 text-gray-300"
                  />
                </div>

                <!-- Barra de progreso -->
                <div class="flex-1">
                  <div class="relative w-full h-8 bg-gray-100 rounded-lg overflow-hidden">
                    <div
                      class="absolute h-full rounded-lg transition-all duration-700 flex items-center justify-end px-3"
                      :class="
                        dist.puntuacion === 5 ? 'bg-gradient-to-r from-green-400 to-green-600' :
                        dist.puntuacion === 4 ? 'bg-gradient-to-r from-blue-400 to-blue-600' :
                        dist.puntuacion === 3 ? 'bg-gradient-to-r from-yellow-400 to-yellow-600' :
                        dist.puntuacion === 2 ? 'bg-gradient-to-r from-orange-400 to-orange-600' :
                        'bg-gradient-to-r from-red-400 to-red-600'
                      "
                      :style="{
                        width: `${satisfaccion.total_calificaciones > 0 ? (dist.cantidad / satisfaccion.total_calificaciones) * 100 : 0}%`
                      }"
                    >
                      <span class="text-white font-bold text-sm" v-if="dist.cantidad > 0">
                        {{ dist.cantidad }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Porcentaje -->
                <div class="w-16 text-right">
                  <span class="text-sm font-bold text-gray-700">
                    {{ satisfaccion.total_calificaciones > 0 ? ((dist.cantidad / satisfaccion.total_calificaciones) * 100).toFixed(1) : 0 }}%
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Nivel de Satisfacción (1 columna) -->
          <div class="lg:col-span-1">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <ThumbsUp class="w-4 h-4 text-green-600" />
              Nivel General
            </h4>
            <div
              class="p-6 rounded-2xl border-2 text-center"
              :class="
                satisfaccion.nivel_satisfaccion === 'Excelente' ? 'bg-gradient-to-br from-green-50 to-green-100 border-green-300' :
                satisfaccion.nivel_satisfaccion === 'Muy Bueno' ? 'bg-gradient-to-br from-blue-50 to-blue-100 border-blue-300' :
                satisfaccion.nivel_satisfaccion === 'Bueno' ? 'bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-300' :
                satisfaccion.nivel_satisfaccion === 'Regular' ? 'bg-gradient-to-br from-orange-50 to-orange-100 border-orange-300' :
                'bg-gradient-to-br from-red-50 to-red-100 border-red-300'
              "
            >
              <div
                class="w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-3"
                :class="
                  satisfaccion.nivel_satisfaccion === 'Excelente' ? 'bg-green-500' :
                  satisfaccion.nivel_satisfaccion === 'Muy Bueno' ? 'bg-blue-500' :
                  satisfaccion.nivel_satisfaccion === 'Bueno' ? 'bg-yellow-500' :
                  satisfaccion.nivel_satisfaccion === 'Regular' ? 'bg-orange-500' :
                  'bg-red-500'
                "
              >
                <span class="text-3xl">
                  {{ satisfaccion.nivel_satisfaccion === 'Excelente' ? '😄' :
                     satisfaccion.nivel_satisfaccion === 'Muy Bueno' ? '😊' :
                     satisfaccion.nivel_satisfaccion === 'Bueno' ? '🙂' :
                     satisfaccion.nivel_satisfaccion === 'Regular' ? '😐' : '😞' }}
                </span>
              </div>
              <p
                class="text-2xl font-black mb-1"
                :class="
                  satisfaccion.nivel_satisfaccion === 'Excelente' ? 'text-green-900' :
                  satisfaccion.nivel_satisfaccion === 'Muy Bueno' ? 'text-blue-900' :
                  satisfaccion.nivel_satisfaccion === 'Bueno' ? 'text-yellow-900' :
                  satisfaccion.nivel_satisfaccion === 'Regular' ? 'text-orange-900' :
                  'text-red-900'
                "
              >
                {{ satisfaccion.nivel_satisfaccion }}
              </p>
              <p class="text-xs text-gray-600 font-medium">Nivel de Satisfacción</p>
            </div>
          </div>
        </div>

        <!-- Últimos Comentarios -->
        <div v-if="satisfaccion.ultimas_calificaciones && satisfaccion.ultimas_calificaciones.length > 0">
          <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <MessageSquare class="w-4 h-4 text-blue-600" />
            Comentarios Recientes de Pacientes
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="cal in satisfaccion.ultimas_calificaciones"
              :key="cal.id_calificacion"
              class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow"
            >
              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <p class="font-semibold text-gray-900">{{ cal.nombre_paciente }}</p>
                  <p class="text-xs text-gray-500">Atendido por: Dr(a). {{ cal.nombre_medico }}</p>
                </div>
                <div class="flex items-center gap-1">
                  <Star v-for="i in cal.puntuacion" :key="i"
                    class="w-4 h-4 text-yellow-500 fill-yellow-500"
                  />
                  <Star v-for="i in (5 - cal.puntuacion)" :key="'empty-' + i"
                    class="w-4 h-4 text-gray-300"
                  />
                </div>
              </div>

              <!-- Comentario -->
              <div class="bg-white rounded-lg p-3 border border-gray-100">
                <p class="text-sm text-gray-700 italic leading-relaxed">
                  "{{ cal.comentario || 'Sin comentarios adicionales' }}"
                </p>
              </div>

              <!-- Fecha -->
              <p class="text-xs text-gray-400 mt-2">
                {{ new Date(cal.fecha).toLocaleDateString('es-PE', {
                  day: 'numeric',
                  month: 'long',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit'
                }) }}
              </p>
            </div>
          </div>

          <!-- Mensaje si no hay comentarios -->
          <div v-if="satisfaccion.ultimas_calificaciones.length === 0" class="text-center py-8 text-gray-500">
            <MessageSquare class="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p>No hay comentarios recientes</p>
          </div>
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import {
  BarChart3,
  DollarSign,
  Calendar,
  UserPlus,
  Activity,
  TrendingUp,
  Target,
  Users,
  RefreshCw,
  AlertCircle,
  Star,
  MessageSquare,
  ThumbsUp
} from 'lucide-vue-next'
import * as indicadoresService from '@/api/indicadoresService'
import type {
  DashboardKPIsResponse,
  TratamientosSolicitados,
  CitasPorMedicoResponse,
  TendenciasIngresosResponse,
  SatisfaccionPacientes
} from '@/api/indicadoresService'

const cargando = ref(false)
const error = ref<string | null>(null)

const filtros = ref({
  fecha_inicio: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  fecha_fin: new Date().toISOString().split('T')[0]
})

const kpis = ref<DashboardKPIsResponse | null>(null)
const tratamientos = ref<TratamientosSolicitados | null>(null)
const citasMedico = ref<CitasPorMedicoResponse | null>(null)
const tendencias = ref<TendenciasIngresosResponse | null>(null)
const satisfaccion = ref<SatisfaccionPacientes | null>(null)

const cargarDatos = async () => {
  cargando.value = true
  error.value = null

  const filtrosLimpios: any = {}
  if (filtros.value.fecha_inicio) filtrosLimpios.fecha_inicio = filtros.value.fecha_inicio
  if (filtros.value.fecha_fin) filtrosLimpios.fecha_fin = filtros.value.fecha_fin

  try {
    // Cargar KPIs primero
    const kpisData = await indicadoresService.obtenerDashboardKPIs(filtrosLimpios)
    kpis.value = kpisData

    // Cargar el resto en paralelo pero con manejo individual
    const [tratamientosData, citasData, tendenciasData, satisfaccionData] = await Promise.allSettled([
      indicadoresService.obtenerTratamientosSolicitados({ ...filtrosLimpios, limit: 10 }),
      indicadoresService.obtenerCitasPorMedico(filtrosLimpios),
      indicadoresService.obtenerTendenciasIngresos({ ...filtrosLimpios, periodo: 'mensual' }),
      indicadoresService.obtenerSatisfaccionPacientes(filtrosLimpios)
    ])

    // Asignar datos solo si fueron exitosos
    if (tratamientosData.status === 'fulfilled') {
      tratamientos.value = tratamientosData.value
    } else {
      console.error('Error cargando tratamientos:', tratamientosData.reason)
    }

    if (citasData.status === 'fulfilled') {
      citasMedico.value = citasData.value
    } else {
      console.error('Error cargando citas por médico:', citasData.reason)
    }

    if (tendenciasData.status === 'fulfilled') {
      tendencias.value = tendenciasData.value
    } else {
      console.error('Error cargando tendencias:', tendenciasData.reason)
    }

    if (satisfaccionData.status === 'fulfilled') {
      satisfaccion.value = satisfaccionData.value
    } else {
      console.error('Error cargando satisfacción:', satisfaccionData.reason)
    }
  } catch (err: any) {
    console.error('Error al cargar indicadores:', err)
    error.value = err.response?.data?.message || 'Error al cargar los indicadores. Por favor, intenta nuevamente.'
  } finally {
    cargando.value = false
  }
}

onMounted(cargarDatos)
</script>
