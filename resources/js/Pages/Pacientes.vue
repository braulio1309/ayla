<template>
  <MainLayout>
    <div class="container-fluid px-4">
      
      <!-- Cabecera del Módulo -->
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h2 class="brand-font fw-bold text-ayla-dark mb-0">Gestión de Pacientes / Clientes</h2>
          <p class="text-muted small mb-0">Directorio centralizado y expedientes de historial clínico de atenciones</p>
        </div>
        <button class="btn btn-ayla-primary px-4" data-bs-toggle="modal" data-bs-target="#modalNuevoPaciente">
          <i class="bi bi-person-plus me-1"></i> Registrar Nuevo Paciente
        </button>
      </div>

      <!-- Tarjeta de Contenido y Buscador -->
      <div class="card-ayla p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div class="input-group" style="max-width: 380px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input 
              type="text" 
              class="form-control border-start-0" 
              placeholder="Buscar paciente por nombre o cédula..."
              v-model="searchQuery"
              @input="ejecutarBusqueda"
            >
          </div>
          <span class="text-muted small">Mostrando {{ pacientes.length }} pacientes registrados</span>
        </div>

        <!-- Tabla de Pacientes -->
        <div class="table-responsive">
          <table class="table table-ayla align-middle">
            <thead>
              <tr>
                <th>Nombre del Paciente</th>
                <th>Cédula / Identificación</th>
                <th>Teléfono</th>
                <th>Correo Electrónico</th>
                <th>Citas Asistidas</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in pacientes" :key="p.id">
                <td class="fw-bold text-ayla-dark">{{ p.nombre }}</td>
                <td>{{ p.cedula }}</td>
                <td>{{ p.telefono }}</td>
                <td>{{ p.email || 'N/A' }}</td>
                <td>
                  <span class="badge bg-ayla-rose">{{ p.citas_count }} Atenciones</span>
                </td>
                <td class="text-end">
                  <button 
                    class="btn btn-sm btn-ayla-outline" 
                    @click="verExpediente(p)" 
                    data-bs-toggle="modal" 
                    data-bs-target="#modalExpediente"
                  >
                    <i class="bi bi-file-earmark-medical me-1"></i> Expediente Histórico
                  </button>
                </td>
              </tr>
              <tr v-if="pacientes.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">
                  No se encontraron pacientes que coincidan con la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- MODAL 1: REGISTRAR NUEVO PACIENTE -->
    <div class="modal fade" id="modalNuevoPaciente" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-ayla border-0">
          <div class="modal-header bg-ayla-cream">
            <h5 class="modal-title brand-font fw-bold"><i class="bi bi-person-plus me-2"></i>Registrar Nuevo Paciente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="guardarPaciente">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label fw-medium">Nombre Completo</label>
                  <input type="text" class="form-control" v-model="formPaciente.nombre" placeholder="Ej. María Alejandra Rivas" required>
                  <span v-if="formPaciente.errors.nombre" class="text-danger small">{{ formPaciente.errors.nombre }}</span>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Identificación (CI/DNI)</label>
                  <input type="text" class="form-control" v-model="formPaciente.cedula" placeholder="V-12345678" required>
                  <span v-if="formPaciente.errors.cedula" class="text-danger small">{{ formPaciente.errors.cedula }}</span>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Teléfono de Contacto</label>
                  <input type="text" class="form-control" v-model="formPaciente.telefono" placeholder="+58 412..." required>
                </div>
                <div class="col-12">
                  <label class="form-label fw-medium">Correo Electrónico</label>
                  <input type="email" class="form-control" v-model="formPaciente.email" placeholder="paciente@email.com">
                </div>
                <div class="col-12">
                  <label class="form-label fw-medium">Notas / Observaciones Médicas</label>
                  <textarea class="form-control" rows="2" v-model="formPaciente.notas" placeholder="Alergias, tipo de piel, condiciones especiales..."></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer border-top">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-ayla-primary px-4" :disabled="formPaciente.processing">Guardar Paciente</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL 2: EXPEDIENTE HISTÓRICO DE ATENCIONES -->
    <div class="modal fade" id="modalExpediente" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content card-ayla border-0" v-if="pacienteSeleccionado">
          <div class="modal-header bg-ayla-cream">
            <h5 class="modal-title brand-font fw-bold"><i class="bi bi-folder2-open me-2"></i>Expediente Histórico de Atenciones</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <!-- Ficha resumida del paciente -->
            <div class="d-flex align-items-center gap-3 p-3 bg-ayla-cream rounded mb-4">
              <div class="bg-ayla-dark text-white rounded-circle p-3 fs-4 fw-bold">
                {{ obtenerIniciales(pacienteSeleccionado.nombre) }}
              </div>
              <div>
                <h5 class="mb-0 fw-bold text-ayla-dark">{{ pacienteSeleccionado.nombre }}</h5>
                <span class="small text-muted">
                  CI: {{ pacienteSeleccionado.cedula }} • Tel: {{ pacienteSeleccionado.telefono }} • Email: {{ pacienteSeleccionado.email || 'Sin correo' }}
                </span>
                <p class="small text-secondary mb-0 mt-1" v-if="pacienteSeleccionado.notas">
                  <em>Notas: {{ pacienteSeleccionado.notas }}</em>
                </p>
              </div>
            </div>

            <h6 class="fw-bold text-ayla-dark mb-3"><i class="bi bi-clock-history me-1"></i> Historial Cronológico de Citas</h6>
            
            <!-- Listado de Citas -->
            <div class="list-group" v-if="pacienteSeleccionado.historial && pacienteSeleccionado.historial.length > 0">
              <div 
                v-for="cita in pacienteSeleccionado.historial" 
                :key="cita.id" 
                class="list-group-item p-3 border-start border-4 border-success rounded mb-2 shadow-sm"
              >
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <strong class="text-ayla-dark fs-6">{{ cita.servicios }}</strong>
                  <span class="badge bg-ayla-dark">${{ cita.monto.toFixed(2) }}</span>
                </div>
                <div class="small text-muted mb-1">
                  <i class="bi bi-calendar-event me-1"></i> {{ cita.fecha }} • Atendido por: <strong>{{ cita.especialista }}</strong>
                </div>
                <div class="small text-secondary" v-if="cita.observaciones">
                  <em>Nota clínica: {{ cita.observaciones }}</em>
                </div>
              </div>
            </div>

            <div class="text-center py-4 text-muted border rounded" v-else>
              <i class="bi bi-journal-x fs-2 d-block mb-1"></i>
              Este paciente aún no registra citas ni atenciones anteriores en el centro.
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-ayla-primary" data-bs-dismiss="modal">Cerrar Expediente</button>
          </div>
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
  pacientes: Array
});

// Buscador Reactivo
const searchQuery = ref(props.filters.search || '');

const ejecutarBusqueda = () => {
  router.get('/pacientes', { search: searchQuery.value }, {
    preserveState: true,
    replace: true
  });
};

// Manejo del paciente seleccionado para Expediente
const pacienteSeleccionado = ref(null);
const verExpediente = (paciente) => {
  pacienteSeleccionado.value = paciente;
};

const obtenerIniciales = (nombre) => {
  if (!nombre) return 'PA';
  return nombre.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

// Formulario de Nuevo Paciente
const formPaciente = useForm({
  nombre: '',
  cedula: '',
  telefono: '',
  email: '',
  notas: ''
});

const guardarPaciente = () => {
  formPaciente.post('/pacientes', {
    onSuccess: () => {
      const modalEl = document.getElementById('modalNuevoPaciente');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
      formPaciente.reset();
    }
  });
};
</script>