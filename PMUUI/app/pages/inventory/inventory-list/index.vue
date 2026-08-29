<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'
import { usePermissions } from '~/composables/usePermissions'

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const UBadge = resolveComponent('UBadge')

const items = ref<any[]>([])

const loading = ref(true)

onMounted(async () => {
  loading.value = true
  items.value = ((await apiFetch('/v1/inventory/inventory-list/items', { parseJson: true })) as any).data
  loading.value = false
})

type Inventory = {
  id: number;
  item_name: string;
  category: string;
  category_type: string;
  quantity: number;
  status: keyof typeof statusColor;
}

const statusColor = {
  available: 'success' as const,
  low_stock: 'warning' as const,
  damaged: 'error' as const,
}

const columns: TableColumn<Inventory>[] = [
  {
    accessorKey: 'id',
    header: '#',
    cell: ({ row }) => `#${row.getValue('id')}`
  },
  {
    accessorKey: 'item_name',
    header: 'Name'
  },
  {
    accessorKey: 'category_type',
    header: 'Type',
    cell: ({ row }) => {
      const type = row.getValue('category_type')
      const color = type === 'equipment' ? 'primary' : type === 'materials' ? 'success' : 'warning'
      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () => type)
    }
  },
  {
    accessorKey: 'category',
    header: 'Category',
  },
  {
    accessorKey: 'quantity',
    header: 'Quantity',
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const color = statusColor[row.getValue('status') as keyof typeof statusColor]

      return h(UBadge, { class: 'capitalize', color }, () =>
        row.getValue('status')
      )
    }
  },
  { accessorKey: 'action', header: 'Action' },
]

async function remove(row: any) {
  if (!confirm('Delete this item?')) return
  await apiFetch(`/v1/inventory/inventory-list/items/${row.original.id}`, { method: 'DELETE' })
  items.value = items.value.filter(i => i.id !== row.id)
}
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-5">
      <h1 class="text-2xl font-bold">Inventory</h1>

      <UButton v-if="can('manage inventory')" to="/inventory/inventory-list/create"> Add Item </UButton>
    </div>

    <UTable
      :data="items"
      :columns="columns"
    >
      <template #action-cell="{ row }">
        <div class="flex gap-2"x>
          <UButton size="xs" :to="`/inventory/inventory-list/${row.original.id}`" icon="i-lucide-eye" ></UButton>
          <UButton v-if="can('manage inventory')" size="xs" color="secondary" :to="`/inventory/inventory-list/edit/${row.original.id}`" icon="i-lucide-edit" ></UButton>
          <UButton v-if="can('manage inventory')" size="xs" color="error" @click="remove(row)" icon="i-lucide-trash" ></UButton>
        </div>
      </template>
    </UTable>
  </div>
</template>
