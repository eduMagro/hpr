<x-app-layout>
    <x-slot name="title">Centro de Ayuda</x-slot>

    <div class="max-w-6xl mx-auto px-4 py-6" x-data="ayudaApp()">

        <!-- Header FERRALLIN -->
        <div class="header-ferrallin">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="header-title">
                        <img src="{{ asset('imagenes/iconos/asistente-sin-fondo.png') }}" alt="Ferrallin" class="header-icon">
                        <div class="header-text">
                            <span class="header-name">FERRALLIN</span>
                            <span class="header-subtitle">Asistente de Ayuda Inteligente (RAG)</span>
                        </div>
                    </h1>
                    <p class="header-status" x-show="!isTyping">
                        <span class="status-dot"></span>
                        <span class="status-text">Potenciado por IA + Base de Conocimiento</span>
                    </p>
                    <p class="header-typing" x-show="isTyping" style="display: none;">
                        <svg class="typing-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span class="typing-text">Buscando información...</span>
                    </p>
                </div>
                <div class="header-right flex gap-2">
                    @if(auth()->user()->esAdminDepartamento())
                    <button @click="showAdmin = !showAdmin" class="btn-admin" :class="showAdmin ? 'bg-yellow-600' : ''">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="btn-text">Admin</span>
                    </button>
                    @endif
                    <button @click="clearChat()" class="btn-clear">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span class="btn-text">Limpiar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- PANEL ADMIN (colapsable) -->
        <div x-show="showAdmin" x-collapse class="mb-4">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gray-800 text-white px-4 py-3 flex justify-between items-center">
                    <h2 class="font-bold flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Gestionar Base de Conocimiento
                    </h2>
                    <div class="flex gap-2">
                        <button @click="cargarDocumentos()" class="text-xs bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded">
                            Recargar
                        </button>
                        <button @click="regenerarTodos()" class="text-xs bg-blue-600 hover:bg-blue-500 px-3 py-1 rounded">
                            Regenerar Embeddings
                        </button>
                        <button @click="abrirModalCrear()" class="text-xs bg-green-600 hover:bg-green-500 px-3 py-1 rounded flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nuevo Documento
                        </button>
                    </div>
                </div>

                <!-- Tabla de documentos -->
                <div class="overflow-x-auto max-h-96">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Categoría</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Título</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 hidden md:table-cell">Contenido</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600">Estado</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600">Embedding</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="doc in documentos" :key="doc.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" x-text="doc.categoria"></span>
                                    </td>
                                    <td class="px-3 py-2 font-medium" x-text="doc.titulo"></td>
                                    <td class="px-3 py-2 text-gray-500 text-xs truncate max-w-xs hidden md:table-cell" x-text="doc.contenido.substring(0, 80) + '...'"></td>
                                    <td class="px-3 py-2 text-center">
                                        <button @click="toggleActivo(doc)" class="text-xs px-2 py-1 rounded" :class="doc.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                            <span x-text="doc.activo ? 'Activo' : 'Inactivo'"></span>
                                        </button>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span x-show="doc.tiene_embedding" class="text-green-500">
                                            <svg class="w-5 h-5 inline" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                        <span x-show="!doc.tiene_embedding" class="text-gray-400">
                                            <svg class="w-5 h-5 inline" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex justify-center gap-1">
                                            <button @click="abrirModalEditar(doc)" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            <button @click="regenerarEmbedding(doc.id)" class="p-1 text-yellow-600 hover:bg-yellow-50 rounded" title="Regenerar embedding">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                            <button @click="eliminarDocumento(doc)" class="p-1 text-red-600 hover:bg-red-50 rounded" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="documentos.length === 0">
                                <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                                    No hay documentos. Haz clic en "Nuevo Documento" para crear el primero.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL CREAR/EDITAR DOCUMENTO -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-red-800 text-white px-6 py-4 flex justify-between items-center">
                    <h3 class="font-bold text-lg" x-text="editingDoc ? 'Editar Documento' : 'Nuevo Documento'"></h3>
                    <button @click="showModal = false" class="text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="guardarDocumento()" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                            <input type="text" x-model="formDoc.categoria" required list="categorias-list"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                placeholder="ej: fichajes, vacaciones...">
                            <datalist id="categorias-list">
                                <template x-for="cat in categoriasExistentes" :key="cat">
                                    <option :value="cat"></option>
                                </template>
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                            <input type="number" x-model="formDoc.orden" min="0"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                placeholder="0">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                        <input type="text" x-model="formDoc.titulo" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            placeholder="ej: Cómo fichar entrada">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contenido *</label>
                        <textarea x-model="formDoc.contenido" required rows="8"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            placeholder="Instrucciones paso a paso..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Usa formato claro con pasos numerados. Este texto se usará para generar el embedding.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keywords (para búsqueda fallback)</label>
                        <input type="text" x-model="formDoc.keywords"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            placeholder="fichar, entrada, gps, ubicación...">
                        <p class="text-xs text-gray-500 mt-1">Palabras clave separadas por comas para búsqueda sin IA</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" x-model="formDoc.activo" id="doc-activo" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <label for="doc-activo" class="text-sm text-gray-700">Documento activo</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2" :disabled="guardando">
                            <svg x-show="guardando" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span x-text="guardando ? 'Guardando...' : (editingDoc ? 'Actualizar' : 'Crear')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <style>
            /* Animaciones */
            @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

            .header-ferrallin { background-color: #7f1d1d; color: white; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #991b1b; }
            .header-content { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; }
            .header-left { flex: 1; }
            .header-title { font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 0.75rem; margin: 0; }
            .header-icon { width: 3rem; height: 3rem; object-fit: contain; flex-shrink: 0; }
            .header-text { display: flex; flex-direction: column; }
            .header-name { display: block; color: white; font-size: 1.5rem; line-height: 1.2; }
            .header-subtitle { font-size: 0.875rem; font-weight: normal; color: #d1d5db; line-height: 1.3; }
            .header-status, .header-typing { color: #d1d5db; margin-top: 0.5rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem; }
            .header-typing { color: #fef08a; }
            .status-dot { width: 0.625rem; height: 0.625rem; background-color: #4ade80; border-radius: 9999px; animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; flex-shrink: 0; }
            .typing-icon { width: 1rem; height: 1rem; animation: spin 1s linear infinite; flex-shrink: 0; }
            .btn-clear, .btn-admin { background-color: #991b1b; color: white; font-weight: 600; padding: 0.75rem 1rem; border-radius: 0.75rem; border: 1px solid #7f1d1d; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s; white-space: nowrap; }
            .btn-clear:hover, .btn-admin:hover { background-color: #7f1d1d; }
            .btn-icon { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }

            @media (max-width: 640px) {
                .header-ferrallin { padding: 0.875rem; }
                .header-content { flex-direction: column; align-items: stretch; }
                .header-title { font-size: 1.25rem; gap: 0.5rem; }
                .header-icon { width: 2.5rem; height: 2.5rem; }
                .header-name { font-size: 1.25rem; }
                .header-subtitle { font-size: 0.75rem; }
                .header-status, .header-typing { font-size: 0.75rem; margin-top: 0.375rem; }
                .btn-clear, .btn-admin { width: 100%; justify-content: center; padding: 0.625rem 0.875rem; font-size: 0.875rem; }
                .header-right { flex-direction: column; }
            }
        </style>

        <!-- Chat del Asistente Virtual -->
        <div class="flex flex-col h-[calc(100vh-250px)] bg-white rounded-xl shadow-xl overflow-hidden" :class="showAdmin ? 'h-[calc(100vh-500px)]' : ''">

            <!-- SUGERENCIAS -->
            <div x-show="messages.filter(m => m.sender === 'user').length === 0" class="px-6 py-4 bg-gradient-to-b from-gray-50 to-white border-b">
                <p class="text-sm font-medium text-gray-700 mb-3">Preguntas frecuentes:</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <template x-for="sugerencia in sugerencias.slice(0, 6)" :key="sugerencia">
                        <button @click="useSugerencia(sugerencia)"
                            class="text-left px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700 transition">
                            <span x-text="sugerencia"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- ZONA DE MENSAJES -->
            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4 bg-gray-50" id="chat-box" x-ref="chatBox">

                <template x-for="(msg, i) in messages" :key="i">
                    <div class="flex gap-3 animate-fade-in" :class="msg.sender === 'user' ? 'flex-row-reverse' : 'flex-row'">
                        <div class="flex-shrink-0">
                            <div :class="msg.sender === 'user' ? 'bg-red-500' : 'bg-gradient-to-br from-red-500 to-red-600'"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold shadow">
                                <span x-text="msg.sender === 'user' ? 'TU' : 'F'"></span>
                            </div>
                        </div>
                        <div class="flex-1 max-w-[75%]">
                            <div :class="msg.sender === 'user' ? 'bg-red-500 text-white' : 'bg-white text-gray-800 border border-gray-200'"
                                class="px-4 py-3 rounded-2xl shadow-sm">
                                <div x-html="formatMessage(msg.text)" class="text-sm leading-relaxed prose prose-sm max-w-none"></div>
                            </div>
                            <div class="mt-1 px-2 flex items-center gap-2">
                                <span class="text-xs text-gray-400" x-text="msg.time"></span>
                                <span x-show="msg.metodo" class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500" x-text="msg.metodo"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Indicador de "escribiendo..." -->
                <div x-show="isTyping" class="flex gap-3 animate-fade-in">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center text-white text-sm font-bold shadow">F</div>
                    </div>
                    <div class="bg-white border border-gray-200 px-4 py-3 rounded-2xl shadow-sm">
                        <div class="flex gap-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÁREA DE INPUT -->
            <form @submit.prevent="sendMessage" class="border-t bg-white px-6 py-4">
                <div class="flex gap-3">
                    <input x-model="input" type="text" placeholder="Escribe tu pregunta aquí..." :disabled="isTyping"
                        class="flex-1 border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed transition"
                        maxlength="500">
                    <button type="submit" :disabled="!input.trim() || isTyping"
                        class="bg-red-500 hover:bg-red-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-6 py-3 rounded-xl font-medium transition-all transform hover:scale-105 active:scale-95 shadow-md">
                        <svg x-show="!isTyping" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        <svg x-show="isTyping" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-2" x-text="input.length + '/500 caracteres'"></p>
            </form>
        </div>
    </div>

    <style>
        @keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fade-in 0.3s ease-out; }
    </style>

    <script>
        function ayudaApp() {
            return {
                // Chat
                input: '',
                isTyping: false,
                messages: [],
                sugerencias: [],

                // Admin
                showAdmin: false,
                showModal: false,
                documentos: [],
                categoriasExistentes: [],
                editingDoc: null,
                guardando: false,
                formDoc: {
                    categoria: '',
                    titulo: '',
                    contenido: '',
                    keywords: '',
                    activo: true,
                    orden: 0
                },

                init() {
                    this.addMessage('bot',
                        '¡Hola! Soy **FERRALLIN**, tu asistente de ayuda inteligente.\n\n' +
                        'Ahora uso un sistema **RAG** (Retrieval-Augmented Generation) que busca en la base de conocimiento la información más relevante para tu pregunta.\n\n' +
                        '¿En qué puedo ayudarte hoy?'
                    );
                    this.loadSugerencias();
                    this.loadConversationHistory();
                    @if(auth()->user()->esAdminDepartamento())
                    this.cargarDocumentos();
                    @endif
                },

                // ─────────────────────────────────────────────────────────────
                // CHAT
                // ─────────────────────────────────────────────────────────────

                async loadSugerencias() {
                    try {
                        const res = await fetch("{{ route('ayuda.sugerencias') }}");
                        const data = await res.json();
                        if (data.success && data.data) {
                            this.sugerencias = data.data.flatMap(cat => cat.ejemplos).slice(0, 8);
                        }
                    } catch (err) {
                        console.error('Error cargando sugerencias:', err);
                        this.sugerencias = [
                            '¿Cómo ficho entrada?',
                            '¿Cómo solicito vacaciones?',
                            '¿Cómo recepciono un pedido?',
                            '¿Cómo importo una planilla?'
                        ];
                    }
                },

                addMessage(sender, text, metodo = null) {
                    const now = new Date();
                    this.messages.push({
                        sender,
                        text,
                        metodo,
                        time: now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }),
                        timestamp: now.getTime()
                    });
                    this.saveToLocalStorage();
                    this.scrollToBottom();
                },

                async sendMessage() {
                    if (!this.input.trim() || this.isTyping) return;

                    const userMsg = this.input.trim();
                    this.addMessage('user', userMsg);
                    this.input = '';
                    this.isTyping = true;

                    try {
                        const res = await fetch("{{ route('ayuda.preguntar') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({ pregunta: userMsg })
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.addMessage('bot', data.data.respuesta, data.data.metodo);
                        } else {
                            this.addMessage('bot', '❌ ' + (data.error || 'Ocurrió un error al procesar tu pregunta.'));
                        }
                    } catch (err) {
                        console.error('Error:', err);
                        this.addMessage('bot', '❌ Error de conexión. Por favor, inténtalo de nuevo.');
                    } finally {
                        this.isTyping = false;
                        this.scrollToBottom();
                    }
                },

                useSugerencia(texto) {
                    this.input = texto;
                    this.$nextTick(() => this.sendMessage());
                },

                formatMessage(text) {
                    return text
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/###\s*(.*?)(\n|$)/g, '<h4 class="font-bold text-base mt-2 mb-1">$1</h4>')
                        .replace(/##\s*(.*?)(\n|$)/g, '<h3 class="font-bold text-lg mt-3 mb-1">$1</h3>')
                        .replace(/\n/g, '<br>')
                        .replace(/`(.*?)`/g, '<code class="bg-gray-200 px-1 rounded text-red-600">$1</code>')
                        .replace(/•/g, '&bull;');
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const chatBox = this.$refs.chatBox;
                        if (chatBox) chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
                    });
                },

                clearChat() {
                    if (confirm('¿Seguro que quieres borrar toda la conversación?')) {
                        this.messages = [];
                        this.addMessage('bot', '¡Conversación limpiada! Soy FERRALLIN, ¿en qué puedo ayudarte ahora?');
                        localStorage.removeItem('ayuda_chat_history');
                    }
                },

                saveToLocalStorage() {
                    try {
                        localStorage.setItem('ayuda_chat_history', JSON.stringify(this.messages));
                    } catch (e) { console.warn('No se pudo guardar el historial:', e); }
                },

                loadConversationHistory() {
                    try {
                        const saved = localStorage.getItem('ayuda_chat_history');
                        if (saved) {
                            const history = JSON.parse(saved);
                            const lastMsg = history[history.length - 1];
                            const today = new Date().setHours(0, 0, 0, 0);
                            const msgDate = new Date(lastMsg?.timestamp || 0).setHours(0, 0, 0, 0);
                            if (msgDate === today) this.messages = history;
                        }
                    } catch (e) { console.warn('No se pudo cargar el historial:', e); }
                },

                // ─────────────────────────────────────────────────────────────
                // ADMIN - CRUD DOCUMENTOS
                // ─────────────────────────────────────────────────────────────

                async cargarDocumentos() {
                    try {
                        const res = await fetch("{{ route('ayuda.documentos.listar') }}");
                        const data = await res.json();
                        if (data.success) {
                            this.documentos = data.documentos;
                            this.categoriasExistentes = [...new Set(data.documentos.map(d => d.categoria))];
                        }
                    } catch (err) {
                        console.error('Error cargando documentos:', err);
                    }
                },

                abrirModalCrear() {
                    this.editingDoc = null;
                    this.formDoc = { categoria: '', titulo: '', contenido: '', keywords: '', activo: true, orden: 0 };
                    this.showModal = true;
                },

                abrirModalEditar(doc) {
                    this.editingDoc = doc;
                    this.formDoc = {
                        categoria: doc.categoria,
                        titulo: doc.titulo,
                        contenido: doc.contenido,
                        keywords: doc.keywords || '',
                        activo: doc.activo,
                        orden: doc.orden || 0
                    };
                    this.showModal = true;
                },

                async guardarDocumento() {
                    this.guardando = true;
                    try {
                        const url = this.editingDoc
                            ? "{{ url('ayuda/documentos') }}/" + this.editingDoc.id
                            : "{{ route('ayuda.documentos.crear') }}";
                        const method = this.editingDoc ? 'PUT' : 'POST';

                        const res = await fetch(url, {
                            method,
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify(this.formDoc)
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.showModal = false;
                            this.cargarDocumentos();
                            this.loadSugerencias();
                        } else {
                            alert('Error: ' + (data.error || 'No se pudo guardar'));
                        }
                    } catch (err) {
                        console.error('Error:', err);
                        alert('Error de conexión');
                    } finally {
                        this.guardando = false;
                    }
                },

                async toggleActivo(doc) {
                    try {
                        const res = await fetch("{{ url('ayuda/documentos') }}/" + doc.id + "/toggle-activo", {
                            method: 'POST',
                            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" }
                        });
                        const data = await res.json();
                        if (data.success) {
                            doc.activo = data.activo;
                        }
                    } catch (err) {
                        console.error('Error:', err);
                    }
                },

                async eliminarDocumento(doc) {
                    if (!confirm(`¿Eliminar "${doc.titulo}"?`)) return;
                    try {
                        const res = await fetch("{{ url('ayuda/documentos') }}/" + doc.id, {
                            method: 'DELETE',
                            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.cargarDocumentos();
                        }
                    } catch (err) {
                        console.error('Error:', err);
                    }
                },

                async regenerarEmbedding(id) {
                    try {
                        const res = await fetch("{{ url('ayuda/documentos') }}/" + id + "/regenerar-embedding", {
                            method: 'POST',
                            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.cargarDocumentos();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    } catch (err) {
                        console.error('Error:', err);
                    }
                },

                async regenerarTodos() {
                    if (!confirm('¿Regenerar TODOS los embeddings? Esto puede tardar varios segundos y consumir créditos de API.')) return;
                    try {
                        const res = await fetch("{{ route('ayuda.documentos.regenerar-todos') }}", {
                            method: 'POST',
                            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.cargarDocumentos();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    } catch (err) {
                        console.error('Error:', err);
                    }
                }
            }
        }
    </script>
</x-app-layout>
