<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, h } from 'vue'
import { ref } from 'vue'
import { usePermissions } from "~/composables/usePermissions";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const UBadge = resolveComponent('UBadge')

const settings = ref<any[]>([])
const loading = ref(true)
const showForm = ref(false)
const editing = ref<any>(null)

const form = reactive({
  key: '',
  value: '',
  type: 'string',
  description: '',
})

onMounted(async () => {
  loading.value = true
  settings.value = ((await apiFetch('/v1/settings', { parseJson: true })) as any).data
  loading.value = false
})

function openCreate() {
  editing.value = null
  form.key = ''
  form.value = ''
  form.type = 'string'
  form.description = ''
  showForm.value = true
}

function openEdit(row: any) {
  editing.value = row
  form.key = row.key
  form.value = row.value ?? ''
  form.type = row.type ?? 'string'
  form.description = row.description ?? ''
  showForm.value = true
}

async function save() {
  if (editing.value) {
    await apiFetch(`/v1/settings/${editing.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  } else {
    await apiFetch('/v1/settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  }
  showForm.value = false
  settings.value = ((await apiFetch('/v1/settings', { parseJson: true })) as any).data
}

async function remove(row: any) {
  if (!confirm('Delete this setting?')) return
  await apiFetch(`/v1/settings/${row.id}`, { method: 'DELETE' })
  settings.value = settings.value.filter((s: any) => s.id !== row.id)
}

const typeOptions = ['string', 'number', 'boolean', 'json']

type Setting = {
  id: number
  key: string
  value: string
  type: string
  description: string
}

const typeColor: Record<string, string> = {
  number: 'primary',
  boolean: 'success',
  json: 'warning',
}

const columns: TableColumn<Setting>[] = [
  { accessorKey: 'key', header: 'Key' },
  { accessorKey: 'value', header: 'Value' },
  {
    accessorKey: 'type',
    header: 'Type',
    cell: ({ row }) => {
      const t = row.getValue('type')
      const color = typeColor[t as string] ?? 'neutral'
      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () => t)
    },
  },
  { accessorKey: 'description', header: 'Description' },
  { accessorKey: 'action', header: 'Action' },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Settings</h1>
      <UButton v-if="can('manage settings')" icon="i-lucide-plus" @click="openCreate"> Add Setting </UButton>
    </div>

    <UTable :data="settings" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton v-if="can('manage settings')" size="xs" @click="openEdit(row.original)" icon="i-lucide-edit" ></UButton>
        <UButton v-if="can('manage settings')" size="xs" color="error" variant="ghost" @click="remove(row.original)" icon="i-lucide-trash" ></UButton>
      </template>
    </UTable>

    <UModal v-model:open="showForm">
      <UCard>
        <template #header> {{ editing ? 'Edit Setting' : 'New Setting' }} </template>
        <div class="space-y-4">
          <UFormField label="Key">
            <UInput v-model="form.key" :disabled="!!editing" />
          </UFormField>
          <UFormField label="Value">
            <UInput v-model="form.value" />
          </UFormField>
          <UFormField label="Type">
            <USelect v-model="form.type" :items="typeOptions" />
          </UFormField>
          <UFormField label="Description">
            <UTextarea v-model="form.description" />
          </UFormField>
        </div>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" @click="showForm = false">Cancel</UButton>
            <UButton @click="save">Save</UButton>
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>
