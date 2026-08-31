<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, ref, computed, watch } from 'vue'
import { useTablePagination } from '~/composables/useTablePagination'

definePageMeta({
  layout: "dashboard",
});

const logs = ref<any[]>([])
const { page, pageSize, pageSizeNumber, goToPageInput, totalPages, handleGoToPage, data, totalItems, loading } = useTablePagination(null, 10, {
  fetchData: async (page, pageSize) => {
    const result = await apiFetch(`/v1/inventory/logs?page=${page}&per_page=${pageSize}`, { parseJson: true })
    return { data: result.data, total: result.meta.total }
  },
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

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UBadge :color="row.original.action === 'add' ? 'success' : row.original.action === 'deduct' ? 'error' : 'warning'" variant="subtle">
          {{ row.original.action }}
        </UBadge>
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
