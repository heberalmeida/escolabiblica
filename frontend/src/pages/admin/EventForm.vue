<template>
  <div class="p-4 sm:p-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">{{ isEditing ? 'Editar Evento' : 'Novo Evento' }}</h1>
      <button @click="$router.push('/admin/events')"
        class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
        Voltar
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-10">
      <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <Form v-else :validation-schema="schema" :initial-values="initialValues" @submit="onSubmit"
      class="bg-white p-6 rounded-lg shadow-md space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
          <label class="block mb-1 font-medium">Nome do Evento *</label>
          <Field name="name" v-slot="{ field, meta }">
            <input v-bind="field" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500"
              :class="meta.touched && meta.errors ? 'border-red-600' : 'border-gray-300'" />
          </Field>
          <ErrorMessage name="name" class="text-red-600 text-xs mt-1" />
        </div>

        <div class="md:col-span-2">
          <label class="block mb-1 font-medium">Descrição/Tema</label>
          <Field name="description" v-slot="{ field }">
            <textarea v-bind="field" rows="3"
              class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500"></textarea>
          </Field>
        </div>

        <div>
          <label class="block mb-1 font-medium">Data de Início *</label>
          <Field name="start_date" type="date" v-slot="{ field }">
            <input v-bind="field" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" />
          </Field>
          <ErrorMessage name="start_date" class="text-red-600 text-xs mt-1" />
        </div>

        <div>
          <label class="block mb-1 font-medium">Data de Término *</label>
          <Field name="end_date" type="date" v-slot="{ field }">
            <input v-bind="field" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" />
          </Field>
          <ErrorMessage name="end_date" class="text-red-600 text-xs mt-1" />
        </div>

        <div>
          <label class="block mb-1 font-medium">Preço (em centavos) *</label>
          <Field name="price" type="number" v-slot="{ field }">
            <input v-bind="field" min="0" step="1"
              class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" />
          </Field>
          <ErrorMessage name="price" class="text-red-600 text-xs mt-1" />
          <p class="text-xs text-gray-500 mt-1">Ex: 50000 = R$ 500,00 (0 = Gratuito)</p>
        </div>

        <div>
          <label class="block mb-1 font-medium">Imagem do Evento</label>
          <input type="file" accept="image/*" @change="handleImageChange" class="w-full border rounded-lg p-2" />
          <div v-if="imagePreview" class="mt-2">
            <img :src="imagePreview" alt="Preview" class="max-w-xs rounded-lg" />
          </div>
        </div>

        <div class="md:col-span-2">
          <label class="block mb-2 font-medium">Métodos de Pagamento *</label>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <Field name="payment_methods" type="checkbox" value="PIX" v-slot="{ field }">
                <input type="checkbox" v-bind="field" value="PIX" />
              </Field>
              <span>PIX</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <Field name="payment_methods" type="checkbox" value="BOLETO" v-slot="{ field }">
                <input type="checkbox" v-bind="field" value="BOLETO" />
              </Field>
              <span>Boleto</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <Field name="payment_methods" type="checkbox" value="CREDIT_CARD" v-slot="{ field }">
                <input type="checkbox" v-bind="field" value="CREDIT_CARD" />
              </Field>
              <span>Cartão</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <Field name="payment_methods" type="checkbox" value="FREE" v-slot="{ field }">
                <input type="checkbox" v-bind="field" value="FREE" />
              </Field>
              <span>Gratuito</span>
            </label>
          </div>
        </div>

        <div>
          <label class="flex items-center gap-2 cursor-pointer">
            <Field name="active" type="checkbox" v-slot="{ field }">
              <input type="checkbox" v-bind="field" :checked="field.value" />
            </Field>
            <span>Evento Ativo</span>
          </label>
        </div>
      </div>

      <div class="flex gap-4">
        <button type="submit" :disabled="submitting"
          class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg disabled:opacity-50">
          {{ submitting ? 'Salvando...' : 'Salvar' }}
        </button>
        <button type="button" @click="$router.push('/admin/events')"
          class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg">
          Cancelar
        </button>
      </div>
    </Form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Form, Field, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'
import { eventsApi } from '@/api/events'

const route = useRoute()
const router = useRouter()
const isEditing = computed(() => !!route.params.id)
const loading = ref(false)
const submitting = ref(false)
const imagePreview = ref(null)
const imageFile = ref(null)

const schema = yup.object({
  name: yup.string().required('Nome é obrigatório'),
  start_date: yup.date().required('Data de início é obrigatória'),
  end_date: yup.date().required('Data de término é obrigatória').min(yup.ref('start_date'), 'Data de término deve ser após data de início'),
  price: yup.number().required('Preço é obrigatório').min(0),
})

const initialValues = ref({
  name: '',
  description: '',
  start_date: '',
  end_date: '',
  price: 0,
  active: true,
  payment_methods: [],
})

onMounted(async () => {
  if (isEditing.value) {
    await loadEvent()
  }
})

async function loadEvent() {
  try {
    loading.value = true
    const { data } = await eventsApi.get(route.params.id)
    initialValues.value = {
      name: data.name,
      description: data.description || '',
      start_date: data.start_date,
      end_date: data.end_date,
      price: data.price,
      active: data.active,
      payment_methods: data.payment_methods?.map(pm => pm.method) || [],
    }
    if (data.image) {
      imagePreview.value = getImageUrl(data.image)
    }
  } catch (error) {
    console.error('Erro ao carregar evento:', error)
    alert('Erro ao carregar evento')
  } finally {
    loading.value = false
  }
}

function handleImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    imageFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

async function onSubmit(values) {
  try {
    submitting.value = true
    const formData = { ...values }

    if (imageFile.value) {
      formData.image = imagePreview.value // base64
    } else if (imagePreview.value && imagePreview.value.startsWith('data:')) {
      formData.image = imagePreview.value
    }

    if (isEditing.value) {
      await eventsApi.update(route.params.id, formData)
    } else {
      await eventsApi.create(formData)
    }

    router.push('/admin/events')
  } catch (error) {
    console.error('Erro ao salvar evento:', error)
    alert('Erro ao salvar evento')
  } finally {
    submitting.value = false
  }
}

function getImageUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  if (path.startsWith('data:')) return path
  return `${import.meta.env.VITE_API_BASE_URL}/storage/${path}`
}
</script>
