<script setup lang="ts">

const router = useRouter();

const login = (role: string) => {
  router.push(`/login?role=${role}`);
};

</script>

<template>
  <div class="h-screen overflow-hidden bg-white">
    <div class="grid h-full lg:grid-cols-[60%_40%]">
      <!-- LEFT IMAGE -->
      <div class="hidden lg:block relative">
        <img
          src="#"
          class="absolute inset-0 h-full w-full object-cover"
        />
        <!-- src="/images/port.jpg" -->

        <div class="absolute inset-0 bg-black/10" />
      </div>

      <!-- RIGHT PANEL -->
      <div class="flex flex-col bg-white">
        <!-- HEADER -->
        <div class="border-b-4 border-amber-300 px-10 py-8">
          <div class="flex items-center gap-5">
            <img src="/assets/images/pmu-logo.png" class="h-20 w-20 object-contain" />

            <div>
              <h1 class="text-4xl font-bold tracking-wide text-slate-800">
                PORT MANAGEMENT UNIT
              </h1>

              <p class="text-lg text-slate-500">
                Zone 2, Brgy. San Cirilo, Pasacao, Camarines Sur
              </p>
            </div>
          </div>
        </div>

        <!-- LOGIN -->
        <div class="flex flex-1 flex-col items-center justify-center px-12">
          <h2
            class="mb-10 text-5xl font-extrabold tracking-wide text-slate-800"
          >
            LOG IN AS
          </h2>

          <div class="w-full max-w-md space-y-8">
            <UButton
              block
              size="xl"
              variant="outline"
              color="neutral"
              icon="i-lucide-user-round"
              class="h-16 rounded-xl text-xl font-semibold"
              @click="login('manager')"
            >
              Port Manager
            </UButton>

            <div class="text-center text-2xl font-semibold text-slate-400">
              or
            </div>

            <UButton
              block
              size="xl"
              variant="outline"
              color="neutral"
              icon="i-lucide-chart-column-big"
              class="h-16 rounded-xl text-xl font-semibold"
              @click="login('statistician')"
            >
              Port Statistician
            </UButton>
          </div>
        </div>

        <!-- FOOTER -->
        <div class="p-10">
          <div
            class="flex items-center gap-4 rounded-xl bg-slate-800 p-6 text-white"
          >
            <UIcon name="i-lucide-info" class="h-8 w-8 text-amber-400" />

            <p class="text-base leading-relaxed">
              You are currently not logged in. You need to be authenticated
              before accessing the Fish Port Operations Management System.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
