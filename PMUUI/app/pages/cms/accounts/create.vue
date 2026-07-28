<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
definePageMeta({
  layout: "dashboard",
});

const roles = ref([])
const form = reactive({
  name: "",
  email: "",
  roles: [] as string[],
  status: "active",
  password: "",
});

onMounted(async () => {
  roles.value = ((await apiFetch('/v1/roles', { parseJson: true })) as any).data
})

function save() {
  apiFetch('/v1/users', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/cms/accounts'))
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

      <UFormField label="Password">
        <UInput v-model="form.password" type="password" />
      </UFormField>

      <UFormField label="Roles">
        <USelect
          v-model="form.roles"
          multiple
          :items="roles.map((r: any) => ({ label: r.name, value: r.name }))"
          placeholder="Select roles"
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
