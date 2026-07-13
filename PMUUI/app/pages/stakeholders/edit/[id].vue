<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

const form = reactive({
  id: Number(id),
  name: "",
  type: "",
  contact_no: "",
  email: "",
  address: "",
  status: "active",
});

onMounted(async () => {
  const s = ((await apiFetch('/v1/stakeholders/' + id, { parseJson: true }))) as any;
  Object.assign(form, {
    id: s.id,
    name: s.name,
    type: s.type,
    contact_no: s.contact_no,
    email: s.email,
    address: s.address,
    status: s.status,
  });
})

function save() {
  apiFetch('/v1/stakeholders/' + id, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true
  }).then(() => useRouter().push('/stakeholders'))
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
