<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'

definePageMeta({
  layout: "dashboard",
});

const form = reactive({
  item_name: "",
  category: "",
  category_type: "supplies",
  quantity: 0,
  unit: "pcs",
  status: "available",
});

function save() {
  apiFetch('/v1/inventory/items', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/cms/inventory'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Inventory Item</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Item Name">
        <UInput v-model="form.item_name" />
      </UFormField>

      <UFormField label="Category">
        <UInput v-model="form.category" />
      </UFormField>

      <UFormField label="Category Type">
        <USelect
          v-model="form.category_type"
          :items="['equipment', 'materials', 'supplies']"
        />
      </UFormField>

      <UFormField label="Quantity">
        <UInput type="number" v-model="form.quantity" />
      </UFormField>

      <UFormField label="Unit">
        <UInput v-model="form.unit" />
      </UFormField>

      <UFormField label="Status">
        <USelect
          v-model="form.status"
          :items="['available', 'low_stock', 'damaged']"
        />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
