<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { ref, computed, watch, h } from "vue";
import { useTablePagination } from "~/composables/useTablePagination";
import { UPopover } from "#components";

definePageMeta({
  layout: "dashboard",
});

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
      `/v1/audit-logs?page=${page}&per_page=${pageSize}`,
      { parseJson: true },
    );
    // Handle Laravel paginated response: { data: [...], meta: { total: N } }
    // or fallback if meta is at top level
    const items = Array.isArray(result?.data)
      ? result.data
      : Array.isArray(result)
        ? result
        : [];
    const total = result?.meta?.total ?? result?.total ?? items.length;
    return { data: items, total };
  },
});

const columns: TableColumn<any>[] = [
  {
    accessorKey: "id",
    header: "#",
    cell: ({ row }) => `#${row.getValue("id")}`,
  },
  {
    accessorKey: "user",
    header: "User",
    cell: ({ row }) => row.original.user?.name ?? "-",
  },
  { accessorKey: "action", header: "Action" },
  { accessorKey: "table_name", header: "Table" },
  { accessorKey: "record_id", header: "Record ID" },
  {
    accessorKey: "old_values",
    header: "Old Values",
    cell: ({ row }) => {
      const val = formatJson(row.original.old_values);
      if (val === "-" || val.length <= 40) return val;

      return h(
        UPopover,
        {},
        {
          default: () =>
            h(
              "span",
              {
                class:
                  "text-primary underline cursor-pointer hover:text-primary-600",
              },
              val.slice(0, 40) + "...",
            ),
          content: () =>
            h(
              "div",
              {
                class: "whitespace-pre-wrap max-w-md p-3",
              },
              val,
            ),
        },
      );
    },
  },
  {
    accessorKey: "new_values",
    header: "New Values",
    cell: ({ row }) => {
      const val = formatJson(row.original.new_values);
      if (val === "-" || val.length <= 40) return val;

      return h(
        UPopover,
        {},
        {
          default: () =>
            h(
              "span",
              {
                class:
                  "text-primary underline cursor-pointer hover:text-primary-600",
              },
              val.slice(0, 40) + "...",
            ),
          content: () =>
            h(
              "div",
              {
                class: "whitespace-pre-wrap max-w-md p-3",
              },
              val,
            ),
        },
      );
    },
  },
  {
    accessorKey: "created_at",
    header: "Date",
    cell: ({ row }) =>
      new Date(row.original.created_at).toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      }),
  },
];

function formatJson(value: any): string {
  if (!value) return "-";
  try {
    const obj = typeof value === "string" ? JSON.parse(value) : value;
    return Object.entries(obj)
      .map(([k, v]) => `${k}: ${v}`)
      .join(", ");
  } catch {
    return String(value);
  }
}
</script>

<template>
  <div class="p-6 space-y-5">
    <div>
      <h1 class="text-2xl font-bold">Audit Logs</h1>
      <p class="text-slate-500">System change tracking and history.</p>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading" />

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
