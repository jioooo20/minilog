import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
// import tailwindcss from "tailwindcss";

export default defineConfig({
        server: {
        host: true,
        // port: 5173,
        // strictPort: true,
        // hmr: {
        //     host: '192.168.1.9',
        // }
    },
    plugins: [
        laravel({
            input: "resources/js/app.js",
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // tailwindcss(),
    ],
});
