<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, watch, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from '#imports'

definePageMeta({
  layout: "dashboard",
});

const toast = useToast();
const router = useRouter();

const stakeholders = ref([]);
const feeTypes = ref([]);
const saving = ref(false);
const stakeholdersLoaded = ref(false);
const feeTypesLoaded = ref(false);

async function loadStakeholders() {
  if (stakeholdersLoaded.value) return;
  stakeholders.value = ((await apiFetch('/v1/stakeholders', { parseJson: true })) as any).data;
  stakeholdersLoaded.value = true;
}

async function loadFeeTypes() {
  if (feeTypesLoaded.value) return;
  feeTypes.value = ((await apiFetch('/v1/fee-types', { parseJson: true })) as any).data;
  feeTypesLoaded.value = true;
}

const form = reactive({
  stakeholder_id: null,
  items: [
    {
      fee_type_id: null,
      quantity: 1,
      unit_price: 0,
    }
  ],
  transaction_date: "",
  status: "pending",
});

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

watch(() => form.items, (newItems) => {
  newItems.forEach((item) => {
    const feeType = feeTypes.value.find((f: any) => f.id === item.fee_type_id);
    if (feeType && item.unit_price === 0) {
      item.unit_price = feeType.base_rate;
    }
  });
}, { deep: true });

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
    const items = form.items.map(item => ({
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

    await apiFetch('/v1/transactions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      parseJson: true
    });

    toast.add({ title: 'Transaction created', description: 'The transaction was saved successfully.', color: 'success' });
    router.push('/cms/transactions');
  } catch (e: any) {
    toast.add({ title: 'Failed to create transaction', description: e.message ?? 'Please try again.', color: 'error' });
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="p-6 max-w-3xl">
    <h1 class="text-2xl font-bold mb-5">Create Transaction</h1>

    <UForm @submit="save">
      <UFormField label="Stakeholder">
        <USelect
          v-model="form.stakeholder_id"
          :items="stakeholders.map((s: any) => ({ label: s.name, value: s.id }))"
          placeholder="Select stakeholder"
          @update:open="(isOpen: boolean) => isOpen && loadStakeholders()"
        />
      </UFormField>

      <UFormField label="Transaction Items" class="mb-4">
        <div v-for="(item, index) in form.items" :key="index" class="flex gap-2 mb-2 items-end">
          <USelect
            v-model="item.fee_type_id"
            :items="feeTypes.map((f: any) => ({ label: f.fee_name, value: f.id }))"
            placeholder="Fee Type"
            class="w-48"
            @update:open="(isOpen: boolean) => isOpen && loadFeeTypes()"
          />
          <UInputNumber
            v-model="item.quantity"
            :min="1"
            placeholder="Qty"
            class="w-24"
          />
          <UInputNumber
            v-model="item.unit_price"
            :step="0.01"
            :min="0"
            placeholder="Unit Price"
            class="w-36"
          />
          <span class="w-24 font-mono text-right text-primary">
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

      <UFormField label="Total Amount">
        <span class="text-2xl font-bold text-success">
          {{ formatCurrency(calculateTotal()) }}
        </span>
      </UFormField>

      <UFormField label="Date">
        <UInput type="date" v-model="form.transaction_date" />
      </UFormField>

      <UFormField label="Status">
        <USelect v-model="form.status" :items="['pending', 'completed', 'cancelled']" />
      </UFormField>

      <UButton type="submit" :loading="saving"> Save </UButton>
    </UForm>
  </div>
</template>
