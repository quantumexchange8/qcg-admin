<script setup>
import {
    IconDotsVertical,
    IconReportMoney,
    IconTool,
    IconSettingsDollar,
    IconTrashX,
    IconChevronRight,
    IconUserCheck,
    IconUserCancel,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import { h, ref, watch } from "vue";
import TieredMenu from "primevue/tieredmenu";
import ToggleSwitch from 'primevue/toggleswitch';
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import Dialog from "primevue/dialog";
import Adjustment from "@/Components/Adjustment.vue";
import AccountReport from "@/Pages/Member/Account/Partials/AccountReport.vue";

const props = defineProps({
    account: Object,
})

const menu = ref();
const visible = ref(false)
const dialogType = ref('')
const title = ref('');
const adjustmentType = ref('')

const items = ref([
    {
        label: 'account_report',
        icon: h(IconReportMoney),
        command: () => {
            visible.value = true;
            dialogType.value = 'account_report';
        },
    },
    {
        label: 'adjustment', // Main item for adjustments
        icon: h(IconTool),
        items: [  // Submenu items for "adjustment_options"
            {
                label: 'account_balance',
                // icon: h(IconSettingsDollar),
                command: () => {
                    visible.value = true;
                    dialogType.value = 'adjustment';
                    adjustmentType.value = 'account_balance';
                    title.value = 'balance';
                },
            },
            {
                label: 'account_credit',
                // icon: h(IconSettingsDollar),
                command: () => {
                    visible.value = true;
                    dialogType.value = 'adjustment';
                    adjustmentType.value = 'account_credit';
                    title.value = 'credit';
                },
            },
        ],
    },
    {
        label: 'delete_trading_account',
        icon: h(IconTrashX),
        command: () => {
            requireConfirmation('delete_trading_account', props.account.meta_login)
        },
    },

]);

const toggle = (event) => {
    menu.value.toggle(event);
};

const checked = ref(props.account.status === 'active')

watch(() => props.account.status, (newStatus) => {
    checked.value = newStatus === 'active';
});

const confirm = useConfirm();

const requireConfirmation = (action_type, meta_login) => {
    const messages = {
        activate_trading_account: {
            group: 'headless',
            color: 'primary',
            icon: h(IconUserCheck),
            header: trans('public.activate_trading_account'),
            message: trans('public.activate_trading_account_caption', {account: `${meta_login}`}),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.post(route('member.updateAccountStatus'), {
                    meta_login: props.account.meta_login,
                })

                checked.value = !checked.value;
            }
        },
        deactivate_trading_account: {
            group: 'headless',
            color: 'error',
            icon: h(IconUserCancel),
            header: trans('public.deactivate_trading_account'),
            message: trans('public.deactivate_trading_account_caption', {account: `${meta_login}`}),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.post(route('member.updateAccountStatus'), {
                    meta_login: props.account.meta_login,
                })

                checked.value = !checked.value;
            }
        },
        delete_trading_account: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_trading_account'),
            message: trans('public.delete_trading_account_desc' , {account: `${meta_login}`}),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.delete(route('member.accountDelete'), {
                    data: {
                        meta_login: props.account.meta_login,
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

const handleAccountStatus = () => {
    if (props.account.status === 'active') {
        requireConfirmation('deactivate_trading_account', props.account.meta_login)
    } else {
        requireConfirmation('activate_trading_account', props.account.meta_login)
    }
}

</script>

<template>
    <div class="flex gap-3 items-center justify-center">
        <ToggleSwitch
            v-model="checked"
            readonly
            @click="handleAccountStatus"
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
                <component
                    :is="item.icon"
                    size="20"
                    stroke-width="1.25"
                    class="grow-0 shrink-0"
                    :class="{'text-error-500': item.label === 'delete_trading_account'}"
                />
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
        :header="dialogType === 'adjustment' ? $t('public.adjustment_header', {type: $t(`public.${title}`)}) : $t(`public.${dialogType}`)"
        class="dialog-xs"
        :class="(dialogType === 'account_report') ? 'md:dialog-md' : 'md:dialog-sm'"
    >
        <template v-if="dialogType === 'adjustment'">
            <Adjustment
                :type="adjustmentType"
                :account="props.account"
                @update:visible="visible = false"
            />
        </template>

        <template v-if="dialogType === 'account_report'">
            <AccountReport
                :account="props.account"
                @update:visible="visible = $event"
            />
        </template>

    </Dialog>
</template>
