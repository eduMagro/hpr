# 🚦 Estados y flujos en Producción

Este documento define los estados posibles de **planillas**, **etiquetas** y **elementos**, junto con las reglas de transición.

---

## 📑 Estados de Planilla

| Estado       | Descripción                                              |
| ------------ | -------------------------------------------------------- |
| `pendiente`  | La planilla está creada pero no ha comenzado.            |
| `fabricando` | Al menos una de sus etiquetas ha entrado en fabricación. |
| `completada` | Todos sus elementos/etiquetas han finalizado.            |

### Reglas

-   Si una etiqueta de la planilla comienza → la planilla pasa a `fabricando`.
-   Una planilla solo pasa a `completada` cuando todas sus etiquetas estén `completada`/`fabricada` según flujo.

---

## 🏷️ Estados de Etiqueta

| Estado                    | Descripción                                              |
| ------------------------- | -------------------------------------------------------- |
| `pendiente`               | Aún no se ha fabricado ningún elemento.                  |
| `fabricando`              | Elementos en curso de fabricación.                       |
| `fabricada`               | Elementos terminados en la primera fase (corte/doblado). |
| `ensamblando`             | En curso en máquina ensambladora.                        |
| `ensamblada`              | Ensamblaje finalizado.                                   |
| `soldando`                | En proceso de soldadura.                                 |
| `doblando`                | En proceso de doblado manual.                            |
| `parcialmente_completada` | Finalizada en una máquina, pendiente en otras.           |
| `completada`              | Todos los elementos terminados.                          |

### Reglas

-   `pendiente → fabricando` al iniciar cualquier elemento.
-   `fabricando → fabricada` cuando todos los elementos han terminado en la primera máquina.
-   `fabricada → ensamblando / soldando / doblando` según máquina secundaria asignada o reglas de `ensamblado`.
-   `ensamblando → ensamblada` al terminar todos los elementos en ensambladora.
-   `ensamblada → soldando` si requiere soldadura.
-   `soldando / doblando → completada` al finalizar la fase.
-   `fabricada → completada` directo si no hay fases extra.
-   Puede marcarse `parcialmente_completada` si la etiqueta termina en una máquina pero tiene elementos en otras.

---

## 🔩 Estados de Elemento

| Estado       | Descripción                      |
| ------------ | -------------------------------- |
| `pendiente`  | Aún no fabricado.                |
| `fabricando` | En proceso.                      |
| `fabricado`  | Completado en su máquina.        |
| `completado` | Finalizado en fases adicionales. |

---

## 🔄 Transiciones clave (resumen)

1. **Inicio de fabricación** → cambia etiqueta y planilla a `fabricando`.
2. **Faltante de stock** → no impide empezar; se lanza aviso y movimiento de recarga.
3. **Cambio de máquina** → elementos pueden moverse a máquina secundaria/terciaria.
4. **Finalización** → cuando todos los elementos de una etiqueta están completos:
    - Si hay fases posteriores → cambia de estado (ej. `ensamblando`).
    - Si no hay → pasa directo a `completada`.

---

## 🧪 Ejemplos prácticos de flujo (orden real)

-   Etiqueta `ETQ2506005`:
    1. `pendiente` → `fabricando` (cortadora_dobladora)
    2. `fabricando` → `fabricada`
    3. `fabricada` → `ensamblando` (se asigna `ensambladora`)
    4. `ensamblando` → `ensamblada`
    5. `ensamblada` → `soldando` (si aplica)
    6. `soldando` → `completada`
