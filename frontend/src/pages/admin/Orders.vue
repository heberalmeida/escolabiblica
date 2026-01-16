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
          <input 
            v-model="filters.order" 
            @input="debouncedLoad"
            type="text" 
            placeholder="Ex: 12345"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" 
          />
        </div>

        <div class="flex flex-col lg:col-span-2">
          <label class="text-xs text-gray-500 mb-1">Cliente</label>
          <input 
            v-model="filters.client" 
            @input="debouncedLoad"
            type="text" 
            placeholder="Nome do cliente"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" 
          />
        </div>

        <div class="flex flex-col">
          <label class="text-xs text-gray-500 mb-1">Status</label>
          <select 
            v-model="filters.status" 
            @change="handleStatusChange"
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
          <select 
            v-model="filters.payment" 
            @change="handlePaymentChange"
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
              <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Pedido</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comprador</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inscrições</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="order in orders" :key="order.id"
                    class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-sm font-mono text-gray-900">
                      {{ order.payment_id || order.id || '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900">
                      <div v-if="order.buyer">
                        <div class="font-medium">{{ order.buyer.name || '-' }}</div>
                        <div class="text-xs text-gray-500">{{ order.buyer.cpf || '' }}</div>
                      </div>
                      <span v-else>-</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                      {{ order.registrations_count || order.registrations?.length || 0 }} ingresso(s)
                      <div v-if="order.registrations && order.registrations.length > 0" class="text-xs text-gray-500 mt-1">
                        <div v-for="reg in order.registrations.slice(0, 3)" :key="reg.id">
                          • {{ reg.name }} - {{ reg.event?.name || 'Evento' }}
                        </div>
                        <div v-if="order.registrations.length > 3" class="text-gray-400">
                          ... e mais {{ order.registrations.length - 3 }}
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm font-semibold text-blue-600">
                      {{ formatBRL(order.total_amount || 0) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                      {{ formatPaymentMethod(order.payment_method) }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                      <span class="px-2 py-1 rounded text-xs font-medium" :class="getStatusClass(order.payment_status)">
                        {{ getStatusLabel(order.payment_status) }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(order.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p v-else class="text-gray-500 text-sm text-center py-6">
              Nenhum pedido encontrado.
            </p>
          </div>
        </div>

        <!-- Paginação -->
        <div v-if="pagination && pagination.total > pagination.per_page" class="mt-6 flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} pedidos
          </div>
          <div class="flex gap-2">
            <button v-if="pagination.current_page > 1" @click="goToPage(pagination.current_page - 1)"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
              Anterior
            </button>
            <button v-if="pagination.current_page < pagination.last_page" @click="goToPage(pagination.current_page + 1)"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
              Próxima
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { registrationsApi, eventsApi } from '@/api/events'
import { useCurrency } from '@/composables/useCurrency'
import { useDebounce } from '@/composables/useDebounce'
import { FontAwesomeIcon } from '@/plugins/fontawesome'
import Loading from '@/components/Loading.vue'
import PeriodPicker from '@/components/PeriodPicker.vue'
import { format } from 'date-fns'

const { formatBRL } = useCurrency()
const orders = ref([])
const events = ref([])
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

const filters = ref({ ...defaultFilters })

async function loadEvents() {
  try {
    const response = await eventsApi.list()
    if (response.data) {
      if (Array.isArray(response.data)) {
        events.value = response.data
      } else if (response.data.data) {
        events.value = response.data.data
      }
    }
  } catch (error) {
    console.error('Erro ao carregar eventos:', error)
  }
}

function setPeriod(range) {
  if (Array.isArray(range) && range.length === 2) {
    filters.value.from = format(range[0], 'yyyy-MM-dd')
    filters.value.to = format(range[1], 'yyyy-MM-dd')
  } else {
    filters.value.from = ''
    filters.value.to = ''
  }
  page.value = 1
  load()
}

async function load() {
  try {
    loading.value = true
    const params = {
      page: page.value,
      per_page: 15,
      group_by_payment: true // Agrupar por pagamento para mostrar pedidos
    }
    
    if (filters.value.order && filters.value.order.trim()) {
      // O backend busca por asaas_payment_id quando group_by_payment é true
      // Vamos passar como parâmetro para filtrar após agrupar
      params.payment_id = filters.value.order.trim()
    }
    
    if (filters.value.client && filters.value.client.trim()) {
      params.buyer_name = filters.value.client.trim()
    }
    
    if (filters.value.status) {
      params.paymentStatus = filters.value.status
    }
    
    if (filters.value.payment) {
      params.payment_method = filters.value.payment
    }
    
    if (filters.value.from && filters.value.to) {
      params.from = filters.value.from
      params.to = filters.value.to
      params.date_type = filters.value.date_type
    }
    
    const response = await registrationsApi.list(params)
    
    if (response.data) {
      let ordersData = []
      let paginationData = null
      
      if (Array.isArray(response.data)) {
        ordersData = response.data
      } else if (response.data.data) {
        ordersData = response.data.data
        paginationData = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 15,
          total: response.data.total || 0,
          from: response.data.from || 0,
          to: response.data.to || 0
        }
      }
      
      orders.value = ordersData
      pagination.value = paginationData
    }
  } catch (error) {
    console.error('Erro ao carregar pedidos:', error)
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  Object.assign(filters.value, defaultFilters)
  page.value = 1
  load()
}

function handleStatusChange() {
  page.value = 1
  load()
}

function handlePaymentChange() {
  page.value = 1
  load()
}

const debouncedLoad = useDebounce(() => {
  page.value = 1
  load()
}, 500)

function goToPage(p) {
  if (p >= 1 && p <= (pagination.value?.last_page || 1)) {
    page.value = p
    load()
  }
}

function formatDate(value) {
  if (!value) return ''
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'America/Sao_Paulo'
  }).format(new Date(value))
}

function formatPaymentMethod(method) {
  if (!method) return '-'
  const methods = {
    'PIX': 'PIX',
    'BOLETO': 'Boleto',
    'CREDIT_CARD': 'Cartão de Crédito',
    'FREE': 'Gratuito'
  }
  return methods[method.toUpperCase()] || method
}

function getStatusLabel(status) {
  if (!status) return 'Pendente'
  const labels = {
    'pending': 'Pendente',
    'paid': 'Pago',
    'canceled': 'Cancelado',
    'refunded': 'Reembolsado',
    'overdue': 'Vencido'
  }
  return labels[status.toLowerCase()] || status
}

function getStatusClass(status) {
  if (!status) return 'bg-gray-100 text-gray-700'
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-700',
    'paid': 'bg-green-100 text-green-700',
    'canceled': 'bg-red-100 text-red-700',
    'refunded': 'bg-purple-100 text-purple-700',
    'overdue': 'bg-orange-100 text-orange-700'
  }
  return classes[status.toLowerCase()] || 'bg-gray-100 text-gray-700'
}

onMounted(async () => {
  await loadEvents()
  await load()
})

</script>
