<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, h, computed, watch } from "vue";
import { ref } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { useTablePagination } from "~/composables/useTablePagination";
import { useToast } from "#imports";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const toast = useToast();

const UBadge = resolveComponent("UBadge");

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
    try {
      const result = await apiFetch(
        `/v1/settings?page=${page}&per_page=${pageSize}`,
        { parseJson: true },
      );
      const items = Array.isArray(result?.data) ? result.data : [];
      const total = result?.meta?.total ?? result?.total ?? items.length;
      return { data: items, total };
    } catch (e: any) {
      toast.add({
        title: "Failed to load settings",
        description: e.message ?? "Please try again.",
        color: "error",
      });
      return { data: [], total: 0 };
    }
  },
});

const showForm = ref(false);
const editing = ref<any>(null);
const viewing = ref(false);

const form = reactive({
  key: "",
  value: "",
  type: "string",
  description: "",
});

function openCreate() {
  editing.value = null;
  viewing.value = false;
  form.key = "";
  form.value = "";
  form.type = "string";
  form.description = "";
  showForm.value = true;
}

function openView(row: any) {
  editing.value = row;
  viewing.value = true;
  form.key = row.key;
  form.value = row.value ?? "";
  form.type = row.type ?? "string";
  form.description = row.description ?? "";
  showForm.value = true;
}

function openEdit(row: any) {
  editing.value = row;
  viewing.value = false;
  form.key = row.key;
  form.value = row.value ?? "";
  form.type = row.type ?? "string";
  form.description = row.description ?? "";
  showForm.value = true;
}

async function save() {
  try {
    if (editing.value) {
      await apiFetch(`/v1/settings/${editing.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });
      toast.add({ title: "Setting updated", color: "success" });
    } else {
      await apiFetch("/v1/settings", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });
      toast.add({ title: "Setting created", color: "success" });
    }
    showForm.value = false;
    page.value = 1;
    refresh();
  } catch (e: any) {
    alert(e.message ?? "Failed to save setting");
  }
}

async function remove(row: any) {
  if (!confirm("Delete this setting?")) return;
  await apiFetch(`/v1/settings/${row.id}`, { method: "DELETE" });
  data.value = data.value.filter((s: any) => s.id !== row.id);
  totalItems.value = Math.max(0, totalItems.value - 1);
}

const typeOptions = ["string", "number", "boolean", "json"];

type Setting = {
  id: number;
  key: string;
  value: string;
  type: string;
  description: string;
};

const typeColor: Record<string, string> = {
  number: "primary",
  boolean: "success",
  json: "warning",
};

const columns: TableColumn<Setting>[] = [
  { accessorKey: "key", header: "Key" },
  { accessorKey: "value", header: "Value" },
  {
    accessorKey: "type",
    header: "Type",
    cell: ({ row }) => {
      const t = row.getValue("type");
      const color = typeColor[t as string] ?? "neutral";
      return h(
        UBadge,
        { class: "capitalize", variant: "subtle", color },
        () => t,
      );
    },
  },
  { accessorKey: "description", header: "Description" },
  { accessorKey: "action", header: "Action" },
];
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Settings</h1>
      <UButton
        v-if="can('create settings')"
        icon="i-lucide-plus"
        @click="openCreate"
      >
        Add Setting
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #empty>
        <div class="text-center py-8 text-slate-500">
          No settings found. Click "Add Setting" to create one.
        </div>
      </template>
      <template #action-cell="{ row }">
        <UButton
        class="me-2"
          v-if="can('view settings')"
          size="xs"
          color="info"
          variant="ghost"
          @click="openView(row.original)"
          icon="i-lucide-eye"
        ></UButton>
        <UButton
        class="me-2"
          v-if="can('edit settings')"
          size="xs"
          color="secondary"
          @click="openEdit(row.original)"
          icon="i-lucide-edit"
        ></UButton>
        <UButton
          v-if="can('delete settings')"
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

    <UModal v-model:open="showForm">
      <template #header>
        {{
          viewing ? "View Setting" : editing ? "Edit Setting" : "New Setting"
        }}
      </template>
      <template #body>
        <div class="space-y-4">
          <div class="flex">
            <UFormField label="Key" class="mb-3 me-3">
              <UInput
                v-model="form.key"
                :disabled="viewing || !!editing"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Value" class="mb-3">
              <UInput v-model="form.value" :disabled="viewing" class="w-full" />
            </UFormField>
          </div>
          <UFormField label="Type" class="mb-3">
            <USelect
              v-model="form.type"
              :items="typeOptions"
              :disabled="viewing"
              class="w-full"
            />
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
