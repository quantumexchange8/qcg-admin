<script setup>
import {
    IconUserCheck,
    IconUserCancel,
} from "@tabler/icons-vue";
import { h, ref, watch } from "vue";
import ToggleSwitch from 'primevue/toggleswitch';
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";

const props = defineProps({
    announcement: Object,
})

const checked = ref(props.announcement.status === 'active')

watch(() => props.announcement.status, (newStatus) => {
    checked.value = newStatus === 'active';
});

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        activate_announcement: {
            group: 'headless',
            color: 'primary',
            icon: h(IconUserCheck),
            header: trans('public.activate_announcement'),
            message: trans('public.activate_announcement_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.activate'),
            action: () => {
                router.post(route('highlights.updateAnnouncementStatus'), {
                    announcement_id: props.announcement.id,
                })

                checked.value = !checked.value;
            }
        },
        deactivate_announcement: {
            group: 'headless',
            color: 'error',
            icon: h(IconUserCancel),
            header: trans('public.deactivate_announcement'),
            message: trans('public.deactivate_announcement_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.deactivate'),
            action: () => {
                router.post(route('highlights.updateAnnouncementStatus'), {
                    announcement_id: props.announcement.id,
                })

                checked.value = !checked.value;
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

const handleAnnouncementStatus = () => {
    if (props.announcement.status === 'active') {
        requireConfirmation('deactivate_announcement')
    } else {
        requireConfirmation('activate_announcement')
    }
}

</script>

<template>
    <div class="flex gap-3 items-center justify-center">
        <ToggleSwitch
            v-model="checked"
            readonly
            @click="handleAnnouncementStatus"
            :disabled="props.announcement.status !== 'active' && props.announcement.status !== 'inactive'"
        />

    </div>

</template>
