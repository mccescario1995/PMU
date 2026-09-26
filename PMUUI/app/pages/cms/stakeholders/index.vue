<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, computed, watch, h } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { ref } from "vue";
import { useTablePagination } from "~/composables/useTablePagination";
import { useToast } from "#imports";
import * as XLSX from "xlsx";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const toast = useToast();

const UBadge = resolveComponent("UBadge");

const stakeholders = ref<any[]>([]);
const searchQuery = ref("");
const {
  page,
  pageSize, pageSizeNumber,
  goToPageInput,
  totalPages,
  handleGoToPage,
  data,
  totalItems,
  loading,
  refresh,
} = useTablePagination(null, 10, {
  fetchData: async (page, pageSize) => {
    const params = new URLSearchParams({
      page: String(page),
      per_page: String(pageSize),
    });
    if (searchQuery.value) {
      params.set("search", searchQuery.value);
    }
    const result = await apiFetch(`/v1/stakeholders?${params.toString()}`, { parseJson: true })
    return { data: result.data, total: result.meta.total }
  },
});

watch(searchQuery, () => {
  page.value = 1;
  refresh();
});

type Stakeholder = {
  id: number;
  name: string;
  official_receipt: string;
};

const columns: TableColumn<Stakeholder>[] = [
  {
    accessorKey: "id",
    header: "#",
    cell: ({ row }) => `#${row.getValue("id")}`,
  },
  {
    accessorKey: "name",
    header: "Name",
  },
  {
    accessorKey: "official_receipt",
    header: "Official Receipt",
  },
  {
    accessorKey: "stakeholder_type_id",
    header: "Type",
    cell: ({ row }) => {
      const typeName = row.original.stakeholder_type?.name ?? "Unknown";
      return h(
        UBadge,
        { class: "capitalize", variant: "subtle", color: "primary" },
        () => typeName,
      );
    },
  },
  {
    accessorKey: "status",
    header: "Status",
    cell: ({ row }) => {
      const status = row.getValue("status");
      const color = status === "active" ? "success" : "error";
      return h(
        UBadge,
        { variant: "subtle", color },
        () => status,
      );
    },
  },
  {
    accessorKey: "action",
    header: "Action",
  },
];

function exportToExcel() {
  const exportData = data.value.map((item: any) => ({
    ID: item.id,
    Name: item.name,
    "Official Receipt": item.official_receipt,
    Type: item.stakeholder_type?.name ?? "Unknown",
    Status: item.status,
    Created: item.created_at,
  }));

  const ws = XLSX.utils.json_to_sheet(exportData);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Stakeholders");
  XLSX.writeFile(wb, `stakeholders_${new Date().toISOString().split('T')[0]}.xlsx`);
}
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between items-center gap-4 flex-wrap">
      <div class="flex flex-col">
        <h1 class="text-2xl font-bold">Stakeholders</h1>
        <UInput
          v-model="searchQuery"
          placeholder="Search stakeholders..."
          icon="i-lucide-search"
          class="max-w-xs"
          @keyup.enter="refresh"
        />
      </div>

      <div class="flex items-center gap-2">
        <UButton
          v-if="can('create stakeholders')"
          to="/cms/stakeholders/create"
          icon="i-lucide-plus"
        >
          Add Stakeholder
        </UButton>
        <UButton
          icon="i-lucide-download"
          @click="exportToExcel"
          variant="outline"
        >
          Export
        </UButton>
      </div>
    </div>

    <UTable
      :data="data"
      :columns="columns"
      :loading="loading"
    >
      <template #action-cell="{ row }">
        <UButton
          size="xs"
          :to="`/stakeholders/${row.original.id}`"
          icon="i-lucide-eye"
        ></UButton>
      </template>
    </UTable>

    <div class="flex items-center justify-between mt-4 flex-wrap gap-4">
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