<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, ref, computed, watch } from 'vue'
import { useTablePagination } from '~/composables/useTablePagination'

definePageMeta({
  layout: "dashboard",
});

const brokers = ref<any[]>([])
const {
  page,
  pageSize, pageSizeNumber,
  goToPageInput,
  totalPages,
  handleGoToPage,
  data,
  totalItems,
  loading,
} = useTablePagination(null, 10, {
  fetchData: async (page, pageSize) => {
    const result = await apiFetch(`/v1/stakeholders?page=${page}&per_page=${pageSize}&type=broker`, { parseJson: true })
    return { data: result.data, total: result.meta.total }
  }
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

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton size="xs" :to="`/stakeholders/${row.original.id}`" icon="i-lucide-eye" ></UButton>
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
