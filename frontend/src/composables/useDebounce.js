import { ref } from 'vue'

export function useDebounce(fn, delay = 600) {
  const timeout = ref(null)

  function debounced(...args) {
    if (timeout.value) clearTimeout(timeout.value)
    timeout.value = setTimeout(() => fn(...args), delay)
  }

  return debounced
}
