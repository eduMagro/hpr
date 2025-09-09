# Repo info

## Proyecto

-   **Framework**: Laravel 11 (PHP ^8.2)
-   **Frontend**: Vite, TailwindCSS, Alpine.js, Vue 3
-   **Node**: 20 (archivo .node-version)
-   **Herramientas**: Laravel Vite Plugin, PostCSS/Autoprefixer

## Paquetes destacados (PHP)

-   **laravel/framework**: ^11.31
-   **barryvdh/laravel-dompdf**: PDF
-   **maatwebsite/excel**: Excel import/export
-   **simplesoftwareio/simple-qrcode**: QR
-   **intervention/image**: Procesado de imágenes
-   **spatie/laravel-csp**: Content-Security-Policy
-   **symfony/dom-crawler + css-selector**: scraping/navegación DOM

## Scripts útiles (Composer)

-   **dev**: arranca servidor, queue listener, logs/pail y Vite en paralelo.
    -   Ejecuta: php artisan serve, queue:listen, pail y npm run dev con concurrently.

## Scripts útiles (NPM)

-   **dev**: vite
-   **build**: vite build

## Estructura (resumen)

-   **app/**: código de aplicación (HTTP, Models, etc.)
-   **resources/**: vistas Blade, assets (css/js)
-   **public/**: archivos públicos (incluye js de máquina: public/js/maquinaJS)
-   **routes/**: web.php, api/console según necesidad
-   **database/**: migraciones, seeders
-   **storage/**: logs, cachés, sesiones
-   **tests/**: tests Feature/Unit

## Notas del proyecto

-   Existen vistas y lógica para etiquetas/maquinas. En `public/js/maquinaJS/canvasMaquina.js` se construyen SVGs de figuras con cotas y ahora con **letras por figura** y **leyenda** inferior izquierda para Ø/peso/barras, minimizando colisiones visuales.
-   Se usan variables CSS (por ejemplo `--bg-estado`) para colorear el fondo lógico del SVG.

## Cómo levantar entorno (típico)

1. Copiar `.env.example` a `.env` y configurar.
2. `composer install` y `php artisan key:generate`.
3. `npm ci` (o `npm install`).
4. `php artisan serve` y `npm run dev` (o usar `composer run dev`).

## Puntos de entrada comunes

-   **HTTP**: controladores bajo `app/Http/Controllers` (p.ej. `EtiquetaController`)
-   **Frontend**: `resources/js`, `public/js/maquinaJS` para dibujo de etiquetas/maquinas.

## Seguridad y CSP

-   Usa `spatie/laravel-csp`; revisar políticas si se añaden scripts inline o recursos externos.
