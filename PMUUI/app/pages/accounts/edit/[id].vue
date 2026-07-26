<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";

definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

// Explicitly typing the references gets rid of 'as any' bugs completely
interface Role {
  id: number | string;
  name: string;
}

const roles = ref<Role[]>([]);

const form = reactive({
  id: Number(id),
  name: "",
  email: "",
  roles: [] as string[],
  status: "active",
});

onMounted(async () => {
  const rolesResponse = await apiFetch("/v1/roles", { parseJson: true });
  roles.value = rolesResponse as Role[];

  const user: any = await apiFetch("/v1/users/" + id, { parseJson: true });

  Object.assign(form, {
    id: user.data.id,
    name: user.data.name,
    email: user.data.email,
    roles: user.data.roles ?? [],
    status: user.data.status,
  });
});

function save() {
  apiFetch("/v1/users/" + id, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push("/accounts"));
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Account #{{ form.id }}</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Name">
        <UInput v-model="form.name" placeholder="Full name" />
      </UFormField>

      <UFormField label="Email">
        <UInput type="email" v-model="form.email" placeholder="email@pmu.gov.ph" />
      </UFormField>

      <UFormField label="Roles">
        <USelect
          v-model="form.roles"
          multiple
          :items="roles.map(r => ({ label: r.name, value: r.name }))"
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