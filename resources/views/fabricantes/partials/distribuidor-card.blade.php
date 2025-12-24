<div class="glass-card rounded-[2.5rem] p-8 hover-lift group relative overflow-hidden animate-fade-in">
    <div class="absolute top-0 right-0 p-8 opacity-5">
        <i data-lucide="truck" class="w-24 h-24 text-emerald-600"></i>
    </div>

    <div class="flex items-start justify-between mb-6">
        <div
            class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex flex-col items-center justify-center text-white font-bold shadow-xl shadow-emerald-100 leading-tight">
            @php
                $words = explode(' ', trim($distribuidor->nombre));
            @endphp
            @if (count($words) >= 2)
                <span class="text-lg tracking-[0.2em] pl-1">{{ strtoupper(substr($words[0], 0, 1)) }}
                    {{ strtoupper(substr($words[0], 1, 1)) }}</span>
                <span class="text-lg tracking-[0.2em] pl-1 -mt-1">{{ strtoupper(substr($words[1], 0, 1)) }}
                    {{ strtoupper(substr($words[1], 1, 1)) }}</span>
            @else
                <span class="text-2xl">{{ strtoupper(substr($words[0], 0, 2)) }}</span>
            @endif
        </div>
        <form action="{{ route('distribuidores.destroy', $distribuidor->id) }}" method="POST"
            onsubmit="return confirm('¿Estás seguro?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-slate-300 hover:text-rose-500 transition-colors">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
            </button>
        </form>
    </div>

    <div class="mb-8">
        <h3 class="text-xl font-bold text-slate-900 font-outfit group-hover:text-emerald-600 transition-colors editable"
            contenteditable="true" data-id="{{ $distribuidor->id }}" data-type="distribuidor" data-field="nombre">
            {{ $distribuidor->nombre }}
        </h3>
        <p class="text-slate-400 text-sm mt-1 uppercase font-bold tracking-widest editable" contenteditable="true"
            data-id="{{ $distribuidor->id }}" data-type="distribuidor" data-field="nif">
            {{ $distribuidor->nif ?? 'SIN NIF' }}
        </p>
    </div>

    <div class="space-y-4 mb-8">
        <div class="flex items-center gap-4 group/item">
            <div
                class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center group-hover/item:bg-emerald-50 transition-colors">
                <i data-lucide="phone" class="w-4 h-4 text-slate-400 group-hover/item:text-emerald-500"></i>
            </div>
            <span class="text-sm font-medium text-slate-600 editable" contenteditable="true"
                data-id="{{ $distribuidor->id }}" data-type="distribuidor" data-field="telefono">
                {{ $distribuidor->telefono ?? 'Sin Teléfono' }}
            </span>
        </div>
        <div class="flex items-center gap-4 group/item">
            <div
                class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center group-hover/item:bg-emerald-50 transition-colors">
                <i data-lucide="mail" class="w-4 h-4 text-slate-400 group-hover/item:text-emerald-500"></i>
            </div>
            <span class="text-sm font-medium text-slate-600 editable" contenteditable="true"
                data-id="{{ $distribuidor->id }}" data-type="distribuidor" data-field="email">
                {{ $distribuidor->email ?? 'Sin Email' }}
            </span>
        </div>
    </div>

    <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
            </div>
            <span class="text-xs font-bold text-slate-400">Pedidos:</span>
        </div>
        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
            {{ $distribuidor->pedidos->count() }}
        </span>
    </div>
</div>
