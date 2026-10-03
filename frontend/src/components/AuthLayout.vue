<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue'

defineProps<{
  title: string
  description: string
}>()
</script>

<template>
  <main class="min-h-screen px-4 py-12 sm:py-20">
    <div class="mx-auto w-full max-w-md">
      <AppLogo size="lg" />
      <h1 class="mt-12 text-2xl font-bold font-stretch-semi-expanded">{{ title }}</h1>
      <p class="mt-1 text-muted">{{ description }}</p>
      <div class="mt-6 rounded-md border border-t-4 border-rule border-t-ink bg-white p-6 sm:p-8">
        <slot />
      </div>
      <div class="mt-6 flex flex-col gap-3 text-sm text-muted">
        <slot name="footer" />
      </div>
    </div>
  </main>
</template>
