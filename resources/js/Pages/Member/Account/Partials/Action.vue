<script setup>
import {
    IconSettingsDollar,
    IconTrashX,
    IconChevronRight,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import { h, ref } from "vue";
import TieredMenu from "primevue/tieredmenu";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import Dialog from "primevue/dialog";
import Adjustment from "@/Components/Adjustment.vue";

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
        label: 'account_balance',
        command: () => {
            visible.value = true;
            dialogType.value = 'adjustment';
            adjustmentType.value = 'account_balance';
            title.value = 'balance';
        },
    },
    {
        label: 'account_credit',
        command: () => {
            visible.value = true;
            dialogType.value = 'adjustment';
            adjustmentType.value = 'account_credit';
            title.value = 'credit';
        },
    },

]);


const confirm = useConfirm();

const requireConfirmation = (action_type, meta_login) => {
    const messages = {
        delete_trading_account: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_trading_account'),
            message: trans('public.delete_trading_account_desc' , {account: `${meta_login}`}),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.visit(route('member.accountDelete'), {
                    method: 'delete',
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

const toggle = (event) => {
    menu.value.toggle(event);
};
</script>

<template>
    <div class="flex gap-3 items-center justify-center">
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
            <IconSettingsDollar size="16" stroke-width="1.25" />
        </Button>
        <TieredMenu ref="menu" id="overlay_tmenu" :model="items" popup>
            <template #item="{ item, props, hasSubmenu }">
                <div
                    class="flex items-center gap-3 self-stretch"
                    v-bind="props.action"
                >
                    <span class="text-gray-700 text-sm">{{ $t(`public.${item.label}`) }}</span>
                    <!-- Conditionally render submenu indicator if the item has a submenu -->
                    <span v-if="hasSubmenu" class="ml-auto text-gray-500">
                        <IconChevronRight size="20" stroke-width="1.25" />
                    </span>
                </div>
            </template>
        </TieredMenu>

        <Button
            type="button"
            variant="error-text"
            size="sm"
            pill
            iconOnly
            @click="requireConfirmation('delete_trading_account', props.account.meta_login)"

        >
            <IconTrashX size="16" stroke-width="1.25" />
        </Button>

    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.adjustment_header', {type: $t(`public.${title}`)})"
        class="dialog-xs md:dialog-sm"
    >
        <template v-if="dialogType === 'adjustment'">
            <Adjustment
                :type="adjustmentType"
                :account="props.account"
                @update:visible="visible = false"
            />
        </template>
    </Dialog>
</template>
