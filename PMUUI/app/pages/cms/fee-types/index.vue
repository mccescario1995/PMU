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

const feeTypes = ref<any[]>([])
const loading = ref(true)
const showForm = ref(false)
const editing = ref<any>(null)

const form = reactive({
  fee_name: '',
  base_rate: 0,
  unit: '',
})

onMounted(async () => {
  loading.value = true
  feeTypes.value = ((await apiFetch('/v1/fee-types', { parseJson: true })) as any).data
  loading.value = false
})

function openCreate() {
  editing.value = null
  form.fee_name = ''
  form.base_rate = 0
  form.unit = ''
  showForm.value = true
}

function openEdit(row: any) {
  editing.value = row
  form.fee_name = row.fee_name
  form.base_rate = row.base_rate
  form.unit = row.unit
  showForm.value = true
}

async function save() {
  if (editing.value) {
    await apiFetch(`/v1/fee-types/${editing.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  } else {
    await apiFetch('/v1/fee-types', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  }
  showForm.value = false
  feeTypes.value = ((await apiFetch('/v1/fee-types', { parseJson: true })) as any).data
}

async function remove(row: any) {
  if (!confirm('Delete this fee type?')) return
  await apiFetch(`/v1/fee-types/${row.id}`, { method: 'DELETE' })
  feeTypes.value = feeTypes.value.filter((f: any) => f.id !== row.id)
}

type FeeType = {
  id: number
  fee_name: string
  base_rate: number
  unit: string
}

const columns: TableColumn<FeeType>[] = [
  { accessorKey: 'id', header: '#' },
  { accessorKey: 'fee_name', header: 'Fee Name' },
  {
    accessorKey: 'base_rate',
    header: 'Base Rate',
    meta: { class: { th: 'text-right', td: 'text-right font-mono' } },
    cell: ({ row }) => {
      const v = Number(row.getValue('base_rate'))
      return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(v)
    },
  },
  { accessorKey: 'unit', header: 'Unit' },
  { accessorKey: 'action', header: 'Action' },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Fee Types</h1>
      <UButton v-if="can('manage settings')" icon="i-lucide-plus" @click="openCreate"> Add Fee Type </UButton>
    </div>

    <UTable :data="feeTypes" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton v-if="can('manage settings')" size="xs" @click="openEdit(row.original)" icon="i-lucide-edit" ></UButton>
        <UButton v-if="can('manage settings')" size="xs" color="error" variant="ghost" @click="remove(row.original)" icon="i-lucide-trash" ></UButton>
      </template>
    </UTable>

    <UModal v-model:open="showForm">
      <UCard>
        <template #header> {{ editing ? 'Edit Fee Type' : 'New Fee Type' }} </template>
        <div class="space-y-4">
          <UFormField label="Fee Name">
            <UInput v-model="form.fee_name" />
          </UFormField>
          <UFormField label="Base Rate">
            <UInput v-model="form.base_rate" type="number" step="0.01" />
          </UFormField>
          <UFormField label="Unit">
            <USelect v-model="form.unit" :items="['kg', 'trip', 'day', 'month', 'head', 'item', 'unit', 'transaction', 'hour']" />
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
