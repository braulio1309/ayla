<template>
  <div class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-ayla sticky-top py-2">
      <div class="container-fluid px-4">
        <Link class="navbar-brand d-flex align-items-center" href="/dashboard">
          <div class="bg-ayla-cream rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
            <i class="bi bi-flower2 text-ayla-dark fs-4"></i>
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
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Dashboard' }" href="/dashboard"><i class="bi bi-grid-1x2-fill me-1"></i> Dashboard</Link></li>
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Agenda' }" href="/agenda"><i class="bi bi-calendar3 me-1"></i> Agenda & Turnos</Link></li>
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Pacientes' }" href="/pacientes"><i class="bi bi-people-fill me-1"></i> Pacientes</Link></li>
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Servicios' }" href="/servicios"><i class="bi bi-flower1 me-1"></i> Servicios</Link></li>
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Usuarios' }" href="/usuarios"><i class="bi bi-shield-lock-fill me-1"></i> Usuarios & Roles</Link></li>
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'PanelEspecialista' }" href="/panel-especialista"><i class="bi bi-person-badge-fill me-1"></i> Mi Panel</Link></li>
            <li class="nav-item"><Link class="nav-link" :class="{ active: $page.component === 'Reportes' }" href="/reportes"><i class="bi bi-graph-up-arrow me-1"></i> Finanzas</Link></li>
          </ul>
          <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-xl-block">
              <div class="fw-bold text-ayla-dark small">Braulio Zapata</div>
              <span class="badge bg-ayla-dark fw-light" style="font-size: 0.7rem;">Administrador</span>
            </div>
            <Link href="/login" method="post" as="button" class="btn btn-outline-danger btn-sm rounded-pill px-3">
              <i class="bi bi-box-arrow-right me-1"></i> Salir
            </Link>
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
import { Link } from '@inertiajs/vue3';
</script>