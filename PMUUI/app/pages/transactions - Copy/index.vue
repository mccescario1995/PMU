<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, ref, computed, watch } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { h } from "vue";
import { useTablePagination } from "~/composables/useTablePagination";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const transactions = ref<any[]>([]);
const {
  page,
  pageSize,
  pageSizeNumber,
  goToPageInput,
  totalPages,
  handleGoToPage,
  data,
  totalItems,
  loading,
} = useTablePagination(null, 10, {
  fetchData: async (page, pageSize) => {
    const result = await apiFetch(
      `/v1/transactions?page=${page}&per_page=${pageSize}`,
      { parseJson: true },
    );
    return { data: result.data, total: result.meta.total };
  },
});

function formatFeeTypes(items: any[]): string {
  if (!items || items.length === 0) return "-";
  if (items.length === 1) return items[0].fee_type?.fee_name ?? "-";
  return items
    .map((i: any) => i.fee_type?.fee_name)
    .filter(Boolean)
    .join(", ");
}

type Transactions = {
  id: number;
  stakeholder: string;
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
    accessorKey: "stakeholder",
    header: "Stakeholder",
    cell: ({ row }) => row.original.stakeholder?.name,
  },
  {
    accessorKey: "type",
    header: "Type(s)",
    cell: ({ row }) => {
      const tx = data.value.find((t: any) => t.id === row.getValue("id"));
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
      return new Date(row.getValue("transaction_date")).toLocaleString(
        "en-US",
        {
          day: "numeric",
          month: "short",
          year: "numeric",
        },
      );
    },
  },
];
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-5">
      <h1 class="text-2xl font-bold">Transactions</h1>

      <UButton v-if="can('create transactions')" to="/transactions/create">
        Add Transaction
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton
          size="xs"
          :to="`/transactions/${row.original.id}`"
          icon="i-lucide-eye"
        ></UButton>
      </template>
    </UTable>

    <div class="flex items-center justify-between mt-4">
      <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Rows per page:</span>
        <USelect v-model="pageSize" :items="[5, 10, 20, 30, 50]" class="w-20" />
      </div>
      <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Go to page:</span>
        <UInput
          v-model="goToPageInput"
          type="number"
          :min="1"
          :max="totalPages"
          class="w-16"
          @keyup.enter="handleGoToPage"
        />
        <UButton size="sm" @click="handleGoToPage">Go</UButton>
      </div>
      <UPagination
        :total="totalItems"
        v-model:page="page"
        :items-per-page="pageSizeNumber"
      />
    </div>
  </div>
</template>
