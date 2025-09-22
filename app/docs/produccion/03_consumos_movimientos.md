# ⚙️ Consumos y movimientos en producción

Este documento explica cómo se gestionan los consumos de materia prima y los movimientos generados durante la fabricación.

---

## 🎯 Propósito

-   Asegurar un consumo correcto y trazable de materia prima.
-   Generar automáticamente los movimientos necesarios para reponer stock y mover etiquetas entre máquinas.
-   Informar al operario con errores o avisos claros.

---

## 🔑 Datos y conceptos

-   **Elemento**: unidad fabricada con atributos físicos.
-   **Producto**: materia prima disponible en una máquina con stock en kilogramos (`productos`).
-   **ProductoBase**: define propiedades base como `diametro` y `tipo` (barra/encarretado) para asociar productos a consumos.
-   **Máquina**: ubicación de consumo o destino de movimientos (`maquinas`).

### Campos clave de consumo

| Campo        | Descripción                                      | Ejemplo |
| ------------ | ------------------------------------------------ | ------- |
| `diametro`   | Diámetro requerido por el elemento.              | 12      |
| `peso`       | Peso del elemento (usado para calcular consumo). | 3.40    |
| `longitud`   | Longitud del elemento.                           | 6000    |
| `peso_stock` | Kilogramos disponibles del producto en máquina.  | 120.50  |

---

## ⚖️ Consumo de materia prima

Durante la fabricación:

1. Se identifican los **diámetros necesarios** para los elementos en la máquina.
2. Se buscan productos en la máquina con el mismo diámetro (y longitud si aplica) a través de su `producto_base`.
3. Se descuentan kilogramos de stock (`peso_stock`) de los productos seleccionados, priorizando los de menor stock.
4. Si un producto queda a `0` → se marca como `consumido`, se libera de `maquina_id` y `ubicacion_id`.

### Reglas de consumo

-   Si no existe ningún producto del diámetro → se genera un **movimiento de recarga** y se detiene el proceso.
-   Si existe stock pero insuficiente → se lanza un **aviso** y se genera **recarga**; el proceso puede continuar según la lógica de fabricación.
-   En máquinas de tipo `ensambladora` solo se permite consumir diámetro `5` (según reglas del servicio de completado de lote).

---

## 📦 Asignación de consumos a elementos

-   Los consumos se reparten de forma proporcional y ordenada entre los productos disponibles.
-   Cada elemento puede registrar hasta **3 productos consumidos**. Esto permite trazabilidad del origen de la materia prima usada.

### Trazabilidad de productos consumidos (por elemento)

| Campo           | Descripción                            |
| --------------- | -------------------------------------- |
| `producto_id`   | Producto principal consumido.          |
| `producto_id_2` | Segundo producto consumido (opcional). |
| `producto_id_3` | Tercer producto consumido (opcional).  |

---

## 🚚 Movimientos automáticos

Existen dos tipos principales:

### 1) Recarga de materia prima

-   **Tipo**: `Recarga materia prima`.
-   Se crea cuando falta stock en una máquina.

**Atributos del movimiento (modelo `movimientos`)**

| Atributo           | Descripción                                              |
| ------------------ | -------------------------------------------------------- |
| `producto_base_id` | Producto base requerido para recargar (diámetro + tipo). |
| `maquina_destino`  | Máquina que necesita la materia prima.                   |
| `estado`           | Estado del movimiento, p. ej. `pendiente`.               |
| `descripcion`      | Detalle con diámetro, tipo y longitud requerida.         |
| `prioridad`        | Nivel de prioridad (si aplica).                          |
| `fecha_solicitud`  | Marca temporal de solicitud.                             |
| `solicitado_por`   | Usuario que origina la solicitud.                        |

-   **Evita duplicados**: si ya existe un movimiento `pendiente` para ese diámetro + máquina, no se crea otro.

### 2) Movimiento de paquete/etiqueta

-   **Tipo**: `Movimiento paquete`.
-   Se crea cuando una etiqueta debe moverse de una máquina a otra (ej. cortadora → dobladora manual).

**Atributos del movimiento**

| Atributo          | Descripción                                  |
| ----------------- | -------------------------------------------- |
| `maquina_origen`  | Máquina actual de la etiqueta.               |
| `maquina_destino` | Máquina a la que debe moverse la etiqueta.   |
| `paquete_id`      | Paquete vinculado, si aplica.                |
| `estado`          | Estado del movimiento, p. ej. `pendiente`.   |
| `descripcion`     | Texto indicando planilla/etiqueta y detalle. |

-   **Evita duplicados**: no se repite por combinación de `maquina_origen` + `maquina_destino` + etiqueta/paquete.

---

## 🔔 Mensajes al operario

| Tipo    | Continuidad del proceso | Cuándo se emite                                 | Registro                 |
| ------- | ----------------------- | ----------------------------------------------- | ------------------------ |
| Error   | Se detiene              | No existe materia prima del diámetro requerido. | Se muestra y se registra |
| Warning | Continúa                | Stock insuficiente; se requiere recarga.        | Se muestra y se registra |

---

## 🧪 Ejemplos prácticos

### Ejemplo 1: Consumo proporcional

-   Elemento A requiere diámetro 12 y consume 10 kg.
-   En máquina hay:
    -   Producto P1 (`peso_stock = 6 kg`, diámetro 12)
    -   Producto P2 (`peso_stock = 8 kg`, diámetro 12)
-   Consumo:
    1. Se descuenta primero de P1 hasta agotar: P1 → `0 kg` (se marca consumido y se libera).
    2. Se descuenta el resto de P2: P2 → `4 kg` restantes.
-   Trazabilidad en el elemento:
    -   `producto_id = P1`, `producto_id_2 = P2`.

### Ejemplo 2: Recarga por falta total de stock

-   Elemento B requiere diámetro 16.
-   No hay productos del diámetro 16 en la máquina.
-   Resultado:
    -   Se crea movimiento tipo `Recarga materia prima` con `estado = pendiente`.
    -   El proceso se detiene con mensaje de error.

### Ejemplo 3: Movimiento de paquete/etiqueta

-   Una etiqueta debe pasar de cortadora a dobladora manual.
-   Resultado:
    -   Se crea movimiento tipo `Movimiento paquete` con `maquina_origen = cortadora`, `maquina_destino = dobladora_manual`, `estado = pendiente`.
    -   Si ya existe uno igual `pendiente`, no se duplica.
