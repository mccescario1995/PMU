<script setup lang="ts">
import { ref } from "vue";

definePageMeta({
  layout: "dashboard",
});

const selectedDate = ref(new Date().toISOString().slice(0, 10));
const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const selectedYear = ref(new Date().getFullYear());

function exportDaily() {
  downloadReport("/v1/reports/transaction/xlsx", "daily", selectedDate.value);
}

function exportMonthly() {
  downloadReport("/v1/reports/transaction/xlsx", "monthly", selectedMonth.value);
}

function exportYearly() {
  downloadReport("/v1/reports/transaction/xlsx", "yearly", selectedYear.value);
}

function downloadReport(url: string, type: string, dateMonthYear: string) {
  const params = new URLSearchParams();
  params.append("type", type);
  if (type === "daily") {
    params.append("date", dateMonthYear);
  } else if (type === "monthly") {
    params.append("month", dateMonthYear);
  } else if (type === "yearly") {
    params.append("year", dateMonthYear);
  }
  const fullUrl = `${url}?${params.toString()}`;

  fetch(fullUrl, {
    method: "GET",
    headers: {
      Accept: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    },
  }).then((response) => {
    if (!response.ok) {
      return response.text().then((text) => {
        throw new Error(`Export failed: ${response.status} ${response.statusText} - ${text}`);
      });
    }
    return response.blob();
  }).then((blob) => {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `${type}-report.xlsx`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  }).catch((error) => {
    console.error("Export failed:", error);
    alert(`Failed to export report: ${error.message}`);
  });
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
          <UButton class="w-full" icon="i-lucide-file-columns" @click="exportDaily">
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
          <UButton class="w-full" icon="i-lucide-file-columns" @click="exportMonthly">
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
          <UButton class="w-full" icon="i-lucide-file-columns" @click="exportYearly">
            Export Excel (Detail)
          </UButton>
        </div>
      </UCard>
    </div>
  </div>
</template>