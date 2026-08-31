<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import type { SelectItem } from "@nuxt/ui";
import { onMounted, ref, computed, watch, h } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { useTablePagination } from "~/composables/useTablePagination";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const UBadge = resolveComponent("UBadge");

const items = ref<any[]>([]);
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
      `/v1/inventory/items?page=${page}&per_page=${pageSize}`,
      { parseJson: true },
    );
    return { data: result.data, total: result.meta.total };
  },
});

type Inventory = {
  id: number;
  item_name: string;
  category: string;
  category_type: string;
  quantity: number;
  status: keyof typeof statusColor;
};

const statusColor = {
  available: "success" as const,
  low_stock: "warning" as const,
  damaged: "error" as const,
};

const columns: TableColumn<Inventory>[] = [
  {
    accessorKey: "id",
    header: "#",
    cell: ({ row }) => `#${row.getValue("id")}`,
  },
  {
    accessorKey: "item_name",
    header: "Name",
  },
  {
    accessorKey: "category_type",
    header: "Type",
    cell: ({ row }) => {
      const type = row.getValue("category_type");
      const color =
        type === "equipment"
          ? "primary"
          : type === "materials"
            ? "success"
            : "warning";
      return h(
        UBadge,
        { class: "capitalize", variant: "subtle", color },
        () => type,
      );
    },
  },
  {
    accessorKey: "category",
    header: "Category",
  },
  {
    accessorKey: "quantity",
    header: "Quantity",
  },
  {
    accessorKey: "status",
    header: "Status",
    cell: ({ row }) => {
      const value = row.getValue("status") as string;
      const color = statusColor[value];
      const label = statusLabel.value[value] ?? value;

      return h(UBadge, { class: "capitalize", color }, () => label);
    },
  },
  { accessorKey: "action", header: "Action" },
];

const status = ref<SelectItem[]>([
  { label: "Available", value: "available" },
  { label: "Low Stock", value: "low_stock" },
  { label: "Damaged", value: "damaged" },
])

// build a lookup: value -> label
const statusLabel = computed(() =>
  Object.fromEntries(status.value.map(s => [s.value, s.label]))
)

// const statusColor: Record<string, BadgeProps['color']> = {
//   available: 'success',
//   low_stock: 'warning',
//   damaged: 'error',
// }

async function remove(row: any) {
  if (!confirm("Delete this item?")) return;
  await apiFetch(`/v1/inventory/items/${row.original.id}`, {
    method: "DELETE",
  });
  data.value = data.value.filter((i) => i.id !== row.original.id);
}
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-5">
      <h1 class="text-2xl font-bold">Inventory</h1>

      <UButton
        v-if="can('create inventory')"
        to="/inventory/inventory-list/create"
      >
        Add Item
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <div class="flex gap-2">
          <UButton
            size="xs"
            :to="`/inventory/inventory-list/${row.original.id}`"
            icon="i-lucide-eye"
          ></UButton>
          <UButton
            v-if="can('edit inventory')"
            size="xs"
            color="secondary"
            :to="`/inventory/inventory-list/edit/${row.original.id}`"
            icon="i-lucide-edit"
          ></UButton>
          <UButton
            v-if="can('delete inventory')"
            size="xs"
            color="error"
            @click="remove(row)"
            icon="i-lucide-trash"
          ></UButton>
        </div>
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
