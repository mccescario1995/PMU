export default defineNuxtRouteMiddleware((to) => {
  const publicPages = ["/", "/login"];

  if (publicPages.includes(to.path)) {
    return;
  }

  const { accessToken } = useAuth();

  if (!accessToken.value) {
    return navigateTo("/login");
  }
});
