<template>
  <div class="mb-6">
    <VueDatePicker
      v-model="date"
      range
      locale="pt-BR"
      format="dd/MM/yyyy"
      :preset-dates="presetDates"
      :enable-time-picker="false"
      :clearable="false"
      :auto-apply="true"
      :close-on-auto-apply="true"
      @update:model-value="emitRange"
    >
      <template #preset-date-range-button="{ label, value, presetDate }">
        <button class="px-2 py-1 text-sm rounded hover:bg-gray-200" @click="presetDate(value)">
          {{ label }}
        </button>
      </template>
    </VueDatePicker>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { startOfMonth, endOfMonth, subMonths } from 'date-fns'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const emit = defineEmits(['change'])
const today = new Date()
const date = ref([startOfMonth(today), today])

function emitRange(val) {
  emit('change', val)
}

function formatMonth(d) {
  return new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' }).format(d)
}

const presetDates = ref([
  { label: 'Hoje', value: [today, today] },
  { label: 'Este mês', value: [startOfMonth(today), today] },
  { label: formatMonth(subMonths(today, 1)), value: [startOfMonth(subMonths(today, 1)), endOfMonth(subMonths(today, 1))] },
  { label: formatMonth(subMonths(today, 2)), value: [startOfMonth(subMonths(today, 2)), endOfMonth(subMonths(today, 2))] },
  { label: formatMonth(subMonths(today, 3)), value: [startOfMonth(subMonths(today, 3)), endOfMonth(subMonths(today, 3))] },
  { label: formatMonth(subMonths(today, 4)), value: [startOfMonth(subMonths(today, 4)), endOfMonth(subMonths(today, 4))] },
  { label: formatMonth(subMonths(today, 5)), value: [startOfMonth(subMonths(today, 5)), endOfMonth(subMonths(today, 5))] }
])
</script>
