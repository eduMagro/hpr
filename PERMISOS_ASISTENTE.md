# 🔐 Sistema de Permisos del Asistente Virtual

## Fecha: 12 Noviembre 2025

---

## 🎯 Funcionalidad Implementada

Se ha agregado un **sistema completo de permisos** que permite controlar qué usuarios pueden:
1. **Usar el asistente virtual**
2. **Modificar la base de datos** (INSERT, UPDATE, DELETE, CREATE TABLE)

---

## 📊 Permisos Disponibles

### 1. **Puede usar Asistente** (`puede_usar_asistente`)
- ✅ Activado por defecto para todos los usuarios
- Permite acceder al asistente virtual
- Permite ejecutar consultas **SELECT** (solo lectura)
- **NO** permite modificar datos

### 2. **Puede modificar BD** (`puede_modificar_bd`)
- ❌ Desactivado por defecto
- ⚠️ **MUY PODEROSO** - úsalo con precaución
- Permite ejecutar:
  - `INSERT` - Insertar nuevos registros
  - `UPDATE` - Actualizar registros existentes
  - `DELETE` - Eliminar registros
  - `CREATE TABLE` - Crear nuevas tablas
- **NO** permite operaciones peligrosas (DROP, TRUNCATE, ALTER DATABASE)

---

## 🛡️ Seguridad

### Operaciones Siempre Bloqueadas:
```sql
❌ DROP DATABASE / DROP TABLE
❌ TRUNCATE
❌ ALTER DATABASE
❌ GRANT / REVOKE (permisos)
❌ EXEC / EXECUTE
❌ INTO OUTFILE / LOAD_FILE (archivos)
```

### Operaciones Permitidas (con permiso):
```sql
✅ SELECT (todos)
✅ INSERT (solo usuarios autorizados)
✅ UPDATE (solo usuarios autorizados)
✅ DELETE (solo usuarios autorizados)
✅ CREATE TABLE (solo usuarios autorizados)
```

### Auditoría Completa:
- Todas las consultas se registran en `chat_consultas_sql`
- Incluye: usuario, consulta SQL, pregunta original, resultados, fecha
- Permite rastrear quién hizo qué y cuándo

---

## 🎨 Interfaz de Administración

### Acceso:
**Solo administradores** pueden gestionar permisos.

**URL:** `/asistente/permisos`

### Características:
- 📋 Lista todos los usuarios del sistema
- 🔄 Actualización en tiempo real (sin recargar página)
- ✅ Checkboxes para activar/desactivar permisos
- 💾 Guardado automático al hacer clic
- 🔔 Notificaciones de éxito/error

---

## 🔧 Cambios Técnicos Implementados

### 1. Base de Datos

**Migración:** `2025_11_12_182539_add_asistente_permissions_to_users_table.php`

**Campos agregados a `users`:**
```php
$table->boolean('puede_usar_asistente')->default(true);
$table->boolean('puede_modificar_bd')->default(false);
```

### 2. Modelo User

**Actualizado:** `app/Models/User.php`

**Agregado a `$fillable`:**
```php
'puede_usar_asistente',
'puede_modificar_bd',
```

**Agregado a `$casts`:**
```php
'puede_usar_asistente' => 'boolean',
'puede_modificar_bd' => 'boolean',
```

### 3. Servicio del Asistente

**Actualizado:** `app/Services/AsistenteVirtualService.php`

**Cambios principales:**

#### a) Validación según permisos:
```php
private function esConsultaSegura(string $sql, $user): bool
{
    // Valida según permisos del usuario
    if ($user->puede_modificar_bd) {
        // Permite INSERT, UPDATE, DELETE, CREATE
    } else {
        // Solo permite SELECT
    }
}
```

#### b) Ejecución de operaciones de modificación:
```php
// Detecta tipo de operación
if ($esSelect) {
    $resultados = DB::select($sql);
} else {
    $filasAfectadas = DB::statement($sql);
}
```

#### c) Prompt adaptado al usuario:
```php
$permisosTexto = $puedeModificar
    ? "Este usuario PUEDE ejecutar INSERT, UPDATE, DELETE, CREATE TABLE."
    : "Este usuario SOLO puede ejecutar consultas SELECT de lectura.";
```

### 4. Controlador

**Actualizado:** `app/Http/Controllers/AsistenteVirtualController.php`

**Métodos agregados:**

```php
// Muestra vista de administración
public function administrarPermisos()

// Actualiza permisos de un usuario
public function actualizarPermisos(Request $request, int $userId)
```

### 5. Vista de Permisos

**Creada:** `resources/views/asistente/permisos.blade.php`

- Tabla de usuarios con checkboxes
- JavaScript para actualización AJAX
- Leyenda explicativa
- Advertencia de seguridad

### 6. Rutas

**Agregadas en:** `routes/web.php`

```php
// Vista de administración
Route::get('/asistente/permisos', [AsistenteVirtualController::class, 'administrarPermisos'])

// API para actualizar permisos
Route::post('/api/asistente/permisos/{userId}', [AsistenteVirtualController::class, 'actualizarPermisos'])
```

---

## 📖 Guía de Uso

### Para Administradores:

#### 1. Acceder a la Gestión de Permisos:
```
1. Iniciar sesión como administrador
2. Ir a: http://localhost/manager/asistente/permisos
3. Verás la lista de todos los usuarios
```

#### 2. Otorgar Permiso para Usar el Asistente:
```
✅ Activar checkbox "Puede usar Asistente"
- El usuario podrá hacer consultas SELECT
- No podrá modificar datos
```

#### 3. Otorgar Permiso para Modificar BD:
```
⚠️ PRECAUCIÓN: Solo para usuarios de confianza

✅ Activar checkbox "Puede modificar BD"
- El usuario podrá hacer INSERT, UPDATE, DELETE
- Asegúrate de que comprenda SQL
- Todas sus acciones quedan registradas
```

### Para Usuarios con Permisos de Modificación:

#### Ejemplos de Operaciones Permitidas:

**Insertar un nuevo registro:**
```
Tú: "Inserta un nuevo cliente con nombre 'Construcciones ABC' y email 'info@abc.com'"

Asistente:
✅ Registro insertado correctamente. Se ha añadido la información a la base de datos.
```

**Actualizar un registro:**
```
Tú: "Actualiza el estado del pedido 123 a 'completado'"

Asistente:
✅ Actualización completada. Se ha modificado 1 registro(s).
```

**Eliminar registros:**
```
Tú: "Elimina las alertas más antiguas de hace 6 meses"

Asistente:
✅ Eliminación completada. Se han eliminado 15 registro(s).
```

**Crear una tabla:**
```
Tú: "Crea una tabla llamada temp_exports con columnas id, nombre y fecha"

Asistente:
✅ Tabla creada correctamente. La estructura se ha creado en la base de datos.
```

### Para Usuarios Sin Permisos:

**Solo consultas de lectura:**
```
✅ "Lista los pedidos pendientes"
✅ "Muéstrame los usuarios activos"
✅ "¿Cuántas salidas hay hoy?"
❌ "Actualiza el estado del pedido 123"
❌ "Elimina el cliente ABC"
```

---

## 🔍 Auditoría y Monitoreo

### Ver Operaciones de Modificación:

```sql
SELECT
    u.name AS usuario,
    ccs.consulta_natural AS pregunta,
    ccs.consulta_sql AS sql_ejecutado,
    ccs.filas_afectadas,
    ccs.created_at AS fecha
FROM chat_consultas_sql ccs
JOIN users u ON ccs.user_id = u.id
WHERE ccs.consulta_sql NOT LIKE 'SELECT%'
ORDER BY ccs.created_at DESC;
```

### Ver Usuarios con Permisos de Modificación:

```sql
SELECT
    name,
    email,
    rol,
    puede_usar_asistente,
    puede_modificar_bd
FROM users
WHERE puede_modificar_bd = 1;
```

### Estadísticas de Uso:

```sql
SELECT
    u.name AS usuario,
    COUNT(*) AS total_consultas,
    SUM(CASE WHEN ccs.consulta_sql LIKE 'SELECT%' THEN 1 ELSE 0 END) AS consultas_lectura,
    SUM(CASE WHEN ccs.consulta_sql NOT LIKE 'SELECT%' THEN 1 ELSE 0 END) AS modificaciones
FROM chat_consultas_sql ccs
JOIN users u ON ccs.user_id = u.id
GROUP BY u.id, u.name
ORDER BY total_consultas DESC;
```

---

## ⚠️ Advertencias de Seguridad

### 🔴 IMPORTANTE:

1. **Solo otorga permisos de modificación a usuarios de confianza**
   - Pueden modificar o eliminar datos críticos
   - Aunque hay validaciones, un usuario malicioso podría causar daños

2. **Los usuarios deben comprender SQL básico**
   - Un UPDATE sin WHERE modifica TODA la tabla
   - Un DELETE sin WHERE elimina TODOS los registros

3. **Revisa regularmente la auditoría**
   - Verifica qué operaciones se están ejecutando
   - Detecta patrones inusuales

4. **Haz backups regulares**
   - Antes de otorgar permisos nuevos
   - Mantén backups diarios de la BD

5. **Considera revocar permisos después de tareas específicas**
   - Si alguien necesita hacer una importación puntual
   - Otorga el permiso temporalmente

---

## 🧪 Pruebas Recomendadas

### Prueba 1: Usuario Sin Permisos

1. Iniciar sesión como usuario normal
2. Ir al asistente
3. Intentar: "Actualiza el nombre del cliente 1 a 'Test'"
4. **Esperado:** Error indicando que solo se permiten consultas SELECT

### Prueba 2: Usuario Con Permisos

1. Otorgar permiso `puede_modificar_bd` a un usuario
2. Iniciar sesión con ese usuario
3. Intentar: "Inserta un registro de prueba en la tabla productos_base"
4. **Esperado:** Mensaje de éxito

### Prueba 3: Operaciones Peligrosas Bloqueadas

1. Con usuario autorizado
2. Intentar: "Elimina la tabla users"
3. **Esperado:** Error indicando que la operación no está permitida

### Prueba 4: Auditoría

1. Ejecutar varias operaciones
2. Consultar tabla `chat_consultas_sql`
3. **Esperado:** Ver todas las operaciones registradas

---

## 📝 Configuración Predeterminada

**Al crear la migración:**
- Todos los usuarios existentes: `puede_usar_asistente = true`
- Todos los usuarios existentes: `puede_modificar_bd = false`

**Para usuarios nuevos:**
- El formulario de creación debe establecer estos valores
- Recomendado: `puede_usar_asistente = true`, `puede_modificar_bd = false`

---

## 🔄 Migración Realizada

```bash
php artisan migrate
```

**Resultado:**
```
✅ 2025_11_12_182539_add_asistente_permissions_to_users_table
```

**Rollback (si es necesario):**
```bash
php artisan migrate:rollback --step=1
```

---

## 📞 Soporte

### Si hay problemas:

1. **Verificar permisos en BD:**
```sql
SELECT id, name, puede_usar_asistente, puede_modificar_bd
FROM users
WHERE id = TU_USER_ID;
```

2. **Verificar logs:**
```bash
tail -f storage/logs/laravel.log | grep "SQL"
```

3. **Limpiar caché:**
```bash
php artisan cache:clear
php artisan config:clear
```

---

## ✅ Estado del Sistema

**Sistema de permisos:** ✅ Completamente funcional
**Interfaz de administración:** ✅ Operativa
**Auditoría:** ✅ Registrando todas las operaciones
**Seguridad:** ✅ Validaciones implementadas
**Documentación:** ✅ Completa

---

## 🚀 Próximos Pasos (Opcional)

### Mejoras Futuras Sugeridas:

1. **Permisos Granulares:**
   - Permitir modificar solo ciertas tablas
   - Diferentes niveles de permisos

2. **Aprobación de Modificaciones:**
   - Operaciones críticas requieren aprobación de admin
   - Sistema de workflow

3. **Límites de Operaciones:**
   - Máximo de registros modificables por consulta
   - Rate limiting

4. **Notificaciones:**
   - Email al admin cuando se ejecutan operaciones críticas
   - Alertas en tiempo real

5. **Dashboard de Auditoría:**
   - Visualización gráfica de operaciones
   - Análisis de patrones

---

## 📋 Checklist de Implementación

- ✅ Migración creada y ejecutada
- ✅ Modelo User actualizado
- ✅ Servicio actualizado con validaciones
- ✅ Controlador con métodos de gestión
- ✅ Vista de administración creada
- ✅ Rutas configuradas
- ✅ Sistema de auditoría funcionando
- ✅ Documentación completa
- ✅ Pruebas básicas realizadas

---

**¡Sistema de permisos listo para usar!** 🎉🔐
