<template>
  <MainLayout>
    <div class="container-fluid px-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="brand-font fw-bold text-ayla-dark mb-0">Gestión de Pacientes</h2>
        <button class="btn btn-ayla-primary">+ Registrar Paciente</button>
      </div>

      <div class="card-ayla p-4">
        <table class="table align-middle">
          <thead>
            <tr><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Citas</th><th>Acción</th></tr>
          </thead>
          <tbody>
            <tr v-for="p in pacientes" :key="p.id">
              <td><strong>{{ p.nombre }}</strong></td>
              <td>{{ p.cedula }}</td>
              <td>{{ p.telefono }}</td>
              <td><span class="badge bg-ayla-rose">{{ p.citas_count }} Citas</span></td>
              <td><button class="btn btn-sm btn-outline-secondary">Ver Expediente</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
defineProps({ pacientes: Array });
</script>