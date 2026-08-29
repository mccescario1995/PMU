export default defineNuxtPlugin(async () => {
  const auth = useAuth();

  if (import.meta.client) {
    await auth.hydrateUser();
  }
});
