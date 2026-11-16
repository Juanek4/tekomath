import { defineConfig } from 'vite'
export default defineConfig({ publicDir: false, build: { outDir: 'public/shared', emptyOutDir: true, lib: { entry: 'src/services/imageTextRecognition.ts', formats: ['es'], fileName: () => 'image-recognition.js' } } })
