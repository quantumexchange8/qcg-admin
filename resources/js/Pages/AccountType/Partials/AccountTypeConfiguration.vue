<script setup>
import { Head } from "@inertiajs/vue3";
import { h, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { useForm } from "@inertiajs/vue3";
import {
    IconChevronRight,
    IconId,
    IconIdOff,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from 'primevue/inputtext';
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import ToggleSwitch from 'primevue/toggleswitch';
import Dialog from "primevue/dialog";
import ColorPicker from 'primevue/colorpicker';
import { trans, wTrans } from "laravel-vue-i18n";

defineProps({
    title: String,
})

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        unsaved_changes: {
            group: 'headless',
            color: 'primary',
            icon: h(IconId),
            header: trans('public.unsaved_changes'),
            message: trans('public.unsaved_changes_message'),
            cancelButton: trans('public.leave_page'),
            acceptButton: trans('public.stay_on_page'),
        }
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
    <Head :title="title"></Head>

    <div class="flex flex-col flex-1 max-w-[1440px] justify-center items-start bg-gray-100">
        <nav
            aria-label="secondary"
            class="flex w-full h-16 sticky top-0 z-10 py-2 px-5 gap-3 justify-center items-start bg-white"
        >
            <div class="w-full flex items-center gap-2">
                <Button
                    external
                    type="button"
                    variant="primary-text"
                    size="sm"
                    :href="route('accountType')"
                >
                    {{ $t('public.account_type') }}
                </Button>
                <IconChevronRight
                    :size="20"
                    stroke-width="1.25"
                    class="text-gray-400"
                />
                <Button
                    external
                    type="button"
                    variant="gray-text"
                    size="sm"
                >
                    {{ $t(`${accountType.name} - ${'public.account_configuration'}`) }}
                </Button>
            </div>
            <Button></Button>
            <Button></Button>
        </nav>
    </div>
</template>
