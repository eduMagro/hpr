<?php

return [
    'main' => [
        [
            'id' => 'produccion',
            'label' => 'Producción',
            'icon' => '🏭',
            'route' => 'secciones.produccion',
            'color' => 'blue',
            'submenu' => [
                [
                    'label' => 'Máquinas',
                    'route' => 'maquinas.index',
                    'icon' => '⚙️',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'maquinas.index', 'permission' => 'ver'],
                        ['label' => 'Nueva máquina', 'route' => 'maquinas.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Incidencias',
                    'route' => 'incidencias.index',
                    'icon' => '🔧',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'incidencias.index', 'permission' => 'ver'],
                        ['label' => 'Nueva incidencia', 'route' => 'incidencias.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Materia Prima',
                    'route' => 'productos.index',
                    'icon' => '🧱',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'productos.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo producto', 'route' => 'productos.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Planillas',
                    'route' => 'planillas.index',
                    'icon' => '📄',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'planillas.index', 'permission' => 'ver'],
                        ['label' => 'Nueva planilla', 'route' => 'planillas.create', 'permission' => 'crear'],
                        ['label' => 'Órdenes', 'route' => 'produccion.verOrdenesPlanillas', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Etiquetas',
                    'route' => 'etiquetas.index',
                    'icon' => '🏷️',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'etiquetas.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Elementos',
                    'route' => 'elementos.index',
                    'icon' => '🔩',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'elementos.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Paquetes',
                    'route' => 'paquetes.index',
                    'icon' => '📦',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'paquetes.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo paquete', 'route' => 'paquetes.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Ubicaciones',
                    'route' => 'ubicaciones.index',
                    'icon' => '📍',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'ubicaciones.index', 'permission' => 'ver'],
                        ['label' => 'Nueva ubicación', 'route' => 'ubicaciones.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Movimientos',
                    'route' => 'movimientos.index',
                    'icon' => '🔄',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'movimientos.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo movimiento', 'route' => 'movimientos.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Panel de Control',
                    'route' => 'production.logs.index',
                    'icon' => '📊',
                    'actions' => [
                        ['label' => 'Ver panel', 'route' => 'production.logs.index', 'permission' => 'ver'],
                    ]
                ],
            ]
        ],
        [
            'id' => 'planificacion',
            'label' => 'Planificación',
            'icon' => '📅',
            'route' => 'secciones.planificacion',
            'color' => 'purple',
            'submenu' => [
                [
                    'label' => 'Planificación Máquinas',
                    'route' => 'produccion.verMaquinas',
                    'icon' => '⚙️',
                    'actions' => [
                        ['label' => 'Ver planificación', 'route' => 'produccion.verMaquinas', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Planificación Ensamblaje',
                    'route' => 'produccion.maquinasEnsamblaje',
                    'icon' => '🔧',
                    'actions' => [
                        ['label' => 'Ver ensambladoras', 'route' => 'produccion.maquinasEnsamblaje', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Planificación Portes',
                    'route' => 'planificacion.index',
                    'icon' => '🚚',
                    'actions' => [
                        ['label' => 'Ver calendario', 'route' => 'planificacion.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Trabajadores',
                    'route' => 'produccion.verTrabajadores',
                    'icon' => '👷',
                    'actions' => [
                        ['label' => 'Ver planificación', 'route' => 'produccion.verTrabajadores', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Trabajadores Obra',
                    'route' => 'produccion.verTrabajadoresObra',
                    'icon' => '🏗️',
                    'actions' => [
                        ['label' => 'Ver asignaciones', 'route' => 'produccion.verTrabajadoresObra', 'permission' => 'ver'],
                    ]
                ],
            ]
        ],
        [
            'id' => 'logistica',
            'label' => 'Logística',
            'icon' => '🚛',
            'route' => 'secciones.logistica',
            'color' => 'green',
            'submenu' => [
                [
                    'label' => 'Entradas',
                    'route' => 'entradas.index',
                    'icon' => '⬇️',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'entradas.index', 'permission' => 'ver'],
                        ['label' => 'Nueva entrada', 'route' => 'entradas.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Salidas Ferralla',
                    'route' => 'salidas-ferralla.index',
                    'icon' => '➡️',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'salidas-ferralla.index', 'permission' => 'ver'],
                        ['label' => 'Nueva salida', 'route' => 'salidas-ferralla.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Salidas Almacén',
                    'route' => 'salidas-almacen.index',
                    'icon' => '📤',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'salidas-almacen.index', 'permission' => 'ver'],
                        ['label' => 'Nueva salida', 'route' => 'salidas-almacen.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Pedidos Compra',
                    'route' => 'pedidos.index',
                    'icon' => '🛒',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'pedidos.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo pedido', 'route' => 'pedidos.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Pedidos Globales',
                    'route' => 'pedidos_globales.index',
                    'icon' => '🌐',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'pedidos_globales.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo pedido', 'route' => 'pedidos_globales.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Proveedores',
                    'route' => 'fabricantes.index',
                    'icon' => '🏭',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'fabricantes.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo proveedor', 'route' => 'fabricantes.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Empresas Transporte',
                    'route' => 'empresas-transporte.index',
                    'icon' => '🚚',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'empresas-transporte.index', 'permission' => 'ver'],
                        ['label' => 'Nueva empresa', 'route' => 'empresas-transporte.create', 'permission' => 'crear'],
                    ]
                ],
            ]
        ],
        [
            'id' => 'rrhh',
            'label' => 'Recursos Humanos',
            'icon' => '👥',
            'route' => 'secciones.recursos-humanos',
            'color' => 'indigo',
            'submenu' => [
                [
                    'label' => 'Usuarios',
                    'route' => 'users.index',
                    'icon' => '👤',
                    'actions' => [
                        ['label' => 'Ver tabla usuarios', 'route' => 'users.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Incorporaciones',
                    'route' => 'incorporaciones.index',
                    'icon' => '📋',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'incorporaciones.index', 'permission' => 'ver'],
                        ['label' => 'Nueva incorporación', 'route' => 'incorporaciones.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Registrar Usuario',
                    'route' => 'register',
                    'icon' => '➕',
                    'actions' => [
                        ['label' => 'Crear nuevo usuario', 'route' => 'register', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Vacaciones',
                    'route' => 'vacaciones.index',
                    'icon' => '🌴',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'vacaciones.index', 'permission' => 'ver'],
                        ['label' => 'Solicitar', 'route' => 'vacaciones.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Registros Entrada/Salida',
                    'route' => 'asignaciones-turnos.index',
                    'icon' => '🕐',
                    'actions' => [
                        ['label' => 'Ver registros', 'route' => 'asignaciones-turnos.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'EPIS',
                    'route' => 'epis.index',
                    'icon' => '🦺',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'epis.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo EPI', 'route' => 'epis.create', 'permission' => 'crear'],
                    ]
                ],
            ]
        ],
        [
            'id' => 'comercial',
            'label' => 'Comercial',
            'icon' => '🤝',
            'route' => 'secciones.comercial',
            'color' => 'orange',
            'submenu' => [
                [
                    'label' => 'Clientes',
                    'route' => 'clientes.index',
                    'icon' => '👥',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'clientes.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo cliente', 'route' => 'clientes.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Empresas',
                    'route' => 'empresas.index',
                    'icon' => '🏢',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'empresas.index', 'permission' => 'ver'],
                    ]
                ],
            ]
        ],
        [
            'id' => 'sistema',
            'label' => 'Sistema',
            'icon' => '⚙️',
            'route' => 'secciones.sistema',
            'color' => 'gray',
            'submenu' => [
                [
                    'label' => 'Turnos',
                    'route' => 'turnos.index',
                    'icon' => '🕐',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'turnos.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo turno', 'route' => 'turnos.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Alertas',
                    'route' => 'alertas.index',
                    'icon' => '🔔',
                    'badge' => 'alertas_count', // Se llenará dinámicamente
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'alertas.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Papelera',
                    'route' => 'papelera.index',
                    'icon' => '🗑️',
                    'actions' => [
                        ['label' => 'Ver elementos', 'route' => 'papelera.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Ayuda',
                    'route' => 'ayuda.index',
                    'icon' => '❓',
                    'actions' => [
                        ['label' => 'Centro de ayuda', 'route' => 'ayuda.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Atajos de Teclado',
                    'route' => 'atajos.index',
                    'icon' => '⌨️',
                    'actions' => [
                        ['label' => 'Ver atajos', 'route' => 'atajos.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Estadísticas',
                    'route' => 'estadisticas.index',
                    'icon' => '📊',
                    'actions' => [
                        ['label' => 'Ver panel', 'route' => 'estadisticas.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Permisos y configuración',
                    'route' => 'departamentos.index',
                    'icon' => '🔐',
                    'actions' => [
                        ['label' => 'Gestionar permisos', 'route' => 'departamentos.index', 'permission' => 'ver'],
                    ]
                ],
            ]
        ],
    ],

    // Menús contextuales para cada módulo
    'context_menus' => [

        // PRODUCCIÓN
        'planillas' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'planillas.index', 'icon' => '📋'],
                ['label' => 'Nueva Planilla', 'route' => 'planillas.create', 'icon' => '➕'],
                ['label' => 'Órdenes', 'route' => 'produccion.verOrdenesPlanillas', 'icon' => '📦'],
            ],
            'config' => [
                'colorBase' => 'blue',
                'style' => 'tabs',
                'mobileLabel' => 'Planillas',
            ]
        ],

        'maquinas' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'maquinas.index', 'icon' => '⚙️'],
                ['label' => 'Nueva Máquina', 'route' => 'maquinas.create', 'icon' => '➕'],
                ['label' => 'Planificación', 'route' => 'produccion.verMaquinas', 'icon' => '📅'],
            ],
            'config' => [
                'colorBase' => 'blue',
                'style' => 'tabs',
                'mobileLabel' => 'Máquinas',
            ]
        ],

        'elementos' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'elementos.index', 'icon' => '🔩'],
            ],
            'config' => [
                'colorBase' => 'blue',
                'style' => 'tabs',
                'mobileLabel' => 'Elementos',
            ]
        ],

        'etiquetas' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'etiquetas.index', 'icon' => '🏷️'],
            ],
            'config' => [
                'colorBase' => 'blue',
                'style' => 'tabs',
                'mobileLabel' => 'Etiquetas',
            ]
        ],

        'paquetes' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'paquetes.index', 'icon' => '📦'],
                ['label' => 'Nuevo Paquete', 'route' => 'paquetes.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'blue',
                'style' => 'tabs',
                'mobileLabel' => 'Paquetes',
            ]
        ],

        // INVENTARIO
        'productos' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'productos.index', 'icon' => '🧱'],
                ['label' => 'Nuevo Producto', 'route' => 'productos.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'green',
                'style' => 'tabs',
                'mobileLabel' => 'Materia Prima',
            ]
        ],

        'ubicaciones' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'ubicaciones.index', 'icon' => '📍'],
                ['label' => 'Nueva Ubicación', 'route' => 'ubicaciones.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'green',
                'style' => 'tabs',
                'mobileLabel' => 'Ubicaciones',
            ]
        ],

        'movimientos' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'movimientos.index', 'icon' => '🔄'],
                ['label' => 'Nuevo Movimiento', 'route' => 'movimientos.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'green',
                'style' => 'tabs',
                'mobileLabel' => 'Movimientos',
            ]
        ],

        'entradas' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'entradas.index', 'icon' => '⬇️'],
                ['label' => 'Nueva Entrada', 'route' => 'entradas.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'green',
                'style' => 'tabs',
                'mobileLabel' => 'Entradas',
            ]
        ],

        'salidas' => [
            'items' => [
                ['label' => 'Salidas Ferralla', 'route' => 'salidas-ferralla.index', 'icon' => '➡️'],
                ['label' => 'Salidas Almacén', 'route' => 'salidas-almacen.index', 'icon' => '📤'],
            ],
            'config' => [
                'colorBase' => 'green',
                'style' => 'pills',
                'mobileLabel' => 'Tipo de Salida',
            ]
        ],

        'salidas-ferralla' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'salidas-ferralla.index', 'icon' => '📋'],
                ['label' => 'Nueva Salida', 'route' => 'salidas-ferralla.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'green',
                'style' => 'tabs',
                'mobileLabel' => 'Salidas Ferralla',
            ]
        ],

        'salidas-almacen' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'salidas-almacen.index', 'icon' => '📋'],
                ['label' => 'Nueva Salida', 'route' => 'salidas-almacen.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'green',
                'style' => 'tabs',
                'mobileLabel' => 'Salidas Almacén',
            ]
        ],

        // COMERCIAL
        'clientes' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'clientes.index', 'icon' => '👥'],
                ['label' => 'Nuevo Cliente', 'route' => 'clientes.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'purple',
                'style' => 'tabs',
                'mobileLabel' => 'Clientes',
            ]
        ],

        'empresas' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'empresas.index', 'icon' => '🏢'],
            ],
            'config' => [
                'colorBase' => 'purple',
                'style' => 'tabs',
                'mobileLabel' => 'Empresas',
            ]
        ],

        'fabricantes' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'fabricantes.index', 'icon' => '🏭'],
                ['label' => 'Nuevo Proveedor', 'route' => 'fabricantes.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'purple',
                'style' => 'tabs',
                'mobileLabel' => 'Proveedores',
            ]
        ],

        'empresas-transporte' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'empresas-transporte.index', 'icon' => '🚚'],
                ['label' => 'Nueva Empresa', 'route' => 'empresas-transporte.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'purple',
                'style' => 'tabs',
                'mobileLabel' => 'Transporte',
            ]
        ],

        'planificacion' => [
            'items' => [
                ['label' => 'Calendario', 'route' => 'planificacion.index', 'icon' => '📅'],
            ],
            'config' => [
                'colorBase' => 'purple',
                'style' => 'tabs',
                'mobileLabel' => 'Planificación Portes',
            ]
        ],

        // COMPRAS
        'pedidos' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'pedidos.index', 'icon' => '🛒'],
                ['label' => 'Nuevo Pedido', 'route' => 'pedidos.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'orange',
                'style' => 'tabs',
                'mobileLabel' => 'Pedidos',
            ]
        ],

        'pedidos-globales' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'pedidos_globales.index', 'icon' => '🌐'],
                ['label' => 'Nuevo Pedido', 'route' => 'pedidos_globales.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'orange',
                'style' => 'tabs',
                'mobileLabel' => 'Pedidos Globales',
            ]
        ],

        // RECURSOS HUMANOS
        'usuarios' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'users.index', 'icon' => '👤'],
                ['label' => 'Nuevo Usuario', 'route' => 'users.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Usuarios',
                'checkRole' => 'oficina',
            ]
        ],

        'incorporaciones' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'incorporaciones.index', 'icon' => '📋'],
                ['label' => 'Nueva Incorporación', 'route' => 'incorporaciones.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Incorporaciones',
            ]
        ],

        'departamentos' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'departamentos.index', 'icon' => '🏛️'],
                ['label' => 'Nuevo Departamento', 'route' => 'departamentos.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Departamentos',
            ]
        ],

        'vacaciones' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'vacaciones.index', 'icon' => '🌴'],
                ['label' => 'Solicitar', 'route' => 'vacaciones.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Vacaciones',
            ]
        ],

        'turnos' => [
            'items' => [
                ['label' => 'Asignaciones', 'route' => 'asignaciones-turnos.index', 'icon' => '⏱️'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Turnos',
            ]
        ],

        'nominas' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'nominas.index', 'icon' => '💰'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Nóminas',
            ]
        ],

        'trabajadores' => [
            'items' => [
                ['label' => 'Planificación', 'route' => 'produccion.verTrabajadores', 'icon' => '📅'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Trabajadores',
            ]
        ],

        'epis' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'epis.index', 'icon' => '🦺'],
                ['label' => 'Nuevo EPI', 'route' => 'epis.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'EPIS',
            ]
        ],

        // SISTEMA
        'alertas' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'alertas.index', 'icon' => '🔔'],
            ],
            'config' => [
                'colorBase' => 'gray',
                'style' => 'tabs',
                'mobileLabel' => 'Alertas',
            ]
        ],

        'papelera' => [
            'items' => [
                ['label' => 'Ver Elementos', 'route' => 'papelera.index', 'icon' => '🗑️'],
            ],
            'config' => [
                'colorBase' => 'gray',
                'style' => 'tabs',
                'mobileLabel' => 'Papelera',
            ]
        ],

        'ayuda' => [
            'items' => [
                ['label' => 'Centro de Ayuda', 'route' => 'ayuda.index', 'icon' => '❓'],
            ],
            'config' => [
                'colorBase' => 'gray',
                'style' => 'tabs',
                'mobileLabel' => 'Ayuda',
            ]
        ],

        'estadisticas' => [
            'items' => [
                ['label' => 'Panel General', 'route' => 'estadisticas.index', 'icon' => '📊'],
                ['label' => 'Stock', 'route' => 'estadisticas.verStock', 'icon' => '📦'],
                ['label' => 'Obras', 'route' => 'estadisticas.verObras', 'icon' => '🏗️'],
                ['label' => 'Consumo Máquinas', 'route' => 'estadisticas.verConsumo-maquinas', 'icon' => '⚙️'],
                ['label' => 'Técnicos Despiece', 'route' => 'estadisticas.verTecnicosDespiece', 'icon' => '👷'],
            ],
            'config' => [
                'colorBase' => 'gray',
                'style' => 'pills',
                'mobileLabel' => 'Estadísticas',
            ]
        ],

    ],
];
