<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const currency = (value: number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

const today = new Date().toISOString().slice(0, 10)

const transactions = ref<any[]>([])
const total = ref(0)
const count = ref(0)

onMounted(async () => {
  const report = (await apiFetch('/v1/reports/daily?date=' + today, { parseJson: true })) as any
  total.value = report.total ?? 0
  count.value = report.count ?? 0
  transactions.value = (report.transactions ?? []).map((t: any) => ({
    id: t.id,
    type: t.items?.[0]?.fee_type?.fee_name ?? t.stakeholder?.name ?? "-",
    stakeholder: t.stakeholder?.name ?? "-",
    amount: t.total_amount,
    time: (t.transaction_date ?? "").toString().slice(11, 16) || "-",
  }))
})

type Transaction = {
  id: number
  type: string
  stakeholder: string
  amount: number
  time: string
}

const columns: TableColumn<Transaction>[] = [
  {
    accessorKey: 'id',
    header: '#',
    cell: ({ row }) => `#${row.getValue('id')}`,
  },
  { accessorKey: 'type', header: 'Type' },
  { accessorKey: 'stakeholder', header: 'Stakeholder' },
  { accessorKey: 'time', header: 'Time' },
  {
    accessorKey: 'amount',
    header: 'Amount',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
    cell: ({ row }) => currency(Number(row.getValue("amount"))),
  },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Daily Report</h1>
        <p class="text-slate-500">{{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
      </div>
      <UButton icon="i-lucide-download"> Export </UButton>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <UCard>
        <template #header> Transactions Today </template>
        <p class="text-2xl font-bold">{{ count }}</p>
      </UCard>
      <UCard>
        <template #header> Total Collection </template>
        <p class="text-2xl font-bold text-success">{{ currency(total) }}</p>
      </UCard>
    </div>

    <UTable :data="transactions" :columns="columns" />
  </div>
</template>
