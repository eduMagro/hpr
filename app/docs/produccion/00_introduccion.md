# 🏭 Módulo de Producción

El módulo de **producción** gestiona el flujo desde la creación de una **planilla de trabajo** hasta la **fabricación y finalización** de los elementos que la componen.

---

## 🎯 Objetivo

Centralizar la información de:

-   Qué hay que fabricar.
-   En qué máquina se fabrica.
-   Con qué materia prima.
-   Quién lo fabrica (operarios asignados).
-   En qué estado está cada parte del proceso.

---

## 🧩 Entidades principales

-   **Planilla**: documento de producción asociado a una obra/cliente.
-   **Etiqueta**: subgrupo dentro de una planilla, vinculado a fases o máquinas (padre/subetiquetas mediante `etiqueta_sub_id`).
-   **Elemento**: pieza individual con dimensiones, peso y estado.
-   **Paquete**: agrupación de etiquetas ya fabricadas.
-   **OrdenPlanilla**: cola de trabajo en cada máquina.
-   **Máquina**: recurso de producción (cortadora, dobladora, ensambladora, etc.).

---

## 🔄 Flujo general

1. **Planificación** → se crean planillas con sus elementos.
2. **Asignación** → cada elemento se vincula a una máquina (puede haber secundaria/terciaria).
3. **Fabricación** → los operarios ponen en marcha la producción (estado `fabricando`).
4. **Consumo de materia prima** → se descuentan productos de stock y se generan recargas.
5. **Finalización** → los elementos pasan a estados `fabricada`/`ensamblada`/`soldada`/`completada`.
6. **Paquetización** → los elementos terminados se agrupan en paquetes listos para logística.

---

## 🚦 Estados clave (resumen)

-   `pendiente` → no ha comenzado.
-   `fabricando` → en curso.
-   `fabricada` → fin de primera fase (corte/doblado).
-   `ensamblando` · `soldando` · `doblando` → fases posteriores.
-   `completada` → etiqueta finalizada.
