<template>
  <MainLayout>
    <div class="container-fluid px-4">
      
      <!-- Banner de Bienvenida y Acciones Rápidas -->
      <div class="card-ayla p-4 mb-4 bg-ayla-cream text-ayla-dark">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h2 class="brand-font fw-bold mb-1">Panel de Control Administrativo</h2>
            <p class="mb-0">Resumen operativo general de turnos, ingresos y atención a pacientes.</p>
          </div>
          <div v-if="isAdmin" class="d-flex gap-2">
            <button class="btn btn-ayla-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoTurno">
              <i class="bi bi-plus-circle me-1"></i> Agendar Turno
            </button>
            <button class="btn btn-ayla-secondary" data-bs-toggle="modal" data-bs-target="#modalNuevoPaciente">
              <i class="bi bi-person-plus me-1"></i> Registrar Paciente
            </button>
          </div>
        </div>
      </div>

      <div v-if="isAdmin" class="card-ayla p-4 mb-4 border-ayla-rose">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h5 class="brand-font fw-bold mb-1">Tasas de cambio BCV</h5>
            <p class="text-muted small mb-0">Se guardan como histórico al agendar cada cita.</p>
          </div>
          <form class="row g-2 align-items-end" @submit.prevent="actualizarTasas">
            <div class="col-auto">
              <label class="form-label small mb-1">Dólar en Bs</label>
              <input v-model.number="tasasForm.dolar_bcv" type="number" min="0.0001" step="0.0001" class="form-control" required>
            </div>
            <div class="col-auto">
              <label class="form-label small mb-1">Euro en Bs</label>
              <input v-model.number="tasasForm.euro_bcv" type="number" min="0.0001" step="0.0001" class="form-control" required>
            </div>
            <div class="col-auto">
              <button class="btn btn-ayla-primary" type="submit">Guardar tasas</button>
            </div>
          </form>
        </div>
        <div class="small text-muted mt-2">Última actualización: {{ tasas?.actualizada_en || 'Consultando API...' }}</div>
      </div>

      <!-- Tarjetas KPI -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card-ayla p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small">Ingresos del Mes</span>
                <h3 class="fw-bold text-ayla-dark mb-0">${{ kpis.ingresos_mes.toFixed(2) }}</h3>
                <span class="text-ayla-rose small fw-medium">Bs. {{ Number(kpis.ingresos_mes_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                <span class="text-success small fw-medium"><i class="bi bi-arrow-up-right"></i> +14% vs mes anterior</span>
              </div>
              <div class="bg-ayla-cream p-3 rounded-circle text-ayla-dark">
                <i class="bi bi-currency-dollar fs-3"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-ayla p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small">Turnos Hoy</span>
                <h3 class="fw-bold text-ayla-dark mb-0">{{ kpis.turnos_hoy }} Citas</h3>
                <span class="text-ayla-rose small fw-medium">6 Confirmadas • 2 En proceso</span>
              </div>
              <div class="bg-ayla-rose p-3 rounded-circle text-white">
                <i class="bi bi-calendar-check fs-3"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-ayla p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small">Pacientes Registrados</span>
                <h3 class="fw-bold text-ayla-dark mb-0">{{ kpis.pacientes_totales }}</h3>
                <span class="text-muted small">+12 nuevos este mes</span>
              </div>
              <div class="bg-ayla-taupe p-3 rounded-circle text-white">
                <i class="bi bi-people fs-3"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card-ayla p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small">Especialistas Activos</span>
                <h3 class="fw-bold text-ayla-dark mb-0">{{ kpis.especialistas_activos }} Profesionales</h3>
                <span class="text-success small fw-medium">100% Disponibilidad</span>
              </div>
              <div class="bg-ayla-dark p-3 rounded-circle text-white">
                <i class="bi bi-person-badge fs-3"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sección Central: Tabla Citas de Hoy + Gráficos/Estado -->
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card-ayla p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="brand-font fw-bold text-ayla-dark mb-0">Turnos Programados para Hoy</h5>
              <Link href="/agenda" class="btn btn-sm btn-outline-secondary">Ver Agenda Completa</Link>
            </div>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Hora</th>
                    <th>Paciente</th>
                    <th>Servicio(s)</th>
                    <th>Especialista</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th class="text-end">Acción</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="c in citas_hoy" :key="c.id">
                    <td><strong>{{ c.hora }}</strong></td>
                    <td>{{ c.paciente }}</td>
                    <td>{{ c.servicio }}</td>
                    <td>{{ c.especialista }}<br><span v-if="c.asistente" class="small text-ayla-rose">Asistente: {{ c.asistente }} (3%)</span></td>
                    <td><strong>${{ c.monto.toFixed(2) }}</strong><br><span class="small text-ayla-rose">Bs. {{ Number(c.monto_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span></td>
                    <td>
                      <span class="badge" :class="{
                        'bg-success': c.estado === 'Completado',
                        'bg-warning text-dark': c.estado === 'En Proceso',
                        'bg-secondary': c.estado === 'Confirmado'
                      }">{{ c.estado }}</span>
                    </td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-light border py-0 px-2" @click="verDetalleCita(c)" data-bs-toggle="modal" data-bs-target="#modalDetalleCita">
                        Detalle
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card-ayla p-4 mb-4">
            <h5 class="brand-font fw-bold text-ayla-dark mb-3">Distribución por Servicio</h5>
            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span>Cosmiatría & Faciales</span>
                <strong>45% ($576)</strong>
              </div>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-ayla-dark" style="width: 45%;"></div>
              </div>
            </div>
            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span>Masajes & Spa</span>
                <strong>30% ($384)</strong>
              </div>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-ayla-rose" style="width: 30%;"></div>
              </div>
            </div>
            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span>Manicura / Pedicura</span>
                <strong>25% ($320)</strong>
              </div>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-ayla-cream text-dark" style="width: 25%;"></div>
              </div>
            </div>
          </div>

          
        </div>
      </div>

    </div>

    <!-- MODAL 1: AGENDAR NUEVO TURNO -->
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
                  <select class="form-select" v-model="formTurno.especialista_id" required>
                    <option value="">Seleccionar especialista...</option>
                    <option v-for="e in especialistas_lista" :key="e.id" :value="e.id">{{ e.name }}</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-medium">Asistente (opcional)</label>
                  <select class="form-select" v-model="formTurno.asistente_id">
                    <option value="">Sin asistente</option>
                    <option v-for="e in asistentesDisponibles" :key="e.id" :value="e.id">{{ e.name }} (3%)</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label fw-medium d-flex justify-content-between">
                    <span>Servicios de la Cita (Acumulables)</span>
                    <span class="text-muted small">Seleccione uno o varios</span>
                  </label>
                  <div class="card p-3 bg-light border">
                    <div v-for="s in serviciosState" :key="s.id" class="form-check mb-2">
                      <input class="form-check-input" type="checkbox" :id="'srv-dash-'+s.id" v-model="s.selected">
                      <label class="form-check-label d-flex justify-content-between w-100" :for="'srv-dash-'+s.id">
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
                  <label class="form-label fw-medium">Tiempo de Holgura</label>
                  <select class="form-select" v-model="formTurno.holgura_min">
                    <option :value="10">10 minutos</option>
                    <option :value="15">15 minutos</option>
                    <option :value="20">20 minutos</option>
                  </select>
                </div>

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
              <button type="submit" class="btn btn-ayla-primary px-4" :disabled="formTurno.processing">Guardar Turno</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL 2: REGISTRAR NUEVO PACIENTE -->
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
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Identificación (CI/DNI)</label>
                  <input type="text" class="form-control" v-model="formPaciente.cedula" placeholder="V-12345678" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Teléfono</label>
                  <input type="text" class="form-control" v-model="formPaciente.telefono" placeholder="+58 412..." required>
                </div>
                <div class="col-12">
                  <label class="form-label fw-medium">Correo Electrónico</label>
                  <input type="email" class="form-control" v-model="formPaciente.email" placeholder="paciente@email.com">
                </div>
                <div class="col-12">
                  <label class="form-label fw-medium">Notas / Observaciones Médicas</label>
                  <textarea class="form-control" rows="2" v-model="formPaciente.notas" placeholder="Alergias, tipo de piel..."></textarea>
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

    <!-- MODAL 3: DETALLE / ESTADO DE CITA -->
    <div class="modal fade" id="modalDetalleCita" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-ayla border-0" v-if="citaSeleccionada">
          <div class="modal-header bg-ayla-cream">
            <h5 class="modal-title brand-font fw-bold">Detalles de Cita #AY-{{ citaSeleccionada.id }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <ul class="list-group list-group-flush mb-3">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Paciente:</span>
                <strong>{{ citaSeleccionada.paciente }}</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Servicio:</span>
                <span>{{ citaSeleccionada.servicio }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Especialista:</span>
                <span>{{ citaSeleccionada.especialista }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Cabina Asignada:</span>
                <span>{{ citaSeleccionada.cabina }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Monto Total:</span>
                <strong class="text-success">${{ citaSeleccionada.monto.toFixed(2) }}</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Estado Actual:</span>
                <span class="badge bg-success">{{ citaSeleccionada.estado }}</span>
              </li>
            </ul>
            <div class="p-3 bg-light rounded small" v-if="citaSeleccionada.observaciones">
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
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
  kpis: Object,
  citas_hoy: Array,
  pacientes_lista: Array,
  servicios_lista: Array,
  especialistas_lista: Array,
  tasas: Object
});

const isAdmin = computed(() => usePage().props.auth?.user?.role === 'admin');
const tasasForm = ref({
  dolar_bcv: Number(props.tasas?.dolar_bcv || 0),
  euro_bcv: Number(props.tasas?.euro_bcv || 0)
});
const actualizarTasas = () => {
  useForm(tasasForm.value).post('/tasas-cambio');
};

// Cita seleccionada para ver en modal
const citaSeleccionada = ref(null);
const verDetalleCita = (cita) => {
  citaSeleccionada.value = cita;
};

// Servicios interactivos con selección múltiple
const serviciosState = ref(
  props.servicios_lista.map((s, index) => ({
    ...s,
    selected: index === 0 // primer servicio seleccionado por defecto
  }))
);

// Formulario de Turno (Inertia)
const formTurno = useForm({
  paciente_id: '',
  especialista_id: '',
  asistente_id: '',
  servicios: [],
  fecha: new Date().toISOString().substr(0, 10),
  hora_inicio: '09:00',
  holgura_min: 15
});

const asistentesDisponibles = computed(() => props.especialistas_lista.filter((especialista) => {
  return Number(especialista.id) !== Number(formTurno.especialista_id || 0);
}));

// Cálculos computados reactivos
const duracionTotal = computed(() => {
  const min = serviciosState.value.filter(s => s.selected).reduce((acc, s) => acc + s.duracion, 0);
  return min > 0 ? min + Number(formTurno.holgura_min) : 0;
});

const precioTotal = computed(() => {
  return serviciosState.value.filter(s => s.selected).reduce((acc, s) => acc + s.precio, 0);
});

const guardarTurno = () => {
  formTurno.servicios = serviciosState.value.filter(s => s.selected).map(s => s.id);
  formTurno.post('/agenda', {
    onSuccess: () => {
      // Ocultar modal mediante Bootstrap JS
      const modalEl = document.getElementById('modalNuevoTurno');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
      formTurno.reset();
    }
  });
};

// Formulario de Paciente (Inertia)
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