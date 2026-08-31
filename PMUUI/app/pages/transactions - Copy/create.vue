<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, watch, ref } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "#imports";
import type { SelectItem } from "@nuxt/ui";

definePageMeta({
  layout: "dashboard",
});

const toast = useToast();
const router = useRouter();

const stakeholders = ref([]);
const feeTypes = ref([]);
const saving = ref(false);

const form = reactive({
  stakeholder_id: null,
  items: [
    {
      fee_type_id: null,
      quantity: 1,
      unit_price: 0,
    },
  ],
  transaction_date: "",
  status: "pending",
});

const stakeholdersLoaded = ref(false);
const feeTypesLoaded = ref(false);

async function loadStakeholders() {
  if (stakeholdersLoaded.value) return;
  stakeholders.value = (
    (await apiFetch("/v1/stakeholders", { parseJson: true })) as any
  ).data;
  stakeholdersLoaded.value = true;
}

async function loadFeeTypes() {
  if (feeTypesLoaded.value) return;
  feeTypes.value = (
    (await apiFetch("/v1/fee-types", { parseJson: true })) as any
  ).data;
  feeTypesLoaded.value = true;
}

function addItem() {
  form.items.push({
    fee_type_id: null,
    quantity: 1,
    unit_price: 0,
  });
}

function removeItem(index: number) {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  }
}

watch(
  () => form.items,
  (newItems) => {
    newItems.forEach((item) => {
      const feeType = feeTypes.value.find((f) => f.id === item.fee_type_id);
      if (feeType && item.unit_price === 0) {
        item.unit_price = feeType.base_rate;
      }
    });
  },
  { deep: true },
);

function calculateSubtotal(item: any) {
  return Number(item.quantity || 0) * Number(item.unit_price || 0);
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "PHP",
  }).format(value);
}

function calculateTotal() {
  return form.items.reduce((sum, item) => sum + calculateSubtotal(item), 0);
}

async function save() {
  saving.value = true;
  try {
    const items = form.items.map((item) => ({
      fee_type_id: item.fee_type_id,
      quantity: item.quantity,
      unit_price: item.unit_price,
      subtotal: calculateSubtotal(item),
    }));

    const payload = {
      stakeholder_id: form.stakeholder_id,
      transaction_date: form.transaction_date,
      status: form.status,
      remarks: "",
      total_amount: calculateTotal(),
      items: items,
    };

    await apiFetch("/v1/transactions", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
      parseJson: true,
    });

    toast.add({
      title: "Transaction created",
      description: "The transaction was saved successfully.",
      color: "success",
    });
    router.push("/transactions");
  } catch (e: any) {
    toast.add({
      title: "Failed to create transaction",
      description: e.message ?? "Please try again.",
      color: "error",
    });
  } finally {
    saving.value = false;
  }
}

const items = ref<SelectItem[]>([
  {
    label: "Pending",
    value: "pending",
  },
  {
    label: "Completed",
    value: "completed",
  },
  {
    label: "Cancelled",
    value: "cancelled",
  },
]);
</script>

<template>
  <div class="p-6 max-w-3xl">
    <h1 class="text-2xl font-bold mb-5">Create Transaction</h1>

    <UForm @submit="save">
      <UFormField label="Stakeholder" class="mb-3">
        <USelect
          class="w-full"
          v-model="form.stakeholder_id"
          :items="stakeholders.map((s) => ({ label: s.name, value: s.id }))"
          placeholder="Select stakeholder"
          @update:open="(isOpen: boolean) => isOpen && loadStakeholders()"
        />
      </UFormField>

      <UFormField label="Transaction Items" class="mb-4">
        <div
          v-for="(item, index) in form.items"
          :key="index"
          class="flex gap-2 mb-2 items-end"
        >
          <USelect
            v-model="item.fee_type_id"
            :items="feeTypes.map((f) => ({ label: f.fee_name, value: f.id }))"
            placeholder="Fee Type"
            class="w-[50%]"
            @update:open="(isOpen: boolean) => isOpen && loadFeeTypes()"
          />
          <UInputNumber
            v-model="item.quantity"
            :min="1"
            placeholder="Qty"
            class="w-[20%]"
          />
          <UInputNumber
            v-model="item.unit_price"
            :step="0.01"
            :min="0"
            placeholder="Unit Price"
            class="w-[20%]"
          />
          <span class="w-auto font-mono text-right text-primary">
            {{ formatCurrency(calculateSubtotal(item)) }}
          </span>
          <UButton
            v-if="form.items.length > 1"
            size="xs"
            color="error"
            variant="outline"
            icon="i-lucide-trash-2"
            @click="removeItem(index)"
          />
        </div>
        <UButton
          type="button"
          variant="outline"
          icon="i-lucide-plus"
          class="w-fit"
          @click="addItem"
        >
          Add Item
        </UButton>
      </UFormField>

      <UFormField label="Total Amount" class="mb-3">
        <span class="text-2xl font-bold text-success">
          {{ formatCurrency(calculateTotal()) }}
        </span>
      </UFormField>

      <div class="flex flex-row">
        <UFormField label="Date" class="mb-3 me-3 w-full">
          <UInput type="date" v-model="form.transaction_date" class="w-full"/>
        </UFormField>

        <UFormField label="Status" class="mb-3 w-full">
          <USelect v-model="form.status" :items="items"  class="w-full"/>
        </UFormField>
      </div>
      <UButton type="submit" :loading="saving"> Save </UButton>
    </UForm>
  </div>
</template>
