<script setup lang="ts">
import { onMounted, ref, useId, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    open: boolean
    title: string
    /** Prevent closing (Escape) while an action is in progress. */
    busy?: boolean
    size?: 'sm' | 'lg'
  }>(),
  { busy: false, size: 'lg' },
)

const emit = defineEmits<{ close: [] }>()

const dialog = ref<HTMLDialogElement>()
const titleId = useId()

function sync(open: boolean): void {
  if (open && !dialog.value?.open) {
    dialog.value?.showModal()
  } else if (!open && dialog.value?.open) {
    dialog.value.close()
  }
}

// Escape triggers "cancel"; let the parent decide whether the dialog really closes.
function onCancel(event: Event): void {
  event.preventDefault()

  if (!props.busy) {
    emit('close')
  }
}

// Open after the content renders so the browser can focus its [autofocus] field.
watch(() => props.open, sync, { flush: 'post' })
onMounted(() => sync(props.open))
</script>

<template>
  <dialog
    ref="dialog"
    :aria-labelledby="titleId"
    class="m-auto w-[calc(100%-2rem)] rounded-md border-t-4 border-ink bg-white p-0 text-ink shadow-xl backdrop:bg-ink/50"
    :class="size === 'sm' ? 'max-w-md' : 'max-w-2xl'"
    @cancel="onCancel"
  >
    <div v-if="open" class="flex max-h-[calc(100dvh-4rem)] flex-col">
      <header class="flex items-start justify-between gap-4 px-6 pt-6">
        <h2 :id="titleId" class="text-xl font-bold font-stretch-semi-expanded">{{ title }}</h2>
        <button
          type="button"
          class="-mt-1 -mr-2 rounded-md px-2 py-1 text-muted hover:text-ink"
          aria-label="Close"
          :disabled="busy"
          @click="emit('close')"
        >
          ✕
        </button>
      </header>
      <slot />
    </div>
  </dialog>
</template>
