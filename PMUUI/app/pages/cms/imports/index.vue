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

const imports = ref<any[]>([])
const loading = ref(true)

onMounted(async () => {
  loading.value = true
  imports.value = ((await apiFetch('/v1/imports', { parseJson: true })) as any).data
  loading.value = false
})

const statusColor: Record<string, string> = {
  pending: 'warning',
  processing: 'info',
  completed: 'success',
  failed: 'error',
}

type Import = {
  id: number
  file_name: string
  status: string
  errors: number
  imported_at: string
}

const columns: TableColumn<Import>[] = [
  { accessorKey: 'id', header: '#' },
  { accessorKey: 'file_name', header: 'File Name' },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const s = row.getValue('status')
      return h('span', { class: 'capitalize' }, () => s)
    },
  },
  {
    accessorKey: 'errors',
    header: 'Errors',
    meta: { class: { th: 'text-right', td: 'text-right' } },
  },
  {
    accessorKey: 'imported_at',
    header: 'Imported At',
    cell: ({ row }) => new Date(row.getValue('imported_at')).toLocaleString('en-US'),
  },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Import Logs</h1>
      <UButton v-if="can('manage settings')" icon="i-lucide-upload" @click="alert('Import feature coming soon')"> Import Data </UButton>
    </div>

    <UTable :data="imports" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton size="xs" variant="ghost" @click="alert('View import details coming soon')"> View </UButton>
      </template>
    </UTable>
  </div>
</template>
