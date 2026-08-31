<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted } from "vue";
import { ref } from "vue";

definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

const form = reactive({
  id: Number(id),
  name: "",
  stakeholder_type_id: null,
  contact_no: "",
  email: "",
  address: "",
  status: "active",
});

const types = ref<any[]>([]);

onMounted(async () => {
  const s = (
    (await apiFetch("/v1/stakeholders/" + id, { parseJson: true })) as any
  ).data;
  Object.assign(form, {
    id: s.id,
    name: s.name,
    stakeholder_type_id: s.stakeholder_type_id,
    contact_no: s.contact_no,
    email: s.email,
    address: s.address,
    status: s.status,
  });

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

function save() {
  apiFetch("/v1/stakeholders/" + id, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(form),
    parseJson: true,
  }).then(() => useRouter().push("/stakeholders"));
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Stakeholder #{{ form.id }}</h1>

    <UForm :state="form" @submit="save" class="space-y-4">
      <UFormField label="Name" class="mb-3">
        <UInput v-model="form.name" class="w-full"/>
      </UFormField>

      <UFormField label="Stakeholder Type" class="mb-3">
        <USelect
          class="w-full"
          v-model="form.stakeholder_type_id"
          :items="types"
          value-key="id"
          label-key="name"
        />
      </UFormField>

      <UFormField label="Contact" class="mb-3">
        <UInput v-model="form.contact_no" class="w-full"/>
      </UFormField>

      <UFormField label="Email" class="mb-3">
        <UInput type="email" v-model="form.email" class="w-full"/>
      </UFormField>

      <UFormField label="Address" class="mb-3">
        <UInput v-model="form.address" class="w-full"/>
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
