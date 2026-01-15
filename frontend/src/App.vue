<template>
  <header class="bg-white/95 shadow-sm sticky top-0 z-50">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-8 py-4 flex justify-between items-center">
      <div class="flex items-center gap-6">
        <RouterLink to="/" class="text-lg font-semibold text-gray-700 hover:text-green-600 transition">
          Home
        </RouterLink>
        <RouterLink to="/registrations" class="text-lg font-semibold text-gray-700 hover:text-blue-600 transition">
          Minhas Inscrições
        </RouterLink>

        <RouterLink
          v-if="auth.isAuthenticated"
          to="/admin"
          class="text-lg font-semibold text-green-600 hover:underline"
        >
          Admin
        </RouterLink>
      </div>

      <div class="flex items-center gap-4">
        <RouterLink
          to="/cart"
          class="flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-green-500 text-green-600 hover:bg-green-50 transition"
        >
          <font-awesome-icon :icon="['fas', 'shopping-cart']" />
          <span class="hidden sm:inline font-medium">Carrinho</span>
          <span
            v-if="cartCount"
            class="bg-green-600 text-white font-bold text-xs px-2 py-0.5 rounded-full"
          >
            {{ cartCount }}
          </span>
        </RouterLink>
      </div>
    </div>
  </header>

  <main class="bg-gray-50">
    <RouterView />
  </main>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart.store'
import { useAuthStore } from '@/stores/auth.store'
import { FontAwesomeIcon } from '@/plugins/fontawesome'

const cart = useCartStore()
const auth = useAuthStore()

onMounted(() => {
  auth.fetchUser()
})

const cartCount = computed(() =>
  cart.items.reduce((total, item) => total + item.quantity, 0)
)
</script>

<style>
body {
      background-color: #fbf9fa;
}
</style>
