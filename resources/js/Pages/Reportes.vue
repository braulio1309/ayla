<template>
  <MainLayout>
    <div class="container-fluid px-4 py-2">
      
      <!-- Cabecera del Módulo con Botón Exportar PDF -->
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h2 class="brand-font fw-bold text-ayla-dark mb-0">Módulo de Finanzas & Auditoría Gerencial</h2>
          <p class="text-muted small mb-0">Desglose completo de ingresos filtrables por trabajador, servicio y fechas</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-ayla-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalAylaAdicional">
            <i class="bi bi-plus-circle me-1"></i> Agregar Ayla Adicionales
          </button>
          <button class="btn btn-outline-success btn-sm bg-white rounded-3 px-3 py-2" @click="exportarExcel">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Descargar Excel
          </button>
          <button class="btn btn-outline-secondary btn-sm bg-white rounded-3 px-3 py-2" @click="exportarPDF">
            <i class="bi bi-download me-1"></i> Exportar Reporte PDF
          </button>
        </div>
      </div>

      <!-- Card de Filtros de Auditoría -->
      <div class="card-ayla p-3 mb-4">
        <form @submit.prevent="filtrarAuditoria" class="row g-3 align-items-center">
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Intervalo de Tiempo</label>
            <select class="form-select" v-model="filterForm.periodo">
              <option value="hoy">Hoy</option>
              <option value="agosto_2026">Mensual (Agosto 2026)</option>
              <option value="julio_2026">Mensual (Julio 2026)</option>
              <option value="semanal">Semana Actual</option>
              <option value="anual">Año 2026</option>
            </select>
          </div>
          
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Filtrar por Especialista</label>
            <select class="form-select" v-model="filterForm.especialista_id">
              <option value="">Todos los trabajadores</option>
              <option value="ayla_adicionales">Ayla Adicionales</option>
              <option v-for="e in especialistas_lista" :key="e.id" :value="e.id">{{ e.nombre }}</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Filtrar por Servicio</label>
            <select class="form-select" v-model="filterForm.servicio_id">
              <option value="">Todos los servicios</option>
              <option v-for="s in servicios_lista" :key="s.id" :value="s.id">{{ s.nombre }}</option>
            </select>
          </div>

          <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-ayla-primary w-100 py-2">
              <i class="bi bi-bar-chart-line me-1"></i> Generar Auditoría
            </button>
          </div>
        </form>
      </div>

      <!-- Tarjetas KPI -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card-ayla p-4 bg-ayla-dark text-white h-100">
            <span class="small text-white-50 d-block mb-1">Ingresos Brutos Auditados</span>
            <h2 class="brand-font fw-bold mb-1">${{ kpis.ingresos_brutos.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</h2>
            <span class="d-block">Bs. {{ Number(kpis.ingresos_brutos_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}</span>
            <span class="small text-white-50">En el periodo seleccionado</span>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card-ayla p-4 bg-ayla-cream text-ayla-dark h-100">
            <span class="small text-muted d-block mb-1">Comisión Especialistas</span>
            <h2 class="brand-font fw-bold mb-1">${{ kpis.total_comision_especialistas.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</h2>
            <span class="d-block text-ayla-rose fw-bold small">Bs. {{ Number(kpis.total_comision_especialistas_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
            <span class="small text-muted">Total pagado a profesionales (tasa euro)</span>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card-ayla p-4 bg-ayla-rose text-white h-100">
            <span class="small text-white-50 d-block mb-1">Ganancia del Negocio</span>
            <h2 class="brand-font fw-bold mb-1">${{ kpis.total_negocio.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</h2>
            <span class="d-block text-white fw-bold small">Bs. {{ Number(kpis.total_negocio_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
            <span class="small text-white-50">Monto restante después de comisiones</span>
          </div>
        </div>
      </div>

      <!-- Tabla de Auditoría Detallada por Especialista -->
      <div class="card-ayla p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="brand-font fw-bold text-ayla-dark mb-0">Auditoría Detallada por Especialista</h5>
          <button type="button" class="btn btn-sm btn-outline-secondary" @click="mostrarAuditoria = !mostrarAuditoria">
            <i class="bi" :class="mostrarAuditoria ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            {{ mostrarAuditoria ? 'Recoger' : 'Mostrar' }}
          </button>
        </div>
        <div v-show="mostrarAuditoria" class="table-responsive">
          <table class="table table-ayla align-middle mb-0">
            <thead>
              <tr>
                <th>Especialista</th>
                <th>Categoría Principal</th>
                <th>Citas Completadas</th>
                <th>Generado por especialista</th>
                <th>Ganancia total por comisiones</th>
                <th>Restante para el negocio</th>
                <th>% Aporte Negocio</th>
                <th v-if="filters?.es_semana_actual" class="text-end">Pago semanal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in auditoria_especialistas" :key="index">
                <td class="fw-bold text-ayla-dark">{{ item.especialista }}</td>
                <td>{{ item.categoria }}</td>
                <td>{{ item.citas_completadas }}</td>
                <td class="fw-bold text-success">${{ item.ingreso_generado.toFixed(2) }}<br><span class="small text-ayla-rose">Bs. {{ Number(item.ingreso_generado_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span></td>
                <td class="fw-bold text-ayla-dark">${{ totalComisiones(item).toFixed(2) }}<br><span class="small text-ayla-rose">Bs. {{ formatoBs(totalComisionesBs(item)) }}</span></td>
                <td class="fw-bold text-primary">${{ item.ganancia_negocio.toFixed(2) }}<br><span class="small text-ayla-rose">Bs. {{ Number(item.ganancia_negocio_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span></td>
                <td>{{ item.aporte_porcentaje }}</td>
                <td v-if="filters?.es_semana_actual" class="text-end">
                  <span v-if="!item.user_id" class="text-muted small">No aplica</span>
                  <span v-else-if="item.semana_pagada" class="text-success fs-5" title="Semana pagada">
                    <i class="bi bi-check-circle-fill"></i>
                  </span>
                  <button v-else type="button" class="btn btn-sm btn-outline-success" @click="marcarSemanaPagada(item)">
                    <i class="bi bi-cash-coin me-1"></i>Marcar pagada
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="aylaAdicionales.length" class="card-ayla p-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="brand-font fw-bold text-ayla-dark mb-0">Detalle de Ayla Adicionales</h5>
          <button type="button" class="btn btn-sm btn-outline-secondary" @click="mostrarAylaAdicionales = !mostrarAylaAdicionales">
            <i class="bi" :class="mostrarAylaAdicionales ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            {{ mostrarAylaAdicionales ? 'Recoger' : 'Mostrar' }}
          </button>
        </div>
        <div v-show="mostrarAylaAdicionales" class="table-responsive">
          <table class="table table-ayla align-middle mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th class="text-end">Monto</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(adicional, index) in aylaAdicionales" :key="`${adicional.fecha}-${index}`">
                <td>{{ adicional.fecha }}</td>
                <td>{{ adicional.descripcion }}</td>
                <td class="text-end fw-bold text-success">
                  ${{ Number(adicional.monto || 0).toFixed(2) }}
                  <br><span class="small text-ayla-rose fw-normal">Bs. {{ formatoBs(adicional.monto_bs) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-ayla p-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h5 class="brand-font fw-bold text-ayla-dark mb-0">Agendas detalladas</h5>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">{{ agendas.length }} cita(s) encontradas</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="mostrarAgendas = !mostrarAgendas">
              <i class="bi" :class="mostrarAgendas ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
              {{ mostrarAgendas ? 'Recoger' : 'Mostrar' }}
            </button>
          </div>
        </div>
        <div v-show="mostrarAgendas" class="table-responsive">
          <table class="table table-ayla align-middle mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Paciente</th>
                <th>Servicio</th>
                <th class="text-end">Total ganado</th>
                <th>Asistente</th>
                <th>Estado</th>
                <th class="text-end">Monto</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="agenda in agendas" :key="agenda.id">
                <td>{{ agenda.fecha }}</td>
                <td>{{ agenda.hora }}</td>
                <td class="fw-bold text-ayla-dark">{{ agenda.paciente }}</td>
                <td>
                  <div v-if="agenda.servicios_detalle?.length" class="small">
                    <div v-for="servicio in agenda.servicios_detalle" :key="`${agenda.id}-${servicio.id}`" class="border-bottom py-1">
                      <strong>{{ servicio.nombre }}</strong> · {{ servicio.especialista }}<br>
                      <span>${{ Number(servicio.precio || 0).toFixed(2) }} (Bs. {{ formatoBs(servicio.precio_bs) }}) · Comisión {{ Number(servicio.comision_porcentaje || 0).toFixed(2) }}%: ${{ Number(servicio.comision || 0).toFixed(2) }}</span>
                    </div>
                    <div class="mt-2 text-success fw-bold border-top pt-2">
                      Total ganado: ${{ totalGanadoAgenda(agenda).toFixed(2) }}
                      <span class="text-ayla-rose small">(Bs. {{ formatoBs(totalGanadoAgendaBs(agenda)) }})</span>
                    </div>
                  </div>
                  <span v-else>{{ agenda.servicio }}</span>
                </td>
                <td class="text-end fw-bold text-success">
                  ${{ Number(totalGanadoAgenda(agenda) || 0).toFixed(2) }}
                  <br><span class="small text-ayla-rose fw-normal">Bs. {{ formatoBs(totalGanadoAgendaBs(agenda)) }}</span>
                </td>
                <td>{{ agenda.asistente ? `${agenda.asistente} (${Number(agenda.comision_asistente_porcentaje || 0).toFixed(2)}%)` : 'Sin asistente' }}</td>
                <td><span class="badge bg-ayla-rose">{{ agenda.estado }}</span></td>
                <td class="text-end fw-bold">${{ Number(agenda.monto).toFixed(2) }}<br><span class="small text-ayla-rose">Bs. {{ formatoBs(agenda.monto_bs) }}</span></td>
              </tr>
              <tr v-if="agendas.length === 0">
                <td colspan="8" class="text-center py-4 text-muted">No hay agendas para los filtros seleccionados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal fade" id="modalAylaAdicional" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content card-ayla border-0">
            <div class="modal-header bg-ayla-cream">
              <h5 class="modal-title brand-font fw-bold">Agregar Ayla Adicionales</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form @submit.prevent="guardarAylaAdicional">
              <div class="modal-body p-4">
                <div class="mb-3">
                  <label class="form-label fw-medium">Fecha</label>
                  <input v-model="aylaAdicionalForm.fecha" type="date" class="form-control" required>
                  <span v-if="aylaAdicionalForm.errors.fecha" class="text-danger small">{{ aylaAdicionalForm.errors.fecha }}</span>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-medium">Descripción</label>
                  <input v-model="aylaAdicionalForm.descripcion" type="text" maxlength="255" class="form-control" required>
                  <span v-if="aylaAdicionalForm.errors.descripcion" class="text-danger small">{{ aylaAdicionalForm.errors.descripcion }}</span>
                </div>
                <div>
                  <label class="form-label fw-medium">Monto (USD)</label>
                  <input v-model.number="aylaAdicionalForm.monto" type="number" min="0.01" step="0.01" class="form-control" required>
                  <span v-if="aylaAdicionalForm.errors.monto" class="text-danger small">{{ aylaAdicionalForm.errors.monto }}</span>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-ayla-primary" :disabled="aylaAdicionalForm.processing">Guardar ingreso</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  filters: Object,
  kpis: Object,
  auditoria_especialistas: Array,
  agendas: Array,
  ayla_adicionales: Array,
  especialistas_lista: Array,
  servicios_lista: Array
});

const filterForm = ref({
  periodo: props.filters?.periodo || 'agosto_2026',
  especialista_id: props.filters?.especialista_id || '',
  servicio_id: props.filters?.servicio_id || ''
});

const agendas = computed(() => props.agendas || []);
const aylaAdicionales = computed(() => props.ayla_adicionales || []);
const mostrarAuditoria = ref(true);
const mostrarAgendas = ref(true);
const mostrarAylaAdicionales = ref(true);

const fechaActual = new Date();
const fechaHoy = `${fechaActual.getFullYear()}-${String(fechaActual.getMonth() + 1).padStart(2, '0')}-${String(fechaActual.getDate()).padStart(2, '0')}`;
const aylaAdicionalForm = useForm({
  fecha: fechaHoy,
  descripcion: '',
  monto: null,
});

const formatoBs = (monto) => Number(monto || 0).toLocaleString('es-VE', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2
});

const totalGanadoServiciosAgenda = (agenda) => {
  const servicios = Array.isArray(agenda?.servicios_detalle) ? agenda.servicios_detalle : [];
  return servicios.reduce((total, servicio) => total + Number(servicio?.comision || 0), 0);
};

const totalGanadoServiciosAgendaBs = (agenda) => {
  const servicios = Array.isArray(agenda?.servicios_detalle) ? agenda.servicios_detalle : [];
  return servicios.reduce((total, servicio) => total + Number(servicio?.comision_bs || 0), 0);
};

const totalGanadoAgenda = (agenda) => Number(agenda?.total_ganado_filtrado ?? totalGanadoServiciosAgenda(agenda));

const totalGanadoAgendaBs = (agenda) => Number(agenda?.total_ganado_filtrado_bs ?? totalGanadoServiciosAgendaBs(agenda));

const totalComisiones = (item) => Number(item?.comision_especialista || 0) + Number(item?.comision_asistentes || 0);

const totalComisionesBs = (item) => Number(item?.comision_especialista_bs || 0) + Number(item?.comision_asistentes_bs || 0);

const guardarAylaAdicional = () => {
  aylaAdicionalForm.post('/reportes/ayla-adicionales', {
    preserveScroll: true,
    onSuccess: () => {
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalAylaAdicional'));
      if (modal) modal.hide();
      aylaAdicionalForm.reset('descripcion', 'monto');
      aylaAdicionalForm.fecha = fechaHoy;
    },
  });
};

const marcarSemanaPagada = (item) => {
  if (!item?.user_id || !confirm(`¿Marcar como pagada la semana actual de ${item.especialista}?`)) return;

  router.post('/reportes/pagos-semanales', {
    especialista_id: item.user_id,
  }, {
    preserveScroll: true,
  });
};

const exportarExcel = () => {
  const parametros = new URLSearchParams({
    periodo: filterForm.value.periodo || '',
    especialista_id: filterForm.value.especialista_id || '',
    servicio_id: filterForm.value.servicio_id || ''
  });

  window.location.href = `/reportes/exportar?${parametros.toString()}`;
};

const filtrarAuditoria = () => {
  router.get('/reportes', filterForm.value, {
    preserveState: true,
    replace: true
  });
};

const exportarPDF = () => {
  window.print();
};
</script>