<script setup lang="ts">
import { computed, useId } from 'vue'

const props = defineProps<{
  label: string
  error?: string
  /** Mark the label with an asterisk; the control itself should also be `required`. */
  required?: boolean
}>()

const id = useId()
const messageId = `${id}-message`

/** Attributes that wire the slotted control to its label and message. */
const control = computed(() => ({
  id,
  'aria-invalid': Boolean(props.error),
  'aria-describedby': props.error ? messageId : undefined,
}))
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label :for="id" class="text-sm font-medium">
      {{ label }}<span v-if="required" class="text-alert" aria-hidden="true"> *</span>
    </label>
    <slot :control="control" />
    <p v-if="error" :id="messageId" class="text-sm text-alert">{{ error }}</p>
  </div>
</template>
