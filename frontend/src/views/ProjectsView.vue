<script setup lang="ts">
import { computed } from 'vue'
import AppButton from '@/components/AppButton.vue'
import ProjectList from '@/components/projects/ProjectList.vue'
import ProjectToolbar from '@/components/projects/ProjectToolbar.vue'
import { useProjectFilters } from '@/composables/useProjectFilters'
import { useProjectList } from '@/composables/useProjectList'

const { filters, hasActiveFilters, updateFilters, clearFilters } = useProjectFilters()
const { projects, loading, loaded, error, reload } = useProjectList(filters)

const summary = computed(() => {
  const count = projects.value.length
  const noun = count === 1 ? 'project' : 'projects'

  return hasActiveFilters.value ? `${count} matching ${noun}` : `${count} ${noun}`
})
</script>

<template>
  <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
      <h1 class="text-3xl font-black tracking-tight font-stretch-expanded sm:text-4xl">Projects</h1>
      <p v-if="loaded" class="text-sm text-muted" aria-live="polite">{{ summary }}</p>
    </div>

    <section class="mt-6 rounded-md border border-t-4 border-rule border-t-ink bg-white p-4 sm:p-6">
      <ProjectToolbar
        :filters="filters"
        :has-active-filters="hasActiveFilters"
        @update="updateFilters"
        @clear="clearFilters"
      />
    </section>

    <section class="mt-6" :aria-busy="loading">
      <div
        v-if="error"
        role="alert"
        class="flex flex-col items-start gap-3 rounded-md border border-alert/30 bg-alert/5 p-6"
      >
        <p class="font-semibold text-alert">The projects could not be loaded.</p>
        <p class="text-sm text-muted">{{ error.message }}</p>
        <AppButton variant="secondary" @click="reload">Try again</AppButton>
      </div>

      <p v-else-if="!loaded" class="py-16 text-center text-muted">Loading projects…</p>

      <div
        v-else-if="projects.length === 0"
        class="flex flex-col items-center gap-2 rounded-md border border-dashed border-rule px-6 py-16 text-center"
      >
        <template v-if="hasActiveFilters">
          <p class="font-semibold">No projects match your filters</p>
          <p class="text-sm text-muted">Try a different search, or clear the filters.</p>
          <AppButton variant="secondary" class="mt-2" @click="clearFilters">
            Clear filters
          </AppButton>
        </template>
        <template v-else>
          <p class="font-semibold">No projects yet</p>
          <p class="text-sm text-muted">Projects you add will appear here.</p>
        </template>
      </div>

      <div
        v-else
        class="transition-opacity md:rounded-md md:border md:border-rule md:bg-white md:px-6"
        :class="{ 'opacity-60': loading }"
      >
        <ProjectList :projects="projects" />
      </div>
    </section>
  </main>
</template>
