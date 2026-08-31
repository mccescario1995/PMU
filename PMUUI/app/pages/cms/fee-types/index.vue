<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, h, computed, watch } from "vue";
import { ref } from "vue";
import type { SelectItem } from "@nuxt/ui";
import { usePermissions } from "~/composables/usePermissions";
import { useTablePagination } from "~/composables/useTablePagination";
const toast = useToast();


definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const UBadge = resolveComponent("UBadge");
const loading = ref(false);


const {
  page,
  pageSize,
  pageSizeNumber,
  goToPageInput,
  totalPages,
  handleGoToPage,
  data,
  totalItems,
} = useTablePagination(null, 10, {
  fetchData: async (page, pageSize) => {
    const result = await apiFetch(
      `/v1/fee-types?page=${page}&per_page=${pageSize}`,
      { parseJson: true },
    );
    return { data: result.data, total: result.meta.total };
  },
});

const showForm = ref(false);
const editing = ref<any>(null);

const form = reactive({
  fee_name: "",
  base_rate: 0,
  unit: "kg",
});

function openCreate() {
  editing.value = null;
  form.fee_name = "";
  form.base_rate = 0;
  form.unit = "kg";
  showForm.value = true;
}

function openEdit(row: any) {
  editing.value = row;
  form.fee_name = row.fee_name;
  form.base_rate = row.base_rate;
  form.unit = row.unit;
  showForm.value = true;
}

async function save() {
  try {
    loading.value = true;
    if (editing.value) {
      await apiFetch(`/v1/fee-types/${editing.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });
    } else {
      await apiFetch("/v1/fee-types", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });
    }
    showForm.value = false;
    toast.add({
      title: "Fee updated",
      color: "success",
    });
    page.value = 1;
  } catch (e: any) {
    alert(e.message ?? "Failed to save fee type");
  } finally {
    loading.value = false;
  }
}

async function remove(row: any) {
  if (!confirm("Delete this fee type?")) return;
  await apiFetch(`/v1/fee-types/${row.id}`, { method: "DELETE" });
  data.value = data.value.filter((f: any) => f.id !== row.id);
  totalItems.value = Math.max(0, totalItems.value - 1);
}

type FeeType = {
  id: number;
  fee_name: string;
  base_rate: number;
  unit: string;
};

const columns: TableColumn<FeeType>[] = [
  { accessorKey: "id", header: "#" },
  { accessorKey: "fee_name", header: "Fee Name" },
  {
    accessorKey: "base_rate",
    header: "Base Rate",
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
    cell: ({ row }) => {
      const v = Number(row.getValue("base_rate"));
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(v);
    },
  },
  { accessorKey: "unit", header: "Unit" },
  { accessorKey: "action", header: "Action" },
];

const feeUnits = ref<SelectItem[]>([
  {
    label: "Kilogram (kg)",
    value: "kg",
  },
  {
    label: "Gram (g)",
    value: "gram",
  },
  {
    label: "Metric Ton (ton)",
    value: "ton",
  },
  {
    label: "Trip",
    value: "trip",
  },
  {
    label: "Day",
    value: "day",
  },
  {
    label: "Hour",
    value: "hour",
  },
  {
    label: "Month",
    value: "month",
  },
  {
    label: "Year",
    value: "year",
  },
  {
    label: "Head",
    value: "head",
  },
  {
    label: "Tub",
    value: "tub",
  },
  {
    label: "Box",
    value: "box",
  },
  {
    label: "Sack",
    value: "sack",
  },
  {
    label: "Block",
    value: "block",
  },
  {
    label: "Liter (L)",
    value: "liter",
  },
  {
    label: "Cubic Meter (m³)",
    value: "cubic_meter",
  },
  {
    label: "Gross Registered Tonnage (GRT)",
    value: "grt",
  },
  {
    label: "Item",
    value: "item",
  },
  {
    label: "Unit",
    value: "unit",
  },
  {
    label: "Stall",
    value: "stall",
  },
  {
    label: "Square Meter (m²)",
    value: "square_meter",
  },
  {
    label: "Person",
    value: "person",
  },
]);
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Fee Types</h1>
      <UButton
        v-if="can('create fee types')"
        icon="i-lucide-plus"
        @click="openCreate"
      >
        Add Fee Type
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton
          v-if="can('edit fee types')"
          size="xs"
          @click="openEdit(row.original)"
          icon="i-lucide-edit"
        ></UButton>
        <UButton
          v-if="can('delete fee types')"
          size="xs"
          color="error"
          variant="ghost"
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

    <UModal v-model:open="showForm">
      <template #header>
        {{ editing ? "Edit Fee Type" : "New Fee Type" }}
      </template>
      <template #body class="space-y-4">
        <UFormField label="Fee Name" class="mb-3">
          <UInput v-model="form.fee_name" />
        </UFormField>
        <div class="flex">
          <UFormField label="Base Rate" class="me-3">
            <UInput v-model="form.base_rate" type="number" step="0.01" />
          </UFormField>
          <UFormField>/</UFormField>
          <UFormField label="Unit" class="w-full">
            <USelect
              class="w-full"
              v-model="form.unit"
              :items="feeUnits"
            />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" @click="showForm = false">Cancel</UButton>
          <UButton @click="save">Save</UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
