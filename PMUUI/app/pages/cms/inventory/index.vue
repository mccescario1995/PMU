<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, computed, watch, h } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { ref } from "vue";
import { useTablePagination } from "~/composables/useTablePagination";
import { useRouteRefresh } from "~/composables/useRouteRefresh";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const { registerRefresh } = useRouteRefresh()

const UBadge = resolveComponent("UBadge");

const statusColor = {
  available: "success" as const,
  low_stock: "error" as const,
  damaged: "error" as const,
  inactive: "neutral" as const,
};

const items = ref<any[]>([]);
const priceModal = ref(false);
const priceForm = ref({ id: 0, item_name: '', price: 0 });
const viewModal = ref(false);
const viewItem = ref<any>(null);
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
  refresh,
} = useTablePagination(null, 10, {
  fetchData: async (page, pageSize) => {
    const result = await apiFetch(`/v1/inventory/items?page=${page}&per_page=${pageSize}`, { parseJson: true })
    return { data: result.data, total: result.meta.total }
  },
})

onMounted(() => {
  registerRefresh(refresh)
})

type Inventory = {
  id: number;
  item_name: string;
  category: string;
  category_type: string;
  quantity: number;
  unit: string;
  minimum_stock: number;
  reorder_quantity: number;
  average_daily_usage: number;
  price: number;
  status: string;
  stock_status: string;
  days_remaining: number | null;
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
    accessorKey: "minimum_stock",
    header: "Min Stock",
  },
  {
    accessorKey: "reorder_quantity",
    header: "Restock Qty",
  },
  {
    accessorKey: "average_daily_usage",
    header: "Avg Daily Usage",
  },
  {
    accessorKey: "price",
    header: "Price",
    cell: ({ row }) => {
      const value = row.getValue("price") as number;
      return value !== null && value !== undefined ? `₱${value.toFixed(2)}` : "₱0.00";
    },
  },
  {
    accessorKey: "stock_status",
    header: "Stock Status",
    cell: ({ row }) => {
      const value = row.getValue("stock_status") as string;
      const color = statusColor[value as keyof typeof statusColor] ?? "neutral";
      return h(UBadge, { class: "capitalize", variant: "subtle", color }, () =>
        value.replace(/_/g, " "),
      );
    },
  },
  {
    accessorKey: "days_remaining",
    header: "Days Remaining",
    cell: ({ row }) => {
      const value = row.getValue("days_remaining") as number | null;
      return value !== null && value !== undefined ? value.toFixed(1) : "N/A";
    },
  },
  { accessorKey: "action", header: "Action" },
];

async function remove(row: any) {
  if (!confirm("Delete this item?")) return;
  await apiFetch(`/v1/inventory/items/${row.original.id}`, {
    method: "DELETE",
  });
  data.value = data.value.filter((i: any) => i.id !== row.original.id);
}

function openPriceModal(row: any) {
  priceForm.value = {
    id: row.original.id,
    item_name: row.original.item_name,
    price: row.original.price ?? 0,
  };
  priceModal.value = true;
}

async function savePrice() {
  await apiFetch(`/v1/inventory/items/${priceForm.value.id}/price`, {
    method: "PUT",
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ price: priceForm.value.price }),
    parseJson: true,
    throwOnError: true,
  });
  priceModal.value = false;
  // Refresh the table data
  const result = await apiFetch(`/v1/inventory/items?page=${page.value}&per_page=${pageSizeNumber.value}`, { parseJson: true });
  data.value = result.data;
}

function openView(row: any) {
  viewItem.value = row.original;
  viewModal.value = true;
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

    <UTable
      :data="data"
      :columns="columns"
      :loading="loading"
    >
      <template #action-cell="{ row }">
        <UButton
          size="xs"
          @click.stop="openView(row)"
          icon="i-lucide-eye"
        ></UButton>
        <!-- <UButton
          v-if="can('edit inventory')"
          size="xs"
          :to="`/inventory/inventory-list/edit/${row.original.id}`"
          icon="i-lucide-edit"
        ></UButton> -->
        <UButton
          size="xs"
          v-if="can('view inventory')"
          @click.stop="openPriceModal(row)"
          icon="i-lucide-philippine-peso"
        ></UButton>
        <UButton
          v-if="can('delete inventory')"
          size="xs"
          color="error"
          variant="ghost"
          @click="remove(row)"
          icon="i-lucide-trash"
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

  <UModal v-model:open="priceModal" title="Update Price">
    <template #body>
      <div class="space-y-4">
        <p class="text-sm text-slate-500">Item: <strong>{{ priceForm.item_name }}</strong></p>
        <UFormField label="Price">
          <UInput
            type="number"
            step="0.01"
            min="0"
            v-model="priceForm.price"
            placeholder="Enter price"
          />
        </UFormField>
      </div>
    </template>
    <template #footer>
      <UButton variant="ghost" @click="priceModal = false">Cancel</UButton>
      <UButton @click="savePrice">Save Price</UButton>
    </template>
  </UModal>

  <UModal v-model:open="viewModal" title="View Inventory Item">
    <template #body>
      <div class="space-y-4" v-if="viewItem">
        <div class="grid grid-cols-2 gap-4">
          <UFormField label="Item Name">
            <UInput :value="viewItem.item_name" disabled />
          </UFormField>
          <UFormField label="Category Type">
            <UInput :value="viewItem.category_type" disabled />
          </UFormField>
          <UFormField label="Category">
            <UInput :value="viewItem.category" disabled />
          </UFormField>
          <UFormField label="Quantity">
            <UInput type="number" :value="viewItem.quantity" disabled />
          </UFormField>
          <UFormField label="Unit">
            <UInput :value="viewItem.unit" disabled />
          </UFormField>
          <UFormField label="Minimum Stock">
            <UInput type="number" :value="viewItem.minimum_stock" disabled />
          </UFormField>
          <UFormField label="Restock Qty">
            <UInput type="number" :value="viewItem.reorder_quantity" disabled />
          </UFormField>
          <UFormField label="Avg Daily Usage">
            <UInput type="number" step="0.01" :value="viewItem.average_daily_usage" disabled />
          </UFormField>
          <UFormField label="Price">
            <UInput :value="viewItem.price !== null && viewItem.price !== undefined ? `₱${viewItem.price.toFixed(2)}` : '₱0.00'" disabled />
          </UFormField>
          <UFormField label="Status">
            <UInput :value="viewItem.status" disabled />
          </UFormField>
          <UFormField label="Stock Status">
            <UInput :value="viewItem.stock_status?.replace(/_/g, ' ')" disabled />
          </UFormField>
          <UFormField label="Days Remaining">
            <UInput :value="viewItem.days_remaining !== null && viewItem.days_remaining !== undefined ? viewItem.days_remaining.toFixed(1) : 'N/A'" disabled />
          </UFormField>
        </div>
      </div>
    </template>
    <template #footer>
      <UButton variant="ghost" @click="viewModal = false">Close</UButton>
    </template>
  </UModal>
</template>
