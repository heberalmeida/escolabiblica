<template>
  <transition name="fade">
    <div
      v-if="show"
      :class="[
        'fixed bottom-8 left-1/2 -translate-x-1/2 px-6 py-3 rounded-2xl shadow-xl shadow-black/20 text-white font-medium z-[99999] flex items-center gap-3 transition-all duration-300 backdrop-blur-sm',
        type === 'success'
          ? 'bg-green-500 hover:bg-green-600'
          : type === 'error'
            ? 'bg-red-600 hover:bg-red-700'
            : type === 'warning'
              ? 'bg-amber-400 hover:bg-amber-500 text-gray-900'
              : 'bg-gray-800 hover:bg-gray-900'
      ]"
    >
      <font-awesome-icon
        :icon="[
          'fas',
          type === 'success'
            ? 'check-circle'
            : type === 'error'
              ? 'circle-exclamation'
              : type === 'warning'
                ? 'triangle-exclamation'
                : 'info-circle'
        ]"
        class="text-xl"
      />
      <span class="text-sm sm:text-base">{{ message }}</span>
    </div>
  </transition>
</template>

<script setup>
import { ref } from 'vue'
import { FontAwesomeIcon } from '@/plugins/fontawesome'

const show = ref(false)
const message = ref('')
const type = ref('info')
let timeout = null

function open(payload, t = 'info') {
  if (typeof payload === 'object' && payload !== null) {
    message.value = payload.message || ''
    type.value = payload.type || 'info'
  } else {
    message.value = payload
    type.value = t
  }

  show.value = true
  clearTimeout(timeout)
  timeout = setTimeout(() => { show.value = false }, 3000)
}

defineExpose({ open })
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
