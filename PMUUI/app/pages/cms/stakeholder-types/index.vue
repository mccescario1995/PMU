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

const types = ref<any[]>([])
const loading = ref(true)
const showForm = ref(false)
const editing = ref<any>(null)

const form = reactive({
  name: '',
  description: '',
})

onMounted(async () => {
  loading.value = true
  types.value = ((await apiFetch('/v1/stakeholder-types', { parseJson: true })) as any).data
  loading.value = false
})

function openCreate() {
  editing.value = null
  form.name = ''
  form.description = ''
  showForm.value = true
}

function openEdit(row: any) {
  editing.value = row
  form.name = row.name
  form.description = row.description ?? ''
  showForm.value = true
}

async function save() {
  if (editing.value) {
    await apiFetch(`/v1/stakeholder-types/${editing.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  } else {
    await apiFetch('/v1/stakeholder-types', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  }
  showForm.value = false
  types.value = ((await apiFetch('/v1/stakeholder-types', { parseJson: true })) as any).data
}

async function remove(row: any) {
  if (!confirm('Delete this stakeholder type?')) return
  await apiFetch(`/v1/stakeholder-types/${row.id}`, { method: 'DELETE' })
  types.value = types.value.filter((t: any) => t.id !== row.id)
}

type StakeholderType = {
  id: number
  name: string
  description: string
}

const columns: TableColumn<StakeholderType>[] = [
  { accessorKey: 'id', header: '#' },
  { accessorKey: 'name', header: 'Name' },
  { accessorKey: 'description', header: 'Description' },
  { accessorKey: 'action', header: 'Action' },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Stakeholder Types</h1>
      <UButton v-if="can('manage stakeholders')" icon="i-lucide-plus" @click="openCreate"> Add Type </UButton>
    </div>

    <UTable :data="types" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton v-if="can('manage stakeholders')" size="xs" @click="openEdit(row.original)"> Edit </UButton>
        <UButton v-if="can('manage stakeholders')" size="xs" color="error" variant="ghost" @click="remove(row.original)"> Delete </UButton>
      </template>
    </UTable>

    <UModal v-model:open="showForm">
      <UCard>
        <template #header> {{ editing ? 'Edit Stakeholder Type' : 'New Stakeholder Type' }} </template>
        <div class="space-y-4">
          <UFormField label="Name">
            <UInput v-model="form.name" />
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
