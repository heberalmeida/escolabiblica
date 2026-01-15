<template>
  <div class="p-4 sm:p-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Eventos</h1>
      <router-link to="/admin/events/new"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition">
        <font-awesome-icon :icon="['fas', 'plus']" />
        Novo Evento
      </router-link>
    </div>

    <div v-if="loading" class="flex justify-center py-10">
      <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
          <div class="flex items-center justify-between">
            <span class="text-lg font-bold" :class="event.price === 0 ? 'text-green-600' : 'text-blue-600'">
              {{ event.price === 0 ? 'Gratuito' : formatBRL(event.price) }}
            </span>
            <span class="px-2 py-1 rounded text-xs"
              :class="event.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
              {{ event.active ? 'Ativo' : 'Inativo' }}
            </span>
          </div>
          <div class="mt-4 flex gap-2">
            <router-link :to="`/admin/events/${event.id}`"
              class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-center text-sm transition">
              Editar
            </router-link>
            <button @click="deleteEvent(event.id)"
              class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm transition">
              Excluir
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!loading && events.length === 0" class="text-center py-10 text-gray-500">
      Nenhum evento cadastrado.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { eventsApi } from '@/api/events'
import { FontAwesomeIcon } from '@/plugins/fontawesome'

const events = ref([])
const loading = ref(true)

onMounted(async () => {
  await loadEvents()
})

async function loadEvents() {
  try {
    loading.value = true
    const response = await eventsApi.list()
    
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
  } catch (error) {
    console.error('Erro ao carregar eventos:', error)
    console.error('Detalhes:', error.response?.data)
    alert('Erro ao carregar eventos: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

async function deleteEvent(id) {
  if (!confirm('Tem certeza que deseja excluir este evento?')) return

  try {
    await eventsApi.delete(id)
    await loadEvents()
  } catch (error) {
    console.error('Erro ao excluir evento:', error)
    alert('Erro ao excluir evento')
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
  return `${import.meta.env.VITE_API_BASE_URL}/storage/${path}`
}
</script>
