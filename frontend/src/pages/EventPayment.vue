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
let unsubscribe = null
let isFetching = false
let fetchTimeout = null

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

function extractPaymentIdFromPixPayload(payload) {
  if (!payload) return null
  // O payload do PIX pode conter a URL do QR code: pix-h.asaas.com/qr/cobv/c2fc42c7-b139-47f7-8172-b83d4fb16a78
  // Tentar extrair o UUID do final (formato: 8-4-4-4-12 caracteres hexadecimais)
  const match = payload.match(/qr\/cobv\/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i)
  if (match) return match[1]
  
  // Tentar extrair qualquer UUID válido do payload (formato exato)
  const uuidMatch = payload.match(/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i)
  if (uuidMatch) return uuidMatch[1]
  
  return null
}

async function fetchRegistrations() {
  // Prevenir múltiplas chamadas simultâneas
  if (isFetching) {
    return
  }
  
  try {
    isFetching = true
    let paymentId = null
    
    // Primeiro, tentar usar payment.id se disponível (vem direto do backend)
    if (paymentData.value?.id) {
      paymentId = paymentData.value.id
    }
    
    // Se não, tentar extrair payment_id do boletoUrl
    if (!paymentId && paymentData.value?.boletoUrl) {
      paymentId = extractPaymentIdFromUrl(paymentData.value.boletoUrl)
    }
    
    // Se não, tentar extrair do payload do PIX (última opção, menos confiável)
    if (!paymentId && paymentData.value?.pix?.payload) {
      paymentId = extractPaymentIdFromPixPayload(paymentData.value.pix.payload)
    }
    
    // Se temos payment_id, buscar diretamente
    if (paymentId) {
      try {
        // Limpar o payment_id - aceitar UUID ou formato Asaas (pay_xxxxx)
        let cleanPaymentId = paymentId
        
        // Tentar extrair UUID se for formato UUID
        const uuidMatch = paymentId.match(/^([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i)
        if (uuidMatch) {
          cleanPaymentId = uuidMatch[1]
        } else {
          // Se não for UUID, aceitar formato Asaas (pay_xxxxx) ou qualquer string válida
          cleanPaymentId = paymentId.trim()
        }
        
        // Validar que o payment_id não está vazio
        if (!cleanPaymentId || cleanPaymentId.length < 3) {
          throw new Error('Payment ID inválido')
        }
        
        const { data } = await http.get(`/registrations/by-payment/${encodeURIComponent(cleanPaymentId)}`)
        paymentInfo.value = data
        registrations.value = data.registrations || []
        setupFirebaseListener(cleanPaymentId)
        loading.value = false
        isFetching = false
        return
      } catch (err) {
        console.error('Erro ao buscar por payment_id:', err)
        console.log('Payment ID tentado:', paymentId)
        
        // Se falhou e temos payload do PIX, tentar buscar diretamente pelo payload
        if (paymentData.value?.pix?.payload) {
          try {
            const { data } = await http.post('/registrations/by-pix-payload', {
              payload: paymentData.value.pix.payload
            })
            paymentInfo.value = data
            registrations.value = data.registrations || []
            if (data.payment_id) {
              setupFirebaseListener(data.payment_id)
            }
            loading.value = false
            isFetching = false
            return
          } catch (pixErr) {
            console.warn('Erro ao buscar por PIX payload:', pixErr)
            // Continuar tentando outras formas
          }
        }
      }
    }
    
    // Se não encontrou, tentar buscar pelas registrations e encontrar pelo digitableLine ou pix
    try {
      // Primeiro, tentar buscar registrations recentes (últimas 5 minutos) para encontrar pelo PIX payload
      const { data } = await http.get('/registrations', {
        params: { per_page: 100, group_by_payment: true }
      })
      
      if (data.data && Array.isArray(data.data)) {
        // Ordenar por data de criação (mais recente primeiro)
        const sortedOrders = [...data.data].sort((a, b) => {
          const dateA = new Date(a.created_at || 0)
          const dateB = new Date(b.created_at || 0)
          return dateB - dateA
        })
        
        const found = sortedOrders.find(order => {
          // Se temos digitableLine, procurar por ela
          if (paymentData.value?.digitableLine) {
            return order.gateway_payload?.identificationField === paymentData.value.digitableLine
          }
          // Se temos pix payload, procurar por ele (comparação exata)
          if (paymentData.value?.pix?.payload) {
            const orderPixPayload = order.gateway_payload?.pixQrCode || order.gateway_payload?.pix?.payload
            if (orderPixPayload === paymentData.value.pix.payload) {
              return true
            }
            // Também tentar comparar apenas o UUID do payload (mais flexível)
            const pixUuid = extractPaymentIdFromPixPayload(paymentData.value.pix.payload)
            if (pixUuid) {
              // Verificar se o payment_id contém o UUID ou vice-versa
              if (order.payment_id && (order.payment_id.includes(pixUuid) || pixUuid.includes(order.payment_id))) {
                return true
              }
              // Verificar se algum registration tem o payment_id que contém o UUID
              if (order.registrations && order.registrations.some(reg => {
                const regPaymentId = reg.asaas_payment_id || reg.payment_id
                return regPaymentId && (regPaymentId.includes(pixUuid) || pixUuid.includes(regPaymentId))
              })) {
                return true
              }
            }
          }
          // Procurar por valor e método de pagamento (última opção)
          if (paymentData.value?.amount) {
            const orderAmount = order.total_amount / 100
            const paymentAmount = paymentData.value.amount
            // Tolerância de 1 centavo para diferenças de arredondamento
            return Math.abs(orderAmount - paymentAmount) < 0.02 && 
                   order.payment_method === paymentData.value.method
          }
          return false
        })
        
        if (found) {
          paymentInfo.value = found
          registrations.value = found.registrations || []
          const foundPaymentId = found.payment_id || found.id
          if (foundPaymentId) {
            setupFirebaseListener(foundPaymentId)
            // Tentar buscar detalhes completos se tiver payment_id válido
            if (foundPaymentId && foundPaymentId.length >= 8) {
              try {
                const { data: paymentDetails } = await http.get(`/registrations/by-payment/${foundPaymentId}`)
                paymentInfo.value = paymentDetails
                registrations.value = paymentDetails.registrations || []
              } catch (err) {
                console.warn('Erro ao buscar detalhes do pagamento:', err)
                // Usar os dados já encontrados (já temos tudo que precisamos)
              }
            }
          }
          loading.value = false
          isFetching = false
          return
        }
      }
    } catch (err) {
      console.error('Erro ao buscar registrations:', err)
    } finally {
      isFetching = false
    }
    
    // Se não encontrou, usar os dados do paymentData diretamente
    // Isso permite mostrar a página mesmo sem encontrar no banco ainda
    if (!paymentInfo.value || !paymentInfo.value.payment_id) {
      paymentInfo.value = {
        id: paymentData.value?.id || null,
        payment_id: paymentData.value?.id || null,
        payment_method: paymentData.value?.method || 'PIX',
        payment_status: paymentData.value?.creditCard?.status === 'CONFIRMED' ? 'paid' : 'pending',
        total_amount: paymentData.value?.amount ? Math.round(paymentData.value.amount * 100) : 0,
        total_amount_formatted: paymentData.value?.amount ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(paymentData.value.amount) : 'R$ 0,00',
        gateway_payload: paymentData.value,
        registrations: [],
        registrations_count: 0,
      }
      
      // Tentar buscar novamente após alguns segundos caso não tenha encontrado
      // Isso é útil quando o pagamento foi feito mas ainda não foi processado
      if (!paymentId && paymentData.value?.method === 'PIX') {
        // Aguardar 3 segundos antes de tentar novamente (dar tempo para o webhook processar)
        setTimeout(async () => {
          if (!isFetching) {
            await fetchRegistrations()
          }
        }, 3000)
      }
    }
    
    loading.value = false
  } catch (err) {
    console.error('Erro ao buscar registrations:', err)
    // Continuar com os dados do paymentData
    if (!paymentInfo.value || !paymentInfo.value.payment_id) {
      paymentInfo.value = {
        id: paymentData.value?.id || null,
        payment_id: paymentData.value?.id || null,
        payment_method: paymentData.value?.method || 'PIX',
        payment_status: 'pending',
        total_amount: paymentData.value?.amount ? Math.round(paymentData.value.amount * 100) : 0,
        total_amount_formatted: paymentData.value?.amount ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(paymentData.value.amount) : 'R$ 0,00',
        gateway_payload: paymentData.value,
        registrations: [],
        registrations_count: 0,
      }
    }
  } finally {
    isFetching = false
    loading.value = false
  }
}
  
  function setupFirebaseListener(paymentId) {
  if (unsubscribe) unsubscribe()
  unsubscribe = null
  
  if (!paymentId) return
  
  const prefix = import.meta.env.VITE_FIREBASE_COLLECTION_PREFIX || ''
  
  // Escutar atualizações pelo payment_id (mais direto)
  const paymentRef = dbRef(db, `updates/${prefix}registrations_by_payment_${paymentId}`)
  let lastPaymentUpdate = null
  unsubscribe = onValue(paymentRef, async (snapshot) => {
    if (snapshot.exists()) {
      const data = snapshot.val()
      const updateToken = data.last_updated || JSON.stringify(data)
      
      // Evitar recarregar se for a mesma atualização ou se já está carregando
      if (updateToken === lastPaymentUpdate || isFetching) return
      lastPaymentUpdate = updateToken
      
      // Debounce: aguardar 1 segundo antes de recarregar para evitar múltiplas chamadas
      if (fetchTimeout) {
        clearTimeout(fetchTimeout)
      }
      fetchTimeout = setTimeout(async () => {
        if (!isFetching) {
          isFetching = true
          try {
            await fetchRegistrations()
          } finally {
            isFetching = false
          }
        }
      }, 1000)
    }
  })
  
  // Também escutar por telefone como fallback
  if (registrations.value.length > 0 && registrations.value[0].phone) {
    const phone = registrations.value[0].phone.replace(/\D/g, '')
    const phoneRef = dbRef(db, `updates/${prefix}registrations_by_phone_${phone}`)
    
    let lastPhoneUpdate = null
    const phoneUnsubscribe = onValue(phoneRef, async (snapshot) => {
      if (snapshot.exists()) {
        const data = snapshot.val()
        const updateToken = data.last_updated || JSON.stringify(data)
        
        // Evitar recarregar se for a mesma atualização ou se já está carregando
        if (updateToken === lastPhoneUpdate || isFetching) return
        lastPhoneUpdate = updateToken
        
        // Debounce: aguardar 1 segundo antes de recarregar
        if (fetchTimeout) {
          clearTimeout(fetchTimeout)
        }
        fetchTimeout = setTimeout(async () => {
          if (!isFetching) {
            isFetching = true
            try {
              await fetchRegistrations()
            } finally {
              isFetching = false
            }
          }
        }, 1000)
      }
    })
    
    // Armazenar ambos os listeners
    const originalUnsubscribe = unsubscribe
    unsubscribe = () => {
      if (originalUnsubscribe) originalUnsubscribe()
      if (phoneUnsubscribe) phoneUnsubscribe()
      if (fetchTimeout) {
        clearTimeout(fetchTimeout)
        fetchTimeout = null
      }
    }
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
  if (fetchTimeout) {
    clearTimeout(fetchTimeout)
    fetchTimeout = null
  }
  isFetching = false
})
</script>
