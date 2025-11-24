import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Core app files
                "resources/css/app.css",
                "resources/js/app.js",

                // Utils
                "resources/js/utils/livewire-helper.js",

                // Global styles
                "resources/css/styles.css",
                "resources/css/etiquetas-responsive.css",

                // Module-specific entries
                "resources/js/modules/calendario-salidas/index.js",
                "resources/js/modules/calendario-trabajadores/index.js",
                "resources/css/estilosCalendarioSalidas.css",

                // Localizaciones
                "resources/css/localizaciones/styleLocIndex.css",
                "resources/css/localizaciones/styleLocCreate.css",
                "resources/css/ubicaciones/mapaUbis.css",
                "resources/css/mapaLocalizaciones.css",

                // Feature-specific bundles
                "resources/js/maquinaJS/maquina-bundle.js",
                "resources/js/elementosJs/elementos-bundle.js",
                "resources/js/paquetesJs/paquetes-bundle.js",
                "resources/js/salidasJs/salidas-bundle.js",
                "resources/js/qr/qr-bundle.js",
            ],
            refresh: true,
        }),
    ],
    build: {
        // Generar hashes únicos para cada build (cache busting)
        rollupOptions: {
            output: {
                // Agregar hash a los nombres de archivos
                entryFileNames: "assets/[name].[hash].js",
                chunkFileNames: "assets/[name].[hash].js",
                assetFileNames: "assets/[name].[hash].[ext]",
            },
        },
    },
    server: {
        host: "0.0.0.0",
        port: 5173,
        hmr: {
            host: "172.23.208.1",
        },
    },
});
