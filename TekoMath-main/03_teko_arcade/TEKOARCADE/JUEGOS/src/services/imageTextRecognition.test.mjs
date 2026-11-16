import assert from 'node:assert/strict'
import { test } from 'node:test'
import {
  recognizeImageText,
  terminateImageTextRecognition,
} from './imageTextRecognition.ts'

test('recognizes and normalizes Spanish text from an image blob', async () => {
  let receivedImage
  const recognized = await recognizeImageText(new Blob(['image'], {type:'image/png'}), {
    workerFactory: async () => ({
      recognize: async (image) => {
        receivedImage = image
        return { data: { text: '  Calculá seno(θ)\r\n  opuesto 6  ' } }
      },
      terminate: async () => undefined,
    }),
  })

  assert.equal(receivedImage.size, 5)
  assert.equal(recognized, 'Calculá seno(θ)\n  opuesto 6')
  await terminateImageTextRecognition()
})

test('rejects empty and oversized images before starting OCR', async () => {
  let workerCreated = false
  const workerFactory = async () => {
    workerCreated = true
    throw new Error('worker should not start')
  }
  await assert.rejects(recognizeImageText(new Blob([]), { workerFactory }), /12 MB/)
  await assert.rejects(recognizeImageText(new Blob([new Uint8Array(12 * 1024 * 1024 + 1)]), { workerFactory }), /12 MB/)
  assert.equal(workerCreated, false)
})
test('rejects unsupported files and low-confidence recognition without fabricating text', async () => {
  await assert.rejects(recognizeImageText(new Blob(['text'], { type: 'text/plain' })), /JPG/)
  await assert.rejects(recognizeImageText(new Blob(['image'], { type: 'image/png' }), {
    workerFactory: async () => ({ recognize: async () => ({ data: { text: 'uncertain', confidence: 10 } }), terminate: async () => undefined }),
  }), /claridad/)
  await terminateImageTextRecognition()
})