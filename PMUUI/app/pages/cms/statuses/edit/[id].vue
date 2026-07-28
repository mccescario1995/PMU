<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'

definePageMeta({
  layout: "dashboard",
});

const route = useRoute()
const id = route.params.id

const form = reactive({
  name: '',
  type: 'inventory',
  color: 'green',
})

onMounted(async () => {
  const item = ((await apiFetch('/v1/statuses/' + id, { parseJson: true })) as any).data
  Object.assign(form, {
    name: item.name,
    type: item.type,
    color: item.color,
  })
})

function save() {
  apiFetch('/v1/statuses/' + id, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/cms/statuses'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Status #{{ id }}</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Name">
        <UInput v-model="form.name" />
      </UFormField>

      <UFormField label="Type">
        <USelect v-model="form.type" :items="['inventory', 'transaction', 'stakeholder']" />
      </UFormField>

      <UFormField label="Color">
        <USelect v-model="form.color" :items="['green', 'yellow', 'red', 'blue', 'gray']" />
      </UFormField>

      <div class="flex gap-2 mt-4">
        <UButton type="submit"> Save </UButton>
        <UButton variant="ghost" to="/cms/statuses"> Cancel </UButton>
      </div>
    </UForm>
  </div>
</template>
