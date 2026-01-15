<template>
  <div class="bg-gray-50 min-h-screen">
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="flex justify-between items-center mb-10 flex-col sm:flex-row gap-4 sm:gap-0">
        <h1
          class="text-3xl font-bold flex-1 text-center sm:text-left flex items-center gap-3 text-gray-800">
          <font-awesome-icon :icon="['fas', 'box']" class="text-green-600" />
          Produtos
        </h1>

        <router-link
          to="/admin/products/new"
          class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition w-full sm:w-auto justify-center">
          <font-awesome-icon :icon="['fas', 'plus']" />
          Novo Produto
        </router-link>
      </div>

      <div v-if="loading">
        <Loading />
      </div>

      <div v-else>
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden mb-10">
          <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'list']" class="text-green-600" />
              Lista de Produtos
            </h2>
            <span v-if="products.length" class="text-sm text-gray-500">
              Total: {{ products.length }}
            </span>
          </div>

          <div class="p-6">
            <div v-if="products.length" class="hidden sm:block overflow-x-auto">
              <table class="w-full text-sm border rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-gray-700">
                  <tr>
                    <th class="p-3 border text-left">#</th>
                    <th class="p-3 border text-left">Nome</th>
                    <th class="p-3 border text-left">Preço</th>
                    <th class="p-3 border text-center">Ativo</th>
                    <th class="p-3 border text-center">Destaque</th>
                    <th class="p-3 border text-center">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in products" :key="p.id" class="hover:bg-gray-50 transition">
                    <td class="p-3 border text-gray-600 text-center">{{ p.id }}</td>
                    <td class="p-3 border text-gray-800 truncate max-w-[200px]">{{ p.name }}</td>
                    <td class="p-3 border font-semibold text-gray-900">{{ formatBRL(p.base_price) }}</td>
                    <td class="p-3 border text-center">
                      <span
                        :class="getProductStatus(p) ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'">
                        <font-awesome-icon
                          :icon="getProductStatus(p) ? ['fas', 'check-circle'] : ['fas', 'times-circle']"
                          class="mr-1" />
                        {{ getProductStatus(p) ? 'Sim' : 'Não' }}
                      </span>
                    </td>
                    <td class="p-3 border text-center">
                      <span
                        :class="getProductFeatured(p) ? 'text-yellow-500 font-semibold' : 'text-gray-500 font-semibold'">
                        <font-awesome-icon
                          :icon="getProductFeatured(p) ? ['fas', 'check-circle'] : ['fas', 'times-circle']"
                          class="mr-1" />
                        {{ getProductFeatured(p) ? 'Sim' : 'Não' }}
                      </span>
                    </td>
                    <td class="p-3 border text-center space-x-2">
                      <router-link
                        :to="`/admin/products/${p.id}`"
                        class="inline-flex items-center gap-1 bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition">
                        <font-awesome-icon :icon="['fas', 'edit']" />
                        Editar
                      </router-link>

                      <button
                        @click="confirmRemove(p)"
                        class="inline-flex items-center gap-1 bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition">
                        <font-awesome-icon :icon="['fas', 'trash']" />
                        Remover
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="products.length" class="block sm:hidden space-y-4">
              <div
                v-for="p in products"
                :key="p.id"
                class="border rounded-xl shadow-sm p-4 bg-white hover:shadow-md transition">
                <div class="flex justify-between items-center mb-2">
                  <h3 class="font-bold text-gray-800 truncate">{{ p.name }}</h3>
                  <span class="text-xs text-gray-500">#{{ p.id }}</span>
                </div>

                <p class="text-gray-700 text-sm mb-1">
                  <strong>Preço:</strong> {{ formatBRL(p.base_price) }}
                </p>

                <div class="flex justify-between items-center text-sm mt-2">
                  <span
                    :class="getProductStatus(p) ? 'text-green-600 font-medium' : 'text-red-600 font-medium'">
                    <font-awesome-icon
                      :icon="getProductStatus(p) ? ['fas', 'check-circle'] : ['fas', 'times-circle']"
                      class="mr-1" />
                    {{ getProductStatus(p) ? 'Ativo' : 'Inativo' }}
                  </span>

                  <span
                    :class="getProductFeatured(p) ? 'text-yellow-500 font-medium' : 'text-gray-500 font-medium'">
                    <font-awesome-icon
                      :icon="getProductFeatured(p) ? ['fas', 'check-circle'] : ['fas', 'times-circle']"
                      class="mr-1" />
                    {{ getProductFeatured(p) ? 'Destaque' : 'Comum' }}
                  </span>
                </div>

                <div class="flex gap-2 mt-4">
                  <router-link
                    :to="`/admin/products/${p.id}`"
                    class="flex-1 inline-flex items-center justify-center gap-1 bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                    <font-awesome-icon :icon="['fas', 'edit']" />
                    Editar
                  </router-link>

                  <button
                    @click="confirmRemove(p)"
                    class="flex-1 inline-flex items-center justify-center gap-1 bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition text-sm">
                    <font-awesome-icon :icon="['fas', 'trash']" />
                    Remover
                  </button>
                </div>
              </div>
            </div>

            <p v-else class="text-gray-500 text-sm text-center py-6">
              Nenhum produto cadastrado.
            </p>
          </div>
        </div>

        <Pagination
          v-if="pagination?.last_page > 1"
          :currentPage="page"
          :lastPage="pagination.last_page"
          @change="goToPage"
        />
      </div>
    </div>

    <ConfirmDialog
      :open="confirmDialog.open"
      :message="confirmDialog.message"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import http from '@/api/http'
import { useCurrency } from '@/composables/useCurrency'
import Loading from '@/components/Loading.vue'
import Pagination from '@/components/Pagination.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { FontAwesomeIcon } from '@/plugins/fontawesome'
import { db, ref as dbRef, onValue } from '@/firebase'

const { formatBRL } = useCurrency()
const products = ref([])
const pagination = ref(null)
const loading = ref(false)
const page = ref(1)
let unsubscribe = null

const confirmDialog = ref({
  open: false,
  message: '',
  id: null
})

function confirmRemove(p) {
  confirmDialog.value = {
    open: true,
    message: `Tem certeza que deseja remover o produto <strong>${p.name}</strong>?`,
    id: p.id
  }
}

function handleCancel() {
  confirmDialog.value.open = false
}

async function handleConfirm() {
  const id = confirmDialog.value.id
  confirmDialog.value.open = false

  try {
    await http.delete(`/admin/products/${id}`)
    products.value = products.value.filter(p => p.id !== id)
    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Produto removido com sucesso!' } }))
  } catch (error) {
    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: error.response?.data?.message || 'Erro ao remover o produto.' } }))
  }
}

function getProductStatus(p) {
  if (!p.active) return false
  if (!Array.isArray(p.variants) || p.variants.length === 0) return false
  return p.variants.some(v => v.active)
}

function getProductFeatured(p) {
  if (!Array.isArray(p.variants) || p.variants.length === 0) return false
  return p.variants.some(v => v.featured)
}

async function loadProducts() {
  loading.value = true
  try {
    const { data } = await http.get('/admin/products', { params: { page: page.value } })
    products.value = data.data ?? data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page
    }
  } catch (error) {
    console.error('Erro ao carregar produtos:', error)
  } finally {
    loading.value = false
  }
}

function goToPage(newPage) {
  page.value = newPage
  loadProducts()
}

onMounted(() => {
  loadProducts()
  const empresaId = import.meta.env.VITE_EMPRESA_ID || 'default'
  const productsRef = dbRef(db, `webhooks/products_${empresaId}/last_updated`)
  let first = true
  unsubscribe = onValue(productsRef, (snapshot) => {
    if (snapshot.exists()) {
      if (first) {
        first = false
        return
      }
      loadProducts()
    }
  })
})

onUnmounted(() => {
  if (unsubscribe) {
    unsubscribe()
    unsubscribe = null
  }
})
</script>
