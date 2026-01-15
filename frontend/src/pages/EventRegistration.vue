<template>
  <div class="max-w-4xl mx-auto p-4 sm:p-8">
    <div v-if="loading" class="flex justify-center py-10">
      <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else-if="event">
      <!-- Alerta se o evento já terminou -->
      <div v-if="isEventEnded" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-2 text-red-700">
          <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-xl" />
          <div>
            <p class="font-bold">Inscrições Encerradas</p>
            <p class="text-sm">As inscrições para este evento foram encerradas em {{ formatDate(event.end_date) }}.</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div v-if="event.image" class="h-64 bg-gray-200 overflow-hidden">
          <img :src="getImageUrl(event.image)" :alt="event.name" class="w-full h-full object-cover" />
        </div>
        <div class="p-6">
          <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ event.name }}</h1>
          <p v-if="event.description" class="text-gray-600 mb-4">{{ event.description }}</p>
          <div class="flex items-center gap-4 text-sm text-gray-500">
            <span>{{ formatDate(event.start_date) }} - {{ formatDate(event.end_date) }}</span>
            <span class="text-lg font-bold" :class="event.price === 0 ? 'text-green-600' : 'text-blue-600'">
              {{ event.price === 0 ? 'Gratuito' : formatBRL(event.price) }}
            </span>
          </div>
          <div v-if="!isEventEnded" class="mt-2 text-sm text-blue-600">
            <font-awesome-icon :icon="['fas', 'info-circle']" />
            Inscrições abertas até {{ formatDate(event.end_date) }}
          </div>
        </div>
      </div>

      <!-- Resumo do Pedido -->
      <div v-if="!isEventEnded" class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Resumo do Pedido</h2>
        <div class="space-y-2">
          <div class="flex justify-between">
            <span class="text-gray-600">Evento:</span>
            <span class="font-medium">{{ event.name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Valor unitário:</span>
            <span class="font-medium">{{ event.price === 0 ? 'Gratuito' : formatBRL(event.price) }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Quantidade:</span>
            <Field name="quantity" type="number" v-slot="{ field }">
              <input 
                v-bind="field"
                min="1" 
                max="10" 
                @input="e => { field.onChange(e); updateRegistrationsCount(e.target.value); quantity = parseInt(e.target.value) || 1 }"
                class="w-20 border rounded-lg p-2 text-center"
              />
            </Field>
          </div>
          <div class="border-t pt-2 mt-2">
            <div class="flex justify-between text-lg font-bold">
              <span>Total:</span>
              <span class="text-blue-600">{{ formatBRL(totalAmount) }}</span>
            </div>
          </div>
        </div>
      </div>

      <Form v-if="!isEventEnded" :validation-schema="schema" :initial-values="initialFormValues()" @submit="onSubmit" v-slot="{ errors, values, setFieldValue }"
        class="bg-white p-6 rounded-lg shadow-md space-y-6">

        <div v-for="(reg, index) in registrations" :key="index" class="border rounded-lg p-4 space-y-4">
          <h3 class="font-bold text-lg">Inscrição {{ index + 1 }}</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium">Nome *</label>
              <Field :name="`registrations.${index}.name`" v-slot="{ field }">
                <input v-bind="field" class="w-full border rounded-lg p-2" />
              </Field>
              <ErrorMessage :name="`registrations.${index}.name`" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Telefone *</label>
              <Field :name="`registrations.${index}.phone`" v-slot="{ field }">
                <input v-bind="field" v-maska="'(##) #####-####'" class="w-full border rounded-lg p-2" />
              </Field>
              <ErrorMessage :name="`registrations.${index}.phone`" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">CPF</label>
              <Field :name="`registrations.${index}.cpf`" v-slot="{ field }">
                <input v-bind="field" v-maska="'###.###.###-##'" class="w-full border rounded-lg p-2" />
              </Field>
            </div>

            <div>
              <label class="block mb-1 font-medium">Data de Nascimento *</label>
              <Field :name="`registrations.${index}.birth_date`" v-slot="{ field }">
                <input v-bind="field" v-maska="'##/##/####'" placeholder="DD/MM/AAAA" class="w-full border rounded-lg p-2" />
              </Field>
              <ErrorMessage :name="`registrations.${index}.birth_date`" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Gênero *</label>
              <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                  <Field :name="`registrations.${index}.gender`" type="radio" value="MASCULINO" v-slot="{ field }">
                    <input type="radio" v-bind="field" />
                  </Field>
                  <span>Masculino</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <Field :name="`registrations.${index}.gender`" type="radio" value="FEMININO" v-slot="{ field }">
                    <input type="radio" v-bind="field" />
                  </Field>
                  <span>Feminino</span>
                </label>
              </div>
              <ErrorMessage :name="`registrations.${index}.gender`" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Setor</label>
              <Field :name="`registrations.${index}.sector`" v-slot="{ field }">
                <select 
                  v-bind="field"
                  @change="(e) => { field.onChange(e); setFieldValue(`registrations.${index}.congregation`, '') }"
                  @blur="field.onBlur"
                  class="w-full border rounded-lg p-2">
                  <option value="">Selecione</option>
                  <option v-for="sector in sectors" :key="sector.value" :value="sector.value">
                    {{ sector.name }}
                  </option>
                </select>
              </Field>
              <p v-if="sectors.length === 0" class="text-xs text-gray-500 mt-1">Carregando setores...</p>
            </div>

            <div>
              <label class="block mb-1 font-medium">Congregação</label>
              <Field :name="`registrations.${index}.congregation`" v-slot="{ field }">
                <select 
                  v-bind="field"
                  @change="field.onChange"
                  @blur="field.onBlur"
                  class="w-full border rounded-lg p-2">
                  <option value="">Selecione</option>
                  <option v-for="church in getChurchesBySector(values.registrations?.[index]?.sector)" :key="church.id || church.nome" :value="church.nome">
                    {{ church.nome }}
                  </option>
                </select>
              </Field>
              <p v-if="churches.length === 0" class="text-xs text-gray-500 mt-1">Carregando congregações...</p>
              <p v-if="values.registrations?.[index]?.sector && getChurchesBySector(values.registrations[index].sector).length === 0" class="text-xs text-yellow-600 mt-1">
                Nenhuma congregação encontrada para este setor
              </p>
            </div>

            <div>
              <label class="block mb-1 font-medium">Tipo</label>
              <Field :name="`registrations.${index}.church_type`" v-slot="{ field }">
                <select 
                  v-bind="field"
                  @change="field.onChange"
                  @blur="field.onBlur"
                  class="w-full border rounded-lg p-2">
                  <option value="">Selecione</option>
                  <option value="De outra igreja">De outra igreja</option>
                  <option value="Membro">Membro</option>
                  <option value="Diácono">Diácono</option>
                  <option value="Diaconisa">Diaconisa</option>
                  <option value="Presbítero">Presbítero</option>
                  <option value="Missionária">Missionária</option>
                  <option value="Evangelista">Evangelista</option>
                  <option value="Pastor">Pastor</option>
                  <option value="Missionário">Missionário</option>
                  <option value="Visitante">Visitante</option>
                </select>
              </Field>
            </div>

            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <Field :name="`registrations.${index}.whatsapp_authorization`" type="checkbox" v-slot="{ field }">
                  <input type="checkbox" v-bind="field" />
                </Field>
                <span>Autoriza receber novidades via WhatsApp</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Dados do Comprador (quem está pagando) -->
        <div v-if="event.price > 0" class="border-t pt-6 mt-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Dados do Comprador (Quem está pagando)</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium">Nome Completo *</label>
              <Field name="name" v-slot="{ field }">
                <input v-bind="field" class="w-full border rounded-lg p-2" placeholder="Nome do comprador" />
              </Field>
              <ErrorMessage name="name" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">CPF *</label>
              <Field name="cpf" v-slot="{ field }">
                <input v-bind="field" v-maska="'###.###.###-##'" class="w-full border rounded-lg p-2" placeholder="000.000.000-00" />
              </Field>
              <ErrorMessage name="cpf" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">E-mail *</label>
              <Field name="email" v-slot="{ field }">
                <input v-bind="field" type="email" class="w-full border rounded-lg p-2" placeholder="email@exemplo.com" />
              </Field>
              <ErrorMessage name="email" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Telefone *</label>
              <Field name="phone" v-slot="{ field }">
                <input v-bind="field" v-maska="'(##) #####-####'" class="w-full border rounded-lg p-2" placeholder="(00) 00000-0000" />
              </Field>
              <ErrorMessage name="phone" class="text-red-600 text-xs mt-1" />
            </div>
          </div>
        </div>

        <!-- Dados de Endereço -->
        <div v-if="event.price > 0" class="border-t pt-6 mt-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Endereço do Comprador</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium">CEP *</label>
              <Field name="postalCode" v-slot="{ field }">
                <input 
                  v-bind="field" 
                  v-maska="'#####-###'" 
                  placeholder="00000-000"
                  @input="e => handleCepInput(e.target.value, setFieldValue)"
                  @blur="() => fetchCep(field.value, setFieldValue)"
                  class="w-full border rounded-lg p-2" />
              </Field>
              <div v-if="loadingCep" class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                <span class="w-3 h-3 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                Buscando endereço...
              </div>
              <ErrorMessage name="postalCode" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Número *</label>
              <Field name="addressNumber" v-slot="{ field }">
                <input v-bind="field" class="w-full border rounded-lg p-2" />
              </Field>
              <ErrorMessage name="addressNumber" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Complemento</label>
              <Field name="addressComplement" v-slot="{ field }">
                <input v-bind="field" class="w-full border rounded-lg p-2" />
              </Field>
            </div>

            <div>
              <label class="block mb-1 font-medium">Endereço *</label>
              <Field name="address" v-slot="{ field }">
                <input v-bind="field" class="w-full border rounded-lg p-2" />
              </Field>
              <ErrorMessage name="address" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Bairro *</label>
              <Field name="province" v-slot="{ field }">
                <input v-bind="field" class="w-full border rounded-lg p-2" />
              </Field>
              <ErrorMessage name="province" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Cidade *</label>
              <Field name="city" v-slot="{ field }">
                <input v-bind="field" class="w-full border rounded-lg p-2" />
              </Field>
              <ErrorMessage name="city" class="text-red-600 text-xs mt-1" />
            </div>

            <div>
              <label class="block mb-1 font-medium">Estado (UF) *</label>
              <Field name="state" v-slot="{ field }">
                <input 
                  v-bind="field" 
                  placeholder="MS" 
                  maxlength="2"
                  class="w-full border rounded-lg p-2 uppercase" />
              </Field>
              <ErrorMessage name="state" class="text-red-600 text-xs mt-1" />
            </div>
          </div>
        </div>

        <!-- Valor Total -->
        <div v-if="event.price > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex justify-between items-center">
            <span class="text-lg font-semibold text-gray-800">Total a Pagar:</span>
            <span class="text-2xl font-bold text-blue-600">{{ formatBRL(grandTotal(values)) }}</span>
          </div>
          <div v-if="values.payment_method && grandTotal(values) !== totalAmount" class="text-sm text-gray-600 mt-2">
            <span>Subtotal: {{ formatBRL(totalAmount) }}</span>
            <span v-if="values.payment_method === 'PIX' && chargePix" class="ml-2">+ Taxa: {{ formatBRL(199) }}</span>
            <span v-if="values.payment_method === 'BOLETO' && chargeBoleto" class="ml-2">+ Taxa: {{ formatBRL(199) }}</span>
            <span v-if="values.payment_method === 'CREDIT_CARD' && cardTax(values)" class="ml-2">+ Taxa: {{ cardTax(values) }}</span>
          </div>
        </div>

        <div v-if="event.price > 0 && event.payment_methods?.length > 0" class="border-t pt-6 mt-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Método de Pagamento</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <label v-for="method in event.payment_methods" :key="method.method"
              class="p-4 border-2 rounded-lg cursor-pointer flex flex-col items-center justify-center gap-2 transition-all"
              :class="values.payment_method === method.method 
                ? 'border-blue-600 bg-blue-50' 
                : 'border-gray-300 hover:border-gray-400'"
              @click="setFieldValue('payment_method', method.method)">
              <Field name="payment_method" type="radio" :value="method.method" v-slot="{ field }">
                <input type="radio" v-bind="field" class="hidden" />
              </Field>
              <font-awesome-icon 
                v-if="method.method === 'PIX'" 
                :icon="['fas', 'qrcode']" 
                class="text-3xl text-blue-600" 
              />
              <font-awesome-icon 
                v-else-if="method.method === 'BOLETO'" 
                :icon="['fas', 'barcode']" 
                class="text-3xl text-blue-600" 
              />
              <font-awesome-icon 
                v-else-if="method.method === 'CREDIT_CARD'" 
                :icon="['fas', 'credit-card']" 
                class="text-3xl text-blue-600" 
              />
              <span class="font-medium">{{ getPaymentMethodName(method.method) }}</span>
            </label>
          </div>
          <ErrorMessage name="payment_method" class="text-red-600 text-xs mt-1" />

          <!-- Campos de Cartão de Crédito -->
          <div v-if="values.payment_method === 'CREDIT_CARD'" class="bg-white p-4 rounded-2xl shadow-sm space-y-4 mt-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Nome no Cartão *</label>
                <Field name="card.holderName" v-slot="{ field, meta }">
                  <input 
                    v-bind="field" 
                    type="text" 
                    placeholder="Nome como está no cartão"
                    class="w-full border rounded-lg p-2"
                    :class="meta.touched && meta.errors ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.holderName" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Número do Cartão *</label>
                <Field name="card.number" v-slot="{ field, meta }">
                  <input 
                    v-bind="field" 
                    type="text" 
                    v-maska="'#### #### #### ####'"
                    placeholder="0000 0000 0000 0000"
                    class="w-full border rounded-lg p-2"
                    :class="meta.touched && meta.errors ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.number" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Mês *</label>
                <Field name="card.expiryMonth" v-slot="{ field, meta }">
                  <input 
                    v-bind="field" 
                    type="text" 
                    v-maska="'##'"
                    placeholder="MM"
                    maxlength="2"
                    class="w-full border rounded-lg p-2"
                    :class="meta.touched && meta.errors ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.expiryMonth" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Ano *</label>
                <Field name="card.expiryYear" v-slot="{ field, meta }">
                  <input 
                    v-bind="field" 
                    type="text" 
                    v-maska="'####'"
                    placeholder="AAAA"
                    maxlength="4"
                    class="w-full border rounded-lg p-2"
                    :class="meta.touched && meta.errors ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.expiryYear" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">CVV *</label>
                <Field name="card.ccv" v-slot="{ field, meta }">
                  <input 
                    v-bind="field" 
                    type="text" 
                    v-maska="'###'"
                    placeholder="000"
                    maxlength="3"
                    class="w-full border rounded-lg p-2"
                    :class="meta.touched && meta.errors ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.ccv" class="text-red-600 text-xs mt-1" />
              </div>
            </div>

            <div v-if="installmentOptions.length > 0">
              <label class="block text-sm font-medium mb-1">Parcelamento *</label>
              <Field name="installments" v-slot="{ field, meta }">
                <select 
                  v-bind="field"
                  class="w-full border rounded-lg p-2"
                  :class="meta.touched && meta.errors ? 'border-red-600' : 'border-gray-300'">
                  <option value="">Selecione</option>
                  <option v-for="opt in installmentOptions" :key="opt.count" :value="opt.count">
                    {{ opt.count }}x de {{ formatBRL(opt.parcela) }} (Total: {{ formatBRL(opt.total) }})
                  </option>
                </select>
              </Field>
              <ErrorMessage name="installments" class="text-red-600 text-xs mt-1" />
            </div>

            <div v-if="cardTax(values)" class="text-sm text-gray-600">
              <span>Taxa Cartão: {{ cardTax(values) }}</span>
            </div>
          </div>
        </div>

        <button type="submit" :disabled="submitting"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold disabled:opacity-50">
          {{ submitting ? 'Processando...' : 'Confirmar Inscrição' }}
        </button>
      </Form>

      <div v-else class="bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-600">As inscrições para este evento foram encerradas.</p>
        <router-link to="/events" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
          Ver Outros Eventos
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { Form, Field, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'
import { eventsApi, registrationsApi, churchesApi } from '@/api/events'
import { vMaska } from 'maska/vue'
import http from '@/api/http'
import { useCurrency } from '@/composables/useCurrency'

const route = useRoute()
const event = ref(null)
const churches = ref([])
const sectors = ref([])
const loading = ref(true)
const submitting = ref(false)
const registrations = ref([{}])
const quantity = ref(1)
const loadingCep = ref(false)
let cepTimer = null
const { formatBRL } = useCurrency()

const chargePix = import.meta.env.VITE_CHARGE_TAX_PIX === 'true'
const chargeBoleto = import.meta.env.VITE_CHARGE_TAX_BOLETO === 'true'
const chargeCard = import.meta.env.VITE_CHARGE_TAX_CREDIT_CARD === 'true'

const isEventEnded = computed(() => {
  if (!event.value?.end_date) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const endDate = new Date(event.value.end_date)
  endDate.setHours(23, 59, 59, 999)
  return today > endDate
})

const totalAmount = computed(() => {
  if (!event.value) return 0
  return event.value.price * quantity.value
})

const installmentOptions = computed(() => {
  if (!event.value || event.value.price === 0) return []
  const max = 10
  const minTotal = 20000 // R$ 200,00 em centavos
  const options = []
  const baseTotal = totalAmount.value
  
  // 1x com taxa
  options.push({
    count: 1,
    parcela: baseTotal + 49 + baseTotal * (2.99 / 100),
    total: baseTotal + 49 + baseTotal * (2.99 / 100),
    percent: 2.99
  })
  
  // Parcelas de 2x a 10x se o valor for >= R$ 200
  if (baseTotal >= minTotal) {
    for (let n = 2; n <= max; n++) {
      let percent = 0
      const fix = 49
      if (n >= 2 && n <= 6) {
        percent = 3.49 + (1.70 * n)
      } else if (n >= 7 && n <= 10) {
        percent = 3.99 + (1.70 * n)
      }
      const totalComTaxa = baseTotal + fix + baseTotal * (percent / 100)
      const parcela = totalComTaxa / n
      options.push({ count: n, parcela, total: totalComTaxa, percent })
    }
  }
  return options
})

function grandTotal(vals) {
  if (!vals?.payment_method) return totalAmount.value
  if (vals.payment_method === 'PIX' && chargePix) return totalAmount.value + 199
  if (vals.payment_method === 'BOLETO' && chargeBoleto) return totalAmount.value + 199
  if (vals.payment_method === 'CREDIT_CARD' && chargeCard) {
    const n = Number(vals.installments || 1)
    let percent = 0
    if (n === 1) percent = 2.99
    else if (n >= 2 && n <= 6) percent = 3.49 + (1.70 * n)
    else if (n >= 7 && n <= 12) percent = 3.99 + (1.70 * n)
    else if (n >= 13 && n <= 21) percent = 4.29 + (1.70 * n)
    return totalAmount.value + 49 + totalAmount.value * (percent / 100)
  }
  return totalAmount.value
}

function cardTax(vals) {
  if (vals.payment_method !== 'CREDIT_CARD' || !chargeCard) return null
  const n = Number(vals.installments || 1)
  if (n === 1) return `R$ 0,49 + 2,99%`
  if (n >= 2 && n <= 6) return `R$ 0,49 + ${(3.49 + 1.70 * n).toFixed(2)}%`
  if (n >= 7 && n <= 12) return `R$ 0,49 + ${(3.99 + 1.70 * n).toFixed(2)}%`
  if (n >= 13 && n <= 21) return `R$ 0,49 + ${(4.29 + 1.70 * n).toFixed(2)}%`
  return null
}

function initialFormValues() {
  return {
    quantity: 1,
    registrations: [{}], // Inicializar com pelo menos uma inscrição
    name: '',
    cpf: '',
    email: '',
    phone: '',
    postalCode: '',
    addressNumber: '',
    addressComplement: '',
    address: '',
    province: '',
    city: '',
    state: '',
  }
}

const schema = computed(() => {
  const baseSchema = {
    quantity: yup.number().required('Quantidade é obrigatória').min(1, 'Quantidade mínima é 1').max(10, 'Quantidade máxima é 10'),
    registrations: yup.array().of(
      yup.object({
        name: yup.string().required('Nome é obrigatório'),
        phone: yup.string().required('Telefone é obrigatório'),
        cpf: yup.string().nullable(),
        birth_date: yup.string().required('Data de nascimento é obrigatória').matches(/^\d{2}\/\d{2}\/\d{4}$/, 'Data inválida. Use o formato DD/MM/AAAA'),
        gender: yup.string().required('Gênero é obrigatório'),
        sector: yup.string().nullable(),
        congregation: yup.string().nullable(),
        church_type: yup.string().nullable(),
        whatsapp_authorization: yup.boolean(),
      })
    ),
  }

  // Se o evento é pago, adiciona validação de endereço e pagamento
  if (event.value && event.value.price > 0) {
    baseSchema.name = yup.string().required('Nome do comprador é obrigatório')
    baseSchema.cpf = yup.string().required('CPF do comprador é obrigatório')
    baseSchema.email = yup.string().email('E-mail inválido').required('E-mail é obrigatório')
    baseSchema.phone = yup.string().required('Telefone do comprador é obrigatório')
    baseSchema.postalCode = yup.string().required('CEP é obrigatório').matches(/^\d{5}-?\d{3}$/, 'CEP inválido')
    baseSchema.addressNumber = yup.string().required('Número é obrigatório')
    baseSchema.address = yup.string().required('Endereço é obrigatório')
    baseSchema.province = yup.string().required('Bairro é obrigatório')
    baseSchema.city = yup.string().required('Cidade é obrigatória')
    baseSchema.state = yup.string().required('Estado é obrigatório').length(2, 'UF deve ter 2 letras')
    baseSchema.payment_method = yup.string().required('Método de pagamento é obrigatório').oneOf(['PIX', 'BOLETO', 'CREDIT_CARD'], 'Método inválido')
    baseSchema.installments = yup.number().when('payment_method', {
      is: 'CREDIT_CARD',
      then: s => s.required('Selecione o parcelamento').min(1).max(21),
      otherwise: s => s.notRequired()
    })
    baseSchema.card = yup.mixed().when('payment_method', {
      is: 'CREDIT_CARD',
      then: () =>
        yup.object({
          holderName: yup.string().required('Nome no cartão é obrigatório'),
          number: yup.string().required('Número obrigatório').test('len', 'Número inválido', v => (v || '').replace(/\D/g, '').length >= 13),
          expiryMonth: yup.string().required('Mês obrigatório').matches(/^(0[1-9]|1[0-2])$/, 'Mês inválido'),
          expiryYear: yup.string().required('Ano obrigatório').matches(/^\d{4}$/, 'Ano inválido'),
          ccv: yup.string().required('CVV obrigatório').matches(/^\d{3}$/, 'CVV inválido')
        }),
      otherwise: () => yup.mixed().notRequired()
    })
  }

  return yup.object(baseSchema)
})

onMounted(async () => {
  await Promise.all([loadEvent(), loadChurches()])
})

async function loadEvent() {
  try {
    loading.value = true
    const { data } = await eventsApi.get(route.params.id)
    event.value = data
  } catch (error) {
    console.error('Erro ao carregar evento:', error)
    alert('Erro ao carregar evento')
  } finally {
    loading.value = false
  }
}

// Mapeamento de setores para nomes completos (mesmo formato do Checkout.vue)
const sectorNames = {
  '1': 'Sede',
  '2': 'Setor 1 - Aero Rancho',
  '3': 'Setor 2 - Guanandi',
  '4': 'Setor 3 - Piratininga',
  '5': 'Setor 4 - Campo Nobre',
  '6': 'Setor 5 - Moreninha',
  '7': 'Setor 6 - Tiradentes',
  '8': 'Setor 7 - Novo Amazonas',
  '9': 'Setor 8 - Itamaracá',
  '10': 'Setor 9 - Boa Vista',
  '11': 'Setor 10 - Santo Amaro',
  '12': 'Setor 11 - Bom Jardim',
  '13': 'Setor 12 - Sílvia Regina',
  '14': 'Setor 13 - Eldorado',
  '15': 'Setor 14 - Campo Novo',
  '16': 'Setor 15 - Nova Campo Grande',
  '17': 'Setor 16 - Jardim Aero Rancho',
  '18': 'Setor 17 - Santo Eugênio',
  '19': 'Setor 18 - Rochedinho',
  '20': 'Setor 19 - Nogueira',
  '21': 'Setor 20 - Santa Emília',
  'SEDE': 'Sede',
}

async function loadChurches() {
  try {
    const response = await churchesApi.list()
    console.log('Resposta completa da API de igrejas:', response)
    
    // A API pode retornar { churches: [...], grouped_by_sector: {...} } ou array direto
    let churchesData = []
    if (response.data) {
      if (Array.isArray(response.data)) {
        churchesData = response.data
      } else if (response.data.churches) {
        churchesData = response.data.churches
      } else if (Array.isArray(response.data.data)) {
        churchesData = response.data.data
      }
    }
    
    churches.value = churchesData
    
    // Extrair setores únicos das igrejas e mapear para nomes
    const uniqueSectorValues = [...new Set(churches.value.map(church => {
      const sector = church?.setor || church?.sector
      // Manter o valor original (pode ser número ou string)
      return sector
    }).filter(Boolean))]
    
    sectors.value = uniqueSectorValues
      .map(sectorValue => {
        // Converter para string para buscar no mapeamento
        const sectorKey = String(sectorValue)
        // Tentar encontrar o nome no mapeamento
        const name = sectorNames[sectorKey] || sectorNames[sectorValue] || `Setor ${sectorValue}`
        return {
          value: sectorValue, // Manter valor original para salvar no banco
          name: name
        }
      })
      .sort((a, b) => {
        // Ordenar: SEDE primeiro, depois numéricos
        const aStr = String(a.value).toUpperCase()
        const bStr = String(b.value).toUpperCase()
        if (aStr === 'SEDE' || aStr === '1') return -1
        if (bStr === 'SEDE' || bStr === '1') return 1
        const numA = parseInt(a.value) || 999
        const numB = parseInt(b.value) || 999
        return numA - numB
      })
    
    console.log('Igrejas carregadas:', churches.value.length)
    console.log('Setores encontrados:', sectors.value)
    console.log('Primeira igreja (exemplo):', churches.value[0])
  } catch (error) {
    console.error('Erro ao carregar igrejas:', error)
    console.error('Resposta do erro:', error.response?.data)
    churches.value = []
    sectors.value = []
  }
}

watch(() => route.params.id, () => {
  loadEvent()
}, { immediate: false })

function updateRegistrationsCount(value) {
  const qty = parseInt(value) || 1
  quantity.value = qty
  if (qty > registrations.value.length) {
    const diff = qty - registrations.value.length
    registrations.value.push(...Array(diff).fill({}))
  } else if (qty < registrations.value.length) {
    registrations.value = registrations.value.slice(0, qty)
  }
}

async function fetchCep(cep, setFieldValue) {
  const cleanCep = (cep || '').replace(/\D/g, '')
  if (cleanCep.length !== 8) return
  loadingCep.value = true
  try {
    const { data } = await http.get(`/cep/${cleanCep}`)
    if (data) {
      setFieldValue('address', data.logradouro || '')
      setFieldValue('province', data.bairro || '')
      setFieldValue('city', data.localidade || '')
      setFieldValue('state', data.uf || '')
    }
  } catch (error) {
    console.error('Erro ao buscar CEP:', error)
  } finally {
    loadingCep.value = false
  }
}

function handleCepInput(val, setFieldValue) {
  const cleanCep = String(val || '').replace(/\D/g, '')
  clearTimeout(cepTimer)
  if (cleanCep.length === 8) {
    cepTimer = setTimeout(() => fetchCep(cleanCep, setFieldValue), 700)
  }
}

async function onSubmit(values) {
  try {
    submitting.value = true
    const qty = parseInt(values.quantity)
    const regs = values.registrations.slice(0, qty).map(reg => {
      // Converter data de DD/MM/AAAA para AAAA-MM-DD
      if (reg.birth_date && reg.birth_date.includes('/')) {
        const [day, month, year] = reg.birth_date.split('/')
        reg.birth_date = `${year}-${month}-${day}`
      }
      return reg
    })

    const payload = {
      event_id: event.value.id,
      quantity: qty,
      registrations: regs,
      payment_method: values.payment_method || 'FREE',
    }

    // Se o evento é pago, adiciona dados de endereço
    if (event.value.price > 0) {
      payload.buyer = {
        name: values.name || regs[0]?.name,
        cpf: (values.cpf || regs[0]?.cpf || '').replace(/\D/g, ''),
        email: values.email,
        phone: (values.phone || regs[0]?.phone || '').replace(/\D/g, ''),
        postalCode: values.postalCode?.replace(/\D/g, '') || '',
        addressNumber: values.addressNumber || '',
        addressComplement: values.addressComplement || '',
        address: values.address || '',
        province: values.province || '',
        city: values.city || '',
        state: values.state?.toUpperCase() || '',
      }

      // Se for cartão de crédito, adiciona dados do cartão e parcelamento
      if (values.payment_method === 'CREDIT_CARD' && values.card) {
        payload.card = {
          holderName: values.card.holderName,
          number: values.card.number.replace(/\D/g, ''),
          expiryMonth: values.card.expiryMonth,
          expiryYear: values.card.expiryYear,
          ccv: values.card.ccv
        }
        payload.installments = Number(values.installments || 1)
      }
    }

    const { data } = await registrationsApi.create(payload)

    // Redirecionar para página de sucesso com QR codes
    const registrationsParam = encodeURIComponent(JSON.stringify(data.registrations))
    window.location.href = `/registration-success?registrations=${registrationsParam}`
  } catch (error) {
    console.error('Erro ao realizar inscrição:', error)
    alert(error.response?.data?.message || 'Erro ao realizar inscrição')
  } finally {
    submitting.value = false
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('pt-BR')
}

function getImageUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL}/storage/${path}`
}

function getPaymentMethodName(method) {
  const names = {
    PIX: 'PIX',
    BOLETO: 'Boleto',
    CREDIT_CARD: 'Cartão',
    FREE: 'Gratuito',
  }
  return names[method] || method
}

function getChurchesBySector(selectedSector) {
  if (!selectedSector) {
    return churches.value
  }
  return churches.value.filter(church => 
    (church.setor || church.sector) === selectedSector
  )
}
</script>
