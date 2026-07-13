<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const UBadge = resolveComponent('UBadge')

const currency = (value: number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

const revenue = ref<any[]>([])

onMounted(async () => {
  revenue.value = (await apiFetch('/v1/dashboard/revenue-breakdown', { parseJson: true })) as any[]
})

type Revenue = {
  source: string
  amount: number
  count: number
}

const total = computed(() => revenue.value.reduce((sum, r) => sum + (r.amount ?? 0), 0))
const totalCount = computed(() => revenue.value.reduce((sum, r) => sum + (r.count ?? 0), 0))
const sources = computed(() => revenue.value.length)

const columns: TableColumn<Revenue>[] = [
  {
    accessorKey: 'source',
    header: 'Revenue Source',
  },
  {
    accessorKey: 'amount',
    header: 'Amount',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
    cell: ({ row }) => currency(Number(row.getValue("amount"))),
  },
  {
    accessorKey: 'count',
    header: 'Transactions',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
    cell: ({ row }) => row.getValue("count"),
  },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Revenue</h1>
      <p class="text-slate-500">Breakdown of income by port operation fee.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <UCard>
        <template #header> Total Revenue </template>
        <p class="text-2xl font-bold text-success">{{ currency(total) }}</p>
      </UCard>
      <UCard>
        <template #header> Total Transactions </template>
        <p class="text-2xl font-bold">{{ totalCount }}</p>
      </UCard>
      <UCard>
        <template #header> Revenue Sources </template>
        <p class="text-2xl font-bold text-success">{{ sources }}</p>
      </UCard>
    </div>

    <UTable :data="revenue" :columns="columns" />
  </div>
</template>
