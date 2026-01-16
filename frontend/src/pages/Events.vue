<template>
  <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
    <h1 class="text-3xl font-bold mb-6 text-center sm:text-left flex items-center gap-3 text-gray-800">
      <font-awesome-icon :icon="['fas', 'calendar']" class="text-blue-600" />
      Eventos Disponíveis
    </h1>

    <div v-if="loading" class="flex justify-center py-10">
      <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else>
      <div v-if="events.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="event in events" :key="event.id"
          class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
          <div v-if="event.image" class="h-48 bg-gray-200 overflow-hidden">
            <img :src="getImageUrl(event.image)" :alt="event.name" class="w-full h-full object-cover" />
          </div>
          <div v-else class="h-48 bg-blue-100 flex items-center justify-center">
            <font-awesome-icon :icon="['fas', 'calendar']" class="text-6xl text-blue-300" />
          </div>
          <div class="p-4">
            <h3 class="font-bold text-lg text-gray-800 mb-2">{{ event.name }}</h3>
            <p v-if="event.description" class="text-sm text-gray-600 mb-2 line-clamp-2">{{ event.description }}</p>
            <div class="text-sm text-gray-500 mb-2">
              <div>{{ formatDate(event.start_date) }} - {{ formatDate(event.end_date) }}</div>
            </div>
            <div class="flex items-center justify-between mb-4">
              <span class="text-lg font-bold" :class="event.price === 0 ? 'text-green-600' : 'text-blue-600'">
                {{ event.price === 0 ? 'Gratuito' : formatBRL(event.price) }}
              </span>
              <span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-700">
                Inscrições até {{ formatDate(event.end_date) }}
              </span>
            </div>
            <button
              @click="addToCart(event)"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center transition">
              Adicionar ao Carrinho
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-10 text-gray-500">
        <font-awesome-icon :icon="['fas', 'calendar-times']" class="text-6xl text-gray-300 mb-4" />
        <p class="text-xl">Nenhum evento disponível para inscrição no momento.</p>
        <p class="text-sm mt-2">Novos eventos serão divulgados em breve.</p>
      </div>
    </div>
    <Toast ref="toastRef" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { eventsApi } from '@/api/events'
import { FontAwesomeIcon } from '@/plugins/fontawesome'
import { useCartStore } from '@/stores/cart.store'
import Toast from '@/components/Toast.vue'

const cart = useCartStore()
const toastRef = ref(null)

const events = ref([])
const loading = ref(true)

onMounted(async () => {
  await loadEvents()
})

async function loadEvents() {
  try {
    loading.value = true
    // Buscar apenas eventos disponíveis (end_date >= hoje)
    const response = await eventsApi.list({ available: 'true', active: 'true' })

    // A API retorna paginação
    if (response.data) {
      if (Array.isArray(response.data)) {
        events.value = response.data
      } else if (response.data.data) {
        events.value = response.data.data
      } else {
        events.value = []
      }
    } else {
      events.value = []
    }

    console.log('Eventos carregados:', events.value)
  } catch (error) {
    console.error('Erro ao carregar eventos:', error)
    console.error('Detalhes:', error.response?.data)
    events.value = []
  } finally {
    loading.value = false
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('pt-BR')
}

function formatBRL(cents) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function getImageUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  if (path.startsWith('data:')) return path
  // Remover /api/v1 do baseURL se existir, pois a rota /storage está em web.php
  const baseUrl = import.meta.env.VITE_API_BASE_URL.replace(/\/api\/v1$/, '')
  return `${baseUrl}/storage/${path}`
}

function addToCart(event) {
  cart.addEvent(event, 1)
  toastRef.value?.open(`${event.name} adicionado ao carrinho!`, 'success')
}
</script>
