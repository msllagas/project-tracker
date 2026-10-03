<script setup lang="ts">
import { useRoute } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'
import ToastRegion from '@/components/ToastRegion.vue'

const route = useRoute()
</script>

<template>
  <AppHeader v-if="route.meta.requiresAuth" />
  <RouterView />
  <ToastRegion />
</template>
