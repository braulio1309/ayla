<template>
  <MainLayout>
    <div class="container-fluid px-4">
      
      <!-- Cabecera del Módulo -->
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h2 class="brand-font fw-bold text-ayla-dark mb-0">Catálogo de Servicios & Costos</h2>
          <p class="text-muted small mb-0">Configuración de precios base, tiempos de duración e información de tratamientos</p>
        </div>
        <button v-if="isAdmin" class="btn btn-ayla-primary px-4" @click="abrirModalCrear" data-bs-toggle="modal" data-bs-target="#modalServicio">
          <i class="bi bi-plus-circle me-1"></i> Nuevo Servicio
        </button>
      </div>

      <!-- Barra de Filtros y Búsqueda -->
      <div class="card-ayla p-3 mb-4">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
              <input 
                type="text" 
                class="form-control border-start-0" 
                placeholder="Buscar servicio por nombre o descripción..."
                v-model="searchQuery"
                @input="ejecutarFiltro"
              >
            </div>
          </div>
          <div class="col-md-4">
            <select class="form-select" v-model="categoriaSeleccionada" @change="ejecutarFiltro">
              <option value="">Todas las Categorías</option>
              <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>
          <div class="col-md-2 text-end">
            <button class="btn btn-outline-secondary w-100" @click="limpiarFiltros" title="Limpiar Filtros">
              <i class="bi bi-x-circle me-1"></i> Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- Rejilla de Tarjetas de Servicios -->
      <div class="row g-4" v-if="servicios.length > 0">
        <div class="col-md-6 col-lg-4" v-for="s in servicios" :key="s.id">
          <div class="card-ayla p-4 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge" :class="obtenerBadgeCategoria(s.categoria)">{{ s.categoria }}</span>
              <h3 class="fw-bold text-ayla-dark mb-0">${{ s.precio_base.toFixed(2) }}</h3>
            </div>
            
            <h5 class="fw-bold text-ayla-dark mt-1">{{ s.nombre }}</h5>
            <p class="text-muted small flex-grow-1">{{ s.descripcion || 'Sin descripción detallada.' }}</p>

            <div v-if="s.especialistas && s.especialistas.length" class="small text-muted mb-2">
              <strong>Especialistas:</strong>
              <div class="mt-1">
                <span v-for="(es, index) in s.especialistas" :key="es.id" class="badge bg-light text-dark border me-1 mb-1">
                  {{ es.name }} (${{ es.precio_especialista.toFixed(2) }})
                </span>
              </div>
            </div>
            
            <div class="border-top pt-3 d-flex justify-content-between align-items-center mt-auto">
              <span class="small text-muted fw-medium">
                <i class="bi bi-clock me-1 text-ayla-rose"></i> {{ s.duracion_min }} Minutos
              </span>
              <button v-if="isAdmin"
                class="btn btn-sm btn-ayla-outline" 
                @click="abrirModalEditar(s)"
                data-bs-toggle="modal" 
                data-bs-target="#modalServicio"
              >
                <i class="bi bi-pencil me-1"></i> Editar
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="card-ayla p-5 text-center text-muted" v-else>
        <i class="bi bi-flower1 fs-1 text-ayla-taupe d-block mb-2"></i>
        <h5>No se encontraron servicios</h5>
        <p class="small mb-0">No existen servicios registrados con los criterios de búsqueda seleccionados.</p>
      </div>

    </div>

    <!-- MODAL DUAL: REGISTRAR / EDITAR SERVICIO -->
    <div class="modal fade" id="modalServicio" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-ayla border-0">
          <div class="modal-header bg-ayla-cream">
            <h5 class="modal-title brand-font fw-bold">
              <i class="bi bi-flower1 me-2"></i>{{ modoEdicion ? 'Editar Servicio' : 'Registrar Nuevo Servicio' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="guardarServicio">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label fw-medium">Nombre del Servicio</label>
                  <input type="text" class="form-control" v-model="formServicio.nombre" placeholder="Ej. Masaje Descontracturante" required>
                  <span v-if="formServicio.errors.nombre" class="text-danger small">{{ formServicio.errors.nombre }}</span>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-medium">Categoría</label>
                  <select class="form-select" v-model="formServicio.categoria" required>
                    <option value="">Seleccionar...</option>
                    <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
                  </select>
                  <span v-if="formServicio.errors.categoria" class="text-danger small">{{ formServicio.errors.categoria }}</span>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-medium">Precio Base ($ USD)</label>
                  <input type="number" step="0.01" class="form-control" v-model="formServicio.precio_base" placeholder="0.00" required>
                  <span v-if="formServicio.errors.precio_base" class="text-danger small">{{ formServicio.errors.precio_base }}</span>
                </div>

                <div class="col-12">
                  <label class="form-label fw-medium">Duración Estimada (en Minutos)</label>
                  <input type="number" class="form-control" v-model="formServicio.duracion_min" placeholder="60" required>
                  <span v-if="formServicio.errors.duracion_min" class="text-danger small">{{ formServicio.errors.duracion_min }}</span>
                </div>

                <div class="col-12">
                  <label class="form-label fw-medium">Especialistas que lo realizan</label>
                  <div class="border rounded p-3 bg-light">
                    <div v-for="especialista in especialistas_lista" :key="especialista.id" class="mb-3">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <label class="form-check-label d-flex align-items-center gap-2 mb-0">
                          <input class="form-check-input" type="checkbox" :value="especialista.id" v-model="formServicio.especialistas">
                          <span>{{ especialista.name }}</span>
                        </label>
                        <span v-if="formServicio.especialistas.includes(especialista.id)" class="small text-muted">Precio</span>
                      </div>
                      <input
                        v-if="formServicio.especialistas.includes(especialista.id)"
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-control mt-2"
                        :value="formServicio.precios_especialistas[especialista.id] ?? formServicio.precio_base"
                        @input="formServicio.precios_especialistas[especialista.id] = Number($event.target.value) || 0"
                        :placeholder="`Precio para ${especialista.name}`"
                      >
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label fw-medium">Descripción</label>
                  <textarea class="form-control" rows="3" v-model="formServicio.descripcion" placeholder="Detalles sobre lo que incluye este servicio o técnica aplicada..."></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer border-top">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-ayla-primary px-4" :disabled="formServicio.processing">
                {{ modoEdicion ? 'Actualizar Servicio' : 'Guardar Servicio' }}
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
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
  filters: Object,
  servicios: Array,
  categorias: Array,
  especialistas: Array
});

const especialistas_lista = computed(() => props.especialistas || []);
const isAdmin = computed(() => usePage().props.auth?.user?.role === 'admin');

// Filtros Reactivos
const searchQuery = ref(props.filters.search || '');
const categoriaSeleccionada = ref(props.filters.categoria || '');

const ejecutarFiltro = () => {
  router.get('/servicios', {
    search: searchQuery.value,
    categoria: categoriaSeleccionada.value
  }, {
    preserveState: true,
    replace: true
  });
};

const limpiarFiltros = () => {
  searchQuery.value = '';
  categoriaSeleccionada.value = '';
  router.get('/servicios');
};

// Formulario Dual (Creación / Edición)
const modoEdicion = ref(false);
const servicioIdActual = ref(null);

const formServicio = useForm({
  nombre: '',
  categoria: '',
  precio_base: '',
  duracion_min: '',
  descripcion: '',
  especialistas: [],
  precios_especialistas: {}
});

const abrirModalCrear = () => {
  modoEdicion.value = false;
  servicioIdActual.value = null;
  formServicio.reset();
  formServicio.especialistas = [];
  formServicio.precios_especialistas = {};
  formServicio.clearErrors();
};

const abrirModalEditar = (servicio) => {
  modoEdicion.value = true;
  servicioIdActual.value = servicio.id;
  formServicio.clearErrors();
  formServicio.nombre = servicio.nombre;
  formServicio.categoria = servicio.categoria;
  formServicio.precio_base = servicio.precio_base;
  formServicio.duracion_min = servicio.duracion_min;
  formServicio.descripcion = servicio.descripcion;
  formServicio.especialistas = (servicio.especialistas || []).map((especialista) => especialista.id);
  formServicio.precios_especialistas = {};
  (servicio.especialistas || []).forEach((especialista) => {
    formServicio.precios_especialistas[especialista.id] = especialista.precio_especialista;
  });
};

const guardarServicio = () => {
  const payload = {
    ...formServicio,
    especialistas: formServicio.especialistas,
    precios_especialistas: formServicio.precios_especialistas,
  };

  if (modoEdicion.value) {
    formServicio.put(`/servicios/${servicioIdActual.value}`, {
      data: payload,
      onSuccess: () => cerrarModal()
    });
  } else {
    formServicio.post('/servicios', {
      data: payload,
      onSuccess: () => cerrarModal()
    });
  }
};

const cerrarModal = () => {
  const modalEl = document.getElementById('modalServicio');
  const modal = bootstrap.Modal.getInstance(modalEl);
  if (modal) modal.hide();
  formServicio.reset();
  formServicio.especialistas = [];
  formServicio.precios_especialistas = {};
};

const obtenerBadgeCategoria = (categoria) => {
  switch (categoria) {
    case 'Cosmiatría': return 'badge-ayla-cream';
    case 'Masajes & Spa': return 'badge-ayla-rose';
    case 'Manos / Pies': return 'badge-ayla-taupe';
    default: return 'bg-ayla-dark';
  }
};
</script>