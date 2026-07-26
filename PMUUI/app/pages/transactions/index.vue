<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { h } from "vue";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const transactions = ref<any[]>([])

onMounted(async () => {
  const list = ((await apiFetch('/v1/transactions', { parseJson: true })) as any[]).data
  transactions.value = list
})

function formatFeeTypes(items: any[]): string {
  if (!items || items.length === 0) return "-";
  if (items.length === 1) return items[0].fee_type?.fee_name ?? "-";
  return items.map((i: any) => i.fee_type?.fee_name).filter(Boolean).join(", ");
}

type Transactions = {
  id: number;
  type: string;
  amount: number;
  date: string;
};

const columns: TableColumn<Transactions>[] = [
  {
    accessorKey: "id",
    header: "#",
    cell: ({ row }) => `#${row.getValue("id")}`,
  },
  {
    accessorKey: "type",
    header: "Type(s)",
    cell: ({ row }) => {
      const tx = transactions.value.find(t => t.id === row.getValue("id"));
      return formatFeeTypes(tx?.items ?? []);
    },
  },
  {
    accessorKey: "total_amount",
    header: "Amount",
    meta: {
      class: {
        th: "text-right font-bold text-primary",
        td: "text-right font-mono",
      },
    },
    cell: ({ row }) => {
      const amount = Number.parseFloat(row.getValue("total_amount"));
      const formatted = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "PHP",
      }).format(amount);
      return h("span", { class: "font-semibold text-success" }, formatted);
    },
  },
  {
    accessorKey: "transaction_date",
    header: "Date",
    cell: ({ row }) => {
      return new Date(row.getValue('transaction_date')).toLocaleString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      })
    }
  },
];
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-5">
      <h1 class="text-2xl font-bold">Transactions</h1>

      <UButton v-if="can('manage transactions')" to="/transactions/create"> Add Transaction </UButton>
    </div>

    <UTable :data="transactions" :columns="columns">
      <template #action-cell="{ row }">
        <UButton size="xs" :to="`/transactions/${row.original.id}`"> View </UButton>
      </template>
    </UTable>
  </div>
</template>
