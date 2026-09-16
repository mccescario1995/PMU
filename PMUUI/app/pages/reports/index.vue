<script setup lang="ts">
import { ref } from "vue";

definePageMeta({
  layout: "dashboard",
});

const selectedDate = ref(new Date().toISOString().slice(0, 10));
const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const selectedYear = ref(new Date().getFullYear());

function exportDaily() {
  window.open(
    `/v1/reports/transaction/xlsx?type=daily&date=${selectedDate.value}`,
    "_blank"
  );
}

function exportMonthly() {
  window.open(
    `/v1/reports/transaction/xlsx?type=monthly&month=${selectedMonth.value}`,
    "_blank"
  );
}

function exportYearly() {
  window.open(
    `/v1/reports/transaction/xlsx?type=yearly&year=${selectedYear.value}`,
    "_blank"
  );
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