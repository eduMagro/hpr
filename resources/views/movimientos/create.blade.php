<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Movimientos') }}
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
                        <h2>Crear Movimiento de Producto</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('movimientos.store') }}" method="POST">
                            @csrf
    
                            <!-- Seleccionar Producto -->
                            <div class="form-group mb-4">
                                <label for="producto_id" class="form-label fw-bold">Producto</label>

                                {{-- BUSCAR POR QR --}}
                                <input type="text" name="producto_id" class="form-control mb-3" placeholder="Buscar por QR"
                                value="{{ request('producto_id') }}">

                                {{-- BUSCAR POR SELECT --}}
                                {{-- <select id="producto_id" name="producto_id" class="form-control form-control-lg" required>
                                    <option value="">Seleccione un producto</option>
                                    @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}">
                                        {{ $producto->qr }} - 
                                        (Origen: 
                                        @if ($producto->ubicacion)
                                            {{ $producto->ubicacion->descripcion }}
                                        @elseif ($producto->maquina)
                                            Máquina: {{ $producto->maquina->nombre }}
                                        @else
                                            Sin origen
                                        @endif)
                                    </option>
                                @endforeach
                                </select> --}}
                            </div>
    
                            <!-- Movimiento de una ubicación a otra -->
                            <div class="form-group mb-4">
                                <label for="ubicacion_destino" class="form-label fw-bold">Ubicación Destino</label>
                                <select id="ubicacion_destino" name="ubicacion_destino" class="form-control form-control-lg">
                                    <option value="">Seleccione una nueva ubicación</option>
                                    @foreach ($ubicaciones as $ubicacion)
                                        <option value="{{ $ubicacion->id }}">{{ $ubicacion->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
    
                            <!-- Movimiento hacia una máquina -->
                            <div class="form-group mb-4">
                                <label for="maquina_id" class="form-label fw-bold">Máquina Destino</label>
                                <select id="maquina_id" name="maquina_id" class="form-control form-control-lg">
                                    <option value="">Seleccione una máquina</option>
                                    @foreach ($maquinas as $maquina)
                                        <option value="{{ $maquina->id }}">{{ $maquina->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
    
                            <!-- Botón para enviar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">Registrar Movimiento</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center text-muted">
                        <small>El producto puede moverse a otra ubicación o a una máquina, pero no ambos.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
</x-app-layout>
