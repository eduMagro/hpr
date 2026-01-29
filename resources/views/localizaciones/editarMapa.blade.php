<x-app-layout>
    <x-slot name="title">Editor de Planta Industrial</x-slot>

    {{-- Estilos para sobrescribir el layout y ocupar todo el espacio --}}
    <style>
        #mainlayout {
            padding: 0 !important;
            height: 100% !important;
            max-height: 100% !important;
            overflow: hidden !important;
        }
        #mainlayout > nav:first-child {
            display: none !important;
        }
        .page-content {
            padding: 0 !important;
            height: 100% !important;
        }
        main.flex-1 {
            overflow: hidden !important;
        }
    </style>

    <div class="editor-container">
        <!-- Barra Superior Compacta -->
        <header class="editor-header">
            <div class="header-left">
                <!-- Logo y Home -->
                <a href="{{ route('dashboard') }}" class="nav-logo" title="Inicio">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </a>

                <!-- Menu de Navegacion -->
                <nav class="nav-menu">
                    @php
                        $menuConfig = config('menu.main');
                    @endphp
                    @foreach($menuConfig as $section)
                    <div class="nav-dropdown">
                        <button type="button" class="nav-dropdown-btn" data-color="{{ $section['color'] }}">
                            <span class="nav-icon">{{ $section['icon'] }}</span>
                            <span class="nav-label">{{ $section['label'] }}</span>
                            <svg class="nav-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="nav-dropdown-menu">
                            <div class="nav-dropdown-header">{{ $section['icon'] }} {{ $section['label'] }}</div>
                            @foreach($section['submenu'] as $item)
                            <a href="{{ route($item['route']) }}" class="nav-dropdown-item">
                                <span class="item-icon">{{ $item['icon'] }}</span>
                                <span class="item-label">{{ $item['label'] }}</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </nav>

                <div class="nav-divider"></div>

                <a href="{{ route('localizaciones.index') }}" class="btn-back" title="Volver a Localizaciones">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="header-title">
                    <span class="title-text">Editor</span>
                    <span class="header-subtitle">
                        {{ strtoupper($obraActiva->obra ?? 'GENERAL') }}
                    </span>
                </div>
            </div>

            <div class="header-center">
                <select id="obra-selector" class="selector-input" title="Seleccionar Nave">
                    @foreach($obras as $obra)
                        <option value="{{ $obra->id }}" {{ ($obraActualId == $obra->id || (!$obraActualId && $loop->first)) ? 'selected' : '' }}>
                            {{ $obra->obra }}
                        </option>
                    @endforeach
                </select>

                <div class="orientation-toggle">
                    <button type="button" id="btn-horizontal" class="orient-btn {{ !$estaGirado ? 'active' : '' }}" title="Horizontal (nave acostada)">
                        H
                    </button>
                    <button type="button" id="btn-vertical" class="orient-btn {{ $estaGirado ? 'active' : '' }}" title="Vertical (nave de pie)">
                        V
                    </button>
                </div>

                <div class="zoom-controls">
                    <button type="button" id="btn-zoom-out" class="zoom-btn" title="Alejar">-</button>
                    <span id="zoom-level" class="zoom-level">100%</span>
                    <button type="button" id="btn-zoom-in" class="zoom-btn" title="Acercar">+</button>
                    <button type="button" id="btn-zoom-fit" class="zoom-btn" title="Ajustar a pantalla">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    </button>
                </div>

                <div class="cursor-coords">
                    <span>X:<span id="cursor-x">--</span></span>
                    <span>Y:<span id="cursor-y">--</span></span>
                </div>
            </div>

            <div class="header-right">
                <span class="status-text" id="status-mode">Seleccion</span>
                <span class="status-msg" id="status-message"></span>
                <div class="help-container">
                    <button type="button" class="help-btn" id="help-btn" title="Atajos de teclado">?</button>
                    <div class="help-tooltip" id="help-tooltip">
                        <div class="tooltip-title">Atajos de Teclado</div>
                        <div class="tooltip-section">
                            <div class="shortcut"><kbd>Ctrl</kbd>+<kbd>C</kbd> <span>Copiar</span></div>
                            <div class="shortcut"><kbd>Ctrl</kbd>+<kbd>V</kbd> <span>Pegar</span></div>
                            <div class="shortcut"><kbd>Ctrl</kbd>+<kbd>Z</kbd> <span>Deshacer</span></div>
                        </div>
                        <div class="tooltip-section">
                            <div class="shortcut"><kbd>Del</kbd> <span>Eliminar</span></div>
                            <div class="shortcut"><kbd>Esc</kbd> <span>Cancelar/Deseleccionar</span></div>
                        </div>
                        <div class="tooltip-section">
                            <div class="shortcut"><kbd>+</kbd> / <kbd>-</kbd> <span>Zoom</span></div>
                            <div class="shortcut"><kbd>0</kbd> <span>Ajustar pantalla</span></div>
                            <div class="shortcut"><kbd>Ctrl</kbd>+<kbd>Rueda</kbd> <span>Zoom con raton</span></div>
                        </div>
                        <div class="tooltip-section">
                            <div class="shortcut-info">Arrastra los bordes para redimensionar</div>
                            <div class="shortcut-info">Arrastra el centro para mover</div>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-dropdown">
                    <button type="button" class="user-dropdown-btn" id="user-menu-btn">
                        <div class="user-avatar">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </div>
                        <svg class="user-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="user-dropdown-menu" id="user-menu">
                        <div class="user-info">
                            <div class="user-name">{{ Auth::user()->name ?? 'Usuario' }}</div>
                            <div class="user-email">{{ Auth::user()->email ?? '' }}</div>
                        </div>
                        <div class="user-menu-divider"></div>
                        <a href="{{ route('users.mi-perfil') }}" class="user-menu-item">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Mi Perfil</span>
                        </a>
                        <a href="{{ route('dashboard') }}" class="user-menu-item">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                        <div class="user-menu-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" class="user-menu-form">
                            @csrf
                            <button type="submit" class="user-menu-item user-menu-logout">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span>Cerrar Sesion</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="editor-body">
            <!-- Panel Lateral Izquierdo -->
            <aside class="tools-panel" id="tools-panel">
                <!-- Herramientas de Creacion -->
                <div class="panel-section tools-section">
                    <div class="section-header">
                        <span class="section-title">Crear Zona</span>
                    </div>
                    <div class="tool-grid">
                        <button type="button" class="tool-btn" data-tool="maquina" title="Maquina">
                            <span class="tool-color bg-blue-600"></span>
                            <span class="tool-label">Maquina</span>
                        </button>
                        <button type="button" class="tool-btn" data-tool="almacenamiento" title="Almacen">
                            <span class="tool-color bg-green-600"></span>
                            <span class="tool-label">Almacen</span>
                        </button>
                        <button type="button" class="tool-btn" data-tool="transitable" title="Pasillo">
                            <span class="tool-color bg-gray-500"></span>
                            <span class="tool-label">Pasillo</span>
                        </button>
                        <button type="button" class="tool-btn" data-tool="carga_descarga" title="Carga/Descarga">
                            <span class="tool-color bg-orange-500"></span>
                            <span class="tool-label">Carga</span>
                        </button>
                    </div>
                </div>

                <!-- Maquinas Disponibles -->
                @if(isset($maquinas) && $maquinas->count() > 0)
                <div class="panel-section machines-section">
                    <div class="section-header">
                        <span class="section-title">Maquinas</span>
                        <span class="section-count">{{ $maquinas->count() }}</span>
                    </div>
                    <div class="machines-list" id="machines-list">
                        @foreach($maquinas as $maquina)
                            <div class="machine-item" draggable="true"
                                 data-id="{{ $maquina->id }}"
                                 data-nombre="{{ $maquina->nombre }}"
                                 data-ancho="{{ ($maquina->ancho_m ?? 2) * 2 }}"
                                 data-largo="{{ ($maquina->largo_m ?? 2) * 2 }}">
                                <span class="machine-dot"></span>
                                <span class="machine-name">{{ $maquina->nombre }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Lista de Localizaciones -->
                <div class="panel-section locations-section">
                    <div class="section-header">
                        <span class="section-title">Localizaciones</span>
                        <span class="section-count" id="loc-count">{{ $localizaciones->count() }}</span>
                    </div>
                    <input type="text" id="search-loc" class="search-input" placeholder="Buscar...">
                    <div class="locations-list" id="locations-list"></div>
                </div>
            </aside>

            <!-- Area Principal del Mapa -->
            <main class="map-area" id="map-viewport">
                <div class="map-wrapper" id="map-wrapper">
                    <div class="grid-container" id="grid-container">
                        <div class="grid-lines" id="grid-lines"></div>
                        <div class="objects-layer" id="objects-layer"></div>
                        <div class="ghost-layer" id="ghost-layer"></div>
                        <div class="selection-box hidden" id="selection-box"></div>
                    </div>
                </div>
            </main>

            <!-- Panel Lateral Derecho: Propiedades -->
            <aside class="properties-panel" id="properties-panel">
                <div class="props-header">
                    <span>Propiedades</span>
                    <button type="button" id="close-properties" class="close-btn">&times;</button>
                </div>

                <div class="props-empty" id="props-empty">
                    <p>Selecciona un elemento</p>
                </div>

                <div class="props-form hidden" id="props-form">
                    <input type="hidden" id="prop-id">

                    <div class="prop-badge" id="prop-type-badge">
                        <span id="prop-type-text">TIPO</span>
                    </div>

                    <div class="prop-group">
                        <label>Nombre</label>
                        <input type="text" id="prop-nombre" class="prop-input" placeholder="Nombre">
                    </div>

                    <div class="prop-group hidden" id="prop-maquina-group">
                        <label>Maquina</label>
                        <select id="prop-maquina" class="prop-input">
                            <option value="">-- Seleccionar --</option>
                            @foreach($maquinas ?? [] as $maquina)
                                <option value="{{ $maquina->id }}">{{ $maquina->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="prop-group">
                        <label>Posicion (coords reales)</label>
                        <div class="coords-grid">
                            <div class="coord-field"><span>X1</span><input type="number" id="prop-x1" min="1"></div>
                            <div class="coord-field"><span>Y1</span><input type="number" id="prop-y1" min="1"></div>
                            <div class="coord-field"><span>X2</span><input type="number" id="prop-x2" min="1"></div>
                            <div class="coord-field"><span>Y2</span><input type="number" id="prop-y2" min="1"></div>
                        </div>
                    </div>

                    <div class="prop-group">
                        <label>Dimensiones</label>
                        <div class="dim-display">
                            <span id="prop-width">0</span>x<span id="prop-height">0</span> celdas
                            (<span id="prop-width-m">0</span>x<span id="prop-height-m">0</span>m)
                        </div>
                    </div>

                    <div class="prop-actions">
                        <button type="button" id="btn-save-props" class="btn-save">Guardar</button>
                        <button type="button" id="btn-delete-loc" class="btn-delete">Eliminar</button>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Modal Crear -->
    <div class="modal-overlay hidden" id="modal-create">
        <div class="modal-box">
            <div class="modal-header">
                <span id="modal-title">Nueva Zona</span>
                <button type="button" id="modal-close" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-tipo">
                <input type="hidden" id="modal-x1">
                <input type="hidden" id="modal-y1">
                <input type="hidden" id="modal-x2">
                <input type="hidden" id="modal-y2">

                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" id="modal-nombre" class="form-input" placeholder="Nombre de la zona">
                </div>

                <div class="form-group hidden" id="modal-maquina-group">
                    <label>Maquina</label>
                    <select id="modal-maquina" class="form-input">
                        <option value="">-- Seleccionar --</option>
                        @foreach($maquinas ?? [] as $maquina)
                            <option value="{{ $maquina->id }}">{{ $maquina->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-info">
                    <span>Pos: <strong id="modal-pos-display">(0,0)-(0,0)</strong></span>
                    <span>Tam: <strong id="modal-size-display">0x0</strong></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="modal-cancel" class="btn-cancel">Cancelar</button>
                <button type="button" id="modal-confirm" class="btn-confirm">Crear</button>
            </div>
        </div>
    </div>

    <style>
        :root {
            --header-h: 36px;
            --panel-w: 200px;
            --props-w: 220px;
        }

        .editor-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            background: #e5e7eb;
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 12px;
        }

        .editor-header {
            height: var(--header-h);
            background: #1f2937;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 8px;
            gap: 12px;
            flex-shrink: 0;
            z-index: 100;
        }

        .header-left { display: flex; align-items: center; gap: 6px; }

        /* Logo/Home button */
        .nav-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            color: #60a5fa;
            transition: all 0.15s;
        }
        .nav-logo:hover { background: #374151; color: #93c5fd; }

        /* Navigation Menu */
        .nav-menu { display: flex; align-items: center; gap: 2px; }
        .nav-divider { width: 1px; height: 20px; background: #4b5563; margin: 0 4px; }

        .nav-dropdown { position: relative; }
        .nav-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 3px;
            padding: 4px 6px;
            border-radius: 4px;
            color: #d1d5db;
            font-size: 11px;
            transition: all 0.15s;
            background: transparent;
            border: none;
            cursor: pointer;
        }
        .nav-dropdown-btn:hover { background: #374151; color: white; }
        .nav-dropdown.active .nav-dropdown-btn { background: #374151; color: white; }

        .nav-icon { font-size: 12px; }
        .nav-label { font-weight: 500; }
        .nav-arrow { width: 10px; height: 10px; opacity: 0.6; transition: transform 0.2s; }
        .nav-dropdown.active .nav-arrow { transform: rotate(180deg); }

        /* Dropdown Menu */
        .nav-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 4px;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 8px;
            min-width: 200px;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-5px);
            transition: all 0.15s ease;
        }
        .nav-dropdown.active .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .nav-dropdown-header {
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
            border-bottom: 1px solid #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            color: #d1d5db;
            font-size: 12px;
            transition: all 0.15s;
            text-decoration: none;
        }
        .nav-dropdown-item:hover {
            background: #374151;
            color: white;
        }
        .nav-dropdown-item .item-icon { font-size: 14px; width: 20px; text-align: center; }
        .nav-dropdown-item .item-label { flex: 1; }

        /* Color variations for hover */
        .nav-dropdown-btn[data-color="blue"]:hover,
        .nav-dropdown.active .nav-dropdown-btn[data-color="blue"] { background: rgba(59, 130, 246, 0.2); }
        .nav-dropdown-btn[data-color="purple"]:hover,
        .nav-dropdown.active .nav-dropdown-btn[data-color="purple"] { background: rgba(139, 92, 246, 0.2); }
        .nav-dropdown-btn[data-color="green"]:hover,
        .nav-dropdown.active .nav-dropdown-btn[data-color="green"] { background: rgba(34, 197, 94, 0.2); }
        .nav-dropdown-btn[data-color="indigo"]:hover,
        .nav-dropdown.active .nav-dropdown-btn[data-color="indigo"] { background: rgba(99, 102, 241, 0.2); }
        .nav-dropdown-btn[data-color="orange"]:hover,
        .nav-dropdown.active .nav-dropdown-btn[data-color="orange"] { background: rgba(249, 115, 22, 0.2); }
        .nav-dropdown-btn[data-color="gray"]:hover,
        .nav-dropdown.active .nav-dropdown-btn[data-color="gray"] { background: rgba(107, 114, 128, 0.2); }

        .btn-back {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            color: #9ca3af;
            transition: all 0.15s;
        }
        .btn-back:hover { background: #374151; color: white; }

        .header-title { display: flex; align-items: center; gap: 8px; }
        .title-text { font-weight: 600; font-size: 13px; }
        .header-subtitle { font-size: 10px; color: #9ca3af; font-family: monospace; }

        .header-center { flex: 1; display: flex; align-items: center; justify-content: center; gap: 12px; }

        .selector-input {
            background: #374151;
            border: 1px solid #4b5563;
            border-radius: 4px;
            color: white;
            padding: 4px 8px;
            font-size: 11px;
            min-width: 140px;
        }
        .selector-input:focus { outline: none; border-color: #3b82f6; }

        .orientation-toggle { display: flex; background: #374151; border-radius: 4px; padding: 2px; }
        .orient-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 24px;
            border-radius: 3px;
            color: #9ca3af;
            font-weight: bold;
            font-size: 11px;
            transition: all 0.15s;
        }
        .orient-btn:hover { color: white; }
        .orient-btn.active { background: #3b82f6; color: white; }

        .zoom-controls { display: flex; align-items: center; gap: 2px; background: #374151; border-radius: 4px; padding: 2px; }
        .zoom-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 3px;
            color: #9ca3af;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.15s;
        }
        .zoom-btn:hover { background: #4b5563; color: white; }
        .zoom-level { font-size: 10px; font-family: monospace; min-width: 36px; text-align: center; color: #d1d5db; }

        .cursor-coords {
            display: flex;
            gap: 8px;
            font-family: monospace;
            font-size: 10px;
            background: #111827;
            padding: 4px 8px;
            border-radius: 4px;
            color: #6b7280;
        }
        .cursor-coords span span { color: #10b981; margin-left: 2px; }

        .header-right { display: flex; align-items: center; gap: 8px; }
        .status-text { font-size: 10px; color: #9ca3af; background: #374151; padding: 3px 8px; border-radius: 3px; }
        .status-msg { font-size: 10px; color: #10b981; }
        .status-msg.error { color: #ef4444; }

        /* Boton de ayuda con tooltip */
        .help-container { position: relative; }
        .help-btn {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #374151;
            color: #9ca3af;
            font-weight: bold;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
            border: 1px solid #4b5563;
        }
        .help-btn:hover { background: #4b5563; color: white; }

        .help-tooltip {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 8px;
            padding: 12px;
            min-width: 220px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-5px);
            transition: all 0.2s ease;
        }
        .help-container:hover .help-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .tooltip-title {
            font-size: 11px;
            font-weight: 600;
            color: #f3f4f6;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #374151;
        }
        .tooltip-section {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #374151;
        }
        .tooltip-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .shortcut {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10px;
            color: #d1d5db;
            margin-bottom: 4px;
        }
        .shortcut:last-child { margin-bottom: 0; }
        .shortcut span { color: #9ca3af; }
        .shortcut kbd {
            background: #374151;
            border: 1px solid #4b5563;
            border-radius: 3px;
            padding: 1px 5px;
            font-family: monospace;
            font-size: 9px;
            color: #f3f4f6;
        }
        .shortcut-info {
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
            margin-bottom: 2px;
        }

        .editor-body { flex: 1; display: flex; overflow: hidden; min-height: 0; }

        .tools-panel, .properties-panel {
            width: var(--panel-w);
            background: white;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
        }
        .tools-panel { border-right: 1px solid #d1d5db; }
        .properties-panel { width: var(--props-w); border-left: 1px solid #d1d5db; }

        .panel-section { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
        .section-title { font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-count { font-size: 9px; background: #e5e7eb; color: #6b7280; padding: 1px 5px; border-radius: 8px; }

        .tool-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; }
        .tool-btn {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 6px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            transition: all 0.15s;
        }
        .tool-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
        .tool-btn.active { border-color: #3b82f6; background: #eff6ff; }
        .tool-color { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }
        .tool-label { font-size: 10px; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .machines-section { max-height: 120px; overflow: hidden; display: flex; flex-direction: column; }
        .machines-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }
        .machine-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 6px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 3px;
            cursor: grab;
            transition: all 0.15s;
        }
        .machine-item:hover { background: #eff6ff; border-color: #3b82f6; }
        .machine-dot { width: 8px; height: 8px; background: #3b82f6; border-radius: 2px; flex-shrink: 0; }
        .machine-name { font-size: 10px; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .locations-section { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 0; }
        .search-input { width: 100%; padding: 5px 8px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 10px; margin-bottom: 6px; }
        .search-input:focus { outline: none; border-color: #3b82f6; }

        .locations-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }
        .loc-item { display: flex; align-items: center; gap: 6px; padding: 4px 6px; border-radius: 3px; cursor: pointer; transition: all 0.15s; }
        .loc-item:hover { background: #f3f4f6; }
        .loc-item.selected { background: #eff6ff; }
        .loc-color { width: 8px; height: 8px; border-radius: 2px; flex-shrink: 0; }
        .loc-name { flex: 1; font-size: 10px; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .loc-coords { font-size: 9px; color: #9ca3af; font-family: monospace; }

        /* MAPA - Sin scroll innecesario */
        .map-area {
            flex: 1;
            overflow: hidden;
            background: #9ca3af;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .map-wrapper {
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .map-wrapper.can-scroll {
            overflow: auto;
            align-items: flex-start;
            justify-content: flex-start;
        }

        .grid-container {
            position: relative;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            border: 2px solid #4b5563;
            transform-origin: center center;
            flex-shrink: 0;
        }

        .map-wrapper.can-scroll .grid-container {
            transform-origin: 0 0;
            margin: 20px;
        }

        .grid-lines { position: absolute; inset: 0; pointer-events: none; opacity: 0.3; }
        .objects-layer, .ghost-layer { position: absolute; inset: 0; }
        .ghost-layer { pointer-events: none; z-index: 100; }

        .selection-box { position: absolute; border: 2px dashed #3b82f6; background: rgba(59, 130, 246, 0.1); pointer-events: none; z-index: 90; }
        .selection-box.hidden { display: none; }

        .map-object {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 1px rgba(0,0,0,0.5);
            border-radius: 2px;
            cursor: move;
            transition: filter 0.1s;
            overflow: visible;
            padding: 1px 3px;
            z-index: 10;
            user-select: none;
        }
        .object-label {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            max-width: 100%;
            pointer-events: none;
        }
        .map-object:hover { filter: brightness(1.1); z-index: 20; }
        .map-object.selected { outline: 2px solid #fbbf24; outline-offset: 1px; z-index: 30; }
        .map-object.dragging { opacity: 0.5; cursor: grabbing; }
        .map-object.resizing { opacity: 0.7; }

        /* Handles de redimension */
        .resize-handle {
            position: absolute;
            background: #fbbf24;
            z-index: 40;
            opacity: 0;
            transition: opacity 0.15s;
            box-sizing: border-box;
        }
        .map-object.selected .resize-handle { opacity: 1; }
        .map-object:hover .resize-handle { opacity: 0.6; }

        /* Esquinas - cuadrados en las 4 esquinas */
        .resize-handle.corner {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            border: 2px solid #92400e;
        }
        .resize-handle.nw { top: -6px; left: -6px; cursor: nw-resize; }
        .resize-handle.ne { top: -6px; right: -6px; cursor: ne-resize; }
        .resize-handle.sw { bottom: -6px; left: -6px; cursor: sw-resize; }
        .resize-handle.se { bottom: -6px; right: -6px; cursor: se-resize; }

        /* Lados - barras en el centro de cada borde */
        .resize-handle.edge {
            border: 1px solid #92400e;
            border-radius: 3px;
        }
        .resize-handle.n {
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 10px;
            cursor: n-resize;
        }
        .resize-handle.s {
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 10px;
            cursor: s-resize;
        }
        .resize-handle.w {
            left: -5px;
            top: 50%;
            transform: translateY(-50%);
            width: 10px;
            height: 30px;
            cursor: w-resize;
        }
        .resize-handle.e {
            right: -5px;
            top: 50%;
            transform: translateY(-50%);
            width: 10px;
            height: 30px;
            cursor: e-resize;
        }

        .type-maquina { background: linear-gradient(135deg, #2563eb, #1d4ed8); border: 1px solid #1e40af; }
        .type-almacenamiento { background: linear-gradient(135deg, #16a34a, #15803d); border: 1px solid #166534; }
        .type-transitable { background: linear-gradient(135deg, #9ca3af, #6b7280); border: 1px solid #4b5563; opacity: 0.7; }
        .type-carga_descarga { background: linear-gradient(135deg, #f59e0b, #d97706); border: 1px solid #b45309; }

        .ghost {
            position: absolute;
            border: 2px dashed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: bold;
            border-radius: 2px;
            z-index: 110;
        }
        .ghost.valid { background: rgba(34, 197, 94, 0.3); border-color: #16a34a; color: #166534; }
        .ghost.invalid { background: rgba(239, 68, 68, 0.3); border-color: #dc2626; color: #991b1b; }

        /* Panel Propiedades */
        .props-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 11px;
            color: #374151;
        }
        .close-btn { width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 3px; color: #9ca3af; font-size: 16px; cursor: pointer; }
        .close-btn:hover { background: #f3f4f6; color: #374151; }

        .props-empty { flex: 1; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 11px; text-align: center; padding: 16px; }
        .props-form { flex: 1; padding: 8px; display: flex; flex-direction: column; gap: 10px; overflow-y: auto; }
        .props-form.hidden { display: none; }

        .prop-badge { text-align: center; }
        .prop-badge span { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 9px; font-weight: 600; text-transform: uppercase; }
        .prop-badge .type-maquina { background: #dbeafe; color: #1e40af; }
        .prop-badge .type-almacenamiento { background: #dcfce7; color: #166534; }
        .prop-badge .type-transitable { background: #f3f4f6; color: #374151; }
        .prop-badge .type-carga_descarga { background: #fef3c7; color: #92400e; }

        .prop-group { display: flex; flex-direction: column; gap: 4px; }
        .prop-group label { font-size: 10px; font-weight: 500; color: #6b7280; }
        .prop-group.hidden { display: none; }
        .prop-input { padding: 5px 8px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; }
        .prop-input:focus { outline: none; border-color: #3b82f6; }

        .coords-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; }
        .coord-field { display: flex; align-items: center; border: 1px solid #e5e7eb; border-radius: 3px; overflow: hidden; }
        .coord-field span { padding: 4px; background: #f3f4f6; font-size: 9px; font-weight: 600; color: #6b7280; }
        .coord-field input { flex: 1; padding: 4px; border: none; font-size: 10px; font-family: monospace; width: 100%; min-width: 0; }
        .coord-field input:focus { outline: none; }

        .dim-display { font-size: 11px; color: #374151; }

        .prop-actions { display: flex; gap: 6px; margin-top: auto; padding-top: 8px; border-top: 1px solid #e5e7eb; }
        .btn-save, .btn-delete { flex: 1; padding: 6px; border-radius: 4px; font-size: 10px; font-weight: 500; cursor: pointer; transition: all 0.15s; }
        .btn-save { background: #3b82f6; color: white; }
        .btn-save:hover { background: #2563eb; }
        .btn-delete { background: white; color: #dc2626; border: 1px solid #dc2626; }
        .btn-delete:hover { background: #fef2f2; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.hidden { display: none; }
        .modal-box { background: white; border-radius: 8px; width: 320px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-weight: 600; font-size: 13px; }
        .modal-close { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 4px; color: #9ca3af; font-size: 18px; cursor: pointer; }
        .modal-close:hover { background: #f3f4f6; color: #374151; }
        .modal-body { padding: 16px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 11px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .form-group.hidden { display: none; }
        .form-input { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; }
        .form-input:focus { outline: none; border-color: #3b82f6; }
        .form-info { display: flex; justify-content: space-between; background: #f9fafb; padding: 8px 12px; border-radius: 4px; font-size: 11px; color: #6b7280; }
        .form-info strong { color: #374151; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px; border-top: 1px solid #e5e7eb; background: #f9fafb; border-radius: 0 0 8px 8px; }
        .btn-cancel, .btn-confirm { padding: 8px 16px; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer; }
        .btn-cancel { color: #6b7280; }
        .btn-cancel:hover { background: #e5e7eb; }
        .btn-confirm { background: #3b82f6; color: white; }
        .btn-confirm:hover { background: #2563eb; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // =============================================
        // NAVEGACION DROPDOWNS
        // =============================================
        const navDropdowns = document.querySelectorAll('.nav-dropdown');
        let activeDropdown = null;

        navDropdowns.forEach(dropdown => {
            const btn = dropdown.querySelector('.nav-dropdown-btn');
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (activeDropdown && activeDropdown !== dropdown) {
                    activeDropdown.classList.remove('active');
                }
                dropdown.classList.toggle('active');
                activeDropdown = dropdown.classList.contains('active') ? dropdown : null;
            });
        });

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (activeDropdown && !activeDropdown.contains(e.target)) {
                activeDropdown.classList.remove('active');
                activeDropdown = null;
            }
        });

        // Cerrar dropdown con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && activeDropdown) {
                activeDropdown.classList.remove('active');
                activeDropdown = null;
            }
        });

        // =============================================
        // CONFIGURACION
        // =============================================
        const CONFIG = {
            // Dimensiones REALES de la nave (como se guardan en BD)
            COLS_REAL: {{ $obraActiva->ancho_m ?? 22 }} * 2,  // ancho en celdas
            ROWS_REAL: {{ $obraActiva->largo_m ?? 115 }} * 2, // largo en celdas

            // Dimensiones de VISTA (pueden estar transpuestas)
            COLS_VISTA: {{ $columnasVista }},
            ROWS_VISTA: {{ $filasVista }},

            // Orientacion actual
            IS_VERTICAL: {{ $estaGirado ? 'true' : 'false' }},

            NAVE_ID: {{ $obraActiva->id ?? 'null' }},
            CELL_SIZE: 0.5,
            MIN_ZOOM: 0.5,
            MAX_ZOOM: 3,
            ZOOM_STEP: 0.25,

            // Calculados dinamicamente
            baseWidth: 0,
            baseHeight: 0,
            cellPx: 0
        };

        const STATE = {
            zoom: 1,
            localizaciones: @json($localizaciones),
            selectedId: null,
            activeTool: null,
            isDragging: false,
            isDrawing: false,
            isResizing: false,
            drawStart: null,
            draggedItem: null,
            dragOffset: { x: 0, y: 0 },
            resizeItem: null,
            resizeDirection: null,
            resizeStart: null,
            clipboard: null,
            history: [] // Historial para deshacer (max 50 acciones)
        };

        const DOM = {
            gridContainer: document.getElementById('grid-container'),
            objectsLayer: document.getElementById('objects-layer'),
            ghostLayer: document.getElementById('ghost-layer'),
            selectionBox: document.getElementById('selection-box'),
            mapWrapper: document.getElementById('map-wrapper'),
            mapViewport: document.getElementById('map-viewport'),
            locationsList: document.getElementById('locations-list'),
            locCount: document.getElementById('loc-count'),
            propsPanel: document.getElementById('properties-panel'),
            propsEmpty: document.getElementById('props-empty'),
            propsForm: document.getElementById('props-form'),
            cursorX: document.getElementById('cursor-x'),
            cursorY: document.getElementById('cursor-y'),
            zoomLevel: document.getElementById('zoom-level'),
            modal: document.getElementById('modal-create'),
            statusMode: document.getElementById('status-mode'),
            statusMessage: document.getElementById('status-message')
        };

        // =============================================
        // TRANSFORMACION DE COORDENADAS
        // Convierte entre coordenadas REALES (BD) y VISTA (pantalla)
        // =============================================
        function realToView(x, y) {
            if (CONFIG.IS_VERTICAL) {
                // Vertical: X real -> X vista, Y real -> Y vista (sin cambio)
                return { x, y };
            } else {
                // Horizontal: transponer - X real -> Y vista, Y real -> X vista
                return { x: y, y: x };
            }
        }

        function viewToReal(x, y) {
            if (CONFIG.IS_VERTICAL) {
                return { x, y };
            } else {
                // Inversa de la transposicion
                return { x: y, y: x };
            }
        }

        function realRectToView(x1, y1, x2, y2) {
            const p1 = realToView(x1, y1);
            const p2 = realToView(x2, y2);
            return {
                x1: Math.min(p1.x, p2.x),
                y1: Math.min(p1.y, p2.y),
                x2: Math.max(p1.x, p2.x),
                y2: Math.max(p1.y, p2.y)
            };
        }

        function viewRectToReal(x1, y1, x2, y2) {
            const p1 = viewToReal(x1, y1);
            const p2 = viewToReal(x2, y2);
            return {
                x1: Math.min(p1.x, p2.x),
                y1: Math.min(p1.y, p2.y),
                x2: Math.max(p1.x, p2.x),
                y2: Math.max(p1.y, p2.y)
            };
        }

        // =============================================
        // INICIALIZACION
        // =============================================
        function init() {
            setupGrid();
            renderObjects();
            renderLocationsList();
            setupEventListeners();
        }

        function setupGrid() {
            // Calcular tamano del grid para que quepa en el viewport
            const viewport = DOM.mapViewport.getBoundingClientRect();
            const availableWidth = viewport.width - 20; // padding
            const availableHeight = viewport.height - 20;

            // Calcular escala para que quepa manteniendo proporcion
            const scaleX = availableWidth / CONFIG.COLS_VISTA;
            const scaleY = availableHeight / CONFIG.ROWS_VISTA;
            CONFIG.cellPx = Math.min(scaleX, scaleY);

            // Tamano base del grid (al 100% zoom)
            CONFIG.baseWidth = CONFIG.COLS_VISTA * CONFIG.cellPx;
            CONFIG.baseHeight = CONFIG.ROWS_VISTA * CONFIG.cellPx;

            DOM.gridContainer.style.width = `${CONFIG.baseWidth}px`;
            DOM.gridContainer.style.height = `${CONFIG.baseHeight}px`;

            // Patron de lineas
            const gridLines = document.getElementById('grid-lines');
            gridLines.style.backgroundSize = `${100 / CONFIG.COLS_VISTA}% ${100 / CONFIG.ROWS_VISTA}%`;
            gridLines.style.backgroundImage = `
                linear-gradient(to right, #6b7280 1px, transparent 1px),
                linear-gradient(to bottom, #6b7280 1px, transparent 1px)
            `;

            applyZoom();
        }

        function applyZoom() {
            DOM.gridContainer.style.transform = `scale(${STATE.zoom})`;
            DOM.zoomLevel.textContent = `${Math.round(STATE.zoom * 100)}%`;

            // Determinar si necesita scroll
            const viewport = DOM.mapViewport.getBoundingClientRect();
            const scaledWidth = CONFIG.baseWidth * STATE.zoom;
            const scaledHeight = CONFIG.baseHeight * STATE.zoom;
            const needsScroll = scaledWidth > viewport.width - 20 || scaledHeight > viewport.height - 20;

            DOM.mapWrapper.classList.toggle('can-scroll', needsScroll);
        }

        // =============================================
        // RENDERIZADO
        // =============================================
        function renderObjects() {
            DOM.objectsLayer.innerHTML = '';
            STATE.localizaciones.forEach(loc => {
                // Convertir coordenadas REALES a VISTA
                const viewCoords = realRectToView(loc.x1, loc.y1, loc.x2, loc.y2);

                const el = document.createElement('div');
                el.className = `map-object type-${loc.tipo}`;
                el.dataset.id = loc.id;

                const width = viewCoords.x2 - viewCoords.x1 + 1;
                const height = viewCoords.y2 - viewCoords.y1 + 1;

                el.style.left = `${(viewCoords.x1 - 1) / CONFIG.COLS_VISTA * 100}%`;
                el.style.top = `${(viewCoords.y1 - 1) / CONFIG.ROWS_VISTA * 100}%`;
                el.style.width = `${width / CONFIG.COLS_VISTA * 100}%`;
                el.style.height = `${height / CONFIG.ROWS_VISTA * 100}%`;

                // Texto del nombre
                const labelSpan = document.createElement('span');
                labelSpan.className = 'object-label';
                labelSpan.textContent = loc.nombre || loc.localizacion || loc.tipo.toUpperCase().substring(0, 8);
                el.appendChild(labelSpan);

                // Agregar handles de redimension
                const handles = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];
                handles.forEach(dir => {
                    const handle = document.createElement('div');
                    handle.className = `resize-handle ${dir} ${['n','s','e','w'].includes(dir) ? 'edge' : 'corner'}`;
                    handle.dataset.direction = dir;
                    handle.addEventListener('mousedown', (e) => {
                        e.stopPropagation();
                        startResize(e, loc, dir);
                    });
                    el.appendChild(handle);
                });

                if (STATE.selectedId === loc.id) el.classList.add('selected');

                el.addEventListener('click', (e) => {
                    if (!e.target.classList.contains('resize-handle')) {
                        e.stopPropagation();
                        selectObject(loc.id);
                    }
                });
                el.addEventListener('mousedown', (e) => {
                    if (!e.target.classList.contains('resize-handle')) {
                        startDrag(e, loc);
                    }
                });

                DOM.objectsLayer.appendChild(el);
            });
        }

        function renderLocationsList() {
            const searchTerm = document.getElementById('search-loc')?.value?.toLowerCase() || '';
            const filtered = STATE.localizaciones.filter(loc => {
                const name = (loc.nombre || loc.localizacion || '').toLowerCase();
                return name.includes(searchTerm);
            });

            DOM.locationsList.innerHTML = filtered.map(loc => {
                const colorClass = {
                    'maquina': 'bg-blue-600',
                    'almacenamiento': 'bg-green-600',
                    'transitable': 'bg-gray-500',
                    'carga_descarga': 'bg-orange-500'
                }[loc.tipo] || 'bg-gray-400';

                return `
                    <div class="loc-item ${STATE.selectedId === loc.id ? 'selected' : ''}" data-id="${loc.id}">
                        <div class="loc-color ${colorClass}"></div>
                        <span class="loc-name">${loc.nombre || loc.localizacion || loc.tipo}</span>
                        <span class="loc-coords">${loc.x1},${loc.y1}</span>
                    </div>
                `;
            }).join('');

            DOM.locCount.textContent = STATE.localizaciones.length;

            DOM.locationsList.querySelectorAll('.loc-item').forEach(item => {
                item.addEventListener('click', () => selectObject(parseInt(item.dataset.id)));
            });
        }

        // =============================================
        // SELECCION
        // =============================================
        function selectObject(id) {
            STATE.selectedId = id;
            DOM.objectsLayer.querySelectorAll('.map-object').forEach(el => {
                el.classList.toggle('selected', parseInt(el.dataset.id) === id);
            });
            DOM.locationsList.querySelectorAll('.loc-item').forEach(item => {
                item.classList.toggle('selected', parseInt(item.dataset.id) === id);
            });

            if (id) {
                const loc = STATE.localizaciones.find(l => l.id === id);
                if (loc) showProperties(loc);
            } else {
                hideProperties();
            }
        }

        function showProperties(loc) {
            DOM.propsEmpty.style.display = 'none';
            DOM.propsForm.classList.remove('hidden');

            // Mostrar coordenadas REALES (como estan en BD)
            document.getElementById('prop-id').value = loc.id;
            document.getElementById('prop-nombre').value = loc.nombre || loc.localizacion || '';
            document.getElementById('prop-x1').value = loc.x1;
            document.getElementById('prop-y1').value = loc.y1;
            document.getElementById('prop-x2').value = loc.x2;
            document.getElementById('prop-y2').value = loc.y2;

            const width = loc.x2 - loc.x1 + 1;
            const height = loc.y2 - loc.y1 + 1;
            document.getElementById('prop-width').textContent = width;
            document.getElementById('prop-height').textContent = height;
            document.getElementById('prop-width-m').textContent = (width * CONFIG.CELL_SIZE).toFixed(1);
            document.getElementById('prop-height-m').textContent = (height * CONFIG.CELL_SIZE).toFixed(1);

            const badge = document.getElementById('prop-type-text');
            badge.textContent = loc.tipo.replace('_', '/').toUpperCase();
            badge.className = `type-${loc.tipo}`;

            const maquinaGroup = document.getElementById('prop-maquina-group');
            if (loc.tipo === 'maquina') {
                maquinaGroup.classList.remove('hidden');
                document.getElementById('prop-maquina').value = loc.maquina_id || '';
            } else {
                maquinaGroup.classList.add('hidden');
            }
        }

        function hideProperties() {
            DOM.propsEmpty.style.display = 'flex';
            DOM.propsForm.classList.add('hidden');
        }

        // =============================================
        // DRAG & DROP
        // =============================================
        function startDrag(e, loc) {
            if (e.button !== 0) return;
            e.preventDefault();
            STATE.isDragging = true;
            STATE.draggedItem = loc;

            const rect = DOM.gridContainer.getBoundingClientRect();
            const cellW = rect.width / CONFIG.COLS_VISTA;
            const cellH = rect.height / CONFIG.ROWS_VISTA;

            // Posicion del click en coordenadas de VISTA
            const clickViewX = Math.ceil((e.clientX - rect.left) / cellW);
            const clickViewY = Math.ceil((e.clientY - rect.top) / cellH);

            // Coordenadas de vista del objeto
            const viewCoords = realRectToView(loc.x1, loc.y1, loc.x2, loc.y2);

            STATE.dragOffset = {
                x: clickViewX - viewCoords.x1,
                y: clickViewY - viewCoords.y1
            };

            const objEl = DOM.objectsLayer.querySelector(`[data-id="${loc.id}"]`);
            if (objEl) objEl.classList.add('dragging');

            const ghost = document.createElement('div');
            ghost.className = 'ghost';
            ghost.id = 'drag-ghost';
            ghost.textContent = loc.nombre || loc.localizacion || loc.tipo;

            const viewWidth = viewCoords.x2 - viewCoords.x1 + 1;
            const viewHeight = viewCoords.y2 - viewCoords.y1 + 1;
            ghost.style.width = `${viewWidth / CONFIG.COLS_VISTA * 100}%`;
            ghost.style.height = `${viewHeight / CONFIG.ROWS_VISTA * 100}%`;

            ghost.dataset.viewWidth = viewWidth;
            ghost.dataset.viewHeight = viewHeight;

            DOM.ghostLayer.appendChild(ghost);

            document.addEventListener('mousemove', onDrag);
            document.addEventListener('mouseup', endDrag);
            DOM.statusMode.textContent = 'Arrastrando';
        }

        function onDrag(e) {
            if (!STATE.isDragging || !STATE.draggedItem) return;

            const rect = DOM.gridContainer.getBoundingClientRect();
            const cellW = rect.width / CONFIG.COLS_VISTA;
            const cellH = rect.height / CONFIG.ROWS_VISTA;

            const mouseViewX = Math.ceil((e.clientX - rect.left) / cellW);
            const mouseViewY = Math.ceil((e.clientY - rect.top) / cellH);

            const ghost = document.getElementById('drag-ghost');
            if (!ghost) return;

            const viewWidth = parseInt(ghost.dataset.viewWidth);
            const viewHeight = parseInt(ghost.dataset.viewHeight);

            let newViewX1 = Math.max(1, Math.min(mouseViewX - STATE.dragOffset.x, CONFIG.COLS_VISTA - viewWidth + 1));
            let newViewY1 = Math.max(1, Math.min(mouseViewY - STATE.dragOffset.y, CONFIG.ROWS_VISTA - viewHeight + 1));

            ghost.style.left = `${(newViewX1 - 1) / CONFIG.COLS_VISTA * 100}%`;
            ghost.style.top = `${(newViewY1 - 1) / CONFIG.ROWS_VISTA * 100}%`;

            // Convertir a coordenadas reales para verificar colision
            const newRealCoords = viewRectToReal(newViewX1, newViewY1, newViewX1 + viewWidth - 1, newViewY1 + viewHeight - 1);

            const hasCollision = checkCollision(newRealCoords.x1, newRealCoords.y1, newRealCoords.x2, newRealCoords.y2, STATE.draggedItem.id);
            ghost.className = `ghost ${hasCollision ? 'invalid' : 'valid'}`;

            ghost.dataset.newViewX1 = newViewX1;
            ghost.dataset.newViewY1 = newViewY1;
        }

        function endDrag() {
            document.removeEventListener('mousemove', onDrag);
            document.removeEventListener('mouseup', endDrag);

            const ghost = document.getElementById('drag-ghost');
            const objEl = DOM.objectsLayer.querySelector(`[data-id="${STATE.draggedItem?.id}"]`);

            if (objEl) objEl.classList.remove('dragging');

            if (ghost && STATE.draggedItem && ghost.classList.contains('valid')) {
                const newViewX1 = parseInt(ghost.dataset.newViewX1);
                const newViewY1 = parseInt(ghost.dataset.newViewY1);
                const viewWidth = parseInt(ghost.dataset.viewWidth);
                const viewHeight = parseInt(ghost.dataset.viewHeight);

                // Convertir posicion de VISTA a REAL
                const newRealCoords = viewRectToReal(newViewX1, newViewY1, newViewX1 + viewWidth - 1, newViewY1 + viewHeight - 1);
                const loc = STATE.draggedItem;

                if (newRealCoords.x1 !== loc.x1 || newRealCoords.y1 !== loc.y1) {
                    savePosition(loc, newRealCoords.x1, newRealCoords.y1, newRealCoords.x2, newRealCoords.y2);
                }
            }

            if (ghost) ghost.remove();
            STATE.isDragging = false;
            STATE.draggedItem = null;
            DOM.statusMode.textContent = 'Seleccion';
        }

        function checkCollision(x1, y1, x2, y2, excludeId) {
            return STATE.localizaciones.some(loc => {
                if (loc.id === excludeId) return false;
                if (loc.tipo === 'transitable') return false;
                return !(x2 < loc.x1 || x1 > loc.x2 || y2 < loc.y1 || y1 > loc.y2);
            });
        }

        // =============================================
        // RESIZE (REDIMENSIONAR)
        // =============================================
        function startResize(e, loc, direction) {
            if (e.button !== 0) return;
            e.preventDefault();
            e.stopPropagation();

            STATE.isResizing = true;
            STATE.resizeItem = loc;
            STATE.resizeDirection = direction;

            // Guardar coordenadas de vista actuales
            const viewCoords = realRectToView(loc.x1, loc.y1, loc.x2, loc.y2);
            STATE.resizeStart = {
                viewX1: viewCoords.x1,
                viewY1: viewCoords.y1,
                viewX2: viewCoords.x2,
                viewY2: viewCoords.y2
            };

            const objEl = DOM.objectsLayer.querySelector(`[data-id="${loc.id}"]`);
            if (objEl) objEl.classList.add('resizing');

            // Crear ghost para preview
            const ghost = document.createElement('div');
            ghost.className = 'ghost valid';
            ghost.id = 'resize-ghost';
            ghost.style.left = `${(viewCoords.x1 - 1) / CONFIG.COLS_VISTA * 100}%`;
            ghost.style.top = `${(viewCoords.y1 - 1) / CONFIG.ROWS_VISTA * 100}%`;
            ghost.style.width = `${(viewCoords.x2 - viewCoords.x1 + 1) / CONFIG.COLS_VISTA * 100}%`;
            ghost.style.height = `${(viewCoords.y2 - viewCoords.y1 + 1) / CONFIG.ROWS_VISTA * 100}%`;
            DOM.ghostLayer.appendChild(ghost);

            document.addEventListener('mousemove', onResize);
            document.addEventListener('mouseup', endResize);
            DOM.statusMode.textContent = 'Redimensionando';
        }

        function onResize(e) {
            if (!STATE.isResizing || !STATE.resizeItem) return;

            const rect = DOM.gridContainer.getBoundingClientRect();
            const cellW = rect.width / CONFIG.COLS_VISTA;
            const cellH = rect.height / CONFIG.ROWS_VISTA;

            const mouseViewX = Math.max(1, Math.min(Math.ceil((e.clientX - rect.left) / cellW), CONFIG.COLS_VISTA));
            const mouseViewY = Math.max(1, Math.min(Math.ceil((e.clientY - rect.top) / cellH), CONFIG.ROWS_VISTA));

            const dir = STATE.resizeDirection;
            let { viewX1, viewY1, viewX2, viewY2 } = STATE.resizeStart;

            // Ajustar coordenadas segun la direccion del handle
            if (dir.includes('n')) viewY1 = Math.min(mouseViewY, viewY2);
            if (dir.includes('s')) viewY2 = Math.max(mouseViewY, viewY1);
            if (dir.includes('w')) viewX1 = Math.min(mouseViewX, viewX2);
            if (dir.includes('e')) viewX2 = Math.max(mouseViewX, viewX1);

            // Asegurar tamano minimo de 1 celda
            if (viewX2 < viewX1) viewX2 = viewX1;
            if (viewY2 < viewY1) viewY2 = viewY1;

            // Limitar a los bordes del grid
            viewX1 = Math.max(1, viewX1);
            viewY1 = Math.max(1, viewY1);
            viewX2 = Math.min(CONFIG.COLS_VISTA, viewX2);
            viewY2 = Math.min(CONFIG.ROWS_VISTA, viewY2);

            const ghost = document.getElementById('resize-ghost');
            if (!ghost) return;

            ghost.style.left = `${(viewX1 - 1) / CONFIG.COLS_VISTA * 100}%`;
            ghost.style.top = `${(viewY1 - 1) / CONFIG.ROWS_VISTA * 100}%`;
            ghost.style.width = `${(viewX2 - viewX1 + 1) / CONFIG.COLS_VISTA * 100}%`;
            ghost.style.height = `${(viewY2 - viewY1 + 1) / CONFIG.ROWS_VISTA * 100}%`;

            // Verificar colision
            const newRealCoords = viewRectToReal(viewX1, viewY1, viewX2, viewY2);
            const hasCollision = checkCollision(newRealCoords.x1, newRealCoords.y1, newRealCoords.x2, newRealCoords.y2, STATE.resizeItem.id);
            ghost.className = `ghost ${hasCollision ? 'invalid' : 'valid'}`;

            // Guardar nuevas coordenadas en el ghost
            ghost.dataset.viewX1 = viewX1;
            ghost.dataset.viewY1 = viewY1;
            ghost.dataset.viewX2 = viewX2;
            ghost.dataset.viewY2 = viewY2;

            // Mostrar dimensiones
            const width = viewX2 - viewX1 + 1;
            const height = viewY2 - viewY1 + 1;
            ghost.textContent = `${width}x${height}`;
        }

        function endResize() {
            document.removeEventListener('mousemove', onResize);
            document.removeEventListener('mouseup', endResize);

            const ghost = document.getElementById('resize-ghost');
            const objEl = DOM.objectsLayer.querySelector(`[data-id="${STATE.resizeItem?.id}"]`);

            if (objEl) objEl.classList.remove('resizing');

            if (ghost && STATE.resizeItem && ghost.classList.contains('valid')) {
                const viewX1 = parseInt(ghost.dataset.viewX1);
                const viewY1 = parseInt(ghost.dataset.viewY1);
                const viewX2 = parseInt(ghost.dataset.viewX2);
                const viewY2 = parseInt(ghost.dataset.viewY2);

                // Convertir a coordenadas reales
                const newRealCoords = viewRectToReal(viewX1, viewY1, viewX2, viewY2);
                const loc = STATE.resizeItem;

                // Solo guardar si cambio algo
                if (newRealCoords.x1 !== loc.x1 || newRealCoords.y1 !== loc.y1 ||
                    newRealCoords.x2 !== loc.x2 || newRealCoords.y2 !== loc.y2) {
                    savePosition(loc, newRealCoords.x1, newRealCoords.y1, newRealCoords.x2, newRealCoords.y2);
                }
            }

            if (ghost) ghost.remove();
            STATE.isResizing = false;
            STATE.resizeItem = null;
            STATE.resizeDirection = null;
            STATE.resizeStart = null;
            DOM.statusMode.textContent = 'Seleccion';
        }

        // =============================================
        // DIBUJAR NUEVA ZONA
        // =============================================
        function startDrawing(e) {
            if (!STATE.activeTool || STATE.isDragging || STATE.isResizing) return;
            if (e.target.closest('.map-object')) return;
            if (e.target.classList.contains('resize-handle')) return;

            const rect = DOM.gridContainer.getBoundingClientRect();
            const cellW = rect.width / CONFIG.COLS_VISTA;
            const cellH = rect.height / CONFIG.ROWS_VISTA;

            const viewX = Math.ceil((e.clientX - rect.left) / cellW);
            const viewY = Math.ceil((e.clientY - rect.top) / cellH);

            if (viewX < 1 || viewX > CONFIG.COLS_VISTA || viewY < 1 || viewY > CONFIG.ROWS_VISTA) return;

            STATE.isDrawing = true;
            STATE.drawStart = { x: viewX, y: viewY };

            DOM.selectionBox.classList.remove('hidden');
            updateSelectionBox(viewX, viewY, viewX, viewY);

            document.addEventListener('mousemove', onDrawing);
            document.addEventListener('mouseup', endDrawing);
            DOM.statusMode.textContent = 'Dibujando';
        }

        function onDrawing(e) {
            if (!STATE.isDrawing) return;

            const rect = DOM.gridContainer.getBoundingClientRect();
            const cellW = rect.width / CONFIG.COLS_VISTA;
            const cellH = rect.height / CONFIG.ROWS_VISTA;

            let viewX = Math.max(1, Math.min(Math.ceil((e.clientX - rect.left) / cellW), CONFIG.COLS_VISTA));
            let viewY = Math.max(1, Math.min(Math.ceil((e.clientY - rect.top) / cellH), CONFIG.ROWS_VISTA));

            updateSelectionBox(STATE.drawStart.x, STATE.drawStart.y, viewX, viewY);
        }

        function endDrawing() {
            document.removeEventListener('mousemove', onDrawing);
            document.removeEventListener('mouseup', endDrawing);

            if (!STATE.isDrawing) return;

            const box = DOM.selectionBox;
            const viewX1 = Math.min(parseInt(box.dataset.x1), parseInt(box.dataset.x2));
            const viewY1 = Math.min(parseInt(box.dataset.y1), parseInt(box.dataset.y2));
            const viewX2 = Math.max(parseInt(box.dataset.x1), parseInt(box.dataset.x2));
            const viewY2 = Math.max(parseInt(box.dataset.y1), parseInt(box.dataset.y2));

            DOM.selectionBox.classList.add('hidden');
            STATE.isDrawing = false;

            // Convertir coordenadas de VISTA a REAL para guardar
            const realCoords = viewRectToReal(viewX1, viewY1, viewX2, viewY2);

            openCreateModal(STATE.activeTool, realCoords.x1, realCoords.y1, realCoords.x2, realCoords.y2);

            DOM.statusMode.textContent = 'Seleccion';
        }

        function updateSelectionBox(startX, startY, endX, endY) {
            const x1 = Math.min(startX, endX), y1 = Math.min(startY, endY);
            const x2 = Math.max(startX, endX), y2 = Math.max(startY, endY);

            DOM.selectionBox.style.left = `${(x1 - 1) / CONFIG.COLS_VISTA * 100}%`;
            DOM.selectionBox.style.top = `${(y1 - 1) / CONFIG.ROWS_VISTA * 100}%`;
            DOM.selectionBox.style.width = `${(x2 - x1 + 1) / CONFIG.COLS_VISTA * 100}%`;
            DOM.selectionBox.style.height = `${(y2 - y1 + 1) / CONFIG.ROWS_VISTA * 100}%`;

            DOM.selectionBox.dataset.x1 = startX;
            DOM.selectionBox.dataset.y1 = startY;
            DOM.selectionBox.dataset.x2 = endX;
            DOM.selectionBox.dataset.y2 = endY;
        }

        // =============================================
        // MODAL
        // =============================================
        function openCreateModal(tipo, x1, y1, x2, y2) {
            // x1,y1,x2,y2 ya estan en coordenadas REALES
            document.getElementById('modal-tipo').value = tipo;
            document.getElementById('modal-x1').value = x1;
            document.getElementById('modal-y1').value = y1;
            document.getElementById('modal-x2').value = x2;
            document.getElementById('modal-y2').value = y2;
            document.getElementById('modal-nombre').value = '';

            const labels = { 'maquina': 'Maquina', 'almacenamiento': 'Almacen', 'transitable': 'Pasillo', 'carga_descarga': 'Carga/Descarga' };
            document.getElementById('modal-title').textContent = `Nueva ${labels[tipo] || tipo}`;
            document.getElementById('modal-pos-display').textContent = `(${x1},${y1})-(${x2},${y2})`;
            document.getElementById('modal-size-display').textContent = `${x2 - x1 + 1}x${y2 - y1 + 1}`;

            document.getElementById('modal-maquina-group').classList.toggle('hidden', tipo !== 'maquina');
            DOM.modal.classList.remove('hidden');
            document.getElementById('modal-nombre').focus();
        }

        function closeModal() { DOM.modal.classList.add('hidden'); }

        // =============================================
        // API
        // =============================================
        async function savePosition(loc, newX1, newY1, newX2, newY2) {
            // Guardar estado anterior para deshacer
            const before = { x1: loc.x1, y1: loc.y1, x2: loc.x2, y2: loc.y2, nombre: loc.nombre };

            try {
                const response = await fetch(`/localizaciones/${loc.id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ _method: 'PUT', x1: newX1, y1: newY1, x2: newX2, y2: newY2 })
                });
                const data = await response.json();
                if (data.success) {
                    // Determinar si fue move o resize
                    const wasResize = (newX2 - newX1) !== (before.x2 - before.x1) || (newY2 - newY1) !== (before.y2 - before.y1);
                    pushHistory({ type: wasResize ? 'resize' : 'move', id: loc.id, before });

                    loc.x1 = newX1; loc.y1 = newY1; loc.x2 = newX2; loc.y2 = newY2;
                    renderObjects();
                    if (STATE.selectedId === loc.id) showProperties(loc);
                    showMessage(wasResize ? 'Redimensionado' : 'Movido', 'success');
                } else {
                    showMessage(data.message || 'Error', 'error');
                    renderObjects();
                }
            } catch (err) {
                showMessage('Error de conexion', 'error');
                renderObjects();
            }
        }

        async function createLocalizacion() {
            const tipo = document.getElementById('modal-tipo').value;
            const nombre = document.getElementById('modal-nombre').value.trim();
            const x1 = parseInt(document.getElementById('modal-x1').value);
            const y1 = parseInt(document.getElementById('modal-y1').value);
            const x2 = parseInt(document.getElementById('modal-x2').value);
            const y2 = parseInt(document.getElementById('modal-y2').value);
            const maquinaId = document.getElementById('modal-maquina').value;

            if (!nombre) { showMessage('Ingresa un nombre', 'error'); return; }
            if (tipo === 'maquina' && !maquinaId) { showMessage('Selecciona maquina', 'error'); return; }

            const payload = { tipo, nombre, x1, y1, x2, y2, nave_id: CONFIG.NAVE_ID };
            if (tipo === 'maquina') payload.maquina_id = maquinaId;

            try {
                const response = await fetch('/localizaciones', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) {
                    STATE.localizaciones.push(data.localizacion);
                    pushHistory({ type: 'create', id: data.localizacion.id });
                    renderObjects();
                    renderLocationsList();
                    closeModal();
                    selectObject(data.localizacion.id);
                    showMessage('Creado', 'success');
                } else {
                    showMessage(data.message || 'Error', 'error');
                }
            } catch (err) {
                showMessage('Error de conexion', 'error');
            }
        }

        async function updateLocalizacion() {
            const id = parseInt(document.getElementById('prop-id').value);
            const loc = STATE.localizaciones.find(l => l.id === id);
            if (!loc) return;

            // Guardar estado anterior para deshacer
            const before = { x1: loc.x1, y1: loc.y1, x2: loc.x2, y2: loc.y2, nombre: loc.nombre || loc.localizacion };

            const nombre = document.getElementById('prop-nombre').value.trim();
            const x1 = parseInt(document.getElementById('prop-x1').value);
            const y1 = parseInt(document.getElementById('prop-y1').value);
            const x2 = parseInt(document.getElementById('prop-x2').value);
            const y2 = parseInt(document.getElementById('prop-y2').value);
            const maquinaId = document.getElementById('prop-maquina').value;

            const payload = { _method: 'PUT', nombre, x1: Math.min(x1, x2), y1: Math.min(y1, y2), x2: Math.max(x1, x2), y2: Math.max(y1, y2) };
            if (loc.tipo === 'maquina') payload.maquina_id = maquinaId || null;

            try {
                const response = await fetch(`/localizaciones/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) {
                    pushHistory({ type: 'update', id: loc.id, before });
                    Object.assign(loc, { nombre: payload.nombre, x1: payload.x1, y1: payload.y1, x2: payload.x2, y2: payload.y2, maquina_id: payload.maquina_id });
                    renderObjects();
                    renderLocationsList();
                    showProperties(loc);
                    showMessage('Guardado', 'success');
                } else {
                    showMessage(data.message || 'Error', 'error');
                }
            } catch (err) {
                showMessage('Error', 'error');
            }
        }

        async function deleteLocalizacion() {
            const id = parseInt(document.getElementById('prop-id').value);
            const loc = STATE.localizaciones.find(l => l.id === id);
            if (!loc) return;

            // SweetAlert para confirmar eliminacion
            const result = await Swal.fire({
                title: 'Eliminar localizacion',
                text: `¿Seguro que deseas eliminar "${loc.nombre || loc.localizacion}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            // Guardar copia completa para poder restaurar
            const deletedData = { ...loc };

            try {
                const response = await fetch(`/localizaciones/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await response.json();
                if (data.success) {
                    pushHistory({ type: 'delete', id: id, data: deletedData });
                    STATE.localizaciones = STATE.localizaciones.filter(l => l.id !== id);
                    STATE.selectedId = null;
                    renderObjects();
                    renderLocationsList();
                    hideProperties();
                    showMessage('Eliminado', 'success');
                } else {
                    showMessage(data.message || 'Error', 'error');
                }
            } catch (err) {
                showMessage('Error', 'error');
            }
        }

        // =============================================
        // HISTORIAL / DESHACER
        // =============================================
        function pushHistory(action) {
            STATE.history.push(action);
            if (STATE.history.length > 50) STATE.history.shift(); // Max 50 acciones
        }

        async function undo() {
            if (STATE.history.length === 0) {
                showMessage('Nada que deshacer', 'error');
                return;
            }

            const action = STATE.history.pop();

            try {
                switch (action.type) {
                    case 'create':
                        // Deshacer creacion = eliminar
                        await fetch(`/localizaciones/${action.id}`, {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        });
                        STATE.localizaciones = STATE.localizaciones.filter(l => l.id !== action.id);
                        if (STATE.selectedId === action.id) STATE.selectedId = null;
                        showMessage('Deshecho: creacion', 'success');
                        break;

                    case 'delete':
                        // Deshacer eliminacion = recrear
                        const createRes = await fetch('/localizaciones', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({
                                tipo: action.data.tipo,
                                nombre: action.data.nombre || action.data.localizacion,
                                x1: action.data.x1,
                                y1: action.data.y1,
                                x2: action.data.x2,
                                y2: action.data.y2,
                                nave_id: CONFIG.NAVE_ID,
                                maquina_id: action.data.maquina_id || null
                            })
                        });
                        const createData = await createRes.json();
                        if (createData.success) {
                            STATE.localizaciones.push(createData.localizacion);
                        }
                        showMessage('Deshecho: eliminacion', 'success');
                        break;

                    case 'move':
                    case 'resize':
                    case 'update':
                        // Deshacer movimiento/resize/actualizacion = restaurar coords anteriores
                        await fetch(`/localizaciones/${action.id}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({
                                _method: 'PUT',
                                nombre: action.before.nombre || action.before.localizacion,
                                x1: action.before.x1,
                                y1: action.before.y1,
                                x2: action.before.x2,
                                y2: action.before.y2
                            })
                        });
                        const loc = STATE.localizaciones.find(l => l.id === action.id);
                        if (loc) {
                            loc.x1 = action.before.x1;
                            loc.y1 = action.before.y1;
                            loc.x2 = action.before.x2;
                            loc.y2 = action.before.y2;
                            if (action.before.nombre) loc.nombre = action.before.nombre;
                        }
                        showMessage('Deshecho: ' + action.type, 'success');
                        break;
                }

                renderObjects();
                renderLocationsList();
                if (STATE.selectedId) {
                    const selLoc = STATE.localizaciones.find(l => l.id === STATE.selectedId);
                    if (selLoc) showProperties(selLoc);
                    else hideProperties();
                }
            } catch (err) {
                showMessage('Error al deshacer', 'error');
            }
        }

        // =============================================
        // COPIAR / PEGAR
        // =============================================
        function copyLocation() {
            if (!STATE.selectedId) {
                showMessage('Selecciona una localizacion', 'error');
                return;
            }
            const loc = STATE.localizaciones.find(l => l.id === STATE.selectedId);
            if (loc) {
                STATE.clipboard = { ...loc };
                showMessage('Copiado: ' + (loc.nombre || loc.localizacion), 'success');
            }
        }

        async function pasteLocation() {
            if (!STATE.clipboard) {
                showMessage('Nada en el portapapeles', 'error');
                return;
            }

            const original = STATE.clipboard;
            const width = original.x2 - original.x1;
            const height = original.y2 - original.y1;

            // Buscar posicion libre probando diferentes offsets
            let newX1, newY1, newX2, newY2;
            let found = false;

            // Probar diferentes posiciones: derecha, abajo, izquierda, arriba, y combinaciones
            const offsets = [
                { dx: width + 2, dy: 0 },       // Derecha
                { dx: 0, dy: height + 2 },      // Abajo
                { dx: -(width + 2), dy: 0 },    // Izquierda
                { dx: 0, dy: -(height + 2) },   // Arriba
                { dx: width + 2, dy: height + 2 },   // Diagonal derecha-abajo
                { dx: -(width + 2), dy: height + 2 }, // Diagonal izquierda-abajo
                { dx: width + 2, dy: -(height + 2) }, // Diagonal derecha-arriba
                { dx: -(width + 2), dy: -(height + 2) }, // Diagonal izquierda-arriba
            ];

            for (const offset of offsets) {
                newX1 = original.x1 + offset.dx;
                newY1 = original.y1 + offset.dy;
                newX2 = newX1 + width;
                newY2 = newY1 + height;

                // Verificar que este dentro del grid
                if (newX1 < 1 || newY1 < 1 || newX2 > CONFIG.COLS_REAL || newY2 > CONFIG.ROWS_REAL) {
                    continue;
                }

                // Verificar que no colisione
                if (!checkCollision(newX1, newY1, newX2, newY2, null)) {
                    found = true;
                    break;
                }
            }

            // Si no encontro posicion con offsets, buscar espacio libre en el grid
            if (!found) {
                for (let y = 1; y <= CONFIG.ROWS_REAL - height; y += 2) {
                    for (let x = 1; x <= CONFIG.COLS_REAL - width; x += 2) {
                        if (!checkCollision(x, y, x + width, y + height, null)) {
                            newX1 = x;
                            newY1 = y;
                            newX2 = x + width;
                            newY2 = y + height;
                            found = true;
                            break;
                        }
                    }
                    if (found) break;
                }
            }

            if (!found) {
                showMessage('No hay espacio libre para pegar', 'error');
                return;
            }

            // Generar nombre unico
            let baseName = original.nombre || original.localizacion || original.tipo;
            let newName = baseName + ' (copia)';
            let counter = 2;
            while (STATE.localizaciones.some(l => (l.nombre || l.localizacion) === newName)) {
                newName = baseName + ` (copia ${counter})`;
                counter++;
            }

            const payload = {
                tipo: original.tipo,
                nombre: newName,
                x1: newX1,
                y1: newY1,
                x2: newX2,
                y2: newY2,
                nave_id: CONFIG.NAVE_ID
            };
            if (original.maquina_id) payload.maquina_id = original.maquina_id;

            try {
                const response = await fetch('/localizaciones', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) {
                    STATE.localizaciones.push(data.localizacion);
                    pushHistory({ type: 'create', id: data.localizacion.id });
                    // Actualizar clipboard para siguiente pegado
                    STATE.clipboard = { ...data.localizacion };
                    renderObjects();
                    renderLocationsList();
                    selectObject(data.localizacion.id);
                    showMessage('Pegado: ' + newName, 'success');
                } else {
                    showMessage(data.message || 'Error al pegar', 'error');
                }
            } catch (err) {
                showMessage('Error de conexion', 'error');
            }
        }

        // =============================================
        // ZOOM
        // =============================================
        function setZoom(level) {
            STATE.zoom = Math.max(CONFIG.MIN_ZOOM, Math.min(CONFIG.MAX_ZOOM, level));
            applyZoom();
        }

        function fitToScreen() {
            // Recalcular tamano del grid para que quepa
            setupGrid();
            setZoom(1);
        }

        function showMessage(text, type = 'info') {
            DOM.statusMessage.textContent = text;
            DOM.statusMessage.className = 'status-msg' + (type === 'error' ? ' error' : '');
            setTimeout(() => { DOM.statusMessage.textContent = ''; }, 2500);
        }

        // =============================================
        // EVENT LISTENERS
        // =============================================
        function setupEventListeners() {
            // Tools
            document.querySelectorAll('.tool-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tool = btn.dataset.tool;
                    document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
                    if (STATE.activeTool === tool) {
                        STATE.activeTool = null;
                        DOM.statusMode.textContent = 'Seleccion';
                    } else {
                        STATE.activeTool = tool;
                        btn.classList.add('active');
                        DOM.statusMode.textContent = 'Crear ' + tool;
                    }
                });
            });

            // Grid events
            DOM.gridContainer.addEventListener('mousedown', startDrawing);
            DOM.gridContainer.addEventListener('click', (e) => {
                if (!e.target.closest('.map-object') && !STATE.isDrawing) selectObject(null);
            });

            // Cursor tracking
            DOM.gridContainer.addEventListener('mousemove', (e) => {
                const rect = DOM.gridContainer.getBoundingClientRect();
                const viewX = Math.ceil((e.clientX - rect.left) / (rect.width / CONFIG.COLS_VISTA));
                const viewY = Math.ceil((e.clientY - rect.top) / (rect.height / CONFIG.ROWS_VISTA));
                if (viewX > 0 && viewX <= CONFIG.COLS_VISTA && viewY > 0 && viewY <= CONFIG.ROWS_VISTA) {
                    DOM.cursorX.textContent = viewX;
                    DOM.cursorY.textContent = viewY;
                }
            });

            // Zoom
            document.getElementById('btn-zoom-in').addEventListener('click', () => setZoom(STATE.zoom + CONFIG.ZOOM_STEP));
            document.getElementById('btn-zoom-out').addEventListener('click', () => setZoom(STATE.zoom - CONFIG.ZOOM_STEP));
            document.getElementById('btn-zoom-fit').addEventListener('click', fitToScreen);

            // Wheel zoom
            DOM.mapWrapper.addEventListener('wheel', (e) => {
                if (e.ctrlKey) {
                    e.preventDefault();
                    setZoom(STATE.zoom + (e.deltaY < 0 ? CONFIG.ZOOM_STEP : -CONFIG.ZOOM_STEP));
                }
            }, { passive: false });

            // Keyboard
            document.addEventListener('keydown', (e) => {
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

                // Ctrl+C = Copiar, Ctrl+V = Pegar, Ctrl+Z = Deshacer
                if (e.ctrlKey || e.metaKey) {
                    if (e.key === 'c' || e.key === 'C') {
                        e.preventDefault();
                        copyLocation();
                        return;
                    }
                    if (e.key === 'v' || e.key === 'V') {
                        e.preventDefault();
                        pasteLocation();
                        return;
                    }
                    if (e.key === 'z' || e.key === 'Z') {
                        e.preventDefault();
                        undo();
                        return;
                    }
                }

                switch (e.key) {
                    case 'Delete': case 'Backspace': if (STATE.selectedId) deleteLocalizacion(); break;
                    case 'Escape': selectObject(null); STATE.activeTool = null; document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active')); closeModal(); break;
                    case '+': case '=': setZoom(STATE.zoom + CONFIG.ZOOM_STEP); break;
                    case '-': setZoom(STATE.zoom - CONFIG.ZOOM_STEP); break;
                    case '0': fitToScreen(); break;
                }
            });

            // Search
            document.getElementById('search-loc').addEventListener('input', renderLocationsList);

            // Properties
            document.getElementById('close-properties').addEventListener('click', () => selectObject(null));
            document.getElementById('btn-save-props').addEventListener('click', updateLocalizacion);
            document.getElementById('btn-delete-loc').addEventListener('click', deleteLocalizacion);

            // Enter para guardar en campos de propiedades
            const propsInputs = document.querySelectorAll('#props-form input, #props-form select');
            propsInputs.forEach(input => {
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        updateLocalizacion();
                    }
                });
            });

            // Enter para crear en modal
            document.getElementById('modal-nombre').addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    createLocalizacion();
                }
            });

            // Modal
            document.getElementById('modal-close').addEventListener('click', closeModal);
            document.getElementById('modal-cancel').addEventListener('click', closeModal);
            document.getElementById('modal-confirm').addEventListener('click', createLocalizacion);
            DOM.modal.addEventListener('click', (e) => { if (e.target === DOM.modal) closeModal(); });

            // Nave selector
            document.getElementById('obra-selector').addEventListener('change', (e) => {
                window.location.href = `{{ route('localizaciones.editarMapa') }}?obra=${e.target.value}&orientacion=${CONFIG.IS_VERTICAL ? 'vertical' : 'horizontal'}`;
            });

            // Orientation
            document.getElementById('btn-horizontal').addEventListener('click', () => {
                window.location.href = `{{ route('localizaciones.editarMapa') }}?obra=${document.getElementById('obra-selector').value}&orientacion=horizontal`;
            });
            document.getElementById('btn-vertical').addEventListener('click', () => {
                window.location.href = `{{ route('localizaciones.editarMapa') }}?obra=${document.getElementById('obra-selector').value}&orientacion=vertical`;
            });

            // Machine drag
            document.querySelectorAll('.machine-item').forEach(item => {
                item.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('machine', JSON.stringify({
                        id: item.dataset.id,
                        nombre: item.dataset.nombre,
                        ancho: parseInt(item.dataset.ancho),
                        largo: parseInt(item.dataset.largo)
                    }));
                });
            });

            DOM.gridContainer.addEventListener('dragover', (e) => e.preventDefault());
            DOM.gridContainer.addEventListener('drop', (e) => {
                e.preventDefault();
                const machineData = e.dataTransfer.getData('machine');
                if (!machineData) return;

                const machine = JSON.parse(machineData);
                const rect = DOM.gridContainer.getBoundingClientRect();
                const cellW = rect.width / CONFIG.COLS_VISTA;
                const cellH = rect.height / CONFIG.ROWS_VISTA;

                const viewX = Math.ceil((e.clientX - rect.left) / cellW);
                const viewY = Math.ceil((e.clientY - rect.top) / cellH);

                // Las dimensiones de la maquina estan en coordenadas reales
                // Si horizontal, transponer para la vista
                let viewWidth = CONFIG.IS_VERTICAL ? machine.ancho : machine.largo;
                let viewHeight = CONFIG.IS_VERTICAL ? machine.largo : machine.ancho;

                const viewX1 = Math.max(1, Math.min(viewX, CONFIG.COLS_VISTA - viewWidth + 1));
                const viewY1 = Math.max(1, Math.min(viewY, CONFIG.ROWS_VISTA - viewHeight + 1));
                const viewX2 = viewX1 + viewWidth - 1;
                const viewY2 = viewY1 + viewHeight - 1;

                // Convertir a coordenadas reales
                const realCoords = viewRectToReal(viewX1, viewY1, viewX2, viewY2);

                document.getElementById('modal-tipo').value = 'maquina';
                document.getElementById('modal-x1').value = realCoords.x1;
                document.getElementById('modal-y1').value = realCoords.y1;
                document.getElementById('modal-x2').value = realCoords.x2;
                document.getElementById('modal-y2').value = realCoords.y2;
                document.getElementById('modal-nombre').value = machine.nombre;
                document.getElementById('modal-maquina').value = machine.id;
                document.getElementById('modal-title').textContent = `Colocar: ${machine.nombre}`;
                document.getElementById('modal-pos-display').textContent = `(${realCoords.x1},${realCoords.y1})-(${realCoords.x2},${realCoords.y2})`;
                document.getElementById('modal-size-display').textContent = `${machine.ancho}x${machine.largo}`;
                document.getElementById('modal-maquina-group').classList.remove('hidden');
                DOM.modal.classList.remove('hidden');
            });
        }

        // Init
        init();

        // Recalcular al redimensionar ventana
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                setupGrid();
                renderObjects();
            }, 100);
        });
    });
    </script>

</x-app-layout>
