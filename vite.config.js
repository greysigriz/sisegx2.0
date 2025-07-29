// vite.config.js
import { fileURLToPath, URL, pathToFileURL } from 'node:url'
import { defineConfig, mergeConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import fs from 'fs'
import path from 'path'

const baseConfig = defineConfig({
  plugins: [vue(), vueDevTools()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})

const localConfigPath = path.resolve('./vite.config.local.js')

/** Exporta configuración combinada si existe vite.config.local.js */
export default async () => {
  if (fs.existsSync(localConfigPath)) {
    const localModule = await import(pathToFileURL(localConfigPath).href)
    return mergeConfig(baseConfig, localModule.default)
  }
  return baseConfig
}
