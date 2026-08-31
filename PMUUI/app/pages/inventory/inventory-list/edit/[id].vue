<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import type { SelectItem } from "@nuxt/ui";

definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

const form = reactive({
  item_name: "",
  category: "",
  category_type: "supplies",
  quantity: 0,
  unit: "pcs",
  status: "available",
});

onMounted(async () => {
  const item = await apiFetch(`/v1/inventory/items/${id}`, { parseJson: true });
  Object.assign(form, {
    item_name: item.data.item_name,
    category: item.data.category,
    category_type: item.data.category_type ?? "supplies",
    quantity: item.data.quantity,
    unit: item.data.unit ?? "pcs",
    status: item.data.status,
  });
});

function save() {
  apiFetch(`/v1/inventory/items/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push("/inventory/inventory-list"));
}

const categoryType = ref<SelectItem[]>([
  {
    label: "Equipment",
    value: "equipment",
  },
  {
    label: "Materials",
    value: "materials",
  },
  {
    label: "Supplies",
    value: "supplies",
  },
]);

const status = ref<SelectItem[]>([
  {
    label: "Available",
    value: "available",
  },
  {
    label: "Low Stock",
    value: "low_stock",
  },
  {
    label: "Damaged",
    value: "damaged",
  },
]);
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Inventory Item #{{ id }}</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Item Name" class="mb-3">
        <UInput v-model="form.item_name" class="w-full" />
      </UFormField>

      <UFormField label="Category" class="mb-3">
        <UInput v-model="form.category" class="capitalize w-full" />
      </UFormField>

      <UFormField label="Category Type" class="mb-3">
        <USelect
          class="w-full"
          v-model="form.category_type"
          :items="categoryType"
        />
      </UFormField>

      <div class="flex">
        <UFormField label="Quantity" class="mb-3 w-full mr-3">
          <UInput type="number" v-model="form.quantity" class="w-full" />
        </UFormField>

        <UFormField label="Unit" class="mb-3">
          <UInput v-model="form.unit" class="w-full" />
        </UFormField>
      </div>

      <UFormField label="Status" class="mb-3">
        <USelect
          v-model="form.status"
          :items="status"
          class="w-full"
        />
      </UFormField>

      <div class="flex gap-2 mt-4">
        <UButton type="submit"> Save </UButton>
        <UButton variant="ghost" to="/inventory/inventory-list">
          Cancel
        </UButton>
      </div>
    </UForm>
  </div>
</template>
