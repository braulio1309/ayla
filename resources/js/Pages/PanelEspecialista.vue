<template>
  <MainLayout>
    <div class="container-fluid px-4">
      <div class="card-ayla p-4 mb-4 bg-white border-start border-4 border-ayla-rose">
        <h2 class="brand-font fw-bold text-ayla-dark mb-0">Mi Producción Personal</h2>
        <p class="text-muted small">Bienvenida(o), <strong>{{ especialista.nombre }}</strong></p>
      </div>
      <div class="card-ayla p-4">
        <h5 class="fw-bold text-ayla-dark mb-3">Historial de Mis Atenciones</h5>
        <table class="table">
          <thead>
            <tr><th>Fecha</th><th>Paciente</th><th>Servicio</th><th>Monto</th></tr>
          </thead>
          <tbody>
            <tr v-for="(a, i) in atenciones" :key="i">
              <td>{{ a.fecha }}</td>
              <td><strong>{{ a.paciente }}</strong></td>
              <td>{{ a.servicio }}</td>
              <td class="text-success fw-bold">${{ a.monto.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
defineProps({ especialista: Object, atenciones: Array, comision_total: Number });
</script>