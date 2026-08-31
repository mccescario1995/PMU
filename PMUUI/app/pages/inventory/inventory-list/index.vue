<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import type { SelectItem } from "@nuxt/ui";
import { onMounted, ref, computed, watch, h } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { useTablePagination } from "~/composables/useTablePagination";
import { useToast } from "#imports";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const toast = useToast();

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
  status: string;
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
      const color = statusColor[value as keyof typeof statusColor] ?? "neutral";
      const label = value.replace(/_/g, " ");
      return h(UBadge, { class: "capitalize", color }, () => label);
    },
  },
  { accessorKey: "action", header: "Action" },
];

const categoryTypeOptions: SelectItem[] = [
  { label: "Equipment", value: "equipment" },
  { label: "Materials", value: "materials" },
  { label: "Supplies", value: "supplies" },
];

const statusOptions: SelectItem[] = [
  { label: "Available", value: "available" },
  { label: "Low Stock", value: "low_stock" },
  { label: "Damaged", value: "damaged" },
];

const showModal = ref(false);
const modalMode = ref<"create" | "edit" | "view">("create");
const saving = ref(false);
const editingItem = ref<any>(null);

const form = reactive({
  item_name: "",
  category: "",
  category_type: "supplies",
  quantity: 0,
  unit: "pcs",
  status: "available",
});

function openCreate() {
  modalMode.value = "create";
  editingItem.value = null;
  form.item_name = "";
  form.category = "";
  form.category_type = "supplies";
  form.quantity = 0;
  form.unit = "pcs";
  form.status = "available";
  showModal.value = true;
}

function openView(row: any) {
  modalMode.value = "view";
  editingItem.value = row;
  form.item_name = row.item_name;
  form.category = row.category;
  form.category_type = row.category_type ?? "supplies";
  form.quantity = row.quantity;
  form.unit = row.unit ?? "pcs";
  form.status = row.status;
  showModal.value = true;
}

function openEdit(row: any) {
  modalMode.value = "edit";
  editingItem.value = row;
  form.item_name = row.item_name;
  form.category = row.category;
  form.category_type = row.category_type ?? "supplies";
  form.quantity = row.quantity;
  form.unit = row.unit ?? "pcs";
  form.status = row.status;
  showModal.value = true;
}

async function save() {
  saving.value = true;
  try {
    if (modalMode.value === "edit" && editingItem.value) {
      await apiFetch(`/v1/inventory/items/${editingItem.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });
      toast.add({ title: "Inventory item updated", color: "success" });
    } else {
      await apiFetch("/v1/inventory/items", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });
      toast.add({ title: "Inventory item created", color: "success" });
    }
    showModal.value = false;
  } catch (e: any) {
    toast.add({
      title: modalMode.value === "edit" ? "Failed to update item" : "Failed to create item",
      description: e.message ?? "Please try again.",
      color: "error",
    });
  } finally {
    saving.value = false;
  }
}

async function remove(row: any) {
  if (!confirm("Delete this inventory item?")) return;
  await apiFetch(`/v1/inventory/items/${row.id}`, { method: "DELETE" });
  data.value = data.value.filter((i: any) => i.id !== row.id);
  totalItems.value = Math.max(0, totalItems.value - 1);
  toast.add({ title: "Inventory item deleted", color: "success" });
}
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-5">
      <h1 class="text-2xl font-bold">Inventory</h1>

      <UButton
        v-if="can('create inventory')"
        icon="i-lucide-plus"
        @click="openCreate"
      >
        Add Item
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton
          class="me-2"
          v-if="can('view inventory')"
          size="xs"
          color="info"
          variant="ghost"
          @click="openView(row.original)"
          icon="i-lucide-eye"
        ></UButton>
        <UButton
          class="me-2"
          v-if="can('edit inventory')"
          size="xs"
          color="secondary"
          @click="openEdit(row.original)"
          icon="i-lucide-edit"
        ></UButton>
        <UButton
          v-if="can('delete inventory')"
          size="xs"
          color="error"
          @click="remove(row.original)"
          icon="i-lucide-trash"
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

    <UModal v-model:open="showModal">
        <template #header>
          {{
            modalMode === "view"
              ? "View Inventory Item"
              : modalMode === "edit"
                ? "Edit Inventory Item"
                : "New Inventory Item"
          }}
        </template>
        <template #body>
          <div class="space-y-4">
            <UFormField label="Item Name" class="mb-3">
              <UInput v-model="form.item_name" :disabled="modalMode === 'view'" class="w-full" />
            </UFormField>

            <UFormField label="Category" class="mb-3">
              <UInput v-model="form.category" :disabled="modalMode === 'view'" class="w-full" />
            </UFormField>

            <UFormField label="Category Type" class="mb-3">
              <USelect
                v-model="form.category_type"
                :items="categoryTypeOptions"
                class="w-full"
                :disabled="modalMode === 'view'"
              />
            </UFormField>

            <div class="flex">
              <UFormField label="Quantity" class="mb-3 w-full mr-3">
                <UInput type="number" v-model="form.quantity" :disabled="modalMode === 'view'" class="w-full" />
              </UFormField>

              <UFormField label="Unit" class="mb-3 w-full">
                <UInput v-model="form.unit" :disabled="modalMode === 'view'" class="w-full" />
              </UFormField>
            </div>

            <UFormField label="Status" class="mb-3">
              <USelect
                v-model="form.status"
                :items="statusOptions"
                class="w-full"
                :disabled="modalMode === 'view'"
              />
            </UFormField>
          </div>
        </template>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" @click="showModal = false">Close</UButton>
            <UButton v-if="modalMode !== 'view'" @click="save" :loading="saving">
              Save
            </UButton>
          </div>
        </template>
    </UModal>
  </div>
</template>
