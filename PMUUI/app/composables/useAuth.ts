// composables/useAuth.ts
import { ref } from "vue";
import { useRouter } from "vue-router";

const getStoredToken = (): string | null => {
  if (typeof localStorage !== "undefined") {
    return localStorage.getItem("access_token");
  }
  return null;
};

export const accessToken = ref<string | null>(getStoredToken());
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
    localStorage.setItem("access_token", response.token);

    return { status: "SUCCESS" };
  }

  async function logout() {
    const hadToken = !!accessToken.value;

    try {
      if (accessToken.value) {
        await apiFetch("/v1/auth/logout", { method: "POST" });
      }
    } catch {
      // ignore network errors on logout
    }

    accessToken.value = null;
    user.value = null;
    localStorage.removeItem("access_token");

    if (hadToken) {
      try {
        router.push("/login");
      } catch {
        // ignore navigation errors
      }
    }
  }

  function refresh() {
    return Promise.resolve(accessToken.value);
  }

  return { login, logout, refresh, accessToken, user };
}
