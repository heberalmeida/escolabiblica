<template>
  <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
    <h1 class="text-2xl font-bold mb-6 text-center sm:text-left">Checkout</h1>
    <div v-if="loading" class="flex justify-center items-center py-10">
      <div class="w-10 h-10 border-4 border-green-600 border-t-transparent rounded-full animate-spin"></div>
    </div>
    <div v-else>
      <div v-if="!cart.items.length" class="text-center text-gray-500 py-10">
        Seu carrinho está vazio.
      </div>

      <div v-else>
        <!-- Resumo do Valor no Topo -->
        <div class="bg-gradient-to-r from-green-50 to-blue-50 border-2 border-green-200 p-6 rounded-2xl shadow-lg mb-6">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-2 uppercase tracking-wide">Total do Pedido</p>
            <div class="text-5xl font-bold text-green-700">
              {{ formatBRL(total) }}
            </div>
            <p class="text-xs text-gray-500 mt-2">* Taxas serão calculadas conforme o método de pagamento</p>
          </div>
        </div>

        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">Resumo do Pedido</h2>

        <ul class="divide-y  mb-6  rounded bg-white">
          <li v-for="item in cart.items" :key="item.type === 'event' ? `event-${item.eventId}` : `product-${item.variantId}`" class="p-3 flex justify-between items-center">
            <div>
              <div class="text-sm font-medium text-gray-800">
                {{ item.type === 'event' ? item.name : item.variantName }}
              </div>
              <div class="text-xs text-gray-500">
                <span v-if="item.type === 'event'">Evento • Qtd: {{ item.quantity }}</span>
                <span v-else>Qtd: {{ item.quantity }}</span>
              </div>
            </div>
            <div class="font-bold tabular-nums text-green-600">
              {{ formatBRL(item.price * item.quantity) }}
            </div>
          </li>
        </ul>

        <Form :validation-schema="schema" @submit="onSubmit" v-slot="{ errors, values, setFieldValue, validate, meta, setErrors, setTouched }" :initial-values="getInitialValues()" :validate-on-mount="false" :validate-on-blur="true" :validate-on-change="true" :validate-on-input="false">
          <!-- Informação sobre Eventos -->
          <div v-if="cart.eventItems.length > 0" class="mb-6">
            <div v-for="(eventItem, eventIndex) in cart.eventItems" :key="eventItem.eventId" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <h3 class="text-lg font-semibold text-blue-800 mb-2">{{ eventItem.name }}</h3>
              <p class="text-sm text-blue-600 mb-1">Quantidade: {{ eventItem.quantity }} ingresso(s)</p>
              <p class="text-xs text-blue-500 italic">
                * Cada ingresso deve ter nome e telefone únicos
              </p>
            </div>
          </div>
          <Field name="total" type="hidden" :value="total" />
          <ErrorMessage name="total" class="text-red-600 text-sm text-center block mb-2" />

          <div class="bg-white p-4 rounded-2xl shadow-sm space-y-4 mt-4">
            <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">Dados do Comprador</h2>
            <p class="text-xs text-gray-600 mb-4">
              * Estes dados são do comprador/pagador. Os dados dos ingressos serão preenchidos abaixo.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                <label class="block text-sm font-medium mb-1">Nome completo *</label>
                <Field name="name" v-slot="{ field }">
                  <input v-bind="field"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.name ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="name" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">CPF *</label>
                <Field name="cpf" v-slot="{ field }">
                  <input v-bind="field" v-maska="'###.###.###-##'"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.cpf ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="cpf" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Data de Nascimento *</label>
                <Field name="birth_date" v-slot="{ field }">
                  <input
                    v-bind="field"
                    v-maska="'##/##/####'"
                    placeholder="DD/MM/AAAA"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.birth_date ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="birth_date" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Telefone *</label>
                <Field name="phone" v-slot="{ field }">
                  <input v-bind="field" v-maska="'(##) #####-####'"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.phone ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="phone" class="text-red-600 text-xs mt-1" />
              </div>

              <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                <label class="block text-sm font-medium mb-1">Email *</label>
                <Field name="email" v-slot="{ field }">
                  <input type="email" v-bind="field"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.email ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="email" class="text-red-600 text-xs mt-1" />
              </div>

              <!-- Campos para ingressos de eventos -->
              <template v-if="cart.eventItems.length > 0">
                <div v-for="(eventItem, eventIndex) in cart.eventItems" :key="eventItem.eventId" class="col-span-1 sm:col-span-2 lg:col-span-4">
                  <div class="border-t pt-6 mt-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">{{ eventItem.name }} - Dados dos Ingressos</h4>
                    <p class="text-xs text-gray-600 mb-4">Preencha os dados de cada ingresso. Nome e telefone devem ser únicos.</p>
                    
                    <div v-for="(reg, regIndex) in getEventRegistrations(eventIndex)" :key="regIndex" class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                      <div class="mb-3">
                        <div v-if="regIndex === 0" class="mb-2">
                          <label class="flex items-center gap-2 cursor-pointer">
                            <input
                              type="checkbox"
                              @change="(e) => handleCopyBuyerData(e.target.checked, eventIndex, setFieldValue, values)"
                              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                            <span class="text-sm text-blue-600 font-medium">Copiar dados do comprador</span>
                          </label>
                        </div>
                        <div v-else-if="regIndex === 1 && eventItem.quantity > 1 && values.events?.[eventIndex]?.registrations?.[0]?.church_affiliation && values.events?.[eventIndex]?.registrations?.[0]?.sector && values.events?.[eventIndex]?.registrations?.[0]?.congregation" class="mb-2 p-2 bg-blue-50 border border-blue-200 rounded">
                          <label class="flex items-center gap-2 cursor-pointer">
                            <input
                              type="checkbox"
                              @change="(e) => handleCopyChurchData(e.target.checked, eventIndex, setFieldValue, values)"
                              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                            <span class="text-sm text-blue-700 font-medium">Copiar dados da igreja do primeiro ingresso para todos</span>
                          </label>
                        </div>
                        <h5 class="text-sm font-semibold text-gray-700">Ingresso {{ regIndex + 1 }}</h5>
                      </div>
                      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="col-span-1 sm:col-span-2">
                          <label class="block text-sm font-medium mb-1">Nome completo *</label>
                          <Field :name="`events.${eventIndex}.registrations.${regIndex}.name`" v-slot="{ field, meta }">
                            <input
                              v-bind="field"
                              class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                              :class="(meta.touched && meta.errors) || errors[`events.${eventIndex}.registrations.${regIndex}.name`] ? 'border-red-600' : 'border-gray-300'" />
                          </Field>
                          <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.name`" class="text-red-600 text-xs mt-1" />
                        </div>

                        <div>
                          <label class="block text-sm font-medium mb-1">Telefone *</label>
                          <Field :name="`events.${eventIndex}.registrations.${regIndex}.phone`" v-slot="{ field, meta }">
                            <input
                              v-bind="field"
                              v-maska="'(##) #####-####'"
                              class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                              :class="(meta.touched && meta.errors) || errors[`events.${eventIndex}.registrations.${regIndex}.phone`] ? 'border-red-600' : 'border-gray-300'" />
                          </Field>
                          <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.phone`" class="text-red-600 text-xs mt-1" />
                        </div>

                        <div>
                          <label class="block text-sm font-medium mb-1">Data de Nascimento *</label>
                          <Field :name="`events.${eventIndex}.registrations.${regIndex}.birth_date`" v-slot="{ field, meta }">
                            <input
                              v-bind="field"
                              v-maska="'##/##/####'"
                              placeholder="DD/MM/AAAA"
                              class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                              :class="(meta.touched && meta.errors) || errors[`events.${eventIndex}.registrations.${regIndex}.birth_date`] ? 'border-red-600' : 'border-gray-300'" />
                          </Field>
                          <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.birth_date`" class="text-red-600 text-xs mt-1" />
                        </div>

                        <div>
                          <label class="block text-sm font-medium mb-1">Gênero *</label>
                          <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                              <Field :name="`events.${eventIndex}.registrations.${regIndex}.gender`" type="radio" value="MASCULINO" v-slot="{ field }">
                                <input type="radio" v-bind="field" />
                              </Field>
                              <span class="text-sm">Masculino</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                              <Field :name="`events.${eventIndex}.registrations.${regIndex}.gender`" type="radio" value="FEMININO" v-slot="{ field }">
                                <input type="radio" v-bind="field" />
                              </Field>
                              <span class="text-sm">Feminino</span>
                            </label>
                          </div>
                          <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.gender`" class="text-red-600 text-xs mt-1" />
                        </div>

                        <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                          <label class="block text-sm font-medium mb-1">Afiliação *</label>
                          <Field :name="`events.${eventIndex}.registrations.${regIndex}.church_affiliation`" v-slot="{ field, meta }">
                            <select
                              v-bind="field"
                              @change="(e) => { 
                                field.onChange(e); 
                                setFieldValue(`events.${eventIndex}.registrations.${regIndex}.sector`, ''); 
                                setFieldValue(`events.${eventIndex}.registrations.${regIndex}.congregation`, ''); 
                                setFieldValue(`events.${eventIndex}.registrations.${regIndex}.church_type`, ''); 
                                setFieldValue(`events.${eventIndex}.registrations.${regIndex}.position`, ''); 
                                setFieldValue(`events.${eventIndex}.registrations.${regIndex}.other_church_name`, '');
                                // Forçar validação dos campos dependentes
                                setTimeout(() => {
                                  validate(`events.${eventIndex}.registrations.${regIndex}.sector`);
                                  validate(`events.${eventIndex}.registrations.${regIndex}.congregation`);
                                  validate(`events.${eventIndex}.registrations.${regIndex}.church_type`);
                                  validate(`events.${eventIndex}.registrations.${regIndex}.position`);
                                  validate(`events.${eventIndex}.registrations.${regIndex}.other_church_name`);
                                }, 100);
                              }"
                              @blur="field.onBlur"
                              class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                              :class="(meta.touched && meta.errors) || errors[`events.${eventIndex}.registrations.${regIndex}.church_affiliation`] ? 'border-red-600' : 'border-gray-300'">
                              <option value="">Selecione</option>
                              <option value="ASSEMBLEIA">Assembleia de Deus Missões de Campo Grande</option>
                              <option value="OUTRA_IGREJA">De outra igreja</option>
                              <option value="NAO_EVANGELICO">Não evangélico</option>
                            </select>
                          </Field>
                          <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.church_affiliation`" class="text-red-600 text-xs mt-1" />
                        </div>

                        <!-- Campos condicionais para Assembleia -->
                        <template v-if="values.events?.[eventIndex]?.registrations?.[regIndex]?.church_affiliation === 'ASSEMBLEIA'">
                          <div>
                            <label class="block text-sm font-medium mb-1">Setor *</label>
                            <Field :name="`events.${eventIndex}.registrations.${regIndex}.sector`" v-slot="{ field, meta }">
                              <select
                                v-bind="field"
                                @change="(e) => { field.onChange(e); setFieldValue(`events.${eventIndex}.registrations.${regIndex}.congregation`, '') }"
                                @blur="field.onBlur"
                                class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                                :class="(meta.touched && meta.errors) || errors[`events.${eventIndex}.registrations.${regIndex}.sector`] ? 'border-red-600' : 'border-gray-300'">
                                <option value="">Selecione</option>
                                <option v-for="sector in eventSectors" :key="sector.value" :value="sector.value">
                                  {{ sector.name }}
                                </option>
                              </select>
                            </Field>
                            <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.sector`" class="text-red-600 text-xs mt-1" />
                          </div>

                          <div>
                            <label class="block text-sm font-medium mb-1">Congregação *</label>
                            <Field :name="`events.${eventIndex}.registrations.${regIndex}.congregation`" v-slot="{ field, meta }">
                              <select
                                v-bind="field"
                                @change="field.onChange"
                                @blur="field.onBlur"
                                class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                                :class="(meta.touched && meta.errors) || errors[`events.${eventIndex}.registrations.${regIndex}.congregation`] ? 'border-red-600' : 'border-gray-300'">
                                <option value="">Selecione</option>
                                <option v-for="church in getChurchesBySector(values.events?.[eventIndex]?.registrations?.[regIndex]?.sector)" :key="church.id || church.nome" :value="church.nome">
                                  {{ church.nome }}
                                </option>
                              </select>
                            </Field>
                            <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.congregation`" class="text-red-600 text-xs mt-1" />
                          </div>

                          <div>
                            <label class="block text-sm font-medium mb-1">Tipo *</label>
                            <Field :name="`events.${eventIndex}.registrations.${regIndex}.church_type`" v-slot="{ field, meta }">
                              <select
                                v-bind="field"
                                @change="(e) => { 
                                  field.onChange(e);
                                  setFieldValue(`events.${eventIndex}.registrations.${regIndex}.position`, '');
                                  setTimeout(() => {
                                    validate(`events.${eventIndex}.registrations.${regIndex}.position`);
                                  }, 100);
                                }"
                                @blur="field.onBlur"
                                class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                                :class="(meta.touched && meta.errors) || errors[`events.${eventIndex}.registrations.${regIndex}.church_type`] ? 'border-red-600' : 'border-gray-300'">
                                <option value="">Selecione</option>
                                <option value="Membro">Membro</option>
                                <option value="Diácono">Diácono</option>
                                <option value="Diaconisa">Diaconisa</option>
                                <option value="Presbítero">Presbítero</option>
                                <option value="Missionária">Missionária</option>
                                <option value="Evangelista">Evangelista</option>
                                <option value="Pastor">Pastor</option>
                                <option value="Missionário">Missionário</option>
                                <option value="Visitante">Visitante</option>
                                <option value="OUTRO">Outro (escrever cargo)</option>
                              </select>
                            </Field>
                            <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.church_type`" class="text-red-600 text-xs mt-1" />
                          </div>

                          <div v-if="values.events?.[eventIndex]?.registrations?.[regIndex]?.church_type === 'OUTRO'" class="col-span-1 sm:col-span-2 lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Cargo *</label>
                            <Field :name="`events.${eventIndex}.registrations.${regIndex}.position`" v-slot="{ field, meta }">
                              <input
                                v-bind="field"
                                placeholder="Digite o cargo"
                                class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                                :class="(meta.touched && meta.errors) || errors[`events.${eventIndex}.registrations.${regIndex}.position`] ? 'border-red-600' : 'border-gray-300'" />
                            </Field>
                            <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.position`" class="text-red-600 text-xs mt-1" />
                          </div>
                        </template>

                        <!-- Campos condicionais para De outra igreja -->
                        <template v-if="values.events?.[eventIndex]?.registrations?.[regIndex]?.church_affiliation === 'OUTRA_IGREJA'">
                          <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Nome da Igreja *</label>
                            <Field :name="`events.${eventIndex}.registrations.${regIndex}.other_church_name`" v-slot="{ field, meta }">
                              <input
                                v-bind="field"
                                placeholder="Digite o nome da igreja"
                                class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                                :class="(meta.touched && meta.errors) || errors[`events.${eventIndex}.registrations.${regIndex}.other_church_name`] ? 'border-red-600' : 'border-gray-300'" />
                            </Field>
                            <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.other_church_name`" class="text-red-600 text-xs mt-1" />
                          </div>

                          <div>
                            <label class="block text-sm font-medium mb-1">Cargo Ministerial</label>
                            <Field :name="`events.${eventIndex}.registrations.${regIndex}.church_type`" v-slot="{ field }">
                              <select
                                v-bind="field"
                                @change="(e) => { 
                                  field.onChange(e);
                                  setFieldValue(`events.${eventIndex}.registrations.${regIndex}.position`, '');
                                  setTimeout(() => {
                                    validate(`events.${eventIndex}.registrations.${regIndex}.position`);
                                  }, 100);
                                }"
                                @blur="field.onBlur"
                                class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                                :class="errors[`events.${eventIndex}.registrations.${regIndex}.church_type`] ? 'border-red-600' : 'border-gray-300'">
                                <option value="">Selecione (opcional)</option>
                                <option value="Membro">Membro</option>
                                <option value="Diácono">Diácono</option>
                                <option value="Diaconisa">Diaconisa</option>
                                <option value="Presbítero">Presbítero</option>
                                <option value="Missionária">Missionária</option>
                                <option value="Evangelista">Evangelista</option>
                                <option value="Pastor">Pastor</option>
                                <option value="Missionário">Missionário</option>
                                <option value="Visitante">Visitante</option>
                                <option value="OUTRO">Outro (escrever cargo)</option>
                              </select>
                            </Field>
                            <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.church_type`" class="text-red-600 text-xs mt-1" />
                          </div>

                          <div v-if="values.events?.[eventIndex]?.registrations?.[regIndex]?.church_type === 'OUTRO'" class="col-span-1 sm:col-span-2 lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Cargo *</label>
                            <Field :name="`events.${eventIndex}.registrations.${regIndex}.position`" v-slot="{ field }">
                              <input
                                v-bind="field"
                                placeholder="Digite o cargo"
                                class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                                :class="errors[`events.${eventIndex}.registrations.${regIndex}.position`] ? 'border-red-600' : 'border-gray-300'" />
                            </Field>
                            <ErrorMessage :name="`events.${eventIndex}.registrations.${regIndex}.position`" class="text-red-600 text-xs mt-1" />
                          </div>
                        </template>

                        <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                          <label class="flex items-center gap-2 cursor-pointer">
                            <Field :name="`events.${eventIndex}.registrations.${regIndex}.whatsapp_authorization`" type="checkbox" v-slot="{ field }">
                              <input type="checkbox" v-bind="field" :checked="field.value === true || field.value === undefined" />
                            </Field>
                            <span class="text-sm">Autoriza receber novidades via WhatsApp</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <div class="bg-white p-4 rounded-2xl shadow-sm space-y-4 mt-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">Endereço</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">CEP</label>
                <Field name="postalCode" v-slot="{ field }">
                  <div>
                    <input v-bind="field" v-maska="'#####-###'" placeholder="00000-000"
                      @input="e => handleCepInput(e.target.value, setFieldValue)"
                      @blur="() => fetchCep(field.value, setFieldValue)"
                      class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                      :class="errors.postalCode ? 'border-red-600' : 'border-gray-300'" />
                    <div v-if="loadingCep" class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                      <span
                        class="w-3 h-3 border-2 border-green-600 border-t-transparent rounded-full animate-spin"></span>
                      Buscando endereço...
                    </div>
                  </div>
                </Field>
                <ErrorMessage name="postalCode" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Número</label>
                <Field name="addressNumber" v-slot="{ field }">
                  <input v-bind="field"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.addressNumber ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="addressNumber" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Complemento</label>
                <Field name="addressComplement" v-slot="{ field }">
                  <input v-bind="field"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.addressComplement ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="addressComplement" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Endereço</label>
                <Field name="address" v-slot="{ field }">
                  <input v-bind="field"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.address ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="address" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Bairro</label>
                <Field name="province" v-slot="{ field }">
                  <input v-bind="field"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.province ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="province" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Cidade</label>
                <Field name="city" v-slot="{ field }">
                  <input v-bind="field"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.city ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="city" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Estado</label>
                <Field name="state" v-slot="{ field }">
                  <input v-bind="field" placeholder="UF" maxlength="2"
                    class="w-full border px-3 py-2 rounded-lg uppercase focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.state ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="state" class="text-red-600 text-xs mt-1" />
              </div>

            </div>
          </div>

          <!-- Resumo do Valor acima do Método de Pagamento -->
          <div class="bg-green-50 border-2 border-green-200 p-4 rounded-2xl shadow-sm mt-6 mb-4">
            <div class="flex justify-between items-center">
              <div>
                <p class="text-sm text-gray-600">Sub-total</p>
                <p class="text-lg font-semibold text-gray-800">{{ formatBRL(total) }}</p>
              </div>
              <div v-if="values.method === 'PIX' && chargePix" class="text-right">
                <p class="text-sm text-gray-600">Taxa PIX</p>
                <p class="text-lg font-semibold text-gray-800">R$ 1,99</p>
              </div>
              <div v-else-if="values.method === 'BOLETO' && chargeBoleto" class="text-right">
                <p class="text-sm text-gray-600">Taxa Boleto</p>
                <p class="text-lg font-semibold text-gray-800">R$ 1,99</p>
              </div>
              <div v-else-if="values.method === 'CREDIT_CARD' && chargeCard && cardTax(values)" class="text-right">
                <p class="text-sm text-gray-600">Taxa Cartão</p>
                <p class="text-lg font-semibold text-gray-800">{{ cardTax(values) }}</p>
              </div>
              <div class="text-right border-l-2 border-green-300 pl-4 ml-4">
                <p class="text-sm text-gray-600">Total</p>
                <p class="text-2xl font-bold text-green-700">{{ formatBRL(grandTotal(values)) }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white p-4 rounded-2xl shadow-sm space-y-4 mt-6">
            <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">Método de Pagamento</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
              <div
                v-if="availablePaymentMethods.includes('PIX')"
                class="p-6 border rounded-2xl cursor-pointer flex flex-col items-center justify-center gap-3 transition hover:shadow-md"
                :class="values.method === 'PIX'
                  ? 'border-green-600 bg-green-50 text-green-700 font-semibold shadow-md'
                  : 'hover:border-gray-400'" @click="setFieldValue('method', 'PIX')">
                <font-awesome-icon :icon="['fab', 'pix']" class="text-4xl" />
                <span>PIX</span>
              </div>

              <div
                v-if="availablePaymentMethods.includes('BOLETO')"
                class="p-6 border rounded-2xl cursor-pointer flex flex-col items-center justify-center gap-3 transition hover:shadow-md"
                :class="values.method === 'BOLETO'
                  ? 'border-green-600 bg-green-50 text-green-700 font-semibold shadow-md'
                  : 'hover:border-gray-400'" @click="setFieldValue('method', 'BOLETO')">
                <font-awesome-icon :icon="['fas', 'barcode']" class="text-4xl" />
                <span>Boleto</span>
              </div>

              <div
                v-if="availablePaymentMethods.includes('CREDIT_CARD')"
                class="p-6 border rounded-2xl cursor-pointer flex flex-col items-center justify-center gap-3 transition hover:shadow-md"
                :class="values.method === 'CREDIT_CARD'
                  ? 'border-green-600 bg-green-50 text-green-700 font-semibold shadow-md'
                  : 'hover:border-gray-400'" @click="setFieldValue('method', 'CREDIT_CARD')">
                <font-awesome-icon :icon="['fas', 'credit-card']" class="text-4xl" />
                <span>Cartão</span>
              </div>
            </div>

            <ErrorMessage name="method" class="text-red-600 text-xs mt-1" />
          </div>


          <div v-if="values.method === 'CREDIT_CARD'" class="bg-white p-4 rounded-2xl shadow-sm space-y-4 mt-6">
            <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">Pagamento com Cartão</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                <label class="block text-sm font-medium mb-1">Nome no cartão</label>
                <Field name="card.holderName" v-slot="{ field }">
                  <input v-bind="field"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors['card.holderName'] ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.holderName" class="text-red-600 text-xs mt-1" />
              </div>

              <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                <label class="block text-sm font-medium mb-1">Número do cartão</label>
                <Field name="card.number" v-slot="{ field }">
                  <input v-bind="field" v-maska="'#### #### #### ####'"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors['card.number'] ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.number" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Mês</label>
                <Field name="card.expiryMonth" v-slot="{ field }">
                  <input v-bind="field" v-maska="'##'"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors['card.expiryMonth'] ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.expiryMonth" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Ano</label>
                <Field name="card.expiryYear" v-slot="{ field }">
                  <input v-bind="field" v-maska="'####'"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors['card.expiryYear'] ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.expiryYear" class="text-red-600 text-xs mt-1" />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">CVV</label>
                <Field name="card.ccv" v-slot="{ field }">
                  <input v-bind="field" v-maska="'###'"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors['card.ccv'] ? 'border-red-600' : 'border-gray-300'" />
                </Field>
                <ErrorMessage name="card.ccv" class="text-red-600 text-xs mt-1" />
              </div>

              <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                <label class="block text-sm font-medium mb-1">Parcelamento</label>
                <Field name="installments" v-slot="{ field }">
                  <select v-bind="field"
                    class="w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    :class="errors.installments ? 'border-red-600' : 'border-gray-300'">
                    <option v-for="opt in installmentOptions" :key="opt.count" :value="opt.count">
                      {{ opt.count }}x de {{ formatBRL(opt.parcela) }} (Total: {{ formatBRL(opt.total) }})
                    </option>
                  </select>
                </Field>
                <ErrorMessage name="installments" class="text-red-600 text-xs mt-1" />
              </div>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row justify-between gap-2 sm:gap-0 pt-4">
            <router-link to="/cart"
              class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-center sm:text-left">Voltar</router-link>
            <button type="submit"
              class="bg-green-600 cursor-pointer text-white px-6 py-2 rounded hover:bg-green-700 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loadingPay || (cart.productItems.length > 0 && total < 5)">
              <span v-if="loadingPay"
                class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
              {{ loadingPay ? 'Processando...' : values.method === 'CREDIT_CARD' ? 'Pagar' : 'Gerar pagamento' }}
            </button>
          </div>
        </Form>
      </div>
    </div>
    <Toast ref="toastRef" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import http from '@/api/http'
import { useCartStore } from '@/stores/cart.store'
import { useCurrency } from '@/composables/useCurrency'
import { Form, Field, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'
import Toast from '@/components/Toast.vue'
import { churchesApi, eventsApi } from '@/api/events'
import { vMaska } from 'maska/vue'

const cart = useCartStore()
const { formatBRL } = useCurrency()
const response = ref(null)
const router = useRouter()
const toastRef = ref(null)
const loading = ref(true)
const loadingPay = ref(false)
const loadingCep = ref(false)
let cepTimer = null
const churches = ref([])
const eventSectors = ref([])
const events = ref([]) // Para armazenar dados completos dos eventos (incluindo paymentMethods)

// Mapeamento de setores para nomes completos (baseado no JSON real)
const sectorNames = {
  '00': 'Sede',
  '01': 'Setor 01 - Aero Rancho',
  '02': 'Setor 02 - Guanandi',
  '03': 'Setor 03 - Piratininga',
  '04': 'Setor 04 - Campo Nobre',
  '05': 'Setor 05 - Moreninha',
  '06': 'Setor 06 - Tiradentes',
  '07': 'Setor 07 - Novo Amazonas',
  '08': 'Setor 08 - Itamaracá',
  '09': 'Setor 09 - Boa Vista',
  '10': 'Setor 10 - Santo Amaro',
  '11': 'Setor 11 - Bom Jardim',
  '12': 'Setor 12 - Silvia Regina',
  '13': 'Setor 13 - Eldorado',
  '14': 'Setor 14 - Campo Novo',
  '15': 'Setor 15 - Nova Campo Grande',
  '16': 'Setor 16 - Jardim Aero Rancho',
  '17': 'Setor 17 - Santo Eugênio',
  '18': 'Setor 18 - Rochedinho',
  '19': 'Setor 19 - Nogueira',
  '20': 'Setor 20 - Santa Emília',
}

const chargePix = import.meta.env.VITE_CHARGE_TAX_PIX === 'true'
const chargeBoleto = import.meta.env.VITE_CHARGE_TAX_BOLETO === 'true'
const chargeCard = import.meta.env.VITE_CHARGE_TAX_CREDIT_CARD === 'true'

const setores = {
  1: 'Sede', 2: 'Setor 1 - Aero Rancho', 3: 'Setor 2 - Guanandi',
  4: 'Setor 3 - Piratininga', 5: 'Setor 4 - Campo Nobre',
  6: 'Setor 5 - Moreninha', 7: 'Setor 6 - Tiradentes',
  8: 'Setor 7 - Novo Amazonas', 9: 'Setor 8 - Itamaracá',
  10: 'Setor 9 - Boa Vista', 11: 'Setor 10 - Santo Amaro',
  12: 'Setor 11 - Bom Jardim', 13: 'Setor 12 - Sílvia Regina',
  14: 'Setor 13 - Eldorado', 15: 'Setor 14 - Campo Novo',
  16: 'Setor 15 - Nova Campo Grande', 17: 'Setor 16 - Jardim Aero Rancho',
  18: 'Setor 17 - Santo Eugênio', 19: 'Setor 18 - Rochedinho',
  20: 'Setor 19 - Nogueira', 21: 'Setor 20 - Santa Emília'
}

onMounted(async () => {
  if (cart.eventItems.length > 0) {
    await loadChurches()
    await loadEvents()
  }
  setTimeout(() => {
    loading.value = false
  }, 600)
})

async function loadEvents() {
  try {
    const eventIds = cart.eventItems.map(item => item.eventId)
    const promises = eventIds.map(id => eventsApi.get(id))
    const responses = await Promise.all(promises)
    events.value = responses.map(r => r.data)
  } catch (error) {
    console.error('Erro ao carregar eventos:', error)
    events.value = []
  }
}

const availablePaymentMethods = computed(() => {
  if (cart.eventItems.length === 0) {
    // Se não há eventos, retornar todos os métodos
    return ['PIX', 'BOLETO', 'CREDIT_CARD']
  }
  
  // Se há eventos, verificar métodos ativos de todos os eventos
  const allMethods = new Set()
  
  events.value.forEach(event => {
    if (event.payment_methods) {
      event.payment_methods.forEach(pm => {
        if (pm.active && pm.method !== 'FREE') {
          allMethods.add(pm.method)
        }
      })
    }
  })
  
  // Se evento é gratuito, adicionar FREE
  const hasFreeEvents = cart.eventItems.some(item => {
    const event = events.value.find(e => e.id === item.eventId)
    return event && event.price === 0
  })
  
  if (hasFreeEvents) {
    allMethods.add('FREE')
  }
  
  return Array.from(allMethods)
})

async function loadChurches() {
  try {
    const response = await churchesApi.list()
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

    const uniqueSectorValues = [...new Set(churches.value.map(church => {
      const sector = church?.setor || church?.sector
      return sector
    }).filter(Boolean))]

    eventSectors.value = uniqueSectorValues
      .map(sectorValue => {
        const sectorKey = String(sectorValue).padStart(2, '0') // Garantir formato "00", "01", etc.
        const name = sectorNames[sectorKey] || sectorNames[sectorValue] || `Setor ${sectorValue}`
        return {
          value: sectorValue, // Manter o valor original (string)
          name: name
        }
      })
      .sort((a, b) => {
        const aStr = String(a.value).padStart(2, '0')
        const bStr = String(b.value).padStart(2, '0')
        if (aStr === '00') return -1
        if (bStr === '00') return 1
        return aStr.localeCompare(bStr)
      })
  } catch (error) {
    console.error('Erro ao carregar igrejas:', error)
    churches.value = []
    eventSectors.value = []
  }
}

function getChurchesBySector(selectedSector) {
  if (!selectedSector) {
    return churches.value
  }
  // Normalizar comparação: converter ambos para string e comparar
  const normalizedSelected = String(selectedSector).padStart(2, '0')
  return churches.value.filter(church => {
    const churchSector = String(church?.setor || church?.sector || '').padStart(2, '0')
    return churchSector === normalizedSelected
  })
}

function getEventRegistrations(eventIndex) {
  // Retorna array com índices de 0 até quantity-1 para cada ingresso
  const eventItem = cart.eventItems[eventIndex]
  if (!eventItem) return []
  return Array.from({ length: eventItem.quantity }, (_, i) => i)
}

function handleCopyBuyerData(checked, eventIndex, setFieldValue, formValues) {
  if (checked) {
    // Copiar nome, telefone e data de nascimento do comprador para o primeiro ingresso
    if (!formValues.name || !formValues.phone || !formValues.birth_date) {
      toastRef.value.open('Preencha primeiro os dados do comprador (nome, telefone e data de nascimento).', 'warning')
      return
    }
    
    setFieldValue(`events.${eventIndex}.registrations.0.name`, formValues.name)
    setFieldValue(`events.${eventIndex}.registrations.0.phone`, formValues.phone)
    setFieldValue(`events.${eventIndex}.registrations.0.birth_date`, formValues.birth_date)
    
    toastRef.value.open('Dados do comprador copiados para o primeiro ingresso!', 'success')
  } else {
    // Limpar dados do comprador do primeiro ingresso quando desmarcar
    setFieldValue(`events.${eventIndex}.registrations.0.name`, '')
    setFieldValue(`events.${eventIndex}.registrations.0.phone`, '')
    setFieldValue(`events.${eventIndex}.registrations.0.birth_date`, '')
  }
}

function handleCopyChurchData(checked, eventIndex, setFieldValue, formValues) {
  const eventItem = cart.eventItems[eventIndex]
  if (!eventItem || eventItem.quantity <= 1) return
  
  if (checked) {
    // Copiar afiliação, setor e congregação do primeiro ingresso (índice 0) para todos os demais
    const firstReg = formValues?.events?.[eventIndex]?.registrations?.[0]
    if (!firstReg || !firstReg.church_affiliation || !firstReg.sector || !firstReg.congregation) {
      toastRef.value.open('Preencha primeiro a afiliação, setor e congregação no primeiro ingresso.', 'warning')
      return
    }
    
    // Copiar afiliação, setor e congregação (não copiar tipo, position, etc)
    for (let i = 1; i < eventItem.quantity; i++) {
      setFieldValue(`events.${eventIndex}.registrations.${i}.church_affiliation`, firstReg.church_affiliation || '')
      setFieldValue(`events.${eventIndex}.registrations.${i}.sector`, firstReg.sector || '')
      setFieldValue(`events.${eventIndex}.registrations.${i}.congregation`, firstReg.congregation || '')
    }
    
    toastRef.value.open('Afiliação, setor e congregação copiados para todos os ingressos!', 'success')
  } else {
    // Limpar afiliação, setor e congregação dos ingressos subsequentes quando desmarcar
    for (let i = 1; i < eventItem.quantity; i++) {
      setFieldValue(`events.${eventIndex}.registrations.${i}.church_affiliation`, '')
      setFieldValue(`events.${eventIndex}.registrations.${i}.sector`, '')
      setFieldValue(`events.${eventIndex}.registrations.${i}.congregation`, '')
      setFieldValue(`events.${eventIndex}.registrations.${i}.church_type`, '')
      setFieldValue(`events.${eventIndex}.registrations.${i}.position`, '')
      setFieldValue(`events.${eventIndex}.registrations.${i}.other_church_name`, '')
    }
  }
}

function getInitialValues() {
  const hasPaidEvents = cart.eventItems.some(e => e.price > 0)
  const hasOnlyFreeEvents = cart.eventItems.length > 0 && !hasPaidEvents && cart.productItems.length === 0

  const base = {
    method: hasOnlyFreeEvents ? 'FREE' : null,
    installments: 1,
    name: 'Heber Almeida',
    cpf: '672.122.300-73',
    birth_date: '11/12/2001',
    email: 'eu@heber.com.br',
    phone: '(67) 99288-2549',
    postalCode: '79000000',
    addressNumber: '1000',
    addressComplement: '',
    address: 'Rua Exemplo',
    province: 'Centro',
    city: 'Campo Grande',
    state: 'MS',
    card: { holderName: 'Heber', number: '4444 4444 4444 4444', expiryMonth: '11', expiryYear: '2026', ccv: '123' },
    total: total.value
  }


  // Se houver eventos, adicionar estrutura de eventos com registrations individuais
  if (cart.eventItems.length > 0) {
    base.events = cart.eventItems.map((eventItem, eventIndex) => ({
      event_id: eventItem.eventId,
      quantity: eventItem.quantity,
      registrations: Array.from({ length: eventItem.quantity }, (_, i) => ({
        name: '',
        phone: '',
        birth_date: '',
        gender: '',
        church_affiliation: '',
        other_church_name: '',
        sector: '',
        congregation: '',
        church_type: '',
        position: '',
        whatsapp_authorization: true
      }))
    }))
  }

  return base
}

function validateCPF(v) {
  if (!v) return false
  const s = v.replace(/\D/g, '')
  if (s.length !== 11 || /^(\d)\1+$/.test(s)) return false
  let sum = 0
  for (let i = 0; i < 9; i++) sum += parseInt(s.charAt(i)) * (10 - i)
  let rev = 11 - (sum % 11)
  rev = rev >= 10 ? 0 : rev
  if (rev !== parseInt(s.charAt(9))) return false
  sum = 0
  for (let i = 0; i < 10; i++) sum += parseInt(s.charAt(i)) * (11 - i)
  rev = 11 - (sum % 11)
  rev = rev >= 10 ? 0 : rev
  return rev === parseInt(s.charAt(10))
}

const total = computed(() => cart.items.reduce((s, i) => s + i.price * i.quantity, 0))

const schema = computed(() => {
  const baseSchema = {
    name: yup.string().required('Nome é obrigatório'),
    cpf: yup.string().required('CPF é obrigatório').test('cpf', 'CPF inválido', v => validateCPF(v)),
    birth_date: yup.string().required('Data de nascimento é obrigatória').matches(/^\d{2}\/\d{2}\/\d{4}$/, 'Data inválida. Use o formato DD/MM/AAAA'),
    email: yup.string().email('Email inválido').required('Email é obrigatório'),
    phone: yup.string().required('Telefone é obrigatório'),
    method: yup.string().oneOf(['PIX', 'BOLETO', 'CREDIT_CARD', 'FREE'], 'Selecione um método').required('Selecione um método').test('available-method', 'Método de pagamento não disponível para este evento', function(value) {
      if (!value) return true
      if (cart.eventItems.length === 0) return true
      return availablePaymentMethods.value.includes(value)
    }),
    installments: yup.number().when('method', {
      is: 'CREDIT_CARD',
      then: s => s.required('Selecione o parcelamento').min(1).max(10),
      otherwise: s => s.notRequired()
    }),
    card: yup.mixed().when('method', {
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
    }),
    total: yup.number().min(500, 'O valor mínimo para pagar é R$ 5,00')
  }

  // Se houver produtos, exigir endereço
  if (cart.productItems.length > 0) {
    baseSchema.postalCode = yup.string().required('CEP é obrigatório').matches(/^\d{5}-?\d{3}$/, 'CEP inválido')
    baseSchema.addressNumber = yup.string().required('Número é obrigatório')
    baseSchema.address = yup.string().required('Endereço é obrigatório')
    baseSchema.province = yup.string().required('Bairro é obrigatório')
    baseSchema.city = yup.string().required('Cidade é obrigatória')
    baseSchema.state = yup.string().required('Estado é obrigatório').length(2, 'UF deve ter 2 letras')
    baseSchema.addressComplement = yup.string()
  }

  // Se houver eventos pagos, exigir endereço
  const hasPaidEvents = cart.eventItems.some(e => e.price > 0)
  if (cart.eventItems.length > 0 && hasPaidEvents && cart.productItems.length === 0) {
    baseSchema.postalCode = yup.string().required('CEP é obrigatório').matches(/^\d{5}-?\d{3}$/, 'CEP inválido')
    baseSchema.addressNumber = yup.string().required('Número é obrigatório')
    baseSchema.address = yup.string().required('Endereço é obrigatório')
    baseSchema.province = yup.string().required('Bairro é obrigatório')
    baseSchema.city = yup.string().required('Cidade é obrigatória')
    baseSchema.state = yup.string().required('Estado é obrigatório').length(2, 'UF deve ter 2 letras')
    baseSchema.addressComplement = yup.string()
  }

  // Se houver eventos, adicionar validação de inscrições (cada ingresso deve ter nome e telefone únicos)
  if (cart.eventItems.length > 0) {
    baseSchema.events = yup.array().of(
      yup.object({
        event_id: yup.number().required(),
        quantity: yup.number().required(),
        registrations: yup.array().of(
          yup.object({
            name: yup.string().required('Nome completo é obrigatório').min(3, 'Nome deve ter pelo menos 3 caracteres'),
            phone: yup.string().required('Telefone é obrigatório'),
            birth_date: yup.string().required('Data de nascimento é obrigatória').matches(/^\d{2}\/\d{2}\/\d{4}$/, 'Data inválida. Use o formato DD/MM/AAAA'),
            gender: yup.string().required('Gênero é obrigatório'),
            church_affiliation: yup.string().required('Afiliação é obrigatória').oneOf(['ASSEMBLEIA', 'OUTRA_IGREJA', 'NAO_EVANGELICO'], 'Selecione uma afiliação válida'),
            other_church_name: yup.string().when('church_affiliation', {
              is: 'OUTRA_IGREJA',
              then: s => s.required('Nome da igreja é obrigatório'),
              otherwise: s => s.nullable()
            }),
            sector: yup.string().when('church_affiliation', {
              is: 'ASSEMBLEIA',
              then: s => s.required('Setor é obrigatório para Assembleia'),
              otherwise: s => s.nullable()
            }),
            congregation: yup.string().when('church_affiliation', {
              is: 'ASSEMBLEIA',
              then: s => s.required('Congregação é obrigatória para Assembleia'),
              otherwise: s => s.nullable()
            }),
            church_type: yup.string().when('church_affiliation', {
              is: 'ASSEMBLEIA',
              then: s => s.required('Tipo é obrigatório para Assembleia'),
              otherwise: s => s.nullable()
            }),
            position: yup.string().when(['church_affiliation', 'church_type'], {
              is: (affiliation, type) => (affiliation === 'ASSEMBLEIA' || affiliation === 'OUTRA_IGREJA') && type === 'OUTRO',
              then: s => s.required('Cargo é obrigatório quando tipo é "Outro"'),
              otherwise: s => s.nullable()
            }),
            whatsapp_authorization: yup.boolean(),
          })
        )
      })
    ).min(1, 'Pelo menos um evento é obrigatório')
  }

  // Ajustar total mínimo: se houver apenas eventos gratuitos, não precisa de mínimo
  if (cart.productItems.length === 0 && !hasPaidEvents) {
    baseSchema.total = yup.number().min(0)
  }

  // Se houver apenas eventos gratuitos, método deve ser FREE automaticamente
  if (cart.productItems.length === 0 && !hasPaidEvents) {
    baseSchema.method = yup.string().oneOf(['FREE'], 'Eventos gratuitos não requerem pagamento').default('FREE')
  }

  return yup.object(baseSchema)
})

const installmentOptions = computed(() => {
  const max = 10
  const fix = 49 // R$ 0,49 em centavos
  const options = []
  
  // À vista: R$ 0,49 + 2,99% + (1,70% × 1 mês)
  const vistaPercent = 2.99 + (1.70 * 1) // 2,99% fixo + 1,70% ao mês
  const vistaTotal = total.value + fix + total.value * (vistaPercent / 100)
  options.push({
    count: 1,
    parcela: vistaTotal,
    total: vistaTotal,
    percent: vistaPercent
  })
  
  // Regras de valor mínimo:
  // 2x: mínimo R$ 60 (6000 centavos)
  // 3x: mínimo R$ 60 (6000 centavos) - seguindo a regra de 2x
  // 4x: mínimo R$ 120 (12000 centavos)
  // 5x até 10x: mínimo R$ 120 (12000 centavos) - seguindo a regra de 4x
  
  for (let n = 2; n <= max; n++) {
    // Verificar valor mínimo baseado no número de parcelas
    let minValue = 0
    if (n === 2 || n === 3) {
      minValue = 6000 // R$ 60 para 2x e 3x
    } else if (n >= 4) {
      minValue = 12000 // R$ 120 para 4x e acima
    }
    
    // Só adicionar se o valor total atender ao mínimo
    if (total.value >= minValue) {
      let percentFixo = 0
      if (n >= 2 && n <= 6) {
        // 2 à 6 parcelas: R$ 0,49 + 3,49% (fixo) + (1,70% × n meses)
        percentFixo = 3.49
      } else if (n >= 7 && n <= 12) {
        // 7 à 12 parcelas: R$ 0,49 + 3,99% (fixo) + (1,70% × n meses)
        percentFixo = 3.99
      }
      
      // Taxa total = taxa fixa + (1,70% × número de meses)
      const percent = percentFixo + (1.70 * n)
      const totalComTaxa = total.value + fix + total.value * (percent / 100)
      const parcela = totalComTaxa / n
      options.push({ count: n, parcela, total: totalComTaxa, percent })
    }
  }
  
  return options
})

function grandTotal(vals) {
  const fix = 49 // R$ 0,49 em centavos
  if (!vals?.method) return total.value
  // PIX não tem taxa - sempre retornar valor sem taxa
  if (vals.method === 'PIX') return total.value
  if (vals.method === 'BOLETO' && chargeBoleto) return total.value + 199
  if (vals.method === 'CREDIT_CARD' && chargeCard) {
    const n = Number(vals.installments || 1)
    let percentFixo = 0
    if (n === 1) {
      // À vista: R$ 0,49 + 2,99% (fixo) + (1,70% × 1 mês)
      percentFixo = 2.99
    } else if (n >= 2 && n <= 6) {
      // 2 à 6 parcelas: R$ 0,49 + 3,49% (fixo) + (1,70% × n meses)
      percentFixo = 3.49
    } else if (n >= 7 && n <= 12) {
      // 7 à 12 parcelas: R$ 0,49 + 3,99% (fixo) + (1,70% × n meses)
      percentFixo = 3.99
    }
    // Taxa total = taxa fixa + (1,70% × número de meses)
    const percent = percentFixo + (1.70 * n)
    return total.value + fix + total.value * (percent / 100)
  }
  return total.value
}

function cardTax(vals) {
  if (vals.method !== 'CREDIT_CARD' || !chargeCard) return null
  const n = Number(vals.installments || 1)
  if (n === 1) return `R$ 0,49 + 2,99% + 1,70%`
  if (n >= 2 && n <= 6) return `R$ 0,49 + 3,49% + ${(1.70 * n).toFixed(2)}%`
  if (n >= 7 && n <= 12) return `R$ 0,49 + 3,99% + ${(1.70 * n).toFixed(2)}%`
  return null
}

function buildPayload(vals) {
  let tax = { fix: 0, percent: 0 }
  // PIX não tem taxa - sempre enviar tax = { fix: 0, percent: 0 }
  if (vals.method === 'PIX') tax = { fix: 0, percent: 0 }
  if (vals.method === 'BOLETO' && chargeBoleto) tax = { fix: 199, percent: 0 }
  if (vals.method === 'CREDIT_CARD' && chargeCard) {
    const n = Number(vals.installments || 1)
    tax = { fix: 49, percent: parseFloat(cardPercent(n).toFixed(3)) }
  }

  // Separar produtos (eventos são processados separadamente na página de inscrição)
  const productItems = cart.productItems.map(i => ({ variant_id: i.variantId, quantity: i.quantity }))

  const payload = {
    buyer: {
      name: vals.name,
      cpf: vals.cpf.replace(/\D/g, ''),
      email: vals.email,
      phone: vals.phone.replace(/\D/g, ''),
      postalCode: vals.postalCode.replace(/\D/g, ''),
      addressNumber: vals.addressNumber,
      addressComplement: vals.addressComplement,
      address: vals.address,
      province: vals.province,
      city: vals.city,
      state: vals.state
    },
    items: productItems,
    payment: {
      method: vals.method,
      ...(vals.method === 'CREDIT_CARD'
        ? {
          card: {
            holderName: vals.card.holderName,
            number: vals.card.number.replace(/\D/g, ''),
            expiryMonth: vals.card.expiryMonth,
            expiryYear: vals.card.expiryYear,
            ccv: vals.card.ccv
          },
          installments: Number(vals.installments)
        }
        : {}),
      tax
    }
  }
  return payload
}

function cardPercent(n) {
  let percentFixo = 0
  if (n === 1) {
    // À vista: R$ 0,49 + 2,99% (fixo) + (1,70% × 1 mês)
    percentFixo = 2.99
  } else if (n >= 2 && n <= 6) {
    // 2 à 6 parcelas: R$ 0,49 + 3,49% (fixo) + (1,70% × n meses)
    percentFixo = 3.49
  } else if (n >= 7 && n <= 12) {
    // 7 à 12 parcelas: R$ 0,49 + 3,99% (fixo) + (1,70% × n meses)
    percentFixo = 3.99
  }
  // Taxa total = taxa fixa + (1,70% × número de meses)
  return percentFixo + (1.70 * n)
}

function handleCepInput(val, setFieldValue) {
  const cleanCep = String(val || '').replace(/\D/g, '')
  clearTimeout(cepTimer)
  if (cleanCep.length === 8) {
    cepTimer = setTimeout(() => fetchCep(cleanCep, setFieldValue), 700)
  }
}

async function fetchCep(cep, setFieldValue) {
  const cleanCep = (cep || '').replace(/\D/g, '')
  if (cleanCep.length !== 8) return
  loadingCep.value = true
  try {
    const { data } = await http.get(`/cep/${cleanCep}`)
    setFieldValue('address', data.logradouro)
    setFieldValue('province', data.bairro)
    setFieldValue('city', data.localidade)
    setFieldValue('state', data.uf)
  } catch (e) {
    console.error(e.response?.data || e.message)
    toastRef.value.open('CEP não encontrado.', 'warning')
  } finally {
    loadingCep.value = false
  }
}

function buildEventPayload(vals) {
  const events = cart.eventItems.map((eventItem, eventIndex) => {
    const eventData = vals.events?.[eventIndex] || { registrations: [] }

    // Processar cada registro individualmente
    const registrations = eventData.registrations.map((reg) => {
      const registration = {
        name: reg.name,
        phone: reg.phone.replace(/\D/g, ''),
        cpf: reg.cpf ? reg.cpf.replace(/\D/g, '') : null,
        birth_date: reg.birth_date,
        gender: reg.gender,
        church_affiliation: reg.church_affiliation,
        other_church_name: reg.church_affiliation === 'OUTRA_IGREJA' ? reg.other_church_name : null,
        sector: reg.church_affiliation === 'ASSEMBLEIA' ? reg.sector : null,
        congregation: reg.church_affiliation === 'ASSEMBLEIA' ? reg.congregation : null,
        church_type: (reg.church_affiliation === 'ASSEMBLEIA' || reg.church_affiliation === 'OUTRA_IGREJA') ? reg.church_type : null,
        position: ((reg.church_affiliation === 'ASSEMBLEIA' || reg.church_affiliation === 'OUTRA_IGREJA') && reg.church_type === 'OUTRO') ? reg.position : null,
        whatsapp_authorization: reg.whatsapp_authorization || false,
      }

      // Converter data de DD/MM/AAAA para AAAA-MM-DD
      if (registration.birth_date && registration.birth_date.includes('/')) {
        const [day, month, year] = registration.birth_date.split('/')
        registration.birth_date = `${year}-${month}-${day}`
      }

      return registration
    })

    return {
      event_id: eventItem.eventId,
      quantity: eventItem.quantity,
      registrations: registrations
    }
  })

  // Converter birth_date de DD/MM/AAAA para AAAA-MM-DD
  let buyerBirthDate = vals.birth_date
  if (buyerBirthDate && buyerBirthDate.includes('/')) {
    const [day, month, year] = buyerBirthDate.split('/')
    buyerBirthDate = `${year}-${month}-${day}`
  }

  const payload = {
    buyer: {
      name: vals.name,
      cpf: vals.cpf.replace(/\D/g, ''),
      birth_date: buyerBirthDate,
      email: vals.email,
      phone: vals.phone.replace(/\D/g, ''),
      postalCode: vals.postalCode.replace(/\D/g, ''),
      addressNumber: vals.addressNumber,
      addressComplement: vals.addressComplement,
      address: vals.address,
      province: vals.province,
      city: vals.city,
      state: vals.state.toUpperCase()
    },
    events: events,
    payment: {
      method: vals.method,
    }
  }

  if (vals.method === 'CREDIT_CARD' && vals.card) {
    payload.payment.card = {
      holderName: vals.card.holderName,
      number: vals.card.number.replace(/\D/g, ''),
      expiryMonth: vals.card.expiryMonth,
      expiryYear: vals.card.expiryYear,
      ccv: vals.card.ccv
    }
    payload.payment.installments = Number(vals.installments || 1)
  }

  return payload
}

async function onSubmit(vals, { setErrors, setFieldError, setTouched }) {
  try {
    // Primeiro, marcar todos os campos como "touched" para exibir erros
    // Isso força o vee-validate a mostrar os erros visualmente
    
    // Verificar se há eventos pagos (usado em múltiplos lugares)
    const hasPaidEvents = cart.eventItems.some(e => e.price > 0)
    
    // Marcar campos do comprador
    setTouched('name', true)
    setTouched('cpf', true)
    setTouched('birth_date', true)
    setTouched('phone', true)
    setTouched('email', true)
    
    // Marcar campos de endereço
    if ((hasPaidEvents && cart.productItems.length === 0) || cart.productItems.length > 0) {
      setTouched('postalCode', true)
      setTouched('address', true)
      setTouched('addressNumber', true)
      setTouched('province', true)
      setTouched('city', true)
      setTouched('state', true)
    }
    
    // Marcar método de pagamento
    setTouched('method', true)
    
    // Marcar campos do cartão se necessário
    if (vals.method === 'CREDIT_CARD') {
      setTouched('card.holderName', true)
      setTouched('card.number', true)
      setTouched('card.expiryMonth', true)
      setTouched('card.expiryYear', true)
      setTouched('card.ccv', true)
      setTouched('installments', true)
    }
    
    // Marcar campos dos ingressos
    if (cart.eventItems.length > 0 && vals.events) {
      vals.events.forEach((event, eventIndex) => {
        if (event.registrations) {
          event.registrations.forEach((reg, regIndex) => {
            setTouched(`events.${eventIndex}.registrations.${regIndex}.name`, true)
            setTouched(`events.${eventIndex}.registrations.${regIndex}.phone`, true)
            setTouched(`events.${eventIndex}.registrations.${regIndex}.birth_date`, true)
            setTouched(`events.${eventIndex}.registrations.${regIndex}.gender`, true)
            setTouched(`events.${eventIndex}.registrations.${regIndex}.church_affiliation`, true)
            
            if (reg.church_affiliation === 'ASSEMBLEIA') {
              setTouched(`events.${eventIndex}.registrations.${regIndex}.sector`, true)
              setTouched(`events.${eventIndex}.registrations.${regIndex}.congregation`, true)
              setTouched(`events.${eventIndex}.registrations.${regIndex}.church_type`, true)
              if (reg.church_type === 'OUTRO') {
                setTouched(`events.${eventIndex}.registrations.${regIndex}.position`, true)
              }
            }
            
            if (reg.church_affiliation === 'OUTRA_IGREJA') {
              setTouched(`events.${eventIndex}.registrations.${regIndex}.other_church_name`, true)
              if (reg.church_type === 'OUTRO') {
                setTouched(`events.${eventIndex}.registrations.${regIndex}.position`, true)
              }
            }
          })
        }
      })
    }
    
    // Validar todos os campos antes de processar
    const validationErrors = {}
    let hasErrors = false
    
    // Validar campos do comprador
    if (!vals.name || !vals.name.trim()) {
      validationErrors.name = 'Nome completo é obrigatório'
      hasErrors = true
    }
    if (!vals.cpf || !vals.cpf.replace(/\D/g, '')) {
      validationErrors.cpf = 'CPF é obrigatório'
      hasErrors = true
    }
    if (!vals.birth_date) {
      validationErrors.birth_date = 'Data de nascimento é obrigatória'
      hasErrors = true
    }
    if (!vals.phone || !vals.phone.replace(/\D/g, '')) {
      validationErrors.phone = 'Telefone é obrigatório'
      hasErrors = true
    }
    if (!vals.email || !vals.email.trim()) {
      validationErrors.email = 'Email é obrigatório'
      hasErrors = true
    }
    
    // Validar endereço se necessário
    if ((hasPaidEvents && cart.productItems.length === 0) || cart.productItems.length > 0) {
      if (!vals.postalCode || !vals.postalCode.replace(/\D/g, '')) {
        validationErrors.postalCode = 'CEP é obrigatório'
        hasErrors = true
      }
      if (!vals.address || !vals.address.trim()) {
        validationErrors.address = 'Endereço é obrigatório'
        hasErrors = true
      }
      if (!vals.addressNumber || !vals.addressNumber.trim()) {
        validationErrors.addressNumber = 'Número é obrigatório'
        hasErrors = true
      }
      if (!vals.province || !vals.province.trim()) {
        validationErrors.province = 'Bairro é obrigatório'
        hasErrors = true
      }
      if (!vals.city || !vals.city.trim()) {
        validationErrors.city = 'Cidade é obrigatória'
        hasErrors = true
      }
      if (!vals.state || !vals.state.trim()) {
        validationErrors.state = 'Estado é obrigatório'
        hasErrors = true
      }
    }
    
    // Validar método de pagamento
    if (!vals.method) {
      validationErrors.method = 'Método de pagamento é obrigatório'
      hasErrors = true
    }
    
    // Validar cartão de crédito se necessário
    if (vals.method === 'CREDIT_CARD') {
      if (!vals.card || !vals.card.holderName || !vals.card.holderName.trim()) {
        validationErrors['card.holderName'] = 'Nome no cartão é obrigatório'
        hasErrors = true
      }
      if (!vals.card || !vals.card.number || vals.card.number.replace(/\D/g, '').length < 13) {
        validationErrors['card.number'] = 'Número do cartão é obrigatório'
        hasErrors = true
      }
      if (!vals.card || !vals.card.expiryMonth) {
        validationErrors['card.expiryMonth'] = 'Mês de validade é obrigatório'
        hasErrors = true
      }
      if (!vals.card || !vals.card.expiryYear) {
        validationErrors['card.expiryYear'] = 'Ano de validade é obrigatório'
        hasErrors = true
      }
      if (!vals.card || !vals.card.ccv || vals.card.ccv.length !== 3) {
        validationErrors['card.ccv'] = 'CVV é obrigatório'
        hasErrors = true
      }
      if (!vals.installments) {
        validationErrors.installments = 'Parcelamento é obrigatório'
        hasErrors = true
      }
    }
    
    // Validar registros de eventos
    if (cart.eventItems.length > 0 && vals.events) {
      vals.events.forEach((event, eventIndex) => {
        if (event.registrations) {
          event.registrations.forEach((reg, regIndex) => {
            if (!reg.name || !reg.name.trim()) {
              validationErrors[`events.${eventIndex}.registrations.${regIndex}.name`] = 'Nome completo é obrigatório'
              hasErrors = true
            }
            if (!reg.phone || !reg.phone.replace(/\D/g, '')) {
              validationErrors[`events.${eventIndex}.registrations.${regIndex}.phone`] = 'Telefone é obrigatório'
              hasErrors = true
            }
            if (!reg.birth_date) {
              validationErrors[`events.${eventIndex}.registrations.${regIndex}.birth_date`] = 'Data de nascimento é obrigatória'
              hasErrors = true
            }
            if (!reg.gender) {
              validationErrors[`events.${eventIndex}.registrations.${regIndex}.gender`] = 'Gênero é obrigatório'
              hasErrors = true
            }
            if (!reg.church_affiliation) {
              validationErrors[`events.${eventIndex}.registrations.${regIndex}.church_affiliation`] = 'Afiliação é obrigatória'
              hasErrors = true
            }
            
            // Validações condicionais
            if (reg.church_affiliation === 'ASSEMBLEIA') {
              if (!reg.sector) {
                validationErrors[`events.${eventIndex}.registrations.${regIndex}.sector`] = 'Setor é obrigatório para Assembleia'
                hasErrors = true
              }
              if (!reg.congregation) {
                validationErrors[`events.${eventIndex}.registrations.${regIndex}.congregation`] = 'Congregação é obrigatória para Assembleia'
                hasErrors = true
              }
              if (!reg.church_type) {
                validationErrors[`events.${eventIndex}.registrations.${regIndex}.church_type`] = 'Tipo é obrigatório para Assembleia'
                hasErrors = true
              }
              if (reg.church_type === 'OUTRO' && (!reg.position || !reg.position.trim())) {
                validationErrors[`events.${eventIndex}.registrations.${regIndex}.position`] = 'Cargo é obrigatório quando tipo é "Outro"'
                hasErrors = true
              }
            }
            
            if (reg.church_affiliation === 'OUTRA_IGREJA') {
              if (!reg.other_church_name || !reg.other_church_name.trim()) {
                validationErrors[`events.${eventIndex}.registrations.${regIndex}.other_church_name`] = 'Nome da igreja é obrigatório'
                hasErrors = true
              }
              if (reg.church_type === 'OUTRO' && (!reg.position || !reg.position.trim())) {
                validationErrors[`events.${eventIndex}.registrations.${regIndex}.position`] = 'Cargo é obrigatório quando tipo é "Outro"'
                hasErrors = true
              }
            }
          })
        }
      })
    }
    
    // Se houver erros, exibir e parar
    if (hasErrors) {
      setErrors(validationErrors)
      loadingPay.value = false
      
      // Fazer scroll para o primeiro erro
      setTimeout(() => {
        // Procurar por campos com erro (border-red-600 ou ErrorMessage visível)
        const firstErrorField = document.querySelector('.border-red-600') || 
                               document.querySelector('[class*="text-red-600"]') ||
                               document.querySelector('input:invalid') ||
                               document.querySelector('select:invalid')
        
        if (firstErrorField) {
          firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' })
          if (firstErrorField.focus) {
            firstErrorField.focus()
          }
        } else {
          // Se não encontrar campo, fazer scroll para o topo do formulário
          const formElement = document.querySelector('form') || document.querySelector('[class*="Form"]')
          if (formElement) {
            formElement.scrollIntoView({ behavior: 'smooth', block: 'start' })
          }
        }
      }, 200)
      
      toastRef.value.open('Por favor, preencha todos os campos obrigatórios corretamente. Os campos com erro estão destacados em vermelho.', 'error')
      return
    }

    // Validar nomes e telefones únicos entre todos os ingressos
    if (cart.eventItems.length > 0 && vals.events) {
      const allNames = []
      const allPhones = []
      
      vals.events.forEach(event => {
        if (event.registrations) {
          event.registrations.forEach((reg, regIndex) => {
            if (reg.name) {
              const name = reg.name.trim().toLowerCase()
              if (allNames.includes(name)) {
                toastRef.value.open('Não é permitido ter nomes iguais entre os ingressos.', 'error')
                loadingPay.value = false
                return
              }
              allNames.push(name)
            }
            if (reg.phone) {
              const phone = reg.phone.replace(/\D/g, '')
              if (allPhones.includes(phone)) {
                toastRef.value.open('Não é permitido ter telefones iguais entre os ingressos.', 'error')
                loadingPay.value = false
                return
              }
              allPhones.push(phone)
            }
          })
        }
      })
    }

    // Se houver apenas eventos, processar eventos
    if (cart.eventItems.length > 0 && cart.productItems.length === 0) {
      const payload = buildEventPayload(vals)
      const { data } = await http.post('/checkout/events', payload)

      // Se for evento gratuito, mostrar direto
      if (data.payment.method === 'FREE' || data.payment.amount === 0) {
        cart.clear()
        const registrationsParam = encodeURIComponent(JSON.stringify(data.registrations))
        router.push(`/registration-success?registrations=${registrationsParam}`)
        return
      }

      // Para todos os métodos de pagamento (PIX, Boleto e Cartão), redirecionar para página de pagamento
      // Isso garante que cartão de crédito também mostre a página event-payment com status "Pago"
      if (data.payment.method === 'PIX' || data.payment.method === 'BOLETO' || data.payment.method === 'CREDIT_CARD') {
        // Passar dados do pagamento via query params
        const paymentParam = encodeURIComponent(JSON.stringify(data.payment))
        cart.clear()
        router.push(`/event-payment?payment=${paymentParam}`)
        return
      }

      // Limpar carrinho
      cart.clear()
    }

    // Se houver produtos, processar normalmente
    if (cart.productItems.length > 0) {
      const payload = buildPayload(vals)
      const { data } = await http.post('/checkout', payload)
      response.value = data

      // Se houver eventos também, processar eventos separadamente
      if (cart.eventItems.length > 0) {
        const eventPayload = buildEventPayload(vals)
        const eventData = await http.post('/checkout/events', eventPayload)

        // Remover produtos do carrinho
        cart.items = cart.items.filter(i => i.type === 'event')

        // Verificar se eventos são gratuitos
        const hasFreeEvents = eventData.data.payment.method === 'FREE' || eventData.data.payment.amount === 0

        if (data.payment.method === 'PIX') {
          router.push({ name: 'payment', params: { orderNumber: data.orderNumber } })
          // Eventos só serão mostrados após confirmação de pagamento (via webhook)
          if (hasFreeEvents) {
            toastRef.value.open('Produtos: aguarde confirmação do pagamento. Eventos gratuitos: verifique suas inscrições.', 'info')
          } else {
            toastRef.value.open('Aguarde a confirmação do pagamento para visualizar seus ingressos.', 'info')
          }
        } else if (data.payment.method === 'BOLETO') {
          router.push({ name: 'payment', params: { orderNumber: data.orderNumber } })
          // Eventos só serão mostrados após confirmação de pagamento (via webhook)
          if (hasFreeEvents) {
            toastRef.value.open('Produtos: aguarde confirmação do pagamento. Eventos gratuitos: verifique suas inscrições.', 'info')
          } else {
            toastRef.value.open('Aguarde a confirmação do pagamento para visualizar seus ingressos.', 'info')
          }
        } else if (data.payment.method === 'CREDIT_CARD') {
          router.push({ name: 'payment', params: { orderNumber: data.orderNumber } })
          if (data.payment.creditCard.status === 'CONFIRMED') {
            // Se produtos foram pagos e eventos são gratuitos ou pagos e confirmados
            if (hasFreeEvents) {
              // Eventos gratuitos: mostrar registration-success
              setTimeout(() => {
                const registrationsParam = encodeURIComponent(JSON.stringify(eventData.data.registrations))
                router.push(`/registration-success?registrations=${registrationsParam}`)
              }, 2000)
              return
            } else if (eventData.data.payment.creditCard?.status === 'CONFIRMED') {
              // Eventos pagos confirmados: mostrar event-payment (igual PIX/Boleto)
              setTimeout(() => {
                const paymentParam = encodeURIComponent(JSON.stringify(eventData.data.payment))
                router.push(`/event-payment?payment=${paymentParam}`)
              }, 2000)
              return
            }
          }
        }
        return
      }

      // Apenas produtos, processar normalmente
      if (data.payment.method === 'PIX') {
        cart.clear()
        router.push({ name: 'payment', params: { orderNumber: data.orderNumber } })
      } else if (data.payment.method === 'BOLETO') {
        cart.clear()
        router.push({ name: 'payment', params: { orderNumber: data.orderNumber } })
      } else if (data.payment.method === 'CREDIT_CARD') {
        router.push({ name: 'payment', params: { orderNumber: data.orderNumber } })
        if (data.payment.creditCard.status === 'CONFIRMED') {
          cart.clear()
        }
      }
    }
  } catch (e) {
    console.error(e.response?.data || e.message)
    const backendErrors = e.response?.data?.error
    if (backendErrors) {
      try {
        const parsed = JSON.parse(backendErrors.match(/\{.*\}/)[0])
        parsed.errors.forEach(err => {
          toastRef.value.open(err.description, 'error')
        })
      } catch {
        toastRef.value.open(e.response?.data?.message || 'Erro inesperado.', 'error')
      }
    } else {
      toastRef.value.open(e.response?.data?.message || 'Erro inesperado.', 'error')
    }
  } finally {
    loadingPay.value = false
  }
}
</script>

<style>
.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>
