// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: ['@nuxt/ui', '@nuxtjs/color-mode'],
  runtimeConfig: {
    public: {
      apiBase: process.env.API_BASE_URL || '/api',
    },
  },

  ssr: false,
  css: ["~/assets/css/main.css"],
})