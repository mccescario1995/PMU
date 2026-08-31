<script setup lang="ts">
import type { TableColumn } from "@nuxt/ui";
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted, h, computed, ref, reactive, watch } from "vue";
import { usePermissions } from "~/composables/usePermissions";
import { getPaginationRowModel } from "@tanstack/vue-table";
import { useTablePagination } from "~/composables/useTablePagination";
import type { SelectItem } from "@nuxt/ui";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();
const toast = useToast();

const tab = ref<"roles" | "users">("roles");

const roles = ref<any[]>([]);
const {
  page: rolePage,
  pageSize: rolePageSize,
  goToPageInput: roleGoToPageInput,
  tablePagination: roleTablePagination,
  totalPages: roleTotalPages,
  handleGoToPage: roleHandleGoToPage,
} = useTablePagination(() => roles.value.length);
const users = ref<any[]>([]);
const {
  page: userPage,
  pageSize: userPageSize,
  goToPageInput: userGoToPageInput,
  tablePagination: userTablePagination,
  totalPages: userTotalPages,
  handleGoToPage: userHandleGoToPage,
} = useTablePagination(() => users.value.length);
const permissionGroups = ref<any[]>([]);

const roleOptions = computed(() =>
  roles.value.map((role) => ({
    id: role.id,
    name: role.name,
  })),
);

const loading = ref(true);

const showRoleModal = ref(false);
const editingRole = ref<any>(null);
const roleForm = reactive({
  name: "",
  permissions: [] as string[],
});

const showUserModal = ref(false);
const editingUser = ref<any>(null);
const userForm = reactive({
  name: "",
  email: "",
  password: "",
  status: "active",
  roles: [] as number[],
});

function toArray(r: any): any[] {
  return Array.isArray(r) ? r : (r?.data ?? []);
}

async function load() {
  loading.value = true;
  const [r, u, p] = await Promise.all([
    apiFetch("/v1/roles", { parseJson: true }),
    apiFetch("/v1/users", { parseJson: true }),
    apiFetch("/v1/permissions", { parseJson: true }),
  ]);
  roles.value = toArray(r as any);
  users.value = toArray(u as any);
  permissionGroups.value = toArray(p as any);
  loading.value = false;
}

onMounted(load);

function togglePerm(name: string, checked: boolean) {
  if (checked) {
    if (!roleForm.permissions.includes(name)) roleForm.permissions.push(name);
  } else {
    roleForm.permissions = roleForm.permissions.filter((p) => p !== name);
  }
}

async function saveRole() {
  const payload = { name: roleForm.name, permissions: roleForm.permissions };
  try {
    if (editingRole.value) {
      await apiFetch(`/v1/roles/${editingRole.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        parseJson: true,
        throwOnError: true,
      });
    } else {
      await apiFetch("/v1/roles", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        parseJson: true,
        throwOnError: true,
      });
    }
    openRoleModal.value = false;
    toast.add({
      title: editingRole.value ? "Role updated" : "Role created",
      color: "success",
    });
    await load();
  } catch (e: any) {
    toast.add({
      title: "Failed to save role",
      description: e?.body?.message ?? "Please check the form.",
      color: "error",
    });
  }
}

async function removeRole(role: any) {
  if (
    !confirm(
      `Delete role "${role.name}"? Users with this role will lose its permissions.`,
    )
  )
    return;
  await apiFetch(`/v1/roles/${role.id}`, { method: "DELETE" });
  toast.add({ title: "Role deleted", color: "success" });
  await load();
}

function openCreateUser() {
  editingUser.value = null;
  userForm.name = "";
  userForm.email = "";
  userForm.password = "";
  userForm.status = "active";

  const portManager = roleOptions.value.find(
    (role) => role.name === "Port Manager"
  );

  userForm.roles = portManager ? [portManager.id] : [];

  showUserModal.value = true;
}

function openEditUser(user: any) {
  editingUser.value = user;
  userForm.name = user.name;
  userForm.email = user.email;
  userForm.password = "";
  userForm.status = user.status ?? "active";
  userForm.roles = [...(user.roles ?? [])];
  showUserModal.value = true;
}

async function saveUser() {
  const payload: any = {
    name: userForm.name,
    email: userForm.email,
    status: userForm.status,
    roles: userForm.roles,
  };
  if (userForm.password) payload.password = userForm.password;

  try {
    if (editingUser.value) {
      if (!userForm.password) delete payload.password;
      await apiFetch(`/v1/users/${editingUser.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        parseJson: true,
        throwOnError: true,
      });
    } else {
      if (!userForm.password) {
        toast.add({ title: "Password is required", color: "error" });
        return;
      }
      await apiFetch("/v1/users", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        parseJson: true,
        throwOnError: true,
      });
    }
    showUserModal.value = false;
    toast.add({
      title: editingUser.value ? "User updated" : "User created",
      color: "success",
    });
    await load();
  } catch (e: any) {
    toast.add({
      title: "Failed to save user",
      description: e?.body?.message ?? "Please check the form.",
      color: "error",
    });
  }
}

async function removeUser(user: any) {
  if (!confirm(`Delete user "${user.name}"?`)) return;
  await apiFetch(`/v1/users/${user.id}`, { method: "DELETE" });
  toast.add({ title: "User deleted", color: "success" });
  await load();
}

const statusColor: Record<string, string> = {
  active: "success",
  inactive: "neutral",
  suspended: "error",
};

const roleColumns: TableColumn<any>[] = [
  { accessorKey: "name", header: "Role" },
  {
    accessorKey: "permissions",
    header: "Permissions",
    cell: ({ row }) =>
      `${row.getValue("permissions")?.length ?? 0} permission(s)`,
  },
  { accessorKey: "action", header: "Action" },
];

const userColumns: TableColumn<any>[] = [
  { accessorKey: "name", header: "Name" },
  { accessorKey: "email", header: "Email" },
  {
    accessorKey: "status",
    header: "Status",
    cell: ({ row }) => {
      const s = row.getValue("status");
      return h("span", { class: `text-xs capitalize` }, () => s);
    },
  },
  {
    accessorKey: "roles",
    header: "Roles",
    cell: ({ row }) => (row.getValue("roles") ?? []).join(", ") || "-",
  },
  { accessorKey: "action", header: "Action" },
];

const openRoleModal = ref(false);

function openNewRoleModal() {
  editingRole.value = null;
  roleForm.name = "";
  roleForm.permissions = [];
  openRoleModal.value = true;
}

function openEditRoleModal(role: Role) {
  editingRole.value = role;
  roleForm.name = role.name;
  roleForm.permissions = [...(role.permissions ?? [])];
  openRoleModal.value = true;
}

const userStatus = ref<SelectItem[]>([
  {
    label: "Active",
    value: "active",
  },
  {
    label: "Inactive",
    value: "inactive",
  },
  {
    label: "Suspended",
    value: "suspended",
  },
]);
// 'active', 'inactive', 'suspended'
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Access Control</h1>
        <p class="text-sm text-slate-500">
          Manage roles, permissions and user access.
        </p>
      </div>
    </div>

    <div class="flex gap-2">
      <UButton
        :variant="tab === 'roles' ? 'solid' : 'soft'"
        @click="tab = 'roles'"
        >Roles &amp; Permissions</UButton
      >
      <UButton
        :variant="tab === 'users' ? 'solid' : 'soft'"
        @click="tab = 'users'"
        >Users</UButton
      >
    </div>

    <!-- Roles -->
    <section v-if="tab === 'roles'" class="space-y-4">
      <div class="flex justify-end">
        <UButton
          v-if="can('create roles')"
          icon="i-lucide-plus"
          title="Add Role"
          @click="openNewRoleModal"
          >Add Role</UButton
        >
      </div>

      <UModal v-model:open="openRoleModal">
        <template #header>
          {{ editingRole ? "Edit Role" : "New Role" }}
        </template>

        <template #body>
          <div class="space-y-4">
            <UFormField label="Role Name">
              <UInput
                class="w-full"
                v-model="roleForm.name"
                placeholder="e.g. Inventory Clerk"
              />
            </UFormField>

            <UFormField label="Permissions">
              <div class="space-y-3">
                <div
                  v-for="group in permissionGroups"
                  :key="group.resource"
                  class="rounded-lg border p-3"
                >
                  <p class="mb-2 text-sm font-semibold capitalize">
                    {{ group.resource }}
                  </p>
                  <div class="flex flex-wrap gap-3">
                    <UCheckbox
                      v-for="perm in group.permissions"
                      :key="perm.name"
                      :model-value="roleForm.permissions.includes(perm.name)"
                      :label="perm.action"
                      :ui="{ label: 'capitalize' }"
                      @update:model-value="
                        (val: boolean) => togglePerm(perm.name, val)
                      "
                    />
                  </div>
                </div>
              </div>
            </UFormField>
          </div>
        </template>

        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" @click="openRoleModal = false"
              >Cancel</UButton
            >
            <UButton @click="saveRole">Save</UButton>
          </div>
        </template>
      </UModal>

      <UTable
        :data="roles"
        :columns="roleColumns"
        :loading="loading"
        :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
        v-model:pagination="roleTablePagination"
      >
        <template #action-cell="{ row }">
          <div class="flex gap-2">
            <UButton
              v-if="can('edit roles')"
              size="xs"
              icon="i-lucide-edit"
              @click="openEditRoleModal(row.original)"
            />
            <UButton
              v-if="can('delete roles')"
              size="xs"
              color="error"
              icon="i-lucide-trash"
              @click="removeRole(row.original)"
            />
          </div>
        </template>
      </UTable>

      <div class="flex items-center justify-between mt-4">
        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-500">Rows per page:</span>
          <USelect
            v-model="rolePageSize"
            :items="[5, 10, 20, 30, 50]"
            class="w-20"
          />
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-500">Go to page:</span>
          <UInput
            v-model="roleGoToPageInput"
            type="number"
            :min="1"
            :max="roleTotalPages"
            class="w-16"
            @keyup.enter="roleHandleGoToPage"
          />
          <UButton size="sm" @click="roleHandleGoToPage">Go</UButton>
        </div>
        <UPagination
          :total="roles.length"
          v-model:page="rolePage"
          :items-per-page="rolePageSize"
        />
      </div>
    </section>

    <!-- Users -->
    <section v-else class="space-y-4">
      <div class="flex justify-end">
        <UButton
          v-if="can('create users')"
          icon="i-lucide-plus"
          @click="openCreateUser"
        >
          Add User
        </UButton>
      </div>

      <UModal v-model:open="showUserModal">
        <template #header>
          {{ editingUser ? "Edit User" : "New User" }}
        </template>

        <template #body class="space-y-4">
          <UFormField label="Name" class="mb-3">
            <UInput v-model="userForm.name" class="w-full" placeholder="Enter Name" />
          </UFormField>
          <UFormField label="Email" class="mb-3">
            <UInput
              class="w-full"
              placeholder="Enter Email"
              v-model="userForm.email"
              :disabled="!!editingUser"
              type="email"
            />
          </UFormField>
          <UFormField
            class="mb-3"
            :label="
              editingUser ? 'New Password (leave blank to keep)' : 'Password'
            "
          >
            <UInput
              class="w-full"
              v-model="userForm.password"
              type="password"
              :placeholder="
                editingUser ? 'Leave blank to keep current' : 'Enter password'
              "
            />
          </UFormField>
          <div class="flex flex-col">
            <UFormField label="Status" class="mb-3">
              <USelect
                class="w-full"
                v-model="userForm.status"
                :items="userStatus"
              />
            </UFormField>
            <UFormField label="Roles">
              <USelect
                class="w-full"
                v-model="userForm.roles"
                :items="roleOptions"
                value-key="id"
                label-key="name"
                multiple
              />
            </UFormField>
          </div>
        </template>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" @click="showUserModal = false"
              >Cancel</UButton
            >
            <UButton @click="saveUser">Save</UButton>
          </div>
        </template>
      </UModal>

      <UTable
        :data="users"
        :columns="userColumns"
        :loading="loading"
        :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
        v-model:pagination="userTablePagination"
      >
        <template #action-cell="{ row }">
          <div class="flex gap-2">
            <UButton
              v-if="can('edit users')"
              size="xs"
              @click="openEditUser(row.original)"
              icon="i-lucide-edit"
            />
            <UButton
              v-if="can('delete users')"
              size="xs"
              color="error"
              @click="removeUser(row.original)"
              icon="i-lucide-trash"
            />
          </div>
        </template>
      </UTable>

      <div class="flex items-center justify-between mt-4">
        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-500">Rows per page:</span>
          <USelect
            v-model="userPageSize"
            :items="[5, 10, 20, 30, 50]"
            class="w-20"
          />
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-500">Go to page:</span>
          <UInput
            v-model="userGoToPageInput"
            type="number"
            :min="1"
            :max="userTotalPages"
            class="w-16"
            @keyup.enter="userHandleGoToPage"
          />
          <UButton size="sm" @click="userHandleGoToPage">Go</UButton>
        </div>
        <UPagination
          :total="users.length"
          v-model:page="userPage"
          :items-per-page="userPageSize"
        />
      </div>
    </section>
  </div>
</template>
