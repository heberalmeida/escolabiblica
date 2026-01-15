<template>
  <div class="bg-gray-50">
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-bold flex-1 text-center sm:text-left flex items-center gap-3 text-gray-800">
          <font-awesome-icon :icon="['fas', 'chart-bar']" class="text-green-600" />
          Dashboard
        </h1>
      </div>

      <div class="flex flex-col sm:flex-row gap-4 justify-center mb-10">
        <PeriodPicker @change="loadDashboard" />
      </div>

      <div v-if="loading">
        <Loading />
      </div>

      <div v-else>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-gray-500 text-sm flex items-center gap-2">
                <font-awesome-icon :icon="['fas', 'shopping-cart']" />
                Total Pedidos
              </h2>
            </div>
            <p class="text-3xl font-bold text-blue-600">{{ stats?.orders ?? 0 }}</p>
          </div>

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'check-circle']" />
              Pedidos Pagos
            </h2>
            <p class="text-3xl font-bold text-green-600">{{ stats?.paid ?? 0 }}</p>
          </div>

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'image']" />
              Produtos
            </h2>
            <p class="text-3xl font-bold text-purple-600">{{ stats?.products ?? 0 }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'user']" />
              Usuários
            </h2>
            <p class="text-3xl font-bold text-orange-600">{{ stats?.users ?? 0 }}</p>
          </div>

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'money-bill-wave']" />
              Faturamento
            </h2>
            <p class="text-3xl font-bold text-indigo-600">
              {{ formatCurrency(stats?.value_paid ?? 0) }}
            </p>
          </div>

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'wallet']" />
              Total Valores
            </h2>
            <p class="text-3xl font-bold text-gray-700">
              {{ formatCurrency(stats?.value_total ?? 0) }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'hourglass-half']" />
              Valores Pendentes
            </h2>
            <p class="text-3xl font-bold text-yellow-600">
              {{ formatCurrency(stats?.value_pending ?? 0) }}
            </p>
          </div>

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'times-circle']" />
              Valores Cancelados
            </h2>
            <p class="text-3xl font-bold text-red-600">
              {{ formatCurrency(stats?.value_canceled ?? 0) }}
            </p>
          </div>

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'exclamation-triangle']" />
              Valores Vencidos
            </h2>
            <p class="text-3xl font-bold text-orange-600">
              {{ formatCurrency(stats?.value_overdue ?? 0) }}
            </p>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden mb-10">
          <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'shopping-cart']" class="text-green-600" />
              Últimos Pedidos
            </h2>
            <span v-if="latestOrders.length" class="text-sm text-gray-500">
              Total: {{ latestOrders.length }}
            </span>
          </div>

          <div class="p-6">
            <div v-if="latestOrders.length" class="overflow-x-auto">
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
                  <tr v-for="o in latestOrders" :key="o.id" class="hover:bg-gray-50 transition">
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
                        Parcelado em {{ installmentCount(o) }}x de R$
                        {{ perInstallment(o) }}
                      </div>
                    </td>
                    <td class="p-3 border">
                      <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                        :class="statusPillClass(statusGateway(o))">
                        <font-awesome-icon :icon="statusIcon(statusGateway(o))" />
                        {{ statusLabel(statusGateway(o)) }}
                      </span>
                    </td>
                    <td class="p-3 border ">
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

        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden">
          <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'image']" class="text-green-600" />
              Últimos Produtos
            </h2>
            <span v-if="latestProducts.length" class="text-sm text-gray-500">
              Total: {{ latestProducts.length }}
            </span>
          </div>

          <div class="p-6">
            <ul v-if="latestProducts.length" class="divide-y divide-gray-100">
              <li v-for="p in latestProducts" :key="p.id"
                class="flex justify-between items-center py-3 hover:bg-gray-50 px-2 rounded-lg transition">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <font-awesome-icon :icon="['fas', 'barcode']" class="text-gray-400" />
                  </div>
                  <span class="font-medium text-gray-800 truncate max-w-[180px]">
                    {{ p.name }}
                  </span>
                </div>
                <span class="font-semibold text-green-600">
                  {{ formatCurrency(p.base_price) }}
                </span>
              </li>
            </ul>

            <p v-else class="text-gray-500 text-sm text-center py-6">
              Nenhum produto cadastrado.
            </p>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import http from '@/api/http'
import PeriodPicker from '@/components/PeriodPicker.vue'
import Loading from '@/components/Loading.vue'
import { format, startOfMonth } from 'date-fns'

const stats = ref({})
const latestOrders = ref([])
const latestProducts = ref([])
const loading = ref(false)

function formatDateBR(value) {
  if (!value) return ''
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', timeZone: 'America/Sao_Paulo'
  }).format(new Date(value))
}

function formatCurrency(cents) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(cents / 100)
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
  if (o.gateway_payload?.status) {
    const s = o.gateway_payload.status
    if (s === 'RECEIVED' || s === 'CONFIRMED') return 'paid'
    if (s === 'PENDING') return 'pending'
    if (s === 'CANCELLED' || s === 'DELETED') return 'canceled'
    if (s === 'OVERDUE') return 'overdue'
  }
  return o.status
}

function statusPillClass(status) {
  if (status === 'paid') return 'text-green-600 font-semibold'
  if (status === 'pending') return 'text-yellow-600 font-semibold'
  if (status === 'canceled') return 'text-red-600 font-semibold'
  if (status === 'overdue') return 'text-orange-600 font-semibold'
  return 'text-gray-600 font-semibold'
}

function methodLabel(o) {
  const t = (o.gateway_payload?.billingType || o.payment_method || '').toUpperCase()
  if (t === 'PIX') return 'PIX'
  if (t === 'BOLETO') return 'Boleto'
  if (t === 'CREDIT_CARD') return 'Cartão de Crédito'
  return '—'
}

function fmtLocalDate(d) {
  return format(new Date(d.getFullYear(), d.getMonth(), d.getDate()), 'yyyy-MM-dd')
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

function statusIcon(status) {
  if (status === 'paid') return ['fas', 'check-circle']
  if (status === 'pending') return ['fas', 'hourglass-half']
  if (status === 'canceled') return ['fas', 'times-circle']
  if (status === 'overdue') return ['fas', 'exclamation-triangle']
  return ['fas', 'hourglass-half']
}

function perInstallment(o) {
  const gp = o?.gateway_payload || {}
  if (gp.installmentValue != null) {
    return formatCurrency(Math.round(Number(gp.installmentValue) * 100))
  }
  if (gp.value != null && installmentCount(o) > 1) {
    return formatCurrency(Math.round(Number(gp.value) * 100))
  }
  return null
}

function displayTotal(o) {
  const gp = o?.gateway_payload || {}
  if (gp.totalValue != null) {
    return formatCurrency(Math.round(Number(gp.totalValue) * 100))
  }
  if (isCardInstallments(o)) {
    const count = installmentCount(o)
    const per = gp.installmentValue != null
      ? Math.round(Number(gp.installmentValue) * 100)
      : gp.value != null ? Math.round(Number(gp.value) * 100) : 0
    if (per && count > 1) {
      return formatCurrency(per * count)
    }
  }
  if (gp.value != null) {
    return formatCurrency(Math.round(Number(gp.value) * 100))
  }
  return formatCurrency(Number(o.total_amount || 0))
}

async function loadDashboard(range = []) {
  try {
    loading.value = true
    const params = {}
    if (Array.isArray(range) && range.length === 2 && range[0] && range[1]) {
      params.start_date = fmtLocalDate(range[0])
      params.end_date = fmtLocalDate(range[1])
    } else {
      const today = new Date()
      params.start_date = format(startOfMonth(today), 'yyyy-MM-dd')
      params.end_date = format(today, 'yyyy-MM-dd')
    }
    const { data } = await http.get('/admin/dashboard', { params })
    latestOrders.value = data.latestOrders ?? []
    latestProducts.value = data.latestProducts ?? []
    stats.value = data.stats ?? {}
  } catch (e) {
    console.error('Erro ao carregar dashboard', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => loadDashboard())
</script>
