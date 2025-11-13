# 🔑 Configurar OpenAI en Producción

## Error actual
```
The OpenAI API Key is missing. Please publish the [openai.php] configuration file and set the [api_key].
```

---

## ✅ Solución en 4 pasos

### **Paso 1: Subir el archivo de configuración**

El archivo `config/openai.php` debe existir en producción.

**Opción A: Desde Git**
```bash
cd /ruta/del/proyecto
git pull origin [tu-rama]
```

**Opción B: Subir manualmente por FTP/SSH**

Crear el archivo: `config/openai.php` con este contenido:

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    */

    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),
];
```

---

### **Paso 2: Configurar el archivo .env en producción**

Edita el archivo `.env` en el servidor de producción y añade:

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-proj-tu_api_key_aqui
OPENAI_ORGANIZATION=
OPENAI_REQUEST_TIMEOUT=30
```

**⚠️ IMPORTANTE:**
- Reemplaza `tu_api_key_aqui` con tu API Key real de OpenAI
- Obtén tu API Key desde: https://platform.openai.com/api-keys
- Si no tienes organización, deja `OPENAI_ORGANIZATION` vacío

**Desde SSH:**
```bash
nano .env
# O usa tu editor preferido: vi, vim, etc.
```

**Por FTP/Panel:**
- Descarga el `.env`
- Añade las líneas
- Súbelo de nuevo

---

### **Paso 3: Limpiar cachés en producción**

**CRÍTICO:** Laravel cachea la configuración en producción. Debes limpiar los cachés:

```bash
cd /ruta/del/proyecto

# Limpiar caché de configuración
php artisan config:clear

# Limpiar caché general
php artisan cache:clear

# Limpiar caché de rutas
php artisan route:clear

# Limpiar vistas compiladas
php artisan view:clear

# Optimizar para producción (opcional, pero recomendado)
php artisan config:cache
php artisan route:cache
```

**Si NO tienes acceso SSH:**

Crea un archivo temporal en `public/clear-cache.php`:

```php
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Limpiar cachés
$kernel->call('config:clear');
$kernel->call('cache:clear');
$kernel->call('route:clear');
$kernel->call('view:clear');

echo "✅ Cachés limpiados correctamente\n";
echo "⚠️ ELIMINA ESTE ARCHIVO AHORA POR SEGURIDAD\n";
```

Luego accede a: `https://tudominio.com/clear-cache.php`

**⚠️ ELIMINA el archivo `clear-cache.php` después de usarlo!**

---

### **Paso 4: Verificar que funciona**

**Opción A: Desde Laravel Tinker (SSH)**
```bash
php artisan tinker

# Ejecutar en tinker:
config('openai.api_key')
# Debe mostrar tu API key (o al menos las primeras letras)
```

**Opción B: Crear archivo de prueba temporal**

Crea `public/test-openai.php`:

```php
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$apiKey = config('openai.api_key');

if (empty($apiKey)) {
    echo "❌ ERROR: API Key NO configurada\n";
    echo "Verifica el .env y ejecuta: php artisan config:clear\n";
} else {
    echo "✅ API Key configurada correctamente\n";
    echo "Key (primeros 10 caracteres): " . substr($apiKey, 0, 10) . "...\n";
}

echo "\n⚠️ ELIMINA ESTE ARCHIVO POR SEGURIDAD\n";
```

Accede a: `https://tudominio.com/test-openai.php`

**⚠️ ELIMINA el archivo después!**

**Opción C: Probar desde el asistente**

Accede al Asistente Virtual y envía un mensaje de prueba. Si funciona, todo está correcto.

---

## 🔍 Diagnóstico de problemas

### Si sigue sin funcionar:

#### 1. Verificar permisos de archivos
```bash
# El servidor web debe poder leer estos archivos
chmod 644 config/openai.php
chmod 644 .env
```

#### 2. Verificar que el .env se está cargando
```bash
php artisan tinker

# Ejecutar:
env('OPENAI_API_KEY')
# Debe mostrar tu key
```

#### 3. Verificar logs
```bash
tail -f storage/logs/laravel.log
```

#### 4. Verificar que el paquete OpenAI está instalado
```bash
composer show | grep openai
# Debe mostrar:
# openai-php/client
# openai-php/laravel
```

Si NO aparece:
```bash
composer install --no-dev --optimize-autoloader
```

---

## 📋 Checklist completo

- [ ] Archivo `config/openai.php` existe en producción
- [ ] Variable `OPENAI_API_KEY` añadida al `.env`
- [ ] API Key válida de OpenAI (desde https://platform.openai.com/api-keys)
- [ ] Ejecutado `php artisan config:clear`
- [ ] Ejecutado `php artisan cache:clear`
- [ ] Paquetes composer instalados (`openai-php/client`, `openai-php/laravel`)
- [ ] Permisos de archivos correctos (644)
- [ ] Probado desde el asistente

---

## 🔐 Seguridad

**⚠️ MUY IMPORTANTE:**
- NUNCA subas el `.env` a Git
- NUNCA expongas tu API Key en público
- Borra cualquier archivo de prueba temporal (`test-openai.php`, `clear-cache.php`)
- Revisa que `.env` está en `.gitignore`

---

## 💰 Costos de OpenAI

El asistente usa el modelo `gpt-4o-mini`:
- Muy económico (~$0.15 por millón de tokens de entrada)
- Optimizado para reducir tokens (85-90% de ahorro)
- Ideal para uso empresarial

Puedes monitorear el uso en: https://platform.openai.com/usage

---

## 🆘 Si nada funciona

1. Verifica los logs: `storage/logs/laravel.log`
2. Comprueba que el servidor tiene conexión a Internet
3. Verifica que OpenAI API está operativa: https://status.openai.com/
4. Prueba la API key manualmente:

```bash
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer TU_API_KEY_AQUI"
```

Si devuelve una lista de modelos, la key es válida.

---

**¡Listo! Después de estos pasos FERRALLIN debería funcionar en producción 🚀**
