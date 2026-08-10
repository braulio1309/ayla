<template>
  <div class="bg-ayla-cream d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="card-ayla p-4 text-center shadow-lg border-0" style="max-width: 380px; width: 100%;">
      <div v-if="statusMessage" :class="['alert py-2 mb-3 small', statusType === 'success' ? 'alert-success' : 'alert-danger']">
        {{ statusMessage }}
      </div>
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
        <button type="submit" class="btn btn-ayla-primary w-100 py-2" :disabled="form.processing">
          {{ form.processing ? 'Ingresando...' : 'Ingresar al Sistema' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const flashMessage = computed(() => page.props.flash?.success || '');
const statusMessage = ref('');
const statusType = ref('success');

const form = useForm({
  email: '',
  password: '',
});

const submit = () => {
  statusMessage.value = '';
  form.clearErrors();

  form.post('/login', {
    onSuccess: () => {
      statusType.value = 'success';
      statusMessage.value = flashMessage.value || 'Inicio de sesión correcto. Bienvenido(a).';
    },
    onError: (errors) => {
      statusType.value = 'danger';
      statusMessage.value = errors.email || errors.password || 'No fue posible iniciar sesión. Verifica tus credenciales.';
    },
  });
};
</script>