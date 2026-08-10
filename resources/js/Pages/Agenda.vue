<template>
  <MainLayout>
    <div class="container-fluid px-4">
      
      <!-- Cabecera de Módulo -->
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h2 class="brand-font fw-bold text-ayla-dark mb-0">Agenda & Control de Turnos</h2>
          <p class="text-muted small mb-0">Gestión interactiva de la disponibilidad, cabinas y citas de la clínica/spa</p>
        </div>
        <button class="btn btn-ayla-primary px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalNuevoTurno">
          <i class="bi bi-plus-lg me-1"></i> Agendar Nuevo Turno
        </button>
      </div>

      <!-- Barra de Filtros Interactivos -->
      <div class="card-ayla p-3 mb-4">
        <form @submit.prevent="aplicarFiltros" class="row g-3 align-items-center">
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Fecha de Agenda</label>
            <input type="date" class="form-control" v-model="filterForm.fecha">
          </div>
          <div v-if="!isSpecialist" class="col-md-3">
            <label class="form-label small text-muted mb-1">Filtrar por Especialista</label>
            <select class="form-select" v-model="filterForm.especialista_id">
              <option value="">Todos los especialistas</option>
              <option v-for="e in especialistas_lista" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Estado de Cita</label>
            <select class="form-select" v-model="filterForm.estado">
              <option value="">Todos los estados</option>
              <option value="Confirmado">Confirmados</option>
              <option value="En Proceso">En Proceso</option>
              <option value="Completado">Completados</option>
            </select>
          </div>
          <div class="col-md-3 align-self-end d-flex gap-2">
            <button type="submit" class="btn btn-ayla-secondary w-100">
              <i class="bi bi-funnel me-1"></i> Filtrar
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="limpiarFiltros" title="Limpiar Filtros">
              <i class="bi bi-x-circle"></i>
            </button>
          </div>
        </form>
      </div>

      <div class="card-ayla p-3 mb-4">
        <ul class="nav nav-tabs border-0">
          <li class="nav-item">
            <button class="nav-link" :class="{ active: vistaActual === 'lista' }" @click="vistaActual = 'lista'">
              <i class="bi bi-list-ul me-1"></i> Vista de Lista
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" :class="{ active: vistaActual === 'calendario' }" @click="vistaActual = 'calendario'">
              <i class="bi bi-calendar3 me-1"></i> Vista de Calendario
            </button>
          </li>
        </ul>
      </div>

      <!-- Cuadrícula / Grid de Turnos por Cabina -->
      <div v-if="vistaActual === 'lista'" class="card-ayla p-4">
        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0">
            <thead class="bg-ayla-cream text-ayla-dark">
              <tr class="text-center">
                <th style="width: 110px;">Horario</th>
                <th>Cabina 1 - Cosmiatría</th>
                <th>Cabina 2 - Masajes & Spa</th>
                <th>Cabina 3 - Manicura & Estética</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="turnos.length === 0">
                <td colspan="4" class="text-center py-4 text-muted">
                  No hay turnos registrados para esta fecha.
                </td>
              </tr>
              <tr v-for="turno in turnos" :key="turno.id">
                <td class="text-center fw-bold text-muted small bg-light">{{ turno.hora_inicio }} - {{ turno.hora_fin }}</td>
                <td colspan="3" class="p-2">
                  <div class="turno-card" :class="estadoClass(turno.estado)">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                      <strong>{{ turno.paciente }}</strong>
                      <span class="badge bg-ayla-dark">${{ turno.monto.toFixed(2) }}</span>
                    </div>
                    <div class="small text-muted mt-1"><i class="bi bi-clock me-1"></i> {{ turno.hora_inicio }} - {{ turno.hora_fin }} ({{ turno.duracion_min }} min)</div>
                    <div class="small fw-medium text-ayla-dark">{{ turno.servicio }}</div>
                    <div class="small text-muted"><em>Esp: {{ turno.especialista }}</em></div>
                    <div class="small text-muted mt-1">Cabina: {{ turno.cabina }}</div>
                    <div class="mt-2 d-flex justify-content-between align-items-center">
                      <span class="badge" :class="estadoBadgeClass(turno.estado)">{{ turno.estado }}</span>
                      <button class="btn btn-sm btn-light border py-0 px-2" @click="verDetalle(turno)" data-bs-toggle="modal" data-bs-target="#modalDetalleCita">
                        Ver detalle
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="card-ayla p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="brand-font fw-bold text-ayla-dark mb-1">Calendario de Citas</h5>
            <p class="text-muted small mb-0">Visualiza el estado de la agenda por día.</p>
          </div>
          <div class="text-muted small">Selecciona un día para ver los turnos</div>
        </div>

        <div class="row g-4">
          <div class="col-lg-7">
            <div class="border rounded p-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-outline-secondary btn-sm" @click="cambiarMes(-1)"><i class="bi bi-chevron-left"></i></button>
                <h6 class="fw-bold text-ayla-dark mb-0">{{ nombreMes }} {{ anioCalendario }}</h6>
                <button class="btn btn-outline-secondary btn-sm" @click="cambiarMes(1)"><i class="bi bi-chevron-right"></i></button>
              </div>
              <div class="row text-center small text-muted fw-bold mb-2">
                <div class="col">Do</div>
                <div class="col">Lu</div>
                <div class="col">Ma</div>
                <div class="col">Mi</div>
                <div class="col">Ju</div>
                <div class="col">Vi</div>
                <div class="col">Sa</div>
              </div>
              <div class="row g-2">
                <div v-for="(dia, index) in diasCalendario" :key="index" class="col-12 col-sm-6 col-md-4 col-lg-2">
                  <button class="w-100 border rounded py-3 text-start position-relative" :class="{
                    'bg-ayla-cream': dia.activo,
                    'border-ayla-rose': dia.fecha === fechaCalendario,
                    'text-muted': !dia.enMes
                  }" @click="seleccionarDia(dia)">
                    <div class="fw-bold">{{ dia.dia }}</div>
                    <div v-if="dia.citas > 0" class="small text-ayla-dark">{{ dia.citas }} cita(s)</div>
                    <div v-else class="small text-muted">Sin citas</div>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="border rounded p-3 h-100">
              <h6 class="fw-bold text-ayla-dark mb-3">Turnos del día</h6>
              <div v-if="turnosDelDiaSeleccionado.length === 0" class="text-muted small">No hay turnos para esta fecha.</div>
              <div v-else class="d-flex flex-column gap-2">
                <div v-for="turno in turnosDelDiaSeleccionado" :key="turno.id" class="border rounded p-2">
                  <div class="d-flex justify-content-between align-items-center">
                    <strong class="small">{{ turno.paciente }}</strong>
                    <span class="badge" :class="estadoBadgeClass(turno.estado)">{{ turno.estado }}</span>
                  </div>
                  <div class="small text-muted mt-1">{{ turno.hora_inicio }} - {{ turno.hora_fin }}</div>
                  <div class="small text-ayla-dark">{{ turno.servicio }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- MODAL 1: AGENDAR NUEVO TURNO (Con cálculo dinámico de holgura y acumulables) -->
    <div class="modal fade" id="modalNuevoTurno" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content card-ayla border-0">
          <div class="modal-header bg-ayla-cream">
            <h5 class="modal-title brand-font fw-bold"><i class="bi bi-calendar-plus me-2"></i>Asignación y Agendamiento de Turno</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="guardarTurno">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-medium">Paciente / Cliente</label>
                  <select class="form-select" v-model="formTurno.paciente_id" required>
                    <option value="">Seleccionar paciente...</option>
                    <option v-for="p in pacientes_lista" :key="p.id" :value="p.id">{{ p.nombre }} ({{ p.cedula }})</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-medium">Especialista Asignado</label>
                  <select class="form-select" v-model="formTurno.especialista_id" :disabled="isSpecialist" required>
                    <option value="">Seleccionar especialista...</option>
                    <option v-for="e in especialistas_lista" :key="e.id" :value="e.id">{{ e.name }}</option>
                  </select>
                </div>

                <div v-if="disponibilidadMensaje" class="col-12">
                  <div class="alert alert-warning mb-0 py-2 px-3 small" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ disponibilidadMensaje }}
                  </div>
                </div>

                <!-- Selección de Servicios Acumulables -->
                <div class="col-12">
                  <label class="form-label fw-medium d-flex justify-content-between">
                    <span>Servicios a Realizar (Acumulables)</span>
                    <span class="text-muted small">Seleccione uno o varios</span>
                  </label>
                  <div class="card p-3 bg-light border">
                    <div v-for="s in serviciosState" :key="s.id" class="form-check mb-2">
                      <input class="form-check-input" type="checkbox" :id="'srv-agenda-'+s.id" v-model="s.selected">
                      <label class="form-check-label d-flex justify-content-between w-100" :for="'srv-agenda-'+s.id">
                        <span>{{ s.nombre }} ({{ s.duracion }} min)</span>
                        <strong class="text-ayla-dark">${{ s.precio.toFixed(2) }}</strong>
                      </label>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-medium">Fecha de Atención</label>
                  <input type="date" class="form-control" v-model="formTurno.fecha" required>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-medium">Hora de Inicio</label>
                  <input type="time" class="form-control" v-model="formTurno.hora_inicio" required>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-medium">Tiempo de Holgura / Descanso</label>
                  <select class="form-select" v-model="formTurno.holgura_min">
                    <option :value="10">10 minutos</option>
                    <option :value="15">15 minutos</option>
                    <option :value="20">20 minutos</option>
                  </select>
                </div>

                <!-- Resumen Automático -->
                <div class="col-12 mt-3 p-3 bg-ayla-cream rounded d-flex justify-content-between align-items-center">
                  <div>
                    <span class="text-muted small d-block">Duración Total Estimada (con holgura):</span>
                    <strong class="fs-5 text-ayla-dark">{{ duracionTotal }} min</strong>
                  </div>
                  <div class="text-end">
                    <span class="text-muted small d-block">Monto Económico Total:</span>
                    <strong class="fs-4 text-ayla-dark">${{ precioTotal.toFixed(2) }}</strong>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-top">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-ayla-primary px-4" :disabled="formTurno.processing">Confirmar Turno</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL 2: DETALLE Y CAMBIO DE ESTADO DE CITA -->
    <div class="modal fade" id="modalDetalleCita" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-ayla border-0" v-if="citaSeleccionada">
          <div class="modal-header bg-ayla-cream">
            <h5 class="modal-title brand-font fw-bold">Detalles de la Cita #AY-{{ citaSeleccionada.id }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <ul class="list-group list-group-flush mb-3">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Paciente:</span>
                <strong>{{ citaSeleccionada.paciente }}</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Servicio(s):</span>
                <span>{{ citaSeleccionada.servicio }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Especialista:</span>
                <span>{{ citaSeleccionada.especialista }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Horario:</span>
                <span>{{ citaSeleccionada.hora_inicio }} - {{ citaSeleccionada.hora_fin }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Cabina:</span>
                <span>{{ citaSeleccionada.cabina }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Monto Total:</span>
                <strong class="text-success">${{ citaSeleccionada.monto.toFixed(2) }}</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span class="text-muted">Estado Actual:</span>
                <span class="badge" :class="{
                  'bg-success': citaSeleccionada.estado === 'Completado',
                  'bg-warning text-dark': citaSeleccionada.estado === 'En Proceso',
                  'bg-secondary': citaSeleccionada.estado === 'Confirmado'
                }">{{ citaSeleccionada.estado }}</span>
              </li>
            </ul>

            <div class="p-3 bg-light rounded small mb-3" v-if="citaSeleccionada.observaciones">
              <strong>Observaciones:</strong> {{ citaSeleccionada.observaciones }}
            </div>
          </div>
          <div class="modal-footer border-top d-flex justify-content-between">
            <button type="button" class="btn btn-outline-danger btn-sm">Cancelar Cita</button>
            <button type="button" class="btn btn-ayla-primary btn-sm" data-bs-dismiss="modal">Cerrar</button>
          </div>
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
  turnos: Array,
  calendario: Object,
  pacientes_lista: Array,
  servicios_lista: Array,
  especialistas_lista: Array
});

const vistaActual = ref('lista');
const fechaCalendario = ref(props.filters?.fecha || new Date().toISOString().substr(0, 10));
const mesCalendario = ref(new Date().getMonth());
const anioCalendario = ref(new Date().getFullYear());

const nombreMes = computed(() => {
  return new Date(anioCalendario.value, mesCalendario.value, 1).toLocaleDateString('es-ES', { month: 'long' });
});

const diasCalendario = computed(() => {
  const primerDia = new Date(anioCalendario.value, mesCalendario.value, 1);
  const ultimoDia = new Date(anioCalendario.value, mesCalendario.value + 1, 0);
  const dias = [];
  const inicioSemana = (primerDia.getDay() + 6) % 7;

  for (let i = 0; i < inicioSemana; i++) {
    const fecha = new Date(anioCalendario.value, mesCalendario.value, i - inicioSemana + 1);
    dias.push({ dia: fecha.getDate(), fecha: fecha.toISOString().slice(0, 10), enMes: false, citas: 0, activo: false });
  }

  for (let dia = 1; dia <= ultimoDia.getDate(); dia++) {
    const fecha = new Date(anioCalendario.value, mesCalendario.value, dia);
    const key = fecha.toISOString().slice(0, 10);
    const citas = props.calendario?.[key] || 0;
    dias.push({ dia, fecha: key, enMes: true, citas, activo: key === fechaCalendario.value });
  }

  return dias;
});

const turnosDelDiaSeleccionado = computed(() => {
  if (!fechaCalendario.value) {
    return [];
  }

  return props.turnos.filter((turno) => {
    const fechaTurno = props.filters?.fecha || fechaCalendario.value;
    return turno.hora_inicio && turno.hora_inicio.includes(':') && fechaTurno === fechaCalendario.value;
  });
});

const cambiarMes = (delta) => {
  const nuevaFecha = new Date(anioCalendario.value, mesCalendario.value + delta, 1);
  mesCalendario.value = nuevaFecha.getMonth();
  anioCalendario.value = nuevaFecha.getFullYear();

  const fechaMes = `${nuevaFecha.getFullYear()}-${String(nuevaFecha.getMonth() + 1).padStart(2, '0')}-01`;
  filterForm.value.fecha = fechaMes;
  router.get('/agenda', { ...filterForm.value, fecha: fechaMes }, { preserveState: true });
};

const seleccionarDia = (dia) => {
  fechaCalendario.value = dia.fecha;
  filterForm.value.fecha = dia.fecha;
  router.get('/agenda', { ...filterForm.value, fecha: dia.fecha }, { preserveState: true });
};

const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);
const isSpecialist = computed(() => authUser.value?.role === 'especialista');
const disponibilidadMensaje = computed(() => page.props.errors?.disponibilidad || '');

// Formulario reactivo para los Filtros de búsqueda
const filterForm = ref({
  fecha: props.filters.fecha || new Date().toISOString().substr(0, 10),
  especialista_id: isSpecialist.value ? (authUser.value?.id || '') : (props.filters.especialista_id || ''),
  estado: props.filters.estado || ''
});

const aplicarFiltros = () => {
  const payload = { ...filterForm.value };
  if (isSpecialist.value) {
    payload.especialista_id = authUser.value?.id || '';
  }
  fechaCalendario.value = payload.fecha;
  router.get('/agenda', payload, { preserveState: true });
};

const limpiarFiltros = () => {
  filterForm.value = {
    fecha: new Date().toISOString().substr(0, 10),
    especialista_id: isSpecialist.value ? (authUser.value?.id || '') : '',
    estado: ''
  };
  router.get('/agenda');
};

// Cita seleccionada para modal de detalle
const citaSeleccionada = ref(null);
const verDetalle = (cita) => {
  citaSeleccionada.value = cita;
};

// Manejo del estado de selección múltiple de servicios
const serviciosState = ref(
  props.servicios_lista.map((s, index) => ({
    ...s,
    selected: index === 0
  }))
);

// Formulario de Asignación de Turno (Inertia)
const formTurno = useForm({
  paciente_id: '',
  especialista_id: isSpecialist.value ? (authUser.value?.id || '') : '',
  servicios: [],
  fecha: props.filters.fecha || new Date().toISOString().substr(0, 10),
  hora_inicio: '08:00',
  holgura_min: 15
});

// Cálculos reactivos de tiempo y costo
const duracionTotal = computed(() => {
  const min = serviciosState.value.filter(s => s.selected).reduce((acc, s) => acc + s.duracion, 0);
  return min > 0 ? min + Number(formTurno.holgura_min) : 0;
});

const precioTotal = computed(() => {
  return serviciosState.value.filter(s => s.selected).reduce((acc, s) => acc + s.precio, 0);
});

const estadoClass = (estado) => {
  if (estado === 'Completado') return 'completado';
  if (estado === 'En Proceso') return 'en-proceso';
  return 'pendiente';
};

const estadoBadgeClass = (estado) => {
  if (estado === 'Completado') return 'bg-success';
  if (estado === 'En Proceso') return 'bg-warning text-dark';
  return 'bg-secondary';
};

const resetServicios = () => {
  serviciosState.value = props.servicios_lista.map((s) => ({
    ...s,
    selected: false
  }));
};

const guardarTurno = () => {
  formTurno.servicios = serviciosState.value.filter(s => s.selected).map(s => s.id);
  formTurno.post('/agenda', {
    onSuccess: () => {
      const modalEl = document.getElementById('modalNuevoTurno');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
      formTurno.reset();
      resetServicios();
    }
  });
};
</script>

<style scoped>
.turno-card {
  border-left: 4px solid var(--ayla-rose);
  background-color: rgba(229, 218, 196, 0.25);
  border-radius: 8px;
  padding: 10px;
}
.turno-card.completado {
  border-left-color: #5b8c5a;
  background-color: rgba(91, 140, 90, 0.08);
}
.turno-card.en-proceso {
  border-left-color: #ffc107;
  background-color: rgba(255, 193, 7, 0.08);
}
.turno-card.pendiente {
  border-left-color: var(--ayla-taupe);
  background-color: rgba(181, 165, 150, 0.15);
}
</style>