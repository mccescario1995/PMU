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
  logs.value = (await apiFetch('/v1/inventory/logs', { parseJson: true })) as any[]
  loading.value = false
})

const columns: TableColumn<any>[] = [
  { accessorKey: 'id', header: '#', cell: ({ row }) => `#${row.getValue('id')}` },
  { accessorKey: 'item', header: 'Item', cell: ({ row }) => row.original.item?.item_name ?? `#${row.original.inventory_item_id}` },
  { accessorKey: 'action', header: 'Action' },
  { accessorKey: 'quantity_changed', header: 'Qty Changed', meta: { class: { td: 'text-right font-mono' } } },
  { accessorKey: 'user', header: 'User', cell: ({ row }) => row.original.user?.name ?? '-' },
  { accessorKey: 'created_at', header: 'Date', cell: ({ row }) => new Date(row.original.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div>
      <h1 class="text-2xl font-bold">Inventory Logs</h1>
      <p class="text-slate-500">History of all stock movements.</p>
    </div>

    <UTable :data="logs" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UBadge :color="row.original.action === 'add' ? 'success' : row.original.action === 'deduct' ? 'error' : 'warning'" variant="subtle">
          {{ row.original.action }}
        </UBadge>
      </template>
    </UTable>
  </div>
</template>
