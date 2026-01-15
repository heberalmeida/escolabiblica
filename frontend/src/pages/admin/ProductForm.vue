<template>
  <div class="bg-gray-50 min-h-screen">
    <div class="max-w-[1200px] mx-auto p-4 sm:p-8">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold flex items-center gap-2 text-gray-800">
          <font-awesome-icon :icon="['fas', isEditing ? 'edit' : 'plus']" class="text-green-600" />
          {{ isEditing ? 'Editar Produto' : 'Novo Produto' }}
        </h1>

        <button type="button"
          class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium"
          @click="router.push({ name: 'admin-products' })">
          Voltar
        </button>
      </div>

      <Loading v-if="loading" />

      <Form v-else :validation-schema="schema" :initial-values="initialValues" :validate-on-mount="false"
        @submit="onSubmit" v-slot="{ errors, validate, values, setTouched, setFieldValue }"
        class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-lg transition space-y-6">
        <section>
          <h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <font-awesome-icon :icon="['fas', 'info-circle']" />
            Informações do Produto
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
              <label class="block mb-1 font-medium">Nome *</label>
              <Field name="name" v-slot="{ field }">
                <input v-bind="field" placeholder="Nome do Produto"
                  class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-green-500"
                  :class="errors.name ? 'border-red-600' : 'border-gray-300'" />
              </Field>
              <ErrorMessage name="name" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Preço *</label>
              <Field name="base_price_display" v-slot="{ field, meta }">
                <CurrencyInput :model-value="field.value" @update:model-value="field.onChange" @blur="field.onBlur"
                  placeholder="Preço (R$)" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-green-500"
                  :class="meta.touched && errors.base_price_display ? 'border-red-600' : 'border-gray-300'" />
              </Field>
              <ErrorMessage name="base_price_display" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Preço Anterior</label>
              <Field name="old_price_display" v-slot="{ field, meta }">
                <CurrencyInput :model-value="field.value" @update:model-value="field.onChange" @blur="field.onBlur"
                  placeholder="Preço Anterior (R$)"
                  class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-green-500"
                  :class="meta.touched && errors.old_price_display ? 'border-red-600' : 'border-gray-300'" />
              </Field>
              <ErrorMessage name="old_price_display" class="text-red-600 text-xs mt-1" />
            </div>

            <div class="flex items-center gap-2 mt-2">
              <div class="flex items-center gap-2 mt-2">
                <Field name="active" v-slot="{ field }">
                  <ToggleSwitch :model-value="field.value" @update:model-value="(val) => field.onChange(val)"
                    label="Produto Ativo" />
                </Field>
              </div>
            </div>
          </div>

          <div class="mt-6">
            <label class="block mb-1 font-medium">Descrição *</label>
            <Field name="description" v-slot="{ field }">
              <textarea v-bind="field" rows="3" placeholder="Descrição do Produto"
                class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-green-500"
                :class="errors.description ? 'border-red-600' : 'border-gray-300'"></textarea>
            </Field>
            <ErrorMessage name="description" class="text-red-600 text-xs mt-1" />
          </div>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <font-awesome-icon :icon="['fas', 'box']" />
            Variações por Tipo
          </h2>

          <FieldArray name="variantGroups" v-slot="{ fields: groups, push, remove }">
            <div v-for="(group, i) in groups" :key="group.key"
              class="border border-gray-300 rounded-xl p-5 mb-6 bg-gray-50 shadow-sm">
              <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-800 text-lg">
                  Tipo: {{ group.value.fit || 'Novo' }}
                </h3>

                <button v-if="groups.length > 1" type="button" class="text-red-600 text-sm hover:underline" @click="() => {
                  if (group.value.variants.some(v => v.id)) {
                    openConfirm('Deseja remover este tipo e suas variações permanentemente?', async () => {
                      for (const v of group.value.variants) {
                        if (v.id) await http.delete(`/admin/variants/${v.id}`).catch(() => { })
                      }
                      remove(i)
                    })
                  } else {
                    remove(i)
                  }
                }">
                  Remover Tipo
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-4 bg-white p-4 rounded-xl border border-gray-200">
                <div>
                  <label class="block text-sm font-medium mb-1 text-gray-700">Tipo *</label>
                  <Field :name="`variantGroups[${i}].fit`" v-slot="{ field }">
                    <input v-bind="field" placeholder="Ex: Normal, Baby Look"
                      class="border p-2 rounded-lg w-full focus:ring-2 focus:ring-green-500 transition"
                      :class="errors[`variantGroups[${i}].fit`] ? 'border-red-600' : 'border-gray-300'" />
                  </Field>
                  <ErrorMessage :name="`variantGroups[${i}].fit`" class="text-red-600 text-xs mt-1" />
                </div>

                <div>
                  <label class="block text-sm font-medium mb-1 text-gray-700">Descrição</label>
                  <Field :name="`variantGroups[${i}].size_description`" v-slot="{ field }">
                    <input v-bind="field" placeholder="Descrição opcional"
                      class="border p-2 rounded-lg w-full focus:ring-2 focus:ring-green-500 border-gray-300" />
                  </Field>
                </div>

                <div>
                  <label class="block text-sm font-medium mb-1 text-gray-700">Preço Padrão (R$)</label>
                  <Field :name="`variantGroups[${i}].price_display`" v-slot="{ field, meta }">
                    <CurrencyInput :model-value="field.value" @update:model-value="field.onChange"
                      placeholder="Preço padrão (R$)"
                      class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-green-500"
                      :class="meta.touched && errors?.variantGroups?.[i]?.price_display ? 'border-red-600' : 'border-gray-300'" />
                  </Field>
                  <ErrorMessage :name="`variantGroups[${i}].price_display`" class="text-red-600 text-xs mt-1" />
                </div>

                <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                  <Field :name="`variantGroups[${i}].active`" v-slot="{ field }">
                    <ToggleSwitch label="Ativar item" :model-value="field.value ?? true"
                      @update:model-value="(val) => field.onChange(val)" />
                  </Field>

                  <Field :name="`variantGroups[${i}].featured`" v-slot="{ field }">
                    <ToggleSwitch label="Destaque" :model-value="field.value ?? false"
                      @update:model-value="(val) => field.onChange(val)" />
                  </Field>
                </div>

                <div class="sm:col-span-2 mt-4">
                  <h4 class="font-medium text-gray-700 mb-2">Imagens do Tipo (máx. 4)</h4>
                  <ProductImageUploader v-model="group.value.images" :max-files="4" @remove="handleRemoveGroupImage"
                    @request-remove="({ file, index }) => handleRequestRemoveGroupImage(group.value, file, index)" />
                </div>
              </div>

              <FieldArray :name="`variantGroups[${i}].variants`"
                v-slot="{ fields: variants, push: pushVariant, remove: removeVariant }">
                <div v-for="(variant, j) in variants" :key="variant.key"
                  class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-4 bg-white p-4 rounded-xl border border-gray-200">
                  <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700">Cor *</label>
                    <Field :name="`variantGroups[${i}].variants[${j}].color`" v-slot="{ field }">
                      <input v-bind="field" placeholder="Ex: Preto"
                        class="border p-2 rounded-lg w-full focus:ring-2 focus:ring-green-500 transition"
                        :class="errors[`variantGroups[${i}].variants[${j}].color`] ? 'border-red-600' : 'border-gray-300'" />
                    </Field>
                    <ErrorMessage :name="`variantGroups[${i}].variants[${j}].color`"
                      class="text-red-600 text-xs mt-1" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700">Tamanho *</label>
                    <Field :name="`variantGroups[${i}].variants[${j}].size`" v-slot="{ field }">
                      <input v-bind="field" placeholder="Ex: M, G, GG"
                        class="border p-2 rounded-lg w-full focus:ring-2 focus:ring-green-500 transition"
                        :class="errors[`variantGroups[${i}].variants[${j}].size`] ? 'border-red-600' : 'border-gray-300'" />
                    </Field>
                    <ErrorMessage :name="`variantGroups[${i}].variants[${j}].size`" class="text-red-600 text-xs mt-1" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700">Estoque *</label>
                    <Field :name="`variantGroups[${i}].variants[${j}].stock`" v-slot="{ field }">
                      <input type="number" v-bind="field" min="0" placeholder="0"
                        class="border p-2 rounded-lg w-full focus:ring-2 focus:ring-green-500 transition"
                        :class="errors[`variantGroups[${i}].variants[${j}].stock`] ? 'border-red-600' : 'border-gray-300'" />
                    </Field>
                    <ErrorMessage :name="`variantGroups[${i}].variants[${j}].stock`"
                      class="text-red-600 text-xs mt-1" />
                  </div>

                  <div class="flex items-end">
                    <button v-if="variants.length > 1" type="button" @click="() => {
                      if (variant.value.id) {
                        openConfirm('Deseja remover esta variação permanentemente?', async () => {
                          await http.delete(`/admin/variants/${variant.value.id}`).catch(() => { })
                          removeVariant(j)
                        })
                      } else {
                        removeVariant(j)
                      }
                    }"
                      class="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 w-full sm:w-auto text-sm font-medium transition">
                      Remover
                    </button>
                  </div>
                </div>

                <div class="mt-3">
                  <button type="button"
                    @click="pushVariant({ color: '', size: '', stock: 0, active: true, featured: false })"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    + Adicionar Cor/Tamanho
                  </button>
                </div>
              </FieldArray>
            </div>

            <button type="button"
              @click="push({ fit: '', size_description: '', price_display: null, active: true, variants: [{ color: '', size: '', stock: 0, active: true, featured: false }] })"
              class="bg-green-600 text-white px-4 py-2 rounded-lg">
              + Adicionar Tipo
            </button>
          </FieldArray>
          <div>
            <ErrorMessage name="variantGroups" class="text-red-600 text-sm mt-2" />
          </div>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <font-awesome-icon :icon="['fas', 'image']" />
            Imagens do Produto
          </h2>
          <ProductImageUploader ref="imageUploader" v-model="images" :max-files="4" @remove="handleRemoveImage"
            @request-remove="handleRequestRemoveImage" />
        </section>

        <div class="flex gap-3 pt-6">
          <button type="button" :disabled="isSubmitDisabled"
            @click="setTouched(true, true); validate().then(r => { if (r.valid) onSubmit(values) })" :class="[
              'px-6 py-2 rounded-lg font-semibold flex items-center gap-2 transition',
              isSubmitDisabled ? 'bg-green-600/50 text-white cursor-not-allowed' : 'bg-green-600 text-white hover:bg-green-700'
            ]">
            <font-awesome-icon :icon="['fas', 'save']" />
            Salvar Produto
          </button>

          <button type="button" class="px-6 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 flex items-center gap-2"
            @click="router.push({ name: 'admin-products' })">
            <font-awesome-icon :icon="['fas', 'times-circle']" />
            Cancelar
          </button>
        </div>
      </Form>

      <ConfirmDialog :open="confirmDialog.open" :message="confirmDialog.message" @confirm="confirmDialogConfirm"
        @cancel="confirmDialogCancel" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import http from '@/api/http'
import CurrencyInput from '@/components/CurrencyInput.vue'
import ProductImageUploader from '@/components/ProductImageUploader.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { Form, Field, FieldArray, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'
import Loading from '@/components/Loading.vue'
import ToggleSwitch from '@/components/ToggleSwitch.vue'

const route = useRoute()
const router = useRouter()
const submitting = ref(false)
const loading = ref(false)
const images = ref([])
const imageUploader = ref(null)
const productId = route.params.id
const isEditing = computed(() => productId && productId !== 'new')

const confirmDialog = ref({ open: false, message: '', onConfirm: null })
function openConfirm(message, onConfirm) {
  confirmDialog.value = { open: true, message, onConfirm }
}
function confirmDialogConfirm() {
  confirmDialog.value.onConfirm?.()
  confirmDialog.value.open = false
}
function confirmDialogCancel() {
  confirmDialog.value.open = false
}

async function handleRemoveImage(file) {
  if (!file?.id) return
  try {
    await http.delete(`/admin/product-images/${file.id}`)
  } catch (e) {
    console.error('Erro ao excluir imagem do servidor:', e)
  }
}

const schema = yup.object({
  name: yup.string().required('O nome é obrigatório'),
  description: yup.string().required('A descrição é obrigatória'),
  base_price_display: yup.number().typeError('O preço é obrigatório').required('O preço é obrigatório').min(0.01, 'O preço deve ser maior que zero'),
  old_price_display: yup.number().nullable(),
  active: yup.boolean(),

  variantGroups: yup.array().of(
    yup.object({
      fit: yup.string().required('O tipo é obrigatório'),
      size_description: yup.string().nullable(),
      price_display: yup.number().nullable().transform((v, orig) => (orig === '' ? null : v)).typeError('O preço deve ser numérico'),
      active: yup.boolean().default(true),

      variants: yup.array().of(
        yup.object({
          color: yup.string().required('A cor é obrigatória'),
          size: yup.string().required('O tamanho é obrigatório'),
          stock: yup.number()
            .transform((v, orig) => (orig === '' ? null : v))
            .typeError('O estoque deve ser numérico')
            .required('O estoque é obrigatório')
            .min(0, 'O estoque não pode ser negativo')
        })
      ).min(1, 'Adicione pelo menos uma cor/tamanho')
    })
  ).min(1, 'É necessário adicionar pelo menos um tipo (fit)')
})

const initialValues = ref({
  name: '',
  description: '',
  base_price_display: null,
  old_price_display: null,
  active: true,
  variantGroups: [
    {
      fit: '',
      size_description: '',
      price_display: null,
      active: true,
      variants: [
        { color: '', size: '', stock: 0, active: true, featured: false, images: [] }
      ]
    }
  ]
})

const variantGroups = ref([...initialValues.value.variantGroups])

watch(() => initialValues.value.variantGroups, (newVal) => {
  variantGroups.value = JSON.parse(JSON.stringify(newVal))
}, { deep: true })

onMounted(async () => {
  if (isEditing.value) {
    loading.value = true
    try {
      const { data } = await http.get(`/admin/products/${productId}`)

      Object.assign(initialValues.value, {
        name: data.name,
        description: data.description,
        base_price_display: data.base_price / 100,
        old_price_display: data.old_price ? data.old_price / 100 : null,
        active: Boolean(data.active),
        variantGroups: groupVariants(data.variants || []).map(group => ({
          ...group,
          active: Boolean(group.active),
          images: group.variants.flatMap(v => v.images || []),
          variants: group.variants.map(v => ({
            ...v,
            active: Boolean(v.active)
          }))
        }))
      })

      images.value = data.images?.map(img => ({
        id: img.id,
        url: img.url,
        previewUrl: img.url
      })) || []
    } catch (e) {
      console.error('Erro ao carregar produto:', e)
    } finally {
      loading.value = false
    }
  } else {
    initialValues.value.variantGroups = [
      {
        fit: '',
        size_description: '',
        price_display: null,
        active: true,
        variants: [
          { color: '', size: '', stock: 0, active: true, featured: false }
        ]
      }
    ]

  }
})

function groupVariants(variants = []) {
  const groups = {}

  for (const v of variants) {
    if (!groups[v.fit]) {
      groups[v.fit] = {
        fit: v.fit || '',
        size_description: v.size_description || '',
        price_display: v.price_override !== null ? v.price_override / 100 : null,
        active: v.active ?? true,
        variants: []
      }
    }

    groups[v.fit].variants.push({
      id: v.id ?? null,
      color: v.color || '',
      size: v.size || '',
      stock: Number.isFinite(v.stock) ? v.stock : 0,
      active: v.active ?? true,
      featured: v.featured ?? true,
      images: (v.images || []).map(img => ({
        id: img.id,
        url: img.url,
        previewUrl: img.url
      }))
    })
  }

  return Object.values(groups)
}

function handleRequestRemoveImage({ file, index }) {
  openConfirm('Deseja remover esta imagem permanentemente?', async () => {
    try {
      if (file.id) {
        await http.delete(`/admin/product-images/${file.id}`)
      }

      images.value = images.value.filter((_, i) => i !== index)
    } catch (e) {
      console.error('Erro ao remover imagem:', e)
    }
  })
}


async function handleRemoveGroupImage(payloadOrFile) {
  const file = payloadOrFile?.file ?? payloadOrFile
  if (!file?.id) return

  try {
    await http.delete(`/admin/variant-images/${file.id}`)

    for (const group of initialValues.value.variantGroups) {
      const idx = group.images?.findIndex(img => img.id === file.id)
      if (idx !== -1) {
        group.images.splice(idx, 1)
        break
      }
    }
  } catch (e) {
    console.error('Erro ao excluir imagem do grupo:', e)
  }
}

function handleRequestRemoveGroupImage(group, file, index) {
  openConfirm('Deseja remover esta imagem permanentemente?', async () => {
    try {
      if (file.id) {
        await http.delete(`/admin/variant-images/${file.id}`)
      }

      const updatedImages = [...(group.images || [])]
      updatedImages.splice(index, 1)
      group.images = updatedImages

      const targetGroup = initialValues.value.variantGroups.find(g => g.fit === group.fit)
      if (targetGroup) {
        targetGroup.images = [...updatedImages]
      }
    } catch (e) {
      console.error('Erro ao remover imagem do grupo:', e)
    }
  })
}

async function onSubmit(values) {
  if (submitting.value || isProcessingImages.value) return
  submitting.value = true

  const flattenedVariants = values.variantGroups.flatMap(group =>
    group.variants.map((v, variantIndex) => ({
      id: v.id ?? null,
      color: v.color,
      fit: group.fit,
      size: v.size,
      size_description: group.size_description,
      price_override: group.price_display !== null ? Math.round(group.price_display * 100) : null,
      stock: v.stock ?? 0,
      active: group.active ?? true,
      featured: group.featured ?? false,
      images: (v.images || []).map((img, index) => ({
        id: img.id ?? null,
        dataUrl: img.dataUrl || null,
        url: img.url || null,
        position: index
      }))
    }))
  )

  const groupImages = values.variantGroups.map((group, i) => ({
    fit: group.fit,
    images: (group.images || []).map((img, index) => {
      if (img.dataUrl) {
        return { id: img.id ?? null, dataUrl: img.dataUrl, position: index }
      }

      if (img.id && /^https?:\/\//.test(img.url)) {
        return { id: img.id, position: index }
      }

      return { id: img.id ?? null, url: img.url ?? null, position: index }
    })
  }))

  const payload = {
    name: values.name,
    description: values.description,
    base_price: Math.round((values.base_price_display || 0) * 100),
    old_price: values.old_price_display ? Math.round(values.old_price_display * 100) : null,
    active: values.active,
    variants: flattenedVariants,
    group_images: groupImages,
    images: images.value.map((img, index) => {
      if (img.dataUrl) {
        return {
          id: img.id ?? null,
          dataUrl: img.dataUrl,
          position: index
        }
      }

      if (img.id && /^https?:\/\//.test(img.url)) {
        return {
          id: img.id,
          position: index
        }
      }

      return {
        id: img.id ?? null,
        url: img.url ?? null,
        position: index
      }
    })

  }

  try {
    if (isEditing.value) {
      await http.put(`/admin/products/${productId}`, payload)
    } else {
      await http.post('/admin/products', payload)
    }
    router.push({ name: 'admin-products' })
  } catch (error) {
    console.error('Erro salvar produto:', error?.response?.data)
    alert(error?.response?.data?.message || 'Erro ao salvar produto')
  } finally {
    submitting.value = false
  }
}

const isProcessingImages = computed(() => imageUploader.value?.isAllUploading?.() ?? false)
const isSubmitDisabled = computed(() => submitting.value || isProcessingImages.value)
</script>
