<template>
  <div class="space-y-3">
    <div
      class="border-2 border-dashed border-gray-300 rounded-xl px-6 py-8 text-sm text-gray-600 cursor-pointer flex flex-col items-center gap-3 transition-all"
      :class="{
        'opacity-50 cursor-not-allowed': disabled,
        'border-green-500 bg-green-50 text-green-600': dropActive,
        'border-blue-500 bg-blue-50 text-blue-700': isActive
      }" @click="triggerInput" @dragover.prevent="onDragOver" @dragleave.prevent="onDragLeave" @drop.prevent="onDrop">
      <font-awesome-icon :icon="['fas', 'image']" class="text-3xl" />
      <div class="font-medium text-center">Arraste e solte as imagens<br />ou clique para selecionar</div>
      <div class="text-xs text-gray-500" v-if="maxFiles">
        {{ files.length }}/{{ maxFiles }} arquivos · máx. {{ formatSize(maxTotalSize) }}
      </div>
      <div class="text-xs italic text-blue-600" v-if="isActive">Processando imagem…</div>
    </div>

    <input ref="fileInput" type="file" class="hidden" :accept="accept" :multiple="multiple" @change="createFile" />

    <div class="flex flex-wrap gap-3 mt-3" v-if="temporaryFiles.length">
      <div v-for="(file, index) in temporaryFiles" :key="file.uid"
        class="relative w-32 h-20 rounded-lg overflow-hidden border bg-gray-50">
        <template v-if="file.type === 'image'">
          <img :src="file.previewUrl || file.dataUrl" alt="Prévia" class="w-full h-full object-cover" />
        </template>
        <button type="button"
          class="absolute top-1 right-1 bg-red-600 text-white text-xs rounded-full px-[6px] py-[2px]"
          @click.stop="removeFile(index)">
          ×
        </button>
        <div v-if="file.status === 'uploading'"
          class="absolute inset-x-0 bottom-0 bg-black/60 text-white text-[10px] text-center py-0.5">
          {{ Math.round(file.percent) }}%
        </div>
      </div>
    </div>

    <p v-if="errorMessage" class="text-xs text-red-500">{{ errorMessage }}</p>

    <div v-if="cropState.visible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

      <div class="relative bg-white rounded-2xl shadow-2xl z-10 flex flex-col overflow-hidden"
        style="width: min(95vw, 42rem);">
        <div class="px-5 pt-4 border-b">
          <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <font-awesome-icon :icon="['fas', 'crop']" class="text-green-600" />
            Ajuste o corte
          </h2>
          <p class="text-xs text-gray-500 mt-1">
            Proporção fixa 163:80 — ajuste a área antes de confirmar.
          </p>
        </div>

        <div class="flex-1 p-5">
          <div class="relative w-full rounded-lg bg-gray-100 overflow-hidden border" style="aspect-ratio: 163 / 80;">
            <VuePictureCropper v-if="cropState.visible" :img="cropState.dataUrl" :options="cropperOptions"
              :box-style="cropperBoxStyle" class="absolute inset-0" />
          </div>
        </div>

        <div class="p-5 border-t flex justify-end gap-3">
          <button type="button" class="px-4 py-2 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-100"
            @click="cancelCrop">
            Cancelar
          </button>
          <button type="button"
            class="px-4 py-2 rounded bg-green-600 text-white text-sm font-medium transition hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed"
            :disabled="!cropReady" @click="confirmCrop">
            Aplicar corte
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import Compressor from 'compressorjs'
import VuePictureCropper, { cropper } from 'vue-picture-cropper'
import 'cropperjs/dist/cropper.css'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  accept: {
    type: String,
    default: 'image/*'
  },
  maxFiles: {
    type: Number,
    default: 4
  },
  width: {
    type: Number,
    default: 1280
  },
  height: {
    type: Number,
    default: 1280
  },
  quality: {
    type: Number,
    default: 0.8
  },
  disabled: {
    type: Boolean,
    default: false
  },
  maxTotalSize: {
    type: Number,
    default: 25 * 1024 * 1024
  },
  enforceCrop: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'remove', 'request-remove'])

const fileInput = ref(null)
const files = ref([])
const temporaryFiles = ref([])
const dropActive = ref(false)
const isActive = ref(false)
const errorMessage = ref('')
const cropReady = ref(false)
const cropState = reactive({
  visible: false,
  dataUrl: '',
  name: '',
  type: '',
  resolve: null,
  reject: null
})

const multiple = computed(() => props.maxFiles > 1)

function formatSize(size) {
  if (size > 1024 * 1024 * 1024 * 1024) {
    return (size / 1024 / 1024 / 1024 / 1024).toFixed(2) + ' TB'
  } else if (size > 1024 * 1024 * 1024) {
    return (size / 1024 / 1024 / 1024).toFixed(2) + ' GB'
  } else if (size > 1024 * 1024) {
    return (size / 1024 / 1024).toFixed(2) + ' MB'
  } else if (size > 1024) {
    return (size / 1024).toFixed(2) + ' KB'
  }
  return size.toString() + ' B'
}

function triggerInput() {
  if (props.disabled || cropState.visible || !canAddMore.value) return
  fileInput.value?.click()
}

function onDragOver(event) {
  if (props.disabled) return
  event.dataTransfer.dropEffect = 'copy'
  dropActive.value = true
}

function onDragLeave() {
  dropActive.value = false
}

function onDrop(event) {
  if (props.disabled) return
  dropActive.value = false
  createFile(event)
}

async function createFile(event) {
  if (props.disabled || cropState.visible) return
  errorMessage.value = ''
  const incoming = Array.from(event.target?.files || event.dataTransfer?.files || [])
  if (!incoming.length) return

  const remaining = props.maxFiles - files.value.length
  if (remaining <= 0) {
    errorMessage.value = `Você já atingiu o limite de ${props.maxFiles} arquivos.`
    resetInput(event)
    return
  }

  const selected = incoming.slice(0, remaining)

  const totalSize = selected.reduce((acc, f) => acc + f.size, currentTotalSize.value)
  if (totalSize > props.maxTotalSize) {
    errorMessage.value = `O tamanho total excede ${formatSize(props.maxTotalSize)}.`
    resetInput(event)
    return
  }

  isActive.value = true

  try {
    for (const file of selected) {
      if (!file.type.startsWith('image/')) {
        errorMessage.value = 'Apenas arquivos de imagem são permitidos.'
        continue
      }

      if (isHeic(file)) {
        errorMessage.value = 'Arquivos HEIC/HEIF não são suportados. Converta para JPG ou PNG antes de enviar.'
        continue
      }

      const originalFile = file

      if (isGif(file)) {
        await handleFile(file)
        logCompression(originalFile, file)
        continue
      }

      let workingFile = file

      if (props.enforceCrop) {
        try {
          const cropped = await showCropper(workingFile)
          if (!cropped) continue
          workingFile = cropped
        } catch (err) {
          if (err?.message !== 'crop-cancelled') {
            console.warn('Crop cancelado ou falhou', err)
          }
          continue
        }
      }

      await compressAndHandle(workingFile, originalFile)
    }
  } finally {
    emitFiles()
    resetInput(event)
    isActive.value = false
  }
}

function resetInput(event) {
  if (event?.target?.type === 'file') {
    event.target.value = ''
  }
}

async function compressAndHandle(file, originalReference = file) {
  try {
    const blob = await new Promise((resolve, reject) => {
      new Compressor(file, {
        quality: props.quality,
        width: targetSize.value,
        height: targetSize.value,
        mimeType: file.type === 'image/png' ? 'image/jpeg' : file.type,
        convertSize: 0,
        success(result) {
          resolve(result)
        },
        error(err) {
          reject(err)
        }
      })

    })

    const converted = blob instanceof File
      ? blob
      : new File([blob], file.name, { type: blob.type || file.type })

    logCompression(originalReference, converted)
    await handleFile(converted, file.name)
  } catch (error) {
    console.error('Erro ao comprimir imagem', error)
    errorMessage.value = 'Não foi possível processar uma das imagens.'
  }
}

async function handleFile(file, originalName = null) {
  const uid = generateUid()
  const entry = reactive({
    uid,
    file,
    type: 'image',
    mime: file.type,
    url: null,
    previewUrl: URL.createObjectURL(file),
    status: 'uploading',
    percent: 5,
    name: originalName || file.name,
    size: file.size,
    dataUrl: ''
  })

  files.value.push(entry)
  temporaryFiles.value.push(entry)

  try {
    await convertEntryToDataUrl(entry)
  } catch (error) {
    console.error('Erro ao gerar base64 da imagem', error)
  }

  return entry
}

function convertEntryToDataUrl(entry) {
  return new Promise((resolve, reject) => {
    if (!entry.file) {
      entry.status = 'ready'
      entry.percent = 100
      emitFiles()
      resolve(entry)
      return
    }

    const reader = new FileReader()
    reader.onprogress = event => {
      if (event.lengthComputable) {
        const progress = Math.round((event.loaded / event.total) * 90) + 5
        entry.percent = Math.min(progress, 99)
      }
    }
    reader.onload = () => {
      if (typeof reader.result === 'string') {
        entry.dataUrl = reader.result
        if (entry.previewUrl?.startsWith('blob:')) {
          URL.revokeObjectURL(entry.previewUrl)
        }
        entry.previewUrl = entry.dataUrl
        entry.status = 'ready'
        entry.percent = 100
        entry.file = null
        entry.url = null
        emitFiles()
        resolve(entry)
      } else {
        entry.status = 'error'
        entry.percent = 0
        reject(new Error('invalid-reader-result'))
      }
    }
    reader.onerror = error => {
      entry.status = 'error'
      entry.percent = 0
      reject(error)
    }
    reader.readAsDataURL(entry.file)
  })
}

function removeFile(index) {
  if (index < 0 || index >= files.value.length) return
  const file = files.value[index]

  if (file.id) {
    emit('request-remove', { file, index })
    return
  }

  files.value.splice(index, 1)
  temporaryFiles.value.splice(index, 1)
  emitFiles()
}


const canAddMore = computed(() => files.value.length < props.maxFiles)
const currentTotalSize = computed(() => files.value.reduce((acc, f) => acc + (f.size || 0), 0))

function isAllUploading() {
  return temporaryFiles.value.some(file => {
    if (file.id || file.url) return false
    return file.status === 'uploading' || !file.dataUrl
  })
}

function setItens(newOrder = []) {
  if (!Array.isArray(newOrder) || !newOrder.length) return
  const map = new Map(files.value.map(entry => [entry.uid, entry]))
  const reordered = newOrder.map(item => map.get(item.uid)).filter(Boolean)
  if (!reordered.length) return
  files.value = reordered.slice()
  temporaryFiles.value = reordered.slice()
  emitFiles()
}

let internalUpdate = false

watch(
  () => props.modelValue,
  (newValue) => {
    if (internalUpdate || !Array.isArray(newValue)) return

    const normalized = newValue.map(item => ({
      uid: item.uid || item.id || generateUid(),
      id: item.id || null,
      type: 'image',
      mime: item.mime || item.type || 'image/jpeg',
      url: item.url || null,
      previewUrl: item.previewUrl || item.url || item.dataUrl || '',
      status: item.status || 'ready',
      percent: item.percent ?? 100,
      name: item.name || 'Imagem',
      size: item.size || 0,
      dataUrl: item.dataUrl || ''
    }))

    const isSame =
      files.value.length === normalized.length &&
      files.value.every((f, i) => f.id === normalized[i].id && f.url === normalized[i].url)

    if (isSame) return

    files.value = normalized.map(x => reactive(x))
    temporaryFiles.value = [...files.value]
  },
  { immediate: true }
)

function emitFiles() {
  internalUpdate = true
  const output = files.value
    .filter(entry => entry.dataUrl || entry.url)
    .map(entry => ({
      uid: entry.uid,
      id: entry.id,
      name: entry.name,
      type: entry.mime,
      size: entry.size,
      dataUrl: entry.dataUrl,
      url: entry.url,
      previewUrl: entry.previewUrl,
      status: entry.status,
      percent: entry.percent
    }))
  emit('update:modelValue', output)
  nextTick(() => (internalUpdate = false))
}

function cleanupTemp() {
  temporaryFiles.value.forEach(temp => {
    if (temp.previewUrl?.startsWith('blob:')) {
      URL.revokeObjectURL(temp.previewUrl)
    }
  })
}

onBeforeUnmount(() => {
  cleanupTemp()
})

const maxTotalSize = computed(() => props.maxTotalSize)
const targetSize = computed(() => {
  const width = Number.isFinite(props.width) ? Number(props.width) : 0
  const height = Number.isFinite(props.height) ? Number(props.height) : 0
  if (width && height) return Math.min(width, height)
  return width || height || 1280
})
const aspectRatio = computed(() => 163 / 80)
const cropperOptions = computed(() => ({
  viewMode: 0, // 🔓 Permite cortar além da borda da imagem
  dragMode: 'move', // Move imagem ao arrastar
  aspectRatio: aspectRatio.value,
  initialAspectRatio: aspectRatio.value,
  autoCropArea: 1,
  background: true,
  guides: true,
  center: true,
  highlight: true,
  movable: true,
  zoomable: true,
  cropBoxMovable: true,
  cropBoxResizable: true,
  toggleDragModeOnDblclick: false,
  responsive: true,
  ready() {
    alignCropper()
  }
}))

const cropperBoxStyle = computed(() => ({
  width: '100%',
  height: '100%',
  backgroundColor: '#f3f4f6',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  overflow: 'hidden',
  borderRadius: '0.5rem'
}))

function generateUid() {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`
}

function isGif(file) {
  if (!file) return false
  const name = typeof file.name === 'string' ? file.name.toLowerCase() : ''
  return file.type === 'image/gif' || name.endsWith('.gif')
}

function isHeic(file) {
  if (!file) return false
  const name = typeof file.name === 'string' ? file.name.toLowerCase() : ''
  return file.type === 'image/heic' || file.type === 'image/heif' || name.endsWith('.heic') || name.endsWith('.heif')
}

function alignCropper(attempt = 0) {
  const instance = cropper
  if (!instance) {
    if (attempt < 20) {
      setTimeout(() => alignCropper(attempt + 1), 50)
    }
    return
  }

  try {
    const canvas = instance.getCanvasData()
    if (!canvas.width || !canvas.height) {
      if (attempt < 20) {
        setTimeout(() => alignCropper(attempt + 1), 50)
      }
      return
    }
    const side = Math.min(canvas.width, canvas.height)
    const left = canvas.left + (canvas.width - side) / 2
    const top = canvas.top + (canvas.height - side) / 2
    instance.setCropBoxData({
      width: side,
      height: side,
      left,
      top
    })
    instance.setDragMode('crop')
    cropReady.value = true
  } catch (error) {
    if (attempt < 20) {
      setTimeout(() => alignCropper(attempt + 1), 50)
    }
  }
}

function logCompression(original, processed) {
  if (!original?.size || !processed?.size) return
  const reduction = original.size - processed.size
  const percent = original.size ? (reduction / original.size) * 100 : 0
  console.log(
    `[Uploader] ${original.name}: ${formatSize(original.size)} → ${formatSize(processed.size)} (${percent.toFixed(2)}% de redução)`
  )
}

async function showCropper(file) {
  const dataUrl = await fileToDataUrl(file)

  return new Promise((resolve, reject) => {
    cropReady.value = false
    cropState.visible = true
    cropState.dataUrl = dataUrl
    cropState.name = file.name
    cropState.type = file.type
    cropState.resolve = resolve
    cropState.reject = reject

    nextTick(() => {
      alignCropper()
    })
  })
}

async function confirmCrop() {
  const instance = cropper
  if (!cropReady.value || !instance) {
    cropState.reject?.(new Error('cropper-instance-invalid'))
    closeCropper()
    return
  }

  try {
    const blob = await instance.getBlob({
      width: targetSize.value,
      height: targetSize.value,
      imageSmoothingEnabled: true
    })

    if (!blob) throw new Error('crop-blob-null')

    const fileType = blob.type || cropState.type || 'image/jpeg'
    const croppedFile = new File([blob], cropState.name, { type: fileType })
    cropState.resolve?.(croppedFile)
  } catch (error) {
    cropState.reject?.(error)
  } finally {
    closeCropper()
  }
}

function cancelCrop() {
  cropState.reject?.(new Error('crop-cancelled'))
  closeCropper()
}

function closeCropper() {
  cropState.visible = false
  cropState.dataUrl = ''
  cropState.name = ''
  cropState.type = ''
  cropState.resolve = null
  cropState.reject = null
  cropReady.value = false
}

function fileToDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => {
      if (typeof reader.result === 'string') {
        resolve(reader.result)
      } else {
        reject(new Error('file-read-error'))
      }
    }
    reader.onerror = error => reject(error)
    reader.readAsDataURL(file)
  })
}

defineExpose({
  isAllUploading,
  setItens
})

</script>
