<template>
  <div class="bg-gray-50">
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="flex justify-between items-center mb-10">
  <h1
    class="text-3xl font-bold flex-1 text-center sm:text-left flex items-center gap-3 text-gray-800"
  >
    <font-awesome-icon :icon="['fas', 'shopping-cart']" class="text-green-600" />
    Minhas Compras
  </h1>

  <button
    v-if="savedCpf || orders.length"
    @click="logout"
    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm cursor-pointer inline-flex items-center gap-2"
  >
    <font-awesome-icon :icon="['fas', 'sign-out-alt']" />
    Sair
  </button>
</div>


      <form @submit.prevent="search"
        class="flex flex-col sm:flex-row gap-4 justify-center mb-10">
        <input v-model="cpf" v-maska="'###.###.###-##'" placeholder="Digite seu CPF"
          class="w-full sm:w-72 bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition" />
        <button type="submit" :disabled="loading"
          class="bg-blue-600 text-white px-6 py-2 rounded-lg cursor-pointer hover:bg-blue-700 disabled:bg-gray-400 transition">
          {{ loading ? 'Buscando...' : 'Buscar' }}
        </button>
      </form>

      <div v-if="loading">
        <Loading />
      </div>
      <div v-else>
        <p v-if="error" class="text-red-600 mb-6 text-center">{{ error }}</p>

        <div v-if="orders.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="o in orders" :key="o.id"
            class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden group flex flex-col p-4 border hover:border-blue-200">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div
                  class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 group-hover:bg-blue-100">
                  <font-awesome-icon :icon="methodIcon(o)" class="text-blue-600 text-lg" />
                </div>
                <div>
                  <p class="font-bold text-lg leading-tight">Pedido #{{ o.order_number }}</p>
                  <p class="text-xs text-gray-500">{{ methodLabel(o) }}</p>
                </div>
              </div>
              <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                :class="statusPillClass(statusGateway(o))">
                <font-awesome-icon :icon="statusIcon(statusGateway(o))" />
                {{ statusLabel(statusGateway(o)) }}
              </span>
            </div>

            <div class="flex flex-col gap-1 text-sm text-gray-600 mb-4">
              <div>
                Total:
                <span class="font-bold text-gray-900">R$ {{ displayTotal(o) }}</span>
              </div>
              <div v-if="isCardInstallments(o)" class="text-xs">
                Parcelamento:
                <span class="font-medium">{{ installmentCount(o) }}x</span>
                de <span class="font-medium">R$ {{ perInstallment(o) }}</span>
              </div>
            </div>

            <div class="flex flex-wrap gap-2 mt-auto">
              <a v-if="receiptUrl(o)" :href="receiptUrl(o)" target="_blank" rel="noopener"
                class="bg-emerald-600 text-white px-3 py-2 rounded-lg hover:bg-emerald-700 text-sm inline-flex items-center gap-2">
                Ver comprovante
                <font-awesome-icon :icon="['fas', 'external-link-alt']" />
              </a>
              <button @click="goToPayment(o)"
                class="bg-blue-600 text-white px-3 py-2 rounded-lg cursor-pointer hover:bg-blue-700 text-sm inline-flex items-center gap-2">
                Abrir cobrança
                <font-awesome-icon :icon="['fas', 'external-link-alt']" />
              </button>
            </div>

            <div v-if="o.gateway_payload"
              class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-700">
              <div class="flex items-center gap-2">
                <font-awesome-icon :icon="['fas', 'calendar-alt']" class="text-gray-500" />
                <span>Criado: <strong>{{ formatDateBR(o.gateway_payload.dateCreated) }}</strong></span>
              </div>
              <div v-if="o.gateway_payload.dueDate" class="flex items-center gap-2">
                <font-awesome-icon :icon="['fas', 'calendar-alt']" class="text-gray-500" />
                <span>Vencimento: <strong>{{ formatDateBR(o.gateway_payload.dueDate) }}</strong></span>
              </div>
              <div v-if="o.gateway_payload.clientPaymentDate || o.gateway_payload.paymentDate"
                class="flex items-center gap-2">
                <font-awesome-icon :icon="['fas', 'calendar-alt']" class="text-gray-500" />
                <span>Pago em: <strong>{{ formatDateBR(o.gateway_payload.clientPaymentDate || o.gateway_payload.paymentDate) }}</strong></span>
              </div>
              <div v-if="o.gateway_payload.confirmedDate" class="flex items-center gap-2">
                <font-awesome-icon :icon="['fas', 'calendar-alt']" class="text-gray-500" />
                <span>Confirmado em: <strong>{{ formatDateBR(o.gateway_payload.confirmedDate) }}</strong></span>
              </div>
              <div v-if="o.gateway_payload.appliedFixTax || o.gateway_payload.appliedPercentTax"
                class="flex items-center gap-2 sm:col-span-2">
                <font-awesome-icon :icon="['fas', 'money-bill-wave']" class="text-gray-500" />
                <span>
                  Taxas:
                  <span v-if="o.gateway_payload.appliedFixTax">
                    R$ {{ (o.gateway_payload.appliedFixTax).toFixed(2).replace('.', ',') }}
                  </span>
                  <span v-if="o.gateway_payload.appliedPercentTax">
                    + {{ o.gateway_payload.appliedPercentTax }}%
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <Pagination class="mt-8" :current-page="currentPage" :last-page="lastPage"
          @change="page => search(false, page)" />

        <p v-if="!orders.length && !error" class="text-gray-600 text-center mt-10">Nenhum pedido encontrado.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import http from '@/api/http'
import { useRouter } from 'vue-router'
import { db, ref as dbRef, onValue } from '@/firebase'
import Pagination from '@/components/Pagination.vue'
import Loading from '@/components/Loading.vue'
import { FontAwesomeIcon } from '@/plugins/fontawesome'

function useSessionStorage(key, initialValue = null) {
  const stored = localStorage.getItem(key)
  const data = ref(stored ? JSON.parse(stored) : initialValue)
  function set(value) {
    data.value = value
    localStorage.setItem(key, JSON.stringify(value))
  }
  function clear() {
    data.value = null
    localStorage.removeItem(key)
  }
  return { data, set, clear }
}

const cpf = ref('')
const orders = ref([])
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const lastPage = ref(1)
const router = useRouter()
const unsubscribeFns = []
let lastUpdateToken = null
const { data: savedCpf, set: setCpfSession, clear: clearCpfSession } = useSessionStorage('user_cpf', '')

onMounted(() => {
  if (savedCpf.value) {
    cpf.value = savedCpf.value
    search(true)
  }
})

async function search(setupListeners = false, page = 1) {
  const cleanCpf = cpf.value.replace(/\D/g, '')
  if (!validateCpf(cleanCpf)) {
    error.value = 'CPF inválido.'
    return
  }
  loading.value = true
  error.value = null
  orders.value = []
  try {
    const { data } = await http.get(`/orders/by-cpf/${cleanCpf}?page=${page}`)
    orders.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
    setCpfSession(cpf.value)
    if (setupListeners) bindRealtimeUpdates(cleanCpf)
  } catch {
    error.value = 'Não foi possível buscar os pedidos. Verifique o CPF e tente novamente.'
  } finally {
    loading.value = false
  }
}

function validateCpf(c) {
  if (!c || c.length !== 11 || /^(\d)\1+$/.test(c)) return false
  let s = 0
  for (let i = 0; i < 9; i++) s += parseInt(c.charAt(i)) * (10 - i)
  let r = 11 - (s % 11); r = r >= 10 ? 0 : r
  if (r !== parseInt(c.charAt(9))) return false
  s = 0
  for (let i = 0; i < 10; i++) s += parseInt(c.charAt(i)) * (11 - i)
  r = 11 - (s % 11); r = r >= 10 ? 0 : r
  return r === parseInt(c.charAt(10))
}

function goToPayment(order) {
  router.push(`/orders/${order.order_number}`)
}

function bindRealtimeUpdates(cleanCpf) {
  unsubscribeFns.forEach(fn => fn && fn())
  unsubscribeFns.length = 0

  const prefix = import.meta.env.VITE_FIREBASE_COLLECTION_PREFIX || ''
  const updatesRef = dbRef(db, `updates/${prefix}orders_by_cpf_${cleanCpf}`)

  let isFirstSnapshot = true
  const unsubscribe = onValue(updatesRef, snap => {
    if (!snap.exists()) return

    if (isFirstSnapshot) {
      isFirstSnapshot = false
      return
    }

    const payload = snap.val()
    const token = payload.last_updated || payload.updated_at || JSON.stringify(payload)
    if (token && token === lastUpdateToken) return
    lastUpdateToken = token
    search(false, currentPage.value)
  })

  unsubscribeFns.push(unsubscribe)
}

function logout() {
  cpf.value = ''
  orders.value = []
  error.value = null
  clearCpfSession()
  unsubscribeFns.forEach(fn => fn && fn())
  unsubscribeFns.length = 0
}

function statusLabel(status) {
  switch (status) {
    case 'pending': return 'Pendente'
    case 'paid': return 'Pago'
    case 'canceled': return 'Cancelado'
    case 'overdue': return 'Vencido'
    case 'refunded': return 'Reembolsado'
    case 'partially_refunded': return 'Reembolsado Parcial'
    case 'dispute': return 'Em Disputa'
    default: return status
  }
}

function statusGateway(o) {
  if (o.gateway_payload?.status) {
    const s = o.gateway_payload.status
    if (s === 'RECEIVED' || s === 'CONFIRMED') return 'paid'
    if (s === 'PENDING') return 'pending'
    if (s === 'CANCELLED' || s === 'DELETED') return 'canceled'
    if (s === 'OVERDUE') return 'overdue'
  }
  return o.status
}

function methodLabel(o) {
  const t = (o.gateway_payload?.billingType || o.payment_method || '').toUpperCase()
  switch (t) {
    case 'CREDIT_CARD': return 'Cartão de crédito'
    case 'BOLETO': return 'Boleto'
    case 'PIX': return 'PIX'
    default: return t || '—'
  }
}

function methodIcon(o) {
  const t = (o.gateway_payload?.billingType || o.payment_method || '').toUpperCase()
  if (t === 'PIX') return ['fab', 'pix']
  if (t === 'BOLETO') return ['fas', 'barcode']
  if (t === 'CREDIT_CARD') return ['fas', 'credit-card']
  return ['fas', 'credit-card']
}

function statusIcon(status) {
  if (status === 'paid') return ['fas', 'check-circle']
  if (status === 'pending') return ['fas', 'hourglass-half']
  if (status === 'canceled') return ['fas', 'times-circle']
  if (status === 'overdue') return ['fas', 'exclamation-triangle']
  return ['fas', 'hourglass-half']
}

function statusPillClass(status) {
  if (status === 'paid') return 'bg-green-50 text-green-700 ring-1 ring-green-200'
  if (status === 'pending') return 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200'
  if (status === 'canceled') return 'bg-red-50 text-red-700 ring-1 ring-red-200'
  if (status === 'overdue') return 'bg-orange-50 text-orange-700 ring-1 ring-orange-200'
  return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'
}

function formatDateBR(value) {
  if (!value) return ''
  const s = String(value)
  let d = new Date(s)
  if (isNaN(d)) {
    const norm = s.replace(' ', 'T')
    d = new Date(norm)
  }
  if (isNaN(d)) {
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (m) return `${m[3]}/${m[2]}/${m[1]}`
    return s
  }
  return new Intl.DateTimeFormat('pt-BR', { timeZone: 'America/Sao_Paulo' }).format(d)
}

function isCardInstallments(o) {
  const t = (o?.gateway_payload?.billingType || o?.payment_method || '').toUpperCase()
  if (t !== 'CREDIT_CARD') return false
  const cnt = Number(o?.gateway_payload?.installmentCount || 0)
  if (cnt > 1) return true
  const desc = o?.gateway_payload?.description || ''
  return /Parcela\s+\d+\s+de\s+\d+/i.test(desc)
}

function installmentCount(o) {
  const cnt = Number(o?.gateway_payload?.installmentCount || 0)
  if (cnt > 1) return cnt
  const desc = o?.gateway_payload?.description || ''
  const m = desc.match(/de\s+(\d+)/i)
  return m ? Number(m[1]) : 1
}

function perInstallmentCents(o) {
  const gp = o?.gateway_payload || {}
  if (gp.installmentValue != null) return Math.round(Number(gp.installmentValue) * 100)
  if (gp.value != null && installmentCount(o) > 1) return Math.round(Number(gp.value) * 100)
  return Math.round(Number(gp.value ?? o?.total_amount ?? 0) * 100)
}

function perInstallment(o) {
  const cents = perInstallmentCents(o)
  return cents != null ? (cents / 100).toFixed(2).replace('.', ',') : null
}

function baseSubtotalBRL(o) {
  if (!Array.isArray(o.items) || !o.items.length) return Number(o.total_amount || 0)
  let sum = 0
  for (const it of o.items) {
    const v = it.variant || {}
    const priceCents = (v.price_override != null ? Number(v.price_override) : Number(v.product?.base_price || 0))
    const qty = Number(it.quantity || 1)
    sum += (priceCents / 100) * qty
  }
  return Number(sum.toFixed(2))
}

function displayTotal(o) {
  if (isCardInstallments(o)) {
    const gp = o?.gateway_payload || {}
    if (gp.totalValue) return Number(gp.totalValue).toFixed(2).replace('.', ',')
    const base = baseSubtotalBRL(o)
    const fix = Number(gp.appliedFixTax || 0)
    const percent = Number(gp.appliedPercentTax || 0)
    const total = Math.round((base * (1 + percent / 100) + fix) * 100) / 100
    return total.toFixed(2).replace('.', ',')
  }
  if (o.gateway_payload?.value != null) return Number(o.gateway_payload.value).toFixed(2).replace('.', ',')
  return Number(o.total_amount || 0).toFixed(2).replace('.', ',')
}

function receiptUrl(o) {
  return o?.gateway_payload?.transactionReceiptUrl || null
}

onBeforeUnmount(() => {
  unsubscribeFns.forEach(fn => fn())
})
</script>
