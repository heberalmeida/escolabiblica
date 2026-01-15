<template>
  <div class="bg-gray-50">
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-bold flex-1 text-center sm:text-left flex items-center gap-3 text-gray-800">
          <font-awesome-icon :icon="['fas', 'ticket-alt']" class="text-blue-600" />
          Minhas Inscrições
        </h1>

        <button
          v-if="savedCpf || registrations.length"
          @click="logout"
          class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm cursor-pointer inline-flex items-center gap-2">
          <font-awesome-icon :icon="['fas', 'sign-out-alt']" />
          Sair
        </button>
      </div>

      <form @submit.prevent="search"
        class="flex flex-col sm:flex-row gap-4 justify-center mb-10">
        <input v-model="cpf" v-maska="'###.###.###-##'" placeholder="Digite seu CPF"
          class="w-full sm:w-72 bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
        <button type="submit" :disabled="loading"
          class="bg-blue-600 text-white px-6 py-2 rounded-lg cursor-pointer hover:bg-blue-700 disabled:bg-gray-400 transition">
          {{ loading ? 'Buscando...' : 'Buscar' }}
        </button>
      </form>

      <div v-if="loading">
        <div class="flex justify-center py-10">
          <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
      </div>
      <div v-else>
        <p v-if="error" class="text-red-600 mb-6 text-center">{{ error }}</p>

        <div v-if="registrations.length">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="reg in registrations" :key="reg.id"
              class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden group flex flex-col p-4 border hover:border-blue-200">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 group-hover:bg-blue-100">
                  <font-awesome-icon :icon="['fas', 'calendar']" class="text-blue-600 text-lg" />
                </div>
                <div>
                  <p class="font-bold text-lg leading-tight">{{ reg.event?.name }}</p>
                  <p class="text-xs text-gray-500">Inscrição #{{ reg.registration_number }}</p>
                </div>
              </div>
              <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                :class="statusPillClass(reg.payment_status)">
                <font-awesome-icon :icon="statusIcon(reg.payment_status)" />
                {{ statusLabel(reg.payment_status) }}
              </span>
            </div>

            <div class="flex flex-col gap-2 text-sm text-gray-600 mb-4">
              <div><strong>Nome:</strong> {{ reg.name }}</div>
              <div><strong>Telefone:</strong> {{ reg.phone }}</div>
              <div v-if="reg.cpf"><strong>CPF:</strong> {{ formatCpf(reg.cpf) }}</div>
              <div><strong>Data de Nascimento:</strong> {{ formatDate(reg.birth_date) }}</div>
              <div v-if="reg.gender"><strong>Gênero:</strong> {{ reg.gender === 'MASCULINO' ? 'Masculino' : 'Feminino' }}</div>
              <div v-if="reg.congregation"><strong>Congregação:</strong> {{ reg.congregation }}</div>
              <div v-if="reg.sector"><strong>Setor:</strong> {{ reg.sector }}</div>
              <div v-if="reg.church_type"><strong>Tipo:</strong> {{ reg.church_type }}</div>
              <div v-if="reg.whatsapp_authorization !== null">
                <strong>Autoriza WhatsApp:</strong> {{ reg.whatsapp_authorization ? 'Sim' : 'Não' }}
              </div>
              <div>
                <strong>Valor:</strong>
                <span class="font-bold text-gray-900">
                  {{ reg.price_paid === 0 ? 'Gratuito' : formatBRL(reg.price_paid) }}
                </span>
              </div>
              <div v-if="reg.payment_method">
                <strong>Método de Pagamento:</strong> {{ formatPaymentMethod(reg.payment_method) }}
              </div>
              <div><strong>Data da Inscrição:</strong> {{ formatDateTime(reg.created_at) }}</div>
            </div>

            <div class="mt-auto space-y-2">
              <div v-if="reg.validated" class="bg-green-50 border border-green-200 rounded-lg p-2 text-sm text-green-700">
                <div class="flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'check-circle']" />
                  <span>Validado em {{ formatDateTime(reg.validated_at) }}</span>
                </div>
              </div>
              <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-sm text-yellow-700">
                <div class="flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'clock']" />
                  <span>Pendente de validação</span>
                </div>
              </div>
              <div v-if="reg.payment_status === 'paid' && reg.qr_code" class="space-y-2">
                <div class="bg-white p-4 rounded-lg border-2 border-blue-600 flex justify-center">
                  <div :id="`qr-${reg.id}`" class="w-full flex justify-center"></div>
                </div>
                <button @click="printRegistration(reg)"
                  class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center justify-center gap-2">
                  <font-awesome-icon :icon="['fas', 'print']" />
                  Imprimir
                </button>
              </div>
              <div v-else-if="reg.payment_status !== 'paid'" class="bg-red-50 border border-red-200 rounded-lg p-2 text-sm text-red-700">
                <div class="flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'exclamation-triangle']" />
                  <span>QR Code disponível após confirmação do pagamento</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="paidRegistrations.length > 0" class="mt-6 flex justify-center">
          <button @click="printAll"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition flex items-center gap-2">
            <font-awesome-icon :icon="['fas', 'print']" />
            Imprimir Todas as Inscrições Pagas
          </button>
        </div>

        <Pagination v-if="registrations.length" class="mt-8" :current-page="currentPage" :last-page="lastPage"
          @change="page => search(false, page)" />
        </div>

        <p v-if="!registrations.length && !error" class="text-gray-600 text-center mt-10">Nenhuma inscrição encontrada.</p>
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
const registrations = ref([])
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const lastPage = ref(1)
const { data: savedCpf, set: setCpfSession, clear: clearCpfSession } = useSessionStorage('user_cpf_registrations', '')

onMounted(() => {
  if (savedCpf.value) {
    cpf.value = savedCpf.value
    search(true)
  }
})

// Observar mudanças nas inscrições para gerar QR codes
watch(registrations, async () => {
  await nextTick()
  await generateQRCodes()
}, { deep: true })

async function search(setupListeners = false, page = 1) {
  const cleanCpf = cpf.value.replace(/\D/g, '')
  if (!validateCpf(cleanCpf)) {
    error.value = 'CPF inválido.'
    return
  }
  loading.value = true
  error.value = null
  registrations.value = []
  try {
    const { data } = await registrationsApi.getByCpf(cleanCpf, { page, per_page: 6 })
    registrations.value = data.data || data
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
    setCpfSession(cpf.value)
    
    // Aguardar próximo tick do Vue para garantir que o DOM foi atualizado
    await new Promise(resolve => setTimeout(resolve, 100))
    await generateQRCodes()
  } catch (err) {
    if (err.response?.status === 404) {
      error.value = 'Nenhuma inscrição encontrada para este CPF.'
    } else {
      error.value = 'Não foi possível buscar as inscrições. Verifique o CPF e tente novamente.'
    }
  } finally {
    loading.value = false
  }
}

async function generateQRCodes() {
  if (!registrations.value || registrations.value.length === 0) return
  
  // Aguardar renderização do DOM
  await nextTick()
  await new Promise(resolve => setTimeout(resolve, 200))
  
  for (const reg of registrations.value) {
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
          
          console.log(`QR Code gerado para registro ${reg.id}`)
        } else {
          console.warn(`Container não encontrado: ${containerId} para registro ${reg.id}, payment_status: ${reg.payment_status}, qr_code: ${reg.qr_code ? 'existe' : 'não existe'}`)
        }
      } catch (error) {
        console.error(`Erro ao gerar QR code para registro ${reg.id}:`, error)
      }
    }
  }
}

const paidRegistrations = computed(() => {
  return registrations.value.filter(reg => reg.payment_status === 'paid')
})

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
    '<div class="info-row"><span class="info-label">CPF:</span><span>' + formattedCpf + '</span></div>',
    '<div class="info-row"><span class="info-label">Telefone:</span><span>' + (registration.phone || '-') + '</span></div>',
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
  registrations.value = []
  error.value = null
  clearCpfSession()
  qrCanvases.value = {}
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

onBeforeUnmount(() => {
  // Cleanup se necessário
})
</script>
