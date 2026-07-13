<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
definePageMeta({
  layout: "dashboard",
});

const roles = ref([])
const form = reactive({
  name: "",
  email: "",
  role_id: null,
  status: "active",
});

onMounted(async () => {
  roles.value = (await apiFetch('/v1/roles', { parseJson: true })) as any[]
})

function save() {
  apiFetch('/v1/users', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true
  }).then(() => useRouter().push('/accounts'))
};
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Account</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Name">
        <UInput v-model="form.name" placeholder="Full name" />
      </UFormField>

      <UFormField label="Email">
        <UInput type="email" v-model="form.email" placeholder="email@pmu.gov.ph" />
      </UFormField>

      <UFormField label="Role">
        <USelect
          v-model="form.role_id"
          :items="roles.map(r => ({ label: r.name, value: r.id }))"
          placeholder="Select role"
        />
      </UFormField>

      <UFormField label="Status">
        <USelect
          v-model="form.status"
          :items="['active', 'inactive', 'suspended']"
        />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
