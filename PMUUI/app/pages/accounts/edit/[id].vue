<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";

definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

// Explicitly typing the references gets rid of 'as any' bugs completely
interface Role {
  id: number | string;
  name: string;
}

const roles = ref<Role[]>([]);

const form = reactive({
  id: Number(id),
  name: "",
  email: "",
  role_id: null as number | string | null,
  status: "active",
});

onMounted(async () => {
  // 1. Cleaned up the 'as any[]' cast
  const rolesResponse = await apiFetch("/v1/roles", { parseJson: true });
  roles.value = rolesResponse as Role[];

  // 2. Cleaned up the 'as any' cast by typing the variable instead of casting inline
  const user: any = await apiFetch("/v1/users/" + id, { parseJson: true });

  Object.assign(form, {
    id: user.id,
    name: user.name,
    email: user.email,
    role_id: user.role_id,
    status: user.status,
  });
});

function save() {
  apiFetch("/v1/users/" + id, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(form),
    parseJson: true,
  }).then(() => useRouter().push("/accounts"));
}
</script>