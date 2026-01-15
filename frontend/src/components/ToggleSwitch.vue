<template>
  <label class="flex items-center gap-3 cursor-pointer select-none">
    <span class="font-medium text-gray-700">{{ label }}</span>

    <div
      class="relative inline-flex items-center"
      :class="disabled ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'"
      @click="onToggle"
    >
      <input
        type="checkbox"
        class="sr-only"
        :checked="internalValue"
        :disabled="disabled"
        @change="onToggle"
      />

      <div
        class="w-12 h-6 rounded-full transition-colors duration-300"
        :class="internalValue ? 'bg-green-600' : 'bg-gray-300'"
      ></div>

      <div
        class="absolute left-0.5 top-0.5 bg-white w-5 h-5 rounded-full shadow-md transform transition-transform duration-300"
        :class="internalValue ? 'translate-x-6' : 'translate-x-0'"
      ></div>
    </div>
  </label>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  label: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])
const internalValue = ref(props.modelValue)

watch(
  () => props.modelValue,
  (val) => {
    internalValue.value = val
  }
)

function onToggle() {
  if (props.disabled) return
  internalValue.value = !internalValue.value
  emit('update:modelValue', internalValue.value)
}
</script>
