// composables/useAuth.ts
import { ref } from "vue";
import { useRouter } from "vue-router";

export const accessToken = ref<string | null>(null);
export const user = ref<any>(null);

export function useAuth() {
  const router = useRouter();

  async function login(email: string, password: string) {
    const response = (await apiFetch("/v1/auth/login", {
      method: "POST",
      body: JSON.stringify({ email, password }),
      headers: { "Content-Type": "application/json" },
      parseJson: true,
      throwOnError: true,
    })) as { user: any; token: string };

    accessToken.value = response.token;
    user.value = response.user;

    return { status: "SUCCESS" };
  }

  async function logout() {
    try {
      if (accessToken.value) {
        await apiFetch("/v1/auth/logout", { method: "POST" });
      }
    } catch {
      // ignore network errors on logout
    }

    accessToken.value = null;
    user.value = null;
    router.push("/login");
  }

  function refresh() {
    return Promise.resolve(accessToken.value);
  }

  return { login, logout, refresh, accessToken, user };
}
