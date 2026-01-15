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
              <span :class="{
                'text-green-600 font-bold': paymentStatus === 'paid',
                'text-yellow-600 font-bold': paymentStatus === 'pending',
                'text-red-600 font-bold': paymentStatus === 'canceled',
                'text-orange-600 font-bold': paymentStatus === 'overdue'
              }">
                <font-awesome-icon :icon="paymentStatus === 'paid' ? ['fas', 'check-circle']
                  : paymentStatus === 'pending' ? ['fas', 'hourglass-half']
                  : paymentStatus === 'canceled' ? ['fas', 'times-circle']
                  : paymentStatus === 'overdue' ? ['fas', 'exclamation-triangle']
                  : ['fas', 'hourglass-half']" class="mr-1"/>
                {{ statusLabel(paymentStatus) }}
              </span>
            </p>
            <div v-if="paymentStatus === 'paid'" class="text-green-600 font-bold mt-2">
              Pagamento confirmado! Suas inscrições estão disponíveis.
            </div>
            <div v-else class="text-yellow-600 font-bold mt-2">
              Complete o pagamento para visualizar seus ingressos.
            </div>
          </div>
          
          <div v-if="registrations.length > 0" class="mt-4 border-t pt-4">
            <p class="text-sm font-semibold mb-2">Inscrições ({{ registrations.length }}):</p>
            <div class="space-y-2">
              <div v-for="reg in registrations" :key="reg.id" class="text-sm text-gray-700 bg-gray-50 p-2 rounded">
                <p><strong>{{ reg.name }}</strong> - {{ reg.event?.name || 'Evento' }}</p>
                <p class="text-xs text-gray-500">Inscrição #{{ reg.registration_number }}</p>
              </div>
            </div>
          </div>

          <div v-if="isPix && paymentStatus !== 'paid'" class="space-y-4">
            <div v-if="paymentData.pix?.qrCodeImage || paymentInfo?.gateway_payload?.pixQrCodeImage" class="flex justify-center">
              <img :src="`data:image/png;base64,${paymentData.pix?.qrCodeImage || paymentInfo?.gateway_payload?.pixQrCodeImage}`" alt="QR Code PIX"
                class="w-48 h-48 sm:w-64 sm:h-64 border rounded shadow" />
            </div>

            <div v-if="paymentData.pix?.payload || paymentInfo?.gateway_payload?.pixQrCode">
              <label class="block text-sm font-medium mb-1">Código PIX</label>
              <div class="flex flex-col sm:flex-row gap-2">
                <textarea class="w-full border rounded p-2 text-xs" readonly rows="4">{{ paymentData.pix?.payload || paymentInfo?.gateway_payload?.pixQrCode }}</textarea>
                <button @click="copyPix" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm w-full sm:w-auto">Copiar</button>
              </div>
              <p v-if="copiedPix" class="text-green-600 text-xs mt-1">Código copiado!</p>
            </div>
          </div>

          <div v-if="isBoleto && paymentStatus !== 'paid'" class="space-y-4">
            <div v-if="paymentData.digitableLine || paymentInfo?.gateway_payload?.identificationField">
              <label class="block text-sm font-medium mb-1">Linha Digitável</label>
              <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" class="w-full border rounded p-2 text-sm" :value="paymentData.digitableLine || paymentInfo?.gateway_payload?.identificationField" readonly />
                <button @click="copyDigitableLine" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm w-full sm:w-auto">Copiar</button>
              </div>
              <p v-if="copiedBoleto" class="text-green-600 text-xs mt-1">Linha digitável copiada!</p>
            </div>

            <div v-if="!paymentData.boletoUrl && !paymentInfo?.gateway_payload?.bankSlipUrl" class="text-yellow-600 text-sm">
              O boleto será gerado em breve. Verifique suas inscrições em alguns minutos.
            </div>

            <iframe v-if="paymentData.boletoUrl || paymentInfo?.gateway_payload?.bankSlipUrl"
              :src="paymentData.boletoUrl || paymentInfo?.gateway_payload?.bankSlipUrl" class="w-full h-[500px] sm:h-[600px] border rounded"></iframe>

            <a v-if="paymentData.boletoUrl || paymentInfo?.gateway_payload?.bankSlipUrl"
              :href="paymentData.boletoUrl || paymentInfo?.gateway_payload?.bankSlipUrl" target="_blank" class="inline-flex items-center gap-1 text-blue-600 underline">
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useCurrency } from '@/composables/useCurrency'
import { FontAwesomeIcon } from '@/plugins/fontawesome'
import http from '@/api/http'
import { db, ref as dbRef, onValue } from '@/firebase'

const route = useRoute()
const { formatBRL } = useCurrency()

const paymentData = ref(null)
const registrations = ref([])
const paymentInfo = ref(null)
const loading = ref(true)
const error = ref(null)
const copiedPix = ref(false)
const copiedBoleto = ref(false)

const method = computed(() => {
  return (paymentData.value?.method || paymentInfo.value?.payment_method || '').toUpperCase()
})
const isPix = computed(() => method.value === 'PIX')
const isBoleto = computed(() => method.value === 'BOLETO')
const isCard = computed(() => method.value === 'CREDIT_CARD')
const methodTitle = computed(() => (isPix.value ? 'PIX' : isBoleto.value ? 'Boleto' : isCard.value ? 'Cartão' : 'Pagamento'))

const paymentStatus = computed(() => {
  if (paymentInfo.value?.payment_status) {
    const status = paymentInfo.value.payment_status
    if (status === 'paid') return 'paid'
    if (status === 'pending') return 'pending'
    if (status === 'canceled') return 'canceled'
    if (status === 'overdue') return 'overdue'
  }
  // Verificar no gateway_payload
  const gatewayStatus = paymentInfo.value?.gateway_payload?.status
  if (gatewayStatus === 'CONFIRMED' || gatewayStatus === 'RECEIVED') return 'paid'
  if (gatewayStatus === 'PENDING') return 'pending'
  if (gatewayStatus === 'CANCELLED' || gatewayStatus === 'DELETED') return 'canceled'
  if (gatewayStatus === 'OVERDUE') return 'overdue'
  return 'pending'
})

function statusLabel(status) {
  switch (status) {
    case 'pending': return 'Aguardando Pagamento'
    case 'paid': return 'Pago'
    case 'canceled': return 'Cancelado'
    case 'overdue': return 'Vencido'
    default: return 'Aguardando Pagamento'
  }
}

function loadPaymentData() {
  try {
    const paymentParam = route.query.payment
    if (paymentParam) {
      paymentData.value = JSON.parse(decodeURIComponent(paymentParam))
      // Tentar extrair payment_id do gateway_payload se disponível
      // Se não, precisamos buscar pelas registrations criadas recentemente
      fetchRegistrations()
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

function extractPaymentIdFromUrl(url) {
  if (!url) return null
  // Extrair payment_id do boletoUrl: https://sandbox.asaas.com/b/pdf/e2o3a44u2ktols55
  const match = url.match(/\/([a-zA-Z0-9]+)$/)
  return match ? match[1] : null
}

async function fetchRegistrations() {
  try {
    let paymentId = null
    
    // Primeiro, tentar usar payment.id se disponível
    if (paymentData.value?.id) {
      paymentId = paymentData.value.id
    }
    
    // Se não, tentar extrair payment_id do boletoUrl
    if (!paymentId && paymentData.value?.boletoUrl) {
      paymentId = extractPaymentIdFromUrl(paymentData.value.boletoUrl)
    }
    
    // Se temos payment_id, buscar diretamente
    if (paymentId) {
      try {
        const { data } = await http.get(`/registrations/by-payment/${paymentId}`)
        paymentInfo.value = data
        registrations.value = data.registrations || []
        setupFirebaseListener(paymentId)
        loading.value = false
        return
      } catch (err) {
        console.error('Erro ao buscar por payment_id:', err)
        // Continuar tentando outras formas
      }
    }
    
    // Se não encontrou, tentar buscar pelas registrations e encontrar pelo digitableLine ou pix
    try {
      const { data } = await http.get('/registrations', {
        params: { per_page: 100, group_by_payment: true }
      })
      
      if (data.data && Array.isArray(data.data)) {
        const found = data.data.find(order => {
          // Se temos digitableLine, procurar por ela
          if (paymentData.value?.digitableLine) {
            return order.gateway_payload?.identificationField === paymentData.value.digitableLine
          }
          // Se temos pix payload, procurar por ele
          if (paymentData.value?.pix?.payload) {
            return order.gateway_payload?.pixQrCode === paymentData.value.pix.payload
          }
          return false
        })
        
        if (found) {
          paymentInfo.value = found
          registrations.value = found.registrations || []
          setupFirebaseListener(found.payment_id || found.id)
          loading.value = false
          return
        }
      }
    } catch (err) {
      console.error('Erro ao buscar registrations:', err)
    }
    
    // Se não encontrou, usar os dados do paymentData
    paymentInfo.value = {
      payment_method: paymentData.value.method,
      payment_status: paymentData.value.creditCard?.status === 'CONFIRMED' ? 'paid' : 'pending',
      total_amount: paymentData.value.amount * 100,
      gateway_payload: paymentData.value
    }
  } catch (err) {
    console.error('Erro ao buscar registrations:', err)
    // Continuar com os dados do paymentData
    paymentInfo.value = {
      payment_method: paymentData.value.method,
      payment_status: 'pending',
      total_amount: paymentData.value.amount * 100,
      gateway_payload: paymentData.value
    }
  } finally {
    loading.value = false
  }
}

let unsubscribe = null

function setupFirebaseListener(paymentId) {
  if (!paymentId) return
  
  // Escutar atualizações das registrations pelo payment_id
  // O backend atualiza em webhooks/registrations_{id} para cada registration
  // E também em updates/registrations_by_phone_{phone}
  
  // Para simplificar, vamos escutar todas as registrations e verificar se alguma mudou
  // Ou podemos escutar por phone se tivermos
  if (registrations.value.length > 0 && registrations.value[0].phone) {
    const phone = registrations.value[0].phone.replace(/\D/g, '')
    const prefix = import.meta.env.VITE_FIREBASE_COLLECTION_PREFIX || ''
    const updatesRef = dbRef(db, `updates/${prefix}registrations_by_phone_${phone}`)
    
    unsubscribe = onValue(updatesRef, async (snapshot) => {
      if (snapshot.exists()) {
        // Recarregar registrations quando houver atualização
        await fetchRegistrations()
      }
    })
  }
}

async function copyPix() {
  const pixPayload = paymentData.value?.pix?.payload || paymentInfo.value?.gateway_payload?.pixQrCode
  if (pixPayload) {
    await navigator.clipboard.writeText(pixPayload)
    copiedPix.value = true
    setTimeout(() => (copiedPix.value = false), 2000)
  }
}

async function copyDigitableLine() {
  const digitableLine = paymentData.value?.digitableLine || paymentInfo.value?.gateway_payload?.identificationField
  if (digitableLine) {
    await navigator.clipboard.writeText(digitableLine)
    copiedBoleto.value = true
    setTimeout(() => (copiedBoleto.value = false), 2000)
  }
}

onMounted(() => {
  loadPaymentData()
})

onBeforeUnmount(() => {
  if (unsubscribe) unsubscribe()
})
</script>
