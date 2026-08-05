<template>
  <MainLayout>
    <div class="container-fluid px-4">
      <div class="card-ayla p-4 mb-4 bg-ayla-cream text-ayla-dark">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h2 class="brand-font fw-bold mb-1">Panel de Control Administrativo</h2>
            <p class="mb-0">Resumen operativo general de turnos, ingresos y atención a pacientes.</p>
          </div>
          <div class="d-flex gap-2">
            <Link href="/agenda" class="btn btn-ayla-primary"><i class="bi bi-plus-circle me-1"></i> Nuevo Turno</Link>
            <Link href="/pacientes" class="btn btn-ayla-secondary"><i class="bi bi-person-plus me-1"></i> Registrar Paciente</Link>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card-ayla p-3">
            <span class="text-muted small">Ingresos del Mes</span>
            <h3 class="fw-bold text-ayla-dark mb-0">${{ kpis.ingresos_mes.toFixed(2) }}</h3>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-ayla p-3">
            <span class="text-muted small">Turnos Hoy</span>
            <h3 class="fw-bold text-ayla-dark mb-0">{{ kpis.turnos_hoy }} Citas</h3>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-ayla p-3">
            <span class="text-muted small">Pacientes Registrados</span>
            <h3 class="fw-bold text-ayla-dark mb-0">{{ kpis.pacientes_totales }}</h3>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card-ayla p-3">
            <span class="text-muted small">Especialistas Activos</span>
            <h3 class="fw-bold text-ayla-dark mb-0">{{ kpis.especialistas_activos }} Profesionales</h3>
          </div>
        </div>
      </div>

      <div class="card-ayla p-4">
        <h5 class="brand-font fw-bold text-ayla-dark mb-3">Turnos Programados para Hoy</h5>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr><th>Hora</th><th>Paciente</th><th>Servicio</th><th>Especialista</th><th>Monto</th><th>Estado</th></tr>
            </thead>
            <tbody>
              <tr v-for="(c, i) in citas_hoy" :key="i">
                <td><strong>{{ c.hora }}</strong></td>
                <td>{{ c.paciente }}</td>
                <td>{{ c.servicio }}</td>
                <td>{{ c.especialista }}</td>
                <td><strong>${{ c.monto.toFixed(2) }}</strong></td>
                <td><span class="badge bg-success">{{ c.estado }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../Layouts/MainLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  kpis: Object,
  citas_hoy: Array
});
</script>