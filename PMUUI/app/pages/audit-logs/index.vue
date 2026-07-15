<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const logs = ref<any[]>([])
const loading = ref(true)

onMounted(async () => {
  loading.value = true
  const res = await apiFetch('/v1/audit-logs', { parseJson: true })
  logs.value = (res as any).data ?? res
  loading.value = false
})

const columns: TableColumn<any>[] = [
  { accessorKey: 'id', header: '#', cell: ({ row }) => `#${row.getValue('id')}` },
  { accessorKey: 'user', header: 'User', cell: ({ row }) => row.original.user?.name ?? '-' },
  { accessorKey: 'action', header: 'Action' },
  { accessorKey: 'table_name', header: 'Table' },
  { accessorKey: 'record_id', header: 'Record ID' },
  {
    accessorKey: 'old_values',
    header: 'Old Values',
    cell: ({ row }) => row.original.old_values ? JSON.stringify(row.original.old_values).slice(0, 50) : '-',
  },
  {
    accessorKey: 'new_values',
    header: 'New Values',
    cell: ({ row }) => row.original.new_values ? JSON.stringify(row.original.new_values).slice(0, 50) : '-',
  },
  { accessorKey: 'created_at', header: 'Date', cell: ({ row }) => new Date(row.original.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div>
      <h1 class="text-2xl font-bold">Audit Logs</h1>
      <p class="text-slate-500">System change tracking and history.</p>
    </div>

    <UTable :data="logs" :columns="columns" :loading="loading" />
  </div>
</template>
