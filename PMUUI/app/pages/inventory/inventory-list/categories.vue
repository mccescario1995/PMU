<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, ref, computed, watch, h } from 'vue'
import { getPaginationRowModel } from '@tanstack/vue-table'
import { useTablePagination } from '~/composables/useTablePagination'

definePageMeta({
  layout: "dashboard",
});

const UBadge = resolveComponent('UBadge')

const categories = ref<any[]>([])
const { page, pageSize, pageSizeNumber, goToPageInput, tablePagination, totalPages, handleGoToPage } = useTablePagination(() => categories.value.length)

onMounted(async () => {
  const items = ((await apiFetch('/v1/inventory/items', { parseJson: true })) as any).data
  const counts: Record<string, { count: number; quantity: number; low_stock: number }> = {}
  for (const it of items) {
    const type = it.category_type ?? 'unknown'
    if (!counts[type]) {
      counts[type] = { count: 0, quantity: 0, low_stock: 0 }
    }
    counts[type].count++
    counts[type].quantity += it.quantity
    if (it.status === 'low_stock' || it.status === 'damaged') {
      counts[type].low_stock++
    }
  }
  categories.value = Object.entries(counts).map(([name, data], i) => ({
    id: i + 1,
    name,
    description: '',
    items: data.count,
    quantity: data.quantity,
    low_stock: data.low_stock,
  }))
})

type Category = {
  id: number
  name: string
  description: string
  items: number
  quantity: number
  low_stock: number
}

const columns: TableColumn<Category>[] = [
  {
    accessorKey: 'id',
    header: '#',
    cell: ({ row }) => `#${row.getValue('id')}`,
  },
  { accessorKey: 'name', header: 'Category Type' },
  {
    accessorKey: 'items',
    header: 'Items',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
  },
  {
    accessorKey: 'quantity',
    header: 'Total Qty',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
  },
  {
    accessorKey: 'low_stock',
    header: 'Low Stock',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
  },
  { accessorKey: 'action', header: 'Action' },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <h1 class="text-2xl font-bold">Inventory Categories</h1>

    <UTable :data="categories" :columns="columns" :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }" v-model:pagination="tablePagination">
      <template #action-cell="{ row }">
        <UButton size="xs" :to="`/inventory/inventory-list/stocks`" icon="i-lucide-eye" ></UButton>
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
      <UPagination :total="categories.length" v-model:page="page" :items-per-page="pageSizeNumber" />
    </div>
  </div>
</template>
