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
                <input v-model="searchName" @keyup.enter="searchByName"
                  class="flex-1 border rounded-lg p-2" placeholder="Digite o nome" />
                <button @click="searchByName"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                  Buscar
                </button>
              </div>
            </div>
            <div>
              <label class="block mb-1 font-medium">Buscar por Telefone</label>
              <div class="flex gap-2">
                <input v-model="searchPhone" v-maska="'(##) #####-####'" @keyup.enter="searchByPhone"
                  class="flex-1 border rounded-lg p-2" placeholder="(00) 00000-0000" />
                <button @click="searchByPhone"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                  Buscar
                </button>
              </div>
            </div>
            <div>
              <label class="block mb-1 font-medium">Validar por Número da Inscrição</label>
              <div class="flex gap-2">
                <input v-model="searchRegistrationNumber" 
                  @input="searchRegistrationNumber = $event.target.value.toUpperCase()"
                  @keyup.enter="validateByRegistrationNumber"
                  class="flex-1 border rounded-lg p-2 uppercase font-mono" 
                  placeholder="HYXTW49K" 
                  maxlength="10" />
                <button @click="validateByRegistrationNumber"
                  class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                  Validar
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

    <!-- Resultado da busca com múltiplas inscrições -->
    <div v-if="foundRegistrations.length > 0" class="bg-white p-6 rounded-lg shadow-md">
      <h2 class="text-lg font-bold mb-4">Resultado da Busca ({{ foundRegistrations.length }} inscrição(ões) encontrada(s))</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border rounded-lg overflow-hidden">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="p-3 border text-left">Código</th>
              <th class="p-3 border text-left">Nome</th>
              <th class="p-3 border text-left">Telefone</th>
              <th class="p-3 border text-left">CPF</th>
              <th class="p-3 border text-left">Evento</th>
              <th class="p-3 border text-left">Status</th>
              <th class="p-3 border text-left">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reg in foundRegistrations" :key="reg.id" 
              class="hover:bg-gray-50 transition"
              :class="reg.validated ? 'bg-green-50' : ''">
              <td class="p-3 border font-mono font-bold text-blue-600">
                {{ reg.registration_number }}
              </td>
              <td class="p-3 border text-gray-800">{{ reg.name }}</td>
              <td class="p-3 border text-gray-700">{{ reg.phone }}</td>
              <td class="p-3 border text-gray-700">{{ reg.cpf || '-' }}</td>
              <td class="p-3 border text-gray-700">{{ reg.event?.name || '-' }}</td>
              <td class="p-3 border">
                <span v-if="reg.validated" 
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                  <font-awesome-icon :icon="['fas', 'check-circle']" />
                  Validada
                </span>
                <span v-else 
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                  <font-awesome-icon :icon="['fas', 'clock']" />
                  Pendente
                </span>
                <div v-if="reg.validated_at" class="text-xs text-gray-500 mt-1">
                  {{ formatDate(reg.validated_at) }}
                </div>
                <div v-if="reg.validated_by" class="text-xs text-gray-500">
                  Por: {{ reg.validated_by }}
                </div>
              </td>
              <td class="p-3 border">
                <button v-if="!reg.validated" @click="validateRegistration(reg)"
                  class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold text-sm">
                  <font-awesome-icon :icon="['fas', 'check']" class="mr-1" />
                  Validar
                </button>
                <span v-else class="text-green-600 text-sm font-semibold">
                  ✓ Validada
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Resultado de validação única (QR code ou número da inscrição) -->
    <div v-if="lastValidation && foundRegistrations.length === 0" class="bg-white p-6 rounded-lg shadow-md">
      <h2 class="text-lg font-bold mb-4">Resultado</h2>
      
      <!-- Inscrição encontrada (não validada ainda) -->
      <div v-if="lastValidation.valid === null && lastValidation.registration" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center gap-2 text-blue-700 mb-2">
          <font-awesome-icon :icon="['fas', 'info-circle']" class="text-2xl" />
          <span class="font-bold">{{ lastValidation.message || 'Inscrição encontrada' }}</span>
        </div>
        <div class="space-y-1 text-sm mb-4">
          <div><strong>Nome:</strong> {{ lastValidation.registration.name }}</div>
          <div><strong>Telefone:</strong> {{ lastValidation.registration.phone }}</div>
          <div><strong>Número de Inscrição:</strong> {{ lastValidation.registration.registration_number }}</div>
          <div><strong>Evento:</strong> {{ lastValidation.registration.event?.name }}</div>
          <div><strong>Status:</strong> 
            <span v-if="lastValidation.registration.validated" class="text-green-600 font-semibold">Já validada</span>
            <span v-else class="text-yellow-600 font-semibold">Não validada</span>
          </div>
        </div>
        <button v-if="!lastValidation.registration.validated" @click="handleValidate"
          class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold">
          <font-awesome-icon :icon="['fas', 'check']" class="mr-2" />
          Validar Inscrição
        </button>
      </div>
      
      <!-- Validação realizada com sucesso -->
      <div v-else-if="lastValidation.valid === true" class="bg-green-50 border border-green-200 rounded-lg p-4">
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
      
      <!-- Erro ou já validada -->
      <div v-else class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center gap-2 text-red-700 mb-2">
          <font-awesome-icon :icon="['fas', 'times-circle']" class="text-2xl" />
          <span class="font-bold">{{ lastValidation.message }}</span>
        </div>
        <div v-if="lastValidation.validated_at" class="text-sm text-gray-600 mt-2">
          Já foi validado em {{ formatDate(lastValidation.validated_at) }} por {{ lastValidation.validated_by }}
        </div>
        <div v-if="lastValidation.registration" class="space-y-1 text-sm mt-2">
          <div><strong>Nome:</strong> {{ lastValidation.registration.name }}</div>
          <div><strong>Número de Inscrição:</strong> {{ lastValidation.registration.registration_number }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
import { validationApi, eventsApi, registrationsApi } from '@/api/events'
import { FontAwesomeIcon } from '@/plugins/fontawesome'
import { vMaska } from 'maska/vue'

const scanning = ref(false)
const html5QrCode = ref(null)
const searchName = ref('')
const searchPhone = ref('')
const searchRegistrationNumber = ref('')
const selectedEventId = ref('')
const events = ref([])
const lastValidation = ref(null)
const foundRegistrations = ref([])

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
    // Apenas buscar e mostrar, não validar automaticamente
    await searchByQrCode(decodedText)
    stopScanning()
  } catch (error) {
    console.error('Erro ao buscar QR code:', error)
  }
}

function onScanError(errorMessage) {
  // Ignorar erros de leitura
}

// Buscar por QR code (somente leitura, não valida)
async function searchByQrCode(qrCode) {
  try {
    const { data } = await registrationsApi.getByQrCode(qrCode)
    lastValidation.value = {
      valid: null, // null indica que é apenas busca, não validação
      registration: data,
      message: 'Inscrição encontrada',
    }
  } catch (error) {
    lastValidation.value = {
      valid: false,
      message: error.response?.data?.message || 'Inscrição não encontrada',
    }
  }
}

// Validar por QR code (chamado pelo botão)
async function validateByQrCode() {
  if (!lastValidation.value?.registration?.qr_code) {
    alert('Nenhuma inscrição selecionada para validar')
    return
  }
  try {
    const { data } = await validationApi.validateByQrCode(lastValidation.value.registration.qr_code)
    lastValidation.value = data
    if (data.valid) {
      alert('Inscrição validada com sucesso!')
    }
  } catch (error) {
    lastValidation.value = {
      valid: false,
      message: error.response?.data?.message || 'Erro ao validar inscrição',
      registration: lastValidation.value.registration, // Manter dados da busca
    }
  }
}

// Buscar por nome (não valida automaticamente)
async function searchByName() {
  if (!selectedEventId.value) {
    alert('Selecione um evento')
    return
  }
  if (!searchName.value.trim()) {
    alert('Digite um nome')
    return
  }
  try {
    // Usar endpoint de busca que retorna múltiplas registrations
    const { data } = await validationApi.searchByName(searchName.value, selectedEventId.value)
    foundRegistrations.value = data.registrations || []
    lastValidation.value = null
  } catch (error) {
    foundRegistrations.value = []
    lastValidation.value = {
      valid: false,
      message: error.response?.data?.message || 'Nenhuma inscrição encontrada',
    }
  }
}

// Validar por nome (chamado pelo botão)
async function validateByName() {
  if (!lastValidation.value?.registration) {
    // Se não tem busca anterior, fazer busca e validar
    await searchByName()
    if (lastValidation.value?.registration && !lastValidation.value.registration.validated) {
      // Validar agora
      try {
        const { data } = await validationApi.validateByName(
          lastValidation.value.registration.name,
          selectedEventId.value
        )
        lastValidation.value = data
        if (data.valid) {
          alert('Inscrição validada com sucesso!')
          searchName.value = ''
        }
      } catch (error) {
        lastValidation.value = {
          valid: false,
          message: error.response?.data?.message || 'Erro ao validar inscrição',
          registration: lastValidation.value.registration,
        }
      }
    }
    return
  }
  
  // Se já tem busca, validar
  try {
    const { data } = await validationApi.validateByName(
      lastValidation.value.registration.name,
      selectedEventId.value
    )
    lastValidation.value = data
    if (data.valid) {
      alert('Inscrição validada com sucesso!')
      searchName.value = ''
    }
  } catch (error) {
    lastValidation.value = {
      valid: false,
      message: error.response?.data?.message || 'Erro ao validar inscrição',
      registration: lastValidation.value.registration,
    }
  }
}

// Buscar por telefone (não valida automaticamente)
async function searchByPhone() {
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
    // Usar endpoint de busca que retorna múltiplas registrations
    const { data } = await validationApi.searchByPhone(phone, selectedEventId.value)
    foundRegistrations.value = data.registrations || []
    lastValidation.value = null
  } catch (error) {
    foundRegistrations.value = []
    lastValidation.value = {
      valid: false,
      message: error.response?.data?.message || 'Nenhuma inscrição encontrada',
    }
  }
}

// Validar por telefone (chamado pelo botão)
async function validateByPhone() {
  if (!lastValidation.value?.registration) {
    // Se não tem busca anterior, fazer busca e validar
    await searchByPhone()
    if (lastValidation.value?.registration && !lastValidation.value.registration.validated) {
      // Validar agora
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
          registration: lastValidation.value.registration,
        }
      }
    }
    return
  }
  
  // Se já tem busca, validar
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
      registration: lastValidation.value.registration,
    }
  }
}

function formatDate(date) {
  return new Date(date).toLocaleString('pt-BR')
}

// Validar por número da inscrição
async function validateByRegistrationNumber() {
  if (!searchRegistrationNumber.value.trim()) {
    alert('Digite o número da inscrição')
    return
  }
  try {
    const { data } = await validationApi.validateByRegistrationNumber(searchRegistrationNumber.value.trim())
    foundRegistrations.value = []
    lastValidation.value = data
    if (data.valid) {
      alert('Inscrição validada com sucesso!')
      searchRegistrationNumber.value = ''
    }
  } catch (error) {
    foundRegistrations.value = []
    lastValidation.value = {
      valid: false,
      message: error.response?.data?.message || 'Erro ao validar inscrição',
      registration: error.response?.data?.registration,
    }
  }
}

// Validar uma registration específica (usado quando há múltiplas)
async function validateRegistration(reg) {
  try {
    // Validar pelo número da inscrição
    const { data } = await validationApi.validateByRegistrationNumber(reg.registration_number)
    // Atualizar a lista de registrations encontradas
    const index = foundRegistrations.value.findIndex(r => r.id === reg.id)
    if (index !== -1) {
      // Atualizar com os dados retornados, incluindo validated = true
      foundRegistrations.value[index] = { 
        ...foundRegistrations.value[index], 
        ...data.registration,
        validated: true,
        validated_at: data.registration.validated_at || new Date().toISOString(),
        validated_by: data.registration.validated_by || 'Sistema'
      }
    }
    if (data.valid) {
      alert(`Inscrição ${reg.registration_number} validada com sucesso!`)
    }
  } catch (error) {
    // Se erro 422, pode ser que já foi validada - atualizar mesmo assim
    if (error.response?.status === 422 && error.response?.data?.registration) {
      const index = foundRegistrations.value.findIndex(r => r.id === reg.id)
      if (index !== -1) {
        foundRegistrations.value[index] = { 
          ...foundRegistrations.value[index], 
          ...error.response.data.registration,
          validated: true
        }
      }
    }
    alert(error.response?.data?.message || 'Erro ao validar inscrição')
  }
}

// Função para determinar qual método de validação usar
function handleValidate() {
  if (!lastValidation.value?.registration) return
  
  const reg = lastValidation.value.registration
  
  // Se tem QR code, validar por QR code
  if (reg.qr_code) {
    validateByQrCode()
  } 
  // Se tem nome e evento, validar por nome
  else if (reg.name && selectedEventId.value) {
    validateByName()
  }
  // Se tem telefone e evento, validar por telefone
  else if (reg.phone && selectedEventId.value) {
    validateByPhone()
  }
}
</script>

<style>
#qr-reader {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
}
</style>
