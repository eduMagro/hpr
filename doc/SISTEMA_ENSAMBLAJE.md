# Sistema de Etiquetas de Ensamblaje

## Descripcion General

Sistema para gestionar el ensamblaje de entidades (vigas, pilares, etc.) en el taller. Permite generar etiquetas individuales por unidad de ensamblaje, seguir el estado del proceso y visualizar planos profesionales.

## Arquitectura

### Modelos Principales

| Modelo | Tabla | Descripcion |
|--------|-------|-------------|
| `EtiquetaEnsamblaje` | `etiquetas_ensamblaje` | Etiqueta individual por unidad |
| `PlanillaEntidad` | `planilla_entidades` | Entidad/ensamblaje de una planilla |
| `OrdenPlanillaEnsamblaje` | `orden_planilla_ensamblaje` | Asignacion a maquina de ensamblaje |
| `Elemento` | `elementos` | Barras/estribos que componen la entidad |

### Relaciones Clave

```
Planilla
  └── PlanillaEntidad (entidades)
        ├── EtiquetaEnsamblaje (etiquetas por unidad: 1/3, 2/3, 3/3)
        └── Elemento (barras y estribos)
              └── etiquetaRelacion → Etiqueta → Paquete → LocalizacionPaquete
```

### Cadena para Obtener Ubicacion del Paquete

```php
// Elemento → Etiqueta → Paquete → LocalizacionPaquete
$paquete = $elemento->etiquetaRelacion->paquete ?? null;
if ($paquete && $paquete->localizacionPaquete) {
    $locPaq = $paquete->localizacionPaquete;
    $ubicacion = "Fila {$locPaq->y1} Col {$locPaq->x1}";
    $coords = ['x1' => $locPaq->x1, 'y1' => $locPaq->y1, 'x2' => $locPaq->x2, 'y2' => $locPaq->y2];
}
```

**Nota**: La relacion `etiquetaRelacion` en `Elemento` busca por `etiqueta_sub_id` (string), no por ID.

## Archivos Principales

### Controllers

- `app/Http/Controllers/EtiquetaEnsamblajeController.php`
  - `actualizarEstado()` - Flujo: pendiente → en_proceso → completada
  - `generar()` - Genera etiquetas para una planilla
  - `iniciar()` / `completar()` - Cambios de estado individuales

### Services

- `app/Services/EtiquetaEnsamblajeService.php`
  - Logica de negocio para generar y gestionar etiquetas

### Helpers

- `app/Helpers/SvgBarraHelper.php`
  - `generarPlanoEnsamblado()` - Genera SVG profesional con:
    - Vista longitudinal (barras + estribos)
    - Seccion transversal
    - Tabla de elementos con formas y mini-mapa de ubicacion

### Vistas

- `resources/views/components/entidad/ensamblaje.blade.php`
  - Componente principal de etiqueta de ensamblaje
  - Muestra QR, estado, lista de elementos con ubicacion

## SVG Plano de Ensamblado

### Estructura del SVG

```
┌─────────────────────────────────────────────────────────┐
│  PLANO DE ENSAMBLADO                                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  VISTA LONGITUDINAL                                     │
│  ══════════════════════════════════════════════════     │
│  ──────────────────────────────────────────────────     │  ← Barras superiores
│  │    │    │    │    │    │    │    │    │    │        │  ← Estribos (lineas verticales)
│  ──────────────────────────────────────────────────     │  ← Barras inferiores
│                                                         │
│  SECCION           │  ELEMENTOS                         │
│  ┌────────┐        │  A ──── Ø16 (4)  [minimapa] Fila X │
│  │ A  A   │        │  B ──── Ø12 (4)  [minimapa] Fila Y │
│  │        │        │  C ═╗╔═ Ø8  (38) c/15cm            │
│  │ B  B   │        │                                    │
│  └────────┘        │                                    │
└─────────────────────────────────────────────────────────┘
```

### Metodos del Helper

```php
// Generar plano completo
$svg = SvgBarraHelper::generarPlanoEnsamblado([
    'longitud_cm' => 580,
    'armadura_longitudinal' => [...],
    'armadura_transversal' => [...],
    'separacion_estribos' => 15,
    'estribo_alto' => 30,
    'estribo_ancho' => 25,
    'elementos' => $elementosConLetra,
]);

// Dibujar forma de elemento individual
$svg = SvgBarraHelper::dibujarFormaElemento($dimensiones, $x, $y, $width, $height, $esEstribo);

// Parsear dimensiones FerraWin
$segmentos = SvgBarraHelper::parsearDimensionesCompleto("5 90d 30 90d 10 90d 30 90d 5");
// Retorna: [['longitud' => 5], ['angulo' => 90], ['longitud' => 30], ...]
```

### Formato de Dimensiones FerraWin

```
"580"                           → Barra recta de 580cm
"5 90d 30 90d 10 90d 30 90d 5"  → Estribo rectangular
"100 135d 20"                   → Barra con gancho a 135°
```

## Flujo de Estados

```
PENDIENTE → EN_PROCESO → COMPLETADA
    │           │            │
    │           │            └── Verificar si entidad completa
    │           │                └── Verificar si planilla completa
    │           └── Asigna operario, registra inicio
    └── Estado inicial al generar
```

### Verificacion de Completado

Cuando una etiqueta se completa:
1. Se verifica si todas las etiquetas de la entidad estan completadas
2. Si es asi, se marca la orden de ensamblaje como completada
3. Se verifica si todas las etiquetas de la planilla estan completadas
4. Si es asi, se marca la planilla como completada

## Ubicacion de Elementos en Nave

### Tablas Involucradas

- `localizaciones` - Zonas de la nave (nombre, nave_id)
- `localizaciones_paquetes` - Posicion exacta del paquete (x1, y1, x2, y2)
- `paquetes` - Paquetes de elementos

### Mini-mapa en SVG

El SVG incluye un mini-mapa que muestra la posicion del paquete en la nave:

```php
// En SvgBarraHelper::renderizarTablaElementos()
if ($ubicacionCoords) {
    // Dibujar rectangulo gris (nave)
    $svg .= "<rect ... fill=\"#e5e7eb\"/>";

    // Dibujar rectangulo rojo (posicion del paquete)
    $paqX = $ubicacionCoords['x1'] / $maxCols * $miniW;
    $paqY = $ubicacionCoords['y1'] / $maxRows * $miniH;
    $svg .= "<rect ... fill=\"#dc2626\"/>";
}
```

### Visualizacion en HTML

En la lista de elementos de la etiqueta:

```blade
@if($elemento->ubicacion_texto)
    <div class="text-xs text-blue-700 font-medium flex items-center gap-1">
        <svg><!-- icono ubicacion --></svg>
        <span>📦 {{ $elemento->ubicacion_texto }}</span>
    </div>
@endif
```

---

## RECORDATORIO - ULTIMO TRABAJO (2026-01-05)

### Lo que se hizo hoy:

1. **Mejora del SVG de Etiqueta de Ensamblaje**
   - Reescritura completa de `SvgBarraHelper.php`
   - Nuevo metodo `generarPlanoEnsamblado()` con vista longitudinal, seccion y tabla
   - Agregado `dibujarFormaElemento()` para representar formas de barras/estribos
   - Agregado `parsearDimensionesCompleto()` para parsear formato FerraWin

2. **Correccion de Estribos**
   - Eliminadas flechas que apuntaban hacia arriba
   - Ahora son lineas verticales simples
   - Separacion de estribos ("c/15cm") movida a la derecha para no ser tapada

3. **Indicadores de Cantidad en Seccion**
   - Agregado "4x" al lado de cada grupo de barras en la seccion transversal
   - Valores vienen de `distribucion['armadura_longitudinal']`

4. **Codigo de Elemento Visible**
   - Agregado `etiqueta_sub_id` o `codigo` en la tabla de elementos

5. **Ubicacion del Paquete**
   - Implementada cadena: Elemento → etiquetaRelacion → paquete → localizacionPaquete
   - Mini-mapa en SVG mostrando posicion en nave (rectangulo rojo)
   - Texto de ubicacion en HTML ("Fila X Col Y")

6. **Datos de Prueba Creados**
   - 3 elementos: TEST-ENS-1, TEST-ENS-2, TEST-ENS-3
   - Vinculados a EtiquetaEnsamblaje ID 1 (planilla 6430, entidad 251)
   - Paquete P25120002 con ubicacion Fila 30, Col 54

### Pendiente para continuar:

1. **Verificar visualizacion** - Acceder a la vista de ensamblaje y confirmar que:
   - El mini-mapa se muestra correctamente
   - La ubicacion "Fila 30 Col 54" aparece en los elementos
   - Las formas de elementos se dibujan bien

2. **Limpiar datos de prueba** cuando ya no se necesiten:
   ```php
   Elemento::where('codigo', 'like', 'TEST-ENS-%')->delete();
   ```

3. **Posibles mejoras futuras**:
   - Mejorar el mini-mapa con leyenda de colores
   - Agregar zoom interactivo en el SVG
   - Considerar arreglar la relacion `etiquetaRelacion` para usar ID en vez de `etiqueta_sub_id`

### Archivos modificados hoy:

- `app/Helpers/SvgBarraHelper.php` - Reescritura completa
- `resources/views/components/entidad/ensamblaje.blade.php` - Carga de ubicacion y display

### Comando para verificar datos de prueba:

```bash
php artisan tinker --execute="
use App\Models\Elemento;
\$elementos = Elemento::where('codigo', 'like', 'TEST-ENS-%')
    ->with(['etiquetaRelacion.paquete.localizacionPaquete'])
    ->get();
foreach (\$elementos as \$e) {
    echo \$e->codigo . ': ' . (\$e->etiquetaRelacion->paquete->localizacionPaquete->y1 ?? 'N/A') . PHP_EOL;
}
"
```
