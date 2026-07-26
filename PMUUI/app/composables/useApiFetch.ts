// composables/useApiFetch.ts
import { accessToken } from "./useAuth";

type ApiFetchOptions = RequestInit & {
  parseJson?: boolean;
  throwOnError?: boolean;
};

/**
 * Universal API Fetch (Nuxt 4)
 *
 * - JSON support
 * - Sanctum Bearer token auto-attach
 * - Centralized error handling
 * - SSR safe
 */
export async function apiFetch<T = any>(
  input: string,
  init: ApiFetchOptions = {},
): Promise<Response | T> {
  const config = useRuntimeConfig();
  const baseURL = config.public.apiBase as string;

  const buildHeaders = (token?: string | null) => {
    const headers = new Headers(init.headers);

    if (token) {
      headers.set("Authorization", `Bearer ${token}`);
    }

    headers.set("Accept", "application/json");

    return headers;
  };

  const execute = (token?: string | null) =>
    fetch(`${baseURL}${input}`, {
      ...init,
      headers: buildHeaders(token),
    });

  const response = await execute(accessToken.value);

  if (response.status === 401) {
    try {
      const auth = useAuth();
      auth.logout();
    } catch {
      // ignore errors during logout redirect
    }
    throw new Error("Unauthorized");
  }

  return handleResponse<T>(response, init);
}

async function handleResponse<T>(
  response: Response,
  init: ApiFetchOptions,
): Promise<Response | T> {
  if (init.throwOnError && !response.ok) {
    let errorBody: any = null;

    try {
      errorBody = await response.json();
    } catch {
      errorBody = await response.text();
    }

    throw {
      status: response.status,
      body: errorBody,
    };
  }

  if (init.parseJson) {
    if (response.status === 204) {
      return null as T;
    }

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return (await response.json()) as T;
  }

  return response;
}
