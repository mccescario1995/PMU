<script setup lang="ts">
import { ref } from "vue";
import * as XLSX from "xlsx";
import { apiFetch } from "~/composables/useApiFetch";

definePageMeta({
  layout: "dashboard",
});

const selectedDate = ref(new Date().toISOString().slice(0, 10));
const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const selectedYear = ref(new Date().getFullYear());
const isLoading = ref(false);

async function fetchTransactions(startDate: string, endDate: string) {
  const allTransactions: any[] = [];
  let page = 1;
  const perPage = 200;

  while (true) {
    const response = await apiFetch<any>(`/v1/transactions?page=${page}&per_page=${perPage}`, {
      parseJson: true,
    });

    let data: any[] = [];
    if (response && typeof response === "object") {
      data = (response as any).data || response;
    }

    if (!data || !Array.isArray(data) || data.length === 0) {
      break;
    }

    const filtered = data.filter((t: any) => {
      const txnDate = t.transaction_date?.slice(0, 10);
      return txnDate && txnDate >= startDate && txnDate <= endDate;
    });

    allTransactions.push(...filtered);

    if (data.length < perPage) {
      break;
    }
    page++;
  }

  return allTransactions;
}

function generateExcel(transactions: any[], type: string, label: string) {
  const exportData = transactions.map((t) => ({
    Date: t.transaction_date?.slice(0, 10) || "",
    Time: t.transaction_date?.slice(11, 19) || "",
    Amount: Number(t.total_amount) || 0,
    "Payment Method": t.payment_method || "",
    "Station ID": t.station_id || "",
    "Vehicle Type": t.vehicle_type || "",
    "Transaction ID": t.id || "",
  }));

  const ws = XLSX.utils.json_to_sheet(exportData);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Transactions");

  // Auto-size columns
  const colWidths = [
    { wch: 12 }, // Date
    { wch: 10 }, // Time
    { wch: 12 }, // Amount
    { wch: 18 }, // Payment Method
    { wch: 12 }, // Station ID
    { wch: 15 }, // Vehicle Type
    { wch: 20 }, // Transaction ID
  ];
  ws["!cols"] = colWidths;

  const buf = XLSX.write(wb, { bookType: "xlsx", type: "array" });
  const blob = new Blob([buf], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `${type}-report-${label}.xlsx`;
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(url);
  document.body.removeChild(a);
}

async function exportDaily() {
  isLoading.value = true;
  try {
    const date = selectedDate.value;
    const transactions = await fetchTransactions(date, date);
    if (transactions.length === 0) {
      alert("No transactions found for this date");
      return;
    }
    generateExcel(transactions, "daily", date);
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
    const [year, monthNum] = month.split("-").map(Number);
    const startDate = `${year}-${String(monthNum).padStart(2, "0")}-01`;
    const endDate = new Date(year, monthNum, 0).toISOString().slice(0, 10); // last day of month
    const transactions = await fetchTransactions(startDate, endDate);
    if (transactions.length === 0) {
      alert("No transactions found for this month");
      return;
    }
    generateExcel(transactions, "monthly", month);
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
    const startDate = `${year}-01-01`;
    const endDate = `${year}-12-31`;
    const transactions = await fetchTransactions(startDate, endDate);
    if (transactions.length === 0) {
      alert("No transactions found for this year");
      return;
    }
    generateExcel(transactions, "yearly", String(year));
  } catch (error: any) {
    console.error("Export failed:", error);
    alert(`Failed to export yearly report: ${error.message || error}`);
  } finally {
    isLoading.value = false;
  }
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