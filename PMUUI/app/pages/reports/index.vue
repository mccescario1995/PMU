<script setup lang="ts">
import { ref } from "vue";
import { apiFetch } from "~/composables/useApiFetch";

definePageMeta({
  layout: "dashboard",
});

const selectedDate = ref(new Date().toISOString().slice(0, 10));
const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const selectedYear = ref(new Date().getFullYear());
const isLoading = ref(false);

async function exportDaily() {
  isLoading.value = true;
  try {
    const date = selectedDate.value;
    const endpoint = `/v1/reports/transaction/xlsx?type=daily&date=${date}`;
    await downloadReport(endpoint, "daily", date);
  } catch (error: any) {
    console.error("Export failed:", error);
    alert(`Failed to export daily report: ${error.message || error}`);
  } finally {
    isLoading.value = false;
  }
}

async function exportMonthly() {
  isLoading.value = true;
  try {
    const month = selectedMonth.value; // format: YYYY-MM
    const endpoint = `/v1/reports/transaction/xlsx?type=monthly&month=${month}`;
    await downloadReport(endpoint, "monthly", month);
  } catch (error: any) {
    console.error("Export failed:", error);
    alert(`Failed to export monthly report: ${error.message || error}`);
  } finally {
    isLoading.value = false;
  }
}

async function exportYearly() {
  isLoading.value = true;
  try {
    const year = selectedYear.value;
    const endpoint = `/v1/reports/transaction/xlsx?type=yearly&year=${year}`;
    await downloadReport(endpoint, "yearly", String(year));
  } catch (error: any) {
    console.error("Export failed:", error);
    alert(`Failed to export yearly report: ${error.message || error}`);
  } finally {
    isLoading.value = false;
  }
}

async function downloadReport(endpoint: string, type: string, dateMonthYear: string) {
  const config = useRuntimeConfig();
  const baseURL = config.public.apiBase + "/api";
  const fullUrl = `${baseURL}${endpoint}`;

  const token = useAuth().accessToken.value;
  const headers: HeadersInit = {
    Accept: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  };
  if (token) {
    headers["Authorization"] = `Bearer ${token}`;
  }

  const response = await fetch(fullUrl, {
    method: "GET",
    headers,
  });

  if (!response.ok) {
    const text = await response.text();
    throw new Error(`Export failed: ${response.status} ${response.statusText} - ${text}`);
  }

  const blob = await response.blob();
  const downloadUrl = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = downloadUrl;
  a.download = `${type}-report-${dateMonthYear}.xlsx`;
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(downloadUrl);
  document.body.removeChild(a);
}
</script>

<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Reports</h1>
      <p class="text-slate-500">Generate and download transaction records reports.</p>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
      <!-- Daily -->
      <UCard>
        <template #header>
          <div class="flex items-center gap-3">
            <UIcon name="i-lucide-calendar-1" class="size-6 text-[#2E4C6C]" />
            <h2 class="font-semibold">Daily Report</h2>
          </div>
        </template>
        
        <div class="space-y-3">
          <UInput v-model="selectedDate" type="date" class="w-full" />
          <UButton class="w-full" icon="i-lucide-file-columns" @click="exportDaily" :loading="isLoading" :disabled="isLoading">
            Export Excel (Detail)
          </UButton>
        </div>
      </UCard>

      <!-- Monthly -->
      <UCard>
        <template #header>
          <div class="flex items-center gap-3">
            <UIcon name="i-lucide-calendar-range" class="size-6 text-[#2E4C6C]" />
            <h2 class="font-semibold">Monthly Report</h2>
          </div>
        </template>
        
        <div class="space-y-3">
          <UInput v-model="selectedMonth" type="month" class="w-full" />
          <UButton class="w-full" icon="i-lucide-file-columns" @click="exportMonthly" :loading="isLoading" :disabled="isLoading">
            Export Excel (Detail)
          </UButton>
        </div>
      </UCard>

      <!-- Yearly -->
      <UCard>
        <template #header>
          <div class="flex items-center gap-3">
            <UIcon name="i-lucide-calendars" class="size-6 text-[#2E4C6C]" />
            <h2 class="font-semibold">Yearly Report</h2>
          </div>
        </template>
        <div class="space-y-3">
          <UInput v-model="selectedYear" type="number" placeholder="YYYY" class="w-full" />
          <UButton class="w-full" icon="i-lucide-file-columns" @click="exportYearly" :loading="isLoading" :disabled="isLoading">
            Export Excel (Detail)
          </UButton>
        </div>
      </UCard>
    </div>
  </div>
</template>