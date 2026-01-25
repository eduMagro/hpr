# ⚙️ Producción – Planillas, Etiquetas, Elementos y Máquinas

Este módulo controla todo el flujo de **fabricación**: desde la importación de planillas hasta la finalización de los elementos en máquinas.

---

## 📦 Entidades principales

### Planilla

-   Representa un pedido de fabricación completo.

Campos clave:

| Campo                                                          | Descripción                                |
| -------------------------------------------------------------- | ------------------------------------------ |
| `codigo`, `obra_id`, `cliente_id`                              | Identificadores y referencias.             |
| `estado`                                                       | `pendiente` · `fabricando` · `completada`. |
| `fecha_inicio`, `fecha_finalizacion`, `fecha_estimada_entrega` | Hitos temporales.                          |

Relaciones:

-   `etiquetas` (1:N), `elementos` (1:N), `ordenProduccion` (1:1)

---

### Etiqueta

-   Subconjunto de una planilla, identifica un lote de elementos.

Campos clave y estados:

| Campo / Estado                               | Descripción                                                                                                                           |
| -------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| `codigo`, `etiqueta_sub_id`                  | Identificación (padre/subetiqueta).                                                                                                   |
| `estado`                                     | `pendiente`, `fabricando`, `fabricada`, `ensamblando`, `ensamblada`, `soldando`, `doblando`, `parcialmente_completada`, `completada`. |
| `peso`, `fecha_inicio`, `fecha_finalizacion` | Seguimiento.                                                                                                                          |

Relaciones: `planilla`, `elementos`, `paquete`, operarios y productos.

---

### Elemento

-   Pieza individual a fabricar.

Campos clave:

| Campo                                           | Descripción                                                  |
| ----------------------------------------------- | ------------------------------------------------------------ |
| `diametro`, `longitud`, `barras`, `peso`        | Propiedades físicas.                                         |
| `maquina_id`, `maquina_id_2`                    | Máquinas principal y secundaria.                             |
| `producto_id`, `producto_id_2`, `producto_id_3` | Consumos asignados.                                          |
| `estado`                                        | `pendiente`, `fabricando`, `fabricado`, `completado`.        |
| `estado2`                                       | Estado en máquina secundaria (si aplica): igual que `estado`.|

---

### Máquina

-   Recurso de producción (ej.: `cortadora_dobladora`, `estribadora`, `ensambladora`).
-   Relación con `productos` para stock de materia prima.

---

## 🔄 Flujo de estados (resumen)

1. `pendiente` → `fabricando` (inicio de elementos).
2. `fabricando` → `fabricada` (primera fase completa).
3. `fabricada` → `ensamblando`/`soldando`/`doblando` (si aplica) o `completada`.

---

## 📊 Consumos de materia prima

-   Cada elemento tiene un **peso necesario** según diámetro y longitud.
-   Se consumen productos de la máquina priorizando menor `peso_stock`.
-   Si falta stock → se crea movimiento `Recarga materia prima` (estado `pendiente`).
-   Asignación de consumos: hasta 3 productos por elemento (`producto_id`, `producto_id_2`, `producto_id_3`).

---

## 🚚 Movimientos generados

-   `Recarga materia prima` → por déficit de stock.
-   `Movimiento paquete` → traslado de subetiquetas entre máquinas; evita duplicados por origen/destino/etiqueta.

---

## ⚙️ Controladores/Servicios clave

-   `EtiquetaController`: cálculo de corte, fabricación en lote, actualización de estados.
-   `CompletarLoteService`: completa etiquetas, descuenta consumos, genera avisos y cierres de planilla.
