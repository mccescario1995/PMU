<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'
import { ref } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const form = reactive({
  name: "",
  type: "",
  stakeholder_type_id: null,
  contact_no: "",
  email: "",
  address: "",
  status: "active",
});

const types = ref<any[]>([])
const typesLoaded = ref(false)

async function loadTypes() {
  if (typesLoaded.value) return;
  const fetched = ((await apiFetch('/v1/stakeholder-types', { parseJson: true })) as any).data;
  const existing = new Map(types.value.map((t: any) => [t.id, t]));
  for (const t of fetched) {
    if (!existing.has(t.id)) {
      existing.set(t.id, t);
    }
  }
  types.value = Array.from(existing.values());
  typesLoaded.value = true;
}

function save() {
  apiFetch('/v1/stakeholders', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true
  }).then(() => useRouter().push('/cms/stakeholders'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Stakeholder</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Name">
        <UInput v-model="form.name" />
      </UFormField>

      <UFormField label="Type">
        <USelect v-model="form.type" :items="['buyer', 'broker', 'renter']" />
      </UFormField>

      <UFormField label="Stakeholder Type">
        <USelect v-model="form.stakeholder_type_id" :items="types" value-key="id" label-key="name" @update:open="(isOpen: boolean) => isOpen && loadTypes()" />
      </UFormField>

      <UFormField label="Contact">
        <UInput v-model="form.contact_no" />
      </UFormField>

      <UFormField label="Email">
        <UInput type="email" v-model="form.email" />
      </UFormField>

      <UFormField label="Address">
        <UInput v-model="form.address" />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
