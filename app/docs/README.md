# 📘 Documentación del Sistema ERP – Hierros Paco Reyes

Este proyecto es una aplicación de gestión integral empresarial desarrollada en **Laravel**.

---

## 🚀 Convenciones Generales

-   **Lenguaje**: Laravel 11 + PHP 8.x
-   **Base de datos**: MySQL
-   **Frontend**: Blade + Alpine.js + SweetAlert2 + Chart.js + FullCalendar Scheduler
-   **Variables y nombres**: siempre en **español** y con nombres **largos y descriptivos**.
-   **Control de versiones**: GitHub
-   **Entorno**: XAMPP / Servidor dedicado

---

## 🔐 Accesos y Roles

-   **Admin**: acceso total.
-   **Oficina**: acceso según secciones asignadas a su departamento (tablas pivote `departamentos ↔ secciones`).
-   **Operario**: acceso restringido a prefijos de rutas definidos en `config/acceso.php`.
-   **Transportista**: acceso restringido.

Convención de rutas:

-   `index`, `show` → vistas estándar.
-   `ver.*` → vistas adicionales de consulta.
-   `editar.*` → vistas de edición.
-   Prefijos (`usuarios.`, `maquinas.`, `etiquetas.`, etc.) determinan permisos.

---

## 📑 Índice de Documentación

-   [🔐 Acceso y Roles](acceso.md)
-   [🏭 Producción (introducción)](produccion/00_introduccion.md)
-   [🧩 Modelos de Producción](produccion/01_modelos.md)
-   [🚦 Estados y Flujos](produccion/02_estados_flujo.md)
-   [⚙️ Consumos y Movimientos](produccion/03_consumos_movimientos.md)
-   [⚙️ Resumen de Producción](produccion.md)

> Secciones adicionales (almacén, RRHH, estadísticas, servicios internos, base de datos) podrán añadirse en futuras iteraciones.

---

## 📝 Notas de Estilo

-   Validar inputs antes de persistir.
-   Usar **transacciones (`DB::transaction`)** en procesos críticos (fabricación, movimientos, nóminas).
-   Usar logs (`Log::info`, `Log::warning`, `Log::error`) para trazabilidad.
-   En frontend, usar SweetAlert2 para interacciones críticas y confirmaciones.
-   Paginación por defecto: **10 registros** (configurable).

---

## 📌 Próximos pasos

1. Completar documentación de almacén y logística.
2. Documentar recursos humanos (turnos, vacaciones, nóminas).
3. Añadir diagramas de flujo (mermaid o draw.io exportado) donde aporte claridad.

---

✍️ Autor: **Equipo de Transformación Digital – Hierros Paco Reyes**
