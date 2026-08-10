<template>
  <MainLayout>
    <div class="container-fluid px-4 py-2">
      
      <!-- Cabecera del Módulo con Botón Exportar PDF -->
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h2 class="brand-font fw-bold text-ayla-dark mb-0">Módulo de Finanzas & Auditoría Gerencial</h2>
          <p class="text-muted small mb-0">Desglose completo de ingresos filtrables por trabajador, servicio y fechas</p>
        </div>
        <button class="btn btn-outline-secondary btn-sm bg-white rounded-3 px-3 py-2" @click="exportarPDF">
          <i class="bi bi-download me-1"></i> Exportar Reporte PDF
        </button>
      </div>

      <!-- Card de Filtros de Auditoría -->
      <div class="card-ayla p-3 mb-4">
        <form @submit.prevent="filtrarAuditoria" class="row g-3 align-items-center">
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Intervalo de Tiempo</label>
            <select class="form-select" v-model="filterForm.periodo">
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

      <!-- Tarjetas KPI (3 Tarjetas en Fila) -->
      <div class="row g-3 mb-4">
        <!-- KPI 1: Ingresos Brutos Auditados -->
        <div class="col-md-4">
          <div class="card-ayla p-4 bg-ayla-dark text-white h-100">
            <span class="small text-white-50 d-block mb-1">Ingresos Brutos Auditados</span>
            <h2 class="brand-font fw-bold mb-1">${{ kpis.ingresos_brutos.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</h2>
            <span class="small text-white-50">En el periodo seleccionado</span>
          </div>
        </div>

        <!-- KPI 2: Total Citas Asistidas -->
        <div class="col-md-4">
          <div class="card-ayla p-4 bg-ayla-cream text-ayla-dark h-100">
            <span class="small text-muted d-block mb-1">Total de Citas Asistidas</span>
            <h2 class="brand-font fw-bold mb-1">{{ kpis.total_citas }} Citas</h2>
            <span class="small text-muted">Promedio: ${{ kpis.promedio_cita.toFixed(2) }} / cita</span>
          </div>
        </div>

        <!-- KPI 3: Trabajador con Mayor Producción -->
        <div class="col-md-4">
          <div class="card-ayla p-4 bg-ayla-rose text-white h-100">
            <span class="small text-white-50 d-block mb-1">Trabajador con Mayor Producción</span>
            <h2 class="brand-font fw-bold mb-1">{{ kpis.top_especialista }}</h2>
            <span class="small text-white-50">Generó ${{ kpis.top_especialista_monto.toFixed(2) }} ({{ kpis.top_especialista_porcentaje }}%)</span>
          </div>
        </div>
      </div>

      <!-- Tabla de Auditoría Detallada por Especialista -->
      <div class="card-ayla p-4">
        <h5 class="brand-font fw-bold text-ayla-dark mb-3">Auditoría Detallada por Especialista</h5>
        <div class="table-responsive">
          <table class="table table-ayla align-middle mb-0">
            <thead>
              <tr>
                <th>Especialista</th>
                <th>Categoría Principal</th>
                <th>Citas Completadas</th>
                <th>Ingreso Generado</th>
                <th>% Aporte Negocio</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in auditoria_especialistas" :key="index">
                <td class="fw-bold text-ayla-dark">{{ item.especialista }}</td>
                <td>{{ item.categoria }}</td>
                <td>{{ item.citas_completadas }}</td>
                <td class="fw-bold text-success">${{ item.ingreso_generado.toFixed(2) }}</td>
                <td>{{ item.aporte_porcentaje }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  filters: Object,
  kpis: Object,
  auditoria_especialistas: Array,
  especialistas_lista: Array,
  servicios_lista: Array
});

const filterForm = ref({
  periodo: props.filters?.periodo || 'agosto_2026',
  especialista_id: props.filters?.especialista_id || '',
  servicio_id: props.filters?.servicio_id || ''
});

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