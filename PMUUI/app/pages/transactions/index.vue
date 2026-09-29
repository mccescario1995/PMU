<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, onUnmounted, ref, computed, watch, h } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { useTablePagination } from "~/composables/useTablePagination";
import { useToast } from "#imports";
import type { SelectItem } from "@nuxt/ui";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const toast = useToast();

const searchQuery = ref("");
const dateFilter = ref("");
const dateFilterEnd = ref("");

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
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: pageSize.toString(),
    });
    if (searchQuery.value) params.append("or_number", searchQuery.value);
    if (dateFilter.value) params.append("date_from", dateFilter.value);
    if (dateFilterEnd.value) params.append("date_to", dateFilterEnd.value);

    const result = await apiFetch(
      `/v1/transactions?${params.toString()}`,
      { parseJson: true },
    );

    return { data: result.data, total: result.meta.total };
  },
});

const showModal = ref(false);
const modalMode = ref<"create" | "edit" | "view">("create");
const saving = ref(false);
const editingTransaction = ref<any>(null);
const formErrors = ref<string[]>([]);

const stakeholders = ref<any[]>([]);
const feeTypes = ref<any[]>([]);
const stakeholdersLoaded = ref(false);
const feeTypesLoaded = ref(false);
const transactionRevenue = ref<any[]>([]);
const transactionRevenueFeatures = ref<any[]>([]);
const revenueLoading = ref(false);

const form = reactive({
  stakeholder_id: null as number | null,
  or_number: null as string | null,
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

let orCheckTimeout: ReturnType<typeof setTimeout> | null = null;
const orConflict = ref<{ type: string; id: number; name: string } | null>(null);

async function checkOrAvailability(value: string) {
  if (!value || value.length !== 7) {
    orConflict.value = null;
    return;
  }
  const isEditOrView = modalMode.value === "edit" || modalMode.value === "view";
  const excludeType = isEditOrView ? "transaction" : undefined;
  const excludeId = isEditOrView && editingTransaction.value ? editingTransaction.value.id : undefined;
  const params = new URLSearchParams({ or_number: value });
  if (excludeType) params.set("exclude_type", excludeType);
  if (excludeId) params.set("exclude_id", String(excludeId));
  try {
    const result = await apiFetch(`/v1/check-or-availability?${params.toString()}`, { parseJson: true });
    orConflict.value = result.available ? null : result.conflict;
  } catch {
    orConflict.value = null;
  }
}

watch(
  () => form.or_number,
  (val) => {
    const value = val ?? "";
    // Clear OR conflict if empty or not 7 digits
    if (!value || value.length !== 7) {
      orConflict.value = null;
    }
    // Validate 7 digits
    if (value && !/^\d{7}$/.test(value)) {
      if (!formErrors.value.includes("OR Number must be exactly 7 digits")) {
        formErrors.value.push("OR Number must be exactly 7 digits");
      }
    } else {
      // Remove the 7-digit error if valid
      formErrors.value = formErrors.value.filter(e => e !== "OR Number must be exactly 7 digits");
    }
    // Debounce OR availability check
    if (value && /^\d{7}$/.test(value)) {
      if (orCheckTimeout) clearTimeout(orCheckTimeout);
      orCheckTimeout = setTimeout(() => checkOrAvailability(value), 300);
    }
  },
);

onUnmounted(() => {
  if (orCheckTimeout) clearTimeout(orCheckTimeout);
});

// Watch orConflict to show availability errors in banner
watch(
  () => orConflict.value,
  (conflict) => {
    if (conflict) {
      const msg = `OR Number already used by ${conflict.type === 'stakeholder' ? 'Stakeholder' : 'Transaction'}: ${conflict.name}`;
      if (!formErrors.value.includes(msg)) {
        formErrors.value.push(msg);
      }
    } else {
      // Remove any OR conflict messages
      formErrors.value = formErrors.value.filter(e => !e.startsWith("OR Number already used by"));
    }
  },
);

const statusOptions: SelectItem[] = [
  { label: "Pending", value: "pending" },
  { label: "Completed", value: "completed" },
  { label: "Cancelled", value: "cancelled" },
];

async function loadStakeholders() {
  if (stakeholdersLoaded.value) return;
  stakeholders.value = (
    (await apiFetch("/v1/dropdowns/stakeholders", { parseJson: true })) as any
  ).data;
  stakeholdersLoaded.value = true;
}

async function loadFeeTypes() {
  if (feeTypesLoaded.value) return;
  feeTypes.value = (
    (await apiFetch("/v1/dropdowns/fee-types", { parseJson: true })) as any
  ).data;
  feeTypesLoaded.value = true;
  form.items.forEach((item) => {
    const feeType = feeTypes.value.find((f) => f.id === item.fee_type_id);
    if (feeType) {
      item.unit_price = feeType.base_rate;
    }
  });
}

async function loadTransactionRevenue() {
  revenueLoading.value = true;
  try {
    transactionRevenue.value = (await apiFetch(
      "/v1/transaction-revenue?per_page=100",
      { parseJson: true },
    )) as any[];
  } catch {
    // silent
  } finally {
    revenueLoading.value = false;
  }
}

async function loadTransactionRevenueFeatures() {
  try {
    transactionRevenueFeatures.value = (await apiFetch(
      "/v1/transaction-revenue-features?per_page=100",
      { parseJson: true },
    )) as any[];
  } catch {
    // silent
  }
}

async function openCreate() {
  modalMode.value = "create";
  editingTransaction.value = null;
  await loadFeeTypes();
  form.stakeholder_id = null;
  form.or_number = null;
  form.items = [
    {
      fee_type_id: null,
      quantity: 1,
      unit_price: 0,
    },
  ];
  form.transaction_date = new Date().toISOString().slice(0, 10);
  form.status = "completed";
  orConflict.value = null;
  formErrors.value = [];
  showModal.value = true;
}

async function openView(row: any) {
  modalMode.value = "view";
  editingTransaction.value = row;
  await loadFeeTypes();
  form.stakeholder_id = row.stakeholder_id;
  form.or_number = row.or_number;
  form.items = (row.items ?? []).map((item: any) => ({
    fee_type_id: item.fee_type_id,
    quantity: item.quantity,
    unit_price: item.unit_price,
  }));
  form.transaction_date = (row.transaction_date ?? "").toString().slice(0, 10);
  form.status = row.status;
  orConflict.value = null;
  formErrors.value = [];
  showModal.value = true;
}

async function openEdit(row: any) {
  modalMode.value = "edit";
  editingTransaction.value = row;
  await loadFeeTypes();
  form.stakeholder_id = row.stakeholder_id;
  form.or_number = row.or_number;
  form.items = (row.items ?? []).map((item: any) => ({
    fee_type_id: item.fee_type_id,
    quantity: item.quantity,
    unit_price: item.unit_price,
  }));
  form.transaction_date = (row.transaction_date ?? "").toString().slice(0, 10);
  form.status = row.status;
  orConflict.value = null;
  formErrors.value = [];
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

function calculateSubtotal(item: any) {
  return Number(item.quantity || 0) * Number(item.unit_price || 0);
}

watch(
  () => form.items,
  (newItems) => {
    newItems.forEach((item) => {
      if (item.fee_type_id) {
        const feeType = feeTypes.value.find((f) => f.id === item.fee_type_id);
        if (feeType) {
          item.unit_price = feeType.base_rate;
        }
      }
    });
  },
  { deep: true },
);

function formatCurrency(value: number) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "PHP",
  }).format(value);
}

function calculateTotal() {
  return form.items.reduce((sum, item) => sum + calculateSubtotal(item), 0);
}

function resetPageAndRefresh() {
  if (page.value !== 1) {
    page.value = 1;
    return;
  }

  goToPageInput.value = 1;
  refresh();
}

function clearFilters() {
  searchQuery.value = "";
  dateFilter.value = "";
  dateFilterEnd.value = "";
  resetPageAndRefresh();
}

function validateForm(): boolean {
  formErrors.value = [];
  let isValid = true;

  if (!form.stakeholder_id) {
    formErrors.value.push("Stakeholder is required");
    isValid = false;
  }
  if (!form.or_number || !/^\d{7}$/.test(form.or_number)) {
    formErrors.value.push("OR Number must be exactly 7 digits");
    isValid = false;
  }
  if (!form.transaction_date) {
    formErrors.value.push("Transaction date is required");
    isValid = false;
  }
  if (form.items.length === 0) {
    formErrors.value.push("At least one transaction item is required");
    isValid = false;
  }
  for (const item of form.items) {
    if (!item.fee_type_id) {
      formErrors.value.push("All items must have a fee type selected");
      isValid = false;
      break;
    }
    if (!item.quantity || item.quantity < 1) {
      formErrors.value.push("Quantity must be at least 1 for all items");
      isValid = false;
      break;
    }
  }
  return isValid;
}

async function save() {
  if (!validateForm()) return;

  saving.value = true;
  try {
    const items = form.items.map((item) => ({
      fee_type_id: item.fee_type_id,
      quantity: item.quantity,
    }));

    const payload = {
      stakeholder_id: form.stakeholder_id,
      or_number: form.or_number ? String(form.or_number) : null,
      transaction_date: form.transaction_date,
      status: form.status,
      remarks: "",
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
    loadTransactionRevenue();
    loadTransactionRevenueFeatures();
  } catch (e: any) {
    toast.add({
      title: modalMode.value === "edit" ? "Failed to update transaction" : "Failed to create transaction",
      description: "Please try again.",
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
  // {
  //   accessorKey: "id",
  //   header: "#",
  //   cell: ({ row }) => `#${row.getValue("id")}`,
  // },
  {
    accessorKey: "or",
    header: "OR #",
    cell: ({ row }) => row.original.or_number ?? "-",
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
      return formatFeeTypes(row.original.items ?? []);
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
    accessorKey: "revenue_impact",
    header: "Revenue Impact",
    cell: ({ row }) => {
      if (!row.original) return "-";
      if (row.original.status === "completed") {
        return h("span", { class: "text-sm font-semibold text-success" }, "● Revenue Active");
      }
      if (row.original.status === "cancelled") {
        return h("span", { class: "text-sm font-semibold text-error" }, "● Revenue Reversed");
      }
      return h("span", { class: "text-sm font-semibold text-slate-400" }, "● Pending");
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

onMounted(() => {
  loadStakeholders();
  loadFeeTypes();
  loadTransactionRevenue();
  loadTransactionRevenueFeatures();
})
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-5">
      <h1 class="text-2xl font-bold">Transactions</h1>
      <UButton v-if="can('create transactions')" icon="i-lucide-plus" @click="openCreate">
        Add Transaction
      </UButton>
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
      <UFormField label="OR Number" class="w-64">
        <UInput v-model="searchQuery" placeholder="Search by OR Number" class="w-full"
          @keyup.enter="resetPageAndRefresh">
          <template #leading>
            <UIcon name="i-lucide-search" />
          </template>
        </UInput>
      </UFormField>
      <UFormField label="From Date" class="w-40">
        <UInput v-model="dateFilter" type="date" placeholder="From Date" class="w-full" @change="resetPageAndRefresh" />
      </UFormField>
      <UFormField label="To Date" class="w-40">
        <UInput v-model="dateFilterEnd" type="date" placeholder="To Date" class="w-full"
          @change="resetPageAndRefresh" />
      </UFormField>
      <UButton variant="outline" @click="clearFilters" class="self-end">
        <UIcon name="i-lucide-x" class="mr-1" /> Clear
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton class="me-2" v-if="can('view transactions')" size="xs" color="info" variant="ghost"
          @click="openView(row.original)" icon="i-lucide-eye"></UButton>
        <UButton class="me-2" v-if="can('edit transactions')" size="xs" color="secondary"
          @click="openEdit(row.original)" icon="i-lucide-edit"></UButton>
        <UButton v-if="can('delete transactions')" size="xs" color="error" @click="remove(row.original)"
          icon="i-lucide-trash"></UButton>
      </template>
    </UTable>

    <div class="flex items-center justify-between mt-4">
      <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Rows per page:</span>
        <USelect v-model="pageSize" :items="[5, 10, 20, 30, 50]" class="w-20" />
      </div>
      <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Go to page:</span>
        <UInput v-model="goToPageInput" type="number" :min="1" :max="totalPages" class="w-16"
          @keyup.enter="handleGoToPage" />
        <UButton size="sm" @click="handleGoToPage">Go</UButton>
      </div>
      <UPagination :total="totalItems" v-model:page="page" :items-per-page="pageSizeNumber" />
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
          <div v-if="formErrors.length > 0" class="p-3 bg-red-50 border border-red-200 rounded-lg">
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
              <li v-for="(error, i) in formErrors" :key="i">{{ error }}</li>
            </ul>
          </div>

          <UFormField label="Stakeholder" class="mb-3" required>
            <USelectMenu v-model="form.stakeholder_id" value-key="value" class="w-full"
              :items="stakeholders.map((s) => ({ label: s.name, value: s.id }))" placeholder="Select stakeholder"
              :disabled="modalMode === 'view'" @update:open="(isOpen: boolean) => isOpen && loadStakeholders()">
              <template #empty="{ searchTerm }">
                <p v-if="searchTerm" class="text-center text-sm text-muted p-2">
                  Input the registered stakeholder.
                </p>
                <p v-else class="text-center text-sm text-muted p-2">
                  No stakeholders available.
                </p>
              </template>
            </USelectMenu>
          </UFormField>

          <UFormField label="OR Number" class="mb-3" required>
            <UInput v-model="form.or_number" placeholder="7 digits" type="number" inputmode="numeric" :min="0"
              :disabled="modalMode === 'view'" class="w-full"
              @input="form.or_number = form.or_number?.replace(/\D/g, '').slice(0, 7)" />
          </UFormField>

          <UFormField label="Transaction Items" class="mb-4" required>
            <div v-for="(item, index) in form.items" :key="index" class="flex gap-2 mb-2 items-end">
              <USelect v-model="item.fee_type_id" :items="feeTypes.map((f) => ({ label: f.fee_name, value: f.id }))"
                placeholder="Fee Type" class="w-[30%]" :disabled="modalMode === 'view'"
                @update:open="(isOpen: boolean) => isOpen && loadFeeTypes()" />
              <UInputNumber v-model="item.quantity" :min="1" placeholder="Qty" class="w-[30%]"
                :disabled="modalMode === 'view'" />
              <UInputNumber v-model="item.unit_price" :step="0.01" :min="0" placeholder="Unit Price" class="w-[40%]"
                :disabled="true" readonly />
              <span class="w-auto font-mono text-right text-primary">
                {{ formatCurrency(calculateSubtotal(item)) }}
              </span>
              <UButton v-if="modalMode !== 'view' && form.items.length > 1" size="xs" color="error" variant="outline"
                icon="i-lucide-trash-2" @click="removeItem(index)" />
            </div>
            <UButton v-if="modalMode !== 'view'" type="button" variant="outline" icon="i-lucide-plus" class="w-fit"
              @click="addItem">
              Add Item
            </UButton>
          </UFormField>

          <UFormField label="Total Amount" class="mb-3">
            <span class="text-2xl font-bold text-success">
              {{ formatCurrency(calculateTotal()) }}
            </span>
          </UFormField>

          <div class="flex flex-row">
            <UFormField label="Date" class="mb-3 me-3 w-full" required>
              <UInput type="date" v-model="form.transaction_date" class="w-full" :disabled="modalMode === 'view'" />
            </UFormField>

            <UFormField label="Status" class="mb-3 w-full" required>
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
