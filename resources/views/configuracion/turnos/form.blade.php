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
    });
</script>
