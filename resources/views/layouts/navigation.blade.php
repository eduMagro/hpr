<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <x-application-logo class="h-9 w-auto text-dark me-2" />
            <span class="fw-bold text-primary">Mi Aplicación</span>
        </a>

    <!-- User Dropdown -->
<div class="d-none d-sm-flex align-items-center ms-3">
    <div class="dropdown">
        <button class="btn btn-light dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li>
                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                    <i class="bi bi-gear me-2"></i>Mi Perfil
                </a>
            </li>
            <li>
                <form method="POST" action="{{ route('logout') }}" class="mb-0">
                    @csrf
                    <button type="submit" class="dropdown-item">
                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                    </button>
                </form>
            </li>
        </ul>
    </div>
</div>

<!-- Hamburger Menu for Mobile -->
<div class="d-sm-none">
    <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-expanded="false" aria-controls="navbarResponsive">
        <i class="bi bi-list"></i>
    </button>
</div>

<!-- Responsive Navigation Menu -->
<div class="collapse" id="navbarResponsive">
    <div class="py-2">
        <ul class="list-unstyled">
            <li>
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Inicio</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('productos.index') ? 'active' : '' }}" href="{{ route('productos.index') }}">Materia Prima</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('ubicaciones.index') ? 'active' : '' }}" href="{{ route('ubicaciones.index') }}">Ubicaciones</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('entradas.index') ? 'active' : '' }}" href="{{ route('entradas.index') }}">Entradas</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}" href="{{ route('users.index') }}">Salidas</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('movimientos.index') ? 'active' : '' }}" href="{{ route('movimientos.index') }}">Movimientos</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('maquinas.index') ? 'active' : '' }}" href="{{ route('maquinas.index') }}">Máquinas</a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('planillas.index') ? 'active' : '' }}" href="{{ route('planillas.index') }}">Planillas</a>
            </li>
        </ul>
    </div>

    <!-- Responsive User Options -->
    <div class="border-top pt-3">
        <div class="fw-bold">{{ Auth::user()->name }}</div>
        <div class="text-muted mb-2">{{ Auth::user()->email }}</div>
        <a class="btn btn-link text-decoration-none" href="{{ route('profile.edit') }}">
            <i class="bi bi-gear me-1"></i>Mi Perfil
        </a>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button class="btn btn-link text-decoration-none">
                <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
            </button>
        </form>
    </div>
</div>

