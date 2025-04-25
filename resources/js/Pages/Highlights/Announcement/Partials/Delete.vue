<script setup>
import Button from "@/Components/Button.vue";
import {h, ref, watch, computed, watchEffect} from "vue";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import { IconTrashX } from "@tabler/icons-vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    announcement: Object,
});

const confirm = useConfirm();

const publishConfirmation = (action_type) => {
    const messages = {
        delete_announcement: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_announcement'),
            message: trans('public.delete_announcement_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.delete(route('highlights.deleteAnnouncement'), {
                    data: {
                        announcement_id: props.announcement.id,
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
</script>

<template>
    <Button
        type="button"
        size="base"
        class="w-full"
        variant="error-outlined"
        @click="publishConfirmation('delete_announcement')"
    >
        {{ $t('public.delete') }}
    </Button>
</template>
