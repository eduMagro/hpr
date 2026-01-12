<x-app-layout>
    <x-slot name="title">Crear Obra - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Nueva Obra') }}
        </h2>
    </x-slot>

    <div class="container mt-5">
        <div class="row justify-content-center mb-4">
            <div class="col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="text-uppercase">Registrar Obra</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('obras.store') }}" method="POST">
                            @csrf



                            <!-- Nombre de la Obra -->
                            <div class="form-group mb-4">
                                <label for="obra" class="form-label fw-bold text-uppercase">Nombre de la Obra
                                    *</label>
                                <input type="text" id="obra" name="obra" class="form-control form-control-lg"
                                    placeholder="Introduce el nombre de la obra" value="{{ old('obra') }}" required>
                            </div>
                            <!-- Código de Obra -->
                            <div class="form-group mb-4">
                                <label for="cod_obra" class="form-label fw-bold text-uppercase">Código de Obra *
                                </label>
                                <input type="text" id="cod_obra" name="cod_obra"
                                    class="form-control form-control-lg" placeholder="Ej: 12345 (ID externo)"
                                    value="{{ old('cod_obra') }}" required>
                                <small class="form-text text-muted mt-1">Identificador único (ID Ferrawin) referente a
                                    la obra.</small>
                            </div>

                            <!-- Cliente (Select) -->
                            <div class="form-group mb-4">
                                <label for="cliente_id" class="form-label fw-bold text-uppercase">Cliente *</label>
                                <select id="cliente_id" name="cliente_id" class="form-control form-control-lg" required>
                                    <option value="">-- Selecciona un Cliente --</option>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->id }}"
                                            {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                            {{ $cliente->empresa }} ({{ $cliente->codigo }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Latitud -->
                            <div class="form-group mb-4">
                                <label for="latitud" class="form-label fw-bold text-uppercase">Latitud</label>
                                <input type="text" id="latitud" name="latitud" class="form-control form-control-lg"
                                    placeholder="Introduce la latitud" value="{{ old('latitud') }}">
                            </div>

                            <!-- Longitud -->
                            <div class="form-group mb-4">
                                <label for="longitud" class="form-label fw-bold text-uppercase">Longitud</label>
                                <input type="text" id="longitud" name="longitud"
                                    class="form-control form-control-lg" placeholder="Introduce la longitud"
                                    value="{{ old('longitud') }}">
                            </div>

                            <!-- Distancia -->
                            <div class="form-group mb-4">
                                <label for="distancia" class="form-label fw-bold text-uppercase">Radio (en
                                    metros)</label>
                                <input type="number" id="distancia" name="distancia"
                                    class="form-control form-control-lg" placeholder="Introduce la distancia permitida"
                                    value="{{ old('distancia') }}">
                            </div>

                            <!-- Tipo de Obra -->
                            <div class="form-group mb-4">
                                <label for="tipo" class="form-label fw-bold text-uppercase">Tipo de Obra *</label>
                                <select id="tipo" name="tipo" class="form-control form-control-lg" required>
                                    <option value="">-- Selecciona el Tipo --</option>
                                    <option value="montaje" {{ old('tipo') == 'montaje' ? 'selected' : '' }}>Montaje
                                    </option>
                                    <option value="suministro" {{ old('tipo') == 'suministro' ? 'selected' : '' }}>
                                        Suministro</option>
                                </select>
                                <small class="form-text text-muted mt-1">Seleccione <strong>Montaje</strong> para que
                                    aparezca en la planificación de trabajadores.</small>
                            </div>

                            <!-- Presupuesto Estimado -->
                            <div class="form-group mb-4">
                                <label for="presupuesto_estimado" class="form-label fw-bold text-uppercase">Presupuesto
                                    Estimado (€)</label>
                                <input type="number" step="0.01" id="presupuesto_estimado"
                                    name="presupuesto_estimado" class="form-control form-control-lg"
                                    placeholder="Introduce el presupuesto inicial"
                                    value="{{ old('presupuesto_estimado') }}">
                            </div>


                            <!-- Botones de Acción -->
                            <div class="d-flex justify-content-between">
                                <!-- Botón Cancelar -->
                                <a href="{{ route('obras.index') }}" class="btn btn-danger btn-lg">Cancelar</a>

                                <!-- Botón Guardar -->
                                <button type="submit" class="btn btn-success btn-lg">Registrar Obra</button>
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
