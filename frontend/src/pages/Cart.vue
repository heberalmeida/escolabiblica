<template>
  <div>
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold flex items-center gap-2 text-gray-800">
          <font-awesome-icon :icon="['fas', 'shopping-cart']" class="text-green-600" />
          Carrinho
        </h1>
      </div>

      <div v-if="loading" class="flex justify-center items-center py-16">
        <div class="w-12 h-12 border-4 border-green-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div
        v-else-if="!cart.items.length"
        class="text-center text-gray-500 py-16 bg-white rounded-2xl shadow-sm"
      >
        <font-awesome-icon :icon="['fas', 'box-open']" class="text-5xl text-gray-400 mb-3" />
        <p class="text-lg font-medium">Seu carrinho está vazio.</p>
        <p class="text-sm text-gray-400 mt-1">Adicione produtos para começar sua compra.</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="item in cart.items"
          :key="item.type === 'event' ? `event-${item.eventId}` : `product-${item.variantId}`"
          class="bg-white rounded-2xl shadow-sm hover:shadow-md transition p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-6"
        >
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 sm:w-28 sm:h-28 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
              <img
                v-if="item.image"
                :src="getImageUrl(item.image)"
                class="object-cover w-full h-full"
              />
              <font-awesome-icon
                v-else
                :icon="item.type === 'event' ? ['fas', 'calendar'] : ['fas', 'image']"
                :class="item.type === 'event' ? 'text-blue-400 text-3xl' : 'text-gray-400 text-2xl'"
              />
            </div>

            <div class="flex flex-col justify-center">
              <div class="font-semibold text-gray-800 text-base sm:text-lg leading-tight">
                {{ item.name }}
              </div>
              <div v-if="item.type === 'product'" class="text-sm text-gray-500">{{ item.variantName }}</div>
              <div v-if="item.type === 'event'" class="text-sm text-gray-500">
                <div v-if="item.start_date && item.end_date">
                  {{ formatDate(item.start_date) }} - {{ formatDate(item.end_date) }}
                </div>
                <div v-if="item.description" class="line-clamp-1">{{ item.description }}</div>
              </div>
            </div>
          </div>

          <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-end w-full sm:w-auto gap-4 sm:gap-6 mt-2 sm:mt-0"
          >
            <div class="flex items-center justify-center border rounded-lg shadow-sm overflow-hidden">
              <button
                @click="decrease(item)"
                class="px-3 py-2 text-lg hover:bg-gray-100 transition"
              >
                −
              </button>
              <span class="px-4 py-2 text-lg font-medium min-w-[48px] text-center">
                {{ item.quantity }}
              </span>
              <button
                @click="increase(item)"
                class="px-3 py-2 text-lg hover:bg-gray-100 transition"
              >
                +
              </button>
            </div>

            <div class="text-right sm:text-left flex sm:block flex-col items-center sm:items-end gap-1">
              <div class="font-bold text-green-700 text-lg">
                {{ formatBRL(item.price * item.quantity) }}
              </div>
              <button
                @click="removeItem(item)"
                class="flex items-center justify-center gap-1 text-sm text-red-600 hover:text-red-700 transition"
              >
                <font-awesome-icon :icon="['fas', 'trash']" />
                Remover
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="cart.items.length"
        class="mt-8 bg-white rounded-2xl shadow-sm p-6 flex flex-col sm:flex-row justify-between items-center gap-6"
      >
        <button
          @click="askClearAll"
          class="flex items-center gap-2 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 text-sm w-full sm:w-auto transition"
        >
          <font-awesome-icon :icon="['fas', 'trash']" />
          Limpar Tudo
        </button>

        <div class="flex flex-col sm:items-end gap-1 text-center sm:text-right">
          <div class="text-sm text-gray-500">Total da compra</div>
          <div class="text-3xl font-bold text-green-700 tabular-nums">
            {{ formatBRL(cart.total) }}
          </div>
        </div>
      </div>

      <div
        v-if="cart.items.length"
        class="mt-6 flex flex-col items-center sm:items-end"
      >
        <Form
          :validation-schema="schema"
          @submit="goCheckout"
          :initial-values="{ total: cart.total }"
          v-slot="{ setFieldValue }"
          class="flex flex-col"
        >
          <Field name="total" type="hidden" :value="cart.total" />
          <div class="flex flex-col items-end">
            <ErrorMessage name="total" class="text-red-600 text-sm mb-2" />
          <div>
            <button
            type="submit"
            class="flex items-center justify-center gap-2 bg-green-600 text-white px-8 py-3 rounded-lg shadow hover:bg-green-700 transition w-full sm:w-auto text-lg font-medium"
            @click="setFieldValue('total', cart.total)"
          >
            <font-awesome-icon :icon="['fas', 'credit-card']" />
            Finalizar Compra
          </button>
          </div>
          </div>
        </Form>
      </div>

      <ConfirmDialog
        :open="dialog.open"
        :message="dialog.message"
        @cancel="dialog.open = false"
        @confirm="confirmAction"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCartStore } from '@/stores/cart.store'
import { useCurrency } from '@/composables/useCurrency'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { Form, Field, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'
import { useRouter } from 'vue-router'
import { FontAwesomeIcon } from '@/plugins/fontawesome'

const { formatBRL } = useCurrency()
const cart = useCartStore()
const loading = ref(true)
const router = useRouter()

const dialog = ref({
  open: false,
  message: '',
  action: null
})

onMounted(() => {
  setTimeout(() => {
    loading.value = false
  }, 600)
})

function increase(item) {
  const id = item.type === 'event' ? item.eventId : item.variantId
  cart.updateQuantity(id, item.quantity + 1, item.type)
}

function decrease(item) {
  if (item.quantity > 1) {
    const id = item.type === 'event' ? item.eventId : item.variantId
    cart.updateQuantity(id, item.quantity - 1, item.type)
  }
}

function removeItem(item) {
  const id = item.type === 'event' ? item.eventId : item.variantId
  cart.removeItem(id, item.type)
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-BR')
}

function getImageUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  if (path.startsWith('data:')) return path
  // Remover /api/v1 do baseURL se existir, pois a rota /storage está em web.php
  const baseUrl = import.meta.env.VITE_API_BASE_URL.replace(/\/api\/v1$/, '')
  return `${baseUrl}/storage/${path}`
}

function askClearAll() {
  dialog.value = {
    open: true,
    message: 'Deseja realmente limpar todo o carrinho?',
    action: () => cart.clear()
  }
}

function confirmAction() {
  if (dialog.value.action) dialog.value.action()
  dialog.value.open = false
}

const schema = yup.object({
  total: yup.number().min(500, 'O valor mínimo para finalizar a compra é R$ 5,00')
})

function goCheckout() {
  router.push('/checkout')
}
</script>

<style>
.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>
