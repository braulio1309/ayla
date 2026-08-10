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
              Bienvenida(o), <strong>{{ especialista.nombre }}</strong> ({{ especialista.especialidad }})
            </p>
          </div>
          <div class="text-end">
            <span class="text-muted small d-block">Su comisión acumulada:</span>
            <h2 class="brand-font fw-bold text-ayla-dark mb-0">${{ comision_total.toFixed(2) }}</h2>
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
                <th class="text-end">Monto Generado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(a, index) in atenciones" :key="index">
                <td>{{ a.fecha }}</td>
                <td class="fw-bold text-ayla-dark">{{ a.paciente }}</td>
                <td>{{ a.servicio }}</td>
                <td class="text-end fw-bold text-success">${{ a.monto.toFixed(2) }}</td>
              </tr>
              <tr v-if="atenciones.length === 0">
                <td colspan="4" class="text-center py-4 text-muted">
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
import { ref } from 'vue';

const props = defineProps({
  especialista: Object,
  comision_total: Number,
  atenciones: Array,
  filters: Object
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