<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const brokers = ref<any[]>([])

onMounted(async () => {
  brokers.value = (await apiFetch('/v1/stakeholders?type=broker', { parseJson: true })) as any[]
})

type Broker = {
  id: number
  name: string
  contact_no: string
  email: string
}

const columns: TableColumn<Broker>[] = [
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
      <h1 class="text-2xl font-bold">Brokers</h1>
      <UButton to="/stakeholders/create" icon="i-lucide-plus"> Add Broker </UButton>
    </div>

    <UTable :data="brokers" :columns="columns">
      <template #action-data="{ row }">
        <UButton size="xs" :to="`/stakeholders/${row.id}`"> View </UButton>
      </template>
    </UTable>
  </div>
</template>
