<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, computed, watch } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { ref } from "vue";
import { useTablePagination } from "~/composables/useTablePagination";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const UBadge = resolveComponent("UBadge");

const typeColor = {
  buyer: "success" as const,
  broker: "warning" as const,
  renter: "neutral" as const,
};

const stakeholders = ref<any[]>([]);
const {
  page,
  pageSize, pageSizeNumber,
  goToPageInput,
  totalPages,
  handleGoToPage,
  data,
  totalItems,
  loading,
} = useTablePagination(null, 10, {
  fetchData: async (page, pageSize) => {
    const result = await apiFetch(`/v1/stakeholders?page=${page}&per_page=${pageSize}`, { parseJson: true })
    return { data: result.data, total: result.meta.total }
  },
});

type Stakeholder = {
  id: number;
  name: string;
  type: string;
  contact_no: string;
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
    accessorKey: "type",
    header: "Type",
    cell: ({ row }) => {
      const rawType = row.getValue("type");
      const typeName = row.original.stakeholder_type?.name ?? rawType;
      const color = typeColor[rawType as keyof typeof typeColor];

      return h(
        UBadge,
        { class: "capitalize", variant: "subtle", color },
        () => typeName,
      );
    },
  },
  {
    accessorKey: "contact_no",
    header: "Contact",
  },
  {
    accessorKey: "action",
    header: "Action",
  },
];
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Stakeholders</h1>

      <UButton
        v-if="can('create stakeholders')"
        to="/stakeholders/create"
        icon="i-lucide-plus"
      >
        Add Stakeholder
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
          :to="`/stakeholders/${row.original.id}`"
          icon="i-lucide-eye"
        ></UButton>
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
