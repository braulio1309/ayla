<template>
  <MainLayout>
    <div class="container-fluid px-4">
      
      <!-- Cabecera del Módulo -->
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h2 class="brand-font fw-bold text-ayla-dark mb-0">Gestión de Usuarios & Roles</h2>
          <p class="text-muted small mb-0">Administración de cuentas del personal, accesos al sistema y permisos de perfil</p>
        </div>
        <button class="btn btn-ayla-primary px-4" @click="abrirModalCrear" data-bs-toggle="modal" data-bs-target="#modalUsuario">
          <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
        </button>
      </div>

      <!-- Barra de Buscador y Filtro por Rol -->
      <div class="card-ayla p-3 mb-4">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
              <input 
                type="text" 
                class="form-control border-start-0" 
                placeholder="Buscar usuario por nombre o correo electrónico..."
                v-model="searchQuery"
                @input="ejecutarFiltro"
              >
            </div>
          </div>
          <div class="col-md-4">
            <select class="form-select" v-model="rolSeleccionado" @change="ejecutarFiltro">
              <option value="">Todos los Roles</option>
              <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.nombre }}</option>
            </select>
          </div>
          <div class="col-md-2 text-end">
            <button class="btn btn-outline-secondary w-100" @click="limpiarFiltros" title="Limpiar Filtros">
              <i class="bi bi-x-circle me-1"></i> Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla de Usuarios -->
      <div class="card-ayla p-4">
        <div class="table-responsive">
          <table class="table table-ayla align-middle mb-0">
            <thead>
              <tr>
                <th>Usuario / Personal</th>
                <th>Correo Electrónico</th>
                <th>Rol Asignado</th>
                <th>Estado</th>
                <th class="text-end">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in usuarios" :key="u.id">
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <div class="bg-ayla-cream text-ayla-dark rounded-circle fw-bold p-2 text-center" style="width: 40px; height: 40px; line-height: 24px;">
                      {{ obtenerIniciales(u.name) }}
                    </div>
                    <div>
                      <strong class="text-ayla-dark d-block">{{ u.name }}</strong>
                      <span class="small text-muted">ID: #USR-{{ u.id }}</span>
                    </div>
                  </div>
                </td>
                <td>{{ u.email }}</td>
                <td>
                  <span class="badge" :class="u.role === 'admin' ? 'bg-ayla-dark' : 'bg-ayla-rose'">
                    {{ u.role === 'admin' ? 'Administrador' : 'Especialista' }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="u.is_active ? 'bg-success' : 'bg-secondary'">
                    {{ u.is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="text-end">
                  <button 
                    class="btn btn-sm btn-ayla-outline" 
                    @click="abrirModalEditar(u)"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalUsuario"
                  >
                    <i class="bi bi-pencil me-1"></i> Editar
                  </button>
                </td>
              </tr>
              <tr v-if="usuarios.length === 0">
                <td colspan="5" class="text-center py-4 text-muted">
                  No se encontraron usuarios que coincidan con la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- MODAL DUAL: REGISTRAR / EDITAR USUARIO -->
    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-ayla border-0">
          <div class="modal-header bg-ayla-cream">
            <h5 class="modal-title brand-font fw-bold">
              <i class="bi bi-person-gear me-2"></i>{{ modoEdicion ? 'Editar Usuario' : 'Registrar Nuevo Usuario' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="guardarUsuario">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label fw-medium">Nombre Completo</label>
                  <input type="text" class="form-control" v-model="formUsuario.name" placeholder="Ej. Dr. Carlos Mendoza" required>
                  <span v-if="formUsuario.errors.name" class="text-danger small">{{ formUsuario.errors.name }}</span>
                </div>

                <div class="col-12">
                  <label class="form-label fw-medium">Correo Electrónico</label>
                  <input type="email" class="form-control" v-model="formUsuario.email" placeholder="usuario@aylaspa.com" required>
                  <span v-if="formUsuario.errors.email" class="text-danger small">{{ formUsuario.errors.email }}</span>
                </div>

                <div class="col-12">
                  <label class="form-label fw-medium">
                    Contraseña 
                    <small class="text-muted" v-if="modoEdicion">(Dejar en blanco para mantener la actual)</small>
                  </label>
                  <input type="password" class="form-control" v-model="formUsuario.password" :required="!modoEdicion" placeholder="••••••••">
                  <span v-if="formUsuario.errors.password" class="text-danger small">{{ formUsuario.errors.password }}</span>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-medium">Rol de Usuario</label>
                  <select class="form-select" v-model="formUsuario.role" required>
                    <option value="especialista">Especialista / Profesional</option>
                    <option value="admin">Administrador</option>
                  </select>
                  <span v-if="formUsuario.errors.role" class="text-danger small">{{ formUsuario.errors.role }}</span>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-medium">Estado de Cuenta</label>
                  <select class="form-select" v-model="formUsuario.is_active" required>
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                  </select>
                </div>

                <div class="col-12" v-if="formUsuario.role === 'especialista'">
                  <label class="form-label fw-medium">Comisión del especialista (%)</label>
                  <input type="number" min="0" max="100" step="0.1" class="form-control" v-model.number="formUsuario.comision" placeholder="Ej. 35">
                  <small class="text-muted">Entre 0 y 100. El resto queda para el negocio.</small>
                  <span v-if="formUsuario.errors.comision" class="text-danger small">{{ formUsuario.errors.comision }}</span>
                </div>
              </div>
            </div>
            <div class="modal-footer border-top">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-ayla-primary px-4" :disabled="formUsuario.processing">
                {{ modoEdicion ? 'Actualizar Usuario' : 'Guardar Usuario' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  filters: Object,
  usuarios: Array,
  roles: Array
});

// Filtros Reactivos
const searchQuery = ref(props.filters.search || '');
const rolSeleccionado = ref(props.filters.role || '');

const ejecutarFiltro = () => {
  router.get('/usuarios', {
    search: searchQuery.value,
    role: rolSeleccionado.value
  }, {
    preserveState: true,
    replace: true
  });
};

const limpiarFiltros = () => {
  searchQuery.value = '';
  rolSeleccionado.value = '';
  router.get('/usuarios');
};

const obtenerIniciales = (nombre) => {
  if (!nombre) return 'US';
  return nombre.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

// Formulario Dual (Creación / Edición)
const modoEdicion = ref(false);
const usuarioIdActual = ref(null);

const formUsuario = useForm({
  name: '',
  email: '',
  password: '',
  role: 'especialista',
  is_active: true,
  comision: 0
});

const abrirModalCrear = () => {
  modoEdicion.value = false;
  usuarioIdActual.value = null;
  formUsuario.reset();
  formUsuario.clearErrors();
};

const abrirModalEditar = (usuario) => {
  modoEdicion.value = true;
  usuarioIdActual.value = usuario.id;
  formUsuario.clearErrors();
  formUsuario.name = usuario.name;
  formUsuario.email = usuario.email;
  formUsuario.password = ''; // Opcional en edición
  formUsuario.role = usuario.role;
  formUsuario.is_active = usuario.is_active;
  formUsuario.comision = usuario.comision ?? 0;
};

const guardarUsuario = () => {
  if (modoEdicion.value) {
    formUsuario.put(`/usuarios/${usuarioIdActual.value}`, {
      onSuccess: () => cerrarModal()
    });
  } else {
    formUsuario.post('/usuarios', {
      onSuccess: () => cerrarModal()
    });
  }
};

const cerrarModal = () => {
  const modalEl = document.getElementById('modalUsuario');
  const modal = bootstrap.Modal.getInstance(modalEl);
  if (modal) modal.hide();
  formUsuario.reset();
};
</script>