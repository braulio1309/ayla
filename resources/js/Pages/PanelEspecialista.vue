<template>
  <MainLayout>
    <div class="container-fluid px-4">
      
      <!-- Banner Principal con Borde Rosa y Badge -->
      <div class="card-ayla p-4 mb-4 bg-white border-start border-4 border-ayla-rose">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <span class="badge bg-ayla-rose mb-2 fw-normal px-3 py-1">Perfil Restringido</span>
            <h2 class="brand-font fw-bold text-ayla-dark mb-1">Panel de Rendimiento Personal</h2>
            <p class="text-muted small mb-0">
              Bienvenida(o), <strong>{{ especialistaSegura.nombre }}</strong> ({{ especialistaSegura.especialidad }})
            </p>
          </div>
          <div class="text-end">
            <span class="text-muted small d-block">Tu comisión actual: {{ especialistaSegura.comision }}%</span>
            <h2 class="brand-font fw-bold text-ayla-dark mb-0">${{ comisionTotalSeguro.toFixed(2) }}</h2>
            <span class="text-ayla-rose small">Bs. {{ formatoBs(comisionTotalBsSeguro) }}</span>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="card-ayla p-4 h-100">
            <span class="small text-muted d-block mb-2">Mi total generado</span>
            <h3 class="brand-font fw-bold text-ayla-dark mb-0">${{ totalGeneradoSeguro.toFixed(2) }}</h3>
            <span class="text-ayla-rose small">Bs. {{ formatoBs(totalGeneradoBsSeguro) }}</span>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-ayla p-4 h-100">
            <span class="small text-muted d-block mb-2">Mi comisión</span>
            <h3 class="brand-font fw-bold text-success mb-0">${{ comisionTotalSeguro.toFixed(2) }}</h3>
            <span class="text-ayla-rose small">Bs. {{ formatoBs(comisionTotalBsSeguro) }}</span>
            <span class="text-muted small d-block mt-2">Por asistencias: ${{ Number(props.comision_asistente || 0).toFixed(2) }} / Bs. {{ formatoBs(props.comision_asistente_bs) }}</span>
          </div>
        </div>
      </div>

      <!-- Card de Filtro por Rango de Fechas -->
      <div class="card-ayla p-3 mb-4">
        <form @submit.prevent="consultarProduccion" class="row g-3 align-items-center">
          <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Fecha Inicio</label>
            <input type="date" class="form-control" v-model="filterForm.fecha_inicio">
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Fecha Fin</label>
            <input type="date" class="form-control" v-model="filterForm.fecha_fin">
          </div>
          <div class="col-md-4 align-self-end">
            <button type="submit" class="btn btn-ayla-secondary w-100 py-2">
              <i class="bi bi-filter me-1"></i> Consultar Producción
            </button>
          </div>
        </form>
      </div>

      <!-- Tabla de Historial de Atenciones con Encabezado Beige -->
      <div class="card-ayla p-4">
        <h5 class="brand-font fw-bold text-ayla-dark mb-3">Historial de Mis Atenciones</h5>
        <div class="table-responsive">
          <table class="table table-ayla align-middle mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Paciente Atendido</th>
                <th>Servicios Realizados</th>
                <th>Participación</th>
                <th class="text-end">Monto Generado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(a, index) in atencionesSeguras" :key="index">
                <td>{{ a.fecha }}</td>
                <td class="fw-bold text-ayla-dark">{{ a.paciente }}</td>
                <td>{{ a.servicio }}</td>
                <td>
                  <span v-if="a.es_asistente" class="badge bg-ayla-rose">Asistente (3%)</span>
                  <span v-else class="badge bg-ayla-dark">Principal</span>
                </td>
                <td class="text-end fw-bold text-success">${{ Number(a.monto ?? 0).toFixed(2) }}<br><span class="small text-ayla-rose">Bs. {{ formatoBs(a.monto_bs) }}</span></td>
              </tr>
              <tr v-if="atencionesSeguras.length === 0">
                <td colspan="5" class="text-center py-4 text-muted">
                  No se registraron atenciones en el intervalo de fechas seleccionado.
                </td>
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
import { computed, ref } from 'vue';

const props = defineProps({
  especialista: Object,
  total_generado: Number,
  total_generado_bs: Number,
  comision_total: Number,
  comision_total_bs: Number,
  comision_asistente: Number,
  comision_asistente_bs: Number,
  atenciones: Array,
  filters: Object
});

const especialistaSegura = computed(() => props.especialista || { nombre: '', especialidad: '', comision: 0 });
const totalGeneradoSeguro = computed(() => Number(props.total_generado ?? 0));
const totalGeneradoBsSeguro = computed(() => Number(props.total_generado_bs ?? 0));
const comisionTotalSeguro = computed(() => Number(props.comision_total ?? 0));
const comisionTotalBsSeguro = computed(() => Number(props.comision_total_bs ?? 0));
const atencionesSeguras = computed(() => props.atenciones || []);

const formatoBs = (monto) => Number(monto ?? 0).toLocaleString('es-VE', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

const filterForm = ref({
  fecha_inicio: props.filters?.fecha_inicio || '2026-08-01',
  fecha_fin: props.filters?.fecha_fin || '2026-08-31'
});

const consultarProduccion = () => {
  router.get('/panel-especialista', filterForm.value, {
    preserveState: true,
    replace: true
  });
};
</script>