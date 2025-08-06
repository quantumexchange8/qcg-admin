<script setup>
import { h, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import {
    IconPencilMinus,
    IconListSearch,
    IconTrashX,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import { trans, wTrans } from "laravel-vue-i18n";

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
</script>

<template>
    <div class="flex gap-0.5 items-center justify-center">
        <Button
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
            size="sm"
            type="button"
            iconOnly
            pill
            @click="viewCompetition()"
        >
            <IconListSearch size="16" stroke-width="1.5" />
        </Button>
        <Button
            variant="error-text"
            size="sm"
            type="button"
            iconOnly
            pill
            @click="handleCompetitionStatus()"
        >
            <IconTrashX size="16" stroke-width="1.5" />
        </Button>
    </div>

</template>