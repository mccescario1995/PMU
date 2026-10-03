export default defineNuxtRouteMiddleware((to) => {
  if (import.meta.server) {
    return;
  }

  const publicPages = ["/", "/forgot-password", "/reset-password"];

  if (publicPages.includes(to.path)) {
    return;
  }

  const { accessToken } = useAuth();

  if (!accessToken.value) {
    return navigateTo("/");
  }
});
