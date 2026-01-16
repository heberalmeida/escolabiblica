<template>
  <div class="bg-gray-50">
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-bold flex-1 text-center sm:text-left flex items-center gap-3 text-gray-800">
          <font-awesome-icon :icon="['fas', 'ticket-alt']" class="text-blue-600" />
          Minhas Inscrições
        </h1>

        <button
          v-if="(savedCpf && savedBirthDate) || registrations.length"
          @click="logout"
          class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm cursor-pointer inline-flex items-center gap-2">
          <font-awesome-icon :icon="['fas', 'sign-out-alt']" />
          Sair
        </button>
      </div>

      <form @submit.prevent="search"
        class="flex flex-col gap-4 justify-center mb-10 max-w-2xl mx-auto">
        <div class="flex flex-col sm:flex-row gap-4">
          <input v-model="cpf" v-maska="'###.###.###-##'" placeholder="CPF"
            class="flex-1 bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
          <input v-model="birthDate" v-maska="'##/##/####'" placeholder="Nascimento"
            class="flex-1 bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
        </div>
        <button 
          type="submit" 
          :disabled="loading || !cpf || !birthDate"
          class="bg-blue-600 text-white px-6 py-2 rounded-lg cursor-pointer hover:bg-blue-700 disabled:bg-gray-400 transition">
          {{ loading ? 'Buscando...' : 'Buscar Inscrições' }}
        </button>
      </form>

      <div v-if="loading">
        <div class="flex justify-center py-10">
          <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
      </div>
      <div v-else>
        <p v-if="error" class="text-red-600 mb-6 text-center">{{ error }}</p>

        <div v-if="orders.length">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="order in orders" :key="order.id"
              class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden group flex flex-col p-4 border hover:border-blue-200">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 group-hover:bg-blue-100">
                  <font-awesome-icon :icon="['fas', 'shopping-cart']" class="text-blue-600 text-lg" />
                </div>
                <div>
                  <p class="font-bold text-lg leading-tight">Pedido</p>
                  <p class="text-xs text-gray-500">{{ order.payment_id || 'Gratuito' }}</p>
                </div>
              </div>
              <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                :class="statusPillClass(order.payment_status)">
                <font-awesome-icon :icon="statusIcon(order.payment_status)" />
                {{ statusLabel(order.payment_status) }}
              </span>
            </div>

            <div class="flex flex-col gap-2 text-sm text-gray-600 mb-4">
              <div>
                <strong>Inscrições:</strong>
                <span class="font-bold text-gray-900">{{ order.registrations_count || order.registrations?.length || 0 }}</span>
              </div>
              <div>
                <strong>Valor Total:</strong>
                <span class="font-bold text-gray-900 text-lg">
                  {{ displayTotal(order) }}
                </span>
                <span v-if="isCardInstallments(order)" class="text-xs text-gray-500 ml-2">
                  ({{ installmentCount(order) }}x de {{ formatBRL(perInstallmentCents(order)) }})
                </span>
              </div>
              <div v-if="order.payment_method">
                <strong>Método de Pagamento:</strong> {{ formatPaymentMethod(order.payment_method) }}
              </div>
              <div><strong>Data do Pedido:</strong> {{ formatDateTime(order.created_at) }}</div>
              
              <!-- Lista resumida de inscrições -->
              <div class="mt-2 pt-2 border-t border-gray-200">
                <p class="text-xs font-semibold text-gray-500 mb-1">Inscrições neste pedido:</p>
                <div class="space-y-1">
                  <div v-for="reg in order.registrations" :key="reg.id" class="text-xs text-gray-600">
                    • {{ reg.name }} - {{ reg.event?.name || 'Evento' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-auto space-y-2">
              <div v-if="order.payment_status === 'paid'" class="space-y-2">
                <button @click="printOrder(order)"
                  class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center justify-center gap-2">
                  <font-awesome-icon :icon="['fas', 'print']" />
                  Imprimir Inscrições ({{ order.registrations_count || order.registrations?.length || 0 }})
                </button>
                
                <!-- Botão de Comprovante -->
                <a v-if="getReceiptUrl(order)" :href="getReceiptUrl(order)" target="_blank"
                  class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center justify-center gap-2">
                  <font-awesome-icon :icon="['fas', 'receipt']" />
                  Ver Comprovante
                </a>
              </div>
              <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-sm text-yellow-700">
                <div class="flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'hourglass-half']" />
                  <span>Aguardando confirmação do pagamento</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <Pagination v-if="orders.length" class="mt-8" :current-page="currentPage" :last-page="lastPage"
          @change="page => search(false, page)" />
        </div>

        <p v-if="!orders.length && !error" class="text-gray-600 text-center mt-10">Nenhum pedido encontrado.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { registrationsApi } from '@/api/events'
import { FontAwesomeIcon } from '@/plugins/fontawesome'
import QRCode from 'qrcode'
import Pagination from '@/components/Pagination.vue'
import { vMaska } from 'maska/vue'
import { db, ref as dbRef, onValue } from '@/firebase'

function useSessionStorage(key, initialValue = null) {
  const stored = localStorage.getItem(key)
  const data = ref(stored ? JSON.parse(stored) : initialValue)
  function set(value) {
    data.value = value
    localStorage.setItem(key, JSON.stringify(value))
  }
  function clear() {
    data.value = null
    localStorage.removeItem(key)
  }
  return { data, set, clear }
}

const cpf = ref('')
const birthDate = ref('')
const registrations = ref([])
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const lastPage = ref(1)
const { data: savedCpf, set: setCpfSession, clear: clearCpfSession } = useSessionStorage('user_cpf_registrations', '')
const { data: savedBirthDate, set: setBirthDateSession, clear: clearBirthDateSession } = useSessionStorage('user_birth_date_registrations', '')
const unsubscribeFns = ref([])
let lastUpdateToken = null

const orders = ref([]) // Pedidos agrupados
const allRegistrations = ref([]) // Todas as registrations para impressão

onMounted(() => {
  // Se houver dados salvos de CPF, usar busca por CPF
  if (savedCpf.value && savedBirthDate.value) {
    cpf.value = savedCpf.value
    // Se a data salva está em formato AAAA-MM-DD, converter para DD/MM/AAAA
    if (savedBirthDate.value.includes('-') && savedBirthDate.value.length === 10) {
      const [year, month, day] = savedBirthDate.value.split('-')
      birthDate.value = `${day}/${month}/${year}`
    } else {
      birthDate.value = savedBirthDate.value
    }
    search(true, 1)
  }
})

// Observar mudanças nos pedidos para gerar QR codes
watch([orders, allRegistrations], async () => {
  await nextTick()
  await generateQRCodes()
}, { deep: true })

async function search(setupListeners = false, page = 1) {
  loading.value = true
  error.value = null
  orders.value = []
  allRegistrations.value = []
  
  try {
    const cleanCpf = cpf.value.replace(/\D/g, '')
    if (!validateCpf(cleanCpf)) {
      error.value = 'CPF inválido.'
      loading.value = false
      return
    }
    
    if (!birthDate.value) {
      error.value = 'Por favor, informe a data de nascimento do comprador.'
      loading.value = false
      return
    }
    
    // Validar formato da data DD/MM/AAAA
    if (!/^\d{2}\/\d{2}\/\d{4}$/.test(birthDate.value)) {
      error.value = 'Data de nascimento inválida. Use o formato DD/MM/AAAA.'
      loading.value = false
      return
    }
    
    // Converter data de DD/MM/AAAA para AAAA-MM-DD
    const [day, month, year] = birthDate.value.split('/')
    const formattedBirthDate = `${year}-${month}-${day}`
    
    // Validar se é uma data válida
    const dateObj = new Date(year, month - 1, day)
    if (dateObj.getFullYear() != year || dateObj.getMonth() != month - 1 || dateObj.getDate() != day) {
      error.value = 'Data de nascimento inválida.'
      loading.value = false
      return
    }
    
    // Buscar registrations agrupadas por pagamento usando CPF e data de nascimento do COMPRADOR
    const response = await registrationsApi.getByCpf(cleanCpf, { 
      page, 
      per_page: 12, 
      group_by_payment: true,
      birth_date: formattedBirthDate
    })
    const data = response.data
    
    if (data.data && Array.isArray(data.data)) {
      // Dados já vêm agrupados do backend
      orders.value = data.data
      
      // Coletar todas as registrations para impressão e QR codes
      data.data.forEach(order => {
        if (order.registrations && Array.isArray(order.registrations)) {
          order.registrations.forEach(reg => {
            allRegistrations.value.push(reg)
          })
        }
      })
    } else {
      // Fallback: agrupar manualmente se o backend não suportar
      const registrationsList = data.data || data
      const grouped = {}
      
      registrationsList.forEach(reg => {
        const key = reg.asaas_payment_id || `free_${reg.created_at}`
        if (!grouped[key]) {
          grouped[key] = {
            id: reg.asaas_payment_id || `free_${reg.id}`,
            payment_id: reg.asaas_payment_id,
            payment_method: reg.payment_method,
            payment_status: reg.payment_status,
            total_amount: 0,
            registrations: [],
            created_at: reg.created_at,
            gateway_payload: reg.gateway_payload
          }
        }
        grouped[key].total_amount += reg.price_paid || 0
        grouped[key].registrations.push(reg)
        allRegistrations.value.push(reg)
      })
      
      orders.value = Object.values(grouped).sort((a, b) => 
        new Date(b.created_at) - new Date(a.created_at)
      )
    }
    
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
    // Salvar dados na sessão
    setCpfSession(cpf.value)
    setBirthDateSession(birthDate.value)
    
    // Configurar listeners do Firebase
    if (setupListeners || savedCpf.value) {
      bindRealtimeUpdates(cleanCpf)
    }
    
    // Aguardar próximo tick do Vue para garantir que o DOM foi atualizado
    await new Promise(resolve => setTimeout(resolve, 100))
    await generateQRCodes()
  } catch (err) {
    if (err.response?.status === 404) {
      error.value = 'Nenhuma inscrição encontrada para este CPF e data de nascimento.'
    } else if (err.response?.status === 422) {
      error.value = err.response?.data?.message || 'Dados inválidos. Verifique o CPF e a data de nascimento.'
    } else {
      error.value = 'Não foi possível buscar as inscrições. Verifique o CPF e a data de nascimento e tente novamente.'
    }
  } finally {
    loading.value = false
  }
}

function bindRealtimeUpdates(cleanCpf) {
  // Limpar listeners anteriores
  unsubscribeFns.value.forEach(fn => fn && fn())
  unsubscribeFns.value = []
  
  const prefix = import.meta.env.VITE_FIREBASE_COLLECTION_PREFIX || ''
  
  // Escutar atualizações por payment_id (mais eficiente)
  if (orders.value.length > 0) {
    const paymentIds = [...new Set(orders.value.map(o => o.payment_id).filter(Boolean))]
    
    paymentIds.forEach(paymentId => {
      const paymentRef = dbRef(db, `updates/${prefix}registrations_by_payment_${paymentId}`)
      
      let isFirstSnapshot = true
      const unsubscribe = onValue(paymentRef, async (snapshot) => {
        if (!snapshot.exists()) return
        
        if (isFirstSnapshot) {
          isFirstSnapshot = false
          return
        }
        
        const payload = snapshot.val()
        const token = payload.last_updated || payload.updated_at || JSON.stringify(payload)
        if (token && token === lastUpdateToken) return
        lastUpdateToken = token
        
        // Recarregar registrations quando houver atualização
        await search(false, currentPage.value)
      })
      
      unsubscribeFns.value.push(unsubscribe)
    })
  }
  
  // Também escutar por telefone como fallback (se tivermos registrations)
  if (allRegistrations.value.length > 0) {
    const phones = [...new Set(allRegistrations.value.map(r => r.phone).filter(Boolean))]
    
    phones.forEach(phone => {
      const cleanPhone = phone.replace(/\D/g, '')
      const updatesRef = dbRef(db, `updates/${prefix}registrations_by_phone_${cleanPhone}`)
      
      let isFirstSnapshot = true
      const unsubscribe = onValue(updatesRef, async (snapshot) => {
        if (!snapshot.exists()) return
        
        if (isFirstSnapshot) {
          isFirstSnapshot = false
          return
        }
        
        const payload = snapshot.val()
        const token = payload.last_updated || payload.updated_at || JSON.stringify(payload)
        if (token && token === lastUpdateToken) return
        lastUpdateToken = token
        
        // Recarregar registrations quando houver atualização
        await search(false, currentPage.value)
      })
      
      unsubscribeFns.value.push(unsubscribe)
    })
  }
}

async function generateQRCodes() {
  if (!allRegistrations.value || allRegistrations.value.length === 0) return
  
  // Aguardar renderização do DOM
  await nextTick()
  await new Promise(resolve => setTimeout(resolve, 200))
  
  for (const reg of allRegistrations.value) {
    if (reg.payment_status === 'paid' && reg.qr_code) {
      try {
        const containerId = `qr-${reg.id}`
        const container = document.getElementById(containerId)
        
        if (container) {
          // Limpar conteúdo anterior se houver
          container.innerHTML = ''
          
          const canvas = document.createElement('canvas')
          container.appendChild(canvas)
          
          await QRCode.toCanvas(canvas, reg.qr_code, {
            width: 200,
            margin: 2,
            errorCorrectionLevel: 'M'
          })
          
          // Marcar como gerado apenas após sucesso
          qrCodeGenerated.value.add(reg.id)
          console.log(`QR Code gerado para registro ${reg.id}`)
        }
      } catch (error) {
        console.error(`Erro ao gerar QR code para registro ${reg.id}:`, error)
      }
    }
  }
}

const paidRegistrations = computed(() => {
  return allRegistrations.value.filter(reg => reg.payment_status === 'paid')
})

const qrCodeGenerated = ref(new Set())

function hasQrCode(regId) {
  return qrCodeGenerated.value.has(regId)
}

async function printOrder(order) {
  // Imprimir todas as inscrições do pedido
  const registrations = order.registrations || []
  const paidRegs = registrations.filter(reg => reg.payment_status === 'paid' && reg.qr_code)
  
  if (paidRegs.length === 0) {
    alert('Nenhuma inscrição paga para imprimir neste pedido.')
    return
  }
  
  // Gerar todos os QR codes como imagens base64 antes de abrir a janela
  const qrCodeImages = {}
  for (const reg of paidRegs) {
    try {
      qrCodeImages[reg.id] = await QRCode.toDataURL(reg.qr_code, {
        width: 250,
        margin: 2,
        errorCorrectionLevel: 'M'
      })
    } catch (error) {
      console.error(`Erro ao gerar QR code para registro ${reg.id}:`, error)
      qrCodeImages[reg.id] = ''
    }
  }
  
  const printWindow = window.open('', '_blank')
  const printedDate = new Date().toLocaleString('pt-BR')
  const totalAmount = formatBRL(order.total_amount || 0)
  const registrationsCount = order.registrations_count || registrations.length
  
  const styleClose = '<' + '/' + 'style>'
  const headClose = '<' + '/' + 'head>'
  const bodyClose = '<' + '/' + 'body>'
  const htmlClose = '<' + '/' + 'html>'
  
  let htmlContent = [
    '<!DOCTYPE html>',
    '<html>',
    '<head>',
    '<title>Pedido - ' + (order.payment_id || 'Gratuito') + '</title>',
    '<style>',
    '@media print { @page { size: A4; margin: 15mm; } .registration-card { page-break-inside: avoid; margin-bottom: 30px; } }',
    'body { font-family: Arial, sans-serif; padding: 20px; }',
    '.header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }',
    '.header h1 { color: #2563eb; margin: 0; }',
    '.order-info { background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px; }',
    '.order-info-row { display: flex; justify-content: space-between; padding: 5px 0; }',
    '.registration-card { border: 2px solid #2563eb; border-radius: 8px; padding: 20px; margin-bottom: 30px; }',
    '.info { margin-bottom: 20px; }',
    '.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }',
    '.info-label { font-weight: bold; color: #374151; }',
    '.qr-code { text-align: center; margin: 20px 0; padding: 15px; border: 2px solid #2563eb; border-radius: 8px; }',
    '.qr-code img { max-width: 100%; height: auto; }',
    styleClose,
    headClose,
    '<body>',
    '<div class="header">',
    '<h1>Comprovante de Pedido</h1>',
    '<p>Pedido: ' + (order.payment_id || 'Gratuito') + '</p>',
    '</div>',
    '<div class="order-info">',
    '<div class="order-info-row"><span class="info-label">Total de Inscrições:</span><span>' + registrationsCount + '</span></div>',
    '<div class="order-info-row"><span class="info-label">Valor Total Pago:</span><span style="font-weight: bold; font-size: 1.2em;">' + totalAmount + '</span></div>',
    '<div class="order-info-row"><span class="info-label">Método de Pagamento:</span><span>' + formatPaymentMethod(order.payment_method) + '</span></div>',
    '<div class="order-info-row"><span class="info-label">Status:</span><span style="color: #10b981; font-weight: bold;">Pago</span></div>',
    '</div>'
  ].join('\n')
  
  paidRegs.forEach((reg) => {
    const formattedCpf = reg.cpf ? formatCpf(reg.cpf) : '-'
    const formattedPrice = reg.price_paid === 0 ? 'Gratuito' : formatBRL(reg.price_paid)
    const eventName = reg.event?.name || 'Evento'
    const qrImage = qrCodeImages[reg.id] || ''
    
    htmlContent += [
      '<div class="registration-card">',
      '<h2 style="color: #2563eb; margin-top: 0;">' + eventName + '</h2>',
      '<div class="info">',
      '<div class="info-row"><span class="info-label">Número de Inscrição:</span><span>' + reg.registration_number + '</span></div>',
      '<div class="info-row"><span class="info-label">Nome:</span><span>' + reg.name + '</span></div>',
      '<div class="info-row"><span class="info-label">Telefone:</span><span>' + (reg.phone ? formatPhone(reg.phone) : '-') + '</span></div>',
      '<div class="info-row"><span class="info-label">CPF:</span><span>' + formattedCpf + '</span></div>',
      '<div class="info-row"><span class="info-label">Valor Pago:</span><span>' + formattedPrice + '</span></div>',
      '</div>',
      '<div class="qr-code">',
      '<p style="margin-bottom: 10px; font-weight: bold;">QR Code - Inscrição #' + reg.registration_number + '</p>',
      qrImage ? '<img src="' + qrImage + '" alt="QR Code" />' : '<p style="color: red;">Erro ao gerar QR Code</p>',
      '</div>',
      '</div>'
    ].join('\n')
  })
  
  htmlContent += [
    '<div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 12px;">',
    '<p>Impresso em: ' + printedDate + '</p>',
    '</div>',
    bodyClose,
    htmlClose
  ].join('\n')
  
  printWindow.document.write(htmlContent)
  printWindow.document.close()
  
  printWindow.onload = () => {
    setTimeout(() => {
      printWindow.print()
    }, 300)
  }
}

async function printRegistration(registration) {
  // Gerar QR code como imagem base64 antes de abrir a janela
  let qrCodeImage = ''
  try {
    qrCodeImage = await QRCode.toDataURL(registration.qr_code, {
      width: 250,
      margin: 2,
      errorCorrectionLevel: 'M'
    })
  } catch (error) {
    console.error('Erro ao gerar QR code:', error)
  }
  
  const printWindow = window.open('', '_blank')
  const eventName = registration.event?.name || 'Evento'
  const registrationNumber = registration.registration_number
  const formattedCpf = registration.cpf ? formatCpf(registration.cpf) : '-'
  const formattedDate = formatDate(registration.birth_date)
  const formattedPrice = registration.price_paid === 0 ? 'Gratuito' : formatBRL(registration.price_paid)
  const printedDate = new Date().toLocaleString('pt-BR')
  
  const styleClose = '<' + '/' + 'style>'
  const headClose = '<' + '/' + 'head>'
  const bodyClose = '<' + '/' + 'body>'
  const htmlClose = '<' + '/' + 'html>'
  
  const html = [
    '<!DOCTYPE html>',
    '<html>',
    '<head>',
    '<title>Inscrição - ' + registrationNumber + '</title>',
    '<style>',
    '@media print { @page { size: A4; margin: 20mm; } }',
    'body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }',
    '.header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }',
    '.header h1 { color: #2563eb; margin: 0; }',
    '.info { margin-bottom: 20px; }',
    '.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }',
    '.info-label { font-weight: bold; color: #374151; }',
    '.qr-code { text-align: center; margin: 30px 0; padding: 20px; border: 2px solid #2563eb; border-radius: 8px; }',
    '.qr-code img { max-width: 100%; height: auto; }',
    '.footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 12px; }',
    styleClose,
    headClose,
    '<body>',
    '<div class="header">',
    '<h1>' + eventName + '</h1>',
    '<p>Comprovante de Inscrição</p>',
    '</div>',
    '<div class="info">',
    '<div class="info-row"><span class="info-label">Número de Inscrição:</span><span>' + registrationNumber + '</span></div>',
    '<div class="info-row"><span class="info-label">Nome:</span><span>' + registration.name + '</span></div>',
    '<div class="info-row"><span class="info-label">Telefone:</span><span>' + (registration.phone ? formatPhone(registration.phone) : '-') + '</span></div>',
    '<div class="info-row"><span class="info-label">CPF:</span><span>' + formattedCpf + '</span></div>',
    '<div class="info-row"><span class="info-label">Data de Nascimento:</span><span>' + formattedDate + '</span></div>',
    '<div class="info-row"><span class="info-label">Valor Pago:</span><span>' + formattedPrice + '</span></div>',
    '<div class="info-row"><span class="info-label">Status:</span><span style="color: #10b981; font-weight: bold;">Pago</span></div>',
    '</div>',
    '<div class="qr-code">',
    '<p style="margin-bottom: 15px; font-weight: bold;">Apresente este QR Code na retirada do material</p>',
    qrCodeImage ? '<img src="' + qrCodeImage + '" alt="QR Code" />' : '<p style="color: red;">Erro ao gerar QR Code</p>',
    '</div>',
    '<div class="footer">',
    '<p>Este documento comprova sua inscrição no evento.</p>',
    '<p>Impresso em: ' + printedDate + '</p>',
    '</div>',
    bodyClose,
    htmlClose
  ].join('\n')
  
  printWindow.document.write(html)
  printWindow.document.close()
  
  printWindow.onload = () => {
    setTimeout(() => {
      printWindow.print()
    }, 300)
  }
}

async function printAll() {
  // Gerar todos os QR codes como imagens base64 antes de abrir a janela
  const qrCodeImages = {}
  for (const reg of paidRegistrations.value) {
    try {
      qrCodeImages[reg.id] = await QRCode.toDataURL(reg.qr_code, {
        width: 200,
        margin: 2,
        errorCorrectionLevel: 'M'
      })
    } catch (error) {
      console.error(`Erro ao gerar QR code para registro ${reg.id}:`, error)
      qrCodeImages[reg.id] = ''
    }
  }
  
  const printWindow = window.open('', '_blank')
  const printedDate = new Date().toLocaleString('pt-BR')
  
  const styleClose2 = '<' + '/' + 'style>'
  const headClose2 = '<' + '/' + 'head>'
  const bodyClose2 = '<' + '/' + 'body>'
  const htmlClose2 = '<' + '/' + 'html>'
  
  let htmlContent = [
    '<!DOCTYPE html>',
    '<html>',
    '<head>',
    '<title>Minhas Inscrições</title>',
    '<style>',
    '@media print { @page { size: A4; margin: 15mm; } .registration-card { page-break-inside: avoid; margin-bottom: 30px; } }',
    'body { font-family: Arial, sans-serif; padding: 20px; }',
    '.header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }',
    '.header h1 { color: #2563eb; margin: 0; }',
    '.registration-card { border: 2px solid #2563eb; border-radius: 8px; padding: 20px; margin-bottom: 30px; }',
    '.info { margin-bottom: 20px; }',
    '.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }',
    '.info-label { font-weight: bold; color: #374151; }',
    '.qr-code { text-align: center; margin: 20px 0; padding: 15px; border: 2px solid #2563eb; border-radius: 8px; }',
    '.qr-code img { max-width: 100%; height: auto; }',
    styleClose2,
    headClose2,
    '<body>',
    '<div class="header">',
    '<h1>Minhas Inscrições</h1>',
    '<p>Comprovantes de Inscrição</p>',
    '</div>'
  ].join('\n')
  
  paidRegistrations.value.forEach((reg) => {
    const formattedCpf = reg.cpf ? formatCpf(reg.cpf) : '-'
    const formattedPrice = reg.price_paid === 0 ? 'Gratuito' : formatBRL(reg.price_paid)
    const eventName = reg.event?.name || 'Evento'
    const qrImage = qrCodeImages[reg.id] || ''
    
    htmlContent += [
      '<div class="registration-card">',
      '<h2 style="color: #2563eb; margin-top: 0;">' + eventName + '</h2>',
      '<div class="info">',
      '<div class="info-row"><span class="info-label">Número de Inscrição:</span><span>' + reg.registration_number + '</span></div>',
      '<div class="info-row"><span class="info-label">Nome:</span><span>' + reg.name + '</span></div>',
      '<div class="info-row"><span class="info-label">Telefone:</span><span>' + (reg.phone ? formatPhone(reg.phone) : '-') + '</span></div>',
      '<div class="info-row"><span class="info-label">CPF:</span><span>' + formattedCpf + '</span></div>',
      '<div class="info-row"><span class="info-label">Valor Pago:</span><span>' + formattedPrice + '</span></div>',
      '</div>',
      '<div class="qr-code">',
      '<p style="margin-bottom: 10px; font-weight: bold;">QR Code - Inscrição #' + reg.registration_number + '</p>',
      qrImage ? '<img src="' + qrImage + '" alt="QR Code" />' : '<p style="color: red;">Erro ao gerar QR Code</p>',
      '</div>',
      '</div>'
    ].join('\n')
  })
  
  htmlContent += [
    '<div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 12px;">',
    '<p>Impresso em: ' + printedDate + '</p>',
    '</div>',
    bodyClose2,
    htmlClose2
  ].join('\n')
  
  printWindow.document.write(htmlContent)
  printWindow.document.close()
  
  printWindow.onload = () => {
    setTimeout(() => {
      printWindow.print()
    }, 300)
  }
}

function validateCpf(c) {
  if (!c || c.length !== 11 || /^(\d)\1+$/.test(c)) return false
  let s = 0
  for (let i = 0; i < 9; i++) s += parseInt(c.charAt(i)) * (10 - i)
  let r = 11 - (s % 11); r = r >= 10 ? 0 : r
  if (r !== parseInt(c.charAt(9))) return false
  s = 0
  for (let i = 0; i < 10; i++) s += parseInt(c.charAt(i)) * (11 - i)
  r = 11 - (s % 11); r = r >= 10 ? 0 : r
  return r === parseInt(c.charAt(10))
}

function logout() {
  cpf.value = ''
  birthDate.value = ''
  orders.value = []
  allRegistrations.value = []
  error.value = null
  clearCpfSession()
  clearBirthDateSession()
  unsubscribeFns.value.forEach(fn => fn && fn())
  unsubscribeFns.value = []
  lastUpdateToken = null
  qrCodeGenerated.value.clear()
}

function statusLabel(status) {
  switch (status) {
    case 'pending': return 'Pendente'
    case 'paid': return 'Pago'
    case 'canceled': return 'Cancelado'
    case 'failed': return 'Falhou'
    default: return status
  }
}

function statusIcon(status) {
  if (status === 'paid') return ['fas', 'check-circle']
  if (status === 'pending') return ['fas', 'hourglass-half']
  if (status === 'canceled') return ['fas', 'times-circle']
  if (status === 'failed') return ['fas', 'exclamation-triangle']
  return ['fas', 'hourglass-half']
}

function statusPillClass(status) {
  if (status === 'paid') return 'bg-green-50 text-green-700 ring-1 ring-green-200'
  if (status === 'pending') return 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200'
  if (status === 'canceled') return 'bg-red-50 text-red-700 ring-1 ring-red-200'
  if (status === 'failed') return 'bg-orange-50 text-orange-700 ring-1 ring-orange-200'
  return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-BR')
}

function formatDateTime(date) {
  if (!date) return ''
  return new Date(date).toLocaleString('pt-BR')
}

function formatCpf(cpf) {
  if (!cpf) return ''
  return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}

function formatBRL(cents) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function formatPaymentMethod(method) {
  const methods = {
    'PIX': 'PIX',
    'BOLETO': 'Boleto',
    'CREDIT_CARD': 'Cartão de Crédito',
    'FREE': 'Gratuito',
    'MANUAL': 'Pagamento Manual',
  }
  return methods[method] || method
}

function isCardInstallments(order) {
  if (!order) return false
  const t = (order.gateway_payload?.billingType || order.payment_method || '').toUpperCase()
  if (t !== 'CREDIT_CARD') return false
  const cnt = Number(order.gateway_payload?.installmentCount || 0)
  if (cnt > 1) return true
  const desc = order.gateway_payload?.description || ''
  return /Parcela\s+\d+\s+de\s+\d+/i.test(desc)
}

function installmentCount(order) {
  const cnt = Number(order?.gateway_payload?.installmentCount || 0)
  if (cnt > 1) return cnt
  const desc = order?.gateway_payload?.description || ''
  const m = desc.match(/de\s+(\d+)/i)
  return m ? Number(m[1]) : 1
}

function perInstallmentCents(order) {
  const gp = order?.gateway_payload || {}
  if (gp.installmentValue != null) return Math.round(Number(gp.installmentValue) * 100)
  if (gp.value != null && installmentCount(order) > 1) return Math.round(Number(gp.value) * 100)
  return Math.round(Number(gp.value ?? order?.total_amount ?? 0))
}

function displayTotal(order) {
  if (!order) return formatBRL(0)
  
  const gp = order?.gateway_payload || {}
  
  // Se houver parcelamento, usar totalValue ou calcular
  if (isCardInstallments(order)) {
    if (gp.totalValue != null) {
      return formatBRL(Math.round(Number(gp.totalValue) * 100))
    }
    // Calcular: parcela * número de parcelas
    const count = installmentCount(order)
    const per = perInstallmentCents(order)
    if (per && count > 1) {
      return formatBRL(per * count)
    }
  }
  
  // Se tiver totalValue, usar ele
  if (gp.totalValue != null) {
    return formatBRL(Math.round(Number(gp.totalValue) * 100))
  }
  
  // Se tiver value, usar ele
  if (gp.value != null) {
    return formatBRL(Math.round(Number(gp.value) * 100))
  }
  
  // Fallback: usar total_amount
  return formatBRL(order.total_amount || 0)
}

function getReceiptUrl(order) {
  if (!order) return null
  return order.gateway_payload?.transactionReceiptUrl || 
         order.gateway_payload?.receiptUrl ||
         null
}

onBeforeUnmount(() => {
  unsubscribeFns.value.forEach(fn => fn && fn())
  unsubscribeFns.value = []
})
</script>
