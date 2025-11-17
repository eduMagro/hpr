<x-app-layout>
    <x-slot name="title">Centro de Ayuda</x-slot>

    <div class="max-w-6xl mx-auto px-4 py-6">

        <!-- Header RESPONSIVE -->
        <div class="header-ferrallin">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="header-title">
                        <img src="{{ asset('imagenes/iconos/asistente-sin-fondo.png') }}" alt="Ferrallin" class="header-icon">
                        <div class="header-text">
                            <span class="header-name">FERRALLIN</span>
                            <span class="header-subtitle">Asistente de Ayuda Inteligente</span>
                        </div>
                    </h1>
                    <p class="header-status" x-show="!isTyping">
                        <span class="status-dot"></span>
                        <span class="status-text">Potenciado por OpenAI GPT-4 • Listo para ayudarte</span>
                    </p>
                    <p class="header-typing" x-show="isTyping">
                        <svg class="typing-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span class="typing-text">Escribiendo...</span>
                    </p>
                </div>
                <div class="header-right">
                    <button @click="clearChat()" class="btn-clear">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span class="btn-text">Limpiar</span>
                    </button>
                </div>
            </div>
        </div>

        <style>
            /* Animaciones */
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: .5; }
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }

            /* Header Responsive */
            .header-ferrallin {
                background-color: #7f1d1d;
                color: white;
                padding: 1rem;
                border-radius: 0.75rem;
                margin-bottom: 1rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                border: 1px solid #991b1b;
            }

            .header-content {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 1rem;
            }

            .header-left {
                flex: 1;
            }

            .header-title {
                font-size: 1.5rem;
                font-weight: bold;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin: 0;
            }

            .header-icon {
                width: 3rem;
                height: 3rem;
                object-fit: contain;
                flex-shrink: 0;
            }

            .header-text {
                display: flex;
                flex-direction: column;
            }

            .header-name {
                display: block;
                color: white;
                font-size: 1.5rem;
                line-height: 1.2;
            }

            .header-subtitle {
                font-size: 0.875rem;
                font-weight: normal;
                color: #d1d5db;
                line-height: 1.3;
            }

            .header-status,
            .header-typing {
                color: #d1d5db;
                margin-top: 0.5rem;
                font-size: 0.875rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .header-typing {
                color: #fef08a;
                display: none;
            }

            .status-dot {
                width: 0.625rem;
                height: 0.625rem;
                background-color: #4ade80;
                border-radius: 9999px;
                animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                flex-shrink: 0;
            }

            .status-text,
            .typing-text {
                line-height: 1.3;
            }

            .typing-icon {
                width: 1rem;
                height: 1rem;
                animation: spin 1s linear infinite;
                flex-shrink: 0;
            }

            .btn-clear {
                background-color: #991b1b;
                color: white;
                font-weight: 600;
                padding: 0.75rem 1rem;
                border-radius: 0.75rem;
                border: 1px solid #7f1d1d;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                transition: all 0.2s;
                white-space: nowrap;
            }

            .btn-clear:hover {
                background-color: #7f1d1d;
            }

            .btn-icon {
                width: 1.25rem;
                height: 1.25rem;
                flex-shrink: 0;
            }

            /* Responsive Mobile */
            @media (max-width: 640px) {
                .header-ferrallin {
                    padding: 0.875rem;
                }

                .header-content {
                    flex-direction: column;
                    align-items: stretch;
                }

                .header-title {
                    font-size: 1.25rem;
                    gap: 0.5rem;
                }

                .header-icon {
                    width: 2.5rem;
                    height: 2.5rem;
                }

                .header-name {
                    font-size: 1.25rem;
                }

                .header-subtitle {
                    font-size: 0.75rem;
                }

                .header-status,
                .header-typing {
                    font-size: 0.75rem;
                    margin-top: 0.375rem;
                }

                .status-dot {
                    width: 0.5rem;
                    height: 0.5rem;
                }

                .typing-icon {
                    width: 0.875rem;
                    height: 0.875rem;
                }

                .status-text {
                    font-size: 0.75rem;
                }

                .btn-clear {
                    width: 100%;
                    justify-content: center;
                    padding: 0.625rem 0.875rem;
                    font-size: 0.875rem;
                }

                .btn-text {
                    display: inline;
                }
            }

            @media (max-width: 380px) {
                .header-subtitle {
                    font-size: 0.7rem;
                }

                .status-text {
                    font-size: 0.7rem;
                }

                .typing-text {
                    font-size: 0.75rem;
                }
            }
        </style>

        <!-- Chat del Asistente Virtual -->
        <div x-data="chatApp()" x-init="loadSugerencias()"
            class="flex flex-col h-[calc(100vh-250px)] bg-white rounded-xl shadow-xl overflow-hidden">

            <!-- SUGERENCIAS -->
            <div x-show="messages.filter(m => m.sender === 'user').length === 0"
                class="px-6 py-4 bg-gradient-to-b from-gray-50 to-white border-b">
                <p class="text-sm font-medium text-gray-700 mb-3">💡 Preguntas frecuentes:</p>
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
                    <div class="flex gap-3 animate-fade-in"
                        :class="msg.sender === 'user' ? 'flex-row-reverse' : 'flex-row'">

                        <!-- Avatar -->
                        <div class="flex-shrink-0">
                            <div :class="msg.sender === 'user' ? 'bg-red-500' : 'bg-gradient-to-br from-red-500 to-red-600'"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold shadow">
                                <span x-text="msg.sender === 'user' ? 'TU' : 'F'"></span>
                            </div>
                        </div>

                        <!-- Mensaje -->
                        <div class="flex-1 max-w-[75%]">
                            <div :class="msg.sender === 'user' ?
                                'bg-red-500 text-white' :
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
                            class="w-8 h-8 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center text-white text-sm font-bold shadow">
                            F
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
                        class="flex-1 border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed transition"
                        maxlength="500">

                    <button type="submit" :disabled="!input.trim() || isTyping"
                        class="bg-red-500 hover:bg-red-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-6 py-3 rounded-xl font-medium transition-all transform hover:scale-105 active:scale-95 shadow-md">
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
                        '¡Hola! 👋 Soy **FERRALLIN**, tu asistente de ayuda inteligente.\n\n' +
                        'Puedo ayudarte con información sobre:\n\n' +
                        '• 📍 **Fichajes**: Cómo fichar entrada/salida\n' +
                        '• 🏖️ **Vacaciones**: Solicitar y consultar vacaciones\n' +
                        '• 💰 **Nóminas**: Descargar tus nóminas\n' +
                        '• 📦 **Pedidos**: Recepcionar material\n' +
                        '• 📋 **Planillas**: Importar y asignar a máquinas\n' +
                        '• ⚙️ **Producción**: Fabricación y creación de paquetes\n' +
                        '• 🚚 **Salidas**: Preparar portes\n' +
                        '• 🔐 **Contraseñas**: Cambiar o recuperar contraseña\n' +
                        '• 📊 **Stock**: Consultar disponibilidad de material\n' +
                        '• 👤 **Usuarios**: Gestionar empleados\n\n' +
                        '💡 **Tip:** Pregúntame en lenguaje natural, por ejemplo: "¿cómo descargo mi nómina?" o "necesito solicitar vacaciones"\n\n' +
                        '¿En qué puedo ayudarte hoy?'
                    );
                    this.loadConversationHistory();
                },

                async loadSugerencias() {
                    try {
                        const res = await fetch("{{ route('asistente.sugerencias') }}");
                        const data = await res.json();

                        if (data.success) {
                            // Agregar sugerencias de ayuda personalizadas
                            this.sugerencias = [
                                '¿Cómo cambio mi contraseña?',
                                '¿Cómo ficho entrada/salida?',
                                '¿Cómo solicito vacaciones?',
                                '¿Cómo recepciono un pedido?',
                                '¿Cómo importo una planilla?',
                                '¿Cómo creo un paquete?'
                            ];

                            // Agregar las del backend también
                            const backendSugs = data.data.flatMap(cat => cat.ejemplos);
                            this.sugerencias = [...this.sugerencias, ...backendSugs];
                        }
                    } catch (err) {
                        console.error('Error cargando sugerencias:', err);
                        // Sugerencias por defecto
                        this.sugerencias = [
                            '¿Cómo cambio mi contraseña?',
                            '¿Cómo ficho entrada/salida?',
                            '¿Cómo solicito vacaciones?',
                            '¿Cómo recepciono un pedido?',
                            '¿Cómo importo una planilla?',
                            '¿Cómo creo un paquete?'
                        ];
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
                        .replace(/`(.*?)`/g, '<code class="bg-gray-200 px-1 rounded">$1</code>')
                        .replace(/•/g, '&bull;');
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

                clearChat() {
                    if (confirm('¿Seguro que quieres borrar toda la conversación?')) {
                        this.messages = [];
                        this.addMessage('bot', '🗑️ ¡Conversación limpiada! Soy FERRALLIN, ¿en qué puedo ayudarte ahora?');
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
