<template>
  <div class="p-4 sm:p-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Validação de Inscrições</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-lg font-bold mb-4">Leitor de QR Code</h2>
        <div id="qr-reader" class="mb-4"></div>
        <button v-if="!scanning" @click="startScanning"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
          Iniciar Leitura
        </button>
        <button v-else @click="stopScanning"
          class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
          Parar Leitura
        </button>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-lg font-bold mb-4">Validação Manual</h2>
        <div class="space-y-4">
          <div>
            <label class="block mb-1 font-medium">Buscar por Nome</label>
            <div class="flex gap-2">
              <input v-model="searchName" @keyup.enter="validateByName"
                class="flex-1 border rounded-lg p-2" placeholder="Digite o nome" />
              <button @click="validateByName"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                Buscar
              </button>
            </div>
          </div>
          <div>
            <label class="block mb-1 font-medium">Buscar por Telefone</label>
            <div class="flex gap-2">
              <input v-model="searchPhone" v-maska="'(##) #####-####'" @keyup.enter="validateByPhone"
                class="flex-1 border rounded-lg p-2" placeholder="(00) 00000-0000" />
              <button @click="validateByPhone"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                Buscar
              </button>
            </div>
          </div>
          <div>
            <label class="block mb-1 font-medium">Evento</label>
            <select v-model="selectedEventId" class="w-full border rounded-lg p-2">
              <option value="">Selecione um evento</option>
              <option v-for="evt in events" :key="evt.id" :value="evt.id">{{ evt.name }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div v-if="lastValidation" class="bg-white p-6 rounded-lg shadow-md">
      <h2 class="text-lg font-bold mb-4">Última Validação</h2>
      <div v-if="lastValidation.valid" class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center gap-2 text-green-700 mb-2">
          <font-awesome-icon :icon="['fas', 'check-circle']" class="text-2xl" />
          <span class="font-bold">Validação realizada com sucesso!</span>
        </div>
        <div class="space-y-1 text-sm">
          <div><strong>Nome:</strong> {{ lastValidation.registration.name }}</div>
          <div><strong>Telefone:</strong> {{ lastValidation.registration.phone }}</div>
          <div><strong>Número de Inscrição:</strong> {{ lastValidation.registration.registration_number }}</div>
          <div><strong>Evento:</strong> {{ lastValidation.registration.event?.name }}</div>
        </div>
      </div>
      <div v-else class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center gap-2 text-red-700 mb-2">
          <font-awesome-icon :icon="['fas', 'times-circle']" class="text-2xl" />
          <span class="font-bold">{{ lastValidation.message }}</span>
        </div>
        <div v-if="lastValidation.validated_at" class="text-sm text-gray-600 mt-2">
          Já foi validado em {{ formatDate(lastValidation.validated_at) }} por {{ lastValidation.validated_by }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
import { validationApi, eventsApi } from '@/api/events'
import { FontAwesomeIcon } from '@/plugins/fontawesome'
import { vMaska } from 'maska/vue'

const scanning = ref(false)
const html5QrCode = ref(null)
const searchName = ref('')
const searchPhone = ref('')
const selectedEventId = ref('')
const events = ref([])
const lastValidation = ref(null)

onMounted(async () => {
  await loadEvents()
})

onUnmounted(() => {
  stopScanning()
})

async function loadEvents() {
  try {
    const { data } = await eventsApi.list({ active: true })
    events.value = data.data || data
  } catch (error) {
    console.error('Erro ao carregar eventos:', error)
  }
}

function startScanning() {
  scanning.value = true
  html5QrCode.value = new Html5Qrcode('qr-reader')

  html5QrCode.value.start(
    { facingMode: 'environment' },
    {
      fps: 10,
      qrbox: { width: 250, height: 250 },
    },
    onScanSuccess,
    onScanError
  )
}

function stopScanning() {
  if (html5QrCode.value) {
    html5QrCode.value.stop().then(() => {
      html5QrCode.value.clear()
      html5QrCode.value = null
      scanning.value = false
    }).catch(() => {
      scanning.value = false
    })
  }
}

async function onScanSuccess(decodedText) {
  try {
    await validateByQrCode(decodedText)
    stopScanning()
  } catch (error) {
    console.error('Erro ao validar QR code:', error)
  }
}

function onScanError(errorMessage) {
  // Ignorar erros de leitura
}

async function validateByQrCode(qrCode) {
  try {
    const { data } = await validationApi.validateByQrCode(qrCode)
    lastValidation.value = data
    if (data.valid) {
      alert('Inscrição validada com sucesso!')
    }
  } catch (error) {
    lastValidation.value = {
      valid: false,
      message: error.response?.data?.message || 'Erro ao validar inscrição',
    }
  }
}

async function validateByName() {
  if (!selectedEventId.value) {
    alert('Selecione um evento')
    return
  }
  if (!searchName.value.trim()) {
    alert('Digite um nome')
    return
  }
  try {
    const { data } = await validationApi.validateByName(searchName.value, selectedEventId.value)
    lastValidation.value = data
    if (data.valid) {
      alert('Inscrição validada com sucesso!')
      searchName.value = ''
    }
  } catch (error) {
    lastValidation.value = {
      valid: false,
      message: error.response?.data?.message || 'Erro ao validar inscrição',
    }
  }
}

async function validateByPhone() {
  if (!selectedEventId.value) {
    alert('Selecione um evento')
    return
  }
  if (!searchPhone.value.trim()) {
    alert('Digite um telefone')
    return
  }
  try {
    const phone = searchPhone.value.replace(/\D/g, '')
    const { data } = await validationApi.validateByPhone(phone, selectedEventId.value)
    lastValidation.value = data
    if (data.valid) {
      alert('Inscrição validada com sucesso!')
      searchPhone.value = ''
    }
  } catch (error) {
    lastValidation.value = {
      valid: false,
      message: error.response?.data?.message || 'Erro ao validar inscrição',
    }
  }
}

function formatDate(date) {
  return new Date(date).toLocaleString('pt-BR')
}
</script>

<style>
#qr-reader {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
}
</style>
