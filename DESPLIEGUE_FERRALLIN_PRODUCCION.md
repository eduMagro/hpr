# 🚀 Despliegue de FERRALLIN en Producción

## ❓ ¿Por qué no aparece en producción?

El Asistente Virtual FERRALLIN aparece en local pero **NO** en producción por la siguiente razón:

### El problema: Falta registro en la tabla `secciones`

El dashboard obtiene sus items dinámicamente desde la tabla **`secciones`** de la base de datos (ver `PageController.php:32-34`):

```php
$secciones = Seccion::with('departamentos')
    ->where('mostrar_en_dashboard', true)
    ->get();
```

**En LOCAL:**
- ✅ Se ejecutó el seeder `AsistenteVirtualSeeder` que insertó el registro
- ✅ La sección aparece en la tabla `secciones`
- ✅ El icono aparece en el dashboard

**En PRODUCCIÓN:**
- ❌ **No se ejecutó el seeder**
- ❌ **No existe el registro en la tabla `secciones`**
- ❌ Por tanto, no aparece en el dashboard

---

## 🔧 Solución: 3 pasos

### Paso 1: Ejecutar el script SQL en producción

1. Abre **phpMyAdmin** en el servidor de producción
2. Selecciona tu base de datos
3. Ve a la pestaña **SQL**
4. Copia todo el contenido del archivo: **`ferrallin_produccion_completo.sql`**
5. Pégalo en el editor SQL
6. Haz clic en **"Continuar"** o **"Ejecutar"**

El script realiza automáticamente:
- ✅ Crea las tablas de chat (`chat_conversaciones`, `chat_mensajes`, `chat_consultas_sql`)
- ✅ Añade columnas de permisos a la tabla `users`
- ✅ Crea índices de optimización
- ✅ **Inserta el registro en la tabla `secciones`** (esto hace que aparezca el icono)
- ✅ Registra las migraciones en Laravel

---

### Paso 2: Actualizar el código en producción

Conéctate al servidor de producción y ejecuta:

```bash
# Actualizar el código desde Git
cd /ruta/del/proyecto
git pull origin [rama-ferrallin]

# Limpiar cachés de Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

# Si usas Vite/Mix para assets
npm run build
```

---

### Paso 3: Verificar archivos necesarios

Asegúrate de que existen estos archivos en producción:

#### 1. **Icono del asistente:**
```
public/imagenes/iconos/asistente.png
```

Si falta, súbelo manualmente desde local.

#### 2. **Variable de entorno:**
Edita el archivo `.env` en producción y añade:

```env
# OpenAI API Configuration
OPENAI_API_KEY=tu_clave_api_de_openai_aqui
OPENAI_ORGANIZATION=null
OPENAI_REQUEST_TIMEOUT=30
```

---

## ✅ Verificación

Después de completar los 3 pasos:

1. **Accede al dashboard** con tu usuario
2. Deberías ver el icono **"Asistente Virtual"**
3. Haz clic en él para probar

### Si NO aparece aún:

Ejecuta esta consulta SQL para verificar:

```sql
-- Ver si la sección existe
SELECT * FROM secciones WHERE nombre = 'Asistente Virtual';

-- Debería devolver:
-- id | nombre              | ruta             | icono                          | mostrar_en_dashboard
-- XX | Asistente Virtual   | asistente.index  | imagenes/iconos/asistente.png | 1
```

---

## 🔐 Configuración de permisos (opcional)

Si tu sistema usa permisos por departamento, también necesitas vincular el departamento con la sección:

```sql
-- Obtener el ID de la sección de FERRALLIN
SELECT id FROM secciones WHERE nombre = 'Asistente Virtual';
-- Supongamos que devuelve: 25

-- Vincular con el departamento (ejemplo: departamento 3)
INSERT INTO departamento_seccion (departamento_id, seccion_id)
VALUES (3, 25);
```

Para dar permisos directos a usuarios específicos:

```sql
-- Dar permiso a un usuario específico
INSERT INTO permiso_accesos (user_id, seccion_id, created_at, updated_at)
VALUES (1, 25, NOW(), NOW());
```

---

## 📝 Archivos generados

Se han creado 2 archivos SQL:

1. **`ferrallin_migrations.sql`** - Solo las migraciones de base de datos
2. **`ferrallin_produccion_completo.sql`** - ⭐ **Usa este** - Script completo incluyendo la inserción en `secciones`

---

## 🎯 Resumen rápido

**El problema:** No se ejecutó el seeder en producción, por lo que falta el registro en la tabla `secciones`.

**La solución:**
1. Ejecutar `ferrallin_produccion_completo.sql` en phpMyAdmin
2. Hacer `git pull` y limpiar cachés
3. Verificar que existe el icono y la API key de OpenAI

**Tiempo estimado:** 5-10 minutos

---

## 🆘 Soporte

Si después de seguir estos pasos el asistente no aparece:

1. Verifica los logs de Laravel: `storage/logs/laravel.log`
2. Revisa la consola del navegador (F12) por errores JavaScript
3. Ejecuta las consultas SQL de verificación incluidas al final del script

---

## 📦 Archivos del proyecto FERRALLIN

Archivos añadidos en el commit `636150f`:

**Controladores:**
- `app/Http/Controllers/AsistenteVirtualController.php`

**Middleware:**
- `app/Http/Middleware/VerificarPermisoAsistente.php`

**Modelos:**
- `app/Models/ChatConversacion.php`
- `app/Models/ChatMensaje.php`
- `app/Models/ChatConsultaSql.php`

**Servicios:**
- `app/Services/AsistenteVirtualService.php`

**Vistas:**
- `resources/views/asistente/index.blade.php`
- `resources/views/asistente/permisos.blade.php`

**Migraciones:**
- `database/migrations/2025_11_12_155044_create_chat_tables.php`
- `database/migrations/2025_11_12_182539_add_asistente_permissions_to_users_table.php`
- `database/migrations/2025_11_12_195006_add_indexes_to_chat_tables.php`

**Seeders:**
- `database/seeders/AsistenteVirtualSeeder.php` ⚠️ **Este es el que falta ejecutar en producción**

**Configuración:**
- `config/openai.php`

**Rutas:**
- Añadidas en `routes/web.php` (líneas para el asistente)

**Assets:**
- `public/imagenes/iconos/asistente.png`

---

**¡Listo para desplegar! 🚀**
