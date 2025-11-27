<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Incorporación - {{ $incorporacion->empresa_nombre }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .file-input-wrapper {
            position: relative;
        }
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        /* Estilos mejorados para inputs */
        .input-styled {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #374151;
            background-color: #fff;
            transition: all 0.2s ease;
        }
        .input-styled::placeholder {
            color: #9ca3af;
        }
        .input-styled:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .input-styled:hover:not(:focus) {
            border-color: #9ca3af;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <!-- Cabecera -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Formulario de Incorporación</h1>
            <p class="text-gray-600 mt-2">{{ $incorporacion->empresa_nombre }}</p>
            @if($incorporacion->puesto)
                <p class="text-sm text-gray-500">Puesto: {{ $incorporacion->puesto }}</p>
            @endif
        </div>

        <!-- Mensajes -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulario -->
        <form method="POST" action="{{ route('incorporacion.publica.store', $incorporacion->token) }}"
            enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Sección: Datos Personales -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full mr-3 text-sm font-bold">1</span>
                    Datos Personales
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- DNI -->
                    <div>
                        <label for="dni" class="block text-sm font-medium text-gray-700 mb-1.5">
                            DNI / NIE <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="dni" name="dni" value="{{ old('dni') }}"
                            placeholder="12345678A" maxlength="9"
                            class="input-styled uppercase"
                            required>
                        <p class="text-xs text-gray-500 mt-1.5">8 números + letra (o NIE)</p>
                    </div>

                    <!-- Número afiliación -->
                    <div>
                        <label for="numero_afiliacion_ss" class="block text-sm font-medium text-gray-700 mb-1.5">
                            N. Afiliación Seg. Social <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="numero_afiliacion_ss" name="numero_afiliacion_ss" value="{{ old('numero_afiliacion_ss') }}"
                            placeholder="123456789012" maxlength="12"
                            class="input-styled"
                            required>
                        <p class="text-xs text-gray-500 mt-1.5">12 dígitos (tarjeta sanitaria antigua)</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Correo electrónico <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $incorporacion->email_provisional) }}"
                            placeholder="tu@email.com"
                            class="input-styled"
                            required>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Teléfono <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', $incorporacion->telefono_provisional) }}"
                            placeholder="612345678" maxlength="9"
                            class="input-styled"
                            required>
                    </div>

                    <!-- Certificado bancario -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Certificado titularidad cuenta bancaria <span class="text-red-500">*</span>
                        </label>
                        <div class="file-input-wrapper">
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition cursor-pointer"
                                id="dropzone-bancario">
                                <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-sm text-gray-600" id="text-bancario">Haz clic o arrastra el archivo aquí</p>
                                <p class="text-xs text-gray-400 mt-1">PDF, JPG o PNG (máx. 5MB)</p>
                            </div>
                            <input type="file" name="certificado_bancario" accept=".pdf,.jpg,.jpeg,.png" required
                                onchange="updateFileName(this, 'text-bancario')">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección: Formación -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full mr-3 text-sm font-bold">2</span>
                    Documentación de Formación
                </h2>

                @if($incorporacion->empresa_destino === 'hpr_servicios')
                    <!-- HPR Servicios -->
                    <p class="text-sm text-gray-600 mb-4">
                        Como trabajador de <strong>HPR Servicios</strong> en obra, necesitamos los siguientes certificados:
                    </p>

                    <div class="space-y-4">
                        <!-- Curso 20H -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Curso 20H modalidad genérica <span class="text-gray-400 text-xs">(opcional)</span>
                            </label>
                            <p class="text-xs text-gray-500 mb-2">Albañilería, ferralla, encofrador, etc.</p>
                            <div class="file-input-wrapper">
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition cursor-pointer">
                                    <p class="text-sm text-gray-600" id="text-20h">Seleccionar archivo...</p>
                                </div>
                                <input type="file" name="formacion_curso_20h" accept=".pdf,.jpg,.jpeg,.png"
                                    onchange="updateFileName(this, 'text-20h')">
                            </div>
                        </div>

                        <!-- Curso 6H -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Curso 6H modalidad específica (FERRALLA) <span class="text-gray-400 text-xs">(opcional)</span>
                            </label>
                            <div class="file-input-wrapper">
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition cursor-pointer">
                                    <p class="text-sm text-gray-600" id="text-6h">Seleccionar archivo...</p>
                                </div>
                                <input type="file" name="formacion_curso_6h" accept=".pdf,.jpg,.jpeg,.png"
                                    onchange="updateFileName(this, 'text-6h')">
                            </div>
                        </div>

                        <!-- Otros cursos -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Otros cursos (opcional)
                            </label>
                            <p class="text-xs text-gray-500 mb-2">Puente grúa, aparatos elevadores, espacios confinados, carretilla...</p>
                            <div id="otros-cursos-container" class="space-y-3">
                                <div class="flex gap-2 items-start otro-curso">
                                    <input type="text" name="formacion_otros_nombres[]" placeholder="Nombre del curso"
                                        class="input-styled flex-1">
                                    <div class="file-input-wrapper flex-1">
                                        <div class="border border-gray-300 rounded-lg p-2.5 text-center cursor-pointer text-sm hover:border-gray-400 transition">
                                            <span class="text-gray-500" id="text-otro-0">Seleccionar...</span>
                                        </div>
                                        <input type="file" name="formacion_otros[]" accept=".pdf,.jpg,.jpeg,.png"
                                            onchange="updateFileName(this, 'text-otro-0')">
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="agregarOtroCurso()" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                + Añadir otro curso
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Hierros Paco Reyes -->
                    <p class="text-sm text-gray-600 mb-4">
                        Como trabajador de <strong>Hierros Paco Reyes</strong>, necesitamos los siguientes certificados:
                    </p>

                    <div class="space-y-4">
                        <!-- Formación genérica -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Formación genérica del puesto <span class="text-gray-400 text-xs">(opcional)</span>
                            </label>
                            <p class="text-xs text-gray-500 mb-2">Estructuras metálicas genérico</p>
                            <div class="file-input-wrapper">
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition cursor-pointer">
                                    <p class="text-sm text-gray-600" id="text-generica">Seleccionar archivo...</p>
                                </div>
                                <input type="file" name="formacion_generica" accept=".pdf,.jpg,.jpeg,.png"
                                    onchange="updateFileName(this, 'text-generica')">
                            </div>
                        </div>

                        <!-- Formación específica -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Formación específica del puesto <span class="text-gray-400 text-xs">(opcional)</span>
                            </label>
                            <p class="text-xs text-gray-500 mb-2">Soldador, puente grúa, etc.</p>
                            <div class="file-input-wrapper">
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition cursor-pointer">
                                    <p class="text-sm text-gray-600" id="text-especifica">Seleccionar archivo...</p>
                                </div>
                                <input type="file" name="formacion_especifica" accept=".pdf,.jpg,.jpeg,.png"
                                    onchange="updateFileName(this, 'text-especifica')">
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Aviso RGPD -->
            <div class="bg-gray-50 border rounded-lg p-4 text-sm text-gray-600">
                <p>
                    Al enviar este formulario, aceptas que tus datos personales sean tratados para gestionar tu proceso de incorporación
                    de acuerdo con nuestra política de privacidad. Los documentos enviados serán almacenados de forma segura.
                </p>
            </div>

            <!-- Botón enviar -->
            <button type="submit"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition text-lg">
                Enviar documentación
            </button>
        </form>

        <!-- Footer -->
        <p class="text-center text-sm text-gray-500 mt-8">
            {{ $incorporacion->empresa_nombre }} &middot; Formulario de incorporación
        </p>
    </div>

    <script>
        function updateFileName(input, textId) {
            const text = document.getElementById(textId);
            if (input.files.length > 0) {
                text.textContent = input.files[0].name;
                text.classList.add('text-green-600', 'font-medium');
                text.classList.remove('text-gray-600');
            }
        }

        let otrosCursosCount = 1;
        function agregarOtroCurso() {
            const container = document.getElementById('otros-cursos-container');
            const div = document.createElement('div');
            div.className = 'flex gap-2 items-start otro-curso';
            div.innerHTML = `
                <input type="text" name="formacion_otros_nombres[]" placeholder="Nombre del curso"
                    class="input-styled flex-1">
                <div class="file-input-wrapper flex-1">
                    <div class="border border-gray-300 rounded-lg p-2.5 text-center cursor-pointer text-sm hover:border-gray-400 transition">
                        <span class="text-gray-500" id="text-otro-${otrosCursosCount}">Seleccionar...</span>
                    </div>
                    <input type="file" name="formacion_otros[]" accept=".pdf,.jpg,.jpeg,.png"
                        onchange="updateFileName(this, 'text-otro-${otrosCursosCount}')">
                </div>
                <button type="button" onclick="this.closest('.otro-curso').remove()" class="text-red-500 hover:text-red-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            container.appendChild(div);
            otrosCursosCount++;
        }

        // Convertir DNI a mayúsculas
        document.getElementById('dni').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>
