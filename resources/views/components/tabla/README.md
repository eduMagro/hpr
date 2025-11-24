# Sistema de Componentes de Tabla

Sistema de componentes reutilizables para tablas Livewire con estilos consistentes.

## 🎯 Objetivo

Centralizar los estilos y estructura de todas las tablas del proyecto para que:
- Un cambio en el componente se refleje en todas las tablas
- Se reduzca el código repetitivo
- Se mantenga consistencia visual

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
- `minWidth` (opcional): Ancho mínimo de la tabla. Default: `1000px`

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
    <th class="p-2 border">Columna No Ordenable</th>
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
- `class` (opcional): Clases adicionales

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
- `model`: Nombre de la propiedad Livewire (sin `wire:model`)
- `placeholder`: Texto del placeholder
- `type` (opcional): Tipo de input. Default: `text`

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
- `model`: Nombre de la propiedad Livewire (sin `wire:model`)
- `placeholder`: Texto de la primera opción (default: "Todos")
- `slot`: Opciones del select

---

#### `<x-tabla.filtro-fecha>`
Input de fecha para filtrar.
```blade
<x-tabla.filtro-fecha model="fecha_inicio" />
```

**Props:**
- `model`: Nombre de la propiedad Livewire (sin `wire:model`)

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
- `modelTipo` (opcional): Default: `producto_tipo`
- `modelDiametro` (opcional): Default: `producto_diametro`
- `modelLongitud` (opcional): Default: `producto_longitud`

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
- `colspan`: Número de columnas que abarca
- `mensaje` (opcional): Mensaje a mostrar. Default: "No hay registros disponibles"

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
- `colspan`: Número de columnas que abarca
- `label` (opcional): Etiqueta. Default: "Total"
- `value` (opcional): Valor a mostrar. Default: "0"

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
                <th class="p-2 border">Estado</th>
                <th class="p-2 border">Acciones</th>
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
    <th class="p-2 border bg-purple-500">Encabezado especial</th>
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

## 🐛 Debugging

Si un componente no funciona:
1. Verifica que estás pasando las props correctamente
2. Revisa que el componente exista en `resources/views/components/tabla/`
3. Limpia la caché de views: `php artisan view:clear`

---

## 📞 Soporte

Para dudas o sugerencias sobre el sistema de componentes, consulta este README o revisa el ejemplo de `movimientos-table.blade.php`.
