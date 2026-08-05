<template>
  <MainLayout>
    <div class="container-fluid px-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="brand-font fw-bold text-ayla-dark mb-0">Agenda & Control de Turnos</h2>
          <p class="text-muted small mb-0">Gestión interactiva de la disponibilidad y cabinas</p>
        </div>
        <button class="btn btn-ayla-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoTurno">
          <i class="bi bi-plus-lg me-1"></i> Agendar Nuevo Turno
        </button>
      </div>

      <div class="card-ayla p-4">
        <h5 class="brand-font fw-bold text-ayla-dark mb-3">Distribución por Cabinas</h5>
        <div class="row g-3">
          <div v-for="t in turnos" :key="t.id" class="col-md-6">
            <div class="p-3 border rounded bg-light">
              <div class="d-flex justify-content-between">
                <strong>{{ t.paciente }}</strong>
                <span class="badge bg-ayla-dark">${{ t.monto }}</span>
              </div>
              <p class="small text-muted mb-1">{{ t.servicio }} • {{ t.especialista }}</p>
              <small class="text-success"><i class="bi bi-clock me-1"></i> {{ t.hora_inicio }} ({{ t.duracion_min }} min)</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal con Cálculo Reactivo en Vue 3 -->
    <div class="modal fade" id="modalNuevoTurno" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content card-ayla border-0">
          <div class="modal-header bg-ayla-cream">
            <h5 class="modal-title brand-font fw-bold">Asignación de Turno (Vue 3 Reactivo)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fw-medium">Seleccionar Servicios (Acumulables)</label>
              <div v-for="s in listaServicios" :key="s.id" class="form-check mb-2">
                <input class="form-check-input" type="checkbox" :id="'srv-'+s.id" v-model="s.selected">
                <label class="form-check-label d-flex justify-content-between" :for="'srv-'+s.id">
                  <span>{{ s.nombre }} ({{ s.duracion }} min)</span>
                  <strong>${{ s.precio }}</strong>
                </label>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Tiempo de Holgura</label>
              <select class="form-select" v-model="holgura">
                <option :value="10">10 minutos</option>
                <option :value="15">15 minutos</option>
                <option :value="20">20 minutos</option>
              </select>
            </div>
            <div class="p-3 bg-ayla-cream rounded d-flex justify-content-between">
              <span>Duración Estimada Total: <strong>{{ duracionTotal }} Minutos</strong></span>
              <h4>Total: <strong>${{ precioTotal.toFixed(2) }}</strong></h4>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ayla-primary" data-bs-dismiss="modal">Confirmar Turno</button>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
import { ref, computed } from 'vue';

defineProps({ turnos: Array });

const listaServicios = ref([
  { id: 1, nombre: 'Limpieza Facial Profunda', duracion: 60, precio: 25, selected: true },
  { id: 2, nombre: 'Hidratación Facial', duracion: 30, precio: 15, selected: false },
  { id: 3, nombre: 'Masaje Relajante', duracion: 45, precio: 30, selected: false },
]);

const holgura = ref(15);

const duracionTotal = computed(() => {
  const min = listaServicios.value.filter(s => s.selected).reduce((acc, s) => acc + s.duracion, 0);
  return min > 0 ? min + Number(holgura.value) : 0;
});

const precioTotal = computed(() => {
  return listaServicios.value.filter(s => s.selected).reduce((acc, s) => acc + s.precio, 0);
});
</script>