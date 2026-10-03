<script setup lang="ts">
withDefaults(defineProps<{ tone?: 'dark' | 'light'; size?: 'md' | 'lg' }>(), {
  tone: 'dark',
  size: 'md',
})
</script>

<template>
  <span
    class="inline-flex items-center font-black tracking-tight font-stretch-expanded"
    :class="[
      tone === 'light' ? 'text-white' : 'text-ink',
      size === 'lg' ? 'gap-3 text-3xl sm:text-4xl' : 'gap-2.5 text-lg',
    ]"
  >
    <svg
      viewBox="0 0 32 32"
      aria-hidden="true"
      class="shrink-0"
      :class="size === 'lg' ? 'size-10' : 'size-7'"
    >
      <rect width="32" height="32" rx="7" :fill="tone === 'light' ? '#fff' : '#172036'" />
      <path
        fill="none"
        :stroke="tone === 'light' ? '#172036' : '#fff'"
        stroke-linecap="round"
        stroke-width="2.5"
        d="M9 11h14M9 16h9M9 21h6"
      />
      <path
        fill="none"
        stroke="#f5b400"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2.5"
        d="m20 20 2 2 4-4.5"
      />
    </svg>
    Project Tracker
  </span>
</template>
