<script setup>
import {
    IconUserCheck,
    IconUserCancel,
    IconPencil,
    IconTrashX,
} from "@tabler/icons-vue";
import { h, ref, watch } from "vue";
import ToggleSwitch from 'primevue/toggleswitch';
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import Button from "@/Components/Button.vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import dayjs from "dayjs";
import DatePicker from 'primevue/datepicker';
import { transactionFormat } from "@/Composables/index.js";
import Dialog from 'primevue/dialog';
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    tradePeriod: Object,
})

const { formatAmount, formatDate } = transactionFormat();

const editVisible = ref(false);

const form = useForm({
    trade_period_id: props.tradePeriod.id,
    period_name: props.tradePeriod.period_name,
    start_date: formatDate(props.tradePeriod.start_date),
    end_date: formatDate(props.tradePeriod.end_date),
})

const today = new Date();

const openEditDialog = () => {
    form.reset();
    editVisible.value = true;
}

const submitForm = () => {
    if(form.start_date){
        form.start_date = formatDate(form.start_date);
    }

    if(form.end_date){
        form.end_date = formatDate(form.end_date);
    }

    form.post(route('configuration.editTradePeriod'), {
        onSuccess: () => {
            form.reset();
            editVisible.value = false;
        },
    });
}

const checked = ref(props.tradePeriod.status === 'active')

watch(() => props.tradePeriod.status, (newStatus) => {
    checked.value = newStatus === 'active';
});

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        activate_trade_period: {
            group: 'headless',
            color: 'primary',
            icon: h(IconUserCheck),
            header: trans('public.activate_calculation_period'),
            message: trans('public.activate_calculation_period_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.activate'),
            action: () => {
                router.post(route('configuration.updatePeriodStatus'), {
                    trade_period_id: props.tradePeriod.id,
                })

                checked.value = !checked.value;
            }
        },
        deactivate_trade_period: {
            group: 'headless',
            color: 'error',
            icon: h(IconUserCancel),
            header: trans('public.deactivate_calculation_period'),
            message: trans('public.deactivate_calculation_period_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.deactivate'),
            action: () => {
                router.post(route('configuration.updatePeriodStatus'), {
                    trade_period_id: props.tradePeriod.id,
                })

                checked.value = !checked.value;
            }
        },
        delete_trade_period: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_trade_calculation_period'),
            message: trans('public.delete_trade_calculation_period_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.delete(route('configuration.deleteTradePeriod'), {
                    data: {
                        trade_period_id: props.tradePeriod.id,
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

const handlePeriodStatus = () => {
    if (props.tradePeriod.status === 'active') {
        requireConfirmation('deactivate_trade_period')
    } else {
        requireConfirmation('activate_trade_period')
    }
}

</script>

<template>
    <div class="flex gap-3 py-3 items-center justify-center">
        <ToggleSwitch
            v-model="checked"
            readonly
            @click="handlePeriodStatus"
            :disabled="props.tradePeriod.status !== 'active' && props.tradePeriod.status !== 'inactive'"
        />
        <Button
            variant="gray-text"
            size="sm"
            type="button"
            iconOnly
            pill
            @click="openEditDialog()"
        >
            <IconPencil size="16" stroke-width="1.5" />
        </Button>
        <Button
            variant="error-text"
            size="sm"
            type="button"
            iconOnly
            pill
            @click="requireConfirmation('delete_trade_period')"
        >
            <IconTrashX size="16" stroke-width="1.5" />
        </Button>
    </div>

    <Dialog v-model:visible="editVisible" modal :header="$t('public.edit_calculation_period')" class="dialog-sm" :closeOnEscape="false">
        <form>
            <div class="flex flex-col gap-5 py-6 self-stretch">
                <div class="flex flex-col gap-2 self-stretch">
                    <InputLabel
                        for="period_name"
                        :value="$t('public.period_name')"
                    />
                    <InputText
                        id="period_name"
                        type="text"
                        class="block w-full"
                        v-model="form.period_name"
                        :invalid="!!form.errors.period_name"
                    />
                    <InputError :message="form.errors.period_name" />
                </div>
                <div class="flex flex-col gap-2 self-stretch">
                    <InputLabel
                        for="start_date"
                        :value="$t('public.start_date')"
                    />
                    <div class="flex flex-row gap-3 self-stretch">
                        <DatePicker
                            v-model="form.start_date"
                            selectionMode="single"
                            dateFormat="yy/mm/dd"
                            :minDate="today"
                            showIcon
                            iconDisplay="input"
                            :placeholder="$t('public.select_date')"
                            class="w-full font-normal"
                            :invalid="!!form.errors.start_date"
                        />
                    </div>
                    <InputError :message="form.errors.start_date" />
                </div>
                <div class="flex flex-col gap-2 self-stretch">
                    <InputLabel
                        for="end_date"
                        :value="$t('public.end_date')"
                    />
                    <div class="flex flex-row gap-3 self-stretch">
                        <DatePicker
                            v-model="form.end_date"
                            selectionMode="single"
                            dateFormat="yy/mm/dd"
                            :minDate="today"
                            showIcon
                            iconDisplay="input"
                            :placeholder="$t('public.select_date')"
                            class="w-full font-normal"
                            :invalid="!!form.errors.end_date"
                        />
                    </div>
                    <InputError :message="form.errors.end_date" />
                </div>
            </div>
            <div class="flex flex-row items-center justify-center w-full gap-4">
                <Button
                    type="button"
                    variant="gray-outlined"
                    size="base"
                    class="w-full"
                    :disabled="form.processing"
                    @click.prevent="editVisible = false"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    type="button"
                    variant="primary-flat"
                    size="base"
                    class="w-full"
                    :disabled="form.processing"
                    @click="submitForm"
                >
                    {{ $t('public.edit') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
