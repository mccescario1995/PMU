export default defineNuxtRouteMiddleware((to) => {
  if (import.meta.server) {
    return;
  }

  const publicPages = ["/", "/login"];

  if (publicPages.includes(to.path)) {
    return;
  }

  const { accessToken } = useAuth();

  if (!accessToken.value) {
    return navigateTo("/login");
  }
});
