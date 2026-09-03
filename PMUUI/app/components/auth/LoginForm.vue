<script setup lang="ts">
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useAuth } from "~/composables/useAuth";
import { useToast } from "#imports";
import RoleBadge from "~/components/auth/RoleBadge.vue";

const router = useRouter();
const auth = useAuth();
const toast = useToast();

const username = ref("");
const loading = ref(false);

const show = ref(false);
const password = ref("");

function checkStrength(str: string) {
  const requirements = [
    { regex: /.{8,}/, text: "At least 8 characters" },
    { regex: /\d/, text: "At least 1 number" },
    { regex: /[a-z]/, text: "At least 1 lowercase letter" },
    { regex: /[A-Z]/, text: "At least 1 uppercase letter" },
  ];

  return requirements.map((req) => ({
    met: req.regex.test(str),
    text: req.text,
  }));
}

const strength = computed(() => checkStrength(password.value));
const score = computed(() => strength.value.filter((req) => req.met).length);

const color = computed(() => {
  if (score.value === 0) return "neutral";
  if (score.value <= 1) return "error";
  if (score.value <= 2) return "warning";
  if (score.value === 3) return "warning";
  return "success";
});

const text = computed(() => {
  if (score.value === 0) return "Enter a password";
  if (score.value <= 2) return "Weak password";
  if (score.value === 3) return "Medium password";
  return "Strong password";
});

const role = ref("Port Manager");

const handleSubmit = async () => {
  if (!username.value || !password.value) {
    toast.add({ title: "Error", description: "Enter your email and password.", color: "error" });
    return;
  }

  loading.value = true;
  try {
    await auth.login(username.value, password.value);
    toast.add({ title: "Welcome back", description: "Signed in successfully.", color: "success" });
    router.push("/dashboard");
  } catch (err: any) {
    const message = err?.body?.message ?? "Unable to sign in.";
    toast.add({ title: "Login failed", description: message, color: "error" });
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="mx-auto w-full max-w-md">
    <!-- <RoleBadge :role="role" /> -->

    <UForm @submit.prevent="handleSubmit" class="w-full">
      <UFormField label="Email" class="text-xl">
        <UInput v-model="username" icon="i-lucide-user" size="xl" color="secondary" placeholder="Email" 
          :ui="{ base: 'text-lg px-4 py-3' }" class="w-full mb-3" />
      </UFormField>

      <UFormField label="Password" class="text-xl">
        <UInput v-model="password" size="xl" placeholder="Password" color="secondary" :type="show ? 'text' : 'password'"
          aria-describedby="password-strength" :ui="{ trailing: 'pe-1', base: 'text-lg px-4 py-3' }" class="w-full"
          icon="i-lucide-lock">
          <template #trailing>
            <UButton color="neutral" variant="link" size="sm" :icon="show ? 'i-lucide-eye-off' : 'i-lucide-eye'"
              :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show" aria-controls="password"
              @click="show = !show"
               />
          </template>
        </UInput>
      </UFormField>
      <div class="my-3">
        <UCheckbox label="Remember me" />
      </div>
      <UButton type="submit" block size="xl" variant="outline" color="neutral"
        class="h-16 rounded-xl text-xl font-semibold mt-5" :loading="loading">
        Sign In
      </UButton>
    </UForm>
  </div>
</template>