<script setup>
definePageMeta({
  layout: "dashboard",
});

const form = reactive({
  orgName: "PMU Pasacao",
  address: "Pasacao, Camarines Sur",
  contact: "09391234567",
  email: "admin@pmu.gov.ph",
  unloadingFee: 50,
  facilityFee: 30,
  lowStockThreshold: 25,
});

function save() {
  console.log(form);
}
</script>

<template>
  <div class="p-6 max-w-2xl space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Settings</h1>
      <p class="text-slate-500">Manage port profile and fee configuration.</p>
    </div>

    <UForm :state="form" @submit="save" class="space-y-4">
      <UCard>
        <template #header> Organization </template>
        <UFormField label="Organization Name">
          <UInput v-model="form.orgName" />
        </UFormField>
        <UFormField label="Address">
          <UInput v-model="form.address" />
        </UFormField>
        <UFormField label="Contact Number">
          <UInput v-model="form.contact" />
        </UFormField>
        <UFormField label="Email">
          <UInput type="email" v-model="form.email" />
        </UFormField>
      </UCard>

      <UCard>
        <template #header> Fee Configuration </template>
        <UFormField label="Fish Unloading Fee (PHP/kg)">
          <UInput type="number" v-model="form.unloadingFee" />
        </UFormField>
        <UFormField label="Facility Usage Fee (PHP)">
          <UInput type="number" v-model="form.facilityFee" />
        </UFormField>
        <UFormField label="Low Stock Threshold">
          <UInput type="number" v-model="form.lowStockThreshold" />
        </UFormField>
      </UCard>

      <UButton type="submit"> Save Changes </UButton>
    </UForm>
  </div>
</template>
