<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "#imports";
import { apiFetch } from "~/utils/api";

const router = useRouter();
const toast = useToast();

const email = ref("");
const loading = ref(false);

const handleSubmit = async () => {
  if (!email.value) {
    toast.add({ title: "Error", description: "Enter your email address.", color: "error" });
    return;
  }

  loading.value = true;
  try {
    await apiFetch("/v1/auth/forgot-password", {
      method: "POST",
      body: JSON.stringify({ email: email.value }),
      headers: { "Content-Type": "application/json" },
      parseJson: true,
      throwOnError: true,
    });
    toast.add({ title: "Email sent", description: "If the email exists, a reset link has been sent.", color: "success" });
    router.push("/");
  } catch (err: any) {
    const message = err?.body?.message ?? "Unable to send reset email.";
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

    <h2 class="mb-2 text-3xl font-bold text-slate-800">Forgot Password</h2>
    <p class="mb-8 text-slate-500">Enter your email and we'll send you a reset link.</p>

    <UForm @submit.prevent="handleSubmit" class="w-full">
      <UFormField label="Email" class="text-xl">
        <UInput v-model="email" icon="i-lucide-user" size="xl" color="secondary" placeholder="Email"
          :ui="{ base: 'text-lg px-4 py-3' }" class="w-full mb-3" type="email" />
      </UFormField>

      <UButton type="submit" block size="xl" variant="outline" color="neutral"
        class="h-16 rounded-xl text-xl font-semibold mt-5" :loading="loading">
        Send Reset Link
      </UButton>
    </UForm>

    <div class="mt-6 text-center">
      <p class="text-slate-500">
        Remember your password?
        <UButton variant="link" color="primary" size="sm" to="/"> Sign in </UButton>
      </p>
    </div>
  </div>
</template>