<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Máquina') }}
        </h2>
    </x-slot>
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
                                <label for="codigo" class="form-label fw-bold text-uppercase">Código de la
                                    Máquina</label>
                                <input type="text" id="codigo" name="codigo" class="form-control form-control-lg"
                                    value="{{ old('codigo', $maquina->codigo) }}"
                                    placeholder="Introduce el código de la máquina" required>
                            </div>

                            <!-- Nombre de la máquina -->
                            <div class="form-group mb-4">
                                <label for="nombre" class="form-label fw-bold text-uppercase">Nombre de la
                                    Máquina</label>
                                <input type="text" id="nombre" name="nombre" class="form-control form-control-lg"
                                    value="{{ old('nombre', $maquina->nombre) }}"
                                    placeholder="Introduce el nombre de la máquina" required>
                            </div>

                            <!-- Diámetro mínimo -->
                            <div class="form-group mb-4">
                                <label for="diametro_min" class="form-label fw-bold text-uppercase">Diámetro
                                    Mínimo</label>
                                <input type="number" id="diametro_min" name="diametro_min"
                                    class="form-control form-control-lg"
                                    value="{{ old('diametro_min', $maquina->diametro_min) }}"
                                    placeholder="Introduce el diámetro mínimo">
                            </div>

                            <!-- Diámetro máximo -->
                            <div class="form-group mb-4">
                                <label for="diametro_max" class="form-label fw-bold text-uppercase">Diámetro
                                    Máximo</label>
                                <input type="number" id="diametro_max" name="diametro_max"
                                    class="form-control form-control-lg"
                                    value="{{ old('diametro_max', $maquina->diametro_max) }}"
                                    placeholder="Introduce el diámetro máximo">
                            </div>

                            <!-- Peso mínimo -->
                            <div class="form-group mb-4">
                                <label for="peso_min" class="form-label fw-bold text-uppercase">Peso Mínimo</label>
                                <input type="number" id="peso_min" name="peso_min"
                                    class="form-control form-control-lg"
                                    value="{{ old('peso_min', $maquina->peso_min) }}"
                                    placeholder="Introduce el peso mínimo (opcional)">
                            </div>

                            <!-- Peso máximo -->
                            <div class="form-group mb-4">
                                <label for="peso_max" class="form-label fw-bold text-uppercase">Peso Máximo</label>
                                <input type="number" id="peso_max" name="peso_max"
                                    class="form-control form-control-lg"
                                    value="{{ old('peso_max', $maquina->peso_max) }}"
                                    placeholder="Introduce el peso máximo (opcional)">
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
