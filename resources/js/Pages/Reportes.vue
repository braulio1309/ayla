<template>
  <MainLayout>
    <div class="container-fluid px-4">
      <h2 class="brand-font fw-bold text-ayla-dark mb-4">Módulo de Finanzas & Auditoría</h2>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="card-ayla p-4 bg-ayla-dark text-white">
            <span class="small">Ingresos Brutos Auditados</span>
            <h2 class="fw-bold mb-0">${{ ingresos_brutos.toFixed(2) }}</h2>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-ayla p-4 bg-ayla-cream text-ayla-dark">
            <span class="small">Citas Atendidas</span>
            <h2 class="fw-bold mb-0">{{ citas_completadas }} Citas</h2>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
defineProps({ ingresos_brutos: Number, citas_completadas: Number, desglose_especialistas: Array });
</script>