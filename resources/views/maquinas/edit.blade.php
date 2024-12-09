<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Máquina') }}
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
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="text-uppercase">Editar Máquina</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('maquinas.update', $maquina->id) }}" method="POST">
                            @csrf
                            @method('PUT')
    
                            <!-- Código de la máquina -->
                            <div class="form-group mb-4">
                                <label for="codigo" class="form-label fw-bold text-uppercase">Código de la Máquina</label>
                                <input type="text" id="codigo" name="codigo" class="form-control form-control-lg"
                                    value="{{ old('codigo', $maquina->codigo) }}" placeholder="Introduce el código de la máquina" required>
                            </div>
    
                            <!-- Nombre de la máquina -->
                            <div class="form-group mb-4">
                                <label for="nombre" class="form-label fw-bold text-uppercase">Nombre de la Máquina</label>
                                <input type="text" id="nombre" name="nombre" class="form-control form-control-lg"
                                    value="{{ old('nombre', $maquina->nombre) }}" placeholder="Introduce el nombre de la máquina" required>
                            </div>
    
                            <!-- Botón para actualizar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Actualizar Máquina</button>
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
