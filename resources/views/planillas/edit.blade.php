<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Planilla') }}
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
                <div class="card shadow-lg border-0 mb-4">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="text-uppercase">Editar Planilla</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('planillas.update', $planilla->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                        
                            <!-- Código de la obra -->
                            <div class="form-group mb-4">
                                <label for="cod_obra" class="form-label fw-bold text-uppercase">Código de Obra</label>
                                <input type="text" id="cod_obra" name="cod_obra" class="form-control form-control-lg"
                                    value="{{ old('cod_obra', $planilla->cod_obra) }}" placeholder="Introduce el código de la obra" required>
                            </div>
                        
                            <!-- Cliente -->
                            <div class="form-group mb-4">
                                <label for="cliente" class="form-label fw-bold text-uppercase">Cliente</label>
                                <input type="text" id="cliente" name="cliente" class="form-control form-control-lg"
                                    value="{{ old('cliente', $planilla->cliente) }}" placeholder="Introduce el cliente" required>
                            </div>
                        
                            <!-- Nombre de la obra -->
                            <div class="form-group mb-4">
                                <label for="nom_obra" class="form-label fw-bold text-uppercase">Nombre Obra</label>
                                <input type="text" id="nom_obra" name="nom_obra" class="form-control form-control-lg"
                                    value="{{ old('nom_obra', $planilla->nom_obra) }}" placeholder="Introduce el nombre de obra" required>
                            </div>                        
                            <!-- Sección -->
                            <div class="form-group mb-4">
                                <label for="seccion" class="form-label fw-bold text-uppercase">Sección</label>
                                <input type="text" id="seccion" name="seccion" class="form-control form-control-lg"
                                    value="{{ old('seccion', $planilla->seccion) }}" placeholder="Introduce la sección" required>
                            </div>
                            <!-- Descripción -->
                            <div class="form-group mb-4">
                                <label for="descripcion" class="form-label fw-bold text-uppercase">Descripción</label>
                                <input type="text" id="descripcion" name="descripcion" class="form-control form-control-lg"
                                    value="{{ old('descripcion', $planilla->descripcion) }}" placeholder="Introduce una descripción">
                            </div>                       
                            <!-- Población -->
                            <div class="form-group mb-4">
                                <label for="poblacion" class="form-label fw-bold text-uppercase">Población</label>
                                <input type="text" id="poblacion" name="poblacion" class="form-control form-control-lg"
                                    value="{{ old('poblacion', $planilla->poblacion) }}" placeholder="Introduce la población">
                            </div>
                            <!-- Código -->
                            <div class="form-group mb-4">
                                <label for="codigo" class="form-label fw-bold text-uppercase">Código Planilla</label>
                                <input type="text" id="codigo" name="codigo" class="form-control form-control-lg"
                                    value="{{ old('codigo', $planilla->codigo) }}" placeholder="Introduce el código de Planilla">
                            </div>
                            <!-- Peso total -->
                            {{-- <div class="form-group mb-4">
                                <label for="peso_total" class="form-label fw-bold text-uppercase">Peso Total</label>
                                <input type="number" id="peso_total" name="peso_total" class="form-control form-control-lg"
                                    value="{{ old('peso_total', $planilla->peso_total) }}" placeholder="Introduce el peso total">
                            </div>
                         --}}
                            <!-- Botón para actualizar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Actualizar Planilla</button>
                            </div>
                        </form>
                        
                    </div>

                </div>
            </div>
        </div>
    </div>
       
</x-app-layout>