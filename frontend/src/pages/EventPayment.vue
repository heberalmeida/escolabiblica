<template>
  <div class="bg-gray-50">
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-bold flex-1 text-center sm:text-left flex items-center gap-3 text-gray-800">
          <font-awesome-icon :icon="isPix ? ['fab', 'pix'] : isBoleto ? ['fas', 'barcode'] : ['fas', 'credit-card']" class="text-green-600" />
          Pagamento via {{ methodTitle }}
        </h1>
      </div>

      <div v-if="loading" class="flex justify-center py-10">
        <div class="w-10 h-10 border-4 border-green-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="error" class="text-red-600 text-center font-medium py-6">{{ error }}</div>

      <div v-else-if="paymentInfo || paymentData" class="flex justify-center">
        <div class="w-full max-w-2xl">
          <!-- Card do Pedido -->
          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden group flex flex-col p-4 border hover:border-green-200">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-50 group-hover:bg-green-100">
                <font-awesome-icon :icon="isPix ? ['fab', 'pix'] : isBoleto ? ['fas', 'barcode'] : ['fas', 'credit-card']" class="text-green-600 text-lg" />
              </div>
              <div>
                <p class="font-bold text-lg leading-tight">Pedido</p>
                <p class="text-xs text-gray-500">{{ paymentInfo?.payment_id || paymentData?.id || 'Gratuito' }}</p>
              </div>
            </div>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
              :class="statusPillClass(paymentStatus)">
              <font-awesome-icon :icon="statusIcon(paymentStatus)" />
              {{ statusLabel(paymentStatus) }}
            </span>
          </div>

          <div class="flex flex-col gap-2 text-sm text-gray-600 mb-4">
            <div>
              <strong>Inscrições:</strong>
              <span class="font-bold text-gray-900">{{ registrations.length || paymentInfo?.registrations_count || 0 }}</span>
            </div>
            <div>
              <strong>Valor Total:</strong>
              <span class="font-bold text-gray-900 text-lg">
                {{ formatBRL((paymentInfo?.total_amount || paymentData?.amount * 100) || 0) }}
              </span>
            </div>
            <div v-if="paymentInfo?.payment_method || paymentData?.method">
              <strong>Método de Pagamento:</strong> {{ formatPaymentMethod(paymentInfo?.payment_method || paymentData?.method) }}
            </div>
            <div v-if="paymentInfo?.created_at || paymentData">
              <strong>Data do Pedido:</strong> {{ formatDateTime(paymentInfo?.created_at || new Date().toISOString()) }}
            </div>
            
            <!-- Lista resumida de inscrições -->
            <div v-if="registrations.length > 0" class="mt-2 pt-2 border-t border-gray-200">
              <p class="text-xs font-semibold text-gray-500 mb-1">Inscrições neste pedido:</p>
              <div class="space-y-1">
                <div v-for="reg in registrations" :key="reg.id" class="text-xs text-gray-600">
                  • {{ reg.name }} - {{ reg.event?.name || 'Evento' }}
                </div>
              </div>
            </div>
          </div>

          <!-- QR Code PIX ou Boleto (quando não pago) -->
          <div v-if="paymentStatus !== 'paid'" class="mb-4">
            <div v-if="isPix" class="space-y-4">
              <div v-if="paymentData?.pix?.qrCodeImage || paymentInfo?.gateway_payload?.pixQrCodeImage" class="flex justify-center">
                <img :src="`data:image/png;base64,${paymentData?.pix?.qrCodeImage || paymentInfo?.gateway_payload?.pixQrCodeImage}`" alt="QR Code PIX"
                  class="w-48 h-48 sm:w-64 sm:h-64 border rounded shadow" />
              </div>

              <div v-if="paymentData?.pix?.payload || paymentInfo?.gateway_payload?.pixQrCode">
                <label class="block text-sm font-medium mb-1">Código PIX</label>
                <div class="flex flex-col sm:flex-row gap-2">
                  <textarea class="w-full border rounded p-2 text-xs" readonly rows="4">{{ paymentData?.pix?.payload || paymentInfo?.gateway_payload?.pixQrCode }}</textarea>
                  <button @click="copyPix" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm w-full sm:w-auto">Copiar</button>
                </div>
                <p v-if="copiedPix" class="text-green-600 text-xs mt-1">Código copiado!</p>
              </div>
            </div>

            <div v-if="isBoleto" class="space-y-4">
              <div v-if="paymentData?.digitableLine || paymentInfo?.gateway_payload?.identificationField">
                <label class="block text-sm font-medium mb-1">Linha Digitável</label>
                <div class="flex flex-col sm:flex-row gap-2">
                  <input type="text" class="w-full border rounded p-2 text-sm" :value="paymentData?.digitableLine || paymentInfo?.gateway_payload?.identificationField" readonly />
                  <button @click="copyDigitableLine" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm w-full sm:w-auto">Copiar</button>
                </div>
                <p v-if="copiedBoleto" class="text-green-600 text-xs mt-1">Linha digitável copiada!</p>
              </div>

              <div v-if="paymentData?.boletoUrl || paymentInfo?.gateway_payload?.bankSlipUrl">
                <a :href="paymentData?.boletoUrl || paymentInfo?.gateway_payload?.bankSlipUrl" target="_blank" class="inline-flex items-center gap-1 text-blue-600 underline text-sm">
                  <font-awesome-icon :icon="['fas', 'external-link-alt']" />
                  Abrir boleto em nova aba
                </a>
              </div>
            </div>
          </div>

          <div class="mt-auto space-y-2">
            <div v-if="paymentStatus === 'paid'" class="space-y-2">
              <button @click="printOrder"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center justify-center gap-2">
                <font-awesome-icon :icon="['fas', 'print']" />
                Imprimir Inscrições ({{ registrations.length || paymentInfo?.registrations_count || 0 }})
              </button>
              
              <!-- Botão de Comprovante -->
              <a v-if="receiptUrl" :href="receiptUrl" target="_blank"
                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center justify-center gap-2">
                <font-awesome-icon :icon="['fas', 'receipt']" />
                Ver Comprovante
              </a>
            </div>
            <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-sm text-yellow-700">
              <div class="flex items-center gap-2">
                <font-awesome-icon :icon="['fas', 'hourglass-half']" />
                <span>Aguardando confirmação do pagamento</span>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>

      <div v-else class="text-red-600 text-center font-medium py-6">Dados de pagamento não encontrados.</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useCurrency } from '@/composables/useCurrency'
import { FontAwesomeIcon } from '@/plugins/fontawesome'
import http from '@/api/http'
import { db, ref as dbRef, onValue } from '@/firebase'
import QRCode from 'qrcode'

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
let lastUpdateToken = null

const method = computed(() => {
  return (paymentData.value?.method || paymentInfo.value?.payment_method || '').toUpperCase()
})
const isPix = computed(() => method.value === 'PIX')
const isBoleto = computed(() => method.value === 'BOLETO')
const isCard = computed(() => method.value === 'CREDIT_CARD')
const methodTitle = computed(() => (isPix.value ? 'PIX' : isBoleto.value ? 'Boleto' : isCard.value ? 'Cartão' : 'Pagamento'))

const paymentStatus = computed(() => {
  // Priorizar payment_status do paymentInfo
  if (paymentInfo.value?.payment_status) {
    const status = paymentInfo.value.payment_status
    if (status === 'paid') {
      console.log('[EventPayment] paymentStatus computed: paid (via payment_status)')
      return 'paid'
    }
    if (status === 'pending') return 'pending'
    if (status === 'canceled') return 'canceled'
    if (status === 'overdue') return 'overdue'
  }
  
  // Verificar no gateway_payload do paymentInfo
  const gatewayStatus = paymentInfo.value?.gateway_payload?.status
  if (gatewayStatus === 'CONFIRMED' || gatewayStatus === 'RECEIVED') {
    console.log('[EventPayment] paymentStatus computed: paid (via gateway_status)', gatewayStatus)
    return 'paid'
  }
  if (gatewayStatus === 'PENDING') return 'pending'
  if (gatewayStatus === 'CANCELLED' || gatewayStatus === 'DELETED') return 'canceled'
  if (gatewayStatus === 'OVERDUE') return 'overdue'
  
  // Fallback: verificar no paymentData (apenas se não tiver paymentInfo)
  if (!paymentInfo.value && paymentData.value) {
    if (paymentData.value.creditCard?.status === 'CONFIRMED') return 'paid'
    return 'pending'
  }
  
  return 'pending'
})

const isPaidStatus = computed(() => paymentStatus.value === 'paid')

const receiptUrl = computed(() => {
  return paymentInfo.value?.gateway_payload?.transactionReceiptUrl || 
         paymentInfo.value?.gateway_payload?.receiptUrl ||
         null
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

function statusPillClass(status) {
  switch (status) {
    case 'paid': return 'bg-green-100 text-green-700'
    case 'pending': return 'bg-yellow-100 text-yellow-700'
    case 'canceled': return 'bg-red-100 text-red-700'
    case 'overdue': return 'bg-orange-100 text-orange-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusIcon(status) {
  switch (status) {
    case 'paid': return ['fas', 'check-circle']
    case 'pending': return ['fas', 'hourglass-half']
    case 'canceled': return ['fas', 'times-circle']
    case 'overdue': return ['fas', 'exclamation-triangle']
    default: return ['fas', 'hourglass-half']
  }
}

function formatPaymentMethod(method) {
  const methods = {
    'PIX': 'PIX',
    'BOLETO': 'Boleto',
    'CREDIT_CARD': 'Cartão de Crédito',
    'FREE': 'Gratuito'
  }
  return methods[method?.toUpperCase()] || method || 'Não informado'
}

function formatDateTime(dateString) {
  if (!dateString) return 'Não informado'
  try {
    const date = new Date(dateString)
    return date.toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (e) {
    return dateString
  }
}

function loadPaymentData() {
  try {
    const paymentParam = route.query.payment
    if (paymentParam) {
      paymentData.value = JSON.parse(decodeURIComponent(paymentParam))
      console.log('[EventPayment] Payment data carregado:', { 
        id: paymentData.value?.id,
        method: paymentData.value?.method,
        amount: paymentData.value?.amount
      })
      
      // Configurar listener do Firebase imediatamente com o payment_id do paymentData
      if (paymentData.value?.id) {
        console.log('[EventPayment] Configurando listener Firebase para:', paymentData.value.id)
        setupFirebaseListener(paymentData.value.id)
      }
      
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
  const match = url.match(/\/([a-zA-Z0-9]+)$/)
  return match ? match[1] : null
}

function extractPaymentIdFromPixPayload(payload) {
  if (!payload) return null
  const match = payload.match(/qr\/cobv\/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i)
  if (match) return match[1]
  const uuidMatch = payload.match(/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i)
  if (uuidMatch) return uuidMatch[1]
  return null
}

async function fetchRegistrations() {
  if (isFetching) return
  
  try {
    isFetching = true
    let paymentId = null
    
    if (paymentData.value?.id) {
      paymentId = paymentData.value.id
    }
    
    if (!paymentId && paymentData.value?.boletoUrl) {
      paymentId = extractPaymentIdFromUrl(paymentData.value.boletoUrl)
    }
    
    if (!paymentId && paymentData.value?.pix?.payload) {
      paymentId = extractPaymentIdFromPixPayload(paymentData.value.pix.payload)
    }
    
    if (paymentId) {
      try {
        let cleanPaymentId = paymentId
        const uuidMatch = paymentId.match(/^([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i)
        if (uuidMatch) {
          cleanPaymentId = uuidMatch[1]
        } else {
          cleanPaymentId = paymentId.trim()
        }
        
        if (!cleanPaymentId || cleanPaymentId.length < 3) {
          throw new Error('Payment ID inválido')
        }
        
        const { data } = await http.get(`/registrations/by-payment/${encodeURIComponent(cleanPaymentId)}`)
        console.log('[EventPayment] Dados recebidos do backend:', { 
          payment_status: data.payment_status,
          gateway_status: data.gateway_payload?.status,
          payment_id: data.payment_id,
          registrations_count: data.registrations?.length || 0
        })
        
        // Atualizar paymentInfo de forma reativa (usar Object.assign para garantir reatividade)
        const updatedData = Object.assign({}, data)
        
        // Se gateway_payload.status for CONFIRMED ou RECEIVED, garantir que payment_status seja 'paid'
        if (updatedData.gateway_payload?.status === 'CONFIRMED' || updatedData.gateway_payload?.status === 'RECEIVED') {
          updatedData.payment_status = 'paid'
          console.log('[EventPayment] payment_status atualizado para "paid" devido ao gateway_status:', updatedData.gateway_payload.status)
        }
        
        paymentInfo.value = updatedData
        registrations.value = [...(data.registrations || [])]
        
        console.log('[EventPayment] ===== DADOS ATUALIZADOS DO BACKEND =====')
        console.log('[EventPayment] payment_status:', paymentInfo.value.payment_status)
        console.log('[EventPayment] gateway_payload?.status:', paymentInfo.value.gateway_payload?.status)
        console.log('[EventPayment] paymentStatus computed:', paymentStatus.value)
        console.log('[EventPayment] isPaidStatus:', isPaidStatus.value)
        
        // Sempre reconfigurar listener para garantir que está ativo
        setupFirebaseListener(cleanPaymentId)
        
        loading.value = false
        isFetching = false
        return
      } catch (err) {
        console.error('Erro ao buscar por payment_id:', err)
        
        if (paymentData.value?.pix?.payload) {
          try {
            const { data } = await http.post('/registrations/by-pix-payload', {
              payload: paymentData.value.pix.payload
            })
            // Atualizar paymentInfo de forma reativa (usar Object.assign para garantir reatividade)
            const updatedData = Object.assign({}, data)
            
            // Se gateway_payload.status for CONFIRMED ou RECEIVED, garantir que payment_status seja 'paid'
            if (updatedData.gateway_payload?.status === 'CONFIRMED' || updatedData.gateway_payload?.status === 'RECEIVED') {
              updatedData.payment_status = 'paid'
              console.log('[EventPayment] payment_status atualizado para "paid" devido ao gateway_status:', updatedData.gateway_payload.status)
            }
            
            paymentInfo.value = updatedData
            registrations.value = [...(data.registrations || [])]
            
            console.log('[EventPayment] ===== DADOS ATUALIZADOS (via PIX payload) =====')
            console.log('[EventPayment] payment_status:', paymentInfo.value.payment_status)
            console.log('[EventPayment] gateway_status:', paymentInfo.value.gateway_payload?.status)
            
            if (data.payment_id) {
              // Sempre reconfigurar listener
              setupFirebaseListener(data.payment_id)
            }
            loading.value = false
            isFetching = false
            return
          } catch (pixErr) {
            console.warn('Erro ao buscar por PIX payload:', pixErr)
          }
        }
      }
    }
    
    try {
      const { data } = await http.get('/registrations', {
        params: { per_page: 100, group_by_payment: true }
      })
      
      if (data.data && Array.isArray(data.data)) {
        const sortedOrders = [...data.data].sort((a, b) => {
          const dateA = new Date(a.created_at || 0)
          const dateB = new Date(b.created_at || 0)
          return dateB - dateA
        })
        
        const found = sortedOrders.find(order => {
          if (paymentData.value?.digitableLine) {
            return order.gateway_payload?.identificationField === paymentData.value.digitableLine
          }
          if (paymentData.value?.pix?.payload) {
            const orderPixPayload = order.gateway_payload?.pixQrCode || order.gateway_payload?.pix?.payload
            if (orderPixPayload === paymentData.value.pix.payload) {
              return true
            }
            const pixUuid = extractPaymentIdFromPixPayload(paymentData.value.pix.payload)
            if (pixUuid) {
              if (order.payment_id && (order.payment_id.includes(pixUuid) || pixUuid.includes(order.payment_id))) {
                return true
              }
              if (order.registrations && order.registrations.some(reg => {
                const regPaymentId = reg.asaas_payment_id || reg.payment_id
                return regPaymentId && (regPaymentId.includes(pixUuid) || pixUuid.includes(regPaymentId))
              })) {
                return true
              }
            }
          }
          if (paymentData.value?.amount) {
            const orderAmount = order.total_amount / 100
            const paymentAmount = paymentData.value.amount
            return Math.abs(orderAmount - paymentAmount) < 0.02 && 
                   order.payment_method === paymentData.value.method
          }
          return false
        })
        
        if (found) {
          const foundPaymentId = found.payment_id || found.id
          
          // Sempre buscar detalhes completos para garantir dados atualizados
          if (foundPaymentId && foundPaymentId.length >= 8) {
            try {
              const { data: paymentDetails } = await http.get(`/registrations/by-payment/${foundPaymentId}`)
              console.log('[EventPayment] Detalhes do pagamento encontrados:', {
                payment_status: paymentDetails.payment_status,
                gateway_status: paymentDetails.gateway_payload?.status
              })
              
              // Atualizar paymentInfo de forma reativa (usar Object.assign para garantir reatividade)
              const updatedDetails = Object.assign({}, paymentDetails)
              
              // Se gateway_payload.status for CONFIRMED ou RECEIVED, garantir que payment_status seja 'paid'
              if (updatedDetails.gateway_payload?.status === 'CONFIRMED' || updatedDetails.gateway_payload?.status === 'RECEIVED') {
                updatedDetails.payment_status = 'paid'
                console.log('[EventPayment] payment_status atualizado para "paid" devido ao gateway_status:', updatedDetails.gateway_payload.status)
              }
              
              paymentInfo.value = updatedDetails
              registrations.value = [...(paymentDetails.registrations || [])]
              
              console.log('[EventPayment] ===== DADOS ATUALIZADOS (via busca) =====')
              console.log('[EventPayment] payment_status:', paymentInfo.value.payment_status)
              console.log('[EventPayment] gateway_status:', paymentInfo.value.gateway_payload?.status)
              console.log('[EventPayment] paymentStatus computed:', paymentStatus.value)
              
              // Sempre reconfigurar listener para garantir que está ativo
              setupFirebaseListener(foundPaymentId)
            } catch (err) {
              console.warn('Erro ao buscar detalhes do pagamento:', err)
              // Fallback: usar dados encontrados mesmo sem detalhes
              paymentInfo.value = Object.assign({}, found)
              registrations.value = [...(found.registrations || [])]
              if (foundPaymentId) {
                // Sempre reconfigurar listener
                setupFirebaseListener(foundPaymentId)
              }
            }
          } else {
            paymentInfo.value = Object.assign({}, found)
            registrations.value = [...(found.registrations || [])]
            if (foundPaymentId) {
              // Sempre reconfigurar listener
              setupFirebaseListener(foundPaymentId)
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
    
    if (!paymentInfo.value || !paymentInfo.value.payment_id) {
      paymentInfo.value = Object.assign({}, {
        id: paymentData.value?.id || null,
        payment_id: paymentData.value?.id || null,
        payment_method: paymentData.value?.method || 'PIX',
        payment_status: paymentData.value?.creditCard?.status === 'CONFIRMED' ? 'paid' : 'pending',
        total_amount: paymentData.value?.amount ? Math.round(paymentData.value.amount * 100) : 0,
        total_amount_formatted: paymentData.value?.amount ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(paymentData.value.amount) : 'R$ 0,00',
        gateway_payload: paymentData.value,
        registrations: [],
        registrations_count: 0,
      })
      
      // Sempre configurar listener do Firebase se tiver payment_id (para PIX, Boleto e Cartão)
      const paymentIdToUse = paymentData.value?.id || paymentInfo.value?.payment_id
      if (paymentIdToUse) {
        console.log('[EventPayment] Configurando listener Firebase (fallback) para todos os tipos de pagamento:', paymentIdToUse)
        setupFirebaseListener(paymentIdToUse)
      } else {
        console.warn('[EventPayment] Nenhum payment_id disponível para listener Firebase')
      }
      
      // Tentar buscar novamente após alguns segundos se não encontrou (para PIX, Boleto e Cartão)
      if (!paymentId) {
        setTimeout(async () => {
          if (!isFetching) {
            console.log('[EventPayment] Tentando buscar novamente após timeout...')
            await fetchRegistrations()
          }
        }, 3000)
      }
    } else {
      // Se já tem paymentInfo, garantir que o listener está configurado
      const existingPaymentId = paymentInfo.value.payment_id || paymentInfo.value.id
      if (existingPaymentId && !unsubscribe) {
        console.log('[EventPayment] Reconfigurando listener Firebase com paymentInfo existente:', existingPaymentId)
        setupFirebaseListener(existingPaymentId)
      }
    }
    
    loading.value = false
  } catch (err) {
    console.error('Erro ao buscar registrations:', err)
    if (!paymentInfo.value || !paymentInfo.value.payment_id) {
      paymentInfo.value = Object.assign({}, {
        id: paymentData.value?.id || null,
        payment_id: paymentData.value?.id || null,
        payment_method: paymentData.value?.method || 'PIX',
        payment_status: 'pending',
        total_amount: paymentData.value?.amount ? Math.round(paymentData.value.amount * 100) : 0,
        total_amount_formatted: paymentData.value?.amount ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(paymentData.value.amount) : 'R$ 0,00',
        gateway_payload: paymentData.value,
        registrations: [],
        registrations_count: 0,
      })
    }
  } finally {
    isFetching = false
    loading.value = false
  }
}
  
function setupFirebaseListener(paymentId) {
  if (!paymentId) {
    // Tentar usar payment_id do paymentData se disponível
    paymentId = paymentData.value?.id || paymentInfo.value?.payment_id
    if (!paymentId) {
      console.warn('[EventPayment] Nenhum payment_id disponível para configurar listener')
      return
    }
  }
  
  // Limpar listener anterior se existir
  if (unsubscribe) {
    console.log('[EventPayment] Removendo listener anterior antes de configurar novo')
    unsubscribe()
    unsubscribe = null
  }
  
  console.log('[EventPayment] ===== CONFIGURANDO LISTENER FIREBASE =====')
  console.log('[EventPayment] Payment ID:', paymentId)
  const prefix = import.meta.env.VITE_FIREBASE_COLLECTION_PREFIX || ''
  const firebasePath = `updates/${prefix}registrations_by_payment_${paymentId}`
  console.log('[EventPayment] Firebase path completo:', firebasePath)
  const paymentRef = dbRef(db, firebasePath)
  let isFirstSnapshot = true
  
  unsubscribe = onValue(paymentRef, async (snapshot) => {
    console.log('[EventPayment] ===== FIREBASE SNAPSHOT RECEBIDO =====')
    console.log('[EventPayment] Existe?', snapshot.exists())
    console.log('[EventPayment] isFirstSnapshot?', isFirstSnapshot)
    
    if (!snapshot.exists()) {
      console.log('[EventPayment] Firebase: Snapshot não existe')
      return
    }
    
    const payload = snapshot.val()
    console.log('[EventPayment] Payload completo:', JSON.stringify(payload, null, 2))
    console.log('[EventPayment] payment_status:', payload.payment_status)
    console.log('[EventPayment] gateway_payload?.status:', payload.gateway_payload?.status)
    
    // Sempre ignorar o primeiro snapshot (igual ao RegistrationsByCpf)
    if (isFirstSnapshot) {
      console.log('[EventPayment] Firebase: Ignorando primeiro snapshot (valor inicial)')
      isFirstSnapshot = false
      return
    }
    
    const token = payload.last_updated || payload.updated_at || JSON.stringify(payload)
    
    // Evitar atualizações duplicadas usando token global
    if (token && token === lastUpdateToken) {
      console.log('[EventPayment] Firebase: Token duplicado, ignorando atualização')
      return
    }
    lastUpdateToken = token
    
    console.log('[EventPayment] ===== FIREBASE: ATUALIZAÇÃO DETECTADA! =====')
    console.log('[EventPayment] payment_status:', payload.payment_status)
    console.log('[EventPayment] gateway_status:', payload.gateway_payload?.status)
    console.log('[EventPayment] Token:', token)
    
    // Recarregar dados quando houver atualização (igual ao RegistrationsByCpf)
    if (!isFetching) {
      console.log('[EventPayment] Firebase: Iniciando recarregamento de dados...')
      isFetching = true
      try {
        // Primeiro, tentar atualizar diretamente do payload do Firebase se tiver dados suficientes
        if (payload.payment_status || payload.gateway_payload?.status) {
          console.log('[EventPayment] Firebase: Atualizando paymentInfo diretamente do payload')
          
          // Criar novo objeto para garantir reatividade
          const updatedInfo = Object.assign({}, paymentInfo.value || {})
          
          if (payload.payment_status) {
            updatedInfo.payment_status = payload.payment_status
            console.log('[EventPayment] Firebase: payment_status atualizado para:', payload.payment_status)
          }
          
          if (payload.gateway_payload) {
            // Mesclar gateway_payload mantendo dados existentes
            updatedInfo.gateway_payload = Object.assign({}, updatedInfo.gateway_payload || {}, payload.gateway_payload)
            console.log('[EventPayment] Firebase: gateway_payload atualizado, status:', payload.gateway_payload.status)
          }
          
          // Se gateway_payload.status for CONFIRMED ou RECEIVED, garantir que payment_status seja 'paid'
          if (updatedInfo.gateway_payload?.status === 'CONFIRMED' || updatedInfo.gateway_payload?.status === 'RECEIVED') {
            updatedInfo.payment_status = 'paid'
            console.log('[EventPayment] Firebase: payment_status forçado para "paid" devido ao gateway_status CONFIRMED/RECEIVED')
          }
          
          // Atualizar paymentInfo com nova referência
          paymentInfo.value = updatedInfo
          
          console.log('[EventPayment] Firebase: paymentInfo atualizado do payload', {
            payment_status: paymentInfo.value.payment_status,
            gateway_status: paymentInfo.value.gateway_payload?.status,
            paymentStatus_computed: paymentStatus.value
          })
        }
        
        // Sempre buscar dados atualizados do backend para garantir consistência
        const currentPaymentId = paymentInfo.value?.payment_id || paymentData.value?.id || paymentId
        if (currentPaymentId) {
          console.log('[EventPayment] Firebase: Buscando dados atualizados do backend para payment_id:', currentPaymentId)
          try {
            const { data } = await http.get(`/registrations/by-payment/${encodeURIComponent(currentPaymentId)}`)
            
            // Atualizar paymentInfo de forma reativa (forçar nova referência)
            const updatedData = Object.assign({}, data)
            
            // Se gateway_payload.status for CONFIRMED ou RECEIVED, garantir que payment_status seja 'paid'
            if (updatedData.gateway_payload?.status === 'CONFIRMED' || updatedData.gateway_payload?.status === 'RECEIVED') {
              updatedData.payment_status = 'paid'
              console.log('[EventPayment] Firebase: payment_status atualizado para "paid" devido ao gateway_status:', updatedData.gateway_payload.status)
            }
            
            paymentInfo.value = updatedData
            registrations.value = [...(data.registrations || [])]
            
            console.log('[EventPayment] Firebase: Dados atualizados do backend!', {
              payment_status: paymentInfo.value.payment_status,
              gateway_status: paymentInfo.value.gateway_payload?.status,
              paymentStatus_computed: paymentStatus.value
            })
          } catch (backendErr) {
            console.warn('[EventPayment] Firebase: Erro ao buscar do backend, usando dados do payload', backendErr)
          }
        } else {
          // Fallback: usar fetchRegistrations completo
          await fetchRegistrations()
        }
        
        console.log('[EventPayment] Firebase: Dados recarregados! paymentStatus agora é:', paymentStatus.value)
      } catch (err) {
        console.error('[EventPayment] Firebase: Erro ao recarregar dados', err)
      } finally {
        isFetching = false
      }
    } else {
      console.log('[EventPayment] Firebase: Já está buscando, ignorando atualização')
    }
  }, (error) => {
    console.error('[EventPayment] ===== ERRO NO LISTENER FIREBASE =====', error)
  })
  
  console.log('[EventPayment] ===== LISTENER FIREBASE CONFIGURADO COM SUCESSO =====')
  console.log('[EventPayment] Payment ID:', paymentId)
  console.log('[EventPayment] Path:', firebasePath)
  
  // Também escutar por telefone como fallback (se tivermos registrations)
  const phoneToUse = registrations.value.length > 0 && registrations.value[0].phone
    ? registrations.value[0].phone.replace(/\D/g, '')
    : paymentInfo.value?.gateway_payload?.customer?.phone?.replace(/\D/g, '') || null
  
  if (phoneToUse) {
    console.log('[EventPayment] Configurando listener Firebase por telefone:', phoneToUse)
    const phoneRef = dbRef(db, `updates/${prefix}registrations_by_phone_${phoneToUse}`)
    
    let isFirstPhoneSnapshot = true
    const phoneUnsubscribe = onValue(phoneRef, async (snapshot) => {
      if (!snapshot.exists()) return
      
      // Sempre ignorar o primeiro snapshot
      if (isFirstPhoneSnapshot) {
        console.log('[EventPayment] Firebase (phone): Ignorando primeiro snapshot')
        isFirstPhoneSnapshot = false
        return
      }
      
      const payload = snapshot.val()
      const token = payload.last_updated || payload.updated_at || JSON.stringify(payload)
      
      // Evitar atualizações duplicadas usando token global
      if (token && token === lastUpdateToken) {
        console.log('[EventPayment] Firebase (phone): Token duplicado, ignorando')
        return
      }
      lastUpdateToken = token
      
      console.log('[EventPayment] Firebase (phone): Atualização recebida', { 
        payment_status: payload.payment_status, 
        status: payload.status,
        gateway_status: payload.gateway_payload?.status,
        token 
      })
      
      // Recarregar dados quando houver atualização (mesma lógica do listener principal)
      if (!isFetching) {
        console.log('[EventPayment] Firebase (phone): Iniciando recarregamento de dados...')
        isFetching = true
        try {
          // Primeiro, tentar atualizar diretamente do payload do Firebase
          if (payload.payment_status || payload.gateway_payload?.status) {
            console.log('[EventPayment] Firebase (phone): Atualizando paymentInfo diretamente do payload')
            
            const updatedInfo = Object.assign({}, paymentInfo.value || {})
            
            if (payload.payment_status) {
              updatedInfo.payment_status = payload.payment_status
            }
            
            if (payload.gateway_payload) {
              updatedInfo.gateway_payload = Object.assign({}, updatedInfo.gateway_payload || {}, payload.gateway_payload)
            }
            
            // Se gateway_payload.status for CONFIRMED ou RECEIVED, garantir que payment_status seja 'paid'
            if (updatedInfo.gateway_payload?.status === 'CONFIRMED' || updatedInfo.gateway_payload?.status === 'RECEIVED') {
              updatedInfo.payment_status = 'paid'
              console.log('[EventPayment] Firebase (phone): payment_status forçado para "paid" devido ao gateway_status CONFIRMED/RECEIVED')
            }
            
            paymentInfo.value = updatedInfo
          }
          
          // Sempre buscar dados atualizados do backend
          const currentPaymentId = paymentInfo.value?.payment_id || paymentData.value?.id
          if (currentPaymentId) {
            try {
              const { data } = await http.get(`/registrations/by-payment/${encodeURIComponent(currentPaymentId)}`)
              paymentInfo.value = Object.assign({}, data)
              registrations.value = [...(data.registrations || [])]
            } catch (backendErr) {
              console.warn('[EventPayment] Firebase (phone): Erro ao buscar do backend', backendErr)
              await fetchRegistrations()
            }
          } else {
            await fetchRegistrations()
          }
        } finally {
          isFetching = false
        }
      } else {
        console.log('[EventPayment] Firebase (phone): Já está buscando, ignorando atualização')
      }
    }, (error) => {
      console.error('[EventPayment] Firebase (phone): Erro no listener', error)
    })
    
    const originalUnsubscribe = unsubscribe
    unsubscribe = () => {
      if (originalUnsubscribe) originalUnsubscribe()
      if (phoneUnsubscribe) phoneUnsubscribe()
      if (fetchTimeout) {
        clearTimeout(fetchTimeout)
        fetchTimeout = null
      }
    }
  } else {
    console.log('[EventPayment] Nenhum telefone disponível para listener')
  }
}

async function printOrder() {
  const paidRegs = registrations.value.filter(reg => reg.payment_status === 'paid' && reg.qr_code)
  
  if (paidRegs.length === 0) {
    alert('Nenhuma inscrição paga para imprimir neste pedido.')
    return
  }
  
  const qrCodeImages = {}
  for (const reg of paidRegs) {
    try {
      qrCodeImages[reg.id] = await QRCode.toDataURL(reg.qr_code, {
        width: 250,
        margin: 2,
        errorCorrectionLevel: 'M'
      })
    } catch (error) {
      console.error(`Erro ao gerar QR code para registro ${reg.id}:`, error)
      qrCodeImages[reg.id] = ''
    }
  }
  
  const printWindow = window.open('', '_blank')
  const printedDate = new Date().toLocaleString('pt-BR')
  const totalAmount = formatBRL(paymentInfo.value?.total_amount || paymentData.value?.amount * 100 || 0)
  const registrationsCount = registrations.value.length || paymentInfo.value?.registrations_count || 0
  
  const styleClose = '</style>'
  const headClose = '</head>'
  const bodyClose = '</body>'
  const htmlClose = '</html>'
  
  let htmlContent = [
    '<!DOCTYPE html>',
    '<html>',
    '<head>',
    '<title>Pedido - ' + (paymentInfo.value?.payment_id || paymentData.value?.id || 'Gratuito') + '</title>',
    '<style>',
    '@media print { @page { size: A4; margin: 15mm; } .registration-card { page-break-inside: avoid; margin-bottom: 30px; } }',
    'body { font-family: Arial, sans-serif; padding: 20px; }',
    '.header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }',
    '.registration-card { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; }',
    '.qr-code { text-align: center; margin: 20px 0; }',
    '.qr-code img { max-width: 250px; height: auto; }',
    '.info { margin: 10px 0; }',
    '.info strong { display: inline-block; width: 150px; }',
    styleClose,
    headClose,
    '<body>',
    '<div class="header">',
    '<h1>Comprovante de Inscrições</h1>',
    '<p>Data de impressão: ' + printedDate + '</p>',
    '<p>Total: ' + totalAmount + ' | Inscrições: ' + registrationsCount + '</p>',
    '</div>'
  ]
  
  paidRegs.forEach((reg, index) => {
    htmlContent.push(
      '<div class="registration-card">',
      '<h2>Inscrição #' + (index + 1) + '</h2>',
      '<div class="info"><strong>Número:</strong> ' + (reg.registration_number || reg.id) + '</div>',
      '<div class="info"><strong>Nome:</strong> ' + reg.name + '</div>',
      '<div class="info"><strong>Evento:</strong> ' + (reg.event?.name || 'Evento') + '</div>',
      '<div class="info"><strong>CPF:</strong> ' + (reg.cpf || 'Não informado') + '</div>',
      '<div class="info"><strong>Telefone:</strong> ' + (reg.phone || 'Não informado') + '</div>',
      '<div class="qr-code">',
      '<p><strong>QR Code:</strong></p>',
      qrCodeImages[reg.id] ? '<img src="' + qrCodeImages[reg.id] + '" alt="QR Code" />' : '<p>QR Code não disponível</p>',
      '</div>',
      '</div>'
    )
  })
  
  htmlContent.push(bodyClose, htmlClose)
  
  printWindow.document.write(htmlContent.join(''))
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => {
    printWindow.print()
  }, 250)
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

// Watch para garantir que o listener seja configurado quando paymentInfo mudar
watch(() => paymentInfo.value?.payment_id || paymentInfo.value?.id, (newPaymentId, oldPaymentId) => {
  if (newPaymentId && newPaymentId !== oldPaymentId) {
    console.log('[EventPayment] Watch: payment_id mudou, reconfigurando listener:', { old: oldPaymentId, new: newPaymentId })
    setupFirebaseListener(newPaymentId)
  }
}, { immediate: false })

onMounted(() => {
  loadPaymentData()
})

onBeforeUnmount(() => {
  if (unsubscribe) {
    console.log('[EventPayment] Limpando listener Firebase ao desmontar')
    unsubscribe()
    unsubscribe = null
  }
  if (fetchTimeout) {
    clearTimeout(fetchTimeout)
    fetchTimeout = null
  }
  isFetching = false
})
</script>
