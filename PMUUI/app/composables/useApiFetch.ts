// composables/useApiFetch.ts
import { accessToken } from "./useAuth";

type ApiFetchOptions = RequestInit & {
  parseJson?: boolean;
  throwOnError?: boolean;
  timeout?: number;
};

/**
 * Universal API Fetch (Nuxt 4)
 *
 * - JSON support
 * - Sanctum Bearer token auto-attach
 * - Centralized error handling
 * - SSR safe
 * - Request timeout support
 */
export async function apiFetch<T = any>(
  input: string,
  init: ApiFetchOptions = {},
): Promise<Response | T> {
  const config = useRuntimeConfig();
  const baseURL = config.public.apiBase as string;

  const { timeout = 30000, parseJson, throwOnError, ...fetchInit } = init;

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeout);

  const buildHeaders = (token?: string | null) => {
    const headers = new Headers(fetchInit.headers);

    if (token) {
      headers.set("Authorization", `Bearer ${token}`);
    }

    if (!headers.has("Accept")) {
      headers.set("Accept", "application/json");
    }

    if (fetchInit.body && !headers.has("Content-Type")) {
      headers.set("Content-Type", "application/json");
    }

    return headers;
  };

  const execute = (token?: string | null) =>
    fetch(`${baseURL}${input}`, {
      ...fetchInit,
      headers: buildHeaders(token),
      signal: controller.signal,
    });

  let response: Response;

  try {
    response = await execute(accessToken.value);
  } catch (err: any) {
    clearTimeout(timeoutId);
    if (err.name === "AbortError") {
      throw new Error("Request timeout");
    }
    throw err;
  } finally {
    clearTimeout(timeoutId);
  }

  if (response.status === 401) {
    if (accessToken.value) {
      try {
        const auth = useAuth();
        auth.logout();
      } catch {
        // ignore errors during logout redirect
      }
    }
    throw new Error("Unauthorized");
  }

  return handleResponse<T>(response, { parseJson, throwOnError });
}

async function handleResponse<T>(
  response: Response,
  init: { parseJson?: boolean; throwOnError?: boolean },
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
