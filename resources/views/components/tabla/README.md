# Sistema de Componentes de Tabla

Sistema de componentes reutilizables para tablas Livewire con estilos consistentes.

## 🎯 Objetivo

Centralizar los estilos y estructura de todas las tablas del proyecto para que:

-   Un cambio en el componente se refleje en todas las tablas
-   Se reduzca el código repetitivo
-   Se mantenga consistencia visual

## 📦 Componentes Disponibles

### Estructura Principal

#### `<x-tabla.wrapper>`

Contenedor principal de la tabla con sombra y bordes redondeados.

```blade
<x-tabla.wrapper minWidth="1600px">
    <!-- contenido de la tabla -->
</x-tabla.wrapper>
```

**Props:**

-   `minWidth` (opcional): Ancho mínimo de la tabla. Default: `1000px`

---

#### `<x-tabla.header>`

Cabecera de la tabla con fondo azul.

```blade
<x-tabla.header>
    <!-- filas de encabezados y filtros -->
</x-tabla.header>
```

---

#### `<x-tabla.header-row>`

Fila de encabezados.

```blade
<x-tabla.header-row>
    <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
    <th class="p-2">Columna No Ordenable</th>
</x-tabla.header-row>
```

---

#### `<x-tabla.body>`

Body de la tabla.

```blade
<x-tabla.body>
    @forelse($items as $item)
        <!-- filas -->
    @empty
        <!-- estado vacío -->
    @endforelse
</x-tabla.body>
```

---

### Filas y Celdas

#### `<x-tabla.row>`

Fila de datos con estilos alternados (odd/even) y hover.

```blade
<x-tabla.row>
    <!-- celdas -->
</x-tabla.row>
```

**Props:**

-   `class` (opcional): Clases adicionales

---

#### `<x-tabla.cell>`

Celda estándar centrada.

```blade
<x-tabla.cell>
    {{ $valor }}
</x-tabla.cell>

<!-- Con clases adicionales -->
<x-tabla.cell class="text-left">
    {{ $valor }}
</x-tabla.cell>
```

---

### Filtros

#### `<x-tabla.filtro-row>`

Fila de filtros debajo de los encabezados.

```blade
<x-tabla.filtro-row>
    <!-- componentes de filtro -->
</x-tabla.filtro-row>
```

---

#### `<x-tabla.filtro-input>`

Input de texto para filtrar.

```blade
<x-tabla.filtro-input model="nombre" placeholder="Nombre..." />

<!-- Input tipo fecha -->
<x-tabla.filtro-input model="fecha" placeholder="Fecha..." type="date" />
```

**Props:**

-   `model`: Nombre de la propiedad Livewire (sin `wire:model`)
-   `placeholder`: Texto del placeholder
-   `type` (opcional): Tipo de input. Default: `text`

---

#### `<x-tabla.filtro-select>`

Select para filtrar.

```blade
<x-tabla.filtro-select model="estado" placeholder="Todos">
    <option value="activo">Activo</option>
    <option value="inactivo">Inactivo</option>
</x-tabla.filtro-select>
```

**Props:**

-   `model`: Nombre de la propiedad Livewire (sin `wire:model`)
-   `placeholder`: Texto de la primera opción (default: "Todos")
-   `slot`: Opciones del select

---

#### `<x-tabla.filtro-fecha>`

Input de fecha para filtrar.

```blade
<x-tabla.filtro-fecha model="fecha_inicio" />
```

**Props:**

-   `model`: Nombre de la propiedad Livewire (sin `wire:model`)

---

#### `<x-tabla.filtro-producto-base>`

Filtros para producto base (tipo, diámetro, longitud).

```blade
<x-tabla.filtro-producto-base />

<!-- Con nombres de modelo personalizados -->
<x-tabla.filtro-producto-base
    modelTipo="tipo"
    modelDiametro="diametro"
    modelLongitud="longitud"
/>
```

**Props:**

-   `modelTipo` (opcional): Default: `producto_tipo`
-   `modelDiametro` (opcional): Default: `producto_diametro`
-   `modelLongitud` (opcional): Default: `producto_longitud`

---

#### `<x-tabla.filtro-vacio>`

Celda vacía en la fila de filtros.

```blade
<x-tabla.filtro-vacio />
```

---

#### `<x-tabla.filtro-acciones>`

Celda con botón de reset filtros y slot para botones adicionales.

```blade
<x-tabla.filtro-acciones />

<!-- Con botones adicionales -->
<x-tabla.filtro-acciones>
    <button class="bg-green-500 ...">Exportar</button>
</x-tabla.filtro-acciones>
```

---

### Estados y Utilidades

#### `<x-tabla.empty-state>`

Mensaje cuando no hay registros.

```blade
<x-tabla.empty-state colspan="10" mensaje="No hay registros disponibles" />
```

**Props:**

-   `colspan`: Número de columnas que abarca
-   `mensaje` (opcional): Mensaje a mostrar. Default: "No hay registros disponibles"

---

#### `<x-tabla.footer-total>`

Footer con totales (ej: peso total).

```blade
<x-tabla.footer-total
    colspan="10"
    label="Total peso filtrado"
    value="{{ number_format($totalPeso, 2) }} kg"
/>
```

**Props:**

-   `colspan`: Número de columnas que abarca
-   `label` (opcional): Etiqueta. Default: "Total"
-   `value` (opcional): Valor a mostrar. Default: "0"

---

## 📝 Ejemplo Completo

```blade
<div>
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <x-tabla.wrapper minWidth="1600px">
        <x-tabla.header>
            {{-- Encabezados --}}
            <x-tabla.header-row>
                <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
                <x-tabla.encabezado-ordenable campo="nombre" :sortActual="$sort" :orderActual="$order" texto="Nombre" />
                <th class="p-2">Estado</th>
                <th class="p-2">Acciones</th>
            </x-tabla.header-row>

            {{-- Filtros --}}
            <x-tabla.filtro-row>
                <x-tabla.filtro-input model="id" placeholder="ID" />
                <x-tabla.filtro-input model="nombre" placeholder="Nombre..." />
                <x-tabla.filtro-select model="estado">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </x-tabla.filtro-select>
                <x-tabla.filtro-acciones />
            </x-tabla.filtro-row>
        </x-tabla.header>

        <x-tabla.body>
            @forelse($registros as $registro)
                <x-tabla.row>
                    <x-tabla.cell>{{ $registro->id }}</x-tabla.cell>
                    <x-tabla.cell>{{ $registro->nombre }}</x-tabla.cell>
                    <x-tabla.cell>
                        <span class="px-2 py-1 rounded text-xs">{{ $registro->estado }}</span>
                    </x-tabla.cell>
                    <x-tabla.cell>
                        <x-tabla.boton-ver :href="route('registro.show', $registro)" />
                        <x-tabla.boton-eliminar :action="route('registro.destroy', $registro)" />
                    </x-tabla.cell>
                </x-tabla.row>
            @empty
                <x-tabla.empty-state colspan="4" mensaje="No hay registros disponibles" />
            @endforelse
        </x-tabla.body>

        <x-tabla.footer-total
            colspan="4"
            label="Total registros"
            value="{{ $registros->total() }}"
        />
    </x-tabla.wrapper>

    {{-- Paginación --}}
    <x-tabla.paginacion-livewire :paginador="$registros" />
</div>
```

---

## 🔄 Migración de Tablas Existentes

### Antes (código repetitivo):

```blade
<div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
    <table class="w-full border border-gray-300 rounded-lg">
        <thead class="bg-blue-500 text-white">
            <tr class="text-center text-xs uppercase">
                <!-- encabezados -->
            </tr>
            <tr class="text-center text-xs uppercase">
                <th class="p-1 border">
                    <input type="text" wire:model.live.debounce.300ms="id" class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900..." />
                </th>
                <!-- más filtros repetitivos -->
            </tr>
        </thead>
        <tbody>
            <!-- filas -->
        </tbody>
    </table>
</div>
```

### Después (con componentes):

```blade
<x-tabla.wrapper>
    <x-tabla.header>
        <x-tabla.header-row>
            <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
        </x-tabla.header-row>

        <x-tabla.filtro-row>
            <x-tabla.filtro-input model="id" placeholder="ID" />
        </x-tabla.filtro-row>
    </x-tabla.header>

    <x-tabla.body>
        <!-- filas -->
    </x-tabla.body>
</x-tabla.wrapper>
```

---

## ✅ Ventajas

1. **Consistencia**: Todas las tablas tienen el mismo look & feel
2. **Mantenibilidad**: Un cambio en el componente afecta todas las tablas
3. **Menos código**: Reduce código repetitivo en un 60-70%
4. **Flexibilidad**: Los componentes permiten personalización cuando sea necesario
5. **Accesibilidad**: Centralizamos mejoras de accesibilidad

---

## 🎨 Personalización

Si una tabla necesita un estilo específico, puedes:

1. **Pasar clases adicionales:**

```blade
<x-tabla.row class="bg-red-100">
    <!-- contenido -->
</x-tabla.row>
```

2. **Usar slots:**

```blade
<x-tabla.filtro-acciones>
    <button>Botón extra</button>
</x-tabla.filtro-acciones>
```

3. **No usar el componente en esa celda:**

```blade
<x-tabla.header-row>
    <x-tabla.encabezado-ordenable ... />
    <th class="p-2 bg-purple-500">Encabezado especial</th>
</x-tabla.header-row>
```

---

## 📋 Plan de Migración

### Prioridad Alta (tablas simples):

1. ✅ movimientos-table.blade.php (HECHO)
2. productos-table.blade.php
3. entradas-table.blade.php
4. paquetes-table.blade.php
5. planillas-table.blade.php

### Prioridad Media:

6. elementos-table.blade.php
7. asignaciones-turnos-table.blade.php
8. production-logs-table.blade.php
9. users-table.blade.php

### Prioridad Baja (requieren más trabajo):

10. pedidos-table.blade.php (estructura anidada)
11. pedidos-globales-table.blade.php (dos tablas en una vista)
12. etiquetas-table.blade.php (modal complejo)

---

## 🌐 Estado de adopción (rutas)

### Vistas que ya usan los componentes de tabla

-   `/movimientos` → resources/views/movimientos/index.blade.php (Livewire `movimientos-table`)
-   `/productos` → resources/views/productos/index.blade.php (Livewire `productos-table`)
-   `/entradas` → resources/views/entradas/index.blade.php (Livewire `entradas-table`)
-   `/paquetes` → resources/views/paquetes/index.blade.php (Livewire `paquetes-table`)
-   `/planillas` → resources/views/planillas/index.blade.php (Livewire `planillas-table`)
-   `/asignaciones-turnos` → resources/views/asignaciones-turnos/index.blade.php (Livewire `asignaciones-turnos-table`)
-   `/production-logs` → resources/views/production-logs/index.blade.php (Livewire `production-logs-table`)
-   `/pedidos` → resources/views/pedidos/index.blade.php (Livewire `pedidos-table`)
-   `/pedidos_globales` → resources/views/pedidos_globales/index.blade.php (Livewire `pedidos-globales-table`)
-   `/elementos` → resources/views/elementos/index.blade.php (Livewire `elementos-table`)
-   `/etiquetas` → resources/views/etiquetas/index-livewire.blade.php (Livewire `etiquetas-table`)
-   `/clientes` y `/clientes/{id}` → resources/views/clientes/index.blade.php y clientes/show.blade.php
-   `/fabricantes` (y distribuidores) → resources/views/fabricantes/index.blade.php
-   `/alertas` → resources/views/alertas/index.blade.php
-   `/obras` → resources/views/obras/index.blade.php
-   `/pedidos-almacen-venta` → resources/views/pedidos-almacen-venta/index.blade.php
-   `/salidas-ferralla` → resources/views/salidas/index.blade.php
-   `/salidas-almacen` → resources/views/salidasAlmacen/index.blade.php
-   `/vacaciones` → resources/views/vacaciones/index.blade.php
-   `/ubicaciones` (index, create, nave-a, nave-b, almacen) → resources/views/ubicaciones/\*.blade.php
-   `/maquinas` y `/maquinas/create` → resources/views/maquinas/index.blade.php y maquinas/create.blade.php
-   `/productos/{id}` → resources/views/productos/show.blade.php

### Tablas que aún no usan `<x-tabla.*>` (tienen `<table>`)

-   `/turnos` → resources/views/configuracion/turnos/index.blade.php
-   `/departamentos` → resources/views/departamentos/index.blade.php
-   `/empresas` → resources/views/empresas/index.blade.php
-   `/nominas` (index y detalle) → resources/views/nominas/index.blade.php y nominas/show.blade.php
-   `/obras/{id}` → resources/views/obras/show.blade.php
-   `/pedidos-almacen-venta/create` → resources/views/pedidos-almacen-venta/create.blade.php
-   `/planificacion/index` (vista antigua `index2`) → resources/views/planificacion/index2.blade.php
-   `/produccion/maquinas` → resources/views/produccion/maquinas.blade.php
-   `/salidas-almacen/create` → resources/views/salidasAlmacen/create.blade.php
-   `/ubicaciones/inventario` → resources/views/ubicaciones/inventario.blade.php
-   `/asistente/permisos` → resources/views/asistente/permisos.blade.php
-   `/papelera` → resources/views/papelera/index.blade.php
-   `/panel/fabricacion/trazabilidad` → resources/views/panel/fabricacion/trazabilidad.blade.php
-   Otros con `<table>` pero fuera del flujo principal: componentes de estadísticas, modales de fabricación, PDFs/email (resources/views/components/estadisticas/_.blade.php, resources/views/components/fabricacion/_.blade.php, resources/views/pdfs/trazabilidad-pdf.blade.php, resources/views/emails/pedidos/pedido_creado.blade.php, plantillas vendor/mail).

---

## 🐛 Debugging

Si un componente no funciona:

1. Verifica que estás pasando las props correctamente
2. Revisa que el componente exista en `resources/views/components/tabla/`
3. Limpia la caché de views: `php artisan view:clear`

---

## 📞 Soporte

Para dudas o sugerencias sobre el sistema de componentes, consulta este README o revisa el ejemplo de `movimientos-table.blade.php`.
