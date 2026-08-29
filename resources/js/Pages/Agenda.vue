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
                      <span class="badge bg-ayla-dark">${{ turno.monto.toFixed(2) }}<br>Bs. {{ Number(turno.monto_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                    </div>
                    <div class="small text-muted mt-1"><i class="bi bi-clock me-1"></i> {{ turno.hora_inicio }} - {{ turno.hora_fin }} ({{ turno.duracion_min }} min)</div>
                    <div class="small fw-medium text-ayla-dark">{{ turno.servicio }}</div>
                    <div class="small text-muted"><em>Esp: {{ turno.especialista }}</em></div>
                    <div v-if="turno.asistente" class="small text-muted"><em>Asistente: {{ turno.asistente }} (3%)</em></div>
                    <div class="small text-muted mt-1">Cabina: {{ turno.cabina }}</div>
                    <div class="mt-2 d-flex justify-content-between align-items-center">
                      <span class="badge" :class="estadoBadgeClass(turno.estado)">{{ turno.estado }}</span>
                      <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-light border py-0 px-2" @click="verDetalle(turno)" data-bs-toggle="modal" data-bs-target="#modalDetalleCita">
                          Ver detalle
                        </button>
                        <button v-if="isAdmin" class="btn btn-sm btn-ayla-outline py-0 px-2" @click="abrirEdicionCita(turno)" data-bs-toggle="modal" data-bs-target="#modalEditarCita">
                          <i class="bi bi-pencil"></i> Modificar
                        </button>
                        <button v-if="isAdmin" class="btn btn-sm btn-outline-danger py-0 px-2" @click="eliminarCita(turno.id)" title="Eliminar cita">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
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
              <div class="calendar-weekdays text-center small text-muted fw-bold mb-2">
                <div class="col">Lu</div>
                <div class="col">Ma</div>
                <div class="col">Mi</div>
                <div class="col">Ju</div>
                <div class="col">Vi</div>
                <div class="col">Sa</div>
                <div class="col">Do</div>
              </div>
              <div class="calendar-grid">
                <div v-for="(dia, index) in diasCalendario" :key="index" class="calendar-day">
                  <button class="w-100 border rounded p-2 text-start position-relative h-100" :class="{
                    'bg-ayla-cream': dia.activo,
                    'border-ayla-rose': dia.fecha === fechaCalendario,
                    'text-muted': !dia.enMes,
                    'bg-light': !dia.activo && dia.enMes
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
                  <div v-if="isAdmin" class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-ayla-outline" @click="abrirEdicionCita(turno)" data-bs-toggle="modal" data-bs-target="#modalEditarCita">
                      <i class="bi bi-pencil me-1"></i> Modificar
                    </button>
                    <button class="btn btn-sm btn-outline-danger" @click="eliminarCita(turno.id)">
                      <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                  </div>
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
                  <input v-model="busquedaPaciente" type="text" class="form-control form-control-sm mb-2" placeholder="Buscar cliente...">
                  <select class="form-select" v-model="formTurno.paciente_id" required>
                    <option value="">Seleccionar paciente...</option>
                    <option v-for="p in pacientesFiltrados" :key="p.id" :value="p.id">{{ p.nombre }} ({{ p.cedula }})</option>
                  </select>
                </div>

                <div class="col-md-5">
                  <label class="form-label fw-medium">Especialista principal</label>
                  <input v-model="busquedaEspecialista" type="text" class="form-control form-control-sm mb-2" placeholder="Buscar especialista..." :disabled="isSpecialist">
                  <select class="form-select" v-model="formTurno.especialista_id" :disabled="isSpecialist" required>
                    <option value="">Seleccionar especialista...</option>
                    <option v-for="e in especialistasFiltrados" :key="e.id" :value="e.id">{{ e.name }} ({{ Number(e.comision || 0).toFixed(2) }}%)</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-medium">Asistente (opcional)</label>
                  <input v-model="busquedaAsistente" type="text" class="form-control form-control-sm mb-2" placeholder="Buscar asistente..." :disabled="isSpecialist">
                  <select class="form-select" v-model="formTurno.asistente_id" :disabled="isSpecialist">
                    <option value="">Sin asistente</option>
                    <option v-for="e in asistentesFiltrados" :key="e.id" :value="e.id">{{ e.name }}</option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-medium">Comisión asistente (% sobre monto total)</label>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="form-control"
                    v-model.number="formTurno.comision_asistente"
                    placeholder="0.00"
                    :disabled="!formTurno.asistente_id"
                  >
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
                  <input v-model="busquedaServicio" type="text" class="form-control form-control-sm mb-3" placeholder="Buscar servicio...">
                  <div v-if="serviciosFiltrados.length" class="card p-3 bg-light border">
                    <div v-for="s in serviciosFiltrados" :key="s.id" class="form-check mb-3">
                      <input class="form-check-input" type="checkbox" :id="'srv-agenda-'+s.id" v-model="s.selected">
                      <label class="form-check-label d-flex justify-content-between w-100" :for="'srv-agenda-'+s.id">
                        <span>{{ s.nombre }} ({{ s.duracion }} min)</span>
                        <strong class="text-ayla-dark">${{ Number(s.precio ?? 0).toFixed(2) }}<br><span class="small text-ayla-rose">Bs. {{ Number(s.precio_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span></strong>
                      </label>

                      <div v-if="s.selected" class="mt-2 ms-4">
                        <div class="row g-2 align-items-center">
                          <div class="col-6">
                            <label class="form-label small text-muted mb-1">Especialista del servicio</label>
                            <select
                              class="form-select form-select-sm"
                              :value="formTurno.servicio_especialistas[s.id] ?? getServicioEspecialistaDefault(s)"
                              @change="seleccionarEspecialistaServicio(s, $event.target.value)"
                            >
                              <option v-for="especialista in getEspecialistasServicioDisponibles(s)" :key="especialista.id" :value="especialista.id">
                                {{ especialista.name }} ({{ Number(especialista.comision || 0).toFixed(2) }}%)
                              </option>
                            </select>
                          </div>
                          <div class="col-3">
                            <label class="form-label small text-muted mb-1">Tipo de comisión</label>
                            <select class="form-select form-select-sm" v-model="formTurno.servicio_comision_tipos[s.id]">
                              <option value="porcentaje">Porcentaje</option>
                              <option value="monto">Monto</option>
                            </select>
                          </div>
                          <div class="col-3">
                            <label class="form-label small text-muted mb-1">{{ formTurno.servicio_comision_tipos[s.id] === 'monto' ? 'Gana especialista ($)' : 'Comisión (%)' }}</label>
                            <input
                              type="number"
                              min="0"
                              :max="formTurno.servicio_comision_tipos[s.id] === 'monto' ? undefined : 100"
                              step="0.01"
                              class="form-control form-control-sm"
                              :value="formTurno.servicio_comision_tipos[s.id] === 'monto' ? (formTurno.servicio_comision_montos[s.id] ?? 0) : (formTurno.servicio_comisiones[s.id] ?? getEspecialistaComision(s))"
                              @input="formTurno.servicio_comision_tipos[s.id] === 'monto' ? formTurno.servicio_comision_montos[s.id] = Number($event.target.value) || 0 : formTurno.servicio_comisiones[s.id] = Number($event.target.value) || 0"
                            >
                          </div>
                          <div class="col-3">
                            <label class="form-label small text-muted mb-1">Precio manual</label>
                            <input
                              type="number"
                              min="0"
                              step="0.01"
                              class="form-control form-control-sm"
                              :value="formTurno.precios_servicios[s.id] ?? s.precio"
                              @input="formTurno.precios_servicios[s.id] = Number($event.target.value) || 0"
                            >
                          </div>
                        </div>
                        <div class="mt-2 text-end small text-muted">
                          Precio del servicio: ${{ Number(formTurno.precios_servicios[s.id] ?? getServicioPrecioBase(s)).toFixed(2) }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="alert alert-warning small mb-0">
                    Selecciona un especialista para cargar sus servicios disponibles.
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

                <div class="col-12">
                  <label class="form-label fw-medium">Recurrencia del turno</label>
                  <select class="form-select" v-model="formTurno.recurrencia">
                    <option value="ninguna">Sin recurrencia</option>
                    <option value="diario">Diario</option>
                    <option value="semanal">Cada semana</option>
                    <option value="quincenal">Quincenal (cada 2 semanas)</option>
                    <option value="mensual">Mensual</option>
                  </select>
                </div>

                <div v-if="formTurno.recurrencia !== 'ninguna'" class="col-12">
                  <div class="border rounded p-3 bg-light">
                    <div v-if="formTurno.recurrencia !== 'diario'">
                      <label class="form-label fw-medium">Días de la semana</label>
                      <div class="d-flex flex-wrap gap-2 mb-3">
                        <label v-for="dia in diasSemanaOptions" :key="dia.value" class="btn btn-sm border rounded-pill px-3" :class="{
                          'btn-ayla-primary text-white': formTurno.dias_semana.includes(dia.value),
                          'btn-outline-secondary': !formTurno.dias_semana.includes(dia.value)
                        }">
                          <input type="checkbox" class="form-check-input me-2" :value="dia.value" v-model="formTurno.dias_semana">{{ dia.label }}
                        </label>
                      </div>
                    </div>

                    <div v-else class="small text-muted mb-3">Se agendará una sesión diaria.</div>

                    <div v-if="formTurno.recurrencia === 'mensual'" class="small text-muted mb-2">
                      La serie mensual se repite en la misma fecha del mes desde la fecha de inicio.
                    </div>

                    <label class="form-label fw-medium">Número de sesiones</label>
                    <input type="number" min="1" max="60" class="form-control" v-model.number="formTurno.cantidad_sesiones">
                  </div>
                </div>

                <!-- Totalización y Desglose para el Cliente -->
                <div class="col-12 mt-3 p-3 bg-ayla-cream rounded border">
                  <h6 class="fw-bold brand-font text-ayla-dark mb-2">
                    <i class="bi bi-receipt me-2"></i>Totalización y Desglose para el Cliente
                  </h6>

                  <!-- Tabla de detalle de servicios seleccionados -->
                  <div v-if="serviciosState.some(s => s.selected)" class="table-responsive mb-3">
                    <table class="table table-sm table-bordered bg-white mb-0 align-middle">
                      <thead class="table-light">
                        <tr class="small text-muted">
                          <th>Servicio</th>
                          <th>Especialista</th>
                          <th class="text-center">Comisión Esp.</th>
                          <th class="text-end">Precio ($)</th>
                          <th class="text-end">Precio (Bs)</th>
                        </tr>
                      </thead>
                      <tbody class="small">
                        <tr v-for="s in serviciosState.filter(s => s.selected)" :key="'det-'+s.id">
                          <td>{{ s.nombre }}</td>
                          <td>{{ getEspecialistaActual(s)?.name || 'Principal' }}</td>
                          <td class="text-center">{{ Number(formTurno.servicio_comisiones[s.id] ?? getEspecialistaComision(s)).toFixed(2) }}%</td>
                          <td class="text-end fw-medium">${{ getServicioPrecioBase(s).toFixed(2) }}</td>
                          <td class="text-end text-ayla-rose">Bs. {{ (getServicioPrecioBase(s) * (s.precio_bs / (s.precio || 1))).toFixed(2) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="row g-2 align-items-center pt-2 border-top">
                    <div class="col-md-6">
                      <div class="small text-muted mb-1">
                        Subtotal Servicios: <strong class="text-dark">${{ subtotalServiciosUsd.toFixed(2) }}</strong> / <strong class="text-ayla-rose">Bs. {{ subtotalServiciosBs.toFixed(2) }}</strong>
                      </div>
                      <div v-if="formTurno.asistente_id" class="small text-muted mb-1">
                        Recargo Asistente ({{ Number(formTurno.comision_asistente || 0).toFixed(2) }}%): <strong class="text-dark">+${{ comisionAsistenteMontoUsd.toFixed(2) }}</strong> / <strong class="text-ayla-rose">+Bs. {{ comisionAsistenteMontoBs.toFixed(2) }}</strong>
                      </div>
                      <div class="small text-muted">
                        Duración estimada: <strong>{{ duracionTotal }} min</strong>
                      </div>
                    </div>

                    <div class="col-md-6 text-end">
                      <span class="text-muted small d-block">TOTAL A PAGAR POR EL CLIENTE:</span>
                      <strong class="fs-3 text-ayla-dark d-block">${{ precioTotal.toFixed(2) }}</strong>
                      <span class="text-ayla-rose fw-bold fs-6">Bs. {{ precioTotalBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                    </div>
                  </div>

                  <!-- Ganancias estimadas asignadas a cada especialista -->
                  <div v-if="resumenGananciasEspecialistas.length" class="mt-3 pt-2 border-top">
                    <span class="text-muted small fw-bold d-block mb-1"><i class="bi bi-wallet2 me-1"></i>Ganancias asignadas a especialistas:</span>
                    <div class="d-flex flex-wrap gap-2">
                      <span v-for="g in resumenGananciasEspecialistas" :key="g.nombre" class="badge bg-white text-dark border p-2">
                        <strong>{{ g.nombre }}:</strong> ${{ g.gananciaUsd.toFixed(2) }}
                      </span>
                      <span v-if="formTurno.asistente_id" class="badge bg-white text-dark border p-2">
                        <strong>Asistente ({{ (asistentesFiltrados.find(a => a.id === formTurno.asistente_id)?.name || 'Asistente') }}):</strong> ${{ comisionAsistenteMontoUsd.toFixed(2) }}
                      </span>
                    </div>
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
                <span class="text-muted">Especialista Principal:</span>
                <span>{{ citaSeleccionada.especialista }}</span>
              </li>
              <li v-if="citaSeleccionada.asistente" class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Asistente:</span>
                <span>{{ citaSeleccionada.asistente }} ({{ Number(citaSeleccionada.comision_asistente_porcentaje || 0).toFixed(2) }}%)</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Horario:</span>
                <span>{{ citaSeleccionada.hora_inicio }} - {{ citaSeleccionada.hora_fin }}</span>
              </li>
            </ul>

            <!-- Detalle de Servicios de la Cita -->
            <div v-if="citaSeleccionada.servicios_detalle && citaSeleccionada.servicios_detalle.length" class="mb-3">
              <span class="fw-bold small text-muted d-block mb-2"><i class="bi bi-list-check me-1"></i>Desglose de Servicios & Ganancias por Especialista:</span>
              <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white align-middle mb-0">
                  <thead class="table-light">
                    <tr class="small text-muted">
                      <th>Servicio</th>
                      <th>Especialista</th>
                      <th class="text-center">Comisión</th>
                      <th class="text-end">Monto Servicio</th>
                      <th class="text-end">Ganancia Esp.</th>
                    </tr>
                  </thead>
                  <tbody class="small">
                    <tr v-for="sDet in citaSeleccionada.servicios_detalle" :key="'sd-'+sDet.id">
                      <td>{{ sDet.nombre }}</td>
                      <td>{{ sDet.especialista_nombre }}</td>
                      <td class="text-center">{{ Number(sDet.comision_porcentaje || 0).toFixed(2) }}%</td>
                      <td class="text-end fw-medium">
                        ${{ Number(sDet.precio || 0).toFixed(2) }}
                        <br><span class="small text-ayla-rose">Bs. {{ Number(sDet.precio_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                      </td>
                      <td class="text-end text-success fw-bold">
                        ${{ Number(sDet.ganancia || 0).toFixed(2) }}
                        <br><span class="small text-ayla-rose">Bs. {{ Number(sDet.ganancia_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                      </td>
                    </tr>
                    <tr v-if="citaSeleccionada.asistente" class="table-light">
                      <td><i class="bi bi-person-badge me-1"></i> Asistencia en cita</td>
                      <td>{{ citaSeleccionada.asistente }} (Asistente)</td>
                      <td class="text-center">{{ Number(citaSeleccionada.comision_asistente_porcentaje || 0).toFixed(2) }}%</td>
                      <td class="text-end text-muted">-</td>
                      <td class="text-end text-success fw-bold">
                        +${{ Number(citaSeleccionada.monto_asistente || 0).toFixed(2) }}
                        <br><span class="small text-ayla-rose">+Bs. {{ Number(citaSeleccionada.monto_asistente_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="p-3 bg-ayla-cream rounded d-flex justify-content-between align-items-center mb-3">
              <div>
                <span class="text-muted small d-block">TOTAL A PAGAR POR EL CLIENTE:</span>
                <span class="badge" :class="{
                  'bg-success': citaSeleccionada.estado === 'Completado',
                  'bg-warning text-dark': citaSeleccionada.estado === 'En Proceso',
                  'bg-secondary': citaSeleccionada.estado === 'Confirmado'
                }">{{ citaSeleccionada.estado }}</span>
              </div>
              <div class="text-end">
                <strong class="fs-4 text-ayla-dark">${{ Number(citaSeleccionada.monto || 0).toFixed(2) }}</strong>
                <span class="small text-ayla-rose d-block fw-bold">Bs. {{ Number(citaSeleccionada.monto_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
              </div>
            </div>

            <div class="border rounded p-3 bg-light mb-3">
              <label class="form-label fw-medium">Estado del turno</label>
              <select class="form-select mb-3" v-model="estadoForm.estado">
                <option value="Confirmado">Confirmado</option>
                <option value="En Proceso">En Proceso</option>
                <option value="Completado">Completado</option>
              </select>

              <label class="form-label fw-medium">Nota clínica / observaciones</label>
              <textarea class="form-control" rows="4" v-model="estadoForm.observaciones" placeholder="Escribe la evolución del paciente, tratamiento realizado o recomendaciones."></textarea>
            </div>

            <div class="p-3 bg-light rounded small mb-3" v-if="citaSeleccionada.observaciones">
              <strong>Observaciones:</strong> {{ citaSeleccionada.observaciones }}
            </div>
          </div>
          <div class="modal-footer border-top d-flex justify-content-between">
            <button type="button" class="btn btn-outline-danger btn-sm" @click="eliminarCita(citaSeleccionada.id)">
              <i class="bi bi-trash me-1"></i> Eliminar Cita
            </button>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-ayla-primary btn-sm" @click="guardarEstadoTurno" :disabled="estadoForm.processing">
                {{ estadoForm.processing ? 'Guardando...' : 'Guardar cambios' }}
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalEditarCita" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content card-ayla border-0" v-if="citaSeleccionada">
          <div class="modal-header bg-ayla-cream">
            <h5 class="modal-title brand-font fw-bold"><i class="bi bi-pencil-square me-2"></i>Modificar Cita #AY-{{ citaSeleccionada.id }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="guardarEdicionCita">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-medium">Paciente</label>
                  <select class="form-select" v-model="citaEditForm.paciente_id" required>
                    <option value="">Seleccionar paciente...</option>
                    <option v-for="paciente in pacientes_lista" :key="paciente.id" :value="paciente.id">{{ paciente.nombre }} ({{ paciente.cedula }})</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Especialista</label>
                  <select class="form-select" v-model="citaEditForm.especialista_id" required>
                    <option value="">Seleccionar especialista...</option>
                    <option v-for="especialista in especialistas_lista" :key="especialista.id" :value="especialista.id">{{ especialista.name }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Asistente (opcional)</label>
                  <select class="form-select" v-model="citaEditForm.asistente_id">
                    <option value="">Sin asistente</option>
                    <option v-for="especialista in especialistas_lista" :key="especialista.id" :value="especialista.id" :disabled="Number(especialista.id) === Number(citaEditForm.especialista_id)">{{ especialista.name }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium">Comisión del asistente (%)</label>
                  <input type="number" min="0" max="100" step="0.01" class="form-control" v-model.number="citaEditForm.comision_asistente" :disabled="!citaEditForm.asistente_id">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-medium">Fecha</label>
                  <input type="date" class="form-control" v-model="citaEditForm.fecha" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-medium">Hora de inicio</label>
                  <input type="time" class="form-control" v-model="citaEditForm.hora_inicio" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-medium">Holgura</label>
                  <select class="form-select" v-model="citaEditForm.holgura_min">
                    <option :value="10">10 minutos</option>
                    <option :value="15">15 minutos</option>
                    <option :value="20">20 minutos</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label fw-medium">Servicios</label>
                  <input v-model="busquedaServicioEdicion" type="text" class="form-control form-control-sm mb-2" placeholder="Buscar servicio...">
                  <div class="border rounded p-3 bg-light">
                    <div v-for="servicio in serviciosEdicion" :key="servicio.id" class="form-check mb-3">
                      <input class="form-check-input" type="checkbox" :id="'srv-edit-'+servicio.id" :value="servicio.id" v-model="citaEditForm.servicios">
                      <label class="form-check-label d-flex justify-content-between w-100" :for="'srv-edit-'+servicio.id">
                        <span>{{ servicio.nombre }} ({{ servicio.duracion }} min)</span>
                        <strong>${{ Number(citaEditForm.precios_servicios[servicio.id] ?? servicio.precio).toFixed(2) }}</strong>
                      </label>
                      <div v-if="citaEditForm.servicios.includes(Number(servicio.id))" class="row g-2 mt-1 ms-4">
                        <div class="col-md-6">
                          <label class="form-label small text-muted mb-1">Especialista del servicio</label>
                          <select class="form-select form-select-sm" v-model.number="citaEditForm.servicio_especialistas[servicio.id]">
                            <option v-for="especialista in getEspecialistasEdicion(servicio)" :key="especialista.id" :value="especialista.id">{{ especialista.name }} ({{ Number(especialista.comision || 0).toFixed(2) }}%)</option>
                          </select>
                        </div>
                          <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Tipo de comisión</label>
                            <select class="form-select form-select-sm" v-model="citaEditForm.servicio_comision_tipos[servicio.id]">
                              <option value="porcentaje">Porcentaje</option>
                              <option value="monto">Monto</option>
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">{{ citaEditForm.servicio_comision_tipos[servicio.id] === 'monto' ? 'Gana especialista ($)' : 'Comisión (%)' }}</label>
                            <input type="number" min="0" :max="citaEditForm.servicio_comision_tipos[servicio.id] === 'monto' ? undefined : 100" step="0.01" class="form-control form-control-sm" :value="citaEditForm.servicio_comision_tipos[servicio.id] === 'monto' ? (citaEditForm.servicio_comision_montos[servicio.id] ?? 0) : (citaEditForm.servicio_comisiones[servicio.id] ?? 0)" @input="citaEditForm.servicio_comision_tipos[servicio.id] === 'monto' ? citaEditForm.servicio_comision_montos[servicio.id] = Number($event.target.value) || 0 : citaEditForm.servicio_comisiones[servicio.id] = Number($event.target.value) || 0">
                        </div>
                        <div class="col-md-3">
                          <label class="form-label small text-muted mb-1">Precio manual</label>
                          <input type="number" min="0" step="0.01" class="form-control form-control-sm" v-model.number="citaEditForm.precios_servicios[servicio.id]">
                        </div>
                      </div>
                    </div>
                    <span v-if="!serviciosEdicion.length" class="text-muted small">No hay servicios asignados a este especialista.</span>
                  </div>
                </div>
                <div v-if="disponibilidadMensaje" class="col-12">
                  <div class="alert alert-warning mb-0 py-2 px-3 small">{{ disponibilidadMensaje }}</div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-between">
              <button type="button" class="btn btn-outline-danger" @click="eliminarCita(citaSeleccionada.id)">
                <i class="bi bi-trash me-1"></i> Eliminar Cita
              </button>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-ayla-primary" :disabled="citaEditForm.processing">Guardar cambios</button>
              </div>
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
import { ref, computed, watch } from 'vue';

const props = defineProps({
  filters: Object,
  turnos: Array,
  calendario: Object,
  pacientes_lista: Array,
  servicios_lista: Array,
  especialistas_lista: Array
});

const localDateKey = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const vistaActual = ref('lista');
const fechaCalendario = ref(props.filters?.fecha || localDateKey(new Date()));
const fechaInicial = new Date(`${fechaCalendario.value}T00:00:00`);
const mesCalendario = ref(fechaInicial.getMonth());
const anioCalendario = ref(fechaInicial.getFullYear());

const nombreMes = computed(() => {
  return new Date(anioCalendario.value, mesCalendario.value, 1).toLocaleDateString('es-ES', { month: 'long' });
});

const diasCalendario = computed(() => {
  const primerDia = new Date(anioCalendario.value, mesCalendario.value, 1);
  const ultimoDia = new Date(anioCalendario.value, mesCalendario.value + 1, 0);
  const dias = [];

  // La cabecera comienza en lunes; convertimos domingo (0) en la última columna.
  const primerDiaSemana = (primerDia.getDay() + 6) % 7;

  for (let i = primerDiaSemana; i > 0; i--) {
    const fecha = new Date(anioCalendario.value, mesCalendario.value, 1 - i);
    dias.push({
      dia: fecha.getDate(),
      fecha: localDateKey(fecha),
      enMes: false,
      citas: 0,
      activo: false,
    });
  }

  for (let dia = 1; dia <= ultimoDia.getDate(); dia++) {
    const fecha = new Date(anioCalendario.value, mesCalendario.value, dia);
    const key = localDateKey(fecha);
    dias.push({
      dia,
      fecha: key,
      enMes: true,
      citas: props.calendario?.[key] || 0,
      activo: key === fechaCalendario.value,
    });
  }

  const totalCeldas = Math.ceil(dias.length / 7) * 7;
  while (dias.length < totalCeldas) {
    const fecha = new Date(anioCalendario.value, mesCalendario.value, dias.length - primerDiaSemana + 1);
    dias.push({
      dia: fecha.getDate(),
      fecha: localDateKey(fecha),
      enMes: false,
      citas: 0,
      activo: false,
    });
  }

  return dias;
});

const turnosDelDiaSeleccionado = computed(() => {
  if (!fechaCalendario.value) {
    return [];
  }

  return (props.turnos || []).filter((turno) => turno.hora_inicio && turno.hora_inicio.includes(':'));
});

const cambiarMes = (delta) => {
  const nuevaFecha = new Date(anioCalendario.value, mesCalendario.value + delta, 1);
  mesCalendario.value = nuevaFecha.getMonth();
  anioCalendario.value = nuevaFecha.getFullYear();

  const fechaMes = localDateKey(new Date(nuevaFecha.getFullYear(), nuevaFecha.getMonth(), 1));
  filterForm.value.fecha = fechaMes;
  router.get('/agenda', { ...filterForm.value, fecha: fechaMes }, { preserveState: true });
};

const seleccionarDia = (dia) => {
  fechaCalendario.value = dia.fecha;
  mesCalendario.value = new Date(`${dia.fecha}T00:00:00`).getMonth();
  anioCalendario.value = new Date(`${dia.fecha}T00:00:00`).getFullYear();
  filterForm.value.fecha = dia.fecha;
  router.get('/agenda', { ...filterForm.value, fecha: dia.fecha }, { preserveState: true });
};

const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);
const isSpecialist = computed(() => authUser.value?.role === 'especialista');
const isAdmin = computed(() => authUser.value?.role === 'admin');
const disponibilidadMensaje = computed(() => page.props.errors?.disponibilidad || '');
const busquedaPaciente = ref('');
const busquedaEspecialista = ref('');
const busquedaAsistente = ref('');
const busquedaServicio = ref('');
const busquedaServicioEdicion = ref('');

const pacientesFiltrados = computed(() => {
  const texto = busquedaPaciente.value.trim().toLowerCase();
  return (props.pacientes_lista || []).filter((paciente) => {
    if (!texto) return true;
    const nombre = (paciente.nombre || '').toLowerCase();
    const cedula = (paciente.cedula || '').toLowerCase();
    return nombre.includes(texto) || cedula.includes(texto);
  });
});

const especialistasFiltrados = computed(() => {
  const texto = busquedaEspecialista.value.trim().toLowerCase();
  return (props.especialistas_lista || []).filter((especialista) => {
    if (!texto) return true;
    return especialista.name?.toLowerCase().includes(texto);
  });
});

const asistentesFiltrados = computed(() => {
  const texto = busquedaAsistente.value.trim().toLowerCase();
  return (props.especialistas_lista || []).filter((especialista) => {
    if (Number(especialista.id) === Number(formTurno.especialista_id || 0)) return false;
    if (!texto) return true;
    return especialista.name?.toLowerCase().includes(texto);
  });
});

// Formulario reactivo para los Filtros de búsqueda
const filterForm = ref({
  fecha: props.filters.fecha || localDateKey(new Date()),
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
    fecha: localDateKey(new Date()),
    especialista_id: isSpecialist.value ? (authUser.value?.id || '') : '',
    estado: ''
  };
  router.get('/agenda');
};

// Cita seleccionada para modal de detalle
const citaSeleccionada = ref(null);
const estadoForm = useForm({
  estado: 'Confirmado',
  observaciones: ''
});
const citaEditForm = useForm({
  paciente_id: '',
  especialista_id: '',
  asistente_id: '',
  comision_asistente: 0,
  servicios: [],
  servicio_especialistas: {},
  servicio_comisiones: {},
  servicio_comision_tipos: {},
  servicio_comision_montos: {},
  precios_servicios: {},
  fecha: '',
  hora_inicio: '',
  holgura_min: 15
});

const serviciosEdicion = computed(() => {
  const texto = busquedaServicioEdicion.value.trim().toLowerCase();
  return (props.servicios_lista || []).filter((servicio) => {
    if (!texto) return true;
    return (servicio.nombre || '').toLowerCase().includes(texto)
      || (servicio.descripcion || '').toLowerCase().includes(texto);
  });
});

const getEspecialistasEdicion = (servicio) => {
  const especialistas = props.especialistas_lista || [];
  const relacionados = Array.isArray(servicio.especialistas) ? servicio.especialistas : [];

  const opciones = [
    ...especialistas,
    ...relacionados,
  ].filter((especialista, index, lista) => {
    const id = Number(especialista.id);
    return id > 0 && lista.findIndex((item) => Number(item.id) === id) === index;
  });

  return opciones;
};

const verDetalle = (cita) => {
  citaSeleccionada.value = cita;
  estadoForm.estado = cita.estado || 'Confirmado';
  estadoForm.observaciones = cita.observaciones || '';
};

const abrirEdicionCita = (cita) => {
  citaSeleccionada.value = cita;
  citaEditForm.paciente_id = cita.paciente_id;
  citaEditForm.especialista_id = cita.especialista_id;
  citaEditForm.asistente_id = cita.asistente_id || '';
  citaEditForm.comision_asistente = Number(cita.comision_asistente_porcentaje || 0);
  citaEditForm.servicios = (cita.servicio_ids || []).map((id) => Number(id));
  citaEditForm.fecha = cita.fecha || filterForm.value.fecha;
  citaEditForm.hora_inicio = convertirHoraA24(cita.hora_inicio);
  citaEditForm.holgura_min = Number(cita.holgura_min ?? 15);
  citaEditForm.servicio_especialistas = Object.fromEntries((cita.servicios_detalle || []).map((servicio) => [
    servicio.id,
    Number(servicio.especialista_id || cita.especialista_id)
  ]));
  citaEditForm.servicio_comisiones = Object.fromEntries((cita.servicios_detalle || []).map((servicio) => [
    servicio.id,
    Number(servicio.comision_porcentaje || 0)
  ]));
  citaEditForm.servicio_comision_tipos = Object.fromEntries((cita.servicios_detalle || []).map((servicio) => [servicio.id, servicio.comision_tipo || 'porcentaje']));
  citaEditForm.servicio_comision_montos = Object.fromEntries((cita.servicios_detalle || []).map((servicio) => [servicio.id, Number(servicio.comision_monto || 0)]));
  citaEditForm.precios_servicios = Object.fromEntries((cita.servicios_detalle || []).map((servicio) => [
    servicio.id,
    Number(servicio.precio || 0)
  ]));
  citaEditForm.clearErrors();
};

const convertirHoraA24 = (hora) => {
  const partes = hora.match(/(\d+):(\d+)\s*(AM|PM)/i);
  if (!partes) return hora;
  let horas = Number(partes[1]);
  if (partes[3].toUpperCase() === 'PM' && horas !== 12) horas += 12;
  if (partes[3].toUpperCase() === 'AM' && horas === 12) horas = 0;
  return `${String(horas).padStart(2, '0')}:${partes[2]}`;
};

const guardarEdicionCita = () => {
  citaEditForm.put('/agenda/' + citaSeleccionada.value.id, {
    preserveScroll: true,
    onSuccess: () => {
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarCita'));
      if (modal) modal.hide();
    }
  });
};

const eliminarCita = (citaId) => {
  if (!citaId) return;
  if (confirm('¿Estás seguro de que deseas eliminar esta cita de la agenda? Esta acción no se puede deshacer.')) {
    router.delete('/agenda/' + citaId, {
      preserveScroll: true,
      onSuccess: () => {
        const modalDetalleEl = document.getElementById('modalDetalleCita');
        if (modalDetalleEl) {
          const modalDetalle = bootstrap.Modal.getInstance(modalDetalleEl);
          if (modalDetalle) modalDetalle.hide();
        }
        const modalEditarEl = document.getElementById('modalEditarCita');
        if (modalEditarEl) {
          const modalEditar = bootstrap.Modal.getInstance(modalEditarEl);
          if (modalEditar) modalEditar.hide();
        }
      }
    });
  }
};

const guardarEstadoTurno = () => {
  if (!citaSeleccionada.value) return;

  estadoForm.put('/agenda/' + citaSeleccionada.value.id, {
    preserveScroll: true,
    onSuccess: () => {
      const modalEl = document.getElementById('modalDetalleCita');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
      estadoForm.reset();
      router.get('/agenda', {
        ...filterForm.value,
        fecha: filterForm.value.fecha || new Date().toISOString().substr(0, 10)
      }, { preserveState: true });
    }
  });
};

// Formulario de Asignación de Turno (Inertia)
const diasSemanaOptions = [
  { label: 'Lun', value: 0 },
  { label: 'Mar', value: 1 },
  { label: 'Mié', value: 2 },
  { label: 'Jue', value: 3 },
  { label: 'Vie', value: 4 },
  { label: 'Sáb', value: 5 },
  { label: 'Dom', value: 6 }
];

const formTurno = useForm({
  paciente_id: '',
  especialista_id: isSpecialist.value ? (authUser.value?.id || '') : '',
  asistente_id: '',
  comision_asistente: 0,
  servicios: [],
  precios_servicios: {},
  servicio_especialistas: {},
  servicio_comisiones: {},
  servicio_comision_tipos: {},
  servicio_comision_montos: {},
  fecha: props.filters.fecha || new Date().toISOString().substr(0, 10),
  hora_inicio: '08:00',
  holgura_min: 15,
  recurrencia: 'mensual',
  dias_semana: [],
  cantidad_sesiones: 3
});

const getEspecialistasServicioDisponibles = (servicio) => {
  const especialistas = props.especialistas_lista || [];
  const relacionados = Array.isArray(servicio.especialistas) ? servicio.especialistas : [];

  const opciones = [...especialistas, ...relacionados].filter((especialista, index, lista) => {
    const id = Number(especialista.id);
    return id > 0 && lista.findIndex((item) => Number(item.id) === id) === index;
  });

  return opciones;
};

const getEspecialistaActual = (servicio) => {
  const especialistaId = Number(formTurno.servicio_especialistas?.[servicio.id] ?? formTurno.especialista_id ?? getServicioEspecialistaDefault(servicio) ?? 0);
  return (props.especialistas_lista || []).find((item) => Number(item.id) === especialistaId) || null;
};

const getEspecialistaComision = (servicio) => {
  return Number(getEspecialistaActual(servicio)?.comision ?? 0);
};

const seleccionarEspecialistaServicio = (servicio, especialistaId) => {
  const id = Number(especialistaId) || Number(formTurno.especialista_id || 0);
  const especialista = (props.especialistas_lista || []).find((item) => Number(item.id) === id);

  formTurno.servicio_especialistas[servicio.id] = id;
  formTurno.servicio_comision_tipos[servicio.id] = 'porcentaje';
  formTurno.servicio_comisiones[servicio.id] = Number(especialista?.comision || 0);
  formTurno.servicio_comision_montos[servicio.id] = 0;
};

const getServicioEspecialistaDefault = (servicio) => {
  const especialistasDisponibles = getEspecialistasServicioDisponibles(servicio);
  const principalId = Number(formTurno.especialista_id || 0);

  if (principalId && especialistasDisponibles.some((especialista) => Number(especialista.id) === principalId)) {
    return principalId;
  }

  if (especialistasDisponibles.length > 0) {
    return Number(especialistasDisponibles[0].id);
  }

  return principalId || 0;
};

const getServicioPrecioBase = (servicio) => {
  const especialistaId = Number(formTurno.servicio_especialistas?.[servicio.id] ?? formTurno.especialista_id ?? getServicioEspecialistaDefault(servicio) ?? 0);
  const especialista = (servicio.especialistas || []).find((item) => Number(item.id) === especialistaId);
  return Number(formTurno.precios_servicios?.[servicio.id] ?? especialista?.precio_especialista ?? servicio.precio ?? 0);
};

const serviciosDisponibles = computed(() => {
  return props.servicios_lista || [];
});

const serviciosFiltrados = computed(() => {
  const texto = busquedaServicio.value.trim().toLowerCase();
  return (serviciosState.value || []).filter((servicio) => {
    if (!texto) return true;
    return (servicio.nombre || '').toLowerCase().includes(texto)
      || (servicio.descripcion || '').toLowerCase().includes(texto);
  });
});

const asistentesDisponibles = computed(() => props.especialistas_lista.filter((especialista) => {
  return Number(especialista.id) !== Number(formTurno.especialista_id || 0);
}));

const serviciosState = ref([]);

watch(
  () => formTurno.especialista_id,
  () => {
    serviciosState.value = (serviciosDisponibles.value || []).map((servicio) => ({
      ...servicio,
      selected: false,
    }));

    if (Number(formTurno.especialista_id || 0)) {
      const especialista = (props.especialistas_lista || []).find((e) => Number(e.id) === Number(formTurno.especialista_id));
      if (especialista) {
        formTurno.servicio_comisiones = { ...formTurno.servicio_comisiones, default: Number(especialista.comision || 0) };
      }
    }
  },
  { immediate: true }
);

watch(
  () => serviciosState.value.filter((s) => s.selected).map((s) => s.id),
  (ids) => {
    ids.forEach((id) => {
      if (!formTurno.servicio_comision_tipos[id]) {
        formTurno.servicio_comision_tipos[id] = 'porcentaje';
      }
      if (formTurno.servicio_comision_montos[id] === undefined) {
        formTurno.servicio_comision_montos[id] = 0;
      }
      if (formTurno.servicio_comisiones[id] === undefined || formTurno.servicio_comisiones[id] === null || formTurno.servicio_comisiones[id] === '') {
        const servicio = serviciosState.value.find((s) => Number(s.id) === Number(id));
        if (servicio) {
          formTurno.servicio_comisiones[id] = getEspecialistaComision(servicio);
        }
      }
    });
  },
  { deep: true }
);

// Cálculos reactivos de tiempo y costo
const duracionTotal = computed(() => {
  const min = serviciosState.value.filter(s => s.selected).reduce((acc, s) => acc + s.duracion, 0);
  return min > 0 ? min + Number(formTurno.holgura_min) : 0;
});

const tieneServicioRecurrente = computed(() => {
  return serviciosState.value.some(s => s.selected && s.es_recurrente === true);
});

const subtotalServiciosUsd = computed(() => {
  return serviciosState.value.filter(s => s.selected).reduce((acc, s) => {
    const precioBase = getServicioPrecioBase(s);
    const valorServicio = s.es_recurrente === true ? precioBase : precioBase * sesionesSeleccionadas.value;
    return acc + valorServicio;
  }, 0);
});

const subtotalServiciosBs = computed(() => {
  return serviciosState.value.filter(s => s.selected).reduce((acc, s) => {
    const precioBase = getServicioPrecioBase(s);
    const valorServicio = s.es_recurrente === true ? precioBase : precioBase * sesionesSeleccionadas.value;
    const tasa = Number(s.precio_bs || 0) / Number(s.precio || 1 || 1);
    return acc + valorServicio * tasa;
  }, 0);
});

const comisionAsistenteMontoUsd = computed(() => {
  if (!formTurno.asistente_id) return 0;
  const pct = Number(formTurno.comision_asistente || 0);
  return subtotalServiciosUsd.value * (pct / 100);
});

const comisionAsistenteMontoBs = computed(() => {
  if (!formTurno.asistente_id) return 0;
  const pct = Number(formTurno.comision_asistente || 0);
  return subtotalServiciosBs.value * (pct / 100);
});

const precioTotal = computed(() => {
  return subtotalServiciosUsd.value + comisionAsistenteMontoUsd.value;
});

const precioTotalBs = computed(() => {
  return subtotalServiciosBs.value + comisionAsistenteMontoBs.value;
});

const precioResumen = computed(() => {
  return serviciosState.value.filter(s => s.selected).reduce((acc, s) => {
    const precioBase = getServicioPrecioBase(s);
    return acc + precioBase;
  }, 0);
});

const resumenGananciasEspecialistas = computed(() => {
  const map = {};
  serviciosState.value.filter(s => s.selected).forEach(s => {
    const esp = getEspecialistaActual(s);
    const espId = esp ? esp.id : (formTurno.especialista_id || 0);
    const espNombre = esp ? esp.name : 'Especialista';
    const precioUsd = getServicioPrecioBase(s) * (s.es_recurrente === true ? 1 : sesionesSeleccionadas.value);
    const tipoComision = formTurno.servicio_comision_tipos?.[s.id] ?? 'porcentaje';
    const comisionPct = Number(formTurno.servicio_comisiones?.[s.id] ?? getEspecialistaComision(s) ?? 0);
    const gananciaUsd = tipoComision === 'monto'
      ? Number(formTurno.servicio_comision_montos?.[s.id] ?? 0) * (s.es_recurrente === true ? 1 : sesionesSeleccionadas.value)
      : precioUsd * (comisionPct / 100);

    if (!map[espId]) {
      map[espId] = {
        nombre: espNombre,
        totalServiciosUsd: 0,
        gananciaUsd: 0,
      };
    }
    map[espId].totalServiciosUsd += precioUsd;
    map[espId].gananciaUsd += gananciaUsd;
  });
  return Object.values(map);
});

const sesionesSeleccionadas = computed(() => {
  return formTurno.recurrencia === 'ninguna'
    ? 1
    : Math.max(1, Number(formTurno.cantidad_sesiones) || 1);
});

watch(
  () => formTurno.recurrencia,
  (recurrencia) => {
    if (recurrencia === 'diario') {
      formTurno.dias_semana = [];
    }
  }
);

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
  formTurno.precios_servicios = Object.fromEntries(
    serviciosState.value
      .filter(s => s.selected)
      .map(s => [s.id, Number(formTurno.precios_servicios?.[s.id] ?? getServicioPrecioBase(s) ?? 0)])
  );
  formTurno.servicio_especialistas = Object.fromEntries(
    serviciosState.value
      .filter(s => s.selected)
      .map(s => [s.id, Number(formTurno.servicio_especialistas?.[s.id] ?? getServicioEspecialistaDefault(s) ?? formTurno.especialista_id ?? 0)])
  );
  formTurno.servicio_comisiones = Object.fromEntries(
    serviciosState.value
      .filter(s => s.selected)
      .map(s => [s.id, Number(formTurno.servicio_comisiones?.[s.id] ?? getEspecialistaComision(s) ?? 0)])
  );
  formTurno.servicio_comision_tipos = Object.fromEntries(
    serviciosState.value.filter(s => s.selected).map(s => [s.id, formTurno.servicio_comision_tipos?.[s.id] ?? 'porcentaje'])
  );
  formTurno.servicio_comision_montos = Object.fromEntries(
    serviciosState.value.filter(s => s.selected).map(s => [s.id, Number(formTurno.servicio_comision_montos?.[s.id] ?? 0)])
  );

  if (!formTurno.asistente_id) {
    formTurno.comision_asistente = 0;
  }

  if (formTurno.recurrencia === 'ninguna') {
    formTurno.dias_semana = [];
    formTurno.cantidad_sesiones = 1;
  }

  if (formTurno.recurrencia !== 'ninguna' && formTurno.recurrencia !== 'diario' && formTurno.dias_semana.length === 0) {
    const fechaInicio = new Date(formTurno.fecha);
    const dayIndex = (fechaInicio.getDay() + 6) % 7;
    formTurno.dias_semana = [dayIndex];
  }

  formTurno.post('/agenda', {
    onSuccess: () => {
      const modalEl = document.getElementById('modalNuevoTurno');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
      formTurno.reset();
      formTurno.fecha = localDateKey(new Date());
      formTurno.recurrencia = 'mensual';
      formTurno.dias_semana = [];
      formTurno.cantidad_sesiones = 3;
      formTurno.precios_servicios = {};
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

.calendar-weekdays,
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 0.5rem;
}

.calendar-day {
  min-width: 0;
  min-height: 84px;
}

.calendar-day button {
  min-height: 84px;
}

@media (max-width: 576px) {
  .calendar-weekdays,
  .calendar-grid {
    gap: 0.25rem;
  }

  .calendar-day,
  .calendar-day button {
    min-height: 68px;
  }

  .calendar-day button {
    font-size: 0.8rem;
    padding: 0.4rem !important;
  }
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