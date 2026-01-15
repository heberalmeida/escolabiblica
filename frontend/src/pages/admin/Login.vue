<template>
  <div class="flex items-center justify-center h-screen bg-gray-100">
    <div class="bg-white shadow rounded p-6 w-96">
      <h1 class="text-xl font-bold mb-4 text-center">Login Admin</h1>
      <form @submit.prevent="submit">
        <input v-model="email" type="email" placeholder="Email"
          class="w-full border p-2 rounded mb-3" />
        <input v-model="password" type="password" placeholder="Senha"
          class="w-full border p-2 rounded mb-3" />
        <button type="submit"
          class="bg-green-600 text-white px-4 py-2 rounded w-full hover:bg-green-700">
          Entrar
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth.store'

const auth = useAuthStore()
const email = ref('')
const password = ref('')

async function submit() {
  try {
    await auth.login(email.value, password.value)
  } catch (e) {
    alert('Credenciais inválidas')
  }
}
</script>
