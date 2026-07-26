<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const buyers = ref<any[]>([])

onMounted(async () => {
  buyers.value = (await apiFetch('/v1/stakeholders?type=buyer', { parseJson: true })) as any[]
})

type Buyer = {
  id: number
  name: string
  contact_no: string
  email: string
}

const columns: TableColumn<Buyer>[] = [
  {
    accessorKey: 'id',
    header: '#',
    cell: ({ row }) => `#${row.getValue('id')}`,
  },
  { accessorKey: 'name', header: 'Name' },
  { accessorKey: 'contact_no', header: 'Contact' },
  { accessorKey: 'email', header: 'Email' },
  { accessorKey: 'action', header: 'Action' },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Buyers</h1>
      <UButton to="/stakeholders/create" icon="i-lucide-plus"> Add Buyer </UButton>
    </div>

    <UTable :data="buyers" :columns="columns">
      <template #action-cell="{ row }">
        <UButton size="xs" :to="`/stakeholders/${row.original.id}`"> View </UButton>
      </template>
    </UTable>
  </div>
</template>
