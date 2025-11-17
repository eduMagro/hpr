# 🔍 Verificar Logs de Consumo

## Problema Detectado

El log de `CONSUMO STOCK` muestra:
```
CONSUMO DETALLADO: Ø16mm: 0.00kg [0 productos: ]
```

Cuando debería mostrar algo como:
```
CONSUMO DETALLADO: Ø16mm: 60.52kg [1 productos: P490:60.52kg]
```

## Solución Implementada

Se añadió `$consumosParaLog` en `ServicioEtiquetaBase.php` para guardar una copia del array de consumos ANTES de que se consuman durante la asignación.

## Cómo Verificar

### Paso 1: Fabricar una Etiqueta

Fabrica cualquier etiqueta desde el panel de máquinas.

### Paso 2: Revisar Log de Laravel

```bash
tail -50 storage/logs/laravel.log
```

Busca una línea como:
```
[2025-11-17 18:30:00] local.INFO: ProductionLogger::logConsumoStockPorDiametro
{
    "etiqueta_id": 12345,
    "consumos_count": 1,
    "consumos_data": {
        "16": [
            {
                "producto_id": 490,
                "consumido": 60.52
            }
        ]
    }
}
```

### Paso 3: Verificar CSV

```bash
# Ver últimas líneas del CSV de producción
tail -10 storage/app/produccion_piezas/fabricacion_2025_11.csv
```

Busca la línea de `CONSUMO STOCK` y verifica que muestre los datos correctos.

## Si Sigue Mostrando 0.00kg

### Opción A: El array está vacío al llegar al logger

Si el log de Laravel muestra `"consumos_count": 0`, significa que `$consumosParaLog` está vacío.

**Verificar:**
1. Que se esté ejecutando el código que guarda en `$consumosParaLog`
2. Que no haya un `continue` o `break` que salte la ejecución

### Opción B: El diámetro es 5 (ensambladora)

Si la máquina es tipo `ensambladora`, solo procesa Ø5. Verifica:

```php
if ($maquina->tipo === 'ensambladora' && (int)$diametro !== 5) {
    continue; // ← Esto salta el diámetro
}
```

### Opción C: No hay stock disponible

Si no hay productos con stock para ese diámetro, se lanzará una excepción antes de llegar al logging.

## Debug Adicional

Si necesitas más información, añade esto en `ServicioEtiquetaBase.php` línea 373 (antes del log):

```php
\Log::info('Antes de logConsumoStockDetallado', [
    'consumosParaLog' => $consumosParaLog,
    'count' => count($consumosParaLog)
]);
```

Luego fabrica y revisa:
```bash
tail -100 storage/logs/laravel.log | grep "Antes de logConsumoStockDetallado"
```

---

**Una vez verificado y funcionando, podemos eliminar los logs de debug.**
