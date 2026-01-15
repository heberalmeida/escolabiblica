<template>
  <input
    ref="inputRef"
    type="text"
    class="w-full border p-2 rounded"
  />
</template>

<script setup>
import { useCurrencyInput } from 'vue-currency-input'
import { watch } from 'vue'

const props = defineProps({
  modelValue: [Number, String, null],
  options: {
    type: Object,
    default: () => ({
      locale: 'pt-BR',
      currency: 'BRL',
      currencyDisplay: 'hidden',
      precision: 2,
      autoDecimalDigits: true,        // estilo calculadora
      useGrouping: false,             // sem separador de milhar na digitação
      distractionFree: false,
      hideGroupingSeparatorOnFocus: true,
      hideCurrencySymbolOnFocus: true,
      hideNegligibleDecimalDigitsOnFocus: false,
      valueRange: { min: 0 }
    })
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const {
  inputRef,
  setValue,
  setOptions
} = useCurrencyInput(props.options, { emit })

watch(() => props.modelValue, (val) => setValue(val))
watch(() => props.options, (opt) => setOptions(opt))
</script>
