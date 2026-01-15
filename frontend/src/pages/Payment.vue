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

        <div v-else-if="order" class="space-y-4">
          <div class="border-b pb-4">
            <p class="text-sm text-gray-600">
              Pedido: <span class="font-semibold text-gray-900">{{ order.orderNumber || order.order_number }}</span>
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
            <p>Sub-total: <span class="font-medium">{{ formatBRL(displaySubtotalCents(order)) }}</span></p>

            <p v-if="isCard && (order.gateway_payload?.appliedFixTax || order.gateway_payload?.appliedPercentTax)">
              Taxa Cartão:
              <span v-if="order.gateway_payload?.appliedFixTax">{{ formatBRL(toCents(order.gateway_payload.appliedFixTax)) }}</span>
              <span v-if="order.gateway_payload?.appliedPercentTax"> + {{ order.gateway_payload.appliedPercentTax }}%</span>
            </p>

            <p v-if="isPix && order.gateway_payload?.appliedFixTax">
              Taxa PIX: <span class="font-medium">{{ formatBRL(toCents(order.gateway_payload.appliedFixTax)) }}</span>
            </p>

            <p v-if="isBoleto && order.gateway_payload?.appliedFixTax">
              Taxa Boleto: <span class="font-medium">{{ formatBRL(toCents(order.gateway_payload.appliedFixTax)) }}</span>
            </p>

            <p v-if="isCardInstallments(order)">
              Parcelamento:
              <strong>{{ installmentCountOf(order) }}x</strong>
              de <strong>{{ formatBRL(perInstallmentCentsOf(order)) }}</strong>
            </p>
          </div>

          <div class="text-lg font-bold text-green-700">
            Total: {{ formatBRL(totalWithInstallmentsCents(order)) }}
          </div>

          <div>
            <p class="text-sm">
              Status:
              <span :class="{
                'text-green-600 font-bold': statusGateway(order) === 'paid',
                'text-yellow-600 font-bold': statusGateway(order) === 'pending',
                'text-red-600 font-bold': statusGateway(order) === 'canceled',
                'text-orange-600 font-bold': statusGateway(order) === 'overdue'
              }">
                <font-awesome-icon :icon="statusGateway(order) === 'paid' ? ['fas', 'check-circle']
                  : statusGateway(order) === 'pending' ? ['fas', 'hourglass-half']
                  : statusGateway(order) === 'canceled' ? ['fas', 'times-circle']
                  : statusGateway(order) === 'overdue' ? ['fas', 'exclamation-triangle']
                  : ['fas', 'hourglass-half']" class="mr-1"/>
                {{ statusLabel(statusGateway(order)) }}
              </span>
            </p>

            <div v-if="statusGateway(order) === 'paid'" class="text-green-600 font-bold mt-2">
              Pagamento confirmado!
            </div>
            <div v-else class="text-yellow-600 font-bold mt-2">
              Pagamento em análise.
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-700">
            <p><font-awesome-icon :icon="['fas', 'calendar-alt']" class="mr-2 text-gray-500" /> Data da cobrança: <strong>{{ formatDateBR(order.gateway_payload?.dateCreated) }}</strong></p>
            <p v-if="order.gateway_payload?.clientPaymentDate || order.gateway_payload?.paymentDate">
              <font-awesome-icon :icon="['fas', 'calendar-alt']" class="mr-2 text-gray-500" /> Data do pagamento: <strong>{{ formatDateBR(order.gateway_payload.clientPaymentDate || order.gateway_payload.paymentDate) }}</strong>
            </p>
            <p v-if="order.gateway_payload?.confirmedDate">
              <font-awesome-icon :icon="['fas', 'calendar-alt']" class="mr-2 text-gray-500" /> Confirmado em: <strong>{{ formatDateBR(order.gateway_payload.confirmedDate) }}</strong>
            </p>
          </div>

          <div v-if="statusGateway(order) === 'paid' && order.gateway_payload?.transactionReceiptUrl" class="mt-2">
            <a :href="order.gateway_payload.transactionReceiptUrl" target="_blank" class="text-blue-600 underline inline-flex items-center gap-1">
              <font-awesome-icon :icon="['fas', 'external-link-alt']" />
              Abrir comprovante
            </a>
          </div>

          <div v-if="isPix && statusGateway(order) !== 'paid'" class="space-y-4">
            <div v-if="order.gateway_payload?.pixQrCodeImage" class="flex justify-center">
              <img :src="`data:image/png;base64,${order.gateway_payload.pixQrCodeImage}`" alt="QR Code PIX"
                class="w-48 h-48 sm:w-64 sm:h-64 border rounded shadow" />
            </div>

            <div v-if="order.gateway_payload?.pixQrCode">
              <label class="block text-sm font-medium mb-1">Código PIX</label>
              <div class="flex flex-col sm:flex-row gap-2">
                <textarea class="w-full border rounded p-2 text-xs" readonly rows="4">{{ order.gateway_payload.pixQrCode }}</textarea>
                <button @click="copyPix" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm w-full sm:w-auto">Copiar</button>
              </div>
              <p v-if="copiedPix" class="text-green-600 text-xs mt-1">Código copiado!</p>
            </div>
          </div>

          <div v-if="isBoleto && statusGateway(order) !== 'paid'" class="space-y-4">
            <div v-if="order.gateway_payload?.identificationField">
              <label class="block text-sm font-medium mb-1">Linha Digitável</label>
              <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" class="w-full border rounded p-2 text-sm" :value="order.gateway_payload.identificationField" readonly />
                <button @click="copyDigitableLine" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm w-full sm:w-auto">Copiar</button>
              </div>
              <p v-if="copiedBoleto" class="text-green-600 text-xs mt-1">Linha digitável copiada!</p>
            </div>

            <iframe v-if="order.gateway_payload?.bankSlipUrl"
              :src="order.gateway_payload.bankSlipUrl" class="w-full h-[500px] sm:h-[600px] border rounded"></iframe>

            <a v-if="order.gateway_payload?.bankSlipUrl"
              :href="order.gateway_payload.bankSlipUrl" target="_blank" class="inline-flex items-center gap-1 text-blue-600 underline">
              <font-awesome-icon :icon="['fas', 'external-link-alt']" />
              Abrir boleto em nova aba
            </a>
          </div>

          <div class="mt-6 flex flex-col sm:flex-row justify-between gap-2">
            <RouterLink to="/" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-center">Voltar</RouterLink>
            <RouterLink to="/orders" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-center">Meus Pedidos</RouterLink>
          </div>
        </div>

        <div v-else class="text-red-600 text-center font-medium py-6">Nenhum pedido encontrado.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import http from '@/api/http'
import { useCurrency } from '@/composables/useCurrency'
import { db, ref as dbRef, onValue } from '@/firebase'

const route = useRoute()
const { formatBRL } = useCurrency()

const order = ref(null)
const loading = ref(true)
const error = ref(null)
const copiedPix = ref(false)
const copiedBoleto = ref(false)

const toCents = (v) => Math.round(Number(v || 0) * 100)

const method = computed(() => {
  const t = (order.value?.gateway_payload?.billingType || order.value?.payment_method || '').toUpperCase()
  return t
})
const isPix = computed(() => method.value === 'PIX')
const isBoleto = computed(() => method.value === 'BOLETO')
const isCard = computed(() => method.value === 'CREDIT_CARD')
const methodTitle = computed(() => (isPix.value ? 'PIX' : isBoleto.value ? 'Boleto' : isCard.value ? 'Cartão' : 'Pagamento'))

async function fetchOrder() {
  try {
    const { orderNumber } = route.params
    const { data } = await http.get(`/orders/by-number/${orderNumber}`)
    order.value = data
  } catch {
    error.value = 'Não foi possível carregar os dados do pedido.'
  } finally {
    loading.value = false
  }
}

async function copyPix() {
  if (order.value?.gateway_payload?.pixQrCode) {
    await navigator.clipboard.writeText(order.value.gateway_payload.pixQrCode)
    copiedPix.value = true
    setTimeout(() => (copiedPix.value = false), 2000)
  }
}

async function copyDigitableLine() {
  if (order.value?.gateway_payload?.identificationField) {
    await navigator.clipboard.writeText(order.value.gateway_payload.identificationField)
    copiedBoleto.value = true
    setTimeout(() => (copiedBoleto.value = false), 2000)
  }
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
  if (o?.gateway_payload?.status) {
    const s = o.gateway_payload.status
    if (s === 'RECEIVED' || s === 'CONFIRMED') return 'paid'
    if (s === 'PENDING') return 'pending'
    if (s === 'CANCELLED' || s === 'DELETED') return 'canceled'
    if (s === 'OVERDUE') return 'overdue'
  }
  return o?.status
}

const itemUnitPriceCents = (i) => {
  if (i?.variant?.product?.base_price != null) return Math.round(Number(i.variant.product.base_price))
  if (i?.unit_price != null) {
    const up = Number(i.unit_price)
    if (up >= 1000) return Math.round(up)
    return Math.round(up * 100)
  }
  return 0
}

const itemsSubtotalCents = (items = []) =>
  items.reduce((s, it) => s + itemUnitPriceCents(it) * Number(it.quantity || 1), 0)

function displaySubtotalCents(o) {
  if (!o) return 0
  if (isCardInstallments(o)) {
    if (Array.isArray(o.items) && o.items.length) return itemsSubtotalCents(o.items)
  }
  return o?.total_amount ?? 0
}

const isCardInstallments = (o) => {
  if (!o) return false
  const t = (o.gateway_payload?.billingType || o.payment_method || '').toUpperCase()
  if (t !== 'CREDIT_CARD') return false
  const cnt = Number(o.gateway_payload?.installmentCount || 0)
  if (cnt > 1) return true
  const desc = o.gateway_payload?.description || ''
  return /Parcela\s+\d+\s+de\s+\d+/i.test(desc)
}

const installmentCountOf = (o) => {
  const cnt = Number(o?.gateway_payload?.installmentCount || 0)
  if (cnt > 1) return cnt
  const desc = o?.gateway_payload?.description || ''
  const m = desc.match(/de\s+(\d+)/i)
  return m ? Number(m[1]) : 1
}

const perInstallmentCentsOf = (o) => {
  const gp = o?.gateway_payload || {}
  if (gp.installmentValue != null) return Math.round(Number(gp.installmentValue) * 100)
  if (gp.value != null && installmentCountOf(o) > 1) return Math.round(Number(gp.value) * 100)
  return Math.round(Number(gp.value ?? o?.total_amount ?? 0) * 100)
}

const totalWithInstallmentsCents = (o) => {
  if (isCardInstallments(o)) {
    const gp = o?.gateway_payload || {}
    if (gp.totalValue) return Math.round(Number(gp.totalValue) * 100)
    const baseCents = displaySubtotalCents(o)
    const percent = Number(gp.appliedPercentTax || 0)
    const fixCents = toCents(gp.appliedFixTax || 0)
    const total = baseCents * (1 + percent / 100) + fixCents
    return Math.round(total)
  }
  if (o?.gateway_payload?.value != null) return Math.round(Number(o.gateway_payload.value) * 100)
  return o?.total_amount ?? 0
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

let unsubscribe = null

onMounted(async () => {
  await fetchOrder()
  if (order.value?.id) {
    const orderKey = `orders_${order.value.id}`
    const refDb = dbRef(db, `webhooks/${orderKey}`)
    unsubscribe = onValue(refDb, async snapshot => {
      if (snapshot.exists()) await fetchOrder()
    })
  }
})

onBeforeUnmount(() => {
  if (unsubscribe) unsubscribe()
})
</script>
