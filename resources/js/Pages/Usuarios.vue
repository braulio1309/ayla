<template>
  <MainLayout>
    <div class="container-fluid px-4">
      <h2 class="brand-font fw-bold text-ayla-dark mb-4">Gestión de Usuarios & Seguridad</h2>
      <div class="card-ayla p-4">
        <table class="table">
          <thead>
            <tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th></tr>
          </thead>
          <tbody>
            <tr v-for="u in usuarios" :key="u.id">
              <td><strong>{{ u.name }}</strong></td>
              <td>{{ u.email }}</td>
              <td><span class="badge bg-ayla-dark">{{ u.role }}</span></td>
              <td><span class="badge bg-success">{{ u.status }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
defineProps({ usuarios: Array });
</script>