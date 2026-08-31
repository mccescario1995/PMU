<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted } from "vue";
import { ref } from "vue";

definePageMeta({
  layout: "dashboard",
});

const types = ref<any[]>([]);

const form = reactive({
  name: "",
  stakeholder_type_id: 1,
  contact_no: "",
  email: "",
  address: "",
  status: "active",
});

function save() {
  apiFetch("/v1/stakeholders", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(form),
    parseJson: true,
  }).then(() => useRouter().push("/stakeholders"));
}

onMounted(async () => {
  const allTypes = (
    (await apiFetch("/v1/stakeholder-types", { parseJson: true })) as any
  ).data;
  const existing = new Map(types.value.map((t: any) => [t.id, t]));
  for (const t of allTypes) {
    if (!existing.has(t.id)) {
      existing.set(t.id, t);
    }
  }
  types.value = Array.from(existing.values());
});
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Stakeholder</h1>

    <UForm :state="form" @submit="save" class="space-y-4">
      <UFormField label="Name" class="mb-3">
        <UInput v-model="form.name" class="w-full" />
      </UFormField>

      <UFormField label="Stakeholder Type" class="mb-3">
        <USelect
          v-model="form.stakeholder_type_id"
          :items="types"
          value-key="id"
          label-key="name"
          class="w-full"
        />
      </UFormField>

      <UFormField label="Contact" class="mb-3">
        <UInput v-model="form.contact_no" class="w-full" />
      </UFormField>

      <UFormField label="Email" class="mb-3">
        <UInput type="email" v-model="form.email" class="w-full" />
      </UFormField>

      <UFormField label="Address" class="mb-3">
        <UInput v-model="form.address" class="w-full" />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
