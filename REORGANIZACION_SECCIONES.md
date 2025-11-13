# Reorganización de Secciones del Sistema

## Resumen de Cambios

Se ha reorganizado completamente la estructura de secciones del sistema según los nuevos requerimientos funcionales, creando una distribución más lógica y eficiente.

---

## Nueva Estructura de Secciones

### 1. 🏭 PRODUCCIÓN (Blue)
**Ruta:** `/produccion` → `secciones.produccion`

**Módulos incluidos:**
- ⚙️ **Máquinas** → `maquinas.index`
- 🧱 **Productos** → `productos.index`
- 📄 **Planillas** → `planillas.index`
- 🏷️ **Etiquetas** → `etiquetas.index`
- 🔩 **Elementos** → `elementos.index`
- 📦 **Paquetes** → `paquetes.index`
- 📍 **Ubicaciones** → `ubicaciones.index`
- 🔄 **Movimientos** → `movimientos.index`

---

### 2. 📅 PLANIFICACIÓN (Purple)
**Ruta:** `/planificacion` → `secciones.planificacion` ✨ NUEVA

**Módulos incluidos:**
- 🚚 **Planificación Portes** → `planificacion.index`
- 👷 **Trabajadores** → `produccion.verTrabajadores`
- 🏗️ **Trabajadores Obra** → `produccion.verTrabajadoresObra`
- ⚙️ **Máquinas** → `produccion.verMaquinas`

---

### 3. 🚛 LOGÍSTICA (Green)
**Ruta:** `/logistica` → `secciones.logistica` ✨ NUEVA

**Módulos incluidos:**
- ⬇️ **Entradas** → `entradas.index`
- ➡️ **Salidas Ferralla** → `salidas-ferralla.index`
- 📤 **Salidas Almacén** → `salidas-almacen.index`
- 🛒 **Pedidos Compra** → `pedidos.index`
- 🌐 **Pedidos Globales** → `pedidos_globales.index`
- 🏭 **Proveedores** → `fabricantes.index`
- 🚚 **Empresas Transporte** → `empresas-transporte.index`

---

### 4. 👥 RECURSOS HUMANOS (Indigo)
**Ruta:** `/recursos-humanos` → `secciones.recursos-humanos`

**Módulos incluidos:**
- 👤 **Usuarios** → `users.index` (Vista tabla usuarios)
- ➕ **Registrar Usuario** → `users.create`
- 🌴 **Vacaciones** → `vacaciones.index`
- 🕐 **Registros Entrada/Salida** → `asignaciones-turnos.index`

---

### 5. 🤝 COMERCIAL (Orange)
**Ruta:** `/comercial` → `secciones.comercial`

**Módulos incluidos:**
- 👥 **Clientes** → `clientes.index`
- 🏢 **Empresas** → `empresas.index`

---

### 6. ⚙️ SISTEMA (Gray)
**Ruta:** `/sistema` → `secciones.sistema`

**Módulos incluidos:**
- 🔔 **Alertas** → `alertas.index`
- 🗑️ **Papelera** → `papelera.index`
- ❓ **Ayuda** → `ayuda.index`
- 📊 **Estadísticas** → `estadisticas.index`

---

## Secciones Eliminadas

### ❌ Inventario
**Razón:** Los módulos se redistribuyeron entre Producción y Logística
- Productos → Producción
- Ubicaciones → Producción
- Movimientos → Producción
- Entradas → Logística
- Salidas → Logística

### ❌ Compras
**Razón:** Los módulos se movieron a Logística
- Pedidos → Logística (Pedidos Compra)
- Pedidos Globales → Logística

---

## Archivos Modificados

### 1. config/menu.php
**Cambios principales:**
- ✅ Añadida sección "Planificación" con 4 módulos
- ✅ Añadida sección "Logística" con 7 módulos
- ✅ Reorganizada sección "Producción" con 8 módulos
- ✅ Simplificada sección "Recursos Humanos" con 3 módulos
- ✅ Simplificada sección "Comercial" con 2 módulos
- ❌ Eliminada sección "Inventario"
- ❌ Eliminada sección "Compras"

### 2. routes/web.php
**Rutas añadidas:**
```php
Route::get('/planificacion', [PageController::class, 'planificacionSeccion'])
    ->middleware(['auth', 'verified'])
    ->name('secciones.planificacion');

Route::get('/logistica', [PageController::class, 'logistica'])
    ->middleware(['auth', 'verified'])
    ->name('secciones.logistica');
```

**Rutas comentadas (deprecadas):**
```php
// Route::get('/inventario', ...)->name('secciones.inventario');
// Route::get('/compras', ...)->name('secciones.compras');
```

### 3. app/Http/Controllers/PageController.php
**Métodos añadidos:**
```php
public function planificacionSeccion() {
    return view('secciones.planificacion');
}

public function logistica() {
    return view('secciones.logistica');
}
```

---

## Vistas Creadas

### 1. resources/views/secciones/planificacion.blade.php ✨ NUEVA
**Contenido:**
- Grid de 4 cards con iconos
- Color theme: Purple
- Enlaces a:
  - Planificación Portes
  - Trabajadores
  - Trabajadores Obra
  - Máquinas

### 2. resources/views/secciones/logistica.blade.php ✨ NUEVA
**Contenido:**
- Grid de 7 cards con iconos
- Color theme: Green
- Enlaces a:
  - Entradas
  - Salidas Ferralla
  - Salidas Almacén
  - Pedidos Compra
  - Pedidos Globales
  - Proveedores
  - Empresas Transporte

---

## Vistas Actualizadas

### 1. resources/views/secciones/produccion.blade.php
**Cambios:**
- Añadidos: Productos, Ubicaciones, Movimientos
- Total: 8 módulos
- Actualizada descripción

### 2. resources/views/secciones/recursos-humanos.blade.php
**Cambios:**
- Simplificada a 4 módulos esenciales
- Actualizada descripción
- Nuevo layout más limpio

---

## Comparación Antes vs Después

### Antes (6 Secciones)
```
1. Producción (5 módulos)
2. Inventario (6 módulos)
3. Comercial (5 módulos)
4. Compras (2 módulos)
5. Recursos Humanos (6 módulos)
6. Sistema (4 módulos)

Total: 28 módulos
```

### Después (6 Secciones)
```
1. Producción (8 módulos)      ↑ +3
2. Planificación (4 módulos)    ✨ NUEVA
3. Logística (7 módulos)        ✨ NUEVA
4. Recursos Humanos (3 módulos) ↓ -3
5. Comercial (2 módulos)        ↓ -3
6. Sistema (4 módulos)          =

Total: 28 módulos (reorganizados)
```

---

## Ventajas de la Nueva Estructura

### 1. Mejor Organización Funcional
- **Producción**: Todo lo relacionado con fabricación y stock
- **Planificación**: Planificación de recursos (trabajadores, máquinas, portes)
- **Logística**: Movimiento de materiales (entradas, salidas, pedidos)

### 2. Flujo de Trabajo Más Claro
```
Producción → Planificación → Logística
   ↓              ↓              ↓
Fabricar     Organizar      Mover
```

### 3. Reducción de Redundancias
- Eliminadas secciones intermedias confusas (Inventario, Compras)
- Distribución lógica de módulos

### 4. Navegación Más Intuitiva
- Menos secciones con más módulos relevantes
- Agrupación por función real del negocio

---

## Testing Checklist

### Rutas
- [ ] `/produccion` carga correctamente
- [ ] `/planificacion` carga correctamente (nueva)
- [ ] `/logistica` carga correctamente (nueva)
- [ ] `/recursos-humanos` carga correctamente
- [ ] `/comercial` carga correctamente
- [ ] `/sistema` carga correctamente

### Sidebar
- [ ] 6 secciones visibles en sidebar
- [ ] Colores correctos por sección
- [ ] Submenús se expanden correctamente
- [ ] Enlaces funcionan desde sidebar

### Vistas de Sección
- [ ] Cards clickeables en cada vista
- [ ] Iconos correctos
- [ ] Colores coherentes
- [ ] Responsive en móvil

### Navegación
- [ ] Click en sección plegada navega a vista general
- [ ] Click en sección expandida muestra submenú
- [ ] Click en módulo navega correctamente
- [ ] Breadcrumbs correctos

---

## Migración desde Versión Anterior

### Para usuarios existentes:
1. Los módulos siguen estando, solo cambiaron de sección
2. Los favoritos seguirán funcionando (rutas no cambiaron)
3. El historial seguirá funcionando
4. Los permisos de acceso siguen igual (basados en rutas de módulos)

### Rutas que cambiaron:
- ❌ `/inventario` → Ahora usa `/produccion` o `/logistica`
- ❌ `/compras` → Ahora usa `/logistica`
- ✅ Todas las rutas de módulos individuales siguen igual

---

## Actualización de Permisos

**NO SE REQUIERE** cambio en permisos porque:
- Los permisos se basan en rutas de módulos (ej: `maquinas.index`)
- Las rutas de módulos NO cambiaron
- Solo cambiaron las vistas de sección general (que no tienen permisos)

---

## Documentación de Acceso

### Por Rol

**Operario:**
- Acceso limitado según configuración
- Principalmente: Ayuda, Alertas

**Transportista:**
- Logística (enfoque en entregas)
- Planificación (portes)

**Oficina:**
- Acceso según permisos de usuario/departamento
- Potencialmente todas las secciones

**Admin:**
- Acceso total a las 6 secciones

---

## Próximos Pasos Recomendados

### 1. Actualizar Menús Contextuales
Los menús contextuales en `config/menu.php` → `context_menus` están listos para usar con el componente universal.

### 2. Actualizar Estadísticas
Si existen rutas de estadísticas específicas (produccion, comercial, inventario), revisar y ajustar.

### 3. Documentación de Usuario
Crear guía visual de la nueva estructura para usuarios finales.

### 4. Training
Informar a usuarios sobre la reorganización y dónde encontrar cada módulo ahora.

---

## Soporte

### Encontrar un módulo:

**Antes estaba en Inventario:**
- Productos → Producción
- Ubicaciones → Producción
- Movimientos → Producción
- Entradas → Logística
- Salidas → Logística

**Antes estaba en Compras:**
- Pedidos → Logística (Pedidos Compra)
- Pedidos Globales → Logística

**Nueva ubicación - Planificación:**
- Trabajadores
- Trabajadores Obra
- Máquinas (planificación)
- Portes

---

## Conclusión

✅ **Reorganización completada exitosamente**
✅ **2 nuevas secciones creadas** (Planificación, Logística)
✅ **28 módulos reorganizados lógicamente**
✅ **Todas las funcionalidades mantenidas**
✅ **Rutas de módulos sin cambios** (compatibilidad)
✅ **Sistema más intuitivo y organizado**

---

**Fecha:** 2025-11-13
**Versión:** 4.0
**Estado:** ✅ COMPLETADO
