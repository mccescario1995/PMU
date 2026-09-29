<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, onUnmounted, ref, computed, watch, h, reactive, nextTick } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { useTablePagination } from "~/composables/useTablePagination";
import { useToast } from "#imports";
import type { SelectItem } from "@nuxt/ui";
import * as XLSX from "xlsx";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const toast = useToast();

onMounted(() => {
  loadTypes();
});

const stakeholders = ref<any[]>([]);
const searchQuery = ref("");
const typeFilter = ref<string | null>(null);
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
      page: String(page),
      per_page: String(pageSize),
    });
    if (searchQuery.value) {
      params.set("search", searchQuery.value);
    }
    if (typeFilter.value) {
      params.set("stakeholder_type_id", typeFilter.value);
    }
    const result = await apiFetch(`/v1/stakeholders?${params.toString()}`, {
      parseJson: true,
    });
    return { data: result.data, total: result.meta.total };
  },
});

watch(searchQuery, () => {
  page.value = 1;
  refresh();
});

watch(typeFilter, () => {
  page.value = 1;
  refresh();
});

const showModal = ref(false);
const modalMode = ref<"create" | "edit" | "view">("create");
const saving = ref(false);
const editingStakeholder = ref<any>(null);
const formErrors = ref<string[]>([]);

const types = ref<any[]>([]);
const typesLoaded = ref(false);

const form = reactive({
  name: "",
  official_receipt: "",
  stakeholder_type_id: null as number | null,
  status: "active",
});

let orCheckTimeout: ReturnType<typeof setTimeout> | null = null;
const orConflict = ref<{ type: string; id: number; name: string } | null>(null);

async function checkOrAvailability(value: string) {
  if (!value || value.length !== 7) {
    orConflict.value = null;
    return;
  }
  const isEditOrView = modalMode.value === "edit" || modalMode.value === "view";
  const excludeType = isEditOrView ? "stakeholder" : undefined;
  const excludeId = isEditOrView && editingStakeholder.value ? editingStakeholder.value.id : undefined;
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
  () => form.official_receipt,
  (val) => {
    const value = val ?? "";
    // Clear OR conflict if empty or not 7 digits
    if (!value || value.length !== 7) {
      orConflict.value = null;
    }
    // Validate 7 digits
    if (value && !/^\d{7}$/.test(value)) {
      if (!formErrors.value.includes("Official receipt must be exactly 7 digits")) {
        formErrors.value.push("Official receipt must be exactly 7 digits");
      }
    } else {
      // Remove the 7-digit error if valid
      formErrors.value = formErrors.value.filter(e => e !== "Official receipt must be exactly 7 digits");
    }
    // Debounce OR availability check
    if (value && /^\d{7}$/.test(value)) {
      if (orCheckTimeout) clearTimeout(orCheckTimeout);
      orCheckTimeout = setTimeout(() => checkOrAvailability(value), 300);
    }
  },
);

function validateForm(): boolean {
  formErrors.value = [];
  let isValid = true;

  if (!form.name.trim()) {
    formErrors.value.push("Name is required");
    isValid = false;
  }

  if (!form.official_receipt.trim()) {
    formErrors.value.push("Official receipt is required");
    isValid = false;
  } else if (!/^\d{7}$/.test(form.official_receipt.trim())) {
    formErrors.value.push("Official receipt must be exactly 7 digits");
    isValid = false;
  }

  if (!form.stakeholder_type_id) {
    formErrors.value.push("Stakeholder type is required");
    isValid = false;
  }

  if (!form.status) {
    formErrors.value.push("Status is required");
    isValid = false;
  }

  return isValid;
}

const isFormValid = computed(() => {
  if (!form.name.trim()) return false;
  if (!form.official_receipt.trim() || !/^\d{7}$/.test(form.official_receipt.trim())) return false;
  if (!form.stakeholder_type_id) return false;
  if (!form.status) return false;
  if (orConflict.value) return false;
  if (formErrors.value.length > 0) return false;
  return true;
});

onUnmounted(() => {
  if (orCheckTimeout) clearTimeout(orCheckTimeout);
});

// Watch orConflict to show availability errors in banner
watch(
  () => orConflict.value,
  (conflict) => {
    if (conflict) {
      const msg = `Official receipt already used by ${conflict.type === 'stakeholder' ? 'Stakeholder' : 'Transaction'}: ${conflict.name}`;
      if (!formErrors.value.includes(msg)) {
        formErrors.value.push(msg);
      }
    } else {
      // Remove any OR conflict messages
      formErrors.value = formErrors.value.filter(e => !e.startsWith("Official receipt already used by"));
    }
  },
);

async function loadTypes() {
  if (typesLoaded.value) return;
  const allTypes = (
    (await apiFetch("/v1/dropdowns/stakeholder-types", { parseJson: true })) as any
  ).data;
  const existing = new Map(types.value.map((t: any) => [t.id, t]));
  for (const t of allTypes) {
    if (!existing.has(t.id)) {
      existing.set(t.id, t);
    }
  }
  types.value = Array.from(existing.values());
  typesLoaded.value = true;
}

function openCreate() {
  modalMode.value = "create";
  editingStakeholder.value = null;
  form.name = "";
  form.official_receipt = "";
  form.stakeholder_type_id = null;
  form.status = "active";
  formErrors.value = [];
  orConflict.value = null;
  showModal.value = true;
  loadTypes();
}

function openView(row: any) {
  modalMode.value = "view";
  editingStakeholder.value = row;
  form.name = row.name;
  form.official_receipt = row.official_receipt ?? "";
  form.stakeholder_type_id = row.stakeholder_type_id;
  form.status = row.status ?? "active";
  formErrors.value = [];
  orConflict.value = null;
  showModal.value = true;
  loadTypes();
}

function openEdit(row: any) {
  modalMode.value = "edit";
  editingStakeholder.value = row;
  form.name = row.name;
  form.official_receipt = row.official_receipt ?? "";
  form.stakeholder_type_id = row.stakeholder_type_id;
  form.status = row.status ?? "active";
  formErrors.value = [];
  orConflict.value = null;
  showModal.value = true;
  loadTypes();
}

async function save() {
  if (!validateForm()) return;
  saving.value = true;
  try {
    if (modalMode.value === "edit" && editingStakeholder.value) {
      await apiFetch(`/v1/stakeholders/${editingStakeholder.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });
      toast.add({ title: "Stakeholder updated", color: "success" });
    } else {
      await apiFetch("/v1/stakeholders", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });
      toast.add({ title: "Stakeholder created", color: "success" });
    }
    showModal.value = false;
    refresh();
  } catch (e: any) {
    toast.add({
      title: modalMode.value === "edit" ? "Failed to update stakeholder" : "Failed to create stakeholder",
      description: "Please try again.",
      color: "error",
    });
  } finally {
    saving.value = false;
  }
}

async function remove(row: any) {
  if (!confirm("Delete this stakeholder?")) return;
  await apiFetch(`/v1/stakeholders/${row.id}`, { method: "DELETE" });
  data.value = data.value.filter((s: any) => s.id !== row.id);
  totalItems.value = Math.max(0, totalItems.value - 1);
  toast.add({ title: "Stakeholder deleted", color: "success" });
}

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

type Stakeholder = {
  id: number;
  name: string;
  official_receipt: string;
};

const columns: TableColumn<Stakeholder>[] = [
  // {
  //   accessorKey: "id",
  //   header: "#",
  //   cell: ({ row }) => `#${row.getValue("id")}`,
  // },
  {
    accessorKey: "official_receipt",
    header: "OR",
  },
  {
    accessorKey: "name",
    header: "Name",
  },
  {
    accessorKey: "stakeholder_type_id",
    header: "Type",
    cell: ({ row }) => {
      const typeName = row.original.stakeholder_type?.name ?? "Unknown";
      return h("UBadge", { class: "capitalize", variant: "subtle", color: "primary" }, () => typeName);
    },
  },
  {
    accessorKey: "status",
    header: "Status",
    cell: ({ row }) => {
      const status = row.getValue("status");
      const color = status === "active" ? "success" : "error";
      return h("UBadge", { class: "capitalize", variant: "subtle", color }, () => status);
    },
  },
  {
    accessorKey: "action",
    header: "Action",
  },
];
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between items-center gap-4 flex-wrap">
      <div class="flex flex-col">
        <h1 class="text-2xl font-bold mb-3">Stakeholders</h1>
        <UInput v-model="searchQuery" placeholder="Search stakeholders..." icon="i-lucide-search" class="max-w-xs"
          @keyup.enter="refresh" />
      </div>

      <div class="flex items-center gap-2">
        <UButton v-if="can('create stakeholders')" icon="i-lucide-plus" @click="openCreate">
          Add Stakeholder
        </UButton>
        <UButton icon="i-lucide-download" @click="exportToExcel" variant="outline">
          Export
        </UButton>
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 mt-2">
      <UButton v-for="type in types" :key="type.id" :label="type.name" variant="outline"
        :color="typeFilter === String(type.id) ? 'primary' : undefined"
        @click="typeFilter = typeFilter === String(type.id) ? null : String(type.id)" />
      <UButton :label="'All'" variant="outline" :color="typeFilter === null ? 'primary' : undefined"
        @click="typeFilter = null" />
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton class="me-2" v-if="can('view stakeholders')" size="xs" color="info" variant="ghost"
          @click="openView(row.original)" icon="i-lucide-eye"></UButton>
        <UButton class="me-2" v-if="can('edit stakeholders')" size="xs" color="secondary"
          @click="openEdit(row.original)" icon="i-lucide-edit"></UButton>
        <UButton v-if="can('delete stakeholders')" size="xs" color="error" @click="remove(row.original)"
          icon="i-lucide-trash"></UButton>
      </template>
    </UTable>

    <div class="flex items-center justify-between mt-4 flex-wrap gap-4">
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
            ? "View Stakeholder"
            : modalMode === "edit"
              ? "Edit Stakeholder"
              : "New Stakeholder"
        }}
      </template>
      <template #body>
        <div class="space-y-4">
          <div v-if="formErrors.length > 0" class="p-3 bg-red-50 border border-red-200 rounded-lg">
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
              <li v-for="(error, i) in formErrors" :key="i">{{ error }}</li>
            </ul>
          </div>

          <UFormField label="Name" class="mb-3" required>
            <UInput v-model="form.name" :disabled="modalMode === 'view'" class="w-full" />
          </UFormField>

          <UFormField label="Official Receipt" class="mb-3" required>
            <UInput v-model="form.official_receipt" :disabled="modalMode === 'view'" class="w-full" type="number"
              inputmode="numeric" :min="0"
              @input="form.official_receipt = (form.official_receipt ?? '').replace(/\D/g, '').slice(0, 7)"
              placeholder="Enter Official Receipt" />
          </UFormField>

          <UFormField label="Stakeholder Type" class="mb-3" required>
            <USelect v-model="form.stakeholder_type_id" :items="types" value-key="id" label-key="name" class="w-full"
              :disabled="modalMode === 'view'" />
          </UFormField>

          <UFormField label="Status" class="mb-3" required>
            <USelect v-model="form.status" :items="[
              { label: 'Active', value: 'active' },
              { label: 'Inactive', value: 'inactive' },
            ]" class="w-full" :disabled="modalMode === 'view'" />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" @click="showModal = false">Close</UButton>
          <UButton v-if="modalMode !== 'view'" @click="save" :loading="saving" :disabled="!isFormValid">
            Save
          </UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>