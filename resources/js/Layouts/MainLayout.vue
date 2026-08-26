<template>
  <div class="d-flex flex-column min-vh-100">
    <div v-if="toast.visible" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
      <div
        class="toast show align-items-center border-0 shadow-lg"
        :class="toast.type === 'success' ? 'text-white bg-success' : 'text-white bg-info'"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
        :style="toastStyle"
      >
        <div class="d-flex">
          <div class="toast-body d-flex align-items-center">
            <i :class="toast.type === 'success' ? 'bi bi-check-circle-fill me-2' : 'bi bi-bell-fill me-2'"></i>
            {{ toast.message }}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" aria-label="Cerrar" @click="toast.visible = false"></button>
        </div>
      </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-ayla sticky-top py-2">
      <div class="container-fluid px-4">
        <Link class="navbar-brand d-flex align-items-center" href="/dashboard">
            <div class="bg-ayla-cream rounded-circle p-2 me-2 d-flex align-items-center justify-content-center overflow-hidden" style="width: 42px; height: 42px;">
            <img src="/ayla/ayla-logo.png" alt="Ayla" class="w-100 h-100 object-fit-cover">
          </div>
          <div>
            <span class="brand-font fs-3 fw-bold text-ayla-dark lh-1 d-block">ayla</span>
            <span class="small text-muted fw-normal" style="font-size: 0.72rem; letter-spacing: 0.5px;">CENTRO MÉDICO • BELLEZA & SPA</span>
          </div>
        </Link>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
          <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Dashboard' }" href="/"><i class="bi bi-grid-1x2-fill me-1"></i> Dashboard</Link></li>
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Agenda' }" href="/agenda"><i class="bi bi-calendar3 me-1"></i> Agenda & Turnos</Link></li>
            <li v-if="isAdmin" class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Pacientes' }" href="/pacientes"><i class="bi bi-people-fill me-1"></i> Pacientes</Link></li>
            <li v-if="isAdmin" class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Servicios' }" href="/servicios"><i class="bi bi-flower1 me-1"></i> Servicios</Link></li>
            <li v-if="isAdmin" class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Usuarios' }" href="/usuarios"><i class="bi bi-shield-lock-fill me-1"></i> Usuarios & Roles</Link></li>
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'PanelEspecialista' }" href="/panel-especialista"><i class="bi bi-person-badge-fill me-1"></i> Mi Panel</Link></li>
            <li v-if="isAdmin" class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Reportes' }" href="/reportes"><i class="bi bi-graph-up-arrow me-1"></i> Finanzas</Link></li>
          </ul>
          <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-xl-block">
              <div class="fw-bold text-ayla-dark small">{{ authUser?.name || 'Usuario' }}</div>
              <span class="badge bg-ayla-dark fw-light" style="font-size: 0.7rem;">{{ userRoleLabel }}</span>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" @click="cerrarSesion">
              <i class="bi bi-box-arrow-right me-1"></i> Salir
            </button>
          </div>
        </div>
      </div>
    </nav>

    <main class="py-4 flex-grow-1">
      <slot />
    </main>

    <footer class="bg-white border-top py-3 text-center text-muted small">
      <div class="container">&copy; 2026 <strong>Ayla Centro Médico - Belleza & Spa</strong>. Todos los derechos reservados.</div>
    </footer>
  </div>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);
const flashMessage = computed(() => page.props.flash?.success || '');
const notificationMessage = computed(() => {
  const flashNotification = page.props.flash?.notification || '';
  if (flashNotification) {
    return flashNotification;
  }

  const notifications = page.props.auth?.notifications || [];
  if (!notifications.length) {
    return '';
  }

  const latest = notifications[0];
  return latest.message || '';
});

const toast = ref({
  visible: false,
  type: 'success',
  message: ''
});

const toastStyle = computed(() => ({
  animation: 'toast-in-right 0.45s ease, toast-out-right 0.45s ease 3.1s forwards'
}));

let toastTimer = null;

const showToast = (type, message) => {
  if (!message) {
    return;
  }

  toast.value = {
    visible: true,
    type,
    message,
  };

  if (toastTimer) {
    clearTimeout(toastTimer);
  }

  toastTimer = setTimeout(() => {
    toast.value.visible = false;
  }, 3500);
};

watch(
  () => [flashMessage.value, notificationMessage.value],
  ([successMessage, notificationText]) => {
    if (successMessage) {
      showToast('success', successMessage);
      return;
    }

    if (notificationText) {
      showToast('info', notificationText);
    }
  },
  { immediate: true }
);

const userRoleLabel = computed(() => {
  const role = authUser.value?.role;
  if (role === 'admin') return 'Administrador';
  if (role === 'especialista') return 'Especialista';
  return 'Usuario';
});
const isAdmin = computed(() => authUser.value?.role === 'admin');

const cerrarSesion = () => {
  const logoutUrl = window.location.pathname.replace(/\/[^/]*\/?$/, '/logout');

  router.post(logoutUrl);
};
</script>