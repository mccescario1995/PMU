// composables/usePermissions.ts
import { computed } from "vue";
import { useAuth } from "./useAuth";

export function usePermissions() {
  const { user } = useAuth();

  const permissions = computed(() => new Set(user.value?.all_permissions ?? []));
  const roles = computed(() => new Set(user.value?.roles ?? []));

  function can(permission: string): boolean {
    return permissions.value.has(permission);
  }

  function hasRole(role: string): boolean {
    return roles.value.has(role);
  }

  function isAdmin(): boolean {
    return roles.value.has("Administrator");
  }

  return { can, hasRole, isAdmin, permissions, roles };
}
