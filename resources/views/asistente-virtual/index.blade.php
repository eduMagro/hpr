<x-app-layout>
    <x-slot name="title">Asistente Virtual</x-slot>

    <div x-data="chatApp()" x-init="loadSugerencias()"
        class="flex flex-col h-[calc(100vh-120px)] max-w-4xl mx-auto my-6 bg-white rounded-2xl shadow-xl overflow-hidden">

        <!-- CABECERA MEJORADA -->
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold">Asistente Virtual</h1>
                        <p class="text-xs text-blue-100" x-show="!isTyping">Siempre disponible para ayudarte</p>
                        <p class="text-xs text-blue-100 animate-pulse" x-show="isTyping">Escribiendo...</p>
                    </div>
                </div>

                <!-- Botón limpiar chat -->
                <button @click="clearChat()"
                    class="px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-sm transition backdrop-blur-sm">
                    Limpiar
                </button>
            </div>
        </div>

        <!-- SUGERENCIAS (Solo si no hay mensajes del usuario) -->
        <div x-show="messages.filter(m => m.sender === 'user').length === 0"
            class="px-6 py-4 bg-gradient-to-b from-gray-50 to-white border-b">
            <p class="text-sm font-medium text-gray-700 mb-3">💡 Puedes preguntarme sobre:</p>
            <div class="grid grid-cols-2 gap-2">
                <template x-for="sugerencia in sugerencias.slice(0, 4)" :key="sugerencia">
                    <button @click="useSugerencia(sugerencia)"
                        class="text-left px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition">
                        <span x-text="sugerencia"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- ZONA DE MENSAJES -->
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4 bg-gray-50" id="chat-box" x-ref="chatBox"
            @scroll="checkScroll()">

            <template x-for="(msg, i) in messages" :key="i">
                <div class="flex gap-3 animate-fade-in"
                    :class="msg.sender === 'user' ? 'flex-row-reverse' : 'flex-row'">

                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                        <div :class="msg.sender === 'user' ? 'bg-blue-500' : 'bg-gradient-to-br from-purple-500 to-blue-500'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold shadow">
                            <span x-text="msg.sender === 'user' ? 'TU' : 'IA'"></span>
                        </div>
                    </div>

                    <!-- Mensaje -->
                    <div class="flex-1 max-w-[75%]">
                        <div :class="msg.sender === 'user' ?
                            'bg-blue-500 text-white' :
                            'bg-white text-gray-800 border border-gray-200'"
                            class="px-4 py-3 rounded-2xl shadow-sm">
                            <div x-html="formatMessage(msg.text)" class="text-sm leading-relaxed"></div>
                        </div>
                        <div class="mt-1 px-2 text-xs text-gray-400" x-text="msg.time"></div>
                    </div>
                </div>
            </template>

            <!-- Indicador de "escribiendo..." -->
            <div x-show="isTyping" class="flex gap-3 animate-fade-in">
                <div class="flex-shrink-0">
                    <div
                        class="w-8 h-8 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                        IA
                    </div>
                </div>
                <div class="bg-white border border-gray-200 px-4 py-3 rounded-2xl shadow-sm">
                    <div class="flex gap-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s">
                        </div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ÁREA DE INPUT -->
        <form @submit.prevent="sendMessage" class="border-t bg-white px-6 py-4">
            <div class="flex gap-3">
                <input x-model="input" type="text" placeholder="Escribe tu pregunta aquí..." :disabled="isTyping"
                    class="flex-1 border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed transition"
                    maxlength="500">

                <button type="submit" :disabled="!input.trim() || isTyping"
                    class="bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-6 py-3 rounded-xl font-medium transition-all transform hover:scale-105 active:scale-95 shadow-md">
                    <svg x-show="!isTyping" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    <svg x-show="isTyping" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-2" x-text="input.length + '/500 caracteres'"></p>
        </form>
    </div>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>

    <script>
        function chatApp() {
            return {
                input: '',
                isTyping: false,
                messages: [],
                sugerencias: [],
                autoScroll: true,

                init() {
                    // Mensaje de bienvenida
                    this.addMessage('bot',
                        '¡Hola! 👋 Soy tu asistente virtual. Puedo ayudarte con información sobre pedidos, stock, planillas y mucho más. ¿En qué puedo ayudarte hoy?'
                        );
                    this.loadConversationHistory();
                },

                async loadSugerencias() {
                    try {
                        const res = await fetch("{{ route('asistente.sugerencias') }}");
                        const data = await res.json();

                        if (data.success) {
                            // Aplanar todas las sugerencias en un solo array
                            this.sugerencias = data.data.flatMap(cat => cat.ejemplos);
                        }
                    } catch (err) {
                        console.error('Error cargando sugerencias:', err);
                    }
                },

                addMessage(sender, text) {
                    const now = new Date();
                    const time = now.toLocaleTimeString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    this.messages.push({
                        sender,
                        text,
                        time,
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
                        const res = await fetch("{{ route('asistente.preguntar') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                pregunta: userMsg
                            })
                        });

                        const data = await res.json();

                        // Simular delay natural
                        await new Promise(resolve => setTimeout(resolve, 800));

                        if (data.success) {
                            this.addMessage('bot', data.data.respuesta || data.respuesta ||
                                'No pude generar una respuesta.');
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
                    this.$nextTick(() => {
                        this.sendMessage();
                    });
                },

                formatMessage(text) {
                    // Formato básico de markdown
                    return text
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/\n/g, '<br>')
                        .replace(/`(.*?)`/g, '<code class="bg-gray-200 px-1 rounded">$1</code>');
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const chatBox = this.$refs.chatBox;
                        if (chatBox && this.autoScroll) {
                            chatBox.scrollTo({
                                top: chatBox.scrollHeight,
                                behavior: 'smooth'
                            });
                        }
                    });
                },

                checkScroll() {
                    const chatBox = this.$refs.chatBox;
                    const isNearBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 100;
                    this.autoScroll = isNearBottom;
                },

                clearChat() {
                    if (confirm('¿Seguro que quieres borrar toda la conversación?')) {
                        this.messages = [];
                        this.addMessage('bot', '¡Conversación limpiada! ¿En qué puedo ayudarte?');
                        localStorage.removeItem('asistente_chat_history');
                    }
                },

                saveToLocalStorage() {
                    try {
                        localStorage.setItem('asistente_chat_history', JSON.stringify(this.messages));
                    } catch (e) {
                        console.warn('No se pudo guardar el historial:', e);
                    }
                },

                loadConversationHistory() {
                    try {
                        const saved = localStorage.getItem('asistente_chat_history');
                        if (saved) {
                            const history = JSON.parse(saved);
                            // Solo cargar si es del mismo día
                            const lastMsg = history[history.length - 1];
                            const today = new Date().setHours(0, 0, 0, 0);
                            const msgDate = new Date(lastMsg.timestamp).setHours(0, 0, 0, 0);

                            if (msgDate === today) {
                                this.messages = history;
                            }
                        }
                    } catch (e) {
                        console.warn('No se pudo cargar el historial:', e);
                    }
                }
            }
        }
    </script>
</x-app-layout>
