<script setup lang="ts">
import { ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "#imports";
import { apiFetch } from "~/utils/api";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const token = computed(() => route.query.token as string);

const password = ref("");
const confirmPassword = ref("");
const loading = ref(false);

const showPassword = ref(false);
const showConfirm = ref(false);

const passwordsMatch = computed(() => password.value === confirmPassword.value);

const handleSubmit = async () => {
  if (!password.value || !confirmPassword.value) {
    toast.add({ title: "Error", description: "Fill in both password fields.", color: "error" });
    return;
  }

  if (!passwordsMatch.value) {
    toast.add({ title: "Error", description: "Passwords do not match.", color: "error" });
    return;
  }

  if (password.value.length < 8) {
    toast.add({ title: "Error", description: "Password must be at least 8 characters.", color: "error" });
    return;
  }

  if (!token.value) {
    toast.add({ title: "Error", description: "Invalid or missing reset token.", color: "error" });
    return;
  }

  loading.value = true;
  try {
    await apiFetch("/v1/auth/reset-password", {
      method: "POST",
      body: JSON.stringify({ token: token.value, password: password.value }),
      headers: { "Content-Type": "application/json" },
      parseJson: true,
      throwOnError: true,
    });
    toast.add({ title: "Password reset", description: "Your password has been updated.", color: "success" });
    router.push("/");
  } catch (err: any) {
    const message = err?.body?.message ?? "Unable to reset password.";
    toast.add({ title: "Error", description: message, color: "error" });
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="mx-auto w-full max-w-md">
    <div class="mb-3">
      <UButton icon="i-lucide-arrow-left" class="border border-primary bg-white text-primary" to="/">Back</UButton>
    </div>

    <h2 class="mb-2 text-3xl font-bold text-slate-800">Reset Password</h2>
    <p class="mb-8 text-slate-500">Enter your new password below.</p>

    <UForm @submit.prevent="handleSubmit" class="w-full">
      <UFormField label="New Password" class="text-xl">
        <UInput v-model="password" size="xl" placeholder="New Password" color="secondary"
          :type="showPassword ? 'text' : 'password'" :ui="{ trailing: 'pe-1', base: 'text-lg px-4 py-3' }" class="w-full"
          icon="i-lucide-lock">
          <template #trailing>
            <UButton color="neutral" variant="link" size="sm" :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
              :aria-label="showPassword ? 'Hide password' : 'Show password'" :aria-pressed="showPassword"
              @click="showPassword = !showPassword" />
          </template>
        </UInput>
      </UFormField>

      <UFormField label="Confirm Password" class="text-xl">
        <UInput v-model="confirmPassword" size="xl" placeholder="Confirm Password" color="secondary"
          :type="showConfirm ? 'text' : 'password'" :ui="{ trailing: 'pe-1', base: 'text-lg px-4 py-3' }" class="w-full"
          icon="i-lucide-lock">
          <template #trailing>
            <UButton color="neutral" variant="link" size="sm" :icon="showConfirm ? 'i-lucide-eye-off' : 'i-lucide-eye'"
              :aria-label="showConfirm ? 'Hide password' : 'Show password'" :aria-pressed="showConfirm"
              @click="showConfirm = !showConfirm" />
          </template>
        </UInput>
      </UFormField>

      <UButton type="submit" block size="xl" variant="outline" color="neutral"
        class="h-16 rounded-xl text-xl font-semibold mt-5" :loading="loading" :disabled="!passwordsMatch">
        Reset Password
      </UButton>
    </UForm>

    <div class="mt-6 text-center">
      <p class="text-slate-500">
        <UButton variant="link" color="primary" size="sm" to="/"> Back to login </UButton>
      </p>
    </div>
  </div>
</template>