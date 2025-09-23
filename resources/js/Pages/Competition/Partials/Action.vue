<script setup>
import { h, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import {
    IconPencilMinus,
    IconListSearch,
    IconTrashX,
    IconDotsVertical
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import { trans, wTrans } from "laravel-vue-i18n";
import TieredMenu from "primevue/tieredmenu";

const props = defineProps({
    competition_id: Number,
    status: String,
})

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        delete_upcoming_competition: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_upcoming_competition'),
            message: trans('public.delete_upcoming_competition_desc'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.delete(route('competition.deleteCompetition'), {
                    data: {
                        id: props.competition_id,
                    },
                })
            }
        },
        delete_ongoing_competition: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_ongoing_competition'),
            message: trans('public.delete_ongoing_competition_desc'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.end_n_delete'),
            action: () => {
                router.delete(route('competition.deleteCompetition'), {
                    data: {
                        id: props.competition_id,
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

const handleCompetitionStatus = () => {
    if (props.status === 'ongoing') {
        requireConfirmation('delete_ongoing_competition')
    } else {
        requireConfirmation('delete_upcoming_competition')
    }
}

const editCompetition = () => {
    router.get(route('competition.editCompetition', { id: props.competition_id }));
};

const viewCompetition = () => {
    router.get(route('competition.viewCompetition', { id: props.competition_id }));
};

const menu = ref();

const items = ref([
    {
        label: 'edit',
        icon: h(IconPencilMinus),
        command: () => {
            editCompetition()
        },
    },
    {
        label: 'view_ranking',
        icon: h(IconListSearch),
        command: () => {
            viewCompetition()
        },
    },
    {
        label: 'delete',
        icon: h(IconTrashX),
        command: () => {
            handleCompetitionStatus()
        },
    },
]);

const toggle = (event) => {
    menu.value.toggle(event);
};

</script>

<template>
    <div class="flex gap-0.5 items-center justify-center">
        <Button
            v-if="props.status !== 'completed'"
            class="hidden md:flex"
            variant="gray-text"
            size="sm"
            type="button"
            iconOnly
            pill
            @click="editCompetition()"
        >
            <IconPencilMinus size="16" stroke-width="1.5" />
        </Button>
        <Button
            variant="gray-text"
            class="hidden md:flex"
            size="sm"
            type="button"
            iconOnly
            pill
            @click="viewCompetition()"
        >
            <IconListSearch size="16" stroke-width="1.5" />
        </Button>
        <Button
            v-if="props.status !== 'completed'"
            class="hidden md:flex"
            variant="error-text"
            size="sm"
            type="button"
            iconOnly
            pill
            @click="handleCompetitionStatus()"
        >
            <IconTrashX size="16" stroke-width="1.5" />
        </Button>

        <Button
            v-if="props.status !== 'completed'"
            class="md:hidden"
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
            <template #item="{ item, props }">
                <div
                    class="flex items-center gap-3 self-stretch"
                    v-bind="props.action"
                >
                <component
                    :is="item.icon"
                    size="20"
                    stroke-width="1.25"
                    class="grow-0 shrink-0"
                    :class="{'text-error-500': item.label === 'delete'}"
                />
                    <span class="text-sm" 
                        :class="{'text-gray-700': item.label !== 'delete', 'text-error-500': item.label === 'delete'}"
                    >
                        {{ $t(`public.${item.label}`) }}
                    </span>
                </div>
            </template>
        </TieredMenu>
    </div>

</template>