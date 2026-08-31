<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, h, computed, watch } from "vue";
import { ref } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { getPaginationRowModel } from "@tanstack/vue-table";
import { useTablePagination } from "~/composables/useTablePagination";
import { useToast } from "#imports";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const toast = useToast();

const types = ref<any[]>([]);
const {
  page,
  pageSize,
  pageSizeNumber,
  goToPageInput,
  tablePagination,
  totalPages,
  handleGoToPage,
  refresh,
} = useTablePagination(() => types.value.length);
const loading = ref(true);
const showForm = ref(false);
const editing = ref<any>(null);
const viewing = ref(false);

const form = reactive({
  name: "",
  description: "",
});

onMounted(async () => {
  loading.value = true;
  types.value = (
    (await apiFetch("/v1/stakeholder-types", { parseJson: true })) as any
  ).data;
  loading.value = false;
});

function openCreate() {
  editing.value = null;
  viewing.value = false;
  form.name = "";
  form.description = "";
  showForm.value = true;
}

function openView(row: any) {
  editing.value = row;
  viewing.value = true;
  form.name = row.name;
  form.description = row.description ?? "";
  showForm.value = true;
}

function openEdit(row: any) {
  editing.value = row;
  viewing.value = false;
  form.name = row.name;
  form.description = row.description ?? "";
  showForm.value = true;
}

async function save() {
  try {
    if (editing.value) {
      await apiFetch(`/v1/stakeholder-types/${editing.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });

      toast.add({
        title: "Stakeholder type updated",
        description: "The stakeholder type was updated successfully.",
        color: "success",
      });
    } else {
      await apiFetch("/v1/stakeholder-types", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });

      toast.add({
        title: "Stakeholder type created",
        description: "The stakeholder type was created successfully.",
        color: "success",
      });
    }
    showForm.value = false;
    // refresh();
    types.value = (
      (await apiFetch("/v1/stakeholder-types", { parseJson: true })) as any
    ).data;
  } catch (e: any) {
    toast.add({
      title: editing.value
        ? "Failed to update stakeholder type"
        : "Failed to create stakeholder type",
      description: e.message ?? "Please try again.",
      color: "error",
    });
  }
}

async function remove(row: any) {
  if (!confirm("Delete this stakeholder type?")) return;
  await apiFetch(`/v1/stakeholder-types/${row.id}`, { method: "DELETE" });
  types.value = types.value.filter((t: any) => t.id !== row.id);
  toast.add({ title: "Stakeholder type deleted", color: "success" });
}

type StakeholderType = {
  id: number;
  name: string;
  description: string;
};

const columns: TableColumn<StakeholderType>[] = [
  { accessorKey: "id", header: "#" },
  { accessorKey: "name", header: "Name" },
  { accessorKey: "description", header: "Description" },
  { accessorKey: "action", header: "Action" },
];
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Stakeholder Types</h1>
      <UButton
        v-if="can('create stakeholder types')"
        icon="i-lucide-plus"
        @click="openCreate"
      >
        Add Type
      </UButton>
    </div>

    <UTable
      :data="types"
      :columns="columns"
      :loading="loading"
      :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
      v-model:pagination="tablePagination"
    >
      <template #action-cell="{ row }">
        <UButton
          v-if="can('view stakeholder types')"
          size="xs"
          color="info"
          variant="ghost"
          @click="openView(row.original)"
          icon="i-lucide-eye"
          class="me-2"
        ></UButton>
        <UButton
          v-if="can('edit stakeholder types')"
          size="xs"
          color="secondary"
          @click="openEdit(row.original)"
          icon="i-lucide-edit"
          class="me-2"
        ></UButton>
        <UButton
          v-if="can('delete stakeholder types')"
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
        :total="types.length"
        v-model:page="page"
        :items-per-page="pageSizeNumber"
      />
    </div>

    <UModal v-model:open="showForm">
      <template #header>
        {{
          viewing
            ? "View Stakeholder Type"
            : editing
              ? "Edit Stakeholder Type"
              : "New Stakeholder Type"
        }}
      </template>
      <template #body>
        <div class="space-y-4">
          <UFormField label="Name" class="mb-3">
            <UInput v-model="form.name" :disabled="viewing" class="w-full" />
          </UFormField>
          <UFormField label="Description" class="mb-3">
            <UTextarea
              v-model="form.description"
              :disabled="viewing"
              class="w-full"
            />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" @click="showForm = false">Close</UButton>
          <UButton v-if="!viewing" @click="save">Save</UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
