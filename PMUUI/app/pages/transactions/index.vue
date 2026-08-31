<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, ref, computed, watch, h } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { useTablePagination } from "~/composables/useTablePagination";
import { useToast } from "#imports";
import type { SelectItem } from "@nuxt/ui";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const toast = useToast();

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
    const result = await apiFetch(
      `/v1/transactions?page=${page}&per_page=${pageSize}`,
      { parseJson: true },
    );
    return { data: result.data, total: result.meta.total };
  },
});

const showModal = ref(false);
const modalMode = ref<"create" | "edit" | "view">("create");
const saving = ref(false);
const editingTransaction = ref<any>(null);

const stakeholders = ref<any[]>([]);
const feeTypes = ref<any[]>([]);
const stakeholdersLoaded = ref(false);
const feeTypesLoaded = ref(false);

const form = reactive({
  stakeholder_id: null as number | null,
  items: [
    {
      fee_type_id: null as number | null,
      quantity: 1,
      unit_price: 0,
    },
  ],
  transaction_date: "",
  status: "pending",
});

const statusOptions: SelectItem[] = [
  { label: "Pending", value: "pending" },
  { label: "Completed", value: "completed" },
  { label: "Cancelled", value: "cancelled" },
];

async function loadStakeholders() {
  if (stakeholdersLoaded.value) return;
  stakeholders.value = (
    (await apiFetch("/v1/stakeholders", { parseJson: true })) as any
  ).data;
  stakeholdersLoaded.value = true;
}

async function loadFeeTypes() {
  if (feeTypesLoaded.value) return;
  feeTypes.value = (
    (await apiFetch("/v1/fee-types", { parseJson: true })) as any
  ).data;
  feeTypesLoaded.value = true;
}

function openCreate() {
  modalMode.value = "create";
  editingTransaction.value = null;
  form.stakeholder_id = null;
  form.items = [
    {
      fee_type_id: null,
      quantity: 1,
      unit_price: 0,
    },
  ];
  form.transaction_date = new Date().toISOString().slice(0, 10);
  form.status = "pending";
  showModal.value = true;
}

function openView(row: any) {
  modalMode.value = "view";
  editingTransaction.value = row;
  form.stakeholder_id = row.stakeholder_id;
  form.items = (row.items ?? []).map((item: any) => ({
    fee_type_id: item.fee_type_id,
    quantity: item.quantity,
    unit_price: item.unit_price,
  }));
  form.transaction_date = (row.transaction_date ?? "").toString().slice(0, 10);
  form.status = row.status;
  showModal.value = true;
}

function openEdit(row: any) {
  modalMode.value = "edit";
  editingTransaction.value = row;
  form.stakeholder_id = row.stakeholder_id;
  form.items = (row.items ?? []).map((item: any) => ({
    fee_type_id: item.fee_type_id,
    quantity: item.quantity,
    unit_price: item.unit_price,
  }));
  form.transaction_date = (row.transaction_date ?? "").toString().slice(0, 10);
  form.status = row.status;
  showModal.value = true;
}

function addItem() {
  form.items.push({
    fee_type_id: null,
    quantity: 1,
    unit_price: 0,
  });
}

function removeItem(index: number) {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  }
}

watch(
  () => form.items,
  (newItems) => {
    newItems.forEach((item) => {
      const feeType = feeTypes.value.find((f) => f.id === item.fee_type_id);
      if (feeType && item.unit_price === 0) {
        item.unit_price = feeType.base_rate;
      }
    });
  },
  { deep: true },
);

function calculateSubtotal(item: any) {
  return Number(item.quantity || 0) * Number(item.unit_price || 0);
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "PHP",
  }).format(value);
}

function calculateTotal() {
  return form.items.reduce((sum, item) => sum + calculateSubtotal(item), 0);
}

async function save() {
  saving.value = true;
  try {
    const items = form.items.map((item) => ({
      fee_type_id: item.fee_type_id,
      quantity: item.quantity,
      unit_price: item.unit_price,
      subtotal: calculateSubtotal(item),
    }));

    const payload = {
      stakeholder_id: form.stakeholder_id,
      transaction_date: form.transaction_date,
      status: form.status,
      remarks: "",
      total_amount: calculateTotal(),
      items: items,
    };

    if (modalMode.value === "edit" && editingTransaction.value) {
      await apiFetch(`/v1/transactions/${editingTransaction.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        parseJson: true,
      });
      toast.add({ title: "Transaction updated", color: "success" });
    } else {
      await apiFetch("/v1/transactions", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        parseJson: true,
      });
      toast.add({ title: "Transaction created", color: "success" });
    }

    showModal.value = false;
    refresh();
  } catch (e: any) {
    toast.add({
      title: modalMode.value === "edit" ? "Failed to update transaction" : "Failed to create transaction",
      description: e.message ?? "Please try again.",
      color: "error",
    });
  } finally {
    saving.value = false;
  }
}

async function remove(row: any) {
  if (!confirm("Delete this transaction?")) return;
  await apiFetch(`/v1/transactions/${row.id}`, { method: "DELETE" });
  data.value = data.value.filter((t: any) => t.id !== row.id);
  totalItems.value = Math.max(0, totalItems.value - 1);
  toast.add({ title: "Transaction deleted", color: "success" });
}

function formatFeeTypes(items: any[]): string {
  if (!items || items.length === 0) return "-";
  if (items.length === 1) return items[0].fee_type?.fee_name ?? "-";
  return items
    .map((i: any) => i.fee_type?.fee_name)
    .filter(Boolean)
    .join(", ");
}

type Transactions = {
  id: number;
  stakeholder: string;
  type: string;
  amount: number;
  date: string;
};

const columns: TableColumn<Transactions>[] = [
  {
    accessorKey: "id",
    header: "#",
    cell: ({ row }) => `#${row.getValue("id")}`,
  },
  {
    accessorKey: "stakeholder",
    header: "Stakeholder",
    cell: ({ row }) => row.original.stakeholder?.name,
  },
  {
    accessorKey: "type",
    header: "Type(s)",
    cell: ({ row }) => {
      const tx = data.value.find((t: any) => t.id === row.getValue("id"));
      return formatFeeTypes(tx?.items ?? []);
    },
  },
  {
    accessorKey: "total_amount",
    header: "Amount",
    meta: {
      class: {
        th: "text-right font-bold text-primary",
        td: "text-right font-mono",
      },
    },
    cell: ({ row }) => {
      const amount = Number.parseFloat(row.getValue("total_amount"));
      const formatted = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "PHP",
      }).format(amount);
      return h("span", { class: "font-semibold text-success" }, formatted);
    },
  },
  {
    accessorKey: "transaction_date",
    header: "Date",
    cell: ({ row }) => {
      return new Date(row.getValue("transaction_date")).toLocaleString(
        "en-US",
        {
          day: "numeric",
          month: "short",
          year: "numeric",
        },
      );
    },
  },
  { accessorKey: "action", header: "Action" },
];
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-5">
      <h1 class="text-2xl font-bold">Transactions</h1>
      <UButton v-if="can('create transactions')" icon="i-lucide-plus" @click="openCreate">
        Add Transaction
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton
          class="me-2"
          v-if="can('view transactions')"
          size="xs"
          color="info"
          variant="ghost"
          @click="openView(row.original)"
          icon="i-lucide-eye"
        ></UButton>
        <UButton
          class="me-2"
          v-if="can('edit transactions')"
          size="xs"
          color="secondary"
          @click="openEdit(row.original)"
          icon="i-lucide-edit"
        ></UButton>
        <UButton
          v-if="can('delete transactions')"
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
              ? "View Transaction"
              : modalMode === "edit"
                ? "Edit Transaction"
                : "New Transaction"
          }}
        </template>
        <template #body>
          <div class="space-y-4">
            <UFormField label="Stakeholder" class="mb-3">
              <USelect
                v-model="form.stakeholder_id"
                :items="stakeholders.map((s) => ({ label: s.name, value: s.id }))"
                placeholder="Select stakeholder"
                :disabled="modalMode === 'view'"
                @update:open="(isOpen: boolean) => isOpen && loadStakeholders()"
              />
            </UFormField>

            <UFormField label="Transaction Items" class="mb-4">
              <div
                v-for="(item, index) in form.items"
                :key="index"
                class="flex gap-2 mb-2 items-end"
              >
                <USelect
                  v-model="item.fee_type_id"
                  :items="feeTypes.map((f) => ({ label: f.fee_name, value: f.id }))"
                  placeholder="Fee Type"
                  class="w-[50%]"
                  :disabled="modalMode === 'view'"
                  @update:open="(isOpen: boolean) => isOpen && loadFeeTypes()"
                />
                <UInputNumber
                  v-model="item.quantity"
                  :min="1"
                  placeholder="Qty"
                  class="w-[20%]"
                  :disabled="modalMode === 'view'"
                />
                <UInputNumber
                  v-model="item.unit_price"
                  :step="0.01"
                  :min="0"
                  placeholder="Unit Price"
                  class="w-[20%]"
                  :disabled="modalMode === 'view'"
                />
                <span class="w-auto font-mono text-right text-primary">
                  {{ formatCurrency(calculateSubtotal(item)) }}
                </span>
                <UButton
                  v-if="modalMode !== 'view' && form.items.length > 1"
                  size="xs"
                  color="error"
                  variant="outline"
                  icon="i-lucide-trash-2"
                  @click="removeItem(index)"
                />
              </div>
              <UButton
                v-if="modalMode !== 'view'"
                type="button"
                variant="outline"
                icon="i-lucide-plus"
                class="w-fit"
                @click="addItem"
              >
                Add Item
              </UButton>
            </UFormField>

            <UFormField label="Total Amount" class="mb-3">
              <span class="text-2xl font-bold text-success">
                {{ formatCurrency(calculateTotal()) }}
              </span>
            </UFormField>

            <div class="flex flex-row">
              <UFormField label="Date" class="mb-3 me-3 w-full">
                <UInput type="date" v-model="form.transaction_date" class="w-full" :disabled="modalMode === 'view'" />
              </UFormField>

              <UFormField label="Status" class="mb-3 w-full">
                <USelect v-model="form.status" :items="statusOptions" class="w-full" :disabled="modalMode === 'view'" />
              </UFormField>
            </div>
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
