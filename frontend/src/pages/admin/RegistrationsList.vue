<template>
  <div class="p-4 sm:p-8">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
      <h1 class="text-2xl font-bold text-gray-800">Inscrições</h1>
      <div class="flex gap-4 flex-wrap">
        <select v-model="selectedEventId" @change="loadRegistrations"
          class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">Todos os Eventos</option>
          <option v-for="event in events" :key="event.id" :value="event.id">{{ event.name }}</option>
        </select>
        <select v-model="selectedStatus" @change="filterByStatus"
          class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">Todos os Status</option>
          <option value="pending">Pendente</option>
          <option value="paid">Pago</option>
          <option value="canceled">Cancelado</option>
          <option value="refunded">Reembolsado</option>
          <option value="overdue">Vencido</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-10">
      <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else>
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nº Inscrição</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="registration in registrations" :key="registration.id"
              class="hover:bg-gray-50 transition">
              <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ registration.registration_number }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ registration.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ registration.event?.name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ registration.phone || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ registration.cpf || '-' }}</td>
              <td class="px-4 py-3 text-sm font-semibold" :class="getPriceColor(registration.price_paid)">
                {{ formatBRL(registration.price_paid || 0) }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span class="px-2 py-1 rounded text-xs font-medium" :class="getStatusClass(registration.payment_status)">
                  {{ getStatusLabel(registration.payment_status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm">
                <span v-if="registration.validated" class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">
                  Sim
                </span>
                <span v-else class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">
                  Não
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(registration.created_at) }}</td>
              <td class="px-4 py-3 text-sm">
                <div class="flex gap-2">
                  <button v-if="registration.payment_status !== 'paid'"
                    @click="markAsPaid(registration.id)"
                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition"
                    title="Marcar como Pago">
                    <font-awesome-icon :icon="['fas', 'check']" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div v-if="pagination && pagination.total > pagination.per_page" class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} inscrições
        </div>
        <div class="flex gap-2">
          <button v-if="pagination.current_page > 1" @click="changePage(pagination.current_page - 1)"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            Anterior
          </button>
          <button v-if="pagination.current_page < pagination.last_page" @click="changePage(pagination.current_page + 1)"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            Próxima
          </button>
        </div>
      </div>
    </div>

    <div v-if="!loading && registrations.length === 0" class="text-center py-10 text-gray-500">
      Nenhuma inscrição encontrada.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { registrationsApi, eventsApi } from '@/api/events'
import { FontAwesomeIcon } from '@/plugins/fontawesome'
import { useCurrency } from '@/composables/useCurrency'

const { formatBRL } = useCurrency()

const registrations = ref([])
const allRegistrations = ref([])
const events = ref([])
const loading = ref(true)
const selectedEventId = ref('')
const selectedStatus = ref('')
const pagination = ref(null)

onMounted(async () => {
  await Promise.all([loadEvents(), loadRegistrations()])
})

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

async function loadRegistrations(page = 1) {
  try {
    loading.value = true
    const params = {
      per_page: 15,
      page,
      group_by_payment: false, // Não agrupar para admin, mostrar todas as inscrições
    }
    
    if (selectedEventId.value) {
      params.event_id = selectedEventId.value
    }

    const response = await registrationsApi.list(params)
    
    if (response.data) {
      if (Array.isArray(response.data)) {
        // Se os dados vêm agrupados por pagamento, extrair todas as inscrições
        const flatRegistrations = []
        response.data.forEach(item => {
          if (item.registrations && Array.isArray(item.registrations)) {
            // Adicionar informações do pagamento a cada inscrição
            item.registrations.forEach(reg => {
              flatRegistrations.push({
                ...reg,
                payment_id: item.payment_id || item.id,
                payment_method: item.payment_method,
                payment_status_group: item.payment_status,
                total_amount: item.total_amount,
              })
            })
          } else {
            // Se já é uma inscrição individual, adicionar diretamente
            flatRegistrations.push(item)
          }
        })
        allRegistrations.value = flatRegistrations
        pagination.value = null
      } else if (response.data.data) {
        // Se os dados vêm agrupados por pagamento, extrair todas as inscrições
        const flatRegistrations = []
        response.data.data.forEach(item => {
          if (item.registrations && Array.isArray(item.registrations)) {
            // Adicionar informações do pagamento a cada inscrição
            item.registrations.forEach(reg => {
              flatRegistrations.push({
                ...reg,
                payment_id: item.payment_id || item.id,
                payment_method: item.payment_method,
                payment_status_group: item.payment_status,
                total_amount: item.total_amount,
              })
            })
          } else {
            // Se já é uma inscrição individual, adicionar diretamente
            flatRegistrations.push(item)
          }
        })
        allRegistrations.value = flatRegistrations
        pagination.value = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
          from: response.data.from,
          to: response.data.to,
        }
      }
    }

    filterByStatus()
  } catch (error) {
    console.error('Erro ao carregar inscrições:', error)
    alert('Erro ao carregar inscrições: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

async function markAsPaid(registrationId) {
  if (!confirm('Tem certeza que deseja marcar esta inscrição como paga?')) return

  try {
    await registrationsApi.markAsPaid(registrationId)
    await loadRegistrations(pagination.value?.current_page || 1)
    alert('Inscrição marcada como paga com sucesso!')
  } catch (error) {
    console.error('Erro ao marcar como pago:', error)
    alert('Erro ao marcar como pago: ' + (error.response?.data?.message || error.message))
  }
}

function changePage(page) {
  loadRegistrations(page)
}

function filterByStatus() {
  if (!selectedStatus.value) {
    registrations.value = allRegistrations.value
    return
  }
  
  registrations.value = allRegistrations.value.filter(r => {
    return r.payment_status === selectedStatus.value
  })
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getStatusClass(status) {
  const classes = {
    paid: 'bg-green-100 text-green-700',
    pending: 'bg-yellow-100 text-yellow-700',
    canceled: 'bg-red-100 text-red-700',
    refunded: 'bg-gray-100 text-gray-700',
    partially_refunded: 'bg-orange-100 text-orange-700',
    overdue: 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

function getStatusLabel(status) {
  const labels = {
    paid: 'Pago',
    pending: 'Pendente',
    canceled: 'Cancelado',
    refunded: 'Reembolsado',
    partially_refunded: 'Parcialmente Reembolsado',
    overdue: 'Vencido',
  }
  return labels[status] || status
}

function getPriceColor(price) {
  if (price === 0) return 'text-green-600'
  return 'text-blue-600'
}
</script>
