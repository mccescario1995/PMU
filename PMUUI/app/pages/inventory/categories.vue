<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const UBadge = resolveComponent('UBadge')

const categories = ref<any[]>([])

onMounted(async () => {
  const items = (await apiFetch('/v1/inventory/items', { parseJson: true })) as any[]
  const counts: Record<string, number> = {}
  for (const it of items) {
    counts[it.category] = (counts[it.category] ?? 0) + 1
  }
  categories.value = Object.entries(counts).map(([name, count], i) => ({
    id: i + 1,
    name,
    description: "",
    items: count,
  }))
})

type Category = {
  id: number
  name: string
  description: string
  items: number
}

const columns: TableColumn<Category>[] = [
  {
    accessorKey: 'id',
    header: '#',
    cell: ({ row }) => `#${row.getValue('id')}`,
  },
  { accessorKey: 'name', header: 'Name' },
  { accessorKey: 'description', header: 'Description' },
  {
    accessorKey: 'items',
    header: 'Items',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
  },
  { accessorKey: 'action', header: 'Action' },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Inventory Categories</h1>
      <UButton icon="i-lucide-plus"> Add Category </UButton>
    </div>

    <UTable :data="categories" :columns="columns">
      <template #action-data="{ row }">
        <UButton size="xs" :to="`/inventory/stocks`"> View </UButton>
      </template>
    </UTable>
  </div>
</template>
