<script setup>
import { h, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { useForm } from "@inertiajs/vue3";
import {
    IconAdjustmentsHorizontal,
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

const props = defineProps({
    accountType: Object,
    leverages: Array,
})

const visible = ref(false);

const openDialog = () => {
    if (props.accountType.category === 'promotion') {
        router.get(route('accountType.accountTypeConfiguration'), {
            id: props.accountType.id,
        });
    } else {
        visible.value = true;
    }
}

const closeDialog = () => {
    visible.value = false;
}

const checked = ref(props.accountType.status === 'active')

watch(() => props.accountType.status, (newStatus) => {
    checked.value = newStatus === 'active';
});

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        activate_account_type: {
            group: 'headless',
            color: 'primary',
            icon: h(IconId),
            header: trans('public.activate_account_type'),
            message: trans('public.activate_account_type_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.post(route('accountType.updateStatus'), {
                    id: props.accountType.id,
                })

                checked.value = !checked.value;
            }
        },
        deactivate_account_type: {
            group: 'headless',
            color: 'error',
            icon: h(IconIdOff),
            header: trans('public.deactivate_account_type'),
            message: trans('public.deactivate_account_type_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.post(route('accountType.updateStatus'), {
                    id: props.accountType.id,
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

const handleAccountTypeStatus = () => {
    if (props.accountType.status === 'active') {
        requireConfirmation('deactivate_account_type')
    } else {
        requireConfirmation('activate_account_type')
    }
}

const categories = ref(['individual', 'manage', 'promotion']);
const trade_delay_duration_options = ref([
    {name: '0 sec', value: '0'},
    {name: '1 sec', value: '1'},
    {name: '2 sec', value: '2'},
    {name: '3 sec', value: '3'},
    {name: '4 sec', value: '4'},
    {name: '5 sec', value: '5'},
    {name: '6 sec', value: '6'},
    {name: '7 sec', value: '7'},
    {name: '8 sec', value: '8'},
    {name: '9 sec', value: '9'},
    {name: '10 sec', value: '10'},
    {name: '1 min', value: '60'},
    {name: '2 min', value: '120'},
    {name: '3 min', value: '180'},
    {name: '4 min', value: '240'},
    {name: '5 min', value: '300'},
])
const leverages = ref(props.leverages);

const form = useForm({
    id: props.accountType.id,
    account_type_name: props.accountType.name,
    category: props.accountType.category,
    descriptions: { en: props.accountType.description_en, tw: props.accountType.description_tw },
    leverage: props.accountType.leverage,
    trade_delay_duration: props.accountType.trade_open_duration,
    max_account: props.accountType.maximum_account_number,
    color: props.accountType.color,
})

const submitForm = () => {

    form.post(route('accountType.updateAccountType'), {
        preserveScroll: true,
        onSuccess: () => {
            closeDialog();
        },
        onError: (e) => {
            console.log('Error submit form:', e);
        }
    })
}

</script>

<template>
    <div class="flex gap-3 items-center justify-center">
        <ToggleSwitch
            v-model="checked"
            readonly
            @click="handleAccountTypeStatus"
        />
        <Button
            variant="gray-text"
            size="sm"
            type="button"
            iconOnly
            pill
            @click="openDialog"
            class="hidden md:block"
        >
            <IconAdjustmentsHorizontal size="16" stroke-width="1.25" />
        </Button>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.account_configuration')"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <div class="flex flex-col gap-6 items-center self-stretch py-4 md:py-8">
                <div class="flex flex-col items-center gap-2 self-stretch md:gap-3">
                    <span class="self-stretch text-gray-950 font-bold">{{ $t('public.account_information') }}</span>
                    <div class="w-full grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-5">
                        <div class="flex flex-col items-start gap-2 self-stretch">
                            <InputLabel for="account_type_name" :invalid="!!form.errors.account_type_name">{{ $t('public.account_type_name') }}</InputLabel>
                            <InputText
                                id="account_type_name"
                                type="text"
                                class="block w-full"
                                v-model="form.account_type_name"
                                :placeholder="$t('public.enter_name')"
                                :invalid="!!form.errors.account_type_name"
                                disabled
                            />
                            <InputError :message="form.errors.account_type_name" />
                        </div>

                        <div class="flex flex-col items-start gap-2 self-stretch">
                            <InputLabel for="category" :value="$t('public.account_type_category')" :invalid="!!form.errors.category"/>
                            <Select
                                v-model="form.category"
                                :options="categories"
                                class="w-full font-normal"
                                scroll-height="236px"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <div>{{ $t('public.' + slotProps.value) }}</div>
                                        </div>
                                    </div>
                                </template>
                                <template #option="slotProps">
                                        <div class="flex items-center gap-2">
                                            <div>{{ $t('public.' + slotProps.option) }}</div>
                                        </div>
                                    </template>
                            </Select>
                            <InputError :message="form.errors.category" />
                        </div>

                        <div class="w-full grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-x-5 md:gap-y-2 md:col-span-2">
                            <div class="h-[120px] flex flex-col items-start gap-2 self-stretch">
                                <InputLabel for="descriptions_en" :invalid="!!form.errors['descriptions.en']">{{ `${$t('public.description')}&nbsp;(${$t('public.english')})` }}</InputLabel>
                                <Textarea
                                    id="descriptions_en"
                                    type="text"
                                    class="w-full h-full"
                                    v-model="form.descriptions.en"
                                    :placeholder="$t('public.descriptions_en_placeholder')"
                                    :invalid="!!form.errors['descriptions.en']"
                                    rows="5"
                                    cols="30"
                                />
                                <InputError :message="form.errors['descriptions.en']" />
                            </div>

                            <div class="h-[120px] flex flex-col items-start gap-2 self-stretch">
                                <InputLabel for="descriptions_tw" :invalid="!!form.errors['descriptions.tw']">{{ `${$t('public.description')}&nbsp;(${$t('public.chinese')})` }}</InputLabel>
                                <Textarea
                                    id="descriptions_tw"
                                    type="text"
                                    class="w-full h-full"
                                    v-model="form.descriptions.tw"
                                    :placeholder="$t('public.descriptions_cn_placeholder')"
                                    :invalid="!!form.errors['descriptions.tw']"
                                    rows="5"
                                    cols="30"
                                />
                                <InputError :message="form.errors['descriptions.tw']" />
                            </div>
                            <span class="self-stretch text-gray-500 text-xs md:col-span-2">{{ $t('public.account_information_message') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-2 self-stretch md:gap-3">
                    <span class="self-stretch text-gray-950 font-bold">{{ $t('public.trading_conditions') }}</span>
                    <div class="w-full grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-5">
                        <div class="flex flex-col items-start gap-2 self-stretch">
                            <InputLabel for="leverage" :value="$t('public.leverage')" :invalid="!!form.errors.leverage"/>
                            <Select
                                v-model="form.leverage"
                                :options="leverages"
                                optionLabel="name"
                                optionValue="value"
                                class="w-full font-normal"
                                scroll-height="236px"
                            >
                            </Select>
                            <InputError :message="form.errors.leverage" />
                        </div>

                        <div class="flex flex-col items-start gap-2 self-stretch">
                            <InputLabel for="trade_delay_duration" :value="$t('public.trade_delay_duration')" :invalid="!!form.errors.trade_delay_duration"/>
                            <Select
                                v-model="form.trade_delay_duration"
                                :options="trade_delay_duration_options"
                                optionLabel="name"
                                optionValue="value"
                                class="w-full font-normal"
                                scroll-height="236px"
                            >
                            </Select>
                            <InputError :message="form.errors.trade_delay_duration" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-2 self-stretch md:gap-3">
                    <span class="self-stretch text-gray-950 font-bold">{{ $t('public.others') }}</span>
                    <div class="w-full grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-5">
                        <div class="flex flex-col items-start gap-2 self-stretch">
                            <InputLabel for="max_account" :value="$t('public.maximum_account_creation')" :invalid="!!form.errors.max_account"/>
                            <InputText
                                v-model="form.max_account"
                                id="max_account"
                                type="number"
                                class="w-full"
                                placeholder="0"
                            />
                            <InputError :message="form.errors.max_account" />
                        </div>

                        <div class="flex flex-col items-start gap-2 self-stretch">
                            <InputLabel for="color" :value="$t('public.colour')" :invalid="!!form.errors.color"/>
                            <ColorPicker v-model="form.color" id="Color"/>
                            <InputError :message="form.errors.color" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
                <Button
                    variant="gray-tonal"
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
        </form>
    </Dialog>
</template>
