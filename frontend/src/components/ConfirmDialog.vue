<template>
  <div
    v-if="open"
    class="fixed inset-0 flex items-center justify-center bg-black/40 z-50"
  >
    <div
      class="bg-white rounded-2xl shadow-lg w-full max-w-md mx-4 sm:mx-0 p-6 sm:p-8 border border-gray-100"
    >
      <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center sm:text-left">
        Confirmação
      </h2>

      <p class="text-gray-600 mb-8 text-center sm:text-left leading-relaxed" v-html="message"></p>

      <div class="flex flex-col sm:flex-row justify-end gap-3">
        <button
          @click="$emit('cancel')"
          class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-gray-200 text-gray-800 font-medium hover:bg-gray-300 transition"
        >
          Cancelar
        </button>
        <button
          @click="$emit('confirm')"
          class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700 active:scale-95 transition"
        >
          Confirmar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  open: Boolean,
  message: String
})
</script>
