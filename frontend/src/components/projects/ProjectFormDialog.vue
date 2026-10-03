<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { ApiError } from '@/api/client'
import { createProject, updateProject } from '@/api/projects'
import AppButton from '@/components/AppButton.vue'
import AppDialog from '@/components/AppDialog.vue'
import FormField from '@/components/FormField.vue'
import {
  PRIORITY_LABELS,
  PROJECT_PRIORITIES,
  PROJECT_STATUSES,
  STATUS_LABELS,
  type Project,
  type ProjectPayload,
  type ProjectPriority,
  type ProjectStatus,
} from '@/types'

const props = defineProps<{
  open: boolean
  /** The project to edit, or null to create a new one. */
  project: Project | null
}>()

const emit = defineEmits<{
  close: []
  saved: [project: Project, action: 'created' | 'updated']
}>()

interface FormState {
  client_name: string
  name: string
  description: string
  status: ProjectStatus
  priority: ProjectPriority
  start_date: string
  due_date: string
}

const form = reactive<FormState>(emptyForm())
const error = ref<ApiError | null>(null)
const saving = ref(false)

const isEditing = computed(() => props.project !== null)

function emptyForm(): FormState {
  return {
    client_name: '',
    name: '',
    description: '',
    status: 'planning',
    priority: 'medium',
    start_date: '',
    due_date: '',
  }
}

function formFromProject(project: Project): FormState {
  return {
    client_name: project.client_name,
    name: project.name,
    description: project.description ?? '',
    status: project.status,
    priority: project.priority,
    start_date: project.start_date ?? '',
    due_date: project.due_date ?? '',
  }
}

/** Blank optional fields are sent as null so the API stores "no value". */
function toPayload(state: FormState): ProjectPayload {
  return {
    client_name: state.client_name.trim(),
    name: state.name.trim(),
    description: state.description.trim() || null,
    status: state.status,
    priority: state.priority,
    start_date: state.start_date || null,
    due_date: state.due_date || null,
  }
}

// Start from a clean form every time the dialog opens.
watch(
  () => props.open,
  (open) => {
    if (open) {
      Object.assign(form, props.project ? formFromProject(props.project) : emptyForm())
      error.value = null
    }
  },
  { immediate: true },
)

async function submit(): Promise<void> {
  saving.value = true
  error.value = null

  try {
    const payload = toPayload(form)

    if (props.project) {
      emit('saved', await updateProject(props.project.id, payload), 'updated')
    } else {
      emit('saved', await createProject(payload), 'created')
    }
  } catch (caught) {
    error.value = caught instanceof ApiError ? caught : new ApiError(0, 'Something went wrong.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppDialog
    :open="open"
    :title="isEditing ? 'Edit project' : 'New project'"
    :busy="saving"
    @close="emit('close')"
  >
    <form class="flex min-h-0 flex-col" novalidate @submit.prevent="submit">
      <div class="flex flex-col gap-5 overflow-y-auto px-6 pt-4 pb-6">
        <p
          v-if="error && !error.isValidationError"
          role="alert"
          class="rounded-md bg-alert/10 px-3 py-2 text-sm text-alert"
        >
          {{ error.message }}
        </p>

        <div class="grid gap-5 sm:grid-cols-2">
          <FormField
            v-slot="{ control }"
            label="Client name"
            required
            :error="error?.fieldError('client_name')"
          >
            <input
              v-bind="control"
              v-model="form.client_name"
              type="text"
              required
              autofocus
              maxlength="255"
              class="field-control"
            />
          </FormField>

          <FormField
            v-slot="{ control }"
            label="Project name"
            required
            :error="error?.fieldError('name')"
          >
            <input
              v-bind="control"
              v-model="form.name"
              type="text"
              required
              maxlength="255"
              class="field-control"
            />
          </FormField>
        </div>

        <FormField
          v-slot="{ control }"
          label="Description"
          :error="error?.fieldError('description')"
        >
          <textarea
            v-bind="control"
            v-model="form.description"
            rows="3"
            maxlength="5000"
            class="field-control resize-y"
          />
        </FormField>

        <div class="grid gap-5 sm:grid-cols-2">
          <FormField
            v-slot="{ control }"
            label="Status"
            required
            :error="error?.fieldError('status')"
          >
            <select v-bind="control" v-model="form.status" required class="field-control">
              <option v-for="status in PROJECT_STATUSES" :key="status" :value="status">
                {{ STATUS_LABELS[status] }}
              </option>
            </select>
          </FormField>

          <fieldset class="flex flex-col gap-1.5">
            <legend class="mb-1.5 text-sm font-medium">
              Priority<span class="text-alert" aria-hidden="true"> *</span>
            </legend>
            <div class="grid grid-cols-3 gap-2">
              <label
                v-for="(priority, index) in PROJECT_PRIORITIES"
                :key="priority"
                class="flex h-10.5 cursor-pointer items-center justify-center gap-2 rounded-md border text-sm font-medium transition-colors has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-ink"
                :class="
                  form.priority === priority
                    ? 'border-ink bg-ink text-white'
                    : 'border-rule bg-white hover:border-ink/40'
                "
              >
                <input
                  v-model="form.priority"
                  type="radio"
                  :value="priority"
                  required
                  class="sr-only"
                />
                <span class="inline-flex items-end gap-0.5" aria-hidden="true">
                  <span
                    v-for="bar in 3"
                    :key="bar"
                    class="w-1 rounded-sm"
                    :class="[
                      ['h-1.5', 'h-2.5', 'h-3.5'][bar - 1],
                      bar <= index + 1 ? 'bg-current' : 'bg-current opacity-25',
                    ]"
                  />
                </span>
                {{ PRIORITY_LABELS[priority] }}
              </label>
            </div>
            <p v-if="error?.fieldError('priority')" class="text-sm text-alert">
              {{ error.fieldError('priority') }}
            </p>
          </fieldset>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <FormField
            v-slot="{ control }"
            label="Start date"
            :error="error?.fieldError('start_date')"
          >
            <input v-bind="control" v-model="form.start_date" type="date" class="field-control" />
          </FormField>

          <FormField v-slot="{ control }" label="Due date" :error="error?.fieldError('due_date')">
            <input
              v-bind="control"
              v-model="form.due_date"
              type="date"
              :min="form.start_date || undefined"
              class="field-control"
            />
          </FormField>
        </div>
      </div>

      <footer
        class="flex flex-col-reverse gap-2 border-t border-rule px-6 py-4 sm:flex-row sm:justify-end"
      >
        <AppButton variant="secondary" :disabled="saving" @click="emit('close')">Cancel</AppButton>
        <AppButton type="submit" :loading="saving">
          {{ isEditing ? 'Save changes' : 'Create project' }}
        </AppButton>
      </footer>
    </form>
  </AppDialog>
</template>
