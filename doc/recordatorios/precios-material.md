# Sistema de Precios de Material

**Fecha:** 2026-01-13
**Estado:** Implementado y funcional

## Resumen

Sistema para calcular el coste de material de los elementos de ferralla. Permite configurar precios de forma flexible desde la interfaz.

## Formula de Calculo

```
Coste = (Precio Referencia + Incremento Diametro + Incremento Formato) x Toneladas
```

- **Precio Referencia**: Viene del PedidoGlobal asociado al producto
- **Incremento Diametro**: Segun tabla `precios_material_diametros`
- **Incremento Formato**: Segun excepciones o formato base

## Estructura de Base de Datos

### Tablas creadas

1. **precios_material_diametros**
   - `diametro` (int): Diametro en mm
   - `incremento` (decimal): Euros/tonelada
   - `activo` (boolean)

2. **precios_material_formatos**
   - `codigo`: estandar_12m, largo_especial, corto_6m, encarretado
   - `nombre`, `descripcion`
   - `longitud_min`, `longitud_max`: Rango en metros
   - `es_encarretado` (boolean)
   - `incremento` (decimal): Valor BASE por defecto

3. **precios_material_excepciones**
   - `distribuidor_id` (nullable): Si es null, aplica a todos los distribuidores
   - `fabricante_id`: Fabricante al que aplica
   - `formato_codigo`: Formato al que aplica
   - `incremento`: Valor especial para esta combinacion
   - `notas`

4. **precios_material_config**
   - Configuracion general (clave/valor)
   - Ej: `producto_base_referencia_id`

### Migraciones

- `2026_01_13_120000_create_precios_material_tables.php` - Crea tablas iniciales
- `2026_01_13_140000_refactor_precios_material_flexible.php` - Refactoriza para flexibilidad

## Prioridad de Busqueda de Incremento Formato

1. **Excepcion especifica**: distribuidor + fabricante + formato
2. **Excepcion fabricante**: fabricante + formato (distribuidor = null)
3. **Formato base**: Valor por defecto del formato

## Archivos Principales

### Modelos
- `app/Models/PrecioMaterialDiametro.php`
- `app/Models/PrecioMaterialFormato.php`
- `app/Models/PrecioMaterialExcepcion.php`
- `app/Models/PrecioMaterialConfig.php`

### Servicio
- `app/Services/PrecioMaterialService.php`
  - `calcularCosteElemento(Elemento)`: Calcula coste de un elemento
  - `calcularCosteObra(Obra)`: Suma costes de todos los elementos

### Controlador y Vista
- `app/Http/Controllers/PrecioMaterialController.php`
- `resources/views/logistica/precios-material/index.blade.php`

### Seeder
- `database/seeders/PrecioMaterialSeeder.php`

## Acceso

**Menu:** Logistica > Precios Material
**Ruta:** `/precios-material`

## Configuracion Actual

### Incrementos por Diametro (respecto a 16mm)
| Diametro | Incremento |
|----------|------------|
| 6mm | +70 |
| 8mm | +50 |
| 10mm | +20 |
| 12mm | +5 |
| 14mm | 0 |
| 16mm | 0 (base) |
| 20mm | 0 |
| 25mm | +8 |
| 32mm | +28 |
| 40mm | +50 |

### Incrementos por Formato (base)
| Formato | Incremento |
|---------|------------|
| Estandar 12m | 0 |
| Largo especial (14-16m) | +5 |
| Corto 6m | +10 |
| Encarretado | +30 |

### Excepciones Configuradas
- **Siderurgica Sevillana + Encarretado**: +20 (en vez de +30)

## Uso en Codigo

### Obtener coste de un elemento
```php
$elemento->coste_material; // Accessor, devuelve float
$elemento->coste_material_desglose; // Array con detalles
```

### Obtener coste total de una obra
```php
$obra->coste_material_total; // Accessor, devuelve float
$obra->getCosteMaterialTotal(); // Array con coste_total, elementos_count, errores_count
```

## Pendiente / Ideas Futuras

- [ ] Mostrar coste de material en vista de elementos
- [ ] Mostrar coste total en vista de obra
- [ ] Historico de cambios de precios
- [ ] Importar/exportar configuracion
- [ ] Alertas cuando cambian precios

## Ejemplo de Calculo

**Producto:** Encarretado 8mm de Siderurgica Sevillana
**Precio referencia:** 600 euros/t

```
Incremento diametro 8mm: +50
Incremento encarretado (excepcion Siderurgica): +20
---
Precio/tonelada: 600 + 50 + 20 = 670 euros/t
```

Si fuera otro fabricante (sin excepcion):
```
Precio/tonelada: 600 + 50 + 30 = 680 euros/t
```
