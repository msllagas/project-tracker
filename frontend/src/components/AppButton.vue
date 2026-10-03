<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost-light'
    type?: 'button' | 'submit'
    loading?: boolean
    disabled?: boolean
  }>(),
  { variant: 'primary', type: 'button', loading: false, disabled: false },
)

const variants = {
  primary: 'bg-ink text-white hover:bg-ink-soft',
  secondary: 'border border-rule bg-white text-ink hover:border-ink/40',
  danger: 'bg-alert text-white hover:bg-alert/90',
  'ghost-light': 'text-white/85 hover:bg-white/10 hover:text-white',
}
</script>

<template>
  <button
    :type="type"
    :disabled="loading || disabled || undefined"
    :aria-busy="loading || undefined"
    class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60"
    :class="variants[variant]"
  >
    <slot />
  </button>
</template>
