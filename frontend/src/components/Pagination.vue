<template>
  <div class="flex justify-center items-center gap-2 mt-10">
    <button
      :disabled="currentPage === 1"
      @click="$emit('change', 1)"
      class="w-9 h-9 flex items-center justify-center bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <font-awesome-icon :icon="['fas', 'angle-double-left']" />
    </button>

    <button
      :disabled="currentPage === 1"
      @click="$emit('change', currentPage - 1)"
      class="w-9 h-9 flex items-center justify-center bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <font-awesome-icon :icon="['fas', 'angle-left']" />
    </button>

    <span
      v-for="page in pagesToShow"
      :key="page"
      @click="page !== currentPage && $emit('change', page)"
      class="w-9 h-9 flex items-center justify-center rounded-lg border text-sm font-medium transition"
      :class="[
        page === currentPage
          ? 'bg-blue-600 text-white border-blue-600 cursor-default'
          : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-100 cursor-pointer'
      ]"
    >
      {{ page }}
    </span>

    <button
      :disabled="currentPage === lastPage"
      @click="$emit('change', currentPage + 1)"
      class="w-9 h-9 flex items-center justify-center bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <font-awesome-icon :icon="['fas', 'angle-right']" />
    </button>

    <button
      :disabled="currentPage === lastPage"
      @click="$emit('change', lastPage)"
      class="w-9 h-9 flex items-center justify-center bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <font-awesome-icon :icon="['fas', 'angle-double-right']" />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@/plugins/fontawesome'

const props = defineProps({
  currentPage: Number,
  lastPage: Number
})

defineEmits(['change'])

const pagesToShow = computed(() => {
  const total = Number(props.lastPage)
  const current = Number(props.currentPage)
  const delta = 2
  let pages = []

  let start = Math.max(1, current - delta)
  let end = Math.min(total, current + delta)

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})
</script>
