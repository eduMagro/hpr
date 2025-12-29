<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('maquinas.index') }}" wire:navigate class="text-blue-600">
                {{ __('Movimientos') }}
            </a>
            <span class="mx-2">/</span>
            {{ $linea->codigo ?? $pedido->codigo }}
        </h2>
    </x-slot>

    <div class="py-6">
        @php
            $producto = $productoBase;
            $defecto = $ultimos[$producto->id] ?? null;
            $coladaPorDefecto = $defecto?->n_colada ?? null;
            $ubicacionPorDefecto = $defecto?->ubicacion_id ?? null;

            $entradaAbierta = $pedido->entradas()->where('estado', 'abierto')->with('productos')->latest()->first();

            $productosDeEstaEntrada = \App\Models\Producto::where('entrada_id', $entradaAbierta?->id)
                ->where('producto_base_id', $producto->id)
                ->with('productoBase')
                ->get();

            $movimientoPendiente = $linea?->movimientos()->where('estado', 'pendiente')->first();
        @endphp

        @if ($entradaAbierta && $productosDeEstaEntrada->isNotEmpty())
            <div class="bg-white border rounded shadow p-4 mb-6 max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-base font-semibold text-gray-800">
                        Albarán abierto: <span class="text-blue-600">{{ $entradaAbierta->albaran }}</span>
                    </h3>
                    <form id="cerrar-albaran-form" method="POST"
                        action="{{ route('entradas.cerrar', $entradaAbierta->id) }}" class="hidden">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="movimiento_id" value="{{ $movimientoPendiente?->id }}">
                    </form>

                    <button onclick="confirmarCerrarAlbaran()"
                        class="bg-red-600 text-white text-xs px-3 py-1 rounded hover:bg-red-700">
                        Cerrar Albarán
                    </button>
                </div>

                <p class="text-sm text-gray-600 mb-3">
                    Total recepcionado: <strong>{{ number_format($entradaAbierta->peso_total, 2, ',', '.') }}
                        kg</strong>
                </p>

                <ul class="divide-y text-sm text-gray-800">
                    @foreach ($productosDeEstaEntrada as $prod)
                        <li class="py-2 flex justify-between">
                            <a href="javascript:void(0);" class="font-semibold uppercase text-blue-600 hover:underline"
                                onclick='editarProducto(@json($prod))'>
                                {{ $prod->codigo }}
                            </a>

                            <span>
                                {{ ucfirst($prod->productoBase->tipo ?? '-') }} /
                                Ø{{ $prod->productoBase->diametro ?? '-' }} mm —
                                {{ number_format($prod->peso_inicial, 2, ',', '.') }} kg
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="text-center mt-4">
            <button onclick="iniciarRecepcionMejorada()"
                class="bg-green-600 text-white px-6 py-3 rounded-lg shadow hover:bg-green-700 transition-colors text-lg font-semibold touch-target">
                ➕ Registrar nuevo paquete
            </button>
        </div>

        {{-- Formulario oculto --}}
        <form id="recepcionForm" method="POST"
            action="{{ route('pedidos.recepcion.guardar', ['pedido' => $pedido->id, 'producto_base' => $producto->id]) }}"
            style="display:none;">
            @csrf
            <input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
            <input type="hidden" name="pedido_producto_id" value="{{ $linea?->id }}">
            <input type="hidden" name="producto_base_id" value="{{ $producto->id }}">
            <input type="hidden" name="cantidad_paquetes" id="cantidad_paquetes_input">
            <input type="hidden" name="codigo" id="codigo_input">
            <input type="hidden" name="fabricante_id" id="fabricante_id_input">
            <input type="hidden" name="n_colada" id="n_colada_input">
            <input type="hidden" name="n_paquete" id="n_paquete_input">
            <input type="hidden" name="codigo_2" id="codigo_2_input">
            <input type="hidden" name="n_colada_2" id="n_colada_2_input">
            <input type="hidden" name="n_paquete_2" id="n_paquete_2_input">
            <input type="hidden" name="peso" id="peso_input">
            <input type="hidden" name="ubicacion_id" id="ubicacion_input">
            <input type="hidden" name="otros" id="otros_input">
            <input type="hidden" name="movimiento_id" value="{{ $movimientoPendiente?->id }}">
        </form>
    </div>

    {{-- CSS optimizado para móvil y PC --}}
    <style>
        /* ============================================
           ESTILOS BASE
           ============================================ */
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #d1d5db;
            transition: all 0.3s;
        }

        .step-dot.active {
            background: #10b981;
            transform: scale(1.2);
        }

        .step-dot.completed {
            background: #3b82f6;
        }

        .keyboard-hint {
            font-size: 11px;
            color: #6b7280;
            margin-top: 12px;
            text-align: center;
            line-height: 1.4;
        }

        .resumen-item {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            background: #f9fafb;
            border-radius: 4px;
            margin-bottom: 8px;
            align-items: center;
        }

        .resumen-item strong {
            color: #374151;
        }

        .resumen-item span {
            color: #059669;
            font-weight: 600;
            text-align: right;
        }

        /* Touch target mínimo de 44x44px (Apple HIG) */
        .touch-target {
            min-height: 44px;
            min-width: 44px;
        }

        /* Labels más visibles */
        .swal2-html-container label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        /* Asteriscos requeridos */
        .required-asterisk {
            color: #ef4444;
            margin-left: 2px;
        }

        /* ============================================
           BOTONES MEJORADOS - MÁS INTUITIVOS
           ============================================ */

        /* Contenedor de botones */
        .swal2-actions-custom {
            display: flex !important;
            justify-content: space-between !important;
            width: 100% !important;
            gap: 12px !important;
            margin: 20px 0 0 0 !important;
        }

        /* Botón Anterior/Cancelar - IZQUIERDA */
        .swal2-cancel-custom {
            order: 1 !important;
            background-color: #6b7280 !important;
            color: white !important;
            border: none !important;
            padding: 12px 24px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            min-width: 120px !important;
            text-align: center !important;
        }

        .swal2-cancel-custom:hover {
            background-color: #4b5563 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Botón Siguiente/Finalizar - DERECHA */
        .swal2-confirm-custom {
            order: 2 !important;
            background-color: #10b981 !important;
            color: white !important;
            border: none !important;
            padding: 12px 24px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            min-width: 120px !important;
            text-align: center !important;
        }

        .swal2-confirm-custom:hover {
            background-color: #059669 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* ============================================
           BOTÓN DE CIERRE (X)
           ============================================ */
        .swal2-close-custom {
            font-size: 28px !important;
            color: #6b7280 !important;
            transition: all 0.2s !important;
        }

        .swal2-close-custom:hover {
            color: #ef4444 !important;
            transform: scale(1.1);
        }

        /* Botón deny para "Empezar de nuevo" */
        .swal2-deny-custom {
            background-color: #f59e0b !important;
            color: white !important;
            border: none !important;
            padding: 12px 24px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            min-width: 120px !important;
        }

        .swal2-deny-custom:hover {
            background-color: #d97706 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* ============================================
   OPTIMIZACIONES MÓVILES - MEJORADAS
   ============================================ */
        @media (max-width: 768px) {

            /* Contenedor fijo en la parte superior */
            .swal2-container {
                align-items: flex-start !important;
                padding: 5px 10px 20px 10px !important;
                display: flex !important;
                justify-content: center !important;
            }

            /* Select de coladas en móvil */
            .colada-select {
                font-size: 16px !important;
                min-height: 48px !important;
                padding: 12px 14px !important;
            }

            .colada-custom-input {
                font-size: 16px !important;
                min-height: 48px !important;
                padding: 12px 14px !important;
            }

            .volver-select-btn {
                font-size: 15px !important;
                min-height: 44px !important;
                padding: 12px 16px !important;
            }

            /* Modal sin altura fija - se ajusta al contenido */
            .swal2-popup {
                position: fixed !important;
                top: 10px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                width: calc(100vw - 20px) !important;
                max-width: 500px !important;
                margin: 0 !important;
                padding: 15px 12px !important;
                max-height: calc(100vh - 20px) !important;
                overflow-y: auto !important;
            }

            /* Contenido compacto */
            .swal2-html-container {
                margin: 8px 0 !important;
                padding: 0 !important;
                font-size: 15px !important;
                max-height: none !important;
            }

            .swal2-title {
                font-size: 17px !important;
                padding: 3px 0 !important;
                margin: 0 0 8px 0 !important;
            }

            /* Inputs con font-size 16px para evitar zoom automático en iOS */
            .swal2-input,
            .swal2-select {
                font-size: 16px !important;
                padding: 8px !important;
                height: auto !important;
                min-height: 44px !important;
                margin: 6px 0 !important;
            }

            /* Botones táctiles */
            .swal2-confirm,
            .swal2-cancel,
            .swal2-deny {
                font-size: 15px !important;
                padding: 12px 16px !important;
                min-height: 44px !important;
                min-width: 44px !important;
            }

            /* Botones compactos */
            .swal2-actions {
                margin: 12px 0 5px 0 !important;
                padding: 0 !important;
                gap: 8px !important;
            }

            .swal2-confirm-custom,
            .swal2-cancel-custom,
            .swal2-deny-custom {
                flex: 1 !important;
                min-width: 90px !important;
                padding: 10px 12px !important;
                font-size: 14px !important;
            }

            /* Botón X */
            .swal2-close-custom {
                font-size: 28px !important;
                width: 40px !important;
                height: 40px !important;
                top: 5px !important;
                right: 5px !important;
            }

            /* Indicador de pasos compacto */
            .step-indicator {
                padding: 6px;
                gap: 6px;
                margin-bottom: 10px;
            }

            .step-dot {
                width: 8px;
                height: 8px;
            }

            .step-dot.active {
                transform: scale(1.2);
            }

            /* Resumen items compacto */
            .resumen-item {
                padding: 8px;
                margin-bottom: 6px;
                font-size: 13px;
            }

            /* Checkbox */
            input[type="checkbox"] {
                transform: scale(1.3) !important;
                margin-right: 6px !important;
            }

            /* Labels */
            label {
                font-size: 14px !important;
                margin-bottom: 6px !important;
            }

            /* Keyboard hint oculto en móvil */
            .keyboard-hint {
                display: none !important;
            }

            /* Body sin scroll */
            body.swal2-shown {
                overflow: hidden !important;
            }
        }

        /* Dispositivos muy pequeños */
        @media (max-width: 375px) {
            .swal2-popup {
                width: calc(100vw - 16px) !important;
                padding: 12px 10px !important;
            }
        }

        /* Orientación horizontal */
        @media (max-height: 500px) and (orientation: landscape) {
            .swal2-popup {
                padding: 12px 10px !important;
                top: 5px !important;
            }

            .step-indicator {
                padding: 4px;
                margin-bottom: 6px;
            }

            .swal2-title {
                font-size: 16px !important;
                margin-bottom: 6px !important;
            }
        }

        /* Mejoras de accesibilidad */
        .swal2-confirm:focus,
        .swal2-cancel:focus,
        .swal2-input:focus,
        .swal2-select:focus {
            outline: 3px solid #3b82f6 !important;
            outline-offset: 2px !important;
        }

        /* ============================================
           ESTILOS PARA SELECT DE COLADAS
           ============================================ */

        /* Select de coladas */
        .colada-select {
            border: 2px solid #e5e7eb !important;
            transition: all 0.2s ease !important;
            font-weight: 500 !important;
        }

        .colada-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        /* Input personalizado para colada */
        .colada-custom-input {
            border: 2px solid #10b981 !important;
            background: #f0fdf4 !important;
            transition: all 0.2s ease !important;
        }

        .colada-custom-input:focus {
            border-color: #059669 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
            background: white !important;
        }

        .colada-custom-input::placeholder {
            color: #6b7280;
            font-style: italic;
        }

        /* Botón volver al select */
        .volver-select-btn:hover {
            background: #e5e7eb !important;
            border-color: #9ca3af !important;
        }

        .volver-select-btn:active {
            transform: scale(0.98);
        }

        /* Animaciones */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Animaciones más suaves en móvil */
        @media (prefers-reduced-motion: reduce) {

            .step-dot,
            .swal2-show,
            .swal2-hide {
                transition: none !important;
                animation: none !important;
            }
        }
    </style>

    <script>
        const requiereFabricante = @json($requiereFabricanteManual);
        const fabricantes = @json($fabricantes->pluck('nombre', 'id'));
        const ultimoFabricanteId = @json($ultimoFabricante);

        // ✅ NUEVO: Ubicaciones agrupadas por sector
        const ubicacionesPorSector = @json($ubicacionesPorSector);
        const sectores = @json($sectores);
        const sectorPorDefecto = @json($sectorPorDefecto);
        const ubicacionDefecto = '{{ $ubicacionPorDefecto }}';

        const coladaDefecto = '{{ $coladaPorDefecto }}';
        const coladasDisponibles = @json($linea->coladas->pluck('colada', 'colada'));
        const hasColadas = Object.keys(coladasDisponibles).length > 0;

        // 🎯 Sistema mejorado de recepción con navegación
        class RecepcionWizard {
            constructor() {
                this.currentStep = 0;
                this.data = {};
                this.steps = [];
                this.isMobile = this.detectMobile();
                this.storageKey = 'recepcion_wizard_data'; // ✅ NUEVO: Key para localStorage
                this.setupSteps();
            }

            detectMobile() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                    window.innerWidth <= 768;
            }

            // ✅ NUEVO: Cargar datos guardados
            loadSavedData() {
                try {
                    const saved = localStorage.getItem(this.storageKey);
                    if (saved) {
                        const parsed = JSON.parse(saved);
                        return parsed;
                    }
                } catch (e) {
                    console.error('Error al cargar datos guardados:', e);
                }
                return null;
            }

            // ✅ NUEVO: Guardar datos en localStorage
            saveData() {
                try {
                    const dataToSave = {
                        currentStep: this.currentStep,
                        data: this.data,
                        timestamp: new Date().toISOString()
                    };
                    localStorage.setItem(this.storageKey, JSON.stringify(dataToSave));
                } catch (e) {
                    console.error('Error al guardar datos:', e);
                }
            }

            // ✅ NUEVO: Limpiar datos guardados
            clearSavedData() {
                try {
                    localStorage.removeItem(this.storageKey);
                } catch (e) {
                    console.error('Error al limpiar datos:', e);
                }
            }

            setupSteps() {
                // Definir todos los pasos del wizard
                this.steps = [{
                        name: 'paquetes',
                        title: '¿Cuántos paquetes?',
                        type: 'select',
                        options: {
                            '1': '1 paquete',
                            '2': '2 paquetes'
                        },
                        defaultValue: '1',
                        required: true,
                        validator: (value) => value ? null : 'Debes seleccionar una opción'
                    },
                    // Fabricante (condicional)
                    ...(requiereFabricante ? [{
                        name: 'fabricante_id',
                        title: 'Selecciona el fabricante',
                        type: 'select',
                        options: fabricantes,
                        defaultValue: ultimoFabricanteId,
                        required: true,
                        validator: (value) => value ? null : 'Debes seleccionar un fabricante'
                    }] : []),
                    {
                        name: 'codigo',
                        title: 'Código del paquete',
                        type: 'text',
                        placeholder: 'Escanea el código MP...',
                        required: true,
                        autofocus: true,
                        validator: (value) => {
                            const v = (value || '').trim();
                            if (!v) return 'Código requerido';
                            if (!/^mp/i.test(v)) return 'El código debe empezar por MP';
                            if (v.length > 20) return 'Máximo 20 caracteres';
                            return null;
                        }
                    },
                    {
                        name: 'n_colada',
                        title: 'Número de colada',
                        type: 'text',
                        datalist: hasColadas ? coladasDisponibles : undefined,
                        defaultValue: coladaDefecto,
                        required: true,
                        validator: (value) => value ? null : 'Número de colada requerido'
                    },
                    {
                        name: 'n_paquete',
                        title: 'Número de paquete',
                        type: 'number',
                        required: true,
                        validator: (value) => value ? null : 'Número de paquete requerido'
                    },
                    // Pasos del segundo paquete (condicionales)
                    {
                        name: 'codigo_2',
                        title: 'Código del segundo paquete',
                        type: 'text',
                        placeholder: 'Escanea el código MP...',
                        condition: () => this.data.paquetes === '2',
                        required: true,
                        autofocus: true,
                        validator: (value) => {
                            if (this.data.paquetes !== '2') return null;
                            const v = (value || '').trim();
                            if (!v) return 'Código requerido';
                            if (!/^mp/i.test(v)) return 'El código debe empezar por MP';
                            if (v.length > 20) return 'Máximo 20 caracteres';
                            return null;
                        }
                    },
                    {
                        name: 'n_colada_2',
                        title: 'Colada del segundo paquete',
                        type: 'text',
                        datalist: hasColadas ? coladasDisponibles : undefined,
                        defaultValue: coladaDefecto,
                        condition: () => this.data.paquetes === '2',
                        required: false
                    },
                    {
                        name: 'n_paquete_2',
                        title: 'Nº paquete del segundo',
                        type: 'number',
                        condition: () => this.data.paquetes === '2',
                        required: false
                    },
                    {
                        name: 'peso',
                        title: 'Peso total (kg)',
                        type: 'number',
                        inputAttributes: {
                            step: '0.01',
                            min: '0.01'
                        },
                        required: true,
                        validator: (value) => (value && value > 0) ? null : 'Introduce un peso válido'
                    },
                    {
                        name: 'ubicacion',
                        title: 'Ubicación',
                        type: 'custom',
                        required: true
                    },
                    {
                        name: 'resumen',
                        title: 'Confirmar datos',
                        type: 'resumen'
                    }
                ];
            }

            getActiveSteps() {
                return this.steps.filter(step => !step.condition || step.condition());
            }

            async start() {
                // ✅ NUEVO: Verificar si hay datos guardados
                const savedData = this.loadSavedData();

                if (savedData && savedData.data && Object.keys(savedData.data).length > 0) {
                    const result = await Swal.fire({
                        title: '¿Continuar recepción?',
                        html: '<p style="margin-bottom: 8px;">Hay una recepción sin completar</p>' +
                            '<p style="font-size: 13px; color: #6b7280;">Guardada: ' +
                            new Date(savedData.timestamp).toLocaleString('es-ES') + '</p>',
                        icon: 'question',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: '▶️ Continuar',
                        denyButtonText: '🔄 Empezar de nuevo',
                        cancelButtonText: 'Cancelar',
                        customClass: {
                            container: this.isMobile ? 'swal2-mobile' : '',
                            confirmButton: 'swal2-confirm-custom',
                            cancelButton: 'swal2-cancel-custom',
                            denyButton: 'swal2-deny-custom'
                        }
                    });

                    if (result.isConfirmed) {
                        // Continuar desde donde se quedó
                        this.currentStep = savedData.currentStep;
                        this.data = savedData.data;
                        await this.showStep();
                        return;
                    } else if (result.isDenied) {
                        // Empezar de nuevo
                        this.clearSavedData();
                        this.currentStep = 0;
                        this.data = {};
                        await this.showStep();
                        return;
                    } else {
                        // Canceló todo
                        return;
                    }
                }

                // Si no hay datos guardados, empezar normal
                this.currentStep = 0;
                this.data = {};
                await this.showStep();
            }

            async showStep() {
                const activeSteps = this.getActiveSteps();
                const step = activeSteps[this.currentStep];

                if (!step) {
                    await this.finish();
                    return;
                }

                const stepNumber = this.currentStep + 1;
                const totalSteps = activeSteps.length;

                let swalConfig = {
                    html: this.buildStepHTML(step, stepNumber, totalSteps),
                    showCancelButton: true,
                    showConfirmButton: true,
                    showCloseButton: true, // ✅ NUEVO: Botón X
                    confirmButtonText: this.currentStep === activeSteps.length - 1 ? '✅ Finalizar' : 'Siguiente →',
                    cancelButtonText: this.currentStep === 0 ? 'Cancelar' : '← Anterior',
                    reverseButtons: false,
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    showClass: {
                        popup: 'animate__animated animate__fadeIn animate__faster'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOut animate__faster'
                    },
                    customClass: {
                        container: this.isMobile ? 'swal2-mobile' : '',
                        popup: this.isMobile ? 'swal2-mobile-popup' : '',
                        actions: 'swal2-actions-custom',
                        confirmButton: 'swal2-confirm-custom',
                        cancelButton: 'swal2-cancel-custom',
                        closeButton: 'swal2-close-custom' // ✅ NUEVO
                    },
                    didOpen: () => {
                        this.setupKeyboardNavigation();
                        this.setupStepSpecificBehavior(step);

                    },
                    preConfirm: () => {
                        return this.getStepValue(step);
                    }
                };

                const result = await Swal.fire(swalConfig);

                if (result.isConfirmed) {
                    // Validar
                    const value = result.value;
                    const error = step.validator ? step.validator(value) : null;

                    if (error) {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error,
                            confirmButtonText: 'Reintentar',
                            customClass: {
                                container: this.isMobile ? 'swal2-mobile' : ''
                            }
                        });
                        await this.showStep();
                        return;
                    }

                    // Guardar valor
                    if (step.name !== 'resumen') {
                        this.data[step.name] = this.normalizeValue(step, value);
                    }

                    // ✅ NUEVO: Guardar en localStorage
                    this.saveData();

                    // Avanzar
                    this.currentStep++;
                    await this.showStep();

                } else if (result.isDismissed) {
                    // ✅ NUEVO: Manejar el cierre con X o ESC
                    if (result.dismiss === Swal.DismissReason.close || result.dismiss === Swal.DismissReason.esc) {
                        await this.handleCancel();
                        return;
                    }

                    if (this.currentStep > 0) {
                        // Retroceder
                        this.currentStep--;
                        // ✅ NUEVO: Guardar estado al retroceder
                        this.saveData();
                        await this.showStep();
                    } else {
                        // Cancelar todo
                        await this.handleCancel();
                    }
                }
            }

            // ✅ NUEVO: Manejar cancelación
            async handleCancel() {
                const confirmCancel = await Swal.fire({
                    title: '¿Cancelar recepción?',
                    html: '<p>Los datos se guardarán para continuar después</p>' +
                        '<p style="font-size: 13px; color: #6b7280; margin-top: 8px;">Podrás recuperarlos la próxima vez que recepciones</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar y guardar',
                    cancelButtonText: 'Continuar recepción',
                    confirmButtonColor: '#f59e0b',
                    customClass: {
                        container: this.isMobile ? 'swal2-mobile' : ''
                    }
                });

                if (confirmCancel.isConfirmed) {
                    // Guardar los datos antes de salir
                    this.saveData();

                    await Swal.fire({
                        icon: 'info',
                        title: 'Datos guardados',
                        text: 'Podrás continuar cuando vuelvas a recepcionar',
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            container: this.isMobile ? 'swal2-mobile' : ''
                        }
                    });

                    // Cerrar modal y volver
                    window.history.back();
                } else {
                    // Volver al paso actual
                    await this.showStep();
                }
            }



            buildStepHTML(step, stepNumber, totalSteps) {
                // Construir indicador de pasos
                let dotsHTML = '';
                for (let i = 0; i < totalSteps; i++) {
                    let dotClass = 'step-dot';
                    if (i < stepNumber - 1) dotClass += ' completed';
                    if (i === stepNumber - 1) dotClass += ' active';
                    dotsHTML += '<div class="' + dotClass + '"></div>';
                }

                let html = '<div class="step-indicator">' + dotsHTML + '</div>';
                html += '<div style="text-align: center; font-size: 13px; color: #6b7280; margin-bottom: 15px;">';

                html += '</div>';

                // Contenido del paso
                if (step.type === 'custom' && step.name === 'ubicacion') {
                    html += this.buildUbicacionHTML();
                } else if (step.type === 'resumen') {
                    html += this.buildResumenHTML();
                } else {
                    html += this.buildInputHTML(step);
                }

                return html;
            }

            buildInputHTML(step) {
                const defaultValue = this.data[step.name] || step.defaultValue || '';
                const labelSize = this.isMobile ? '22px' : '20px';

                // Label encima del input
                let html = '<div style="text-align: left; margin: 0 auto; max-width: 90%;">';
                html +=
                    '<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151; font-size: ' +
                    labelSize + ';">';
                html += step.title;
                if (step.required) html += ' <span style="color: #ef4444;">*</span>';

                // Contador de coladas disponibles (solo para campos con datalist)
                if (step.datalist) {
                    const coladasCount = Object.keys(step.datalist).length;
                    if (coladasCount > 0) {
                        html += '<span style="margin-left: 8px; font-size: 12px; color: #6b7280; font-weight: 500; ' +
                            'background: #f3f4f6; padding: 2px 8px; border-radius: 12px;">' +
                            coladasCount + ' disponible' + (coladasCount > 1 ? 's' : '') +
                            '</span>';
                    }
                }

                html += '</label>';

                if (step.type === 'select') {
                    let optionsHTML = '';
                    for (const [value, label] of Object.entries(step.options)) {
                        const selected = value == defaultValue ? ' selected' : '';
                        optionsHTML += '<option value="' + value + '"' + selected + '>' + label + '</option>';
                    }

                    html += '<select id="swal-input" class="swal2-input" style="width: 100%; margin: 0;">' +
                        optionsHTML +
                        '</select>';
                } else if (step.datalist && step.type === 'text') {
                    // 🎯 SELECT CON OPCIÓN "OTRA COLADA"
                    const selectId = 'swal-select-' + step.name;
                    const inputId = 'swal-input';
                    const containerId = 'colada-container-' + step.name;

                    html += '<div id="' + containerId + '">';

                    // Select principal con las coladas disponibles
                    let optionsHTML = '';
                    let hasDefaultInList = false;

                    // Agregar opciones de coladas existentes
                    for (const [value, label] of Object.entries(step.datalist)) {
                        const selected = value == defaultValue ? ' selected' : '';
                        if (value == defaultValue) hasDefaultInList = true;
                        optionsHTML += '<option value="' + value + '"' + selected + '>' + value + '</option>';
                    }

                    // ⚠️ NO agregar el valor por defecto si no está en la lista (nuevo comportamiento)

                    // Separador visual si hay opciones
                    if (Object.keys(step.datalist).length > 0) {
                        optionsHTML += '<option disabled style="font-size: 1px;">───────────────────</option>';
                    }

                    // Opción "Otra colada..." al final
                    optionsHTML += '<option value="__otra__">✍️ Escribir otra colada...</option>';

                    html += '<select id="' + selectId + '" class="swal2-input colada-select" ' +
                        'style="width: 100%; font-size: 16px; margin: 0;">' +
                        optionsHTML +
                        '</select>';

                    // Input SIEMPRE oculto al inicio (sustituye al select cuando se elige "Otra colada...")
                    html += '<div id="input-wrapper-' + step.name + '" style="display: none;">';

                    html += '<input id="' + inputId + '" ' +
                        'type="text" ' +
                        'class="swal2-input colada-custom-input" ' +
                        'placeholder="Escribe el número de colada" ' +
                        'value="" ' +
                        'style="width: 100%; font-size: 16px; margin: 0;">';

                    // Botón para volver al select
                    html += '<button type="button" id="volver-select-' + step.name + '" ' +
                        'class="volver-select-btn" ' +
                        'style="margin-top: 10px; padding: 8px 14px; font-size: 14px; ' +
                        'background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; ' +
                        'color: #374151; cursor: pointer; transition: all 0.2s; width: 100%; ' +
                        'font-weight: 500;">' +
                        '← Volver a seleccionar de la lista' +
                        '</button>';

                    // Mensaje de ayuda
                    html += '<div id="colada-help-' + step.name + '" ' +
                        'style="margin-top: 8px; font-size: 13px; color: #059669; animation: fadeIn 0.3s ease;">' +
                        '💡 Esta colada se guardará para futuras recepciones' +
                        '</div>';

                    html += '</div>'; // fin input-wrapper

                    html += '</div>'; // fin container
                } else {
                    const attrs = step.inputAttributes || {};
                    let attrStr = '';
                    for (const [k, v] of Object.entries(attrs)) {
                        attrStr += ' ' + k + '="' + v + '"';
                    }

                    // En móvil, inputmode ayuda a mostrar el teclado correcto
                    let inputMode = '';
                    if (this.isMobile) {
                        if (step.type === 'number') inputMode = ' inputmode="decimal"';
                        if (step.type === 'text') inputMode = ' inputmode="text"';
                    }

                    html += '<input id="swal-input" ' +
                        'type="' + step.type + '" ' +
                        'class="swal2-input" ' +
                        'placeholder="' + (step.placeholder || '') + '" ' +
                        'value="' + defaultValue + '"' +
                        attrStr +
                        inputMode +
                        ' style="width: 100%; font-size: 16px; margin: 0;"' +
                        (step.autofocus ? ' autofocus' : '') +
                        '>';
                }

                html += '</div>';
                return html;
            }

            buildUbicacionHTML() {
                const labelSize = this.isMobile ? '22px' : '20px';

                let html = '<div style="text-align: left; margin: 0 auto; max-width: 90%;">';

                // ========================================
                // 1️⃣ SELECT DE SECTOR
                // ========================================
                html +=
                    '<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151; font-size: ' +
                    labelSize + ';">';
                html += 'Sector <span style="color: #ef4444;">*</span>';
                html += '</label>';

                // Construir opciones de sectores
                let sectoresOptionsHTML = '';
                for (const sector of sectores) {
                    const selected = sector === sectorPorDefecto ? ' selected' : '';
                    sectoresOptionsHTML += '<option value="' + sector + '"' + selected + '>' + sector + '</option>';
                }

                html +=
                    '<select id="swal-sector" class="swal2-input" style="width:100%; font-size: 16px; margin: 0 0 16px 0;">' +
                    sectoresOptionsHTML +
                    '</select>';

                // ========================================
                // 2️⃣ SELECT DE UBICACIÓN (dentro del sector)
                // ========================================
                html +=
                    '<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151; font-size: ' +
                    labelSize + ';">';
                html += 'Ubicación <span style="color: #ef4444;">*</span>';
                html += '</label>';

                // Construir opciones de ubicaciones del sector seleccionado
                const sectorSeleccionado = sectorPorDefecto;
                const ubicacionesDelSector = ubicacionesPorSector[sectorSeleccionado] || [];

                let ubicacionesOptionsHTML = '';
                for (const ubicacion of ubicacionesDelSector) {
                    const selected = ubicacion.id == ubicacionDefecto ? ' selected' : '';
                    ubicacionesOptionsHTML += '<option value="' + ubicacion.id + '"' + selected + '>' +
                        ubicacion.nombre_sin_prefijo + '</option>';
                }

                html +=
                    '<select id="swal-ubicacion" class="swal2-input" style="width:100%; font-size: 16px; margin: 0 0 12px 0;">' +
                    ubicacionesOptionsHTML +
                    '</select>';

                // ========================================
                // 3️⃣ CHECKBOX PARA ESCANEAR
                // ========================================
                html += '<label style="display:flex;align-items:center;gap:8px;font-size:' + (this.isMobile ? '16px' :
                        '15px') +
                    ';margin-top:8px;cursor:pointer;">' +
                    '<input type="checkbox" id="swal-scan-checkbox" style="transform:scale(' + (this.isMobile ? '1.5' :
                        '1.2') + ');cursor:pointer;">' +
                    '<span>Escanear ubicación en su lugar</span>' +
                    '</label>';

                // Input de escaneo (oculto inicialmente)
                html += '<input id="swal-ubicacion-scan" type="text" class="swal2-input" ' +
                    'placeholder="Escanea el código de ubicación" style="display:none;width:100%; font-size: 16px; margin-top: 8px;">';

                html += '</div>';

                return html;
            }

            buildResumenHTML() {
                const labelSize = this.isMobile ? '24px' : '22px';

                let html = '<div style="text-align: left; max-width: 400px; margin: 0 auto;">';

                // Título del resumen
                html += '<div style="font-weight: 600; font-size: ' + labelSize +
                    '; color: #374151; margin-bottom: 16px; text-align: center;">';
                html += '📋 Revisar';
                html += '</div>';

                html += '<div class="resumen-item">' +
                    '<strong>Cantidad:</strong>' +
                    '<span>' + this.data.paquetes + ' paquete(s)</span>' +
                    '</div>';

                if (this.data.fabricante_id) {
                    const fabricanteNombre = fabricantes[this.data.fabricante_id];
                    html += '<div class="resumen-item">' +
                        '<strong>Fabricante:</strong>' +
                        '<span>' + fabricanteNombre + '</span>' +
                        '</div>';
                }

                html += '<div class="resumen-item">' +
                    '<strong>Código 1:</strong>' +
                    '<span>' + this.data.codigo + '</span>' +
                    '</div>';

                html += '<div class="resumen-item">' +
                    '<strong>Colada 1:</strong>' +
                    '<span>' + this.data.n_colada + '</span>' +
                    '</div>';

                html += '<div class="resumen-item">' +
                    '<strong>Paquete 1:</strong>' +
                    '<span>' + this.data.n_paquete + '</span>' +
                    '</div>';

                if (this.data.paquetes === '2') {
                    html += '<div class="resumen-item">' +
                        '<strong>Código 2:</strong>' +
                        '<span>' + this.data.codigo_2 + '</span>' +
                        '</div>';

                    if (this.data.n_colada_2) {
                        html += '<div class="resumen-item">' +
                            '<strong>Colada 2:</strong>' +
                            '<span>' + this.data.n_colada_2 + '</span>' +
                            '</div>';
                    }

                    if (this.data.n_paquete_2) {
                        html += '<div class="resumen-item">' +
                            '<strong>Paquete 2:</strong>' +
                            '<span>' + this.data.n_paquete_2 + '</span>' +
                            '</div>';
                    }
                }

                html += '<div class="resumen-item">' +
                    '<strong>Peso total:</strong>' +
                    '<span>' + this.data.peso + ' kg</span>' +
                    '</div>';

                // Mostrar sector y ubicación
                const sectorSeleccionado = this.data.sector || sectorPorDefecto;
                const ubicacionId = this.data.ubicacion;

                // Buscar la ubicación en el sector
                let ubicacionNombre = ubicacionId;
                if (ubicacionesPorSector[sectorSeleccionado]) {
                    const ubicacion = ubicacionesPorSector[sectorSeleccionado].find(u => u.id == ubicacionId);
                    if (ubicacion) {
                        ubicacionNombre = ubicacion.nombre_sin_prefijo;
                    }
                }

                html += '<div class="resumen-item">' +
                    '<strong>Sector:</strong>' +
                    '<span>' + sectorSeleccionado + '</span>' +
                    '</div>';

                html += '<div class="resumen-item">' +
                    '<strong>Ubicación:</strong>' +
                    '<span>' + ubicacionNombre + '</span>' +
                    '</div>';

                html += '</div>';
                return html;
            }

            setupKeyboardNavigation() {
                // Solo habilitar atajos de teclado en desktop
                if (this.isMobile) return;

                const popup = Swal.getPopup();
                if (!popup) return;

                // Remover listeners anteriores
                if (this.keyHandler) {
                    popup.removeEventListener('keydown', this.keyHandler);
                }

                // Nuevo handler
                this.keyHandler = (e) => {
                    if (e.key === 'ArrowRight' && !e.shiftKey) {
                        e.preventDefault();
                        const confirmBtn = Swal.getConfirmButton();
                        if (confirmBtn) confirmBtn.click();
                    } else if (e.key === 'ArrowLeft' && !e.shiftKey) {
                        e.preventDefault();
                        const cancelBtn = Swal.getCancelButton();
                        if (cancelBtn) cancelBtn.click();
                    }
                };

                popup.addEventListener('keydown', this.keyHandler);
            }

            setupStepSpecificBehavior(step) {
                // 🎯 Setup para SELECT con opción "Otra colada..."
                if (step.datalist && step.type === 'text') {
                    setTimeout(() => {
                        const select = document.getElementById('swal-select-' + step.name);
                        const inputWrapper = document.getElementById('input-wrapper-' + step.name);
                        const input = document.getElementById('swal-input');
                        const volverBtn = document.getElementById('volver-select-' + step.name);

                        if (!select || !inputWrapper || !input) return;

                        // Función para mostrar el input (ocultar select)
                        const mostrarInput = () => {
                            select.style.display = 'none';
                            inputWrapper.style.display = 'block';
                            inputWrapper.style.animation = 'slideDown 0.2s ease';
                            setTimeout(() => input.focus(), 50);
                        };

                        // Función para mostrar el select (ocultar input)
                        const mostrarSelect = () => {
                            inputWrapper.style.display = 'none';
                            select.style.display = 'block';
                            input.value = '';
                            // Resetear el select a la primera opción válida
                            select.selectedIndex = 0;
                        };

                        // Evento cuando cambia el select
                        select.addEventListener('change', () => {
                            if (select.value === '__otra__') {
                                mostrarInput();
                            }
                        });

                        // Evento del botón "Volver"
                        if (volverBtn) {
                            volverBtn.addEventListener('click', (e) => {
                                e.preventDefault();
                                mostrarSelect();
                            });
                        }

                        // Inicializar: siempre mostrar el select al inicio
                        mostrarSelect();
                    }, 100);
                }

                if (step.name === 'ubicacion') {
                    const sectorSelect = document.getElementById('swal-sector');
                    const ubicacionSelect = document.getElementById('swal-ubicacion');
                    const checkbox = document.getElementById('swal-scan-checkbox');
                    const inputScan = document.getElementById('swal-ubicacion-scan');

                    // Actualizar ubicaciones cuando cambia el sector
                    if (sectorSelect && ubicacionSelect) {
                        sectorSelect.addEventListener('change', () => {
                            const sectorSeleccionado = sectorSelect.value;
                            const ubicacionesDelSector = ubicacionesPorSector[sectorSeleccionado] || [];

                            // Limpiar y reconstruir opciones de ubicaciones
                            ubicacionSelect.innerHTML = '';

                            for (const ubicacion of ubicacionesDelSector) {
                                const option = document.createElement('option');
                                option.value = ubicacion.id;
                                option.textContent = ubicacion.nombre_sin_prefijo;
                                ubicacionSelect.appendChild(option);
                            }
                        });
                    }

                    // Checkbox para escanear ubicación
                    if (checkbox && ubicacionSelect && inputScan && sectorSelect) {
                        checkbox.addEventListener('change', () => {
                            if (checkbox.checked) {
                                sectorSelect.style.display = 'none';
                                ubicacionSelect.style.display = 'none';
                                inputScan.style.display = 'block';
                                inputScan.focus();
                            } else {
                                sectorSelect.style.display = 'block';
                                ubicacionSelect.style.display = 'block';
                                inputScan.style.display = 'none';
                            }
                        });
                    }
                }

                // Autoenfoque en inputs de texto
                if (step.autofocus || step.type === 'text' || step.type === 'number') {
                    setTimeout(() => {
                        const input = document.getElementById('swal-input');
                        if (input) {
                            input.focus();
                            if (!this.isMobile) {
                                input.select();
                            }
                        }
                    }, 100);
                }
            }

            getStepValue(step) {
                if (step.type === 'resumen') {
                    return true;
                }

                if (step.name === 'ubicacion') {
                    const checkbox = document.getElementById('swal-scan-checkbox');
                    if (checkbox && checkbox.checked) {
                        // Si está escaneando, devolver el valor escaneado
                        return document.getElementById('swal-ubicacion-scan').value;
                    }

                    // Guardar también el sector seleccionado
                    const sectorSelect = document.getElementById('swal-sector');
                    if (sectorSelect) {
                        this.data.sector = sectorSelect.value;
                    }

                    // Devolver el ID de la ubicación
                    return document.getElementById('swal-ubicacion').value;
                }

                // Para campos de colada con datalist
                if (step.datalist && step.type === 'text') {
                    const select = document.getElementById('swal-select-' + step.name);
                    const input = document.getElementById('swal-input');

                    if (select && select.value === '__otra__') {
                        // Si seleccionó "Otra colada...", devolver el valor del input
                        return input ? input.value : '';
                    } else if (select) {
                        // Si seleccionó una colada existente, devolver esa
                        return select.value;
                    }

                    // Fallback
                    return input ? input.value : null;
                }

                const input = document.getElementById('swal-input');
                return input ? input.value : null;
            }

            normalizeValue(step, value) {
                if (step.name === 'codigo' || step.name === 'codigo_2') {
                    return value.trim().toUpperCase();
                }
                return value;
            }

            async finish() {
                // Llenar formulario
                document.getElementById('cantidad_paquetes_input').value = this.data.paquetes;
                document.getElementById('codigo_input').value = this.data.codigo;
                document.getElementById('n_colada_input').value = this.data.n_colada;
                document.getElementById('n_paquete_input').value = this.data.n_paquete;
                document.getElementById('peso_input').value = this.data.peso;
                document.getElementById('ubicacion_input').value = this.data.ubicacion;
                document.getElementById('otros_input').value = this.data.otros || '';

                if (this.data.fabricante_id) {
                    document.getElementById('fabricante_id_input').value = this.data.fabricante_id;
                }

                if (this.data.paquetes === '2') {
                    document.getElementById('codigo_2_input').value = this.data.codigo_2 || '';
                    document.getElementById('n_colada_2_input').value = this.data.n_colada_2 || '';
                    document.getElementById('n_paquete_2_input').value = this.data.n_paquete_2 || '';
                }

                // ✅ NUEVO: Limpiar datos guardados antes de enviar
                this.clearSavedData();

                // Mostrar loading
                Swal.fire({
                    title: 'Guardando...',
                    text: 'Registrando la recepción',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    customClass: {
                        container: this.isMobile ? 'swal2-mobile' : ''
                    },
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar formulario
                document.getElementById('recepcionForm').submit();
            }
        }

        // Instancia global
        let wizard;

        function iniciarRecepcionMejorada() {
            wizard = new RecepcionWizard();
            wizard.start();
        }

        async function confirmarCerrarAlbaran() {
            @if ($entradaAbierta)
                // Primero verificar discrepancias
                try {
                    const response = await fetch(
                        '{{ route('entradas.verificarDiscrepancias', $entradaAbierta->id) }}');
                    const data = await response.json();

                    if (data.discrepancias && data.discrepancias.length > 0) {
                        // Construir lista HTML con discrepancias
                        let discrepanciasHTML = '<div style="text-align: left; font-size: 14px;">';

                        data.discrepancias.forEach(disc => {
                            const emoji = disc.tipo === 'exceso' ? '🔴' : '🟡';
                            const colorClass = disc.tipo === 'exceso' ? 'color: #dc2626;' : 'color: #d97706;';
                            discrepanciasHTML +=
                                `<div style="margin-bottom: 12px; padding: 8px; border-left: 3px solid ${disc.tipo === 'exceso' ? '#dc2626' : '#d97706'}; background: #f9fafb;">`;
                            discrepanciasHTML +=
                                `<div style="${colorClass} font-weight: 600; margin-bottom: 4px;">${emoji} Colada ${disc.colada}</div>`;
                            discrepanciasHTML +=
                                `<div style="font-size: 13px;">Esperados: <strong>${disc.esperados}</strong> | Recepcionados: <strong>${disc.recepcionados}</strong></div>`;
                            discrepanciasHTML += '</div>';
                        });

                        discrepanciasHTML += '</div>';

                        // Mostrar modal con discrepancias
                        const result = await Swal.fire({
                            title: '⚠️ Discrepancias detectadas',
                            html: discrepanciasHTML,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#e3342f',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Cerrar de todas formas',
                            cancelButtonText: 'Seguir recepcionando',
                            width: '400px'
                        });

                        if (result.isConfirmed) {
                            document.getElementById('cerrar-albaran-form').submit();
                        }
                    } else {
                        // No hay discrepancias, mostrar confirmación normal
                        const result = await Swal.fire({
                            title: '¿Cerrar albarán?',
                            text: "No podrás volver a editarlo después.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#e3342f',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Sí, cerrar',
                            cancelButtonText: 'Cancelar'
                        });

                        if (result.isConfirmed) {
                            document.getElementById('cerrar-albaran-form').submit();
                        }
                    }
                } catch (error) {
                    console.error('Error al verificar discrepancias:', error);
                    // Si hay error en la verificación, continuar con confirmación normal
                    const result = await Swal.fire({
                        title: '¿Cerrar albarán?',
                        text: "No podrás volver a editarlo después.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e3342f',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, cerrar',
                        cancelButtonText: 'Cancelar'
                    });

                    if (result.isConfirmed) {
                        document.getElementById('cerrar-albaran-form').submit();
                    }
                }
            @else
                // No hay entrada abierta
                Swal.fire('Error', 'No hay entrada abierta para cerrar.', 'error');
            @endif
        }

        // Función de edición de productos
        const fabricantesOptions = @json($fabricantes->pluck('nombre', 'id'));

        async function editarProducto(prod) {
            console.log('🟢 Abriendo modal para producto', prod);

            let fabricanteOptionsHTML = '';
            for (const [id, nombre] of Object.entries(fabricantesOptions)) {
                const selected = id == prod.fabricante_id ? ' selected' : '';
                fabricanteOptionsHTML += '<option value="' + id + '"' + selected + '>' + nombre + '</option>';
            }

            const formHtml =
                '<div style="text-align: left; margin-bottom: 10px;">' +
                '<label for="swal-codigo" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Código:</label>' +
                '<input id="swal-codigo" class="swal2-input" placeholder="Código" value="' + (prod.codigo || '') +
                '" style="font-size: 16px; margin-top: 0;">' +
                '</div>' +
                '<div style="text-align: left; margin-bottom: 10px;">' +
                '<label for="swal-colada" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Nº Colada:</label>' +
                '<input id="swal-colada" class="swal2-input" placeholder="Nº Colada" value="' + (prod.n_colada || '') +
                '" style="font-size: 16px; margin-top: 0;">' +
                '</div>' +
                '<div style="text-align: left; margin-bottom: 10px;">' +
                '<label for="swal-paquete" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Nº Paquete:</label>' +
                '<input id="swal-paquete" class="swal2-input" placeholder="Nº Paquete" value="' + (prod.n_paquete ||
                    '') + '" style="font-size: 16px; margin-top: 0;">' +
                '</div>' +
                '<div style="text-align: left; margin-bottom: 10px;">' +
                '<label for="swal-peso" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Peso inicial (kg):</label>' +
                '<input id="swal-peso" class="swal2-input" type="number" step="0.01" placeholder="Peso inicial (kg)" value="' +
                (prod.peso_inicial || '') + '" style="font-size: 16px; margin-top: 0;">' +
                '</div>' +
                '<div style="text-align: left; margin-bottom: 10px;">' +
                '<label for="swal-ubicacion" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Ubicación:</label>' +
                '<input id="swal-ubicacion" class="swal2-input" placeholder="Ubicación" value="' + (prod.ubicacion_id ||
                    '') + '" style="font-size: 16px; margin-top: 0;">' +
                '</div>' +
                '<div style="text-align: left; margin-bottom: 10px;">' +
                '<label for="swal-fabricante" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">Fabricante:</label>' +
                '<select id="swal-fabricante" class="swal2-input" style="font-size: 16px; margin-top: 0;">' +
                '<option value="">Sin fabricante</option>' +
                fabricanteOptionsHTML +
                '</select>' +
                '</div>';

            const {
                value: formValues
            } = await Swal.fire({
                title: 'Editar producto',
                html: formHtml,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: '💾 Guardar cambios',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    return {
                        codigo: document.getElementById('swal-codigo').value,
                        n_colada: document.getElementById('swal-colada').value,
                        n_paquete: document.getElementById('swal-paquete').value,
                        peso_inicial: document.getElementById('swal-peso').value,
                        ubicacion_id: document.getElementById('swal-ubicacion').value,
                        fabricante_id: document.getElementById('swal-fabricante').value,
                    };
                }
            });

            if (formValues) {
                console.log('✅ Datos editados (POST):', formValues);

                fetch('/productos/' + prod.id, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ...formValues,
                            _method: 'PUT'
                        })
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Error al actualizar');
                        return res.json();
                    })
                    .then(data => {
                        Swal.fire('Guardado', 'El producto se actualizó correctamente.', 'success')
                            .then(() => location.reload());
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'No se pudo guardar.', 'error');
                    });
            }
        }
    </script>

    {{-- Opcional: Agregar Animate.css para mejores transiciones --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

</x-app-layout>
