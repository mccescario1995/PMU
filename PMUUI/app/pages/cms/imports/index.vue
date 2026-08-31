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
    const result = await apiFetch(`/v1/imports?page=${page}&per_page=${pageSize}`, { parseJson: true })
    return { data: result.data, total: result.meta.total }
  },
})

const showDetail = ref(false)
const selectedImport = ref<any>(null)

const statusColor: Record<string, string> = {
  pending: "warning",
  processing: "info",
  completed: "success",
  failed: "error",
  partial: "warning",
}

type Import = {
  id: number
  entity_type: string
  filename: string
  total_rows: number
  imported_rows: number
  skipped_rows: number
  errors: string[] | null
  status: string
  created_at: string
}

const columns: TableColumn<Import>[] = [
  { accessorKey: "id", header: "#" },
  { accessorKey: "entity_type", header: "Entity" },
  { accessorKey: "filename", header: "File" },
  {
    accessorKey: "status",
    header: "Status",
    cell: ({ row }) => {
      const s = row.getValue("status")
      return h("span", { class: "capitalize" }, () => s)
    },
  },
  {
    accessorKey: "total_rows",
    header: "Total Rows",
    meta: { class: { th: "text-right", td: "text-right" } },
  },
  {
    accessorKey: "imported_rows",
    header: "Imported",
    meta: { class: { th: "text-right", td: "text-right" } },
  },
  {
    accessorKey: "skipped_rows",
    header: "Skipped",
    meta: { class: { th: "text-right", td: "text-right" } },
  },
  {
    accessorKey: "created_at",
    header: "Imported At",
    cell: ({ row }) =>
      new Date(row.getValue("created_at")).toLocaleString("en-US"),
  },
]

function viewDetails(row: any) {
  selectedImport.value = row
  showDetail.value = true
}
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Import Logs</h1>
      <UButton
        v-if="can('create imports')"
        icon="i-lucide-upload"
        @click="navigateTo('/import')"
      >
        Import Data
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton
          size="xs"
          variant="ghost"
          @click="viewDetails(row.original)"
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
        <UInput v-model="goToPageInput" type="number" :min="1" :max="totalPages" class="w-16" @keyup.enter="handleGoToPage" />
        <UButton size="sm" @click="handleGoToPage">Go</UButton>
      </div>
      <UPagination :total="totalItems" v-model:page="page" :items-per-page="pageSizeNumber" />
    </div>

    <UModal v-model:open="showDetail">
        <template #header> Import Details </template>
        <template #body v-if="selectedImport" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <span class="text-sm text-slate-500">File</span>
              <p class="font-mono text-sm">{{ selectedImport.filename }}</p>
            </div>
            <div>
              <span class="text-sm text-slate-500">Entity Type</span>
              <p class="capitalize">{{ selectedImport.entity_type?.replace('_', ' ') }}</p>
            </div>
            <div>
              <span class="text-sm text-slate-500">Status</span>
              <p class="capitalize">{{ selectedImport.status }}</p>
            </div>
            <div>
              <span class="text-sm text-slate-500">Imported At</span>
              <p class="text-sm">{{ new Date(selectedImport.created_at).toLocaleString("en-US") }}</p>
            </div>
            <div>
              <span class="text-sm text-slate-500">Total Rows</span>
              <p class="text-right font-mono">{{ selectedImport.total_rows }}</p>
            </div>
            <div>
              <span class="text-sm text-slate-500">Imported</span>
              <p class="text-right font-mono text-green-600">{{ selectedImport.imported_rows }}</p>
            </div>
            <div>
              <span class="text-sm text-slate-500">Skipped</span>
              <p class="text-right font-mono text-orange-600">{{ selectedImport.skipped_rows }}</p>
            </div>
          </div>

          <div v-if="selectedImport.errors && selectedImport.errors.length" class="space-y-2">
            <span class="text-sm text-slate-500">Errors</span>
            <div class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-3 max-h-64 overflow-y-auto">
              <ul class="space-y-1 text-sm text-red-800 dark:text-red-200">
                <li v-for="(error, index) in selectedImport.errors" :key="index" class="font-mono">
                  {{ error }}
                </li>
              </ul>
            </div>
          </div>
        </template>
        <template #footer>
          <div class="flex justify-end">
            <UButton variant="ghost" @click="showDetail = false">Close</UButton>
          </div>
        </template>
    </UModal>
  </div>
</template>
