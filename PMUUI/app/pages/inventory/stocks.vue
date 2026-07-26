<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const UBadge = resolveComponent('UBadge')

const statusColor = {
  available: 'success' as const,
  low_stock: 'warning' as const,
  damaged: 'error' as const,
}

const stocks = ref<any[]>([])
const loading = ref(true)

onMounted(async () => {
  loading.value = true
  stocks.value = ((await apiFetch('/v1/inventory/items', { parseJson: true })) as any).data
  loading.value = false
})


type Stock = {
  id: number
  item_name: string
  category: string
  category_type: string
  quantity: number
  status: keyof typeof statusColor
}

const columns: TableColumn<Stock>[] = [
  {
    accessorKey: 'id',
    header: '#',
    cell: ({ row }) => `#${row.getValue('id')}`,
  },
  { accessorKey: 'item_name', header: 'Item' },
  { accessorKey: 'category_type', header: 'Type', cell: ({ row }) => {
    const type = row.getValue('category_type')
    const color = type === 'equipment' ? 'primary' : type === 'materials' ? 'success' : 'warning'
    return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () => type)
  }},
  { accessorKey: 'category', header: 'Category' },
  {
    accessorKey: 'quantity',
    header: 'Quantity',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.getValue('status') as keyof typeof statusColor
      return h(UBadge, { class: "capitalize", variant: "subtle", color: statusColor[status] }, () => status)
    },
  },
  { accessorKey: 'action', header: 'Action' },
]

async function remove(row: any) {
  if (!confirm('Delete this item?')) return
  await apiFetch(`/v1/inventory/items/${row.original.id}`, { method: 'DELETE' })
  stocks.value = stocks.value.filter(i => i.id !== row.id)
}
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Stocks</h1>
      <UButton to="/inventory/create" icon="i-lucide-plus"> Add Item </UButton>
    </div>

    <UTable :data="stocks" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton size="xs" :to="`/inventory/${row.original.id}`"> View </UButton>
        <UButton v-if="can('manage inventory')" size="xs" :to="`/inventory/edit/${row.original.id}`"> Edit </UButton>
        <UButton v-if="can('manage inventory')" size="xs" color="error" variant="ghost" @click="remove(row)"> Delete </UButton>
      </template>
    </UTable>
  </div>
</template>
