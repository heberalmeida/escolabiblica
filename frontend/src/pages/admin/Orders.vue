<template>
  <div class="bg-gray-50 min-h-screen">
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-bold flex-1 text-center sm:text-left flex items-center gap-3 text-gray-800">
          <font-awesome-icon :icon="['fas', 'shopping-cart']" class="text-green-600" />
          Pedidos
        </h1>
      </div>

      <div class="bg-white p-4 rounded-xl shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="flex flex-col">
          <label class="text-xs text-gray-500 mb-1">Nº do Pedido</label>
          <input v-model="filters.order" type="text" placeholder="Ex: 12345"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" />
        </div>

        <div class="flex flex-col lg:col-span-2">
          <label class="text-xs text-gray-500 mb-1">Cliente</label>
          <input v-model="filters.client" type="text" placeholder="Nome do cliente"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" />
        </div>

        <div class="flex flex-col">
          <label class="text-xs text-gray-500 mb-1">Status</label>
          <select v-model="filters.status"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">Todos</option>
            <option value="pending">Pendente</option>
            <option value="paid">Pago</option>
            <option value="canceled">Cancelado</option>
            <option value="overdue">Vencido</option>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-xs text-gray-500 mb-1">Pagamento</label>
          <select v-model="filters.payment"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            <option value="">Todos</option>
            <option value="PIX">PIX</option>
            <option value="BOLETO">Boleto</option>
            <option value="CREDIT_CARD">Cartão de Crédito</option>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-xs text-gray-500 mb-1">Período</label>
          <PeriodPicker @change="setPeriod" />
        </div>

        <div class="flex flex-col justify-center">
          <div class="flex items-center justify-end">
            <button @click="clearFilters"
              class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium w-full sm:w-auto">
              Limpar Filtros
            </button>
          </div>
        </div>
      </div>


      <div v-if="loading">
        <Loading />
      </div>

      <div v-else>
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden mb-10">
          <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'clipboard-list']" class="text-green-600" />
              Lista de Pedidos
            </h2>
            <span v-if="pagination?.total" class="text-sm text-gray-500">
              Total: {{ pagination.total }}
            </span>
          </div>

          <div class="p-6">
            <div v-if="orders.length" class="overflow-x-auto">
              <table class="w-full text-sm border rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-gray-700">
                  <tr>
                    <th class="p-3 border text-left">#</th>
                    <th class="p-3 border text-left">Pedido</th>
                    <th class="p-3 border text-left">Cliente</th>
                    <th class="p-3 border text-left">Valor</th>
                    <th class="p-3 border text-left">Status</th>
                    <th class="p-3 border text-left">Pagamento</th>
                    <th class="p-3 border text-left">Data</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="o in orders" :key="o.id" class="hover:bg-gray-50 transition">
                    <td class="p-3 border text-gray-600">{{ o.id }}</td>
                    <td class="p-3 border">
                      <a :href="`/orders/${o.order_number}`" class="text-blue-600 font-medium hover:underline">
                        #{{ o.order_number }}
                      </a>
                    </td>
                    <td class="p-3 border text-gray-800 truncate max-w-[180px]">
                      {{ o.buyer_name }}
                    </td>
                    <td class="p-3 border">
                      <span class="font-semibold text-gray-900">{{ displayTotal(o) }}</span>
                      <div v-if="isCardInstallments(o)" class="text-xs text-gray-600">
                        Parcelado em {{ installmentCount(o) }}x de {{ perInstallment(o) }}
                      </div>
                    </td>
                    <td class="p-3 border">
                      <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                        :class="statusPillClass(statusGateway(o))">
                        <font-awesome-icon :icon="statusIcon(statusGateway(o))" />
                        {{ statusLabel(statusGateway(o)) }}
                      </span>
                    </td>
                    <td class="p-3 border">
                      <font-awesome-icon v-if="methodLabel(o) === 'PIX'" :icon="['fab', 'pix']"
                        class="text-green-600" />
                      <font-awesome-icon v-else-if="methodLabel(o) === 'Boleto'" :icon="['fas', 'barcode']"
                        class="text-gray-600" />
                      <font-awesome-icon v-else-if="methodLabel(o) === 'Cartão de Crédito'"
                        :icon="['fas', 'credit-card']" class="text-blue-600" />
                      <span>{{ methodLabel(o) }}</span>
                    </td>
                    <td class="p-3 border text-gray-700">
                      {{ formatDateBR(o.created_at) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p v-else class="text-gray-500 text-sm text-center py-6">
              Nenhum pedido encontrado.
            </p>
          </div>
        </div>

        <Pagination v-if="pagination?.last_page > 1" :currentPage="page" :lastPage="pagination.last_page"
          @change="goToPage" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import http from '@/api/http'
import Loading from '@/components/Loading.vue'
import Pagination from '@/components/Pagination.vue'
import PeriodPicker from '@/components/PeriodPicker.vue'
import { useDebounce } from '@/composables/useDebounce'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { format } from 'date-fns'

const orders = ref([])
const page = ref(1)
const pagination = ref({})
const loading = ref(false)

const defaultFilters = {
  order: '',
  client: '',
  status: '',
  payment: '',
  from: '',
  to: '',
  date_type: 'created_at'
}

const filters = useLocalStorage('orders_filters', defaultFilters)

function setPeriod(range) {
  if (Array.isArray(range) && range.length === 2) {
    filters.value.from = format(range[0], 'yyyy-MM-dd')
    filters.value.to = format(range[1], 'yyyy-MM-dd')
  } else {
    filters.value.from = ''
    filters.value.to = ''
  }
}

function buildQuery() {
  const params = new URLSearchParams()
  params.append('page', page.value)
  if (filters.value.order) params.append('order_number', filters.value.order)
  if (filters.value.client) params.append('buyer_name', filters.value.client)
  if (filters.value.status) params.append('status', filters.value.status)
  if (filters.value.payment) params.append('payment', filters.value.payment)
  if (filters.value.from && filters.value.to) {
    params.append('from', filters.value.from)
    params.append('to', filters.value.to)
    params.append('date_type', filters.value.date_type)
  }
  return params.toString()
}

async function load() {
  try {
    loading.value = true
    const query = buildQuery()
    const { data } = await http.get(`/admin/orders?${query}`)
    orders.value = data.data
    pagination.value = data
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  Object.assign(filters.value, defaultFilters)
  page.value = 1
  load()
}

const debouncedLoad = useDebounce(load, 600)
watch(filters, () => {
  page.value = 1
  debouncedLoad()
}, { deep: true })

function goToPage(p) {
  if (p >= 1 && p <= (pagination.value?.last_page || 1)) {
    page.value = p
    load()
  }
}

onMounted(load)

function formatCurrency(cents) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((cents || 0) / 100)
}

function formatDateBR(value) {
  if (!value) return ''
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', timeZone: 'America/Sao_Paulo'
  }).format(new Date(value))
}

function statusLabel(status) {
  switch (status) {
    case 'pending': return 'Pendente'
    case 'paid': return 'Pago'
    case 'canceled': return 'Cancelado'
    case 'overdue': return 'Vencido'
    default: return status
  }
}

function statusGateway(o) {
  const s = o?.gateway_payload?.payment?.status?.toUpperCase?.()
  const main = o?.status?.toLowerCase?.()

  if (main && ['pending', 'paid', 'canceled', 'overdue'].includes(main)) {
    return main
  }
  if (s === 'RECEIVED' || s === 'CONFIRMED') return 'paid'
  if (s === 'PENDING') return 'pending'
  if (s === 'CANCELLED' || s === 'DELETED') return 'canceled'
  if (s === 'OVERDUE') return 'overdue'
  return main || 'pending'
}

function statusPillClass(status) {
  if (status === 'paid') return 'text-green-600 font-semibold'
  if (status === 'pending') return 'text-yellow-600 font-semibold'
  if (status === 'canceled') return 'text-red-600 font-semibold'
  if (status === 'overdue') return 'text-orange-600 font-semibold'
  return 'text-gray-600 font-semibold'
}

function statusIcon(status) {
  if (status === 'paid') return ['fas', 'check-circle']
  if (status === 'pending') return ['fas', 'hourglass-half']
  if (status === 'canceled') return ['fas', 'times-circle']
  if (status === 'overdue') return ['fas', 'exclamation-triangle']
  return ['fas', 'hourglass-half']
}

function methodLabel(o) {
  const t = (o?.gateway_payload?.payment?.billingType || o?.payment_method || '').toUpperCase()
  if (t === 'PIX') return 'PIX'
  if (t === 'BOLETO') return 'Boleto'
  if (t === 'CREDIT_CARD') return 'Cartão de Crédito'
  return '—'
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

function itemUnitPriceCents(i) {
  if (i?.variant?.product?.base_price != null) return Math.round(Number(i.variant.product.base_price))
  if (i?.unit_price != null) {
    const up = Number(i.unit_price)
    return up >= 1000 ? Math.round(up) : Math.round(up * 100)
  }
  return 0
}

function itemsSubtotalCents(items = []) {
  return items.reduce((s, it) => s + itemUnitPriceCents(it) * Number(it.quantity || 1), 0)
}

const toCents = (v) => Math.round(Number(v || 0) * 100)

function estimateBaseFromGatewayCents(o) {
  const gp = o?.gateway_payload || {}
  const count = installmentCount(o)
  const percent = Number(gp.appliedPercentTax || 0)
  const fix = toCents(gp.appliedFixTax || 0)
  const per = gp.installmentValue != null ? toCents(gp.installmentValue) : gp.value != null ? toCents(gp.value) : null
  if (per != null && count > 1) {
    const total = per * count
    return Math.max(0, Math.round((total - fix) / (1 + percent / 100)))
  }
  if (gp.value != null && !count) {
    const total = toCents(gp.value)
    return Math.max(0, Math.round((total - fix) / (1 + percent / 100)))
  }
  return null
}

function totalWithInstallmentsCents(o) {
  const gp = o?.gateway_payload || {}
  if (isCardInstallments(o)) {
    if (gp.totalValue != null) return toCents(gp.totalValue)
    const base =
      (Array.isArray(o.items) && o.items.length ? itemsSubtotalCents(o.items) : null) ??
      estimateBaseFromGatewayCents(o) ??
      0
    const percent = Number(gp.appliedPercentTax || 0)
    const fix = toCents(gp.appliedFixTax || 0)
    return Math.round(base * (1 + percent / 100) + fix)
  }
  if (gp.totalValue != null) return toCents(gp.totalValue)
  if (gp.value != null) return toCents(gp.value)
  return 0
}

function perInstallment(o) {
  const gp = o?.gateway_payload || {}
  if (!isCardInstallments(o)) return null
  if (gp.installmentValue != null) return formatCurrency(toCents(gp.installmentValue))
  if (gp.value != null && installmentCount(o) > 1) return formatCurrency(toCents(gp.value))
  const count = installmentCount(o)
  return formatCurrency(Math.round(totalWithInstallmentsCents(o) / Math.max(1, count)))
}

function displayTotal(o) {
  return formatCurrency(totalWithInstallmentsCents(o))
}
</script>
