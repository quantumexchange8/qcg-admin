<script setup>
import Button from "@/Components/Button.vue";
import { ref, watchEffect } from "vue";
import { useForm } from '@inertiajs/vue3';
import ToggleSwitch from "primevue/toggleswitch";

const props = defineProps({
    admin: Object,
    permissionsList: Array,
})

const emit = defineEmits(['update:visible'])

const closeDialog = () => {
    emit('update:visible', false);
    form.reset();
}

// Initialize the form based on the admin's current permissions
const form = useForm({
    id: props.admin.id,
    permissions: props.admin.permissions.map(permission => permission.name),  // Extract current permission names
});

const permissionsState = ref({});

// Initialize permissionsState based on permissionsList
watchEffect(() => {
    permissionsState.value = {};
    props.permissionsList.forEach(permission => {
        // Check if the admin has this permission and set the state accordingly
        permissionsState.value[permission.name] = form.permissions.includes(permission.name);
    });
});

// Update the togglePermission function to modify permissionsState
const togglePermission = (permission) => {
    const index = form.permissions.indexOf(permission);
    if (index > -1) {
        // Permission exists, remove it
        form.permissions.splice(index, 1);
    } else {
        // Permission does not exist, add it
        form.permissions.push(permission);
    }
    // Update the permissionsState to reflect the current state
    permissionsState.value[permission] = !permissionsState.value[permission]; // Toggle the state
};

const submit = () => {
    form.post(route('adminRole.adminUpdatePermissions'), {
        onSuccess: () => {
            emit('update:visible', false);
            form.reset();
        },
    });
};
</script>

<template>
    <form>
        <div class="flex flex-col items-center py-4 self-stretch md:py-6">
            <!-- Permissions -->
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div
                    v-for="permission in props.permissionsList"
                    :key="permission.id"
                    class="flex justify-center items-center gap-3 self-stretch"
                >
                    <ToggleSwitch
                        v-model="permissionsState[permission.name]"
                        @change="() => togglePermission(permission.name)"
                    />
                    <span class="w-full text-gray-700 text-sm font-medium">{{ $t('public.allow_permission', {permission: $t(`public.${permission.name}`)})}}</span>
                </div>
            </div>
        </div>
        <div class="w-full flex justify-end items-center gap-4 pt-6 self-stretch">
            <Button
                type="button"
                size="base"
                class="w-full"
                variant="gray-outlined"
                @click="closeDialog"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary-flat"
                size="base"
                class="w-full"
                @click="submit"
                :disabled="form.processing"
            >
                {{ $t('public.save') }}
            </Button>
        </div>
    </form>
</template>
