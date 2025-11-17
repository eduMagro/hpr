# 📝 Sistema de Logging CSV para Fabricación

**Powered by FERRALLIN** - Sistema de Trazabilidad Completa

**Fecha:** 17 de Noviembre de 2025

---

## 🎯 Objetivo

Sistema de logging detallado que registra **TODO** el proceso de fabricación de etiquetas en archivos CSV, incluyendo:

✅ Asignación de coladas a elementos
✅ Consumo de stock por diámetro
✅ Trazabilidad completa
✅ Warnings y excepciones
✅ Estadísticas de asignación

**IMPORTANTE:** Los logs se guardan en **CSV**, NO en base de datos.

---

## 📁 Ubicación de Logs

```
storage/app/produccion_piezas/
└── fabricacion_YYYY_MM.csv
```

### Formato de Nombre

```
fabricacion_2025_11.csv  ← Noviembre 2025
fabricacion_2025_12.csv  ← Diciembre 2025
```

**Un archivo por mes** - Rotación automática.

---

## 🔧 Componentes del Sistema

### 1. ProductionLogger Service

**Archivo:** `app/Services/ProductionLogger.php`

**Métodos para Coladas:**

#### `logAsignacionColadas()`

Registra cómo se asignaron productos (con sus coladas) a cada elemento.

**Parámetros:**
```php
ProductionLogger::logAsignacionColadas(
    Etiqueta $etiqueta,           // Etiqueta fabricada
    Maquina $maquina,              // Máquina donde se fabricó
    array $elementosConColadas,    // Elementos con sus coladas asignadas
    array $productosAfectados,     // Productos consumidos
    array $warnings                // Warnings si hubo problemas
);
```

**Formato de $elementosConColadas:**
```php
[
    [
        'elemento' => Elemento,  // Modelo Eloquent
        'coladas' => [
            [
                'producto_id' => 592,
                'n_colada' => '165',
                'peso_consumido' => 1126.69
            ],
            // ... hasta 3 coladas por elemento
        ]
    ],
    // ... más elementos
]
```

**Ejemplo de Log Generado:**
```csv
"2025-11-17 14:30:25","ASIGNACION_COLADAS","Etiq#12345 | Maq:Syntax Line 28 | 5 elems | Simple:3, Doble:1, Triple:1 | Elem160132[Ø16mm,1126.69kg]→P592(Colada:165,1126.69kg) | Elem160133[Ø12mm,850.00kg]→P189(Colada:90217,500kg)+P190(Colada:90218,350kg) | ..."
```

#### `logConsumoStockPorDiametro()`

Registra cuánto stock se consumió de cada diámetro.

**Parámetros:**
```php
ProductionLogger::logConsumoStockPorDiametro(
    Etiqueta $etiqueta,
    Maquina $maquina,
    array $consumosPorDiametro
);
```

**Formato de $consumosPorDiametro:**
```php
[
    12 => [  // Diámetro 12mm
        ['producto_id' => 189, 'consumido' => 500.00],
        ['producto_id' => 190, 'consumido' => 350.00],
    ],
    16 => [  // Diámetro 16mm
        ['producto_id' => 592, 'consumido' => 1126.69],
    ],
]
```

**Ejemplo de Log Generado:**
```csv
"2025-11-17 14:30:25","CONSUMO_STOCK","Etiq#12345 | Maq:Syntax Line 28 | Ø12mm:850.00kg[2 prods:P189:500kg+P190:350kg] | Ø16mm:1126.69kg[1 prod:P592:1126.69kg]"
```

---

### 2. Integración en ServicioEtiquetaBase

**Archivo:** `app/Servicios/Etiquetas/Base/ServicioEtiquetaBase.php`

#### Llamadas de Logging (Líneas 358-362)

```php
// Después de asignar productos a elementos...

// LOG DETALLADO: Asignación de coladas a elementos
$this->logAsignacionColadasDetallada($elementosEnMaquina, $etiqueta, $maquina, $productosAfectados, $warnings);

// LOG DETALLADO: Consumo de stock por diámetro
$this->logConsumoStockDetallado($consumos, $etiqueta, $maquina);
```

#### Métodos Helper

**`logAsignacionColadasDetallada()`** (Líneas 538-599)

Prepara los datos de elementos y coladas antes de llamar al logger.

```php
protected function logAsignacionColadasDetallada(
    $elementosEnMaquina,
    Etiqueta $etiqueta,
    Maquina $maquina,
    array $productosAfectados,
    array $warnings
): void
```

**Qué hace:**
1. Recorre cada elemento fabricado
2. Busca información de cada producto asignado (producto_id, producto_id_2, producto_id_3)
3. Extrae el n_colada de cada producto
4. Construye array $elementosConColadas
5. Llama a ProductionLogger::logAsignacionColadas()

**`logConsumoStockDetallado()`** (Líneas 604-617)

Registra el consumo de stock por diámetro.

```php
protected function logConsumoStockDetallado(
    array $consumos,
    Etiqueta $etiqueta,
    Maquina $maquina
): void
```

**Qué hace:**
1. Recibe el array $consumos (ya tiene el formato correcto)
2. Llama directamente a ProductionLogger::logConsumoStockPorDiametro()

---

## 📊 Formato de Logs CSV

### Estructura del Archivo

```csv
timestamp,tipo,detalles
"2025-11-17 14:30:25","ASIGNACION_COLADAS","Etiq#12345 | ..."
"2025-11-17 14:30:25","CONSUMO_STOCK","Etiq#12345 | ..."
"2025-11-17 14:30:26","FABRICACION_INICIADA","Etiq#12346 | ..."
```

### Tipos de Log

| Tipo | Descripción |
|------|-------------|
| `ASIGNACION_COLADAS` | Detalle de qué coladas se asignaron a qué elementos |
| `CONSUMO_STOCK` | Cuánto stock se consumió por diámetro |
| `FABRICACION_INICIADA` | Inicio de fabricación |
| `FABRICACION_COMPLETADA` | Fin de fabricación |
| `RECARGA_SOLICITADA` | Se solicitó recarga de material |
| `ERROR` | Errores durante fabricación |
| `WARNING` | Advertencias |

---

## 🔍 Ejemplos Reales de Logs

### Ejemplo 1: Asignación Simple (1 producto por elemento)

```csv
"2025-11-17 14:30:25","ASIGNACION_COLADAS","Etiq#12345 | Maq:Syntax Line 28 | 3 elementos fabricados | Estadísticas: Simple:3, Doble:0, Triple:0 | Detalle: Elem160132[Ø16mm,1126.69kg]→P592(Colada:165,1126.69kg) | Elem160133[Ø16mm,950.00kg]→P592(Colada:165,950.00kg) | Elem160134[Ø16mm,800.00kg]→P592(Colada:165,800.00kg)"
```

**Interpretación:**
- Etiqueta #12345
- Máquina: Syntax Line 28
- 3 elementos fabricados
- Todos con asignación simple (1 producto cada uno)
- Todos usaron Producto 592 (Colada: 165)
- Total consumido de colada 165: 2,876.69 kg

---

### Ejemplo 2: Asignación Doble (2 productos por elemento)

```csv
"2025-11-17 15:45:10","ASIGNACION_COLADAS","Etiq#12346 | Maq:Syntax Line 28 | 2 elementos fabricados | Estadísticas: Simple:0, Doble:2, Triple:0 | Detalle: Elem160135[Ø12mm,850.00kg]→P189(Colada:90217,500kg)+P190(Colada:90218,350kg) | Elem160136[Ø12mm,1200.00kg]→P190(Colada:90218,450kg)+P191(Colada:90219,750kg)"
```

**Interpretación:**
- Etiqueta #12346
- 2 elementos fabricados
- Ambos requirieron 2 productos (stock fragmentado)
- Elemento 160135: Mezcla de coladas 90217 + 90218
- Elemento 160136: Mezcla de coladas 90218 + 90219

---

### Ejemplo 3: Asignación Triple (3 productos por elemento - MÁXIMO)

```csv
"2025-11-17 16:20:30","ASIGNACION_COLADAS","Etiq#12347 | Maq:Syntax Line 28 | 1 elemento fabricado | Estadísticas: Simple:0, Doble:0, Triple:1 | WARNING: Stock muy fragmentado | Detalle: Elem160137[Ø12mm,1500.00kg]→P189(Colada:90217,300kg)+P190(Colada:90218,400kg)+P191(Colada:90219,800kg)"
```

**Interpretación:**
- 1 elemento fabricado
- Requirió 3 productos (fragmentación extrema)
- Mezcla de 3 coladas diferentes: 90217 + 90218 + 90219
- WARNING: Indica stock muy fragmentado (considerar consolidación)

---

### Ejemplo 4: Consumo de Stock

```csv
"2025-11-17 14:30:25","CONSUMO_STOCK","Etiq#12345 | Maq:Syntax Line 28 | Consumo por diámetro: Ø12mm:850.00kg[2 productos:P189:500.00kg+P190:350.00kg] | Ø16mm:2876.69kg[1 producto:P592:2876.69kg]"
```

**Interpretación:**
- Diámetro 12mm: Consumió 850 kg de 2 productos diferentes
  - Producto 189: 500 kg
  - Producto 190: 350 kg
- Diámetro 16mm: Consumió 2,876.69 kg de 1 producto
  - Producto 592: 2,876.69 kg

---

## 💡 Casos de Uso

### 1. Rastrear Elementos por Colada

**Pregunta:** "¿Qué elementos se fabricaron con la colada 165?"

**Solución:**
```bash
# Buscar en el CSV
grep "Colada:165" storage/app/produccion_piezas/fabricacion_2025_11.csv
```

**Resultado:**
```
Elem160132[Ø16mm,1126.69kg]→P592(Colada:165,1126.69kg)
Elem160133[Ø16mm,950.00kg]→P592(Colada:165,950.00kg)
```

---

### 2. Auditoría de Trazabilidad

**Pregunta:** "¿Un cliente reporta problema con un elemento, qué coladas se usaron?"

**Solución:**
```bash
# Buscar elemento específico
grep "Elem160135" storage/app/produccion_piezas/fabricacion_2025_11.csv
```

**Resultado:**
```
Elem160135[Ø12mm,850.00kg]→P189(Colada:90217,500kg)+P190(Colada:90218,350kg)
```

**Respuesta:** Elemento 160135 se fabricó con **mezcla de 2 coladas**:
- Colada 90217: 500 kg
- Colada 90218: 350 kg

---

### 3. Análisis de Fragmentación

**Pregunta:** "¿Cuántos elementos requieren mezcla de coladas?"

**Solución:**
```bash
# Buscar asignaciones dobles y triples
grep "Doble:" storage/app/produccion_piezas/fabricacion_2025_11.csv
grep "Triple:" storage/app/produccion_piezas/fabricacion_2025_11.csv
```

---

### 4. Consumo por Diámetro

**Pregunta:** "¿Cuánto Ø12mm se consumió hoy?"

**Solución:**
```bash
# Buscar consumos de Ø12mm en la fecha de hoy
grep "2025-11-17.*CONSUMO_STOCK.*Ø12mm" storage/app/produccion_piezas/fabricacion_2025_11.csv
```

---

## 🚀 Próximos Pasos

### 1. Crear UI para Visualizar Logs

**Ubicación:** Panel de Control → Fabricación

**Funcionalidad:**
- Tabla con registros de fabricación
- Botón "Ver Detalles" en cada fila
- Modal/Dropdown mostrando:
  - Asignación de coladas
  - Consumo de stock
  - Warnings
  - Timeline de eventos

**Ejemplo de Modal:**
```
╔════════════════════════════════════════════════╗
║  Detalle de Fabricación - Etiqueta #12345      ║
╠════════════════════════════════════════════════╣
║                                                ║
║  📋 Elementos Fabricados: 3                    ║
║  ⏱️ Fecha: 17/11/2025 14:30:25                 ║
║  🏭 Máquina: Syntax Line 28                    ║
║                                                ║
║  🔍 Asignación de Coladas:                     ║
║  ├─ Elem 160132 (Ø16mm, 1126.69kg)            ║
║  │  └─ Colada: 165 (Producto #592)            ║
║  ├─ Elem 160133 (Ø16mm, 950.00kg)             ║
║  │  └─ Colada: 165 (Producto #592)            ║
║  └─ Elem 160134 (Ø16mm, 800.00kg)             ║
║     └─ Colada: 165 (Producto #592)            ║
║                                                ║
║  📊 Consumo de Stock:                          ║
║  └─ Ø16mm: 2,876.69 kg (1 producto)           ║
║     └─ P592: 2,876.69 kg                       ║
║                                                ║
║  ✅ Sin warnings                               ║
║                                                ║
╚════════════════════════════════════════════════╝
```

---

### 2. Parser de CSV

**Crear servicio:** `app/Services/ProductionLogParser.php`

**Métodos:**
```php
// Obtener logs de una etiqueta
ProductionLogParser::getLogsForEtiqueta(int $etiquetaId): array

// Obtener logs de un rango de fechas
ProductionLogParser::getLogsByDateRange(Carbon $from, Carbon $to): array

// Buscar por colada
ProductionLogParser::getLogsByColada(string $colada): array

// Buscar por elemento
ProductionLogParser::getLogsByElemento(int $elementoId): array

// Estadísticas
ProductionLogParser::getStats(string $month): array
```

---

### 3. Dashboard de Trazabilidad

**Vista:** `resources/views/panel/fabricacion/trazabilidad.blade.php`

**Funcionalidad:**
- Búsqueda por colada
- Búsqueda por elemento
- Búsqueda por fecha
- Exportar resultados
- Gráficos de consumo

---

## 📁 Estructura de Archivos

```
app/
├── Services/
│   ├── ProductionLogger.php        ← Logger principal
│   └── ProductionLogParser.php     ← Parser CSV (próximo)
│
└── Servicios/
    └── Etiquetas/
        └── Base/
            └── ServicioEtiquetaBase.php  ← Integración de logging

storage/
└── app/
    └── produccion_piezas/
        ├── fabricacion_2025_11.csv
        ├── fabricacion_2025_12.csv
        └── ...

tests/
└── Feature/
    └── Coladas/
        ├── AsignacionColadasTest.php
        ├── SISTEMA_LOGGING_CSV.md     ← Este archivo
        └── ...
```

---

## 🔐 Seguridad y Permisos

### Permisos del Directorio

```bash
# Asegurar que Laravel puede escribir
chmod 755 storage/app/produccion_piezas/
```

### Backup de Logs

**Recomendación:** Hacer backup mensual de archivos CSV:

```bash
# Automatizar con cron
0 0 1 * * cp storage/app/produccion_piezas/fabricacion_*.csv /backup/logs/
```

---

## 🧪 Testing

### Verificar que se Generan Logs

```php
// En AsignacionColadasTest.php
public function test_logs_generados_correctamente()
{
    // Fabricar etiqueta
    // ...

    // Verificar que existe el archivo CSV
    $csvPath = storage_path('app/produccion_piezas/fabricacion_' . date('Y_m') . '.csv');
    $this->assertFileExists($csvPath);

    // Verificar que contiene logs de la etiqueta
    $contenido = file_get_contents($csvPath);
    $this->assertStringContainsString('ASIGNACION_COLADAS', $contenido);
    $this->assertStringContainsString("Etiq#{$etiqueta->id}", $contenido);
}
```

---

## 📖 Comandos Útiles

### Ver Logs en Tiempo Real

```bash
# Seguir nuevos logs
tail -f storage/app/produccion_piezas/fabricacion_$(date +%Y_%m).csv
```

### Buscar por Fecha

```bash
# Logs de hoy
grep "$(date +%Y-%m-%d)" storage/app/produccion_piezas/fabricacion_$(date +%Y_%m).csv
```

### Buscar por Tipo

```bash
# Solo asignaciones de coladas
grep "ASIGNACION_COLADAS" storage/app/produccion_piezas/fabricacion_2025_11.csv

# Solo consumos de stock
grep "CONSUMO_STOCK" storage/app/produccion_piezas/fabricacion_2025_11.csv
```

### Contar Elementos Fabricados

```bash
# Total de elementos fabricados este mes
grep "ASIGNACION_COLADAS" storage/app/produccion_piezas/fabricacion_2025_11.csv | wc -l
```

---

## ⚙️ Configuración

### Variables de Entorno (Opcional)

```env
# .env
PRODUCTION_LOG_PATH=produccion_piezas
PRODUCTION_LOG_RETENTION_MONTHS=12
```

---

## 🎯 Resumen

✅ **Implementado:**
- ProductionLogger con métodos de coladas
- Integración en ServicioEtiquetaBase
- Logging automático durante fabricación
- Formato CSV detallado
- Documentación completa

📋 **Pendiente:**
- UI para visualizar logs
- Parser de CSV
- Dashboard de trazabilidad
- Tests específicos de logging

---

## 📞 Soporte

Para dudas sobre el sistema de logging:
1. Revisar este documento
2. Revisar `app/Services/ProductionLogger.php`
3. Revisar ejemplos en tests

---

**Powered by FERRALLIN 🤖**
**"Trazabilidad completa, transparencia total"** ✨
