<nav class="bg-white border-bottom border-secondary">
    <!-- Primary Navigation Menu -->
    <div class="container px-4">
        <div class="d-flex justify-content-between align-items-center h-100">
            <div class="d-flex">
                <!-- Logo -->
                <div class="d-flex align-items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="h-9 w-auto text-dark" />
                    </a>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="d-none d-sm-flex align-items-center ms-3">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ Auth::user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">Mi Perfil</a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Cerrar Sesión</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="d-sm-none">
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-expanded="false" aria-controls="navbarResponsive">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div class="collapse" id="navbarResponsive">
        <div class="py-2">
            <ul class="list-unstyled">
                <li>
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Inicio</a>
                </li>
                <li>
                    <a href="{{ route('productos.index') }}" class="nav-link {{ request()->routeIs('productos.index') ? 'active' : '' }}">Materia Prima</a>
                </li>
                <li>
                    <a href="{{ route('ubicaciones.index') }}" class="nav-link {{ request()->routeIs('ubicaciones.index') ? 'active' : '' }}">Ubicaciones</a>
                </li>
                <li>
                    <a href="{{ route('entradas.index') }}" class="nav-link {{ request()->routeIs('entradas.index') ? 'active' : '' }}">Entradas</a>
                </li>
                <li>
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">Salidas</a>
                </li>
                <li>
                    <a href="{{ route('movimientos.index') }}" class="nav-link {{ request()->routeIs('movimientos.index') ? 'active' : '' }}">Movimientos</a>
                </li>
                <li>
                    <a href="{{ route('maquinas.index') }}" class="nav-link {{ request()->routeIs('maquinas.index') ? 'active' : '' }}">Máquinas</a>
                </li>
                <li>
                    <a href="{{ route('planillas.index') }}" class="nav-link {{ request()->routeIs('planillas.index') ? 'active' : '' }}">Planillas</a>
                </li>
            </ul>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-top border-secondary">
            <div class="px-4">
                <div class="fw-medium text-dark">{{ Auth::user()->name }}</div>
                <div class="fw-medium text-muted">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3">
                <a href="{{ route('profile.edit') }}" class="nav-link text-decoration-none">
                    Mi Perfil
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="nav-link text-decoration-none">Cerrar Sesión</button>
                </form>
            </div>
        </div>
    </div>
</nav>
