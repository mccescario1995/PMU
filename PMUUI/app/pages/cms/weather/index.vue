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
      `/v1/weather?page=${page}&per_page=${pageSize}`,
      { parseJson: true },
    );
    // Handle Laravel paginated response: { data: [...], meta: { total: N } }
    // or fallback if meta is at top level
    const items = Array.isArray(result?.data) ? result.data : (Array.isArray(result) ? result : []);
    const total = result?.meta?.total ?? result?.total ?? items.length;
    return { data: items, total };
  },
});

const showForm = ref(false);
const editing = ref<any>(null);

const form = reactive({
  weather_date: "",
  rainfall_mm: null as number | null,
  wind_speed: null as number | null,
  temperature: null as number | null,
});

function openCreate() {
  editing.value = null;
  form.weather_date = new Date().toISOString().slice(0, 10);
  form.rainfall_mm = null;
  form.wind_speed = null;
  form.temperature = null;
  showForm.value = true;
}

function openEdit(row: any) {
  editing.value = row;
  form.weather_date = row.weather_date?.slice(0, 10) ?? "";
  form.rainfall_mm = row.rainfall_mm;
  form.wind_speed = row.wind_speed;
  form.temperature = row.temperature;
  showForm.value = true;
}

async function save() {
  try {
    if (editing.value) {
      await apiFetch(`/v1/weather/${editing.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });

      toast.add({ title: "Weather record updated", color: "success" });
    } else {
      await apiFetch("/v1/weather", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
        parseJson: true,
      });

      toast.add({ title: "Weather record created", color: "success" });
    }
    showForm.value = false;
    refresh();
  } catch (e: any) {
    toast.add({ title: "Failed to save weather record", description: e.message ?? "Please try again.", color: "error" });
  }
}

async function remove(row: any) {
  if (!confirm("Delete this weather record?")) return;
  await apiFetch(`/v1/weather/${row.id}`, { method: "DELETE" });
  data.value = data.value.filter((w: any) => w.id !== row.id);
  totalItems.value = Math.max(0, totalItems.value - 1);
}

const weatherLabel = (w: any) => {
  const parts: string[] = [];
  if (w.rainfall_mm !== null && w.rainfall_mm !== undefined)
    parts.push(`${w.rainfall_mm}mm rain`);
  if (w.temperature !== null && w.temperature !== undefined)
    parts.push(`${w.temperature}°C`);
  if (w.wind_speed !== null && w.wind_speed !== undefined)
    parts.push(`${w.wind_speed}km/h wind`);
  return parts.length ? parts.join(", ") : "No weather data";
};

type Weather = {
  id: number;
  weather_date: string;
  rainfall_mm: number | null;
  wind_speed: number | null;
  temperature: number | null;
};

const columns: TableColumn<Weather>[] = [
  { accessorKey: "id", header: "#" },
  {
    accessorKey: "weather_date",
    header: "Date",
    cell: ({ row }) =>
      new Date(row.getValue("weather_date")).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
      }),
  },
  {
    accessorKey: "rainfall_mm",
    header: "Rainfall",
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
  },
  {
    accessorKey: "wind_speed",
    header: "Wind Speed",
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
  },
  {
    accessorKey: "temperature",
    header: "Temperature",
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
  },
  { accessorKey: "action", header: "Action" },
];
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Weather Data</h1>
      <UButton
        v-if="can('create weather')"
        icon="i-lucide-plus"
        @click="openCreate"
      >
        Add Record
      </UButton>
    </div>

    <UTable :data="data" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton
          v-if="can('edit weather')"
          size="xs"
          @click="openEdit(row.original)"
          icon="i-lucide-edit"
        ></UButton>
        <UButton
          v-if="can('delete weather')"
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
        {{ editing ? "Edit Weather Record" : "New Weather Record" }}
      </template>
      <template #body>
        <div class="space-y-4">
          <UFormField label="Date" class="mb-3">
            <UInput v-model="form.weather_date" type="date" class="w-full"/>
          </UFormField>
          <div class="flex gap-4">
          <UFormField label="Rainfall (mm)" class="mb-3 w-full">
            <UInput v-model="form.rainfall_mm" type="number" step="0.1" class="w-full"/>
          </UFormField>
          <UFormField label="Wind Speed (km/h)" class="mb-3 w-full">
            <UInput v-model="form.wind_speed" type="number" step="0.1" class="w-full"/>
          </UFormField>
          </div>
          <UFormField label="Temperature (°C)" class="mb-3">
            <UInput v-model="form.temperature" type="number" step="0.1" class="w-full"/>
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
