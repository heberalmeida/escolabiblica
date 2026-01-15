<template>
  <div class="bg-gray-50">
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="bg-white rounded-2xl shadow-sm p-6">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2 text-gray-800">
          <font-awesome-icon :icon="isPix ? ['fab', 'pix'] : isBoleto ? ['fas', 'barcode'] : ['fas', 'credit-card']" class="text-green-600" />
          Pagamento via {{ methodTitle }}
        </h1>

        <div v-if="loading" class="flex justify-center items-center py-16">
          <div class="w-12 h-12 border-4 border-green-600 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else-if="error" class="text-red-600 text-center font-medium py-6">{{ error }}</div>

        <div v-else-if="paymentData" class="space-y-4">
          <div class="border-b pb-4">
            <p class="text-sm text-gray-600">
              Inscrições em Eventos
            </p>
            <p class="text-lg font-semibold text-gray-900 mt-1">
              Valor: {{ formatBRL(paymentData.amount * 100) }}
            </p>
          </div>

          <div class="text-lg font-bold text-green-700">
            Total: {{ formatBRL(paymentData.amount * 100) }}
          </div>

          <div>
            <p class="text-sm">
              Status:
              <span class="text-yellow-600 font-bold">
                <font-awesome-icon :icon="['fas', 'hourglass-half']" class="mr-1"/>
                Aguardando Pagamento
              </span>
            </p>
            <div class="text-yellow-600 font-bold mt-2">
              Complete o pagamento para visualizar seus ingressos.
            </div>
          </div>

          <div v-if="isPix" class="space-y-4">
            <div v-if="paymentData.pix?.qrCodeImage" class="flex justify-center">
              <img :src="`data:image/png;base64,${paymentData.pix.qrCodeImage}`" alt="QR Code PIX"
                class="w-48 h-48 sm:w-64 sm:h-64 border rounded shadow" />
            </div>

            <div v-if="paymentData.pix?.payload">
              <label class="block text-sm font-medium mb-1">Código PIX</label>
              <div class="flex flex-col sm:flex-row gap-2">
                <textarea class="w-full border rounded p-2 text-xs" readonly rows="4">{{ paymentData.pix.payload }}</textarea>
                <button @click="copyPix" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm w-full sm:w-auto">Copiar</button>
              </div>
              <p v-if="copiedPix" class="text-green-600 text-xs mt-1">Código copiado!</p>
            </div>
          </div>

          <div v-if="isBoleto" class="space-y-4">
            <div v-if="paymentData.digitableLine">
              <label class="block text-sm font-medium mb-1">Linha Digitável</label>
              <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" class="w-full border rounded p-2 text-sm" :value="paymentData.digitableLine" readonly />
                <button @click="copyDigitableLine" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm w-full sm:w-auto">Copiar</button>
              </div>
              <p v-if="copiedBoleto" class="text-green-600 text-xs mt-1">Linha digitável copiada!</p>
            </div>

            <div v-if="!paymentData.boletoUrl" class="text-yellow-600 text-sm">
              O boleto será gerado em breve. Verifique suas inscrições em alguns minutos.
            </div>

            <iframe v-if="paymentData.boletoUrl"
              :src="paymentData.boletoUrl" class="w-full h-[500px] sm:h-[600px] border rounded"></iframe>

            <a v-if="paymentData.boletoUrl"
              :href="paymentData.boletoUrl" target="_blank" class="inline-flex items-center gap-1 text-blue-600 underline">
              <font-awesome-icon :icon="['fas', 'external-link-alt']" />
              Abrir boleto em nova aba
            </a>
          </div>

          <div class="mt-6 flex flex-col sm:flex-row justify-between gap-2">
            <RouterLink to="/" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-center">Voltar</RouterLink>
            <RouterLink to="/registrations" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-center">Minhas Inscrições</RouterLink>
          </div>
        </div>

        <div v-else class="text-red-600 text-center font-medium py-6">Dados de pagamento não encontrados.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useCurrency } from '@/composables/useCurrency'
import { FontAwesomeIcon } from '@/plugins/fontawesome'

const route = useRoute()
const { formatBRL } = useCurrency()

const paymentData = ref(null)
const loading = ref(true)
const error = ref(null)
const copiedPix = ref(false)
const copiedBoleto = ref(false)

const method = computed(() => {
  return (paymentData.value?.method || '').toUpperCase()
})
const isPix = computed(() => method.value === 'PIX')
const isBoleto = computed(() => method.value === 'BOLETO')
const isCard = computed(() => method.value === 'CREDIT_CARD')
const methodTitle = computed(() => (isPix.value ? 'PIX' : isBoleto.value ? 'Boleto' : isCard.value ? 'Cartão' : 'Pagamento'))

function loadPaymentData() {
  try {
    const paymentParam = route.query.payment
    if (paymentParam) {
      paymentData.value = JSON.parse(decodeURIComponent(paymentParam))
      loading.value = false
    } else {
      error.value = 'Dados de pagamento não encontrados.'
      loading.value = false
    }
  } catch (e) {
    console.error('Erro ao carregar dados de pagamento:', e)
    error.value = 'Erro ao carregar dados de pagamento.'
    loading.value = false
  }
}

async function copyPix() {
  if (paymentData.value?.pix?.payload) {
    await navigator.clipboard.writeText(paymentData.value.pix.payload)
    copiedPix.value = true
    setTimeout(() => (copiedPix.value = false), 2000)
  }
}

async function copyDigitableLine() {
  if (paymentData.value?.digitableLine) {
    await navigator.clipboard.writeText(paymentData.value.digitableLine)
    copiedBoleto.value = true
    setTimeout(() => (copiedBoleto.value = false), 2000)
  }
}

onMounted(() => {
  loadPaymentData()
})
</script>
