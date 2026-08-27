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
            <span class="small text-muted d-block mb-2">Mi total generado en servicios</span>
            <h3 class="brand-font fw-bold text-ayla-dark mb-0">${{ totalGeneradoSeguro.toFixed(2) }}</h3>
            <span class="text-ayla-rose small">Bs. {{ formatoBs(totalGeneradoBsSeguro) }}</span>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-ayla p-4 h-100">
            <span class="small text-muted d-block mb-2">Mi comisión total ganada</span>
            <h3 class="brand-font fw-bold text-success mb-0">${{ (comisionTotalSeguro + comisionAsistenteSeguro).toFixed(2) }}</h3>
            <span class="text-ayla-rose small">Bs. {{ formatoBs(comisionTotalBsSeguro + comisionAsistenteBsSeguro) }}</span>
            <div class="small text-muted mt-2 border-top pt-2 d-flex justify-content-between">
              <span>Por servicios: <strong>${{ comisionTotalSeguro.toFixed(2) }}</strong> (Bs. {{ formatoBs(comisionTotalBsSeguro) }})</span>
              <span v-if="comisionAsistenteSeguro > 0" class="text-ayla-dark">Por asistencias: <strong>${{ comisionAsistenteSeguro.toFixed(2) }}</strong> (Bs. {{ formatoBs(comisionAsistenteBsSeguro) }})</span>
            </div>
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
          <div class="col-md-2 align-self-end">
            <button type="button" class="btn btn-outline-secondary w-100 py-2" @click="verHoy">
              <i class="bi bi-calendar-day me-1"></i> Hoy
            </button>
          </div>
          <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-ayla-secondary w-100 py-2">
              <i class="bi bi-filter me-1"></i> Filtrar
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
                <th>Desglose de Servicios / Asistencia</th>
                <th>Rol</th>
                <th class="text-end">Monto Total Cita</th>
                <th class="text-end">Mi Ganancia Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(a, index) in atencionesSeguras" :key="index">
                <td>{{ a.fecha }}</td>
                <td class="fw-bold text-ayla-dark">{{ a.paciente }}</td>
                <td>
                  <div v-if="a.servicios_detalle && a.servicios_detalle.length" class="small">
                    <div v-for="s in a.servicios_detalle" :key="'s-'+s.id" class="border-bottom py-1">
                      <div class="d-flex justify-content-between gap-2">
                        <strong>{{ s.nombre }}:</strong>
                        <span class="text-muted">Total: ${{ Number(s.monto_total || 0).toFixed(2) }} (Bs. {{ formatoBs(s.monto_total_bs) }})</span>
                      </div>
                      <div v-if="s.es_mi_servicio" class="text-success fw-medium">
                        Mi ganancia ({{ Number(s.comision_porcentaje || 0).toFixed(2) }}%): ${{ Number(s.mi_ganancia || 0).toFixed(2) }} (Bs. {{ formatoBs(s.mi_ganancia_bs) }})
                      </div>
                      <div v-else class="text-muted fw-medium">
                        Otros servicios: ${{ Number(s.monto_total || 0).toFixed(2) }} (Bs. {{ formatoBs(s.monto_total_bs) }})
                      </div>
                    </div>
                  </div>
                  <div v-else class="small text-muted">{{ a.servicio }}</div>

                  <div v-if="a.es_asistente" class="mt-1 text-ayla-dark fw-medium small">
                    <i class="bi bi-person-badge me-1"></i>Ganancia por asistencia ({{ Number(a.comision_asistente_porcentaje || 0).toFixed(2) }}%):
                    <span class="text-success fw-bold">+${{ Number(a.ganancia_asistente || 0).toFixed(2) }} (Bs. {{ formatoBs(a.ganancia_asistente_bs) }})</span>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column gap-1 align-items-start">
                    <span v-if="a.es_principal || a.ganancia_servicios > 0" class="badge bg-ayla-dark">Especialista</span>
                    <span v-if="a.es_asistente" class="badge bg-ayla-rose">Asistente ({{ Number(a.comision_asistente_porcentaje || 0).toFixed(2) }}%)</span>
                  </div>
                </td>
                <td class="text-end fw-medium text-dark">
                  ${{ Number(a.monto ?? 0).toFixed(2) }}
                  <br><span class="small text-ayla-rose">Bs. {{ formatoBs(a.monto_bs) }}</span>
                </td>
                <td class="text-end fw-bold text-success fs-6">
                  ${{ Number(a.mi_ganancia_total ?? (Number(a.ganancia_servicios || 0) + Number(a.ganancia_asistente || 0))).toFixed(2) }}
                  <br><span class="small text-ayla-rose fw-normal">Bs. {{ formatoBs(a.mi_ganancia_total_bs) }}</span>
                </td>
              </tr>
              <tr v-if="atencionesSeguras.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">
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
const comisionAsistenteSeguro = computed(() => Number(props.comision_asistente ?? 0));
const comisionAsistenteBsSeguro = computed(() => Number(props.comision_asistente_bs ?? 0));
const atencionesSeguras = computed(() => props.atenciones || []);

const formatoBs = (monto) => Number(monto ?? 0).toLocaleString('es-VE', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

const filterForm = ref({
  fecha: props.filters?.fecha || new Date().toISOString().substr(0, 10),
  fecha_inicio: props.filters?.fecha_inicio || new Date().toISOString().substr(0, 10),
  fecha_fin: props.filters?.fecha_fin || new Date().toISOString().substr(0, 10)
});

const verHoy = () => {
  const hoy = new Date().toISOString().substr(0, 10);
  filterForm.value.fecha = hoy;
  filterForm.value.fecha_inicio = hoy;
  filterForm.value.fecha_fin = hoy;
  consultarProduccion();
};

const consultarProduccion = () => {
  const payload = {
    ...filterForm.value,
    fecha_inicio: filterForm.value.fecha_inicio || filterForm.value.fecha,
    fecha_fin: filterForm.value.fecha_fin || filterForm.value.fecha,
    fecha: filterForm.value.fecha || filterForm.value.fecha_inicio,
  };

  router.get('/panel-especialista', payload, {
    preserveState: true,
    replace: true
  });
};
</script>