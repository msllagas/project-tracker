<script setup lang="ts">
import AppButton from '@/components/AppButton.vue'
import AppDialog from '@/components/AppDialog.vue'

defineProps<{
  open: boolean
  title: string
  confirmLabel: string
  busy?: boolean
  error?: string
}>()

const emit = defineEmits<{ confirm: []; cancel: [] }>()
</script>

<template>
  <AppDialog :open="open" :title="title" :busy="busy" size="sm" @close="emit('cancel')">
    <div class="flex flex-col gap-4 px-6 pt-3 pb-6">
      <div class="text-muted"><slot /></div>
      <p v-if="error" role="alert" class="rounded-md bg-alert/10 px-3 py-2 text-sm text-alert">
        {{ error }}
      </p>
      <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <AppButton variant="secondary" :disabled="busy" @click="emit('cancel')">Cancel</AppButton>
        <AppButton variant="danger" :loading="busy" @click="emit('confirm')">
          {{ confirmLabel }}
        </AppButton>
      </div>
    </div>
  </AppDialog>
</template>
