<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from "vue";
import { usePermissions } from "~/composables/usePermissions";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const transactions = ref<any[]>([])

onMounted(async () => {
  const list = (await apiFetch('/v1/transactions', { parseJson: true })) as any[]
  transactions.value = list.map((t) => ({
    id: t.id,
    type: t.items?.[0]?.fee_type?.fee_name ?? t.stakeholder?.name ?? "-",
    amount: t.total_amount,
    date: t.transaction_date,
  }))
})

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
    header: "Type",
    cell: ({ row }) => row.getValue("type"),
  },
  {
    accessorKey: "amount",
    header: "Amount",
    meta: {
      // do this if money
      class: {
        th: "text-right font-bold text-primary",
        td: "text-right font-mono",
      },
    },
    cell: ({ row }) => {
      const amount = Number.parseFloat(row.getValue("amount"));
      const formatted = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "PHP",
      }).format(amount);
      return h("span", { class: "font-semibold text-success" }, formatted);
    },
  },
  {
    accessorKey: "date",
    header: "Date",
    cell: ({ row }) => {
      return new Date(row.getValue('date')).toLocaleString('en-US', {
        day: 'numeric',
        month: 'short',
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
      <template #action-data="{ row }">
        <UButton size="xs" :to="`/transactions/${row.id}`"> View </UButton>
      </template>
    </UTable>
  </div>
</template>
