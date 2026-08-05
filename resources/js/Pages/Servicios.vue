<template>
  <MainLayout>
    <div class="container-fluid px-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="brand-font fw-bold text-ayla-dark mb-0">Catálogo de Servicios</h2>
        <button class="btn btn-ayla-primary">+ Nuevo Servicio</button>
      </div>
      <div class="row g-4">
        <div v-for="s in servicios" :key="s.id" class="col-md-4">
          <div class="card-ayla p-4 h-100">
            <span class="badge bg-ayla-cream text-dark mb-2">{{ s.categoria }}</span>
            <h5 class="fw-bold">{{ s.nombre }}</h5>
            <p class="small text-muted">{{ s.descripcion }}</p>
            <div class="d-flex justify-content-between align-items-center mt-auto border-top pt-2">
              <span class="small"><i class="bi bi-clock me-1"></i> {{ s.duracion_min }} min</span>
              <h4 class="fw-bold mb-0">${{ s.precio.toFixed(2) }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
defineProps({ servicios: Array });
</script>