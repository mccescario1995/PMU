<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

const stakeholders = ref([]);
const feeTypes = ref([]);

const form = reactive({
  id: Number(id),
  stakeholder_id: null,
  items: [] as any[],
  transaction_date: "",
  status: "pending",
  remarks: "",
});

onMounted(async () => {
  stakeholders.value = ((await apiFetch('/v1/stakeholders', { parseJson: true })) as any).data
  feeTypes.value = ((await apiFetch('/v1/fee-types', { parseJson: true })) as any).data
  const t = (await apiFetch('/v1/transactions/' + id, { parseJson: true })) as any;
  
  form.items = (t.items ?? []).map((item: any) => ({
    fee_type_id: item.fee_type_id,
    quantity: item.quantity,
    unit_price: item.unit_price,
  }));
  
  if (form.items.length === 0) {
    form.items.push({
      fee_type_id: null,
      quantity: 1,
      unit_price: 0,
    });
  }

  Object.assign(form, {
    id: t.id,
    stakeholder_id: t.stakeholder_id,
    transaction_date: (t.transaction_date ?? "").toString().slice(0, 10),
    status: t.status,
    remarks: t.remarks ?? "",
  });
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
    const feeType = feeTypes.value.find(f => f.id === item.fee_type_id);
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

function save() {
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
    remarks: form.remarks,
    total_amount: calculateTotal(),
    items: items,
  };

  apiFetch('/v1/transactions/' + id, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
    parseJson: true
  }).then(() => useRouter().push('/transactions'));
}
</script>

<template>
  <div class="p-6 max-w-3xl">
    <h1 class="text-2xl font-bold mb-5">Edit Transaction #{{ form.id }}</h1>

    <UForm @submit="save">
      <UFormField label="Stakeholder">
        <USelect
          v-model="form.stakeholder_id"
          :items="stakeholders.map(s => ({ label: s.name, value: s.id }))"
          placeholder="Select stakeholder"
        />
      </UFormField>

      <UFormField label="Transaction Items" class="mb-4">
        <div v-for="(item, index) in form.items" :key="index" class="flex gap-2 mb-2 items-end">
          <USelect
            v-model="item.fee_type_id"
            :items="feeTypes.map(f => ({ label: f.fee_name, value: f.id }))"
            placeholder="Fee Type"
            class="w-48"
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

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
