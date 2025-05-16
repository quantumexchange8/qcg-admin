<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Button from "@/Components/Button.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputNumber from "primevue/inputnumber";
import ToggleSwitch from 'primevue/toggleswitch';
import { ref, watch, watchEffect } from "vue";
import { useForm } from "@inertiajs/vue3";
import DatePicker from 'primevue/datepicker';
import {
    IconClock,
} from '@tabler/icons-vue';
import {usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";

const approvalSchedule = ref();
const loading = ref(false);

const form = useForm({
    settings: [],
    amount: null,
});

const checked = ref(false);
watch(checked, (newVal) => {
    if (!newVal) {
        form.settings.forEach(setting => {
            setting.status = false;
        });
    }
});

const getSettings = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/configuration/getAutoApprovalSettings');
        approvalSchedule.value = response.data.approvalSchedule;
        
        form.settings = approvalSchedule.value.map(setting => ({
            day: setting.day,
            status: setting.status === 'active',
            start_time: setting.start_time,
            end_time: setting.end_time,
        }));
        checked.value = approvalSchedule.value.some(setting => setting.status === 'active');
        const foundAmount = approvalSchedule.value.find(setting => setting.spread_amount !== null);
        if (foundAmount) {
            form.amount = foundAmount.spread_amount;
        }
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

getSettings();

const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const dayName = (dayNumber) => days[dayNumber - 1] ?? 'Unknown';

const submitForm = () => {
    // console.log(form)
    form.post(route('configuration.updateAutoApprovalSettings'), {
        preserveScroll: true,
        // onError: () => {
        //     console.log(form.errors)
        // },
    });
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getSettings();
    }
});
</script>

<template>
    <AuthenticatedLayout :title="`${$t('public.sidebar_configuration')}&nbsp;-&nbsp;${$t('public.sidebar_auto_deposit')}`">
        <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <form class="self-stretch">
                <div class="flex flex-col justify-center items-start gap-5 md:gap-6 self-stretch">
                    <div class="flex flex-col gap-3 self-stretch">
                        <div class="flex flex-row gap-3">
                            <ToggleSwitch 
                                v-model="checked"
                            />
                            <span class="text-sm font-semibold text-gray-950">{{ $t("public.enable_auto_approval") }}</span>
                        </div>
                        <span class="text-sm text-gray-950">{{ $t("public.auto_approval_desc") }}</span>
                    </div>
                    <div class="flex flex-col self-stretch">
                        <div v-for="(setting, index) in form.settings" :key="setting.day" class="flex flex-col md:flex-row py-2 gap-3 items-center self-stretch">
                            <div class="flex flex-row gap-3 items-center flex-1 self-stretch">
                                <ToggleSwitch 
                                    v-model="setting.status"
                                    :disabled="!checked"
                                />
                                <span class="text-sm font-semibold text-gray-950 flex-1">{{ dayName(setting.day) }}</span>
                            </div>
                            <div class="flex flex-row gap-1 md:gap-3 items-start self-stretch">
                                <div class="flex flex-col gap-1 w-full">
                                    <DatePicker class="w-full md:w-60" :disabled="!setting.status" id="start_time" placeholder="00:00" v-model="setting.start_time" timeOnly fluid iconDisplay="input" showIcon>
                                        <template #inputicon="slotProps">
                                            <span class="cursor-pointer" @click="slotProps.clickCallback">
                                                <IconClock stroke-width="1.25" class="w-5 h-5 text-gray-500" />
                                            </span>
                                        </template>
                                    </DatePicker>
                                    <InputError :message="form.errors[`settings.${index}.start_time`]" />
                                </div>
                                
                                <span class="mt-4 text-sm text-gray-700">-</span>
                                <div class="flex flex-col gap-1 w-full">
                                    <DatePicker class="w-full md:w-60" :disabled="!setting.status" id="end_time" placeholder="08:00" v-model="setting.end_time" timeOnly fluid iconDisplay="input" showIcon>
                                        <template #inputicon="slotProps">
                                            <span class="cursor-pointer" @click="slotProps.clickCallback">
                                                <IconClock stroke-width="1.25" class="w-5 h-5 text-gray-500" />
                                            </span>
                                        </template>
                                    </DatePicker>
                                    <InputError :message="form.errors[`settings.${index}.end_time`]" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 self-stretch">
                        <span class="text-sm font-semibold text-gray-700">{{ $t('public.max_spread_for_auto_approval') }}</span>
                        <InputNumber
                            v-model="form.amount"
                            inputId="maxAmount"
                            prefix="$ "
                            inputClass="py-3 px-4"
                            placeholder="$ 0.00"
                            :min="0"
                            :minFractionDigits="2"
                            fluid
                        />
                        <span class="text-xs text-gray-500">{{ $t('public.max_spread_desc') }}</span>
                        <InputError :message="form.errors.amount" />
                    </div>
                    <Button
                        variant="primary-flat"
                        :disabled="form.processing"
                        @click="submitForm"
                    >
                        {{ $t('public.save_changes') }}
                    </Button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>