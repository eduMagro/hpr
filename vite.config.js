import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    const serverHost = env.VITE_DEV_SERVER_HOST || "0.0.0.0";
    const serverPort = Number(env.VITE_DEV_SERVER_PORT) || 5173;

    const hmrHost = env.VITE_HMR_HOST || serverHost || "localhost";
    const hmrPort = Number(env.VITE_HMR_PORT) || serverPort;

    return {
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

                    "resources/css/mapaLocalizaciones.css",
                    "resources/css/ubicaciones/mapaUbis.css",

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
            rollupOptions: {
                output: {
                    entryFileNames: "assets/[name].[hash].js",
                    chunkFileNames: "assets/[name].[hash].js",
                    assetFileNames: "assets/[name].[hash].[ext]",
                },
            },
        },
        server: {
            host: serverHost,
            port: serverPort,
            hmr: {
                host: hmrHost,
                port: hmrPort,
            },
        },
    };
});
