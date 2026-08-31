<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, h, computed, watch } from "vue";
import { ref } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { useTablePagination } from "~/composables/useTablePagination";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const logs = ref<any[]>([]);
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
    const result = await apiFetch(`/v1/audit-logs?page=${page}&per_page=${pageSize}`, { parseJson: true })
    return { data: result.data, total: result.meta.total }
  },
})

const actionColor: Record<string, string> = {
  create: "success",
  update: "info",
  delete: "error",
  login: "primary",
  logout: "neutral",
};

type AuditLog = {
  id: number;
  user_name: string;
  action: string;
  model_type: string;
  model_id: number;
  created_at: string;
};

const columns: TableColumn<AuditLog>[] = [
  { accessorKey: "id", header: "#" },
  {
    accessorKey: "action",
    header: "Action",
    cell: ({ row }) => {
      const a = row.getValue("action");
      return h("span", { class: "capitalize" }, () => a);
    },
  },
  { accessorKey: "user_name", header: "User" },
  { accessorKey: "model_type", header: "Model" },
  { accessorKey: "model_id", header: "Model ID" },
  {
    accessorKey: "created_at",
    header: "Timestamp",
    cell: ({ row }) =>
      new Date(row.getValue("created_at")).toLocaleString("en-US"),
  },
];
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Audit Logs</h1>
      <UButton
        v-if="can('view audit logs')"
        icon="i-lucide-download"
        @click="alert('Export coming soon')"
      >
        Export
      </UButton>
    </div>

    <UTable
      :data="data"
      :columns="columns"
      :loading="loading"
    >
      <template #action-cell="{ row }">
        <UButton
          size="xs"
          variant="ghost"
          :to="`/cms/audit-logs/${row.original.id}`"
          icon="i-lucide-eye"
        />
      </template>
    </UTable>

    <div class="flex items-center justify-between mt-4">
      <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Rows per page:</span>
        <USelect
          v-model="pageSize"
          :items="[5, 10, 20, 30, 50]"
          class="w-20"
        />
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
