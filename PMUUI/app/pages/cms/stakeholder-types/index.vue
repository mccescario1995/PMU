<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, h, computed, watch } from 'vue'
import { ref } from 'vue'

import { usePermissions } from "~/composables/usePermissions";
import { getPaginationRowModel } from '@tanstack/vue-table'
import { useTablePagination } from '~/composables/useTablePagination'

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const toast = useToast();

const types = ref<any[]>([])
const { page, pageSize, pageSizeNumber, goToPageInput, tablePagination, totalPages, handleGoToPage } = useTablePagination(() => types.value.length)
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

    toast.add({ title: 'Stakeholder type created', description: 'The stakeholder type was created successfully.', color: 'success' })
  } else {
    await apiFetch('/v1/stakeholder-types', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })

    toast.add({ title: 'Stakeholder type updated', description: 'The stakeholder type was updated successfully.', color: 'success' })
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
      <UButton v-if="can('create stakeholder types')" icon="i-lucide-plus" @click="openCreate"> Add Type </UButton>


    </div>

    <UTable :data="types" :columns="columns" :loading="loading" :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }" v-model:pagination="tablePagination">
      <template #action-cell="{ row }">
        <UButton v-if="can('edit stakeholder types')" size="xs" @click="openEdit(row.original)" icon="i-lucide-edit" ></UButton>
        <UButton v-if="can('delete stakeholder types')" size="xs" color="error" variant="ghost" @click="remove(row.original)" icon="i-lucide-trash" ></UButton>
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
        <UPagination :total="types.length" v-model:page="page" :items-per-page="pageSizeNumber" />
      </div>

    <UModal v-model:open="showForm">

        <template #header> {{ editing ? 'Edit Stakeholder Type' : 'New Stakeholder Type' }} </template>
        <template #body class="space-y-4">
          <UFormField label="Name">
            <UInput v-model="form.name" />
          </UFormField>
          <UFormField label="Description">
            <UTextarea v-model="form.description" />
          </UFormField>
        </template>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" @click="showForm = false">Cancel</UButton>
            <UButton @click="save">Save</UButton>
          </div>
        </template>

    </UModal>
  </div>
</template>
