<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { h } from "vue";
import { useTablePagination } from "~/composables/useTablePagination";
import { UPopover, UTooltip } from "#components";

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

function formatChanges(value: any): string {
  if (!value) return "No changes recorded";
  try {
    const obj = typeof value === "string" ? JSON.parse(value) : value;
    return Object.entries(obj)
      .map(([field, newValue]) => `${formatFieldName(field)}: ${newValue}`)
      .join("; ");
  } catch {
    return String(value);
  }
}

function formatFieldName(field: string): string {
  return field
    .replace(/_/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function getActionLabel(action: string): string {
  const actions: Record<string, string> = {
    created: "Created",
    updated: "Updated",
    deleted: "Deleted",
    restored: "Restored",
  };
  return actions[action] ?? action;
}

const columns: TableColumn<any>[] = [
  // {
  //   accessorKey: "id",
  //   header: "Log #",
  //   cell: ({ row }) => `#${row.getValue("id")}`,
  // },
  {
    accessorKey: "user",
    header: "Performed By",
    cell: ({ row }) => {
      const user = row.original.user;
      if (!user) return "System";
      return `${user.name} (${user.email})`;
    },
  },
  {
    accessorKey: "action",
    header: "Action Taken",
    cell: ({ row }) => getActionLabel(row.getValue("action")),
  },
  {
    accessorKey: "table_name",
    header: "Affected Area",
    cell: ({ row }) => formatFieldName(row.getValue("table_name")),
  },
  // {
  //   accessorKey: "record_id",
  //   header: "Record #",
  //   cell: ({ row }) => row.getValue("record_id") ?? "N/A",
  // },
  // {
  //   accessorKey: "old_values",
  //   header: "Previous Values",
  //   cell: ({ row }) => {
  //     const val = formatChanges(row.original.old_values);
  //     if (val === "No changes recorded" || val.length <= 50) return val;

  //     return h(
  //       UPopover,
  //       {},
  //       {
  //         default: () =>
  //           h(
  //             "span",
  //             {
  //               class:
  //                 "text-primary underline cursor-pointer hover:text-primary-600",
  //             },
  //             val.slice(0, 50) + "...",
  //           ),
  //         content: () =>
  //           h(
  //             "div",
  //             {
  //               class: "whitespace-pre-wrap max-w-md p-3 text-sm",
  //             },
  //             val,
  //           ),
  //       },
  //     );
  //   },
  // },
  // {
  //   accessorKey: "new_values",
  //   header: "New Values",
  //   cell: ({ row }) => {
  //     const val = formatChanges(row.original.new_values);
  //     if (val === "No changes recorded" || val.length <= 50) return val;

  //     return h(
  //       UPopover,
  //       {},
  //       {
  //         default: () =>
  //           h(
  //             "span",
  //             {
  //               class:
  //                 "text-primary underline cursor-pointer hover:text-primary-600",
  //             },
  //             val.slice(0, 50) + "...",
  //           ),
  //         content: () =>
  //           h(
  //             "div",
  //             {
  //               class: "whitespace-pre-wrap max-w-md p-3 text-sm",
  //             },
  //             val,
  //           ),
  //       },
  //     );
  //   },
  // },
  {
    accessorKey: "created_at",
    header: "Date & Time",
    cell: ({ row }) => formatDate(row.original.created_at),
  },
];
</script>

<template>
  <div class="p-6 space-y-5">
    <div>
      <h1 class="text-2xl font-bold">Audit Logs</h1>
      <p class="text-slate-500 mt-1">
        View a history of all changes made in the system. Each entry shows who made a change, what was changed, and when it happened.
      </p>
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

    <div class="pt-4 border-t border-slate-200 text-sm text-slate-500 space-y-1">
      <p><strong>Tip:</strong> Click on "Previous Values" or "New Values" to see full details when truncated.</p>
      <p><strong>Actions:</strong> Created = New record added | Updated = Existing record modified | Deleted = Record removed</p>
    </div>
  </div>
</template>
