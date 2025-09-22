# 📖 Accesos y Permisos

Este documento describe cómo funciona el sistema de **acceso a rutas y permisos**.

---

## 🔑 Roles principales

| Rol                     | Alcance por defecto                                 |
| ----------------------- | --------------------------------------------------- |
| `admin`/`administrador` | Acceso total.                                       |
| `operario`              | Acceso por prefijos configurados.                   |
| `transportista`         | Acceso por prefijos configurados.                   |
| `oficina`               | Acceso por secciones habilitadas a su departamento. |
| Otros                   | Acceso denegado por defecto.                        |

---

## ⚙️ Configuración centralizada (`config/acceso.php`)

La configuración de accesos está centralizada en el archivo `config/acceso.php`.

```php
return [
    // 📌 Prefijos permitidos para OPERARIOS
    'prefijos_operario' => [
        'produccion.trabajadores',
        'users.',
        'alertas.',
        'productos.',
        'pedidos.',
        'ayuda.',
        'maquinas.',
        'entradas.',
    ],

    // 📌 Prefijos permitidos para TRANSPORTISTAS
    'prefijos_transportista' => [
        'users.',
        'alertas.',
        'vacaciones.solicitar',
        'planificacion.index',
        'usuarios.editarSubirImagen',
        'usuarios.imagen',
        'nominas.crearDescargarMes',
    ],

    // 📌 Rutas libres (disponibles para todas las empresas)
    'rutas_libres' => [
        'politica.privacidad',
        'politica.cookies',
        'politicas.aceptar',
        'ayuda.index',
        'usuarios.show',
        'usuarios.index',
        'nominas.crearDescargarMes',
        'turno.cambiarMaquina',
        'salida.completarDesdeMovimiento',
        'alertas.index',
        'alertas.store',
        'alertas.update',
        'alertas.destroy',
        'alertas.verMarcarLeidas',
        'alertas.verSinLeer',
    ],

    // 📌 Correos con acceso total
    'correos_acceso_total' => [
        'eduardo.magro@pacoreyes.com',
        'sebastian.duran@pacoreyes.com',
        'juanjose.dorado@pacoreyes.com',
        'josemanuel.amuedo@pacoreyes.com',
        'jose.amuedo@pacoreyes.com',
        'manuel.reyes@pacoreyes.com',
        'alvarofaces@gruporeyestejero.com',
        'pabloperez@gruporeyestejero.com',
    ],
];
```

---

## 🧠 Cómo se evalúa el acceso (helpers y middleware)

1. Se obtiene el usuario autenticado (`Auth::user()`).
2. Se identifica el **rol** (`admin`, `operario`, `transportista`, `oficina`).
3. Se evalúa según el rol:
    - Admin/Administrador → acceso total.
    - Operario/Transportista → se permite si el nombre de ruta coincide con alguno de los **prefijos configurados**.
    - Oficina → se obtiene el **prefijo base** de la ruta (antes del primer punto) y se valida si la **sección** está asociada a alguno de los **departamentos** del usuario.
    - Otros → denegado por defecto.

### Funciones relevantes (resumen)

-   `obtenerPrefijoBaseDeRuta($nombreRutaActual)` → devuelve el prefijo base en minúsculas, ej. `usuarios.`.
-   `usuarioTieneAcceso($nombreRutaActual)` → aplica toda la lógica anterior.

---

## 🧪 Ejemplos

-   Usuario `operario` accediendo a `maquinas.index` → permitido si `maquinas.` está en `prefijos_operario`.
-   Usuario `oficina` accediendo a `clientes.editar` → permitido si la sección `clientes.` está asociada a algún departamento del usuario.
-   Usuario con rol no reconocido → denegado.
