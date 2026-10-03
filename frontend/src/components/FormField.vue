<script setup lang="ts">
import { computed, useId } from 'vue'

const props = defineProps<{
  label: string
  error?: string
  hint?: string
}>()

const id = useId()
const messageId = `${id}-message`

/** Attributes that wire the slotted control to its label and message. */
const control = computed(() => ({
  id,
  'aria-invalid': Boolean(props.error),
  'aria-describedby': props.error || props.hint ? messageId : undefined,
}))
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label :for="id" class="text-sm font-medium">{{ label }}</label>
    <slot :control="control" />
    <p v-if="error" :id="messageId" class="text-sm text-alert">{{ error }}</p>
    <p v-else-if="hint" :id="messageId" class="text-sm text-muted">{{ hint }}</p>
  </div>
</template>
