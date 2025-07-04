<script setup>
import Button from "@/Components/Button.vue"
import { IconPlaylistAdd } from "@tabler/icons-vue";
import { ref, watch } from "vue";
import Dialog from "primevue/dialog";
import InputError from "@/Components/InputError.vue";
import InputText from 'primevue/inputtext';
import { useForm } from "@inertiajs/vue3";
import Select from "primevue/select";
import { wTrans } from "laravel-vue-i18n";

const props = defineProps({
    actionType: String,
    agents: {
        type: Object,
        default: []
    }
})

const accessLevels = [
    { name: wTrans('public.view_only'), value: 'view' },
    { name: wTrans('public.full_access'), value: 'full' },
];

const languageLabels = {
  en: 'English',
  cn: '中文（简体）',
  tw: '中文（繁體）',
};

const formType = ref(props.actionType);

const visible = ref(false);

const form = useForm({
    // Category
    name: { en: '', tw: '', cn: '' },

    // Access
    user: null,
    access_level: '',
});


const openDialog = () => {
    form.reset();
    visible.value = true;
}

const closeDialog = () => {
    form.reset();
    visible.value = false;
}

const submitForm = () => {
    if (formType.value === 'ticket_category') {
        const payload = {
            name: form.name,
        };

        form.transform(() => payload).post('/configuration/addTicketCategory', {
            onSuccess: () => {
                visible.value = false;
                form.reset();
            }
        });

    } else if (formType.value === 'agent_ticket_access') {
        const payload = {
            user: form.user,
            access_level: form.access_level,
        };

        form.transform(() => payload).post('/configuration/addAgentAccess', {
            onSuccess: () => {
                visible.value = false;
                form.reset();
            }
        });
    }
};
</script>

<template>
    <Button
        type="button"
        variant="primary-text"
        @click="openDialog()"
    >
        <IconPlaylistAdd stroke-width="1.25" class="w-4 h-4" />
        {{ $t('public.add_another') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.' + formType)"
        class="dialog-xs md:dialog-md no-header-border"
        :dismissableMask="true"
    >
        <form @submit.prevent="submitForm()">
            <div class="flex flex-col items-center py-4 gap-6 self-stretch md:gap-8">
                <template v-if="formType === 'ticket_category'">
                    <div class="flex flex-col gap-2 self-stretch">
                        <div class="w-full flex flex-col items-center gap-3">
                            <div
                                v-for="(label, key) in languageLabels"
                                :key="key"
                                class="w-full flex flex-row gap-3"
                            >
                                <div class="w-[120px] h-11 flex flex-shrink-0 items-start py-3 px-4 gap-3 rounded border border-gray-300 bg-white">
                                    <span class="w-full text-gray-950 text-sm whitespace-nowrap">{{ label }}</span>
                                </div>
                                <div class="w-full flex flex-col">
                                    <InputText
                                        :id="'name_' + key"
                                        type="text"
                                        class="block w-full truncate"
                                        v-model="form.name[key]"
                                        :placeholder="$t('public.name_' + key + '_placeholder')"
                                        :invalid="!!form.errors['name.' + key]"
                                    />
                                    <InputError :message="form.errors['name.' + key]" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-if="formType === 'agent_ticket_access'">
                    <div class="w-full flex flex-col md:flex-row items-start gap-3">
                        <div class="flex flex-col w-full items-start gap-2">
                            <Select
                                v-model="form.user"
                                :options="agents"
                                filter
                                :filterFields="['name']"
                                optionLabel="name"
                                :placeholder="$t('public.select_agent')"
                                class="w-full font-normal"
                            />
                            <InputError :message="form.errors.user" />
                        </div>
                        <div class="flex flex-col w-full items-start gap-2">
                            <Select
                                v-model="form.access_level"
                                :options="accessLevels"
                                optionLabel="name"
                                optionValue="value"
                                :placeholder="$t('public.select_access_level')"
                                class="w-full font-normal"
                            />
                            <InputError :message="form.errors.access_level" />
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
                <Button
                    type="button"
                    size="base"
                    class="w-full"
                    variant="gray-outlined"
                    @click="closeDialog()"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary-flat"
                    size="base"
                    class="w-full"
                    @click="submitForm"
                    :disabled="form.processing"
                >
                    {{ $t('public.add') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
