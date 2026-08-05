<template>
  <div class="bg-ayla-cream d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="card-ayla p-4 text-center shadow-lg border-0" style="max-width: 380px; width: 100%;">
      <h1 class="brand-font fw-bold text-ayla-dark mb-0">ayla</h1>
      <p class="text-muted small mb-4">CENTRO MÉDICO • BELLEZA & SPA</p>
      <form @submit.prevent="submit">
        <div class="mb-3 text-start">
          <label class="form-label small fw-medium">Correo Electrónico</label>
          <input type="email" class="form-control" v-model="form.email" required>
        </div>
        <div class="mb-3 text-start">
          <label class="form-label small fw-medium">Contraseña</label>
          <input type="password" class="form-control" v-model="form.password" required>
        </div>
        <button type="submit" class="btn btn-ayla-primary w-100 py-2">Ingresar al Sistema</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: 'admin@ayla.com',
  password: 'password',
});

const submit = () => {
  form.post('/login');
};
</script>