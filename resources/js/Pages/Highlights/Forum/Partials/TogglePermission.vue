<script setup>
import { IconUserCheck, IconUserCancel } from "@tabler/icons-vue";
import ToggleSwitch from "primevue/toggleswitch";
import { h, ref, watch } from "vue";
import { useConfirm } from "primevue/useconfirm";
import { trans } from "laravel-vue-i18n";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    agent: Object,
})

const checked = ref(props.agent.isSelected);

watch(() => props.agent.isSelected, (newStatus) => {
    checked.value = newStatus;
});

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        grant_posting_permission: {
            group: 'headless',
            color: 'primary',
            icon: h(IconUserCheck),
            header: trans('public.grant_posting_permission'),
            message: trans('public.grant_posting_permission_desc'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.post(route('member.updatePostPermission', props.agent.id), {
                    id: props.agent.id,
                })

                checked.value = !checked.value;
            }
        },
        remove_posting_permission: {
            group: 'headless',
            color: 'error',
            icon: h(IconUserCancel),
            header: trans('public.remove_posting_permission'),
            message: trans('public.remove_posting_permission_desc'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.remove'),
            action: () => {
                router.post(route('member.updatePostPermission', props.agent.id), {
                    id: props.agent.id,
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

const handlePermissions = () => {
    if (props.agent.isSelected) {
        requireConfirmation('remove_posting_permission')
    } else {
        requireConfirmation('grant_posting_permission')
    }
}
</script>

<template>
    <ToggleSwitch
        v-model="checked"
        readonly
        @click="handlePermissions"
    />
</template>

