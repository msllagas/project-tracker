import { readonly, ref } from 'vue'

export interface Toast {
  id: number
  message: string
}

const TOAST_DURATION_MS = 4000

const toasts = ref<Toast[]>([])
let nextId = 1

function dismissToast(id: number): void {
  toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

/** Show a short confirmation message that disappears on its own. */
function showToast(message: string): void {
  const id = nextId++

  toasts.value = [...toasts.value, { id, message }]
  setTimeout(() => dismissToast(id), TOAST_DURATION_MS)
}

export function useToast() {
  return { toasts: readonly(toasts), showToast, dismissToast }
}
