<x-app-layout>
    <x-slot name="title">Mi Perfil</x-slot>

    <div class="container mx-auto px-4 py-6">
        @if (auth()->user()->rol === 'operario')
            {{-- ✅ FICHA PARA OPERARIO --}}
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-3xl mx-auto mb-6 border border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Mi Perfil</h2>

                <div class="flex gap-6 items-center border-b pb-4 mb-4">
                    {{-- Avatar --}}
                    <div class="relative flex-shrink-0 mx-auto md:mx-0">
                        @if (auth()->user()->ruta_imagen)
                            <img src="{{ auth()->user()->ruta_imagen }}" alt="Foto de perfil"
                                class="w-24 h-24 rounded-full object-cover ring-4 ring-blue-500 shadow-lg">
                        @else
                            <div
                                class="w-24 h-24 bg-gradient-to-br from-gray-300 to-gray-400 rounded-full flex items-center justify-center text-3xl font-bold text-gray-700 shadow-inner ring-4 ring-blue-500">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif

                        <!-- Botón cambiar foto sobre la imagen -->
                        <form method="POST" action="{{ route('usuario.subirImagen') }}" enctype="multipart/form-data"
                            class="absolute bottom-0 right-0">
                            @csrf
                            <label
                                class="flex items-center justify-center bg-white border border-gray-300 rounded-full p-1 shadow-md cursor-pointer hover:bg-gray-50">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M4 3a2 2 0 00-2 2v3.586A1.5 1.5 0 003.5 10H4v6a2 2 0 002 2h8a2 2 0 002-2v-6h.5A1.5 1.5 0 0018 8.586V5a2 2 0 00-2-2H4zm3 3a1 1 0 112 0 1 1 0 01-2 0zm2 4a2 2 0 114 0 2 2 0 01-4 0z" />
                                </svg>
                                <input type="file" name="imagen" accept="image/*" class="hidden"
                                    onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>
                    {{-- Datos básicos --}}
                    <div>
                        <p class="text-lg font-semibold">{{ $user->nombre_completo }}</p>
                        <p class="text-sm text-gray-500">Operario</p>
                        <p>📧 {{ $user->email }}</p>
                        <p>📞 {{ $user->movil_empresa ?? ($user->movil_personal ?? 'Sin teléfono') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <p><strong>Empresa:</strong> {{ $user->empresa->nombre ?? 'N/A' }}</p>
                    <p><strong>Categoría:</strong> {{ $user->categoria->nombre ?? 'N/A' }}</p>
                    <p><strong>Máquina:</strong> {{ $user->maquina->nombre ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg mb-4">
                    <p><strong>Vacaciones asignadas:</strong> {{ $resumen['diasVacaciones'] }}</p>
                    <p><strong>Faltas injustificadas:</strong> {{ $resumen['faltasInjustificadas'] }}</p>
                    <p><strong>Faltas justificadas:</strong> {{ $resumen['faltasJustificadas'] }}</p>
                    <p><strong>Días de baja:</strong> {{ $resumen['diasBaja'] }}</p>
                </div>

                {{-- 📥 Descargar nóminas --}}
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">📥 Descargar mis nóminas</h3>
                    <form action="{{ route('nominas.descargarMes') }}" method="GET"
                        class="flex flex-wrap items-center gap-3 max-w-md">
                        @csrf
                        <input type="month" name="mes_anio" id="mes_anio" required
                            class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                            📥
                        </button>
                    </form>
                </div>
            </div>
        @elseif(auth()->user()->rol === 'oficina' || auth()->user()->rol === 'admin')
            {{-- ✅ FICHA PARA OFICINA --}}
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-3xl mx-auto mb-6 border border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Mi Perfil</h2>

                <div class="flex gap-6 items-center border-b pb-4 mb-4">
                    {{-- Avatar --}}

                    <div class="relative flex-shrink-0 mx-auto md:mx-0">
                        @if ($user->ruta_imagen)
                            <img src="{{ $user->ruta_imagen }}" alt="Foto de perfil"
                                class="w-24 h-24 rounded-full object-cover ring-4 ring-blue-500 shadow-lg">
                        @else
                            <div
                                class="w-24 h-24 bg-gradient-to-br from-gray-300 to-gray-400 rounded-full flex items-center justify-center text-3xl font-bold text-gray-700 shadow-inner ring-4 ring-blue-500">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif

                        <!-- Botón cambiar foto sobre la imagen -->
                        <form method="POST" action="{{ route('usuario.subirImagen') }}" enctype="multipart/form-data"
                            class="absolute bottom-0 right-0">
                            @csrf
                            <label
                                class="flex items-center justify-center bg-white border border-gray-300 rounded-full p-1 shadow-md cursor-pointer hover:bg-gray-50">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M4 3a2 2 0 00-2 2v3.586A1.5 1.5 0 003.5 10H4v6a2 2 0 002 2h8a2 2 0 002-2v-6h.5A1.5 1.5 0 0018 8.586V5a2 2 0 00-2-2H4zm3 3a1 1 0 112 0 1 1 0 01-2 0zm2 4a2 2 0 114 0 2 2 0 01-4 0z" />
                                </svg>
                                <input type="file" name="imagen" accept="image/*" class="hidden"
                                    onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>
                    {{-- Datos principales --}}
                    <div>
                        <p class="text-lg font-semibold">{{ $user->nombre_completo }}</p>
                        <div class="mt-1 flex flex-wrap gap-2">
                            @forelse($user->departamentos as $dep)
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $dep->nombre }}

                                </span>
                            @empty
                                <span class="text-sm text-gray-500 italic">No asignado a ningún departamento</span>
                            @endforelse
                        </div>
                        <p>📧 {{ $user->email }}</p>
                        <div class="mt-2 space-y-1 text-sm text-gray-700">
                            @if ($user->movil_empresa)
                                <p>📞 <span class="font-semibold">Empresa:</span> {{ $user->movil_empresa }}</p>
                            @endif

                            @if ($user->movil_personal)
                                <p>📱 <span class="font-semibold">Personal:</span> {{ $user->movil_personal }}</p>
                            @endif

                            @if (!$user->movil_empresa && !$user->movil_personal)
                                <p class="italic text-gray-500">Sin teléfonos registrados</p>
                            @endif
                        </div>

                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <p><strong>Empresa:</strong> {{ $user->empresa->nombre ?? 'N/A' }}</p>
                    <p><strong>Categoría:</strong> {{ $user->categoria->nombre ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg mb-4">
                    <p><strong>Vacaciones asignadas:</strong> {{ $resumen['diasVacaciones'] }}</p>
                    <p><strong>Faltas injustificadas:</strong> {{ $resumen['faltasInjustificadas'] }}</p>
                    <p><strong>Faltas justificadas:</strong> {{ $resumen['faltasJustificadas'] }}</p>
                    <p><strong>Días de baja:</strong> {{ $resumen['diasBaja'] }}</p>
                </div>
                {{-- 📥 Descargar nóminas --}}
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">📥 Descargar mis nóminas</h3>
                    <form action="{{ route('nominas.descargarMes') }}" method="GET"
                        class="flex flex-wrap items-center gap-3 max-w-md">
                        @csrf
                        <input type="month" name="mes_anio" id="mes_anio" required
                            class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded shadow transition">
                            📥
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

</x-app-layout>
