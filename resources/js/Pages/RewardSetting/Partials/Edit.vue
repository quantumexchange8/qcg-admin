<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {ref, watch, watchEffect} from "vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import InputNumber from "primevue/inputnumber";
import {useForm, usePage} from '@inertiajs/vue3';
import Select from "primevue/select";
import FileUpload from 'primevue/fileupload';
import Datepicker from 'primevue/datepicker';
import { IconPlus, IconUpload, IconX } from "@tabler/icons-vue";
import RadioButton from 'primevue/radiobutton';
import ToggleSwitch from 'primevue/toggleswitch';
import { transactionFormat } from "@/Composables/index.js";

const props = defineProps({
    reward:Object,
});

const { formatAmount, formatDate } = transactionFormat();

const languageLabels = {
  en: 'English',
  tw: '中文（繁體）',
  cn: '中文（简体）',
};

// const visible = ref(false)

// const openDialog = () => {
//     form.reset();
//     removeAttachment();
//     visible.value = true;
// }

const emit = defineEmits(['update:visible'])

const form = useForm({
    reward_id: props.reward.reward_id,
    cash_amount: props.reward.cash_amount ?? null,
    rewards_type: props.reward.type,
    code: props.reward.code,
    name: {
        en: props.reward.name.en,
        tw: props.reward.name.tw,
        cn: props.reward.name.cn,
    },
    trade_point_required: props.reward.trade_point_required,
    reward_thumbnail: props.reward.reward_thumbnail,
    // start_date: props.reward.start_date ? formatDate(props.reward.start_date) : '',
    expiry_date: props.reward.expiry_date ? formatDate(props.reward.expiry_date) : '',
    maximum_redemption: props.reward.maximum_redemption ?? null,
    max_per_person: props.reward.max_per_person ?? null,
    autohide_after_expiry: props.reward.autohide_after_expiry ?? false,
});

const selectedAttachment = ref(props.reward.reward_thumbnail || null);
const selectedAttachmentName = ref(props.reward.reward_thumbnail ? props.reward.reward_thumbnail.split('/').pop() : null);

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
        form.reward_thumbnail = event.target.files[0];
    } else {
        selectedAttachment.value = null;
    }
};

const removeAttachment = () => {
    selectedAttachment.value = null;
    form.reward_thumbnail = '';
};

const today = new Date();

const submitForm = () => {
    if (form.expiry_date) {
        form.expiry_date = formatDate(form.expiry_date);
    }
    
    form.post(route('reward.editReward'), {
        onSuccess: () => {
            closeDialog();
        },
    });
};

const closeDialog = () => {
    form.reset();
    removeAttachment();
    emit('update:visible', false);
}

watch(() => form.rewards_type, (newValue) => {
    if (newValue !== 'cash_rewards') {
        form.cash_amount = props.reward.cash_amount ?? null;
    }
});

</script>

<template>
    <div class="flex flex-col py-4 gap-6 self-stretch md:py-6 md:gap-8">

        <div class="flex flex-col gap-3">
            <span class="text-sm font-bold text-gray-950">{{ $t('public.reward_details') }}</span>
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <InputLabel
                        for="rewards_type"
                        :value="$t('public.rewards_type')"
                        :invalid="!!form.errors.rewards_type"
                    />
                    <div class="w-full flex flex-row items-center gap-5 self-stretch">
                        <div class="flex items-center gap-3 text-gray-950">
                            <RadioButton
                                v-model="form.rewards_type"
                                inputId="cash_rewards"
                                value="cash_rewards"
                                class="w-5 h-5"
                            />
                            <label for="cash_rewards">{{ $t('public.cash_rewards') }}</label>
                        </div>
                        <div class="flex items-center gap-3 text-gray-950">
                            <RadioButton
                                v-model="form.rewards_type"
                                inputId="physical_rewards"
                                value="physical_rewards"
                                class="w-5 h-5"
                            />
                            <label for="physical_rewards">{{ $t('public.physical_rewards') }}</label>
                        </div>
                    </div>
                </div>
                <div v-if="form.rewards_type === 'cash_rewards'" class="flex flex-col gap-2">
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
                </div>
                <div class="flex flex-col gap-2">
                    <InputLabel
                        for="code"
                        :value="$t('public.code')"
                        :invalid="!!form.errors.code"
                    />
                    <InputText
                        id="code"
                        type="text"
                        class="block w-full"
                        v-model="form.code"
                        placeholder="Unique identifier for internal tracking"
                        :invalid="!!form.errors.code"
                    />
                    <InputError :message="form.errors.code" />
                </div>
                <div class="flex flex-col gap-2">
                    <InputLabel
                        for="name"
                        :value="$t('public.name')"
                        :invalid="!!form.errors.name"
                    />
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
                                    class="block w-full"
                                    v-model="form.name[key]"
                                    :placeholder="$t('public.name_' + key + '_placeholder')"
                                    :invalid="!!form.errors['name.' + key]"
                                />
                                <InputError :message="form.errors['name.' + key]" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <InputLabel
                        for="trade_point_required"
                        :value="`${$t('public.trade_point_required')} (tp)`"
                        :invalid="!!form.errors.trade_point_required"
                    />
                    <InputNumber
                        v-model="form.trade_point_required"
                        :minFractionDigits="2"
                        id="trade_point_required"
                        fluid
                        size="sm"
                        :min="0"
                        :step="1"
                        class="w-full"
                        inputClass="py-3 px-4"
                        placeholder="0.00"
                    />
                    <InputError :message="form.errors.trade_point_required" />
                </div>
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-1">
                        <InputLabel
                            for="reward_thumbnail"
                            :value="$t('public.reward_thumbnail')"
                            :invalid="!!form.errors.reward_thumbnail"
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

        <div class="flex flex-col gap-3">
            <span class="text-sm font-bold text-gray-950">{{ $t('public.availability_n_expiry') }}</span>
            <div class="grid grid-cols-2 gap-5">
                <!-- <div class="flex flex-col gap-2">
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
                </div> -->
                <div class="flex flex-col gap-2">
                    <InputLabel>{{ $t('public.expiry_date') }}</InputLabel>
                    <Datepicker
                        v-model="form.expiry_date"
                        :minDate="today"
                        selectionMode="single"
                        dateFormat="yy/mm/dd"
                        showIcon
                        iconDisplay="input"
                        :placeholder="$t('public.select_date')"
                        class="w-full font-normal"
                    />
                </div>
                <div class="flex flex-col gap-2">
                    <InputLabel>{{ $t('public.autohide_after_expiry') }}</InputLabel>
                    <ToggleSwitch
                        v-model="form.autohide_after_expiry"
                        :disabled="!form.expiry_date"
                        class="my-auto"
                    />
                </div>
                <div class="flex flex-col gap-2">
                    <InputLabel>{{ $t('public.maximum_redemption_quantity') }}</InputLabel>
                    <InputNumber
                        v-model="form.maximum_redemption"
                        id="maximum_redemption"
                        fluid
                        size="sm"
                        :min="0"
                        :step="1"
                        class="w-full"
                        inputClass="py-3 px-4"
                        placeholder="0"
                    />
                </div>
                <div class="flex flex-col gap-2">
                    <InputLabel>{{ $t('public.maximum_per_person') }}</InputLabel>
                    <InputNumber
                        v-model="form.max_per_person"
                        id="max_per_person"
                        fluid
                        size="sm"
                        :min="0"
                        :step="1"
                        class="w-full"
                        inputClass="py-3 px-4"
                        placeholder="0"
                    />
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
        <Button
            variant="gray-outlined"
            class="w-full"
            :disabled="form.processing"
            @click.prevent="closeDialog"
        >
            {{ $t('public.cancel') }}
        </Button>
        <Button
            variant="primary-flat"
            class="w-full"
            :disabled="form.processing"
            @click.prevent="submitForm"
        >
            {{ $t('public.save') }}
        </Button>
    </div>
</template>
