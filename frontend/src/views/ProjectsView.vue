<script setup lang="ts">
import { computed, ref, useTemplateRef, watch } from 'vue'
import { ApiError } from '@/api/client'
import { deleteProject } from '@/api/projects'
import AppButton from '@/components/AppButton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import ProjectFormDialog from '@/components/projects/ProjectFormDialog.vue'
import ProjectList from '@/components/projects/ProjectList.vue'
import ProjectPagination from '@/components/projects/ProjectPagination.vue'
import ProjectToolbar from '@/components/projects/ProjectToolbar.vue'
import { useProjectFilters } from '@/composables/useProjectFilters'
import { useProjectList } from '@/composables/useProjectList'
import { useToast } from '@/composables/useToast'
import type { Project, ProjectFilters } from '@/types'

const { filters, hasActiveFilters, updateFilters, clearFilters } = useProjectFilters()
const { projects, pagination, loading, loaded, error, reload } = useProjectList(filters)
const { showToast } = useToast()

const summary = computed(() => {
  const count = pagination.value?.total ?? 0
  const noun = count === 1 ? 'project' : 'projects'

  return hasActiveFilters.value ? `${count} matching ${noun}` : `${count} ${noun}`
})

const listSection = useTemplateRef('listSection')

function changePage(changes: Pick<ProjectFilters, 'page' | 'per_page'>): void {
  updateFilters(changes)

  // Bring the top of the list back into view when paging from the bottom of a long page.
  if (listSection.value && listSection.value.getBoundingClientRect().top < 0) {
    listSection.value.scrollIntoView()
  }
}

// A page can end up past the last one, for example after deleting its only project.
watch(pagination, (meta) => {
  if (meta && meta.current_page > meta.last_page) {
    updateFilters({ page: meta.last_page })
  }
})

const formOpen = ref(false)
const editingProject = ref<Project | null>(null)

function openCreateForm(): void {
  editingProject.value = null
  formOpen.value = true
}

function openEditForm(project: Project): void {
  editingProject.value = project
  formOpen.value = true
}

function onSaved(_project: Project, action: 'created' | 'updated'): void {
  formOpen.value = false
  showToast(action === 'created' ? 'Project created.' : 'Changes saved.')
  void reload()
}

const projectToDelete = ref<Project | null>(null)
const deleting = ref(false)
const deleteError = ref<string>()

function confirmDelete(project: Project): void {
  projectToDelete.value = project
  deleteError.value = undefined
}

async function deleteConfirmed(): Promise<void> {
  if (!projectToDelete.value) {
    return
  }

  deleting.value = true
  deleteError.value = undefined

  try {
    await deleteProject(projectToDelete.value.id)
    projectToDelete.value = null
    showToast('Project deleted.')
    void reload()
  } catch (caught) {
    deleteError.value = caught instanceof ApiError ? caught.message : 'Something went wrong.'
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
        <h1 class="text-3xl font-black tracking-tight font-stretch-expanded sm:text-4xl">
          Projects
        </h1>
        <p v-if="loaded" class="text-sm text-muted" aria-live="polite">{{ summary }}</p>
      </div>
      <AppButton @click="openCreateForm">New project</AppButton>
    </div>

    <section class="mt-6 rounded-md border border-t-4 border-rule border-t-ink bg-white p-4 sm:p-6">
      <ProjectToolbar
        :filters="filters"
        :has-active-filters="hasActiveFilters"
        @update="updateFilters"
        @clear="clearFilters"
      />
    </section>

    <section ref="listSection" class="mt-6 scroll-mt-6" :aria-busy="loading">
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
        v-else-if="pagination?.total === 0"
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
          <p class="text-sm text-muted">Add your first client project to start tracking it.</p>
          <AppButton class="mt-2" @click="openCreateForm">New project</AppButton>
        </template>
      </div>

      <div
        v-else
        class="transition-opacity md:rounded-md md:border md:border-rule md:bg-white md:px-6"
        :class="{ 'opacity-60': loading }"
      >
        <ProjectList :projects="projects" @edit="openEditForm" @delete="confirmDelete" />
      </div>

      <ProjectPagination
        v-if="pagination"
        class="mt-4"
        :pagination="pagination"
        @update="changePage"
      />
    </section>

    <ProjectFormDialog
      :open="formOpen"
      :project="editingProject"
      @close="formOpen = false"
      @saved="onSaved"
    />

    <ConfirmDialog
      :open="projectToDelete !== null"
      title="Delete this project?"
      confirm-label="Delete project"
      :busy="deleting"
      :error="deleteError"
      @confirm="deleteConfirmed"
      @cancel="projectToDelete = null"
    >
      <p v-if="projectToDelete">
        <strong class="font-semibold text-ink">{{ projectToDelete.name }}</strong>
        for {{ projectToDelete.client_name }} will be permanently deleted.
      </p>
    </ConfirmDialog>
  </main>
</template>
