<script setup>
import {
    IconDotsVertical,
    IconIdBadge2,
    IconLogin,
    IconArrowsRightLeft,
    IconUserUp,
    IconUserCancel,
    IconUserCheck,
    IconTool,
    IconLockCog,
    IconTrashX,
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
import UpgradeToAgent from "@/Pages/Member/Listing/Partials/UpgradeToAgent.vue";
import Adjustment from "@/Components/Adjustment.vue";
import ResetPassowrd from "@/Pages/Member/Listing/Partials/ResetPassword.vue";

const props = defineProps({
    member: Object,
})

const menu = ref();
const visible = ref(false)
const dialogType = ref('')
const title = ref('');
const adjustmentType = ref('')

const items = ref([
    {
        label: 'member_details',
        icon: h(IconIdBadge2),
        command: () => {
            window.location.href = `/member/detail/${props.member.id}`;
        },
    },
    {
        label: 'access_portal',
        icon: h(IconLogin),
        command: () => {
            window.open(route('member.access_portal', props.member.id), '_blank');
        },
    },
    {
        label: 'transfer_upline',
        icon: h(IconArrowsRightLeft),
        command: () => {
            visible.value = true;
            dialogType.value = 'transfer_upline';
            title.value = 'transfer_upline';
        },
    },
    {
        label: 'upgrade_to_agent',
        icon: h(IconUserUp),
        command: () => {
            visible.value = true;
            dialogType.value = 'upgrade_to_agent';
            title.value = 'upgrade_to_agent';
        },
        role: 'member', // Add a custom property to check the role
    },
    {
        label: 'adjustment',
        icon: h(IconTool),
        items: [
            {
                label: 'rebate',
                command: () => {
                    visible.value = true;
                    dialogType.value = 'adjustment';
                    adjustmentType.value = 'rebate';
                    title.value = 'rebate';
                },
            },
            {
                label: 'balance',
                command: () => {
                    visible.value = true;
                    dialogType.value = 'adjustment';
                    adjustmentType.value = 'account_balance';
                    title.value = 'balance';
                },
            },
            {
                label: 'credit',
                command: () => {
                    visible.value = true;
                    dialogType.value = 'adjustment';
                    adjustmentType.value = 'account_credit';
                    title.value = 'credit';
                },
            }
        ]

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
        label: 'delete_member',
        icon: h(IconTrashX),
        command: () => {
            requireConfirmation('delete_member')
        },
    },
]);

const filteredItems = computed(() => {
    return items.value.filter(item => {
        return !(item.role && item.role === 'member' && props.member.role === 'agent');

    });
});

const checked = ref(props.member.status === 'active')

watch(() => props.member.status, (newStatus) => {
    checked.value = newStatus === 'active';
});

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        activate_member: {
            group: 'headless',
            color: 'primary',
            icon: h(IconUserCheck),
            header: trans('public.activate_member'),
            message: trans('public.activate_member_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.visit(route('member.updateMemberStatus', props.member.id), {
                    method: 'post',
                    data: {
                        id: props.member.id,
                    },
                })

                checked.value = !checked.value;
            }
        },
        deactivate_member: {
            group: 'headless',
            color: 'error',
            icon: h(IconUserCancel),
            header: trans('public.deactivate_member'),
            message: trans('public.deactivate_member_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.visit(route('member.updateMemberStatus', props.member.id), {
                    method: 'post',
                    data: {
                        id: props.member.id,
                    },
                })

                checked.value = !checked.value;
            }
        },
        delete_member: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_member'),
            message: trans('public.delete_member_desc'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.visit(route('member.deleteMember'), {
                    method: 'delete',
                    data: {
                        id: props.member.id,
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

const handleMemberStatus = () => {
    if (props.member.status === 'active') {
        requireConfirmation('deactivate_member')
    } else {
        requireConfirmation('activate_member')
    }
}
</script>

<template>
    <div class="flex gap-3 items-center justify-center">
        <ToggleSwitch
            v-model="checked"
            readonly
            @click="handleMemberStatus"
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
        <TieredMenu ref="menu" id="overlay_tmenu" :model="filteredItems" popup>
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
        :header="dialogType !== 'adjustment' ? $t('public.' + title) : $t('public.adjustment_header', {type: $t(`public.${title}`)})"
        class="dialog-xs"
        :class="{
            'md:dialog-md': dialogType === 'upgrade_to_agent',
            'md:dialog-sm': dialogType !== 'upgrade_to_agent',
        }"
    >
        <template v-if="dialogType === 'transfer_upline'">
            <!-- <TransferUpline
                :member="member"
                @update:visible="visible = false"
            /> -->
        </template>
        <template v-if="dialogType === 'upgrade_to_agent'">
            <UpgradeToAgent
                :member="member"
                @update:visible="visible = false"
            />
        </template>
        <template v-if="dialogType === 'adjustment'">
            <Adjustment
                :member="member"
                :type="adjustmentType"
                @update:visible="visible = false"
            />
        </template>
        <template v-if="dialogType === 'reset_password'">
            <ResetPassowrd
                :member="member"
                @update:visible="visible = false"
            />
        </template>

    </Dialog>
</template>
