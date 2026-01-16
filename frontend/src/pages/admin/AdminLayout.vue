<template>
  <div>
    <div class="max-w-[1440px] mx-auto flex p-4 sm:p-8">
      <aside class="hidden lg:flex w-64 bg-white shadow-sm flex-col rounded-2xl overflow-hidden border border-gray-200">
        <div class="h-16 flex items-center justify-center border-b">
          <h1 class="text-xl font-bold text-green-600 flex items-center gap-2">
            <font-awesome-icon :icon="['fas', 'shopping-cart']" class="text-green-600" />
            Admin
          </h1>
        </div>

        <nav class="flex-1 p-4 space-y-2 text-gray-700">
          <router-link to="/admin" class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-green-50"
            :class="{ 'bg-green-100 text-green-700 font-semibold': $route.name === 'admin-dashboard' }">
            <font-awesome-icon :icon="['fas', 'chart-bar']" />
            Dashboard
          </router-link>

          <router-link to="/admin/events"
            class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-blue-50"
            :class="{ 'bg-blue-100 text-blue-700 font-semibold': $route.name?.startsWith('admin-event') }">
            <font-awesome-icon :icon="['fas', 'calendar']" />
            Eventos
          </router-link>

          <router-link to="/admin/orders"
            class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-blue-50"
            :class="{ 'bg-blue-100 text-blue-700 font-semibold': $route.name === 'admin-orders' }">
            <font-awesome-icon :icon="['fas', 'shopping-cart']" />
            Pedidos
          </router-link>

          <router-link to="/admin/registrations"
            class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-blue-50"
            :class="{ 'bg-blue-100 text-blue-700 font-semibold': $route.name === 'admin-registrations' }">
            <font-awesome-icon :icon="['fas', 'user-check']" />
            Inscrições
          </router-link>

          <router-link to="/admin/validate"
            class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-blue-50"
            :class="{ 'bg-blue-100 text-blue-700 font-semibold': $route.name === 'admin-validate' }">
            <font-awesome-icon :icon="['fas', 'qrcode']" />
            Validar Inscrições
          </router-link>

        </nav>
      </aside>

      <div class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm ml-0 lg:ml-6 overflow-hidden">
        <header
          class="bg-white shadow-sm border-b border-gray-200 rounded-t-2xl px-4 sm:px-6 py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button class="lg:hidden text-gray-700" @click="sidebarOpen = true">
              <font-awesome-icon :icon="['fas', 'bars']" class="text-xl" />
            </button>

            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2 truncate">
              <font-awesome-icon :icon="['fas', 'angle-right']" class="text-green-600" />
              <span>{{ currentTitle }}</span>
            </h2>
          </div>

          <div class="flex items-center gap-2">
            <div class="flex items-center gap-2">
              <div
                class="bg-green-100 text-green-700 font-bold w-8 h-8 flex items-center justify-center rounded-full uppercase">
                {{ userInitial }}
              </div>

              <span class="text-gray-700 font-medium truncate max-w-[80px] sm:max-w-[160px]">
                {{ firstName }}
              </span>
            </div>

            <button @click="logout"
              class="flex items-center justify-center gap-2 bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition text-sm">
              <font-awesome-icon :icon="['fas', 'sign-out-alt']" />
              <span class="hidden sm:inline">Sair</span>
            </button>
          </div>
        </header>
        <main class="flex-1 overflow-y-auto">
          <router-view />
        </main>
      </div>

      <transition name="fade">
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 flex lg:hidden">
          <div class="fixed inset-0 bg-black opacity-50" @click="sidebarOpen = false"></div>

          <aside
            class="relative w-64 bg-white shadow-md flex flex-col z-50 rounded-r-2xl overflow-hidden border-r border-gray-200">
            <div class="h-16 flex items-center justify-between border-b px-4">
              <h1 class="text-xl font-bold text-green-600 flex items-center gap-2">
                <font-awesome-icon :icon="['fas', 'shopping-cart']" class="text-green-600" />
                Admin
              </h1>
              <button @click="sidebarOpen = false" class="text-gray-600 hover:text-red-600">
                <font-awesome-icon :icon="['fas', 'times-circle']" />
              </button>
            </div>

            <nav class="flex-1 p-4 space-y-2 text-gray-700">
              <router-link to="/admin" class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-green-50"
                :class="{ 'bg-green-100 text-green-700 font-semibold': $route.name === 'admin-dashboard' }"
                @click="sidebarOpen = false">
                <font-awesome-icon :icon="['fas', 'chart-bar']" />
                Dashboard
              </router-link>

              <router-link to="/admin/events"
                class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-blue-50"
                :class="{ 'bg-blue-100 text-blue-700 font-semibold': $route.name?.startsWith('admin-event') }"
                @click="sidebarOpen = false">
                <font-awesome-icon :icon="['fas', 'calendar']" />
                Eventos
              </router-link>

              <router-link to="/admin/orders"
                class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-blue-50"
                :class="{ 'bg-blue-100 text-blue-700 font-semibold': $route.name === 'admin-orders' }"
                @click="sidebarOpen = false">
                <font-awesome-icon :icon="['fas', 'shopping-cart']" />
                Pedidos
              </router-link>

              <router-link to="/admin/registrations"
                class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-blue-50"
                :class="{ 'bg-blue-100 text-blue-700 font-semibold': $route.name === 'admin-registrations' }"
                @click="sidebarOpen = false">
                <font-awesome-icon :icon="['fas', 'user-check']" />
                Inscrições
              </router-link>

              <router-link to="/admin/validate"
                class="flex items-center gap-2 px-4 py-2 rounded-lg transition hover:bg-blue-50"
                :class="{ 'bg-blue-100 text-blue-700 font-semibold': $route.name === 'admin-validate' }"
                @click="sidebarOpen = false">
                <font-awesome-icon :icon="['fas', 'qrcode']" />
                Validar Inscrições
              </router-link>

            </nav>
          </aside>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useRouter, useRoute } from 'vue-router'
import { FontAwesomeIcon } from '@/plugins/fontawesome'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const sidebarOpen = ref(false)

function logout() {
  auth.logout()
  router.push({ name: 'admin-login' })
}

const firstName = computed(() => {
  if (!auth.user?.name) return ''
  return auth.user.name.split(' ')[0]
})

const userInitial = computed(() => {
  if (!auth.user?.name) return '?'
  return auth.user.name.charAt(0).toUpperCase()
})

const currentTitle = computed(() => {
  switch (route.name) {
    case 'admin-dashboard': return 'Dashboard'
    case 'admin-events': return 'Eventos'
    case 'admin-event-new': return 'Novo Evento'
    case 'admin-event-form': return 'Editar Evento'
    case 'admin-orders': return 'Pedidos'
    case 'admin-registrations': return 'Inscrições'
    case 'admin-validate': return 'Validar Inscrições'
    default: return ''
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
