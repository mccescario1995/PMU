<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui';
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue';

definePageMeta({
  layout: "dashboard",
});


const UBadge = resolveComponent('UBadge')

const items = ref<any[]>([])

onMounted(async () => {
  items.value = (await apiFetch('/v1/inventory/items', { parseJson: true })) as any[]
})

type Inventory = {
  id: number;
  item_name: string;
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
    accessorKey: 'quantity',
    header: 'Quantity',
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const color = statusColor[row.getValue('status') as keyof typeof statusColor]

      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () =>
        row.getValue('status')
      )
    }
  }
]

</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-5">
      <h1 class="text-2xl font-bold">Inventory</h1>

      <UButton to="/inventory/create"> Add Item </UButton>
    </div>

    <UTable
      :data="items"
      :columns="columns"
    >
      <template #action-data="{ row }">
        <UButton size="xs" :to="`/inventory/${row.id}`"> View </UButton>
      </template>
    </UTable>
  </div>
</template>
