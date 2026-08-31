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

const UBadge = resolveComponent("UBadge");

const typeColor = {
  buyer: "success" as const,
  broker: "warning" as const,
  renter: "neutral" as const,
};

const stakeholders = ref<any[]>([]);
const searchQuery = ref("");
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
    const result = await apiFetch(`/v1/stakeholders?${params.toString()}`, {
      parseJson: true,
    });
    return { data: result.data, total: result.meta.total };
  },
});

watch(searchQuery, () => {
  page.value = 1;
});

const showModal = ref(false);
const modalMode = ref<"create" | "edit" | "view">("create");
const saving = ref(false);
const editingStakeholder = ref<any>(null);

const types = ref<any[]>([]);
const typesLoaded = ref(false);

const form = reactive({
  name: "",
  stakeholder_type_id: null as number | null,
  contact_no: "",
  email: "",
  address: "",
  status: "active",
});

async function loadTypes() {
  if (typesLoaded.value) return;
  const allTypes = (
    (await apiFetch("/v1/stakeholder-types", { parseJson: true })) as any
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
  form.stakeholder_type_id = null;
  form.contact_no = "";
  form.email = "";
  form.address = "";
  form.status = "active";
  showModal.value = true;
  loadTypes();
}

function openView(row: any) {
  modalMode.value = "view";
  editingStakeholder.value = row;
  form.name = row.name;
  form.stakeholder_type_id = row.stakeholder_type_id;
  form.contact_no = row.contact_no ?? "";
  form.email = row.email ?? "";
  form.address = row.address ?? "";
  form.status = row.status ?? "active";
  showModal.value = true;
  loadTypes();
}

function openEdit(row: any) {
  modalMode.value = "edit";
  editingStakeholder.value = row;
  form.name = row.name;
  form.stakeholder_type_id = row.stakeholder_type_id;
  form.contact_no = row.contact_no ?? "";
  form.email = row.email ?? "";
  form.address = row.address ?? "";
  form.status = row.status ?? "active";
  showModal.value = true;
  loadTypes();
}

async function save() {
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
      description: e.message ?? "Please try again.",
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
    <div class="flex justify-between items-center gap-4">
      <div class="flex flex-col">
        <h1 class="text-2xl font-bold mb-3">Stakeholders</h1>
        <UInput
          v-model="searchQuery"
          placeholder="Search stakeholders..."
          icon="i-lucide-search"
          class="max-w-xs"
        />
      </div>

      <UButton
        v-if="can('create stakeholders')"
        icon="i-lucide-plus"
        @click="openCreate"
      >
        Add Stakeholder
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton
          class="me-2"
          v-if="can('view stakeholders')"
          size="xs"
          color="info"
          variant="ghost"
          @click="openView(row.original)"
          icon="i-lucide-eye"
        ></UButton>
        <UButton
          class="me-2"
          v-if="can('edit stakeholders')"
          size="xs"
          color="secondary"
          @click="openEdit(row.original)"
          icon="i-lucide-edit"
        ></UButton>
        <UButton
          v-if="can('delete stakeholders')"
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
              ? "View Stakeholder"
              : modalMode === "edit"
                ? "Edit Stakeholder"
                : "New Stakeholder"
          }}
        </template>
        <template #body>
          <div class="space-y-4">
            <UFormField label="Name" class="mb-3">
              <UInput v-model="form.name" :disabled="modalMode === 'view'" class="w-full" />
            </UFormField>

            <UFormField label="Stakeholder Type" class="mb-3">
              <USelect
                v-model="form.stakeholder_type_id"
                :items="types"
                value-key="id"
                label-key="name"
                class="w-full"
                :disabled="modalMode === 'view'"
              />
            </UFormField>

            <UFormField label="Contact" class="mb-3">
              <UInput v-model="form.contact_no" :disabled="modalMode === 'view'" class="w-full" />
            </UFormField>

            <UFormField label="Email" class="mb-3">
              <UInput v-model="form.email" type="email" :disabled="modalMode === 'view'" class="w-full" />
            </UFormField>

            <UFormField label="Address" class="mb-3">
              <UTextarea v-model="form.address" :disabled="modalMode === 'view'" class="w-full" />
            </UFormField>

            <UFormField label="Status" class="mb-3">
              <USelect
                v-model="form.status"
                :items="[
                  { label: 'Active', value: 'active' },
                  { label: 'Inactive', value: 'inactive' },
                ]"
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
