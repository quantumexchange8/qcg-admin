<script setup>
import {
    IconDotsVertical,
    IconReport,
    IconPencilMinus,
    IconTrashX,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import { computed, h, ref, watch } from "vue";
import TieredMenu from "primevue/tieredmenu";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import Dialog from "primevue/dialog";
import ViewIncentiveReport from "@/Pages/Leaderboard/Partials/ViewIncentiveReport.vue";
import EditIncentiveProfile from "@/Pages/Leaderboard/Partials/EditIncentiveProfile.vue";

const props = defineProps({
    profile: Object,
    isLoading: Boolean,
})

const menu = ref();
const visible = ref(false)
const dialogType = ref('')
const title = ref('');

const items = ref([
    {
        label: 'report',
        icon: h(IconReport),
        command: () => {
            visible.value = true;
            dialogType.value = 'report';
            title.value = 'view_incentive_report';
        },
    },
    {
        label: 'edit',
        icon: h(IconPencilMinus),
        command: () => {
            visible.value = true;
            dialogType.value = 'edit';
            title.value = 'edit_incentive_report';
        },
    },
    {
        label: 'delete',
        icon: h(IconTrashX),
        command: () => {
            requireConfirmation('delete')
        },
    },
]);

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        delete: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_incentive_profile'),
            message: trans('public.delete_incentive_profile_desc'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.visit(route('leaderboard.deleteIncentiveProfile'), {
                    method: 'delete',
                    data: {
                        id: props.profile.id,
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
            'md:dialog-md': dialogType === 'edit',
        }"
    >
        <template v-if="dialogType === 'report'">
            <ViewIncentiveReport
                :profile="profile"
                @update:visible="visible = false"
            />
        </template>
        <template v-if="dialogType === 'edit'">
            <EditIncentiveProfile
                :profile="profile"
                @update:visible="visible = false"
            />
        </template>
    </Dialog>
</template>
