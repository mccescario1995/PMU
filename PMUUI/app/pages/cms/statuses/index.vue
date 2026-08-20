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

const statuses = ref<any[]>([])
const loading = ref(true)
const showForm = ref(false)
const editing = ref<any>(null)

const form = reactive({
  name: '',
  type: 'inventory',
  color: 'green',
})

onMounted(async () => {
  loading.value = true
  statuses.value = ((await apiFetch('/v1/statuses', { parseJson: true })) as any).data
  loading.value = false
})

function openCreate() {
  editing.value = null
  form.name = ''
  form.type = 'inventory'
  form.color = 'green'
  showForm.value = true
}

function openEdit(row: any) {
  editing.value = row
  form.name = row.name
  form.type = row.type
  form.color = row.color
  showForm.value = true
}

async function save() {
  if (editing.value) {
    await apiFetch(`/v1/statuses/${editing.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  } else {
    await apiFetch('/v1/statuses', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  }
  showForm.value = false
  statuses.value = ((await apiFetch('/v1/statuses', { parseJson: true })) as any).data
}

async function remove(row: any) {
  if (!confirm('Delete this status?')) return
  await apiFetch(`/v1/statuses/${row.id}`, { method: 'DELETE' })
  statuses.value = statuses.value.filter((s: any) => s.id !== row.id)
}

const colorOptions = [
  { label: 'Green', value: 'green' },
  { label: 'Yellow', value: 'yellow' },
  { label: 'Red', value: 'red' },
  { label: 'Blue', value: 'blue' },
  { label: 'Gray', value: 'gray' },
]

const typeOptions = [
  { label: 'Inventory', value: 'inventory' },
  { label: 'Transaction', value: 'transaction' },
  { label: 'Stakeholder', value: 'stakeholder' },
]

type Status = {
  id: number
  name: string
  type: string
  color: string
}

const columns: TableColumn<Status>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    cell: ({ row }) => h('span', { class: 'capitalize' }, () => row.getValue('name')),
  },
  {
    accessorKey: 'type',
    header: 'Type',
    cell: ({ row }) => {
      const t = row.getValue('type')
      return h('span', { class: 'capitalize' }, () => t)
    },
  },
  {
    accessorKey: 'color',
    header: 'Color',
    cell: ({ row }) => {
      const c = row.getValue('color')
      return h(UBadge, { color: c as any, variant: 'subtle' }, () => c)
    },
  },
  { accessorKey: 'action', header: 'Action' },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Statuses</h1>
      <UButton v-if="can('manage settings')" icon="i-lucide-plus" @click="openCreate"> Add Status </UButton>
    </div>

    <UTable :data="statuses" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton v-if="can('manage settings')" size="xs" @click="openEdit(row.original)" icon="i-lucide-edit" ></UButton>
        <UButton v-if="can('manage settings')" size="xs" color="error" variant="ghost" @click="remove(row.original)" icon="i-lucide-trash" ></UButton>
      </template>
    </UTable>

    <UModal v-model:open="showForm">
      <UCard>
        <template #header> {{ editing ? 'Edit Status' : 'New Status' }} </template>
        <div class="space-y-4">
          <UFormField label="Name">
            <UInput v-model="form.name" />
          </UFormField>
          <UFormField label="Type">
            <USelect v-model="form.type" :items="typeOptions" value-attribute="value" option-attribute="label" />
          </UFormField>
          <UFormField label="Color">
            <USelect v-model="form.color" :items="colorOptions" value-attribute="value" option-attribute="label" />
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
