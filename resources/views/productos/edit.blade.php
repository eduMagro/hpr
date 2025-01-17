<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Detalles Materia Prima') }}
        </h2>
    </x-slot>

    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger mt-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Mostrar mensajes de éxito o error -->
    @if (session('error'))
        <div class="alert alert-danger mt-4">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success mt-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-center">
            <div class="w-full max-w-2xl">
                <div class="bg-white shadow-lg rounded-lg border-0">
                    <div class="bg-primary text-white text-center py-3 rounded-t-lg">
                        <h2 class="text-uppercase text-lg font-bold">Editar Materia Prima</h2>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('productos.update', $producto->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Fabricante -->
                            <div class="form-group mb-4">
                                <label for="fabricante" class="form-label fw-bold text-uppercase">Fabricante</label>
                                <select id="fabricante" name="fabricante" class="form-control form-control-lg" required>
                                    <option value="" disabled
                                        {{ old('fabricante', $producto->fabricante) == null ? 'selected' : '' }}>
                                        Seleccione un fabricante</option>
                                    <option value="MEGASA"
                                        {{ old('fabricante', $producto->fabricante) == 'MEGASA' ? 'selected' : '' }}>
                                        MEGASA</option>
                                    <option value="Getafe"
                                        {{ old('fabricante', $producto->fabricante) == 'Getafe' ? 'selected' : '' }}>
                                        Getafe</option>
                                    <option value="Siderúrgica Sevillana"
                                        {{ old('fabricante', $producto->fabricante) == 'Siderúrgica Sevillana' ? 'selected' : '' }}>
                                        Siderúrgica Sevillana</option>
                                    <option value="NERVADUCTIL"
                                        {{ old('fabricante', $producto->fabricante) == 'NERVADUCTIL' ? 'selected' : '' }}>
                                        NERVADUCTIL</option>
                                </select>
                            </div>

                            <!-- Nombre -->
                            <div class="form-group mb-4">
                                <label for="nombre" class="form-label fw-bold text-uppercase">Nombre del
                                    Producto</label>
                                <input type="text" id="nombre" name="nombre" class="form-control form-control-lg"
                                    value="{{ old('nombre', $producto->nombre) }}" maxlength="255" disabled>
                            </div>

                            <!-- Tipo -->
                            <div class="form-group mb-4">
                                <label for="tipo" class="form-label fw-bold text-uppercase">Tipo</label>
                                <select id="tipo" name="tipo" class="form-control form-control-lg">
                                    <option value="" disabled
                                        {{ old('tipo', $producto->tipo) == null ? 'selected' : '' }}>Seleccione un tipo
                                    </option>
                                    <option value="Encarretado"
                                        {{ old('tipo', $producto->tipo) == 'encarretado' ? 'selected' : '' }}>
                                        Encarretado</option>
                                    <option value="Barras"
                                        {{ old('tipo', $producto->tipo) == 'barras' ? 'selected' : '' }}>Barras
                                    </option>
                                </select>
                            </div>

                            <!-- Diámetro -->
                            <div class="form-group mb-4">
                                <label for="diametro" class="form-label fw-bold text-uppercase">Diámetro</label>
                                <select id="diametro" name="diametro" class="form-control form-control-lg" required>
                                    <option value="" disabled
                                        {{ old('diametro', $producto->diametro) == null ? 'selected' : '' }}>Seleccione
                                        un diámetro</option>
                                    <option value="8"
                                        {{ old('diametro', $producto->diametro) == '8' ? 'selected' : '' }}>8</option>
                                    <option value="10"
                                        {{ old('diametro', $producto->diametro) == '10' ? 'selected' : '' }}>10
                                    </option>
                                    <option value="12"
                                        {{ old('diametro', $producto->diametro) == '12' ? 'selected' : '' }}>12
                                    </option>
                                    <option value="16"
                                        {{ old('diametro', $producto->diametro) == '16' ? 'selected' : '' }}>16
                                    </option>
                                    <option value="20"
                                        {{ old('diametro', $producto->diametro) == '20' ? 'selected' : '' }}>20
                                    </option>
                                    <option value="25"
                                        {{ old('diametro', $producto->diametro) == '25' ? 'selected' : '' }}>25
                                    </option>
                                    <option value="32"
                                        {{ old('diametro', $producto->diametro) == '32' ? 'selected' : '' }}>32
                                    </option>
                                </select>
                            </div>


                            <!-- Longitud -->
                            <div class="form-group mb-4">
                                <label for="longitud" class="form-label fw-bold text-uppercase">Longitud</label>
                                <select id="longitud" name="longitud" class="form-control form-control-lg">
                                    <option value="" disabled
                                        {{ old('longitud', $producto->longitud) == null ? 'selected' : '' }}>Seleccione
                                        una longitud</option>
                                    <option value="6"
                                        {{ old('longitud', $producto->longitud) == '6' ? 'selected' : '' }}>6</option>
                                    <option value="12"
                                        {{ old('longitud', $producto->longitud) == '12' ? 'selected' : '' }}>12
                                    </option>
                                    <option value="14"
                                        {{ old('longitud', $producto->longitud) == '14' ? 'selected' : '' }}>14
                                    </option>
                                    <option value="15"
                                        {{ old('longitud', $producto->longitud) == '15' ? 'selected' : '' }}>15
                                    </option>
                                    <option value="16"
                                        {{ old('longitud', $producto->longitud) == '16' ? 'selected' : '' }}>16
                                    </option>
                                </select>
                            </div>


                            <!-- Nº Colada (Opcional) -->
                            <div class="form-group mb-4">
                                <label for="n_colada" class="form-label fw-bold text-uppercase">Nº Colada</label>
                                <input type="text" id="n_colada" name="n_colada"
                                    class="form-control form-control-lg"
                                    value="{{ old('n_colada', $producto->n_colada) }}" maxlength="255"
                                    placeholder="(Opcional)">
                            </div>

                            <!-- Nº Paquete (Opcional) -->
                            <div class="form-group mb-4">
                                <label for="n_paquete" class="form-label fw-bold text-uppercase">Nº Paquete</label>
                                <input type="text" id="n_paquete" name="n_paquete"
                                    class="form-control form-control-lg"
                                    value="{{ old('n_paquete', $producto->n_paquete) }}" maxlength="255"
                                    placeholder="(Opcional)">
                            </div>

                            <!-- Peso Inicial -->
                            <div class="form-group mb-4">
                                <label for="peso_inicial" class="form-label fw-bold text-uppercase">Peso Inicial
                                    (kg)</label>
                                <input type="number" step="0.01" id="peso_inicial" name="peso_inicial"
                                    class="form-control form-control-lg"
                                    value="{{ old('peso_inicial', $producto->peso_inicial) }}" required>
                            </div>

                            <!-- Peso Stock -->
                            <div class="form-group mb-4">
                                <label for="peso_stock" class="form-label fw-bold text-uppercase">Peso Stock
                                    (kg)</label>
                                <input type="number" step="0.01" id="peso_stock" name="peso_stock"
                                    class="form-control form-control-lg"
                                    value="{{ old('peso_stock', $producto->peso_stock) }}" required>
                            </div>

                            <!-- Ubicación (Opcional) -->
                            <div class="form-group mb-4">
                                <label for="ubicacion_id" class="form-label fw-bold text-uppercase">Ubicación</label>
                                <input type="number" id="ubicacion_id" name="ubicacion_id"
                                    class="form-control form-control-lg"
                                    value="{{ old('ubicacion_id', $producto->ubicacion_id) }}"
                                    placeholder="(Opcional)">
                            </div>

                            <!-- Máquina (Opcional) -->
                            <div class="form-group mb-4">
                                <label for="maquina_id" class="form-label fw-bold text-uppercase">Máquina</label>
                                <input type="number" id="maquina_id" name="maquina_id"
                                    class="form-control form-control-lg"
                                    value="{{ old('maquina_id', $producto->maquina_id) }}" placeholder="(Opcional)">
                            </div>

                            <!-- Estado -->
                            <div class="form-group mb-4">
                                <label for="estado" class="form-label fw-bold text-uppercase">Estado</label>
                                <input type="text" id="estado" name="estado"
                                    class="form-control form-control-lg"
                                    value="{{ old('estado', $producto->estado) }}" maxlength="50"
                                    placeholder="Estado" disabled>
                            </div>

                            <!-- Otros (Opcional) -->
                            <div class="form-group mb-4">
                                <label for="otros" class="form-label fw-bold text-uppercase">Otros</label>
                                <textarea id="otros" name="otros" class="form-control form-control-lg" rows="3"
                                    placeholder="(Opcional)">{{ old('otros', $producto->otros) }}</textarea>
                            </div>

                            <!-- Botón actualizar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Actualizar Producto</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
