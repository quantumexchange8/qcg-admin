<script setup>
import {
    IconDotsVertical,
    IconHistory,
    IconPencilMinus,
    IconClipboardCheck,
    IconTrashX,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import { computed, h, ref, watch } from "vue";
import TieredMenu from "primevue/tieredmenu";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import Dialog from "primevue/dialog";
import ViewTeamTransactions from "@/Pages/Team/Partials/ViewTeamTransactions.vue";
import EditTeam from "@/Pages/Team/Partials/EditTeam.vue";

const props = defineProps({
    team: Object,
    isLoading: Boolean,
})

const menu = ref();
const visible = ref(false)
const dialogType = ref('')
const title = ref('');

const items = ref([
    {
        label: 'transactions',
        icon: h(IconHistory),
        command: () => {
            visible.value = true;
            dialogType.value = 'report';
            title.value = 'view_team_transactions';
        },
    },
    {
        label: 'edit',
        icon: h(IconPencilMinus),
        command: () => {
            visible.value = true;
            dialogType.value = 'edit';
            title.value = 'edit_sales_team';
        },
    },
    {
        label: 'settlement',
        icon: h(IconClipboardCheck),
        command: () => {
            requireConfirmation('mark')
        },
    },
    {
        label: 'remove_sales_team',
        icon: h(IconTrashX),
        command: () => {
            requireConfirmation('remove')
        },
    },
]);

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        mark: {
            group: 'headless',
            color: 'primary',
            icon: h(IconClipboardCheck),
            header: trans('public.mark_settlement_header'),
            message: trans('public.mark_settlement_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.visit(route('team.markSettlementReport'), {
                    method: 'post',
                    data: {
                        id: props.team.id,
                    },
                })
            }
        },
        remove: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.remove_sales_team_header'),
            message: trans('public.remove_sales_team_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.sales_team_remove'),
            action: () => {
                router.visit(route('team.deleteTeam'), {
                    method: 'delete',
                    data: {
                        id: props.team.id,
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
    <Button
        variant="gray-text"
        size="sm"
        type="button"
        iconOnly
        pill
        @click="toggle"
        aria-haspopup="true"
        aria-controls="overlay_tmenu"
        :disabled="props.isLoading"
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
            </div>
        </template>
    </TieredMenu>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.' + title)"
        class="dialog-xs"
        :class="{
            'md:dialog-lg': dialogType === 'report',
            'md:dialog-sm': dialogType === 'edit',
        }"
    >
        <template v-if="dialogType === 'report'">
            <ViewTeamTransactions
                :team="team"
                @update:visible="visible = false"
            />
        </template>
        <template v-if="dialogType === 'edit'">
            <EditTeam
                :team="team"
                @update:visible="visible = false"
            />
        </template>
    </Dialog>
</template>
