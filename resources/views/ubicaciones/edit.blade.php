<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Ubicación') }} wire:navigate
        </h2>
    </x-slot>
    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Mostrar mensajes de éxito o error -->
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }} wire:navigate
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }} wire:navigate
        </div>
    @endif

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="text-uppercase">Editar Ubicación</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('ubicaciones.update', $ubicacion->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                        
                            <!-- Código de la máquina -->
                            <div class="form-group mb-4">
                                <label for="codigo" class="form-label fw-bold text-uppercase">Código de la Ubicación</label>
                                <input type="text" id="codigo" name="codigo" class="form-control form-control-lg"
                                    value="{{ old('codigo', $ubicacion->codigo) }}" placeholder="Introduce el código de la ubicación" required>
                            </div>
                        
                            <!-- descripcion de la máquina -->
                            <div class="form-group mb-4">
                                <label for="descripcion" class="form-label fw-bold text-uppercase">Descripción de la Ubicación</label>
                                <input type="text" id="descripcion" name="descripcion" class="form-control form-control-lg"
                                    value="{{ old('descripcion', $ubicacion->descripcion) }}" placeholder="Introduce la descripción de la ubicación" required>
                            </div>
                        
                            <!-- Botón para actualizar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Actualizar Ubicación</button>
                            </div>
                        </form>
                        
                    </div>
                    <div class="card-footer text-center text-muted">
                        <small>Todos los campos con * son obligatorios.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
