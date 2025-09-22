# 🧩 Modelos principales de Producción

Este módulo se apoya en modelos Eloquent que representan las entidades de producción.

---

## 📑 Planilla

-   **Tabla**: `planillas`

### Campos clave

| Campo                    | Descripción                                     |
| ------------------------ | ----------------------------------------------- |
| `codigo`                 | Identificador humano de la planilla.            |
| `users_id`               | Usuario creador/responsable.                    |
| `cliente_id`             | Cliente asociado.                               |
| `obra_id`                | Obra/Nave asociada.                             |
| `seccion`                | Sección de trabajo.                             |
| `descripcion`            | Descripción general.                            |
| `ensamblado`             | Texto guía para ensamblado (reglas especiales). |
| `comentario`             | Comentarios adicionales.                        |
| `peso_total`             | Peso total calculado (kg).                      |
| `estado`                 | `pendiente` · `fabricando` · `completada`.      |
| `fecha_inicio`           | Fecha/hora de inicio.                           |
| `fecha_finalizacion`     | Fecha/hora de fin.                              |
| `tiempo_fabricacion`     | Duración total (segundos).                      |
| `fecha_estimada_entrega` | Fecha estimada de entrega.                      |
| `revisada`               | Indicador de revisión.                          |
| `revisada_por_id`        | Usuario revisor.                                |
| `revisada_at`            | Fecha/hora de revisión.                         |

### Relaciones

-   `etiquetas()` → 1:N con `etiquetas`.
-   `elementos()` → 1:N con `elementos`.
-   `ordenProduccion()` → 1:1 con `orden_planillas`.
-   `obra()`, `cliente()`, `user()`, `revisor()`.

### Atributos derivados

-   `codigo_limpio` (formatea el sufijo numérico sin ceros).
-   `peso_total_kg` (muestra con formato).

---

## 🏷️ Etiqueta

-   **Tabla**: `etiquetas`

### Campos clave

| Campo                                                      | Descripción                                                                                                                                   |
| ---------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| `codigo`                                                   | Código de etiqueta (p. ej. `ETQ2506005`).                                                                                                     |
| `etiqueta_sub_id`                                          | Identificador jerárquico de subetiqueta (p. ej. `ETQ2506005.01`).                                                                             |
| `planilla_id`                                              | Planilla asociada.                                                                                                                            |
| `producto_id`, `producto_id_2`                             | Productos vinculados (si aplica).                                                                                                             |
| `ubicacion_id`                                             | Ubicación física.                                                                                                                             |
| `operario1_id`, `operario2_id`                             | Operarios principales.                                                                                                                        |
| `soldador1_id`, `soldador2_id`                             | Operarios de soldadura.                                                                                                                       |
| `ensamblador1_id`, `ensamblador2_id`                       | Operarios de ensamblado.                                                                                                                      |
| `nombre`, `marca`                                          | Descriptores.                                                                                                                                 |
| `paquete_id`                                               | Paquete asociado (si aplica).                                                                                                                 |
| `numero_etiqueta`                                          | Número correlativo.                                                                                                                           |
| `peso`                                                     | Peso total (kg).                                                                                                                              |
| `fecha_inicio`, `fecha_finalizacion`                       | Inicio y fin de fabricación.                                                                                                                  |
| `fecha_inicio_ensamblado`, `fecha_finalizacion_ensamblado` | Fechas de ensamblado.                                                                                                                         |
| `fecha_inicio_soldadura`, `fecha_finalizacion_soldadura`   | Fechas de soldadura.                                                                                                                          |
| `estado`                                                   | `pendiente` · `fabricando` · `fabricada` · `ensamblando` · `ensamblada` · `soldando` · `doblando` · `parcialmente_completada` · `completada`. |

### Relaciones

-   `planilla()`
-   `elementos()`
-   `paquete()`
-   `producto()` / `producto2()`
-   Operarios: `operario1()`, `operario2()`, `soldador1()`, `soldador2()`, `ensamblador1()`, `ensamblador2()`

### Atributos derivados

-   `peso_kg` (muestra con formato).

---

## 🔩 Elemento

-   **Tabla**: `elementos`

### Campos clave

| Campo                                            | Descripción                                              |
| ------------------------------------------------ | -------------------------------------------------------- |
| `codigo`                                         | Identificador del elemento (p. ej. `EL25061`).           |
| `planilla_id`, `etiqueta_id`, `etiqueta_sub_id`  | Relaciones de origen.                                    |
| `maquina_id`, `maquina_id_2`, `maquina_id_3`     | Máquinas principal/secundaria/terciaria.                 |
| `producto_id`, `producto_id_2`, `producto_id_3`  | Productos consumidos.                                    |
| `paquete_id`                                     | Paquete (si aplica).                                     |
| `figura`, `fila`, `marca`, `etiqueta`            | Descriptores de dibujo/planilla.                         |
| `diametro`, `longitud`, `barras`, `dobles_barra` | Propiedades geométricas.                                 |
| `peso`, `dimensiones`                            | Propiedades físicas.                                     |
| `tiempo_fabricacion`                             | Duración en segundos.                                    |
| `estado`                                         | `pendiente` · `fabricando` · `fabricado` · `completado`. |

### Relaciones

-   `planilla()`, `etiquetaRelacion()`
-   `maquina()`, `maquina_2()`, `maquina_3()`
-   `producto()`, `producto2()`, `producto3()`
-   `ubicacion()`

### Atributos derivados

-   `longitud_cm`, `longitud_m`, `peso_kg`, `diametro_mm`.

---

## 📦 Paquete

-   **Tabla**: `paquetes`

### Campos clave

| Campo        | Descripción                |
| ------------ | -------------------------- |
| `codigo`     | Identificador del paquete. |
| `peso_total` | Peso total (kg).           |
| `estado`     | Estado logístico.          |

### Relaciones

-   Contiene varias `etiquetas`.

---

## 📋 OrdenPlanilla

-   **Tabla**: `orden_planillas`

### Campos clave

| Campo         | Descripción                             |
| ------------- | --------------------------------------- |
| `maquina_id`  | Máquina a la que se encola la planilla. |
| `planilla_id` | Planilla en cola.                       |
| `posicion`    | Posición en la cola.                    |

### Función

-   Representa la cola de producción por máquina. Al completar, se reordenan posiciones.

---

## 🛠️ Máquina

-   **Tabla**: `maquinas`

### Campos clave

| Campo                          | Descripción                                                |
| ------------------------------ | ---------------------------------------------------------- |
| `codigo`                       | Identificador de máquina.                                  |
| `nombre`                       | Nombre descriptivo.                                        |
| `estado`                       | Estado operativo.                                          |
| `tipo`                         | Tipo funcional (ej. `cortadora_dobladora`, `estribadora`). |
| `obra_id`                      | Obra/Nave a la que pertenece.                              |
| `diametro_min`, `diametro_max` | Rango admisible de diámetros.                              |
| `peso_min`, `peso_max`         | Rango admisible de pesos.                                  |
| `ancho_m`, `largo_m`           | Dimensiones físicas (metros).                              |

### Relaciones

-   `productos()` (stock por máquina)
-   `elementos()` / `elementosSecundarios()` / `elementosTerciarios()`
-   `usuarios()` (por especialidad)
-   `obra()`

### Atributos derivados

-   `celdas` (cálculo según `config('almacen.tamano_celda_m')`).
