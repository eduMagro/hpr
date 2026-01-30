# Plan de Implementación: Sistema de Solicitudes de Compra y Verificación QR

Este documento detalla los pasos para implementar el flujo de solicitudes de compra, aprobación y verificación mediante QR para la integración con la aplicación Big Mat.

## 1. Análisis de Requisitos

- **Actores**:
    - **Solicitante**: Usuario de HPR que necesita comprar material.
    - **Encargado (José Manuel)**: Revisa y aprueba la solicitud.
    - **Recepción Big Mat**: Escanea el QR para validar la compra al entregar el material.
- **Flujo**:
    1.  Creación de solicitud.
    2.  Envío a encargado.
    3.  Aprobación -> Generación de Token/QR.
    4.  Escaneo en Big Mat -> Petición a HPR -> Validación y obtención de datos.

## 2. Base de Datos

Se creará una nueva migración y modelo para `SolicitudCompra`.

### Tabla: `solicitudes_compra`

| Columna            | Tipo            | Descripción                                 |
| :----------------- | :-------------- | :------------------------------------------ |
| `id`               | BigInt (PK)     | Identificador único.                        |
| `user_id`          | Foreign Key     | Usuario que crea la solicitud.              |
| `descripcion`      | Text            | Detalles de lo que se va a comprar (lista). |
| `estado`           | String          | `pendiente`, `aprobada`, `rechazada`.       |
| `encargado_id`     | Foreign Key     | Usuario encargado de aprobar (José Manuel). |
| `fecha_aprobacion` | DateTime        | Cuándo se aprobó.                           |
| `token_qr`         | String (Unique) | Token único (UUID) para la URL del QR.      |
| `timestamps`       | DateTime        | `created_at`, `updated_at`.                 |

## 3. Backend (Laravel)

### Modelo

- **Nombre**: `SolicitudCompra`
- **Relaciones**: `creador()` (User), `encargado()` (User).

### Controlador: `SolicitudCompraController`

- `index()`: Listar mis solicitudes y (si soy encargado) las pendientes de aprobar.
- `store(Request $request)`: Crear nueva solicitud.
- `aprobar($id)`:
    - Validar permisos (solo encargado).
    - Generar `token_qr` único (UUID o similar).
    - Cambiar estado a `aprobada`.
    - Guardar `fecha_aprobacion`.
- `rechazar($id)`: Cambiar estado a `rechazada`.

### API de Verificación (Pública/Intercambio)

- **Ruta**: `GET /laapi/{token}` (Según especificación).
- **Lógica**:
    - Buscar `SolicitudCompra` por `token_qr`.
    - Verificar si existe y si está `aprobada`.
    - **Respuesta JSON**:
        ```json
        {
            "valido": true,
            "solicitud": {
                "id": 123,
                "comprador": "Nombre Apellido",
                "lista_compra": "Martillo, Clavos...",
                "fecha_aprobacion": "2024-01-30 10:00:00"
            }
        }
        ```

## 4. Frontend (HPR Web)

### Vistas

- **Panel Principal (`/solicitudes-compra`)**:
    - Botón "Nueva Solicitud".
    - Tabla de solicitudes (Estado, Fecha, Acciones).
    - Pestaña "Por Aprobar" (Visible solo para encargados).
- **Modal Creación**:
    - Input para lista de compra/descripción.
    - Selector de "Encargado" (si aplica, o automático).
- **Visualización de QR**:
    - Al estar aprobada, botón "Ver QR".
    - Muestra el código QR generado apuntando a `https://app.hierrospacoreyes.es/laapi/{token}`.

## 5. Pasos de Ejecución

1.  [ ] Crear Migración `create_solicitudes_compra_table`.
2.  [ ] Crear Modelo `SolicitudCompra`.
3.  [ ] Crear Controlador `SolicitudCompraController`.
4.  [ ] Definir rutas en `routes/web.php` (Gestión) y ruta de API.
5.  [ ] Implementar vistas Blade (Listado, Creación).
6.  [ ] Implementar lógica de aprobación y generación de QR (usando librería de QR o API externa si no hay librería instalada).
7.  [ ] Implementar Endpoint JSON para Big Mat.
