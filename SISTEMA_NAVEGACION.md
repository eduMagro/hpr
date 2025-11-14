# 🧭 Sistema de Navegación Profesional - Documentación

## 📋 Tabla de Contenidos
1. [Descripción General](#descripción-general)
2. [Archivos Creados](#archivos-creados)
3. [Características Implementadas](#características-implementadas)
4. [Instalación y Configuración](#instalación-y-configuración)
5. [Uso y Personalización](#uso-y-personalización)
6. [Sistema de Permisos](#sistema-de-permisos)
7. [Troubleshooting](#troubleshooting)

---

## 📖 Descripción General

Sistema completo de navegación multinivel con menú lateral colapsable, breadcrumbs dinámicos, búsqueda global y filtrado de permisos integrado.

### ✨ Características Principales

- ✅ **Menú lateral de 3 niveles** (Secciones → Módulos → Acciones)
- ✅ **Colapsable** (modo completo / solo iconos)
- ✅ **Búsqueda global** con atajo de teclado (Cmd/Ctrl + K)
- ✅ **Breadcrumbs dinámicos** con navegación
- ✅ **Sistema de permisos integrado** con caché
- ✅ **Responsive** (desktop, tablet, móvil)
- ✅ **Detección automática** de sección activa
- ✅ **Colores diferenciados** por categoría

---

## 📁 Archivos Creados

### 1. Configuración
```
config/menu.php
```
Archivo de configuración centralizada del menú con estructura de 6 secciones:
- Producción (36+ módulos)
- Inventario
- Comercial
- Compras
- Recursos Humanos
- Sistema

### 2. Servicio
```
app/Services/MenuBuilder.php
```
Servicio que:
- Filtra el menú según permisos del usuario
- Genera breadcrumbs dinámicos
- Maneja caché de menú por usuario
- Integra con sistema de permisos existente

### 3. Componentes Blade
```
resources/views/components/sidebar-menu.blade.php
resources/views/components/breadcrumbs.blade.php
```

### 4. Layouts Actualizados
```
resources/views/layouts/app.blade.php (modificado)
resources/views/layouts/navigation.blade.php (optimizado)
```

---

## 🚀 Instalación y Configuración

### Paso 1: SQL - Configurar Secciones

Ejecuta este SQL en tu base de datos:

```sql
-- Ocultar todas las secciones antiguas excepto Asistente Virtual
UPDATE secciones
SET mostrar_en_dashboard = 0
WHERE id != 39;

-- Volver a mostrar el Asistente Virtual
UPDATE secciones
SET mostrar_en_dashboard = 1
WHERE id = 39;

-- Insertar las 6 nuevas secciones principales
INSERT INTO secciones (nombre, ruta, icono, mostrar_en_dashboard, created_at, updated_at) VALUES
('Producción', 'secciones.produccion', 'imagenes/iconos/maquinas.png', 1, NOW(), NOW()),
('Inventario', 'secciones.inventario', 'imagenes/iconos/materiales.png', 1, NOW(), NOW()),
('Comercial', 'secciones.comercial', 'imagenes/iconos/clientes.png', 1, NOW(), NOW()),
('Compras', 'secciones.compras', 'imagenes/iconos/entradas.png', 1, NOW(), NOW()),
('Recursos Humanos', 'secciones.recursos-humanos', 'imagenes/iconos/departamentos.png', 1, NOW(), NOW()),
('Sistema', 'secciones.sistema', 'imagenes/iconos/estadisticas.png', 1, NOW(), NOW());
```

### Paso 2: Limpiar Caché

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Paso 3: Verificar

1. Inicia sesión en la aplicación
2. Deberías ver el menú lateral a la izquierda
3. Navega entre secciones y verifica los breadcrumbs
4. Prueba el atajo Cmd/Ctrl + K para búsqueda

---

## 🎨 Uso y Personalización

### Agregar un Nuevo Módulo

Edita `config/menu.php` y agrega en la sección correspondiente:

```php
[
    'label' => 'Nuevo Módulo',
    'route' => 'modulo.index',
    'icon' => '🆕',
    'actions' => [
        ['label' => 'Ver todos', 'route' => 'modulo.index', 'permission' => 'ver'],
        ['label' => 'Crear nuevo', 'route' => 'modulo.create', 'permission' => 'crear'],
        ['label' => 'Exportar', 'route' => 'modulo.export', 'permission' => 'ver'],
    ]
]
```

### Cambiar Colores de Secciones

En `config/menu.php`, modifica el campo `color`:

```php
'color' => 'blue',  // Opciones: blue, green, purple, orange, indigo, gray, red
```

### Agregar una Nueva Sección

```php
[
    'id' => 'nueva-seccion',
    'label' => 'Nueva Sección',
    'icon' => '🎯',
    'route' => 'secciones.nueva',
    'color' => 'teal',
    'submenu' => [
        // ... módulos
    ]
]
```

### Personalizar Breadcrumbs

Los breadcrumbs se generan automáticamente. Para agregar lógica personalizada, edita:

```php
// app/Services/MenuBuilder.php - Método getBreadcrumbs()
```

---

## 🔒 Sistema de Permisos

### Cómo Funciona

1. **Acceso Total**: Emails en `config/acceso.php` → ven todo
2. **Por Rol**:
   - **Operario**: Solo prefijos configurados
   - **Transportista**: Solo rutas específicas
   - **Oficina**: Permisos granulares de BD

3. **Por Departamento**: Usuarios heredan permisos de sus departamentos

4. **Cache**: El menú se cachea por 1 hora por usuario

### Limpiar Cache de un Usuario

```php
use App\Services\MenuBuilder;

// Limpiar cache de usuario específico
MenuBuilder::clearUserCache($userId);

// Limpiar todo el cache
MenuBuilder::clearAllCache();
```

### Agregar Permisos a un Módulo

1. Crea la sección en tabla `secciones`
2. Asigna permisos en tabla `permisos_acceso`
3. O asigna departamentos en tabla `departamento_seccion`

---

## 🎯 Estructura del Menú

### Nivel 1: Secciones Principales (6)
```
🏭 Producción
📦 Inventario
🤝 Comercial
🛒 Compras
👥 Recursos Humanos
⚙️ Sistema
```

### Nivel 2: Módulos (36+)

**Producción:**
- Máquinas, Planillas, Elementos, Etiquetas, Paquetes

**Inventario:**
- Productos, Ubicaciones, Movimientos, Entradas, Salidas Ferralla, Salidas Almacén

**Comercial:**
- Clientes, Empresas, Proveedores, Transporte, Planificación Portes

**Compras:**
- Pedidos, Pedidos Globales

**Recursos Humanos:**
- Usuarios, Departamentos, Vacaciones, Turnos, Nóminas, Planificación Trabajadores

**Sistema:**
- Alertas, Papelera, Ayuda, Estadísticas

### Nivel 3: Acciones Rápidas

Aparecen al hacer hover sobre un módulo:
- Ver todos
- Crear nuevo
- Acciones específicas del módulo

---

## 🔍 Búsqueda Global

### Atajos de Teclado

- **Abrir búsqueda**: `Cmd + K` (Mac) / `Ctrl + K` (Windows)
- **Cerrar búsqueda**: `ESC`
- **Navegar resultados**: `↑` `↓` (en desarrollo)
- **Seleccionar**: `Enter` (en desarrollo)

### Funcionamiento

1. Presiona `Cmd/Ctrl + K`
2. Escribe el nombre del módulo o sección
3. Los resultados se filtran en tiempo real
4. Haz clic en un resultado para navegar

---

## 🎨 Diseño Responsive

### Desktop (>1024px)
- Menú lateral completo visible
- Breadcrumbs en línea
- Acciones rápidas al hover

### Tablet (768-1023px)
- Menú lateral colapsable
- Breadcrumbs adaptados
- Touch-friendly

### Mobile (<768px)
- Menú lateral oculto por defecto
- Breadcrumbs compactos
- Botón hamburguesa para abrir menú

---

## ⚙️ Configuración Avanzada

### Cambiar Duración del Cache

En `app/Services/MenuBuilder.php`:

```php
// Línea 16
return Cache::remember("menu_user_{$user->id}", 3600, function () use ($user) {
    // 3600 = 1 hora. Cambia a tu preferencia.
});
```

### Agregar Badges Dinámicos

En `config/menu.php`, agrega:

```php
[
    'label' => 'Alertas',
    'route' => 'alertas.index',
    'icon' => '🔔',
    'badge' => 'alertas_count', // Clave para el contador
]
```

Luego en `sidebar-menu.blade.php`, actualiza la lógica para obtener el valor:

```php
@if(isset($item['badge']))
    @php
        $badgeValue = match($item['badge']) {
            'alertas_count' => auth()->user()->alertasNoLeidas()->count(),
            default => 0
        };
    @endphp
    @if($badgeValue > 0)
        <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
            {{ $badgeValue }}
        </span>
    @endif
@endif
```

### Personalizar Iconos

Puedes usar:
- **Emojis**: `'icon' => '🏭'`
- **SVG**: `'icon' => '<svg>...</svg>'`
- **Clases de iconos**: `'icon' => 'fas fa-cog'`

---

## 🐛 Troubleshooting

### El menú no aparece

1. Verifica que Alpine.js esté cargado:
```html
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
```

2. Limpia cache:
```bash
php artisan cache:clear
php artisan view:clear
```

3. Verifica que el usuario tenga permisos

### El menú muestra todo el contenido

El sistema de permisos puede estar deshabilitado. Verifica:

```php
// app/Services/MenuBuilder.php
// Revisa que userCanAccessRoute() esté funcionando
```

### Los breadcrumbs no funcionan

1. Verifica que la ruta actual tenga un nombre: `Route::get('...')->name('ruta.nombre')`
2. Asegúrate de que la ruta esté en `config/menu.php`

### El caché no se actualiza

Limpia el caché manualmente:

```bash
php artisan cache:clear
```

O limpia el cache del usuario específico:

```php
MenuBuilder::clearUserCache(auth()->id());
```

### Búsqueda no funciona (Cmd+K)

1. Verifica que Alpine.js esté cargado correctamente
2. Revisa la consola del navegador para errores JavaScript
3. Asegúrate de que `x-data` esté inicializando correctamente

---

## 📊 Performance

### Optimizaciones Implementadas

1. **Cache por usuario** (1 hora): Reduce consultas a BD
2. **Carga lazy de acciones**: Solo se muestran al hover
3. **Filtrado en backend**: Solo se envían opciones permitidas
4. **Alpine.js**: Framework ligero para interactividad

### Monitoreo

Para ver el impacto del caché:

```php
// En cualquier controlador
dd(Cache::has("menu_user_" . auth()->id()));
```

---

## 🔄 Actualizaciones Futuras

### En desarrollo:
- [ ] Navegación con teclado en búsqueda
- [ ] Historial de navegación reciente
- [ ] Favoritos personalizables
- [ ] Modo oscuro
- [ ] Drag & drop para reordenar

### Sugerencias de mejora:
1. Integrar con Laravel Scout para búsqueda avanzada
2. Agregar analytics de uso de módulos
3. Crear dashboard de administración del menú
4. Exportar/importar configuración de menú

---

## 📞 Soporte

Si encuentras problemas o tienes sugerencias:

1. Revisa la consola del navegador
2. Verifica los logs de Laravel: `storage/logs/laravel.log`
3. Limpia todos los cachés
4. Revisa los permisos del usuario actual

---

## 📝 Changelog

### v1.0.0 (2025)
- ✅ Implementación inicial del sistema de navegación
- ✅ Menú lateral de 3 niveles
- ✅ Integración con sistema de permisos
- ✅ Búsqueda global con Cmd+K
- ✅ Breadcrumbs dinámicos
- ✅ Sistema de caché
- ✅ Diseño responsive

---

**Desarrollado con ❤️ para Manager App**
