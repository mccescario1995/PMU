<script setup lang="ts">
import { ref, reactive } from "vue";
import { useAuth } from "~/composables/useAuth";
import { useToast } from "#imports";
import { useRouter } from "#imports";
import { apiFetch } from "~/composables/useApiFetch";

definePageMeta({
  layout: "dashboard",
});

const { user } = useAuth();
const toast = useToast();
const router = useRouter();

const loading = ref(false);

const form = reactive({
  current_password: "",
  new_password: "",
  confirm_password: "",
});

const errors = reactive({
  current_password: "",
  new_password: "",
  confirm_password: "",
});

function validateForm(): boolean {
  let isValid = true;

  if (!form.current_password) {
    errors.current_password = "Current password is required";
    isValid = false;
  } else {
    errors.current_password = "";
  }

  if (!form.new_password) {
    errors.new_password = "New password is required";
    isValid = false;
  } else if (form.new_password.length < 6) {
    errors.new_password = "New password must be at least 6 characters";
    isValid = false;
  } else if (!/[A-Z]/.test(form.new_password)) {
    errors.new_password = "New password must contain at least 1 uppercase letter";
    isValid = false;
  } else if (!/[a-z]/.test(form.new_password)) {
    errors.new_password = "New password must contain at least 1 lowercase letter";
    isValid = false;
  } else if (!/[0-9]/.test(form.new_password)) {
    errors.new_password = "New password must contain at least 1 digit";
    isValid = false;
  } else if (form.new_password === form.current_password) {
    errors.new_password = "New password must be different from current password";
    isValid = false;
  } else {
    errors.new_password = "";
  }

  if (!form.confirm_password) {
    errors.confirm_password = "Please confirm your new password";
    isValid = false;
  } else if (form.confirm_password !== form.new_password) {
    errors.confirm_password = "Passwords do not match";
    isValid = false;
  } else {
    errors.confirm_password = "";
  }

  return isValid;
}

function clearError(field: keyof typeof errors) {
  errors[field] = "";
}

async function changePassword() {
  if (!validateForm()) return;

  loading.value = true;
  try {
    const profile = unwrap(user.value);
    const userId = profile?.id;
    if (!userId) throw new Error("User ID not found");

    await apiFetch(`/v1/users/${userId}`, {
      method: "PUT",
      body: JSON.stringify({
        current_password: form.current_password,
        password: form.new_password,
      }),
      headers: {
        "Content-Type": "application/json",
      },
      parseJson: true,
      throwOnError: true,
    });

    form.current_password = "";
    form.new_password = "";
    form.confirm_password = "";

    toast.add({
      title: "Success",
      description: "Password changed successfully.",
      color: "success",
    });

    router.push("/profile");
  } catch (err: any) {
    if (err?.body?.message === "Password is incorrect") {
      errors.current_password = "Current password is incorrect";
    } else {
      toast.add({
        title: "Error",
        description: err?.body?.message || err?.message || "Failed to change password.",
        color: "error",
      });
    }
  } finally {
    loading.value = false;
  }
}

// Helper to unwrap the API response
const unwrap = (response: any) => response?.data || response;
</script>

<template>
  <div class="p-6 max-w-md space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Change Password</h1>
      <p class="text-slate-500">Update your account password.</p>
    </div>

    <UCard>
      <template #header>
        <h3 class="text-lg font-semibold">New Password</h3>
      </template>

      <UForm class="space-y-4 pt-4">
        <UFormField label="Current Password" class="mb-3" :error="errors.current_password" required>
          <UInput
            v-model="form.current_password"
            type="password"
            class="w-full"
            @input="clearError('current_password')"
            placeholder="Enter your current password"
          />
        </UFormField>

        <UFormField label="New Password" class="mb-3" :error="errors.new_password" required>
          <UInput
            v-model="form.new_password"
            type="password"
            class="w-full"
            @input="clearError('new_password')"
            placeholder="Enter new password"
          />
          <template #hint>
            <ul class="text-xs text-slate-500 space-y-1 mt-1">
              <li>At least 6 characters</li>
              <li>At least 1 uppercase letter (A-Z)</li>
              <li>At least 1 lowercase letter (a-z)</li>
              <li>At least 1 digit (0-9)</li>
              <li>Must be different from current password</li>
            </ul>
          </template>
        </UFormField>

        <UFormField label="Confirm New Password" class="mb-3" :error="errors.confirm_password" required>
          <UInput
            v-model="form.confirm_password"
            type="password"
            class="w-full"
            @input="clearError('confirm_password')"
            placeholder="Confirm your new password"
          />
        </UFormField>
      </UForm>

      <template #footer>
        <div class="flex gap-2 justify-end pt-4">
          <UButton variant="ghost" color="neutral" @click="router.push('/profile')" :disabled="loading">
            Cancel
          </UButton>
          <UButton @click="changePassword" :loading="loading">
            Change Password
          </UButton>
        </div>
      </template>
    </UCard>
  </div>
</template>