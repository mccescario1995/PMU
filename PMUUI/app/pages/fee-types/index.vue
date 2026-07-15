<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const feeTypes = ref<any[]>([])
const loading = ref(true)
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
  loading.value = true
  feeTypes.value = (await apiFetch('/v1/fee-types', { parseJson: true })) as any[]
  loading.value = false
}

onMounted(load)
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

    <UTable :data="feeTypes" :columns="[
      { accessorKey: 'id', header: '#', cell: ({ row }) => `#${row.getValue('id')}` },
      { accessorKey: 'fee_name', header: 'Fee Name' },
      { accessorKey: 'base_rate', header: 'Base Rate', meta: { class: { td: 'text-right font-mono' } }, cell: ({ row }) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(row.getValue('base_rate'))) },
      { accessorKey: 'unit', header: 'Unit' },
      { accessorKey: 'action', header: 'Action' },
    ]" :loading="loading">
      <template #action-data="{ row }">
        <div class="flex gap-1">
          <UButton size="xs" variant="outline" @click="startEdit(row.original)"> Edit </UButton>
          <UButton size="xs" color="error" variant="ghost" @click="remove(row.original)"> Delete </UButton>
        </div>
      </template>
    </UTable>
  </div>
</template>
