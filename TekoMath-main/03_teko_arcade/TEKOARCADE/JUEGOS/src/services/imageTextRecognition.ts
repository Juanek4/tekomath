import { createWorker, OEM } from 'tesseract.js'
interface OcrWorker {
  recognize(image: Blob): Promise<{ data: { text: string; confidence?: number } }>
  terminate(): Promise<unknown>
}
export interface ImageTextRecognitionOptions { workerFactory?: () => Promise<OcrWorker> }
let workerPromise: Promise<OcrWorker> | null = null
let queue: Promise<unknown> = Promise.resolve()
export async function recognizeImageText(image: Blob, options: ImageTextRecognitionOptions = {}): Promise<string> {
  if (image.size === 0 || image.size > 12 * 1024 * 1024) throw new Error('La imagen debe pesar entre 1 byte y 12 MB.')
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(image.type)) throw new Error('Usá una imagen JPG, PNG o WebP.')
  const task = queue.then(async () => {
    if (typeof createImageBitmap === 'function') {
      let bitmap: ImageBitmap
      try { bitmap = await createImageBitmap(image) } catch { throw new Error('La imagen está dañada o no se puede abrir.') }
      const pixels = bitmap.width * bitmap.height; bitmap.close()
      if (pixels > 16000000) throw new Error('La imagen es demasiado grande. Recortá el ejercicio antes de continuar.')
    }
    let timer: ReturnType<typeof setTimeout> | undefined
    try {
      const result = await Promise.race([
        (async () => { const worker = await getWorker(options.workerFactory); return worker.recognize(image) })(),
        new Promise<never>((_, reject) => { timer = setTimeout(() => reject(new Error('La lectura tardó demasiado. Probá con una foto más clara o escribí el enunciado.')), 60000) }),
      ])
      const text = result.data.text.normalize('NFC').replace(/\r\n?/g, '\n').trim()
      if (!text || (typeof result.data.confidence === 'number' && result.data.confidence < 35)) throw new Error('No pude leer el texto con suficiente claridad. Recortá la foto o escribí el enunciado.')
      return text.slice(0, 12000)
    } catch (error) { void terminateImageTextRecognition(); throw error }
    finally { if (timer) clearTimeout(timer) }
  })
  queue = task.catch(() => undefined)
  return task
}
export async function terminateImageTextRecognition(): Promise<void> {
  const pending = workerPromise; workerPromise = null
  if (pending) { const worker = await pending.catch(() => null); await worker?.terminate() }
}
async function getWorker(factory?: ImageTextRecognitionOptions['workerFactory']): Promise<OcrWorker> {
  if (!workerPromise) workerPromise = (factory ?? createSpanishWorker)().catch((error: unknown) => { workerPromise = null; throw error })
  return workerPromise
}
function createSpanishWorker(): Promise<OcrWorker> {
  const prefix = import.meta.url.split('/03_teko_arcade/')[0]
  const assetRoot = prefix + '/03_teko_arcade/TEKOARCADE/JUEGOS/dist/tesseract'
  return createWorker('spa', OEM.LSTM_ONLY, { workerPath: `${assetRoot}/worker.min.js`, corePath: `${assetRoot}/tesseract-core-lstm.wasm.js`, langPath: `${assetRoot}/lang`, gzip: true, logger: () => undefined })
}
