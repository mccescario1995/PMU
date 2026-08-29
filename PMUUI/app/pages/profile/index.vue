<script setup lang="ts">
definePageMeta({
    layout: "dashboard",
});

const { user } = useAuth();
const toast = useToast();

const isEditing = ref(false);
const loading = ref(false);
const fetching = ref(true);

const form = reactive({
    name: "",
    email: "",
    role: "",
    status: "",
    created_at: "",
});

const originalForm = reactive({
    name: "",
    email: "",
    role: "",
    status: "",
    created_at: "",
});

// Helper to unwrap the API response
const unwrap = (response: any) => response?.data || response;

onMounted(async () => {
    try {
        const response = await apiFetch("/v1/auth/me", {
            parseJson: true,
            throwOnError: true,
        });

        const profile = response?.data;

        console.log("Fetched profile:", profile);

        form.name = profile.name || "";
        form.email = profile.email || "";
        form.role = profile.role || "";
        form.status = profile.status || "";
        form.created_at = profile.created_at || "";

        originalForm.name = form.name;
        originalForm.email = form.email;
        originalForm.role = form.role;
        originalForm.status = form.status;
        originalForm.created_at = form.created_at;
        console.log("Profile data set in form:", form);
        console.log("Original form data:", originalForm);
        console.log("User state:", user.value.roles, user.value.status, user.value.created_at);
    } catch {
        toast.add({
            title: "Error",
            description: "Failed to load profile.",
            color: "error",
        });
    } finally {
        fetching.value = false;
    }
});

function startEdit() {
    isEditing.value = true;
}

function cancelEdit() {
    form.name = originalForm.name;
    form.email = originalForm.email;
    isEditing.value = false;
}

async function saveProfile() {
    loading.value = true;
    try {
        const profile = unwrap(user.value);
        const userId = profile?.id;
        if (!userId) throw new Error("User ID not found");

        await apiFetch(`/v1/users/${userId}`, {
            method: "PUT",
            body: JSON.stringify({
                name: form.name,
                email: form.email,
            }),
            headers: {
                "Content-Type": "application/json",
            },
            parseJson: true,
            throwOnError: true,
        });

        // Sync the global auth state so UserDropdown updates immediately
        if (user.value) {
            user.value.data.name = form.name;
            user.value.data.email = form.email;
        }

        originalForm.name = form.name;
        originalForm.email = form.email;
        isEditing.value = false;

        toast.add({
            title: "Success",
            description: "Profile updated successfully.",
            color: "success",
        });
    } catch (err) {
        toast.add({
            title: "Update failed",
            description: err?.message || "Failed to update profile.",
            color: "error",
        });
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="p-6 max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold">Profile</h1>
            <p class="text-slate-500">Manage your personal information.</p>
        </div>

        <div v-if="fetching" class="flex justify-center py-12">
            <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
        </div>

        <UCard v-else>
            <template #header>
                <div class="flex items-center justify-between">
                    <span>Personal Information</span>
                    <UButton v-if="!isEditing" variant="ghost" color="neutral" icon="i-lucide-pencil"
                        @click="startEdit">
                        Edit
                    </UButton>
                </div>
            </template>

            <UForm :state="form" @submit="saveProfile" class="space-y-4">
                <UFormField label="Full Name" name="name">
                    <UInput v-model="form.name" :disabled="!isEditing" />
                </UFormField>

                <UFormField label="Email" name="email">
                    <UInput v-model="form.email" type="email" :disabled="!isEditing" />
                </UFormField>
                
                <div v-if="originalForm.role || originalForm.status" class="text-sm text-slate-500 space-y-1 pt-4 border-t border-slate-200">
                    <p><strong class="text-slate-700">Role:</strong> {{ originalForm.role || "N/A" }}</p>
                    <p><strong class="text-slate-700">Status:</strong> {{ originalForm.status || "N/A" }}</p>
                    <p v-if="originalForm.created_at">
                        <strong class="text-slate-700">Member since:</strong>
                        {{ new Date(originalForm.created_at).toLocaleDateString() }}
                    </p>
                </div>

                <div v-if="isEditing" class="flex gap-2 pt-4">
                    <UButton type="submit" :loading="loading"> Save Changes </UButton>
                    <UButton variant="ghost" color="neutral" @click="cancelEdit" :disabled="loading">
                        Cancel
                    </UButton>
                </div>
            </UForm>
        </UCard>
    </div>
</template>