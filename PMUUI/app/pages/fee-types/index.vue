<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { ref, reactive, computed, watch, h } from 'vue'
import { useTablePagination } from '~/composables/useTablePagination'

definePageMeta({
  layout: "dashboard",
});

const feeTypes = ref<any[]>([])
const { page, pageSize, pageSizeNumber, goToPageInput, totalPages, handleGoToPage, data, totalItems, loading } = useTablePagination(null, 10, {
  fetchData: async (page, pageSize) => {
    const result = await apiFetch(`/v1/fee-types?page=${page}&per_page=${pageSize}`, { parseJson: true })
    return { data: result.data, total: result.meta.total }
  },
})
const showForm = ref(false)
const editing = ref<any>(null)

const form = reactive({
  fee_name: '',
  base_rate: 0,
  unit: '',
})

function reset() {
  form.fee_name = ''
  form.base_rate = 0
  form.unit = ''
  editing.value = null
  showForm.value = false
}

function startEdit(item: any) {
  editing.value = item
  form.fee_name = item.fee_name
  form.base_rate = item.base_rate
  form.unit = item.unit ?? ''
  showForm.value = true
}

async function submit() {
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
  reset()
  await load()
}

async function remove(item: any) {
  if (!confirm('Delete this fee type?')) return
  await apiFetch(`/v1/fee-types/${item.id}`, { method: 'DELETE' })
  await load()
}

async function load() {
  const result = await apiFetch('/v1/fee-types', { parseJson: true })
  data.value = result.data
  totalItems.value = result.total
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
      <div>
        <h1 class="text-2xl font-bold">Fee Types</h1>
        <p class="text-slate-500">Manage port fee configurations.</p>
      </div>
      <UButton icon="i-lucide-plus" @click="showForm = true; reset()"> Add Fee Type </UButton>
    </div>

    <UCard v-if="showForm">
      <template #header> {{ editing ? 'Edit' : 'New' }} Fee Type </template>
      <UForm @submit="submit">
        <UFormField label="Fee Name" required>
          <UInput v-model="form.fee_name" />
        </UFormField>
        <UFormField label="Base Rate (PHP)" required>
          <UInput type="number" v-model="form.base_rate" />
        </UFormField>
        <UFormField label="Unit">
          <UInput v-model="form.unit" placeholder="kg, trip, day..." />
        </UFormField>
        <div class="flex gap-2 mt-4">
          <UButton type="submit"> Save </UButton>
          <UButton variant="ghost" @click="reset"> Cancel </UButton>
        </div>
      </UForm>
    </UCard>

    <UTable :data="data" :columns="[
      { accessorKey: 'id', header: '#', cell: ({ row }) => `#${row.getValue('id')}` },
      { accessorKey: 'fee_name', header: 'Fee Name' },
      { accessorKey: 'base_rate', header: 'Base Rate', meta: { class: { td: 'text-right font-mono' } }, cell: ({ row }) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(row.getValue('base_rate'))) },
      { accessorKey: 'unit', header: 'Unit' },
      { accessorKey: 'action', header: 'Action' },
    ]" :loading="loading">
      <template #action-cell="{ row }">
        <div class="flex gap-1">
          <UButton size="xs" variant="outline" @click="startEdit(row.original)" icon="i-lucide-edit" ></UButton>
          <UButton size="xs" color="error" variant="ghost" @click="remove(row.original)" icon="i-lucide-trash" ></UButton>
        </div>
      </template>
    </UTable>

    <div class="flex items-center justify-between mt-4">
      <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Rows per page:</span>
        <USelect v-model="pageSize" :items="[5, 10, 20, 30, 50]" class="w-20" />
      </div>
      <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Go to page:</span>
        <UInput v-model="goToPageInput" type="number" :min="1" :max="totalPages" class="w-16" @keyup.enter="handleGoToPage" />
        <UButton size="sm" @click="handleGoToPage">Go</UButton>
      </div>
      <UPagination :total="totalItems" v-model:page="page" :items-per-page="pageSizeNumber" />
    </div>
  </div>
</template>
