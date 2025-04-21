<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {h, ref, watch, watchEffect} from "vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import InputNumber from "primevue/inputnumber";
import {useForm, usePage} from '@inertiajs/vue3';
import Select from "primevue/select";
import FileUpload from 'primevue/fileupload';
import Datepicker from 'primevue/datepicker';
import { IconPlus, IconUpload, IconX, IconAlertTriangle } from "@tabler/icons-vue";
import RadioButton from 'primevue/radiobutton';
import ToggleSwitch from 'primevue/toggleswitch';
import { transactionFormat } from "@/Composables/index.js";
import Textarea from "primevue/textarea";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";

const props = defineProps({

});

const { formatAmount, formatDate } = transactionFormat();

// const languageLabels = {
//   en: 'English',
//   tw: '中文（繁體）',
//   cn: '中文（简体）',
// };

const visible = ref(false)

const openDialog = () => {
    form.reset();
    form.clearErrors();
    removeAttachment();
    visible.value = true;
}

// const closeDialog = () => {
//     visible.value = false;
// }

const form = useForm({
    visible_to: 'public',
    popup: 'none',
    start_date: '',
    end_date: '',
    subject: '',
    message: '',
    thumbnail: '',
});

const selectedAttachment = ref(null);
const selectedAttachmentName = ref(null);
const handleAttachment = (event) => {
    const attachmentInput = event.target;
    const file = attachmentInput.files[0];

    if (file) {
        // Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedAttachment.value = reader.result;
        };
        reader.readAsDataURL(file);
        selectedAttachmentName.value = file.name;
        form.thumbnail = event.target.files[0];
    } else {
        selectedAttachment.value = null;
    }
};

const removeAttachment = () => {
    selectedAttachment.value = null;
    form.thumbnail = '';
};

const today = new Date();

// const submitForm = () => {
//     if (form.expiry_date) {
//         form.expiry_date = formatDate(form.expiry_date);
//     }
    
//     form.post(route('reward.createReward'), {
//         onSuccess: () => {
//             visible.value = false;
//             form.reset();
//             removeAttachment();
//         },
//     });
// };

// watch(() => form.rewards_type, (newValue) => {
//     if (newValue !== 'cash_rewards') {
//         form.cash_amount = null;
//     }
// });

const confirm = useConfirm();

const publishConfirmation = (action_type) => {
    const messages = {
        no_date_publish: {
            group: 'headless',
            color: 'primary',
            icon: h(IconAlertTriangle),
            header: trans('public.publish_announcement_no_date'),
            message: trans('public.publish_announcement_no_date_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.publish_now'),
            action: () => {
                // submitForm();

                // checked.value = !checked.value;
            }
        },
        with_date_publish: {
            group: 'headless',
            color: 'primary',
            icon: h(IconAlertTriangle),
            header: trans('public.publish_announcement_with_date'),
            message: trans('public.publish_announcement_with_date_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.schedule'),
            action: () => {
                // submitForm();

                // checked.value = !checked.value;
            }
        },
        // delete_announcement: {
        //     group: 'headless',
        //     color: 'error',
        //     icon: h(IconTrashX),
        //     header: trans('public.delete_announcement'),
        //     message: trans('public.delete_announcement'),
        //     cancelButton: trans('public.cancel'),
        //     acceptButton: trans('public.delete'),
        //     action: () => {
        //         // router.post(route('accountType.updateStatus'), {
        //         //     id: props.accountType.id,
        //         // })

        //         // checked.value = !checked.value;
        //     }
        // },
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
        variant="primary-flat"
        size="base"
        class='hidden md:flex w-full md:w-auto truncate'
        @click="openDialog()"
    >
        <IconPlus size="20" stroke-width="1.25" />
        {{ $t('public.new_announcement') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.new_announcement')"
        class="dialog-md"
        :dismissableMask="true"
    >
        <form @submit.prevent="submitForm()">
            <div class="flex flex-col py-4 gap-6 self-stretch md:py-6 md:gap-8">

                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-2">
                            <InputLabel
                                for="visible_to"
                                :value="$t('public.visible_to')"
                                :invalid="!!form.errors.visible_to"
                            />
                            <div class="w-full flex flex-row items-center gap-5 self-stretch">
                                <div class="flex items-center gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.visible_to"
                                        inputId="public"
                                        value="public"
                                        class="w-5 h-5"
                                    />
                                    <label for="public">{{ $t('public.public') }}</label>
                                </div>
                                <div class="flex items-center gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.visible_to"
                                        inputId="selected_members"
                                        value="selected_members"
                                        class="w-5 h-5"
                                    />
                                    <label for="selected_members">{{ $t('public.selected_members') }}</label>
                                </div>
                            </div>
                        </div>
                        <!-- <div v-if="form.rewards_type === 'cash_rewards'" class="flex flex-col gap-2">
                            <InputLabel
                                for="cash_amount"
                                :value="`${$t('public.cash_amount')} ($)`"
                                :invalid="!!form.errors.cash_amount"
                            />
                            <InputNumber
                                v-model="form.cash_amount"
                                :minFractionDigits="2"
                                id="cash_amount"
                                fluid
                                size="sm"
                                :min="0"
                                :step="1"
                                class="w-full"
                                inputClass="py-3 px-4"
                                placeholder="0.00"
                                :invalid="!!form.errors.cash_amount"
                            />
                            <InputError :message="form.errors.cash_amount" />
                        </div> -->
                        <div class="flex flex-col gap-2">
                            <InputLabel
                                for="popup"
                                :value="$t('public.popup_label')"
                                :invalid="!!form.errors.popup"
                            />
                            <div class="w-full flex flex-row items-center gap-5 self-stretch">
                                <div class="flex items-center gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.popup"
                                        inputId="none"
                                        value="none"
                                        class="w-5 h-5"
                                    />
                                    <label for="none">{{ $t('public.no') }}</label>
                                </div>
                                <div class="flex items-center gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.popup"
                                        inputId="first_login"
                                        value="first_login"
                                        class="w-5 h-5"
                                    />
                                    <label for="selected_members">{{ $t('public.first_login_desc') }}</label>
                                </div>
                                <div class="flex items-center gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.popup"
                                        inputId="every_login"
                                        value="every_login"
                                        class="w-5 h-5"
                                    />
                                    <label for="every_login">{{ $t('public.every_login_desc') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-5">
                            <div class="flex flex-col gap-2">
                                <InputLabel>{{ $t('public.start_date') }}</InputLabel>
                                <Datepicker
                                    v-model="form.start_date"
                                    selectionMode="single"
                                    dateFormat="yy/mm/dd"
                                    showIcon
                                    iconDisplay="input"
                                    :placeholder="$t('public.select_date')"
                                    class="w-full font-normal"
                                />
                            </div>
                            <div class="flex flex-col gap-2">
                                <InputLabel>{{ $t('public.end_date') }}</InputLabel>
                                <Datepicker
                                    v-model="form.end_date"
                                    :minDate="today"
                                    selectionMode="single"
                                    dateFormat="yy/mm/dd"
                                    showIcon
                                    iconDisplay="input"
                                    :placeholder="$t('public.select_date')"
                                    class="w-full font-normal"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <InputLabel
                                for="subject"
                                :value="$t('public.subject')"
                                :invalid="!!form.errors.subject"
                            />
                            <InputText
                                id="subject"
                                type="text"
                                class="block w-full"
                                v-model="form.subject"
                                :placeholder="$t('public.subject_placeholder')"
                                :invalid="!!form.errors.subject"
                            />
                            <InputError :message="form.errors.subject" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <InputLabel
                                for="message"
                                :value="$t('public.message')"
                                :invalid="!!form.errors.message"
                            />
                            <Textarea
                                id="message"
                                type="text"
                                class="w-full h-24"
                                v-model="form.message"
                                :placeholder="$t('public.message_placeholder')"
                                :invalid="!!form.errors.message"
                                rows="5"
                                cols="30"
                            />
                            <InputError :message="form.errors.message" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <div class="flex flex-col gap-3">
                                <InputLabel
                                    for="thumbnail"
                                    :value="$t('public.thumbnail')"
                                    :invalid="!!form.errors.thumbnail"
                                    class="font-bold"
                                />
                                <span class="self-stretch text-gray-500 text-xs">{{ $t('public.file_size_limit') }}</span>
                            </div>
                            <div class="flex flex-col gap-3">
                                <input
                                    ref="attachmentInput"
                                    id="attachment"
                                    type="file"
                                    class="hidden"
                                    accept="image/*"
                                    @change="handleAttachment"
                                />
                                <Button
                                    type="button"
                                    variant="primary-flat"
                                    @click="$refs.attachmentInput.click()"
                                    class="w-fit"
                                >
                                    <IconUpload size="20" color="#ffffff" stroke-width="1.25" />

                                    {{ $t('public.choose') }}
                                </Button>
                                <InputError :message="form.errors.reward_thumbnail" />
                            </div>
                            <div
                                v-if="selectedAttachment"
                                class="relative w-full py-3 pl-4 flex justify-between rounded-xl bg-gray-50"
                            >
                                <div class="inline-flex items-center gap-3">
                                    <img :src="selectedAttachment" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                    <div class="text-sm text-gray-950">
                                        {{ selectedAttachmentName }}
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    variant="gray-text"
                                    @click="removeAttachment"
                                    pill
                                    iconOnly
                                >
                                    <IconX size="20" color="#374151" stroke-width="1.25" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full flex justify-end items-center gap-4 pt-6 self-stretch">
                <Button
                    type="button"
                    size="base"
                    class="w-full"
                    variant="gray-outlined"
                    @click="visible = false"
                >
                    {{ $t('public.preview') }}
                </Button>
                <Button
                    type="button"
                    size="base"
                    class="w-full"
                    variant="gray-outlined"
                    @click="visible = false"
                >
                    {{ $t('public.save_as_draft') }}
                </Button>
                <Button
                    type="button"
                    variant="primary-flat"
                    size="base"
                    class="w-full"
                    @click="form.start_date ? publishConfirmation('with_date_publish') : publishConfirmation('no_date_publish')"
                    :disabled="form.processing"
                >
                    {{ $t('public.publish') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
