<script setup>
import {
    IconDotsVertical,
    IconShieldCog,
    IconLockCog,
    IconPencilMinus,
    IconTrashX,
    IconUserCheck,
    IconUserCancel,
    IconChevronRight,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import { computed, h, ref, watch } from "vue";
import TieredMenu from "primevue/tieredmenu";
import ToggleSwitch from 'primevue/toggleswitch';
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import Dialog from "primevue/dialog";
import ManagePermissions from "@/Pages/AdminRole/Partials/ManagePermissions.vue";
import ResetPassowrd from "@/Pages/Member/Listing/Partials/ResetPassword.vue";
import EditAdminRole from "@/Pages/AdminRole/Partials/EditAdminRole.vue";

const props = defineProps({
    admin: Object,
    permissionsList: Array,
})

const menu = ref();
const visible = ref(false)
const dialogType = ref('')
const title = ref('');

const items = ref([
    {
        label: 'permissions',
        icon: h(IconShieldCog),
        command: () => {
            visible.value = true;
            dialogType.value = 'manage_permissions';
            title.value = 'manage_permissions';
        },
    },
    {
        label: 'reset_password',
        icon: h(IconLockCog),
        command: () => {
            visible.value = true;
            dialogType.value = 'reset_password';
            title.value = 'reset_password';
        },
    },
    {
        label: 'edit',
        icon: h(IconPencilMinus),
        command: () => {
            visible.value = true;
            dialogType.value = 'edit_admin_role';
            title.value = 'edit_admin_role';
        },
    },
    {
        label: 'delete_permission',
        icon: h(IconTrashX),
        command: () => {
            requireConfirmation('delete_admin_role')
        },
    },
]);

const checked = ref(props.admin.status === 'active')

watch(() => props.admin.status, (newStatus) => {
    checked.value = newStatus === 'active';
});

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        activate_admin_role: {
            group: 'headless',
            color: 'primary',
            icon: h(IconUserCheck),
            header: trans('public.activate_admin_role'),
            message: trans('public.activate_admin_role_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.activate'),
            action: () => {
                router.post(route('adminRole.updateAdminStatus', props.admin.id), {
                    id: props.admin.id,
                })

                checked.value = !checked.value;
            }
        },
        deactivate_admin_role: {
            group: 'headless',
            color: 'error',
            icon: h(IconUserCancel),
            header: trans('public.deactivate_admin_role'),
            message: trans('public.deactivate_admin_role_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.deactivate'),
            action: () => {
                router.post(route('adminRole.updateAdminStatus', props.admin.id), {
                    id: props.admin.id,
                })

                checked.value = !checked.value;
            }
        },
        delete_admin_role: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_admin_role'),
            message: trans('public.delete_admin_role_desc'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.delete(route('adminRole.deleteAdmin'), {
                    data: {
                        id: props.admin.id,
                    },
                })
            }
        },
    };

    const { group, color, icon, header, message, cancelButton, acceptButton, action } = messages[action_type];

    confirm.require({
        group,
        color,
        icon,
        header,
        message,
        cancelButton,
        acceptButton,
        accept: action
    });
};

const toggle = (event) => {
    menu.value.toggle(event);
};

const handleAdminStatus = () => {
    if (props.admin.status === 'active') {
        requireConfirmation('deactivate_admin_role')
    } else {
        requireConfirmation('activate_admin_role')
    }
}
</script>

<template>
    <div class="flex gap-3 items-center justify-center">
        <ToggleSwitch
            v-model="checked"
            readonly
            @click="handleAdminStatus"
        />
        <Button
            variant="gray-text"
            size="sm"
            type="button"
            iconOnly
            pill
            @click="toggle"
            aria-haspopup="true"
            aria-controls="overlay_tmenu"
        >
            <IconDotsVertical size="16" stroke-width="1.25" color="#667085" />
        </Button>
        <TieredMenu ref="menu" id="overlay_tmenu" :model="items" popup>
            <template #item="{ item, props, hasSubmenu }">
                <div
                    class="flex items-center gap-3 self-stretch"
                    v-bind="props.action"
                >
                    <component :is="item.icon" size="20" stroke-width="1.25" class="grow-0 shrink-0" />
                    <span class="text-gray-700 text-sm">{{ $t(`public.${item.label}`) }}</span>
                    <!-- Conditionally render submenu indicator if the item has a submenu -->
                    <span v-if="hasSubmenu" class="ml-auto text-gray-500">
                        <IconChevronRight size="20" stroke-width="1.25" />
                    </span>
                </div>
            </template>
        </TieredMenu>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.' + title)"
        class="dialog-xs md:dialog-sm"
        :dismissableMask="true"
    >
        <template v-if="dialogType === 'manage_permissions'">
            <ManagePermissions
                :admin="admin"
                :permissionsList="permissionsList"
                @update:visible="visible = false"
            />
        </template>

        <template v-if="dialogType === 'reset_password'">
            <ResetPassowrd
                :member="admin"
                @update:visible="visible = false"
            />
        </template>

        <template v-if="dialogType === 'edit_admin_role'">
            <EditAdminRole
                :admin="admin"
                :permissionsList="permissionsList"
                @update:visible="visible = false"
            />
        </template>

    </Dialog>
</template>
