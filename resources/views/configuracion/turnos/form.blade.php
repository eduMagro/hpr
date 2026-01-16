<!-- Formulario compartido para crear/editar turnos -->
<div class="space-y-6">
    <!-- Nombre del turno -->
    <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre del turno</label>
        <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $turno->nombre ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            required maxlength="50" placeholder="ej: Mañana, Tarde, Noche">
        @error('nombre')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Horarios -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Hora inicio -->
        <div>
            <label for="hora_inicio" class="block text-sm font-medium text-gray-700">Hora de inicio</label>
            <input type="time" name="hora_inicio" id="hora_inicio"
                value="{{ old('hora_inicio', isset($turno) ? substr($turno->hora_inicio, 0, 5) : '') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required>
            @error('hora_inicio')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Hora fin -->
        <div>
            <label for="hora_fin" class="block text-sm font-medium text-gray-700">Hora de fin</label>
            <input type="time" name="hora_fin" id="hora_fin"
                value="{{ old('hora_fin', isset($turno) ? substr($turno->hora_fin, 0, 5) : '') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required>
            @error('hora_fin')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Offset de días -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Offset de días (para turnos que cruzan medianoche)</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Offset inicio -->
            <div>
                <label for="offset_dias_inicio" class="block text-sm font-medium text-gray-700">
                    Offset día de inicio
                </label>
                <select name="offset_dias_inicio" id="offset_dias_inicio"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="-1" {{ old('offset_dias_inicio', $turno->offset_dias_inicio ?? 0) == -1 ? 'selected' : '' }}>
                        Día anterior (-1)
                    </option>
                    <option value="0" {{ old('offset_dias_inicio', $turno->offset_dias_inicio ?? 0) == 0 ? 'selected' : '' }}>
                        Mismo día (0)
                    </option>
                    <option value="1" {{ old('offset_dias_inicio', $turno->offset_dias_inicio ?? 0) == 1 ? 'selected' : '' }}>
                        Día siguiente (+1)
                    </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Normalmente: Mismo día (0)</p>
                @error('offset_dias_inicio')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Offset fin -->
            <div>
                <label for="offset_dias_fin" class="block text-sm font-medium text-gray-700">
                    Offset día de fin
                </label>
                <select name="offset_dias_fin" id="offset_dias_fin"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="-1" {{ old('offset_dias_fin', $turno->offset_dias_fin ?? 0) == -1 ? 'selected' : '' }}>
                        Día anterior (-1)
                    </option>
                    <option value="0" {{ old('offset_dias_fin', $turno->offset_dias_fin ?? 0) == 0 ? 'selected' : '' }}>
                        Mismo día (0)
                    </option>
                    <option value="1" {{ old('offset_dias_fin', $turno->offset_dias_fin ?? 0) == 1 ? 'selected' : '' }}>
                        Día siguiente (+1)
                    </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Para turno de noche: Día siguiente (+1)</p>
                @error('offset_dias_fin')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Ejemplo visual -->
        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
            <p class="text-xs font-medium text-blue-900 mb-2">Ejemplo: Turno de Noche (22:00 - 06:00)</p>
            <ul class="text-xs text-blue-800 space-y-1">
                <li>• Hora inicio: 22:00, Offset inicio: 0 (mismo día)</li>
                <li>• Hora fin: 06:00, Offset fin: 1 (día siguiente)</li>
                <li>• Resultado: Lunes 22:00 → Martes 06:00</li>
                <li class="font-bold mt-2">⚠️ El turno de noche del LUNES comienza el DOMINGO a las 22:00</li>
            </ul>
        </div>
    </div>

    <!-- Días de trabajo -->
    @php
        $diasDefault = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        $diasActuales = old('dias_semana', $turno->dias_semana ?? $diasDefault);
        if (!is_array($diasActuales)) {
            $diasActuales = $diasDefault;
        }
    @endphp
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Días de trabajo</h4>
        <p class="text-xs text-gray-500 mb-4">Selecciona los días en que este turno opera. Por defecto, los turnos operan de lunes a viernes.</p>

        <!-- Botones rápidos -->
        <div class="flex gap-2 mb-4">
            <button type="button" id="btn-dias-laborables"
                class="px-3 py-1 text-xs font-medium rounded-md bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                Solo días laborables (Lun-Vie)
            </button>
            <button type="button" id="btn-todos-dias"
                class="px-3 py-1 text-xs font-medium rounded-md bg-green-100 text-green-700 hover:bg-green-200 transition">
                Todos los días
            </button>
        </div>

        <!-- Grid de checkboxes -->
        <div class="grid grid-cols-7 gap-2">
            @foreach(['lunes' => 'Lun', 'martes' => 'Mar', 'miercoles' => 'Mié', 'jueves' => 'Jue', 'viernes' => 'Vie', 'sabado' => 'Sáb', 'domingo' => 'Dom'] as $dia => $label)
                @php
                    $esFinDeSemana = in_array($dia, ['sabado', 'domingo']);
                    $checked = in_array($dia, $diasActuales);
                @endphp
                <label class="flex flex-col items-center p-2 rounded-lg border cursor-pointer transition
                    {{ $esFinDeSemana ? 'border-orange-300 bg-orange-50' : 'border-gray-300 bg-white' }}
                    hover:border-blue-400 hover:bg-blue-50
                    has-[:checked]:border-blue-500 has-[:checked]:bg-blue-100">
                    <input type="checkbox" name="dias_semana[]" value="{{ $dia }}"
                        {{ $checked ? 'checked' : '' }}
                        class="dia-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="mt-1 text-xs font-medium {{ $esFinDeSemana ? 'text-orange-700' : 'text-gray-700' }}">
                        {{ $label }}
                    </span>
                </label>
            @endforeach
        </div>
        @error('dias_semana')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <!-- Nota sobre fin de semana -->
        <div class="mt-3 p-2 bg-orange-50 border border-orange-200 rounded text-xs text-orange-800">
            <strong>Nota:</strong> Los días de fin de semana (Sáb/Dom) están resaltados en naranja.
            Si activas el sábado o domingo, el calendario de producción mostrará trabajo en esos días.
        </div>
    </div>

    <!-- Orden y Color -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Orden -->
        <div>
            <label for="orden" class="block text-sm font-medium text-gray-700">Orden de visualización</label>
            <input type="number" name="orden" id="orden" min="0"
                value="{{ old('orden', $turno->orden ?? 999) }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="1, 2, 3...">
            <p class="mt-1 text-xs text-gray-500">Números más bajos aparecen primero</p>
            @error('orden')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Color -->
        <div>
            <label for="color" class="block text-sm font-medium text-gray-700">Color (opcional)</label>
            <div class="flex gap-2 mt-1">
                <input type="color" name="color" id="color"
                    value="{{ old('color', $turno->color ?? '#3b82f6') }}"
                    class="h-10 w-20 rounded border-gray-300 cursor-pointer">
                <input type="text" name="color_text" id="color_text"
                    value="{{ old('color', $turno->color ?? '#3b82f6') }}"
                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="#3b82f6" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
            </div>
            <p class="mt-1 text-xs text-gray-500">Color para identificar el turno visualmente</p>
            @error('color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Estado activo -->
    <div class="flex items-center">
        <input type="checkbox" name="activo" id="activo" value="1"
            {{ old('activo', $turno->activo ?? true) ? 'checked' : '' }}
            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        <label for="activo" class="ml-2 block text-sm text-gray-900">
            Turno activo (solo turnos activos se usan en el calendario)
        </label>
    </div>
</div>

<!-- Script para sincronizar color picker con input text -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('color');
        const colorText = document.getElementById('color_text');

        if (colorPicker && colorText) {
            colorPicker.addEventListener('input', function() {
                colorText.value = this.value;
            });

            colorText.addEventListener('input', function() {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                    colorPicker.value = this.value;
                }
            });
        }

        // Botones rápidos para días de trabajo
        const checkboxes = document.querySelectorAll('.dia-checkbox');
        const btnDiasLaborables = document.getElementById('btn-dias-laborables');
        const btnTodosDias = document.getElementById('btn-todos-dias');
        const diasLaborables = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

        if (btnDiasLaborables) {
            btnDiasLaborables.addEventListener('click', function() {
                checkboxes.forEach(cb => {
                    cb.checked = diasLaborables.includes(cb.value);
                    // Trigger change para actualizar estilos
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }

        if (btnTodosDias) {
            btnTodosDias.addEventListener('click', function() {
                checkboxes.forEach(cb => {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }
    });
</script>
