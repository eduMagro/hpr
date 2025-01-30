<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Planilla') }}
        </h2>
    </x-slot>

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

                            @foreach (['cod_obra' => 'Código de Obra', 'cliente' => 'Cliente', 'nom_obra' => 'Nombre de la Obra', 'seccion' => 'Sección', 'descripcion' => 'Descripción', 'codigo' => 'Código Planilla', 'ensamblado' => 'Ensamblado'] as $field => $label)
                                <div class="form-group mb-4">
                                    <label for="{{ $field }}" class="form-label fw-bold text-uppercase">{{ $label }}</label>
                                    <input type="text" id="{{ $field }}" name="{{ $field }}" class="form-control form-control-lg" value="{{ old($field, $planilla->$field) }}" placeholder="Introduce {{ strtolower($label) }}" required>
                                    @error($field)
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach

                            <!-- Peso total -->
                            <div class="form-group mb-4">
                                <label for="peso_total" class="form-label fw-bold text-uppercase">Peso Total (kg)</label>
                                <input type="number" step="0.01" id="peso_total" name="peso_total" class="form-control form-control-lg" value="{{ old('peso_total', $planilla->peso_total) }}" placeholder="Introduce el peso total">
                                @error('peso_total')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                          <!-- Fecha de Inicio -->
<div class="form-group mb-4">
    <label for="fecha_inicio" class="form-label fw-bold text-uppercase">Fecha de Inicio</label>
    <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="form-control form-control-lg"
        value="{{ old('fecha_inicio', $planilla->fecha_inicio ? $planilla->fecha_inicio->format('Y-m-d\TH:i') : '') }}">
    @error('fecha_inicio')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<!-- Fecha de Finalización -->
<div class="form-group mb-4">
    <label for="fecha_finalizacion" class="form-label fw-bold text-uppercase">Fecha de Finalización</label>
    <input type="datetime-local" id="fecha_finalizacion" name="fecha_finalizacion" class="form-control form-control-lg"
        value="{{ old('fecha_finalizacion', $planilla->fecha_finalizacion ? $planilla->fecha_finalizacion->format('Y-m-d\TH:i') : '') }}">
    @error('fecha_finalizacion')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<!-- Tiempo de Fabricación -->
<div class="form-group mb-4">
    <label for="tiempo_fabricacion" class="form-label fw-bold text-uppercase">Tiempo de Fabricación (segundos)</label>
    <input type="number" id="tiempo_fabricacion" name="tiempo_fabricacion" class="form-control form-control-lg"
        value="{{ old('tiempo_fabricacion', $planilla->tiempo_fabricacion) }}">
    @error('tiempo_fabricacion')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>


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
