<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'

definePageMeta({
  layout: "dashboard",
});

const route = useRoute()
const id = route.params.id

const form = reactive({
  name: "",
  description: "",
})

onMounted(async () => {
  const item = ((await apiFetch('/v1/stakeholder-types/' + id, { parseJson: true })) as any).data
  Object.assign(form, {
    name: item.name,
    description: item.description ?? "",
  })
})

function save() {
  apiFetch('/v1/stakeholder-types/' + id, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/cms/stakeholder-types'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Stakeholder Type #{{ id }}</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Name">
        <UInput v-model="form.name" />
      </UFormField>

      <UFormField label="Description">
        <UTextarea v-model="form.description" />
      </UFormField>

      <div class="flex gap-2 mt-4">
        <UButton type="submit"> Save </UButton>
        <UButton variant="ghost" to="/cms/stakeholder-types"> Cancel </UButton>
      </div>
    </UForm>
  </div>
</template>
