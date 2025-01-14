<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <x-application-logo class="h-9 w-auto text-dark me-2" />
            <span class="fw-bold text-primary">Mi Aplicación</span>
        </a>

        <!-- Toggle Button for Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('productos.index') ? 'active' : '' }}" href="{{ route('productos.index') }}">Materia Prima</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('ubicaciones.index') ? 'active' : '' }}" href="{{ route('ubicaciones.index') }}">Ubicaciones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('entradas.index') ? 'active' : '' }}" href="{{ route('entradas.index') }}">Entradas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}" href="{{ route('users.index') }}">Salidas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('movimientos.index') ? 'active' : '' }}" href="{{ route('movimientos.index') }}">Movimientos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('maquinas.index') ? 'active' : '' }}" href="{{ route('maquinas.index') }}">Máquinas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('planillas.index') ? 'active' : '' }}" href="{{ route('planillas.index') }}">Planillas</a>
                </li>
            </ul>

            <!-- User Dropdown -->
            <div class="dropdown ms-3">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="bi bi-gear me-2"></i>Mi Perfil
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Responsive Menu for Mobile -->
<div class="d-lg-none border-top bg-light">
    <div class="container py-2">
        <div class="fw-bold text-dark">{{ Auth::user()->name }}</div>
        <div class="text-muted mb-3">{{ Auth::user()->email }}</div>
        <a href="{{ route('profile.edit') }}" class="btn btn-link btn-sm text-decoration-none">
            <i class="bi bi-gear me-1"></i>Mi Perfil
        </a>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link btn-sm text-decoration-none">
                <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
            </button>
        </form>
    </div>
</div>
