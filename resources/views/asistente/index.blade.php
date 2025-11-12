<x-app-layout>
    <style>
        /* Animaciones personalizadas */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .mensaje-enter {
            animation: fadeIn 0.3s ease-out;
        }

        .conversation-enter {
            animation: slideIn 0.2s ease-out;
        }

        /* Tema oscuro */
        [data-theme="dark"] {
            --bg-primary: #1a1a2e;
            --bg-secondary: #16213e;
            --bg-tertiary: #0f3460;
            --text-primary: #e4e4e7;
            --text-secondary: #a1a1aa;
            --border-color: #27272a;
        }

        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        [data-theme="dark"] .glass {
            background: rgba(0, 0, 0, 0.2);
        }

        /* Scrollbar personalizado */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        [data-theme="dark"] .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #4b5563;
        }

        /* Syntax highlighting básico */
        .sql-keyword { color: #0ea5e9; font-weight: 600; }
        .sql-string { color: #10b981; }
        .sql-number { color: #f59e0b; }
        .sql-function { color: #8b5cf6; }
    </style>

    <div class="py-4" :data-theme="tema">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header mejorado con gradiente -->
            <div class="mb-4 flex justify-between items-center p-6 rounded-xl bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 text-white shadow-xl relative overflow-hidden">
                <!-- Efecto de brillo de fondo -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent animate-pulse"></div>

                <div class="relative z-10">
                    <h1 class="text-4xl font-bold flex items-center gap-3">
                        <span class="text-5xl animate-bounce">⚡</span>
                        <div>
                            <span class="block">FERRALLIN</span>
                            <span class="text-xl font-normal text-blue-100">Asistente Virtual Inteligente</span>
                        </div>
                    </h1>
                    <p class="text-blue-100 mt-3 text-lg flex items-center gap-2">
                        <span class="w-3 h-3 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"></span>
                        Potenciado por OpenAI GPT-4 • Listo para ayudarte
                    </p>
                </div>
                <div class="flex gap-3">
                    @if(Auth::user()->esAdminDepartamento())
                    <a href="{{ route('asistente.permisos') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold py-3 px-5 rounded-xl transition duration-200 flex items-center gap-2 border border-white/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Permisos
                    </a>
                    @endif
                </div>
            </div>

            <div id="asistente-app" class="rounded-2xl shadow-2xl overflow-hidden" style="height: calc(100vh - 230px);">
                <asistente-virtual></asistente-virtual>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
        import { createApp } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js'
        import axios from 'https://cdn.jsdelivr.net/npm/axios@1.6.0/+esm'

        // Configurar axios con CSRF token
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

        const AsistenteVirtual = {
            template: `
                <div class="flex h-full" :class="tema === 'dark' ? 'bg-gray-900' : 'bg-white'">
                    <!-- Sidebar de conversaciones MEJORADA -->
                    <div :class="['w-80 border-r flex flex-col', tema === 'dark' ? 'bg-gray-800 border-gray-700' : 'bg-gradient-to-b from-gray-50 to-white border-gray-200']">
                        <!-- Header sidebar con tema toggle -->
                        <div :class="['p-4 border-b', tema === 'dark' ? 'border-gray-700' : 'border-gray-200']">
                            <div class="flex gap-2 mb-3">
                                <button @click="crearNuevaConversacion"
                                        class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-105">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Nuevo
                                </button>
                                <button @click="toggleTema"
                                        :class="['px-4 py-3 rounded-xl transition duration-200 transform hover:scale-105',
                                                tema === 'dark' ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-gray-800 hover:bg-gray-900']"
                                        :title="tema === 'dark' ? 'Modo claro' : 'Modo oscuro'">
                                    <span class="text-xl">@{{ tema === 'dark' ? '☀️' : '🌙' }}</span>
                                </button>
                            </div>

                            <!-- Búsqueda de conversaciones -->
                            <div class="relative">
                                <input v-model="busquedaConversacion"
                                       type="text"
                                       placeholder="Buscar conversaciones..."
                                       :class="['w-full pl-10 pr-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500 transition',
                                               tema === 'dark' ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-white border-gray-300 text-gray-900']">
                                <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Lista de conversaciones -->
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <div v-if="cargandoConversaciones" :class="['p-4 text-center', tema === 'dark' ? 'text-gray-400' : 'text-gray-500']">
                                <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                                <p class="mt-2">Cargando...</p>
                            </div>
                            <div v-else-if="conversacionesFiltradas.length === 0" :class="['p-4 text-center', tema === 'dark' ? 'text-gray-400' : 'text-gray-500']">
                                <p>No hay conversaciones</p>
                            </div>
                            <div v-else>
                                <div v-for="conv in conversacionesFiltradas"
                                     :key="conv.id"
                                     @click="seleccionarConversacion(conv.id)"
                                     :class="['p-4 border-b cursor-pointer transition-all duration-200 conversation-enter',
                                              tema === 'dark' ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-200 hover:bg-blue-50',
                                              conversacionActual === conv.id ? (tema === 'dark' ? 'bg-gray-700 border-l-4 border-l-blue-500' : 'bg-blue-50 border-l-4 border-l-blue-600') : '']">
                                    <div :class="['font-semibold truncate flex items-center gap-2', tema === 'dark' ? 'text-gray-100' : 'text-gray-900']">
                                        <span class="text-lg">💬</span>
                                        @{{ conv.titulo }}
                                    </div>
                                    <div :class="['text-sm mt-1', tema === 'dark' ? 'text-gray-400' : 'text-gray-500']">
                                        @{{ conv.ultima_actividad }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Área de chat MEJORADA -->
                    <div class="flex-1 flex flex-col">
                        <!-- Header mejorado con Ferrallin -->
                        <div :class="['p-5 border-b flex justify-between items-center backdrop-blur-sm',
                                     tema === 'dark' ? 'bg-gray-800/50 border-gray-700' : 'bg-white/50 border-gray-200']">
                            <div class="flex items-center gap-3">
                                <!-- Avatar de Ferrallin -->
                                <div class="relative">
                                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 via-purple-600 to-pink-500 flex items-center justify-center text-white font-bold text-2xl shadow-lg animate-pulse">
                                        ⚡
                                    </div>
                                    <span class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></span>
                                </div>
                                <div>
                                    <h2 :class="['text-xl font-bold flex items-center gap-2', tema === 'dark' ? 'text-white' : 'text-gray-900']">
                                        <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">FERRALLIN</span>
                                        <span :class="['text-sm font-normal', tema === 'dark' ? 'text-gray-400' : 'text-gray-500']">
                                            @{{ conversacionActual ? '• Chat Activo' : '• Esperando' }}
                                        </span>
                                    </h2>
                                    <p :class="['text-sm flex items-center gap-1', tema === 'dark' ? 'text-gray-400' : 'text-gray-500']">
                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                        Asistente Virtual • En línea
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button v-if="conversacionActual"
                                        @click="eliminarConversacionActual"
                                        :class="['p-3 rounded-xl transition-all duration-200 transform hover:scale-110',
                                                tema === 'dark' ? 'text-red-400 hover:bg-red-900/30' : 'text-red-600 hover:bg-red-50']">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Mensajes MEJORADOS -->
                        <div ref="mensajesContainer" :class="['flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar',
                                                              tema === 'dark' ? 'bg-gray-900' : 'bg-gradient-to-br from-gray-50 via-blue-50/30 to-purple-50/30']">
                            <div v-if="!conversacionActual" class="h-full flex flex-col items-center justify-center">
                                <div class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 via-purple-600 to-pink-500 flex items-center justify-center text-white font-bold text-6xl shadow-2xl shadow-purple-500/50 animate-pulse mb-6">
                                    ⚡
                                </div>
                                <h3 :class="['text-3xl font-bold mb-2', tema === 'dark' ? 'text-white' : 'text-gray-800']">
                                    ¡Hola! Soy <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">FERRALLIN</span>
                                </h3>
                                <p :class="['text-lg mb-6 text-center max-w-md', tema === 'dark' ? 'text-gray-400' : 'text-gray-600']">
                                    Tu asistente virtual inteligente. Crea una nueva conversación para comenzar a hacer preguntas sobre el sistema
                                </p>
                                <div class="grid grid-cols-2 gap-3 max-w-2xl">
                                    <div v-for="sugerencia in sugerenciasMostradas"
                                         :key="sugerencia"
                                         class="text-sm text-gray-600 bg-white p-3 rounded-lg border border-gray-200">
                                        "@{{ sugerencia }}"
                                    </div>
                                </div>
                            </div>

                            <div v-else>
                                <div v-if="cargandoMensajes" class="text-center text-gray-500">
                                    Cargando mensajes...
                                </div>
                                <div v-else-if="mensajes.length === 0" class="h-full flex flex-col items-center justify-center -mt-4">
                                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 via-purple-600 to-pink-500 flex items-center justify-center text-white font-bold text-5xl shadow-2xl shadow-purple-500/50 animate-pulse mb-6">
                                        ⚡
                                    </div>
                                    <h3 :class="['text-2xl font-bold mb-2', tema === 'dark' ? 'text-white' : 'text-gray-800']">
                                        ¡Hola! Soy <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">FERRALLIN</span>
                                    </h3>
                                    <p :class="['text-lg mb-6 text-center max-w-md', tema === 'dark' ? 'text-gray-400' : 'text-gray-600']">
                                        Puedo ayudarte a consultar y gestionar información del sistema. Aquí tienes algunas ideas:
                                    </p>
                                    <div class="grid grid-cols-2 gap-3 max-w-2xl">
                                        <button v-for="sugerencia in sugerenciasMostradas"
                                             :key="sugerencia"
                                             @click="usarSugerencia(sugerencia)"
                                             class="text-sm text-left text-gray-700 bg-white p-3 rounded-lg border border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition cursor-pointer">
                                            <span class="text-blue-600 mr-2">💡</span>@{{ sugerencia }}
                                        </button>
                                    </div>
                                </div>
                                <div v-else>
                                    <!-- Mensajes con avatares y diseño moderno -->
                                    <div v-for="mensaje in mensajes"
                                         :key="mensaje.id"
                                         class="flex gap-3 mensaje-enter"
                                         :class="mensaje.role === 'user' ? 'flex-row-reverse' : 'flex-row'">

                                        <!-- Avatar -->
                                        <div class="flex-shrink-0">
                                            <div v-if="mensaje.role === 'user'"
                                                 class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center text-white font-bold shadow-lg">
                                                👤
                                            </div>
                                            <div v-else
                                                 class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 via-purple-600 to-pink-500 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-purple-500/50 animate-pulse">
                                                ⚡
                                            </div>
                                        </div>

                                        <!-- Contenido del mensaje -->
                                        <div class="flex-1 max-w-3xl">
                                            <div :class="['rounded-2xl p-5 shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl',
                                                         mensaje.role === 'user'
                                                            ? 'bg-gradient-to-br from-blue-600 to-purple-600 text-white'
                                                            : (tema === 'dark' ? 'bg-gray-800 text-gray-100 border border-gray-700' : 'bg-white text-gray-900')]">

                                                <!-- Contenido principal con markdown -->
                                                <div class="prose prose-sm max-w-none" v-html="formatearMensaje(mensaje.contenido)"></div>

                                                <!-- SQL ejecutado con syntax highlighting -->
                                                <div v-if="mensaje.metadata && mensaje.metadata.sql"
                                                     :class="['mt-4 rounded-xl overflow-hidden border',
                                                             tema === 'dark' ? 'bg-gray-900 border-gray-700' : 'bg-gray-100 border-gray-300']">
                                                    <div :class="['flex justify-between items-center px-4 py-2 border-b',
                                                                 tema === 'dark' ? 'bg-gray-800 border-gray-700' : 'bg-gray-200 border-gray-300']">
                                                        <span :class="['text-xs font-bold flex items-center gap-2',
                                                                      tema === 'dark' ? 'text-gray-300' : 'text-gray-700']">
                                                            <span class="text-lg">🔍</span>
                                                            SQL Ejecutado
                                                            <span :class="['px-2 py-0.5 rounded-full text-xs',
                                                                          mensaje.metadata.tipo_operacion === 'SELECT' ? 'bg-blue-500 text-white' :
                                                                          mensaje.metadata.tipo_operacion === 'INSERT' ? 'bg-green-500 text-white' :
                                                                          mensaje.metadata.tipo_operacion === 'UPDATE' ? 'bg-yellow-500 text-white' :
                                                                          'bg-red-500 text-white']">
                                                                @{{ mensaje.metadata.tipo_operacion || 'SELECT' }}
                                                            </span>
                                                        </span>
                                                        <button @click="copiarTexto(mensaje.metadata.sql)"
                                                                :class="['text-xs font-semibold px-3 py-1 rounded-lg transition-all duration-200 flex items-center gap-1',
                                                                        tema === 'dark' ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-blue-500 hover:bg-blue-600 text-white']"
                                                                title="Copiar SQL">
                                                            📋 Copiar
                                                        </button>
                                                    </div>
                                                    <div :class="['p-4 overflow-x-auto', tema === 'dark' ? 'bg-gray-900' : 'bg-gray-50']">
                                                        <pre :class="['text-xs font-mono', tema === 'dark' ? 'text-gray-300' : 'text-gray-800']" v-html="highlightSQL(mensaje.metadata.sql)"></pre>
                                                    </div>
                                                    <div :class="['px-4 py-2 text-xs border-t flex gap-4',
                                                                 tema === 'dark' ? 'bg-gray-800 border-gray-700 text-gray-400' : 'bg-gray-100 border-gray-300 text-gray-600']">
                                                        <span>📊 Filas afectadas: <strong>@{{ mensaje.metadata.filas_afectadas || 0 }}</strong></span>
                                                    </div>
                                                </div>

                                                <!-- Timestamp -->
                                                <div :class="['text-xs mt-3 flex items-center gap-1',
                                                             mensaje.role === 'user' ? 'text-blue-100' : (tema === 'dark' ? 'text-gray-500' : 'text-gray-500')]">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    @{{ mensaje.created_at }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Indicador de escritura mejorado -->
                                <div v-if="escribiendo" class="flex gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 via-purple-600 to-pink-500 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-purple-500/50 animate-pulse">
                                            ⚡
                                        </div>
                                    </div>
                                    <div :class="['rounded-2xl p-5 shadow-lg backdrop-blur-sm',
                                                 tema === 'dark' ? 'bg-gray-800 border border-gray-700' : 'bg-white']">
                                        <div class="flex items-center gap-3">
                                            <div class="flex space-x-2">
                                                <div class="w-3 h-3 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full animate-bounce"></div>
                                                <div class="w-3 h-3 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                                <div class="w-3 h-3 bg-gradient-to-r from-pink-500 to-blue-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                                            </div>
                                            <span :class="['text-sm font-medium', tema === 'dark' ? 'text-gray-300' : 'text-gray-600']">
                                                <strong>Ferrallin</strong> está analizando tu consulta...
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input de mensaje MEJORADO -->
                        <div :class="['p-5 border-t backdrop-blur-sm',
                                     tema === 'dark' ? 'bg-gray-800/50 border-gray-700' : 'bg-white/50 border-gray-200']">
                            <div v-if="!conversacionActual" :class="['text-center py-8', tema === 'dark' ? 'text-gray-400' : 'text-gray-500']">
                                <p class="text-lg">💬 Crea o selecciona una conversación para comenzar</p>
                            </div>
                            <div v-else>
                                <div class="flex gap-3">
                                    <div class="flex-1 relative">
                                        <textarea v-model="mensajeNuevo"
                                                  @keydown.ctrl.enter="enviarMensaje"
                                                  @keydown.meta.enter="enviarMensaje"
                                                  :disabled="enviando"
                                                  placeholder="Escribe tu pregunta aquí... (Prueba: /help para ver comandos)"
                                                  :class="['w-full resize-none rounded-xl border-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 px-4 py-3',
                                                          tema === 'dark' ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-white border-gray-300 text-gray-900']"
                                                  rows="3"></textarea>
                                    </div>
                                    <button @click="enviarMensaje"
                                            :disabled="!mensajeNuevo.trim() || enviando"
                                            class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold px-6 rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:scale-105 disabled:transform-none flex items-center justify-center">
                                        <svg v-if="!enviando" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        <div v-else class="animate-spin w-6 h-6 border-2 border-white border-t-transparent rounded-full"></div>
                                    </button>
                                </div>

                                <!-- Atajos de teclado mejorados -->
                                <div :class="['mt-3 flex justify-between items-center text-xs',
                                             tema === 'dark' ? 'text-gray-400' : 'text-gray-500']">
                                    <div class="flex gap-4 flex-wrap">
                                        <span class="flex items-center gap-1">
                                            ⌨️ <kbd :class="['px-2 py-1 rounded font-mono', tema === 'dark' ? 'bg-gray-700 border border-gray-600' : 'bg-gray-100 border border-gray-300']">Ctrl+Enter</kbd> Enviar
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <kbd :class="['px-2 py-1 rounded font-mono', tema === 'dark' ? 'bg-gray-700 border border-gray-600' : 'bg-gray-100 border border-gray-300']">Esc</kbd> Limpiar
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <kbd :class="['px-2 py-1 rounded font-mono', tema === 'dark' ? 'bg-gray-700 border border-gray-600' : 'bg-gray-100 border border-gray-300']">Ctrl+N</kbd> Nueva
                                        </span>
                                    </div>
                                    <span :class="['font-medium', tema === 'dark' ? 'text-blue-400' : 'text-blue-600']">
                                        💡 Escribe <strong>/help</strong> para comandos rápidos
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            data() {
                return {
                    conversaciones: [],
                    conversacionActual: null,
                    mensajes: [],
                    mensajeNuevo: '',
                    cargandoConversaciones: false,
                    cargandoMensajes: false,
                    enviando: false,
                    escribiendo: false,
                    sugerencias: [],
                    sugerenciasMostradas: [],
                    tema: localStorage.getItem('tema-asistente') || 'light',
                    busquedaConversacion: ''
                }
            },
            computed: {
                conversacionesFiltradas() {
                    if (!this.busquedaConversacion.trim()) {
                        return this.conversaciones
                    }
                    const busqueda = this.busquedaConversacion.toLowerCase()
                    return this.conversaciones.filter(conv =>
                        conv.titulo.toLowerCase().includes(busqueda)
                    )
                }
            },
            async mounted() {
                await this.cargarConversaciones()
                await this.cargarSugerencias()

                // Crear automáticamente una nueva conversación al entrar
                await this.crearNuevaConversacion()

                // Agregar atajos de teclado
                document.addEventListener('keydown', this.manejarAtajos)
            },
            beforeUnmount() {
                // Limpiar event listeners
                document.removeEventListener('keydown', this.manejarAtajos)
            },
            methods: {
                toggleTema() {
                    this.tema = this.tema === 'light' ? 'dark' : 'light'
                    localStorage.setItem('tema-asistente', this.tema)
                    // Aplicar tema al body también
                    document.body.setAttribute('data-theme', this.tema)
                },
                highlightSQL(sql) {
                    if (!sql) return ''

                    // Keywords SQL
                    const keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER',
                                     'ON', 'AND', 'OR', 'ORDER', 'BY', 'GROUP', 'HAVING', 'LIMIT',
                                     'INSERT', 'INTO', 'VALUES', 'UPDATE', 'SET', 'DELETE', 'CREATE',
                                     'TABLE', 'ALTER', 'DROP', 'AS', 'DISTINCT', 'COUNT', 'SUM', 'AVG',
                                     'MAX', 'MIN', 'LIKE', 'IN', 'BETWEEN', 'IS', 'NULL', 'NOT']

                    let highlighted = sql

                    // Highlight keywords
                    keywords.forEach(keyword => {
                        const regex = new RegExp('\\b' + keyword + '\\b', 'gi')
                        highlighted = highlighted.replace(regex, '<span class="sql-keyword">' + keyword.toUpperCase() + '</span>')
                    })

                    // Highlight strings
                    highlighted = highlighted.replace(/'([^']*)'/g, '<span class="sql-string">\'$1\'</span>')
                    highlighted = highlighted.replace(/"([^"]*)"/g, '<span class="sql-string">"$1"</span>')

                    // Highlight numbers
                    highlighted = highlighted.replace(/\b(\d+)\b/g, '<span class="sql-number">$1</span>')

                    // Highlight functions
                    highlighted = highlighted.replace(/\b([A-Z_]+)\(/g, '<span class="sql-function">$1</span>(')

                    return highlighted
                },
                async cargarConversaciones() {
                    this.cargandoConversaciones = true
                    try {
                        const response = await axios.get('/api/asistente/conversaciones')
                        this.conversaciones = response.data.conversaciones
                    } catch (error) {
                        console.error('Error cargando conversaciones:', error)
                        this.mostrarError('Error al cargar las conversaciones')
                    } finally {
                        this.cargandoConversaciones = false
                    }
                },
                async cargarSugerencias() {
                    try {
                        const response = await axios.get('/api/asistente/sugerencias')
                        this.sugerencias = response.data.sugerencias
                        this.sugerenciasMostradas = this.sugerencias.slice(0, 6)
                    } catch (error) {
                        console.error('Error cargando sugerencias:', error)
                    }
                },
                async crearNuevaConversacion() {
                    try {
                        const response = await axios.post('/api/asistente/conversaciones')
                        const nuevaConv = response.data.conversacion
                        this.conversaciones.unshift(nuevaConv)
                        await this.seleccionarConversacion(nuevaConv.id)
                    } catch (error) {
                        console.error('Error creando conversación:', error)
                        this.mostrarError('Error al crear la conversación')
                    }
                },
                async seleccionarConversacion(id) {
                    this.conversacionActual = id
                    this.mensajes = []
                    this.cargandoMensajes = true
                    try {
                        const response = await axios.get(`/api/asistente/conversaciones/${id}/mensajes`)
                        this.mensajes = response.data.mensajes
                        await this.$nextTick()
                        this.scrollToBottom()
                    } catch (error) {
                        console.error('Error cargando mensajes:', error)
                        this.mostrarError('Error al cargar los mensajes')
                    } finally {
                        this.cargandoMensajes = false
                    }
                },
                async enviarMensaje() {
                    if (!this.mensajeNuevo.trim() || this.enviando) return

                    const mensaje = this.mensajeNuevo.trim()
                    this.mensajeNuevo = ''
                    this.enviando = true
                    this.escribiendo = true

                    // Agregar mensaje del usuario inmediatamente
                    this.mensajes.push({
                        id: Date.now(),
                        role: 'user',
                        contenido: mensaje,
                        created_at: new Date().toLocaleString('es-ES')
                    })

                    await this.$nextTick()
                    this.scrollToBottom()

                    try {
                        const response = await axios.post('/api/asistente/mensaje', {
                            conversacion_id: this.conversacionActual,
                            mensaje: mensaje
                        })

                        // Agregar respuesta del asistente
                        this.mensajes.push(response.data.mensaje)
                        await this.cargarConversaciones() // Actualizar lista
                        await this.$nextTick()
                        this.scrollToBottom()
                    } catch (error) {
                        console.error('Error enviando mensaje:', error)
                        this.mostrarError('Error al enviar el mensaje')
                    } finally {
                        this.enviando = false
                        this.escribiendo = false
                    }
                },
                async eliminarConversacionActual() {
                    if (!confirm('¿Estás seguro de que quieres eliminar esta conversación?')) return

                    try {
                        await axios.delete(`/api/asistente/conversaciones/${this.conversacionActual}`)
                        this.conversaciones = this.conversaciones.filter(c => c.id !== this.conversacionActual)
                        this.conversacionActual = null
                        this.mensajes = []
                    } catch (error) {
                        console.error('Error eliminando conversación:', error)
                        this.mostrarError('Error al eliminar la conversación')
                    }
                },
                formatearMensaje(contenido) {
                    if (!contenido) return ''

                    let html = contenido

                    // Escapar HTML existente
                    const escapeHtml = (text) => {
                        const map = {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;'
                        }
                        return text.replace(/[&<>]/g, m => map[m])
                    }

                    // Preservar código en bloques
                    const codeBlocks = []
                    html = html.replace(/```(\w+)?\n([\s\S]*?)```/g, (match, lang, code) => {
                        const placeholder = `___CODE_BLOCK_${codeBlocks.length}___`
                        codeBlocks.push(`<div class="my-3 rounded-lg overflow-hidden border ${this.tema === 'dark' ? 'border-gray-700' : 'border-gray-300'}">
                            <div class="${this.tema === 'dark' ? 'bg-gray-800 text-gray-300' : 'bg-gray-200 text-gray-700'} px-3 py-1 text-xs font-semibold border-b ${this.tema === 'dark' ? 'border-gray-700' : 'border-gray-300'}">
                                ${lang ? '📄 ' + lang.toUpperCase() : 'CODE'}
                            </div>
                            <pre class="${this.tema === 'dark' ? 'bg-gray-900 text-gray-300' : 'bg-gray-50 text-gray-800'} p-3 overflow-x-auto"><code>${escapeHtml(code.trim())}</code></pre>
                        </div>`)
                        return placeholder
                    })

                    // Títulos
                    html = html.replace(/^### (.*?)$/gm, '<h3 class="text-lg font-bold mt-4 mb-2">$1</h3>')
                    html = html.replace(/^## (.*?)$/gm, '<h2 class="text-xl font-bold mt-4 mb-2">$1</h2>')
                    html = html.replace(/^# (.*?)$/gm, '<h1 class="text-2xl font-bold mt-4 mb-2">$1</h1>')

                    // Bold e itálica
                    html = html.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>')
                    html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold">$1</strong>')
                    html = html.replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')

                    // Código inline
                    html = html.replace(/`(.*?)`/g, `<code class="px-2 py-0.5 rounded font-mono text-sm ${this.tema === 'dark' ? 'bg-gray-700 text-blue-300' : 'bg-gray-100 text-blue-600'}">$1</code>`)

                    // Links
                    html = html.replace(/\[([^\]]+)\]\(([^\)]+)\)/g, '<a href="$2" class="text-blue-500 hover:text-blue-700 underline" target="_blank">$1</a>')

                    // Listas numeradas
                    html = html.replace(/^\d+\.\s+(.*?)$/gm, '<li class="ml-4">$1</li>')

                    // Listas no numeradas
                    html = html.replace(/^[-•]\s+(.*?)$/gm, '<li class="ml-4">$1</li>')

                    // Agrupar listas
                    html = html.replace(/(<li class="ml-4">.*<\/li>\n?)+/g, match => {
                        return `<ul class="list-disc pl-5 my-2 space-y-1">${match}</ul>`
                    })

                    // Saltos de línea
                    html = html.replace(/\n\n/g, '</p><p class="my-2">')
                    html = html.replace(/\n/g, '<br>')

                    // Restaurar bloques de código
                    codeBlocks.forEach((block, i) => {
                        html = html.replace(`___CODE_BLOCK_${i}___`, block)
                    })

                    // Envolver en párrafo si no hay uno
                    if (!html.startsWith('<')) {
                        html = `<p>${html}</p>`
                    }

                    return html
                },
                scrollToBottom() {
                    const container = this.$refs.mensajesContainer
                    if (container) {
                        container.scrollTop = container.scrollHeight
                    }
                },
                usarSugerencia(sugerencia) {
                    this.mensajeNuevo = sugerencia
                    this.enviarMensaje()
                },
                copiarTexto(texto) {
                    navigator.clipboard.writeText(texto).then(() => {
                        // Mostrar notificación de éxito
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Copiado!',
                                text: 'SQL copiado al portapapeles',
                                timer: 1500,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            })
                        }
                    }).catch(err => {
                        console.error('Error copiando texto:', err)
                    })
                },
                manejarAtajos(e) {
                    // Ctrl/Cmd + Enter para enviar mensaje
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault()
                        if (this.mensajeNuevo.trim() && this.conversacionActual) {
                            this.enviarMensaje()
                        }
                    }
                    // Esc para limpiar el input
                    if (e.key === 'Escape') {
                        this.mensajeNuevo = ''
                        document.querySelector('textarea')?.blur()
                    }
                    // Ctrl/Cmd + N para nueva conversación
                    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                        e.preventDefault()
                        this.crearNuevaConversacion()
                    }
                },
                mostrarError(mensaje) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: mensaje
                        })
                    } else {
                        alert(mensaje)
                    }
                }
            }
        }

        const app = createApp({
            components: {
                'asistente-virtual': AsistenteVirtual
            }
        })

        app.mount('#asistente-app')
    </script>
    @endpush
</x-app-layout>
