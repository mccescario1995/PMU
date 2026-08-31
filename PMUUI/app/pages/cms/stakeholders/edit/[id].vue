<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'
import { ref } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

const form = reactive({
  id: Number(id),
  name: "",
  type: "",
  stakeholder_type_id: null,
  contact_no: "",
  email: "",
  address: "",
  status: "active",
});

const types = ref<any[]>([])

onMounted(async () => {
  const s = (((await apiFetch('/v1/stakeholders/' + id, { parseJson: true }))) as any).data;
  Object.assign(form, {
    id: s.id,
    name: s.name,
    type: s.type,
    stakeholder_type_id: s.stakeholder_type_id,
    contact_no: s.contact_no,
    email: s.email,
    address: s.address,
    status: s.status,
  });

  if (s.stakeholder_type) {
    types.value = [{ id: s.stakeholder_type.id, name: s.stakeholder_type.name }];
  }

  const allTypes = ((await apiFetch('/v1/stakeholder-types', { parseJson: true })) as any).data;
  const existing = new Map(types.value.map((t: any) => [t.id, t]));
  for (const t of allTypes) {
    if (!existing.has(t.id)) {
      existing.set(t.id, t);
    }
  }
  types.value = Array.from(existing.values());
});

function save() {
  apiFetch('/v1/stakeholders/' + id, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true
  }).then(() => useRouter().push('/cms/stakeholders'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Stakeholder #{{ form.id }}</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Name">
        <UInput v-model="form.name" />
      </UFormField>

      <UFormField label="Type">
        <USelect v-model="form.type" :items="['buyer', 'broker', 'renter']" />
      </UFormField>

      <UFormField label="Stakeholder Type">
        <USelect v-model="form.stakeholder_type_id" :items="types" value-key="id" label-key="name" />
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
