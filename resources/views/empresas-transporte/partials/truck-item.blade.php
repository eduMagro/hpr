<div
    class="flex items-center justify-between p-3 bg-white rounded-2xl border border-slate-100 shadow-sm transition-all hover:border-indigo-200 animate-fade-in">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
            <i data-lucide="truck" class="w-4 h-4"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-slate-700 editable" contenteditable="true" data-id="{{ $camion->id }}"
                data-field="modelo">
                {{ $camion->modelo }}
            </p>
            <p class="text-[10px] text-slate-400 font-medium">
                Carga: <span class="editable" contenteditable="true" data-id="{{ $camion->id }}"
                    data-field="capacidad">{{ $camion->capacidad }}</span> kg
            </p>
        </div>
    </div>
    <span class="status-badge status-{{ strtolower($camion->estado) }} editable" contenteditable="true"
        data-id="{{ $camion->id }}" data-field="estado">
        {{ $camion->estado }}
    </span>
</div>
