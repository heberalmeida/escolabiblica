<template>
  <div class="max-w-4xl mx-auto p-4 sm:p-8">
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
      <div class="mb-6">
        <font-awesome-icon :icon="['fas', 'check-circle']" class="text-6xl text-green-600 mb-4" />
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Inscrição Realizada com Sucesso!</h1>
        <p class="text-gray-600">Sua inscrição foi confirmada. Guarde seu QR Code para retirada do material.</p>
      </div>

      <!-- Mostrar apenas inscrições pagas -->
      <div v-for="(registration, index) in paidRegistrations" :key="registration.id"
        class="border rounded-lg p-6 mb-4 text-left">
        <h2 class="text-xl font-bold mb-4">Inscrição {{ index + 1 }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <strong>Nome:</strong> {{ registration.name }}
          </div>
          <div>
            <strong>Telefone:</strong> {{ registration.phone }}
          </div>
          <div>
            <strong>Número de Inscrição:</strong> {{ registration.registration_number }}
          </div>
          <div>
            <strong>Evento:</strong> {{ registration.event?.name }}
          </div>
          <div v-if="isPaid(registration)">
            <strong>Status:</strong> <span class="text-green-600 font-semibold">Pago</span>
          </div>
        </div>
        <div class="flex justify-center">
          <div class="bg-white p-4 rounded-lg border-2 border-blue-600">
            <canvas :ref="el => qrCanvases[index] = el"></canvas>
          </div>
        </div>
        <p class="text-center text-sm text-gray-600 mt-4">
          Apresente este QR Code na retirada do material
        </p>
      </div>

      <!-- Mensagem para inscrições pendentes -->
      <div v-if="pendingRegistrations.length > 0" class="border border-yellow-300 bg-yellow-50 rounded-lg p-6 mb-4">
        <font-awesome-icon :icon="['fas', 'clock']" class="text-3xl text-yellow-600 mb-2" />
        <h3 class="text-lg font-semibold text-yellow-800 mb-2">Aguardando Confirmação de Pagamento</h3>
        <p class="text-yellow-700 mb-2">
          Você tem {{ pendingRegistrations.length }} inscrição(ões) aguardando confirmação de pagamento.
        </p>
        <p class="text-sm text-yellow-600">
          Assim que o pagamento for confirmado, seus ingressos estarão disponíveis aqui ou você pode consultar pelo CPF na página "Minhas Inscrições".
        </p>
      </div>

      <div class="mt-6">
        <button v-if="paidRegistrations.length > 0" @click="downloadAll" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg mr-4">
          Baixar Todos os QR Codes
        </button>
        <button @click="router.push('/')" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg">
          Voltar ao Início
        </button>
        <button @click="router.push('/registrations')" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg ml-4">
          Minhas Inscrições
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import QRCode from 'qrcode'
import { FontAwesomeIcon } from '@/plugins/fontawesome'

const route = useRoute()
const router = useRouter()
const registrations = ref([])
const qrCanvases = ref([])

// Função auxiliar para verificar se uma inscrição está paga
function isPaid(registration) {
  // Verificar payment_status direto
  if (registration.payment_status === 'paid') {
    return true
  }
  
  // Verificar gateway_payload.status como fallback (para cartão de crédito confirmado)
  const gatewayStatus = registration.gateway_payload?.status
  if (gatewayStatus === 'CONFIRMED' || gatewayStatus === 'RECEIVED') {
    return true
  }
  
  return false
}

// Filtrar apenas inscrições pagas
const paidRegistrations = computed(() => {
  return registrations.value.filter(reg => isPaid(reg))
})

// Inscrições pendentes
const pendingRegistrations = computed(() => {
  return registrations.value.filter(reg => !isPaid(reg))
})

onMounted(async () => {
  const registrationsParam = route.query.registrations
  if (registrationsParam) {
    try {
      registrations.value = JSON.parse(decodeURIComponent(registrationsParam))
      // Só gerar QR codes para inscrições pagas
      if (paidRegistrations.value.length > 0) {
        await generateQRCodes()
      }
    } catch (error) {
      console.error('Erro ao carregar inscrições:', error)
    }
  }
})

async function generateQRCodes() {
  // Aguardar próximo tick para garantir que os refs estão disponíveis
  await new Promise(resolve => setTimeout(resolve, 100))
  
  for (let i = 0; i < paidRegistrations.value.length; i++) {
    const registration = paidRegistrations.value[i]
    const canvas = qrCanvases.value[i]
    if (canvas && registration.qr_code) {
      try {
        await QRCode.toCanvas(canvas, registration.qr_code, {
          width: 300,
          margin: 2,
        })
      } catch (error) {
        console.error('Erro ao gerar QR code:', error)
      }
    }
  }
}

async function downloadAll() {
  for (let i = 0; i < qrCanvases.value.length; i++) {
    const canvas = qrCanvases.value[i]
    if (canvas && paidRegistrations.value[i]) {
      const link = document.createElement('a')
      link.download = `qrcode-${paidRegistrations.value[i].registration_number}.png`
      link.href = canvas.toDataURL()
      link.click()
    }
  }
}
</script>
