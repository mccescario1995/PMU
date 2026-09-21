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

async function fetchReport(type: string, dateMonthYear: string) {
  let endpoint = "";
  if (type === "daily") {
    endpoint = `/v1/reports/daily?date=${dateMonthYear}`;
  } else if (type === "monthly") {
    endpoint = `/v1/reports/monthly?month=${dateMonthYear}`;
  } else if (type === "yearly") {
    endpoint = `/v1/reports/yearly?year=${dateMonthYear}`;
  }
  
  const response = await apiFetch<any>(endpoint, { parseJson: true });
  return (response as any).transactions ?? [];
}

function getFeeTypes(transactions: any[]): string[] {
  const feeTypeSet = new Set<string>();
  transactions.forEach((t) => {
    t.items?.forEach((item: any) => {
      if (item.fee_type?.fee_name) {
        feeTypeSet.add(item.fee_type.fee_name);
      }
    });
  });
  return Array.from(feeTypeSet).sort();
}

function generateExcel(transactions: any[], type: string, label: string) {
  const feeTypes = getFeeTypes(transactions);
  
  // Build header row
  const headers = ["Date", "Time", "Transaction ID", "Stakeholder", "Status", ...feeTypes, "Total"];
  
  // Build data rows
  const rows = transactions.map((t) => {
    const row: Record<string, any> = {
      Date: t.transaction_date?.slice(0, 10) || "",
      Time: t.transaction_date?.slice(11, 19) || "",
      "Transaction ID": t.id || "",
      Stakeholder: t.stakeholder?.name || "",
      Status: t.status || "",
    };
    
    // Initialize fee type columns to 0
    feeTypes.forEach(ft => row[ft] = 0);
    
    // Fill in fee type amounts from items
    t.items?.forEach((item: any) => {
      const feeName = item.fee_type?.fee_name;
      if (feeName && feeTypes.includes(feeName)) {
        row[feeName] = Number(item.subtotal) || 0;
      }
    });
    
    row.Total = Number(t.total_amount) || 0;
    
    // Convert to array in header order
    return headers.map(h => row[h]);
  });
  
  // Create worksheet
  const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Transactions");

  // Auto-size columns
  const colWidths = headers.map((h, i) => {
    let maxLen = h.length;
    rows.forEach(r => {
      const val = r[i]?.toString() || "";
      if (val.length > maxLen) maxLen = val.length;
    });
    return { wch: Math.min(maxLen + 2, 30) };
  });
  ws["!cols"] = colWidths;

  // Write to buffer and download
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
    const transactions = await fetchReport("daily", date);
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
    const transactions = await fetchReport("monthly", month);
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
    const transactions = await fetchReport("yearly", String(year));
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