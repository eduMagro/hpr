<x-app-layout>
    @if (auth()->user()->rol !== 'operario')
        <x-slot name="header">
            <h2 class="text-lg font-semibold text-gray-800">
                <a href="{{ route('entradas.index') }}" class="text-blue-600">
                    {{ __('Entradas') }}
                </a>
                <span class="mx-2">/</span>
                {{ __('Crear Entradas de Material') }}
            </h2>
        </x-slot>
    @endif

    <div class="py-6">
        <div class="text-center mt-4">
            <button onclick="iniciarRegistro()"
                class="bg-green-600 text-white px-6 py-3 rounded-lg shadow hover:bg-green-700 transition-colors text-lg font-semibold touch-target">
                ➕ Registrar nuevo paquete
            </button>
        </div>

        {{-- Formulario oculto --}}
        <form id="inventarioForm" method="POST" action="{{ route('entradas.store') }}" style="display:none;">
            @csrf
            <input type="hidden" name="cantidad_paquetes" id="cantidad_paquetes_input">
            <input type="hidden" name="codigo" id="codigo_input">
            <input type="hidden" name="fabricante_id" id="fabricante_id_input">
            <input type="hidden" name="albaran" id="albaran_input">
            <input type="hidden" name="producto_base_id" id="producto_base_id_input">
            <input type="hidden" name="n_colada" id="n_colada_input">
            <input type="hidden" name="n_paquete" id="n_paquete_input">
            <input type="hidden" name="codigo_2" id="codigo_2_input">
            <input type="hidden" name="n_colada_2" id="n_colada_2_input">
            <input type="hidden" name="n_paquete_2" id="n_paquete_2_input">
            <input type="hidden" name="peso" id="peso_input">
            <input type="hidden" name="ubicacion_id" id="ubicacion_input">
            <input type="hidden" name="obra_id" id="obra_id_input">
        </form>
    </div>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const errores = `{!! implode('<br>', $errors->all()) !!}`;

                Swal.fire({
                    title: '⚠️ Errores de validación',
                    html: errores,
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    iniciarRegistro();
                });
            });
        </script>
    @endif

    {{-- CSS optimizado (igual que recepción) --}}
    <style>
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

        .touch-target {
            min-height: 44px;
            min-width: 44px;
        }

        .swal2-html-container label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .swal2-actions-custom {
            display: flex !important;
            justify-content: space-between !important;
            width: 100% !important;
            gap: 12px !important;
            margin: 20px 0 0 0 !important;
        }

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
        }

        .swal2-cancel-custom:hover {
            background-color: #4b5563 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

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
        }

        .swal2-confirm-custom:hover {
            background-color: #059669 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .swal2-close-custom {
            font-size: 28px !important;
            color: #6b7280 !important;
            transition: all 0.2s !important;
        }

        .swal2-close-custom:hover {
            color: #ef4444 !important;
            transform: scale(1.1);
        }

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

        @media (max-width: 768px) {
            /* Contenedor fijo en la parte superior */
            .swal2-container {
                align-items: flex-start !important;
                padding: 5px 10px 20px 10px !important;
                display: flex !important;
                justify-content: center !important;
            }

            /* Modal posicionado arriba */
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

            /* Resumen items compacto */
            .resumen-item {
                padding: 8px;
                margin-bottom: 6px;
                font-size: 13px;
            }

            /* Body sin scroll */
            body.swal2-shown {
                overflow: hidden !important;
            }
        }
    </style>

    <script>
        // Datos desde backend
        const ubicacionesFull = @json(
            $ubicaciones->mapWithKeys(fn($u) => [
                    $u->id => ['nombre' => $u->nombre_sin_prefijo, 'almacen' => $u->almacen],
                ])) || {};
        const fabricantes = @json($fabricantes->pluck('nombre', 'id')) || {};
        const obras = @json($obras->pluck('obra', 'id')) || {};
        const obraAlm = @json($obraAlmacenes) || {};
        const productosBase = {
            @foreach ($productosBase as $producto)
                {{ $producto->id }}: '{{ strtoupper($producto->tipo) }} Ø{{ $producto->diametro }}{{ $producto->longitud ? ' | ' . $producto->longitud . 'm' : '' }}',
            @endforeach
        };

        class RegistroWizard {
            constructor() {
                this.currentStep = 0;
                this.data = {};
                this.steps = [];
                this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
                this.storageKey = 'registro_wizard_data';
                this.setupSteps();
            }

            loadSavedData() {
                try {
                    const saved = localStorage.getItem(this.storageKey);
                    if (saved) {
                        return JSON.parse(saved);
                    }
                } catch (e) {
                    console.error('Error al cargar datos guardados:', e);
                }
                return null;
            }

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

            clearSavedData() {
                try {
                    localStorage.removeItem(this.storageKey);
                } catch (e) {
                    console.error('Error al limpiar datos:', e);
                }
            }

            setupSteps() {
                this.steps = [
                    {
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
                        name: 'fabricante_id',
                        title: 'Selecciona el fabricante',
                        type: 'select',
                        options: fabricantes,
                        defaultValue: '{{ $ultimoFabricanteId }}',
                        required: true,
                        validator: (value) => value ? null : 'Debes seleccionar un fabricante'
                    },
                    {
                        name: 'producto_base_id',
                        title: 'Producto base',
                        type: 'select',
                        options: productosBase,
                        defaultValue: '{{ $ultimoProductoBaseId }}',
                        required: true,
                        validator: (value) => value ? null : 'Debes seleccionar un producto base'
                    },
                    {
                        name: 'n_colada',
                        title: 'Número de colada',
                        type: 'text',
                        defaultValue: '{{ $ultimaColada }}',
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
                        defaultValue: '{{ $ultimaColada }}',
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
                        name: 'obra_id',
                        title: 'Selecciona almacén',
                        type: 'select',
                        options: obras,
                        defaultValue: '{{ $obraActualId }}',
                        required: true,
                        validator: (value) => value ? null : 'Debes seleccionar un almacén'
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
                const savedData = this.loadSavedData();

                if (savedData && savedData.data && Object.keys(savedData.data).length > 0) {
                    const result = await Swal.fire({
                        title: '¿Continuar registro?',
                        html: '<p style="margin-bottom: 8px;">Hay un registro sin completar</p>' +
                            '<p style="font-size: 13px; color: #6b7280;">Guardado: ' +
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
                        this.currentStep = savedData.currentStep;
                        this.data = savedData.data;
                        await this.showStep();
                        return;
                    } else if (result.isDenied) {
                        this.clearSavedData();
                        this.currentStep = 0;
                        this.data = {};
                        await this.showStep();
                        return;
                    } else {
                        return;
                    }
                }

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
                    showCloseButton: true,
                    confirmButtonText: this.currentStep === activeSteps.length - 1 ? '✅ Finalizar' : 'Siguiente →',
                    cancelButtonText: this.currentStep === 0 ? 'Cancelar' : '← Anterior',
                    reverseButtons: false,
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    customClass: {
                        container: this.isMobile ? 'swal2-mobile' : '',
                        actions: 'swal2-actions-custom',
                        confirmButton: 'swal2-confirm-custom',
                        cancelButton: 'swal2-cancel-custom',
                        closeButton: 'swal2-close-custom'
                    },
                    didOpen: () => {
                        this.setupStepSpecificBehavior(step);
                    },
                    preConfirm: () => {
                        return this.getStepValue(step);
                    }
                };

                const result = await Swal.fire(swalConfig);

                if (result.isConfirmed) {
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

                    if (step.name !== 'resumen') {
                        this.data[step.name] = this.normalizeValue(step, value);
                    }

                    this.saveData();
                    this.currentStep++;
                    await this.showStep();

                } else if (result.isDismissed) {
                    if (result.dismiss === Swal.DismissReason.close || result.dismiss === Swal.DismissReason.esc) {
                        await this.handleCancel();
                        return;
                    }

                    if (this.currentStep > 0) {
                        this.currentStep--;
                        this.saveData();
                        await this.showStep();
                    } else {
                        await this.handleCancel();
                    }
                }
            }

            async handleCancel() {
                const confirmCancel = await Swal.fire({
                    title: '¿Cancelar registro?',
                    html: '<p>Los datos se guardarán para continuar después</p>' +
                        '<p style="font-size: 13px; color: #6b7280; margin-top: 8px;">Podrás recuperarlos la próxima vez que registres</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar y guardar',
                    cancelButtonText: 'Continuar registro',
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
                        text: 'Podrás continuar cuando vuelvas a registrar',
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            container: this.isMobile ? 'swal2-mobile' : ''
                        }
                    });

                    window.location.href = '{{ route("entradas.index") }}';
                } else {
                    await this.showStep();
                }
            }

            buildStepHTML(step, stepNumber, totalSteps) {
                let dotsHTML = '';
                for (let i = 0; i < totalSteps; i++) {
                    let dotClass = 'step-dot';
                    if (i < stepNumber - 1) dotClass += ' completed';
                    if (i === stepNumber - 1) dotClass += ' active';
                    dotsHTML += '<div class="' + dotClass + '"></div>';
                }

                let html = '<div class="step-indicator">' + dotsHTML + '</div>';

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

                let html = '<div style="text-align: left; margin: 0 auto; max-width: 90%;">';
                html += '<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151; font-size: ' + labelSize + ';">';
                html += step.title;
                if (step.required) html += ' <span style="color: #ef4444;">*</span>';
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
                } else {
                    const attrs = step.inputAttributes || {};
                    let attrStr = '';
                    for (const [k, v] of Object.entries(attrs)) {
                        attrStr += ' ' + k + '="' + v + '"';
                    }

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
                const obraId = this.data.obra_id;
                const code = obraAlm[String(obraId)] || 'AL';

                const ubicacionesDelAlmacen = Object.entries(ubicacionesFull)
                    .filter(([id, u]) => u.almacen === code);

                let html = '<div style="text-align: left; margin: 0 auto; max-width: 90%;">';
                html += '<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151; font-size: ' + labelSize + ';">';
                html += 'Ubicación (' + code + ') <span style="color: #ef4444;">*</span>';
                html += '</label>';

                let ubicacionesOptionsHTML = '';
                for (const [id, u] of ubicacionesDelAlmacen) {
                    ubicacionesOptionsHTML += '<option value="' + id + '">' + u.nombre + '</option>';
                }

                html += '<select id="swal-ubicacion" class="swal2-input" style="width:100%; font-size: 16px; margin: 0 0 12px 0;">' +
                    ubicacionesOptionsHTML +
                    '</select>';

                html += '<label style="display:flex;align-items:center;gap:8px;font-size:' + (this.isMobile ? '16px' : '15px') + ';margin-top:8px;cursor:pointer;">' +
                    '<input type="checkbox" id="swal-scan-checkbox" style="transform:scale(' + (this.isMobile ? '1.5' : '1.2') + ');cursor:pointer;">' +
                    '<span>Escanear ubicación en su lugar</span>' +
                    '</label>';

                html += '<input id="swal-ubicacion-scan" type="text" class="swal2-input" ' +
                    'placeholder="Escanea el código de ubicación" style="display:none;width:100%; font-size: 16px; margin-top: 8px;">';

                html += '</div>';

                return html;
            }

            buildResumenHTML() {
                const labelSize = this.isMobile ? '24px' : '22px';

                let html = '<div style="text-align: left; max-width: 400px; margin: 0 auto;">';
                html += '<div style="font-weight: 600; font-size: ' + labelSize + '; color: #374151; margin-bottom: 16px; text-align: center;">';
                html += '📋 Revisar';
                html += '</div>';

                html += '<div class="resumen-item"><strong>Cantidad:</strong><span>' + this.data.paquetes + ' paquete(s)</span></div>';
                html += '<div class="resumen-item"><strong>Código 1:</strong><span>' + this.data.codigo + '</span></div>';
                html += '<div class="resumen-item"><strong>Fabricante:</strong><span>' + fabricantes[this.data.fabricante_id] + '</span></div>';
                html += '<div class="resumen-item"><strong>Producto:</strong><span>' + productosBase[this.data.producto_base_id] + '</span></div>';
                html += '<div class="resumen-item"><strong>Colada 1:</strong><span>' + this.data.n_colada + '</span></div>';
                html += '<div class="resumen-item"><strong>Paquete 1:</strong><span>' + this.data.n_paquete + '</span></div>';

                if (this.data.paquetes === '2') {
                    html += '<div class="resumen-item"><strong>Código 2:</strong><span>' + this.data.codigo_2 + '</span></div>';
                    if (this.data.n_colada_2) {
                        html += '<div class="resumen-item"><strong>Colada 2:</strong><span>' + this.data.n_colada_2 + '</span></div>';
                    }
                    if (this.data.n_paquete_2) {
                        html += '<div class="resumen-item"><strong>Paquete 2:</strong><span>' + this.data.n_paquete_2 + '</span></div>';
                    }
                }

                html += '<div class="resumen-item"><strong>Peso total:</strong><span>' + this.data.peso + ' kg</span></div>';
                html += '<div class="resumen-item"><strong>Almacén:</strong><span>' + obras[this.data.obra_id] + '</span></div>';

                const ubicacionId = this.data.ubicacion;
                const ubicacionNombre = ubicacionesFull[ubicacionId]?.nombre || ubicacionId;
                html += '<div class="resumen-item"><strong>Ubicación:</strong><span>' + ubicacionNombre + '</span></div>';

                html += '</div>';
                return html;
            }

            setupStepSpecificBehavior(step) {
                if (step.name === 'ubicacion') {
                    const ubicacionSelect = document.getElementById('swal-ubicacion');
                    const checkbox = document.getElementById('swal-scan-checkbox');
                    const inputScan = document.getElementById('swal-ubicacion-scan');

                    if (checkbox && ubicacionSelect && inputScan) {
                        checkbox.addEventListener('change', () => {
                            if (checkbox.checked) {
                                ubicacionSelect.style.display = 'none';
                                inputScan.style.display = 'block';
                                inputScan.focus();
                            } else {
                                ubicacionSelect.style.display = 'block';
                                inputScan.style.display = 'none';
                            }
                        });
                    }
                }

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
                        return document.getElementById('swal-ubicacion-scan').value;
                    }
                    return document.getElementById('swal-ubicacion').value;
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
                document.getElementById('cantidad_paquetes_input').value = this.data.paquetes;
                document.getElementById('codigo_input').value = this.data.codigo;
                document.getElementById('fabricante_id_input').value = this.data.fabricante_id;
                document.getElementById('albaran_input').value = 'Entrada manual';
                document.getElementById('producto_base_id_input').value = this.data.producto_base_id;
                document.getElementById('n_colada_input').value = this.data.n_colada;
                document.getElementById('n_paquete_input').value = this.data.n_paquete;
                document.getElementById('peso_input').value = this.data.peso;
                document.getElementById('obra_id_input').value = this.data.obra_id;
                document.getElementById('ubicacion_input').value = this.data.ubicacion;

                if (this.data.paquetes === '2') {
                    document.getElementById('codigo_2_input').value = this.data.codigo_2 || '';
                    document.getElementById('n_colada_2_input').value = this.data.n_colada_2 || '';
                    document.getElementById('n_paquete_2_input').value = this.data.n_paquete_2 || '';
                }

                this.clearSavedData();

                Swal.fire({
                    title: 'Guardando...',
                    text: 'Registrando la entrada',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    customClass: {
                        container: this.isMobile ? 'swal2-mobile' : ''
                    },
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                document.getElementById('inventarioForm').submit();
            }
        }

        let wizard;

        function iniciarRegistro() {
            wizard = new RegistroWizard();
            wizard.start();
        }
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

</x-app-layout>
