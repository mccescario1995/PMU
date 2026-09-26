<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted } from "vue";
import { ref, reactive } from "vue";

definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

const form = reactive({
  id: Number(id),
  name: "",
  official_receipt: "",
  stakeholder_type_id: null as number | null,
  status: "active",
});

const errors = reactive({
  name: "",
  official_receipt: "",
  stakeholder_type_id: "",
  status: "",
});

const types = ref<any[]>([]);

function validateForm(): boolean {
  let isValid = true;

  if (!form.name.trim()) {
    errors.name = "Name is required";
    isValid = false;
  } else {
    errors.name = "";
  }

  if (!form.official_receipt.trim()) {
    errors.official_receipt = "Official receipt is required";
    isValid = false;
  } else if (!/^\d{7}$/.test(form.official_receipt.trim())) {
    errors.official_receipt = "Official receipt must be exactly 7 digits";
    isValid = false;
  } else {
    errors.official_receipt = "";
  }

  if (!form.stakeholder_type_id) {
    errors.stakeholder_type_id = "Stakeholder type is required";
    isValid = false;
  } else {
    errors.stakeholder_type_id = "";
  }

  if (!form.status) {
    errors.status = "Status is required";
    isValid = false;
  } else {
    errors.status = "";
  }

  return isValid;
}

function clearError(field: keyof typeof errors) {
  errors[field] = "";
}

onMounted(async () => {
  const s = (
    (await apiFetch("/v1/stakeholders/" + id, { parseJson: true })) as any
  ).data;
  Object.assign(form, {
    id: s.id,
    name: s.name,
    official_receipt: s.official_receipt,
    stakeholder_type_id: s.stakeholder_type_id,
    status: s.status,
  });

  const allTypes = (
    (await apiFetch("/v1/dropdowns/stakeholder-types", { parseJson: true })) as any
  ).data;
  const existing = new Map(types.value.map((t: any) => [t.id, t]));
  for (const t of allTypes) {
    if (!existing.has(t.id)) {
      existing.set(t.id, t);
    }
  }
  types.value = Array.from(existing.values());
});

async function save() {
  if (!validateForm()) return;
  try {
    await apiFetch("/v1/stakeholders/" + id, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(form),
      parseJson: true,
    });
    useRouter().push("/stakeholders");
  } catch (e: any) {
    console.error(e);
  }
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Stakeholder #{{ form.id }}</h1>

    <UForm @submit="save" class="space-y-4">
      <UFormField label="Name" class="mb-3" :error="errors.name" required>
        <UInput v-model="form.name" class="w-full" @input="clearError('name')"/>
      </UFormField>

      <UFormField label="Official Receipt" class="mb-3" :error="errors.official_receipt" required>
        <UInput
          v-model="form.official_receipt"
          class="w-full"
          type="text"
          inputmode="numeric"
          maxlength="7"
          @input="form.official_receipt = form.official_receipt.replace(/\D/g, '').slice(0, 7); clearError('official_receipt')"
          placeholder="7 digits only"
        />
        <template #description>Exactly 7 digits</template>
      </UFormField>

      <UFormField label="Stakeholder Type" class="mb-3" :error="errors.stakeholder_type_id" required>
        <USelect
          class="w-full"
          v-model="form.stakeholder_type_id"
          :items="types"
          value-key="id"
          label-key="name"
          @change="clearError('stakeholder_type_id')"
        />
      </UFormField>

      <UFormField label="Status" class="mb-3" :error="errors.status" required>
        <USelect
          v-model="form.status"
          :items="[
            { label: 'Active', value: 'active' },
            { label: 'Inactive', value: 'inactive' },
          ]"
          class="w-full"
          @change="clearError('status')"
        />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>