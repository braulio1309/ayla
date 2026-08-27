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

      <div v-if="isAdmin" class="card-ayla p-3 mb-4">
        <form @submit.prevent="aplicarFiltro" class="row g-3 align-items-end">
          <div class="col-md-5">
            <label class="form-label small text-muted mb-1">Especialista</label>
            <select class="form-select" v-model="filterForm.especialista_id">
              <option value="">Todos los especialistas</option>
              <option v-for="e in especialistas_lista" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Fecha</label>
            <input type="date" class="form-control" v-model="filterForm.fecha">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-ayla-secondary w-100">Ver actividad</button>
          </div>
        </form>
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
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-ayla-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoTurno">
                  <i class="bi bi-plus-lg me-1"></i> Agendar Turno
                </button>
                <Link href="/agenda" class="btn btn-sm btn-outline-secondary">Ver Agenda Completa</Link>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Hora</th>
                    <th>Paciente</th>
                    <th>Servicio(s)</th>
                    <th>Especialista</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="citas_hoy.length === 0">
                    <td colspan="7" class="text-center py-4 text-muted">
                      No hay turnos registrados para el día seleccionado.
                    </td>
                  </tr>
                  <tr v-for="c in citas_hoy" :key="c.id">
                    <td><strong>{{ c.hora_inicio || c.hora }}</strong></td>
                    <td>{{ c.paciente }}</td>
                    <td>{{ c.servicio }}</td>
                    <td>{{ c.especialista }}<br><span v-if="c.asistente" class="small text-ayla-rose">Asistente: {{ c.asistente }} ({{ Number(c.comision_asistente_porcentaje || 3).toFixed(2) }}%)</span></td>
                    <td><strong>${{ Number(c.monto || 0).toFixed(2) }}</strong><br><span class="small text-ayla-rose">Bs. {{ Number(c.monto_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span></td>
                    <td>
                      <span class="badge" :class="{
                        'bg-success': c.estado === 'Completado',
                        'bg-warning text-dark': c.estado === 'En Proceso',
                        'bg-secondary': c.estado === 'Confirmado'
                      }">{{ c.estado }}</span>
                    </td>
                    <td class="text-end">
                      <div class="d-flex gap-1 justify-content-end">
                        <button class="btn btn-sm btn-light border py-0 px-2" @click="verDetalleCita(c)" data-bs-toggle="modal" data-bs-target="#modalDetalleCita">
                          Detalle
                        </button>
                        <button v-if="isAdmin" class="btn btn-sm btn-ayla-outline py-0 px-2" @click="abrirEdicionCita(c)" data-bs-toggle="modal" data-bs-target="#modalEditarCita">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button v-if="isAdmin" class="btn btn-sm btn-outline-danger py-0 px-2" @click="eliminarCita(c.id)" title="Eliminar cita">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
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
                  <div v-if="serviciosState.length" class="card p-3 bg-light border">
                    <div v-for="s in serviciosState" :key="s.id" class="form-check mb-3">
                      <input class="form-check-input" type="checkbox" :id="'srv-dash-'+s.id" v-model="s.selected">
                      <label class="form-check-label d-flex justify-content-between w-100" :for="'srv-dash-'+s.id">
                        <span>{{ s.nombre }} ({{ s.duracion }} min)</span>
                        <strong class="text-ayla-dark">${{ s.precio.toFixed(2) }}<br><span class="small text-ayla-rose">Bs. {{ Number(s.precio_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span></strong>
                      </label>

                      <div v-if="s.selected" class="mt-2 ms-4">
                        <div class="row g-2 align-items-center">
                          <div class="col-6">
                            <label class="form-label small text-muted mb-1">Especialista del servicio</label>
                            <select
                              class="form-select form-select-sm"
                              :value="formTurno.servicio_especialistas[s.id] ?? getServicioEspecialistaDefault(s)"
                              @change="formTurno.servicio_especialistas[s.id] = Number($event.target.value) || Number(formTurno.especialista_id || 0)"
                            >
                              <option v-for="especialista in getEspecialistasServicioDisponibles(s)" :key="especialista.id" :value="especialista.id">
                                {{ especialista.name }} ({{ Number(especialista.comision || 0).toFixed(2) }}%)
                              </option>
                            </select>
                          </div>
                          <div class="col-3">
                            <label class="form-label small text-muted mb-1">Comisión (%)</label>
                            <input
                              type="number"
                              min="0"
                              max="100"
                              step="0.01"
                              class="form-control form-control-sm"
                              :value="formTurno.servicio_comisiones[s.id] ?? getEspecialistaComision(s)"
                              @input="formTurno.servicio_comisiones[s.id] = Number($event.target.value) || 0"
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
                <span>{{ citaSeleccionada.hora_inicio || citaSeleccionada.hora }} - {{ citaSeleccionada.hora_fin || '' }}</span>
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

    <!-- MODAL 4: EDITAR CITA -->
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
                  <div class="border rounded p-3 bg-light">
                    <div v-for="servicio in serviciosEdicion" :key="servicio.id" class="form-check mb-3">
                      <input class="form-check-input" type="checkbox" :id="'srv-edit-dash-'+servicio.id" :value="servicio.id" v-model="citaEditForm.servicios">
                      <label class="form-check-label d-flex justify-content-between w-100" :for="'srv-edit-dash-'+servicio.id">
                        <span>{{ servicio.nombre }} ({{ servicio.duracion }} min)</span>
                        <strong>${{ Number(citaEditForm.precios_servicios[servicio.id] ?? servicio.precio).toFixed(2) }}</strong>
                      </label>
                      <div v-if="citaEditForm.servicios.includes(Number(servicio.id))" class="row g-2 mt-1 ms-4">
                        <div class="col-md-6">
                          <label class="form-label small text-muted mb-1">Especialista del servicio</label>
                          <select class="form-select form-select-sm" v-model.number="citaEditForm.servicio_especialistas[servicio.id]">
                            <option v-for="especialista in (servicio.especialistas || [])" :key="especialista.id" :value="especialista.id">{{ especialista.name }} ({{ Number(especialista.comision || 0).toFixed(2) }}%)</option>
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label class="form-label small text-muted mb-1">Comisión (%)</label>
                          <input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm" v-model.number="citaEditForm.servicio_comisiones[servicio.id]">
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
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
  kpis: Object,
  citas_hoy: Array,
  pacientes_lista: Array,
  servicios_lista: Array,
  especialistas_lista: Array,
  tasas: Object,
  filters: Object
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);
const isSpecialist = computed(() => authUser.value?.role === 'especialista');
const isAdmin = computed(() => authUser.value?.role === 'admin');
const disponibilidadMensaje = computed(() => page.props.errors?.disponibilidad || '');

const filterForm = ref({
  fecha: props.filters?.fecha || new Date().toISOString().substr(0, 10),
  especialista_id: props.filters?.especialista_id || ''
});
const tasasForm = ref({
  dolar_bcv: Number(props.tasas?.dolar_bcv || 0),
  euro_bcv: Number(props.tasas?.euro_bcv || 0)
});

watch(() => props.tasas, (nuevasTasas) => {
  if (nuevasTasas) {
    tasasForm.value.dolar_bcv = Number(nuevasTasas.dolar_bcv || 0);
    tasasForm.value.euro_bcv = Number(nuevasTasas.euro_bcv || 0);
  }
}, { deep: true });

const actualizarTasas = () => {
  useForm(tasasForm.value).post('/tasas-cambio');
};

const aplicarFiltro = () => {
  const payload = { ...filterForm.value };
  const fecha = payload.fecha || new Date().toISOString().substr(0, 10);
  payload.fecha = fecha;
  window.location.href = `/dashboard?fecha=${encodeURIComponent(fecha)}${payload.especialista_id ? `&especialista_id=${payload.especialista_id}` : ''}`;
};

const busquedaEspecialista = ref('');
const busquedaAsistente = ref('');

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

// Cita seleccionada para ver en modal
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
  precios_servicios: {},
  fecha: '',
  hora_inicio: '',
  holgura_min: 15
});

const serviciosEdicion = computed(() => {
  return props.servicios_lista || [];
});

const verDetalleCita = (cita) => {
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
  citaEditForm.fecha = cita.fecha || props.filters?.fecha || new Date().toISOString().substr(0, 10);
  citaEditForm.hora_inicio = convertirHoraA24(cita.hora_inicio || cita.hora || '08:00');
  citaEditForm.holgura_min = Number(cita.holgura_min ?? 15);
  citaEditForm.servicio_especialistas = Object.fromEntries((cita.servicios_detalle || []).map((servicio) => [
    servicio.id,
    Number(servicio.especialista_id || cita.especialista_id)
  ]));
  citaEditForm.servicio_comisiones = Object.fromEntries((cita.servicios_detalle || []).map((servicio) => [
    servicio.id,
    Number(servicio.comision_porcentaje || 0)
  ]));
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
  fecha: props.filters?.fecha || new Date().toISOString().substr(0, 10),
  hora_inicio: '08:00',
  holgura_min: 15,
  recurrencia: 'mensual',
  dias_semana: [],
  cantidad_sesiones: 3
});

const getEspecialistasServicioDisponibles = (servicio) => {
  const principalId = Number(formTurno.especialista_id || 0);
  const asistenteId = Number(formTurno.asistente_id || 0);
  const opciones = [];

  if (principalId) {
    const principal = (props.especialistas_lista || []).find((e) => Number(e.id) === principalId);
    if (principal) opciones.push(principal);
  }

  if (asistenteId && asistenteId !== principalId) {
    const asistente = (props.especialistas_lista || []).find((e) => Number(e.id) === asistenteId);
    if (asistente) opciones.push(asistente);
  }

  return opciones.length ? opciones : (props.especialistas_lista || []);
};

const getEspecialistaActual = (servicio) => {
  const especialistaId = Number(formTurno.servicio_especialistas?.[servicio.id] ?? formTurno.especialista_id ?? getServicioEspecialistaDefault(servicio) ?? 0);
  return (props.especialistas_lista || []).find((item) => Number(item.id) === especialistaId) || null;
};

const getEspecialistaComision = (servicio) => {
  return Number(getEspecialistaActual(servicio)?.comision ?? 0);
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
  const especialistaId = Number(formTurno.especialista_id || 0);

  if (!especialistaId) {
    return props.servicios_lista;
  }

  return props.servicios_lista.filter((servicio) => {
    if (!Array.isArray(servicio.especialistas) || servicio.especialistas.length === 0) {
      return false;
    }

    return servicio.especialistas.some((especialista) => Number(especialista.id) === especialistaId);
  });
});

const asistentesDisponibles = computed(() => props.especialistas_lista.filter((especialista) => {
  return Number(especialista.id) !== Number(formTurno.especialista_id || 0);
}));

const serviciosState = ref([]);

watch(
  () => formTurno.especialista_id,
  () => {
    serviciosState.value = serviciosDisponibles.value.map((servicio) => ({
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
    const comisionPct = Number(formTurno.servicio_comisiones?.[s.id] ?? getEspecialistaComision(s) ?? 0);
    const precioUsd = getServicioPrecioBase(s) * (s.es_recurrente === true ? 1 : sesionesSeleccionadas.value);
    const gananciaUsd = precioUsd * (comisionPct / 100);

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
      formTurno.fecha = props.filters?.fecha || new Date().toISOString().substr(0, 10);
      formTurno.recurrencia = 'mensual';
      formTurno.dias_semana = [];
      formTurno.cantidad_sesiones = 3;
      formTurno.precios_servicios = {};
      serviciosState.value.forEach(s => s.selected = false);
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