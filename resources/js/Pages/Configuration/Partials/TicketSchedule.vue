<script setup>
import Button from "@/Components/Button.vue";
import ToggleSwitch from 'primevue/toggleswitch';
import { ref, watch, watchEffect } from "vue";
import { useForm } from "@inertiajs/vue3";
import DatePicker from 'primevue/datepicker';
import {
    IconClock,
} from '@tabler/icons-vue';
import {usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";

const ticketSchedule = ref();
const loading = ref(false);

const form = useForm({
    settings: [],
    schedule_check: false,
});

// const scheduleCheck = ref(false);
watch(() => form.schedule_check, (newVal) => {
    if (!newVal) {
        form.settings.forEach(setting => {
            setting.status = false;
        });
    }
});

const getSettings = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/configuration/getTicketScheduleSettings');
        ticketSchedule.value = response.data.ticketSchedule;
        
        form.settings = ticketSchedule.value.map(setting => ({
            day: setting.day,
            status: setting.status === 'active',
            start_time: setting.start_time,
            end_time: setting.end_time,
        }));
        form.schedule_check = ticketSchedule.value.some(setting => !!setting.is_enabled);

        //  console.log('test')
        //  console.log(form.settings)
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
    //  console.log(form.settings)
    form.post(route('configuration.updateTicketScheduleSettings'), {
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
    <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
        <form class="self-stretch">
            <div class="flex flex-col justify-center items-start gap-5 md:gap-6 self-stretch">
                <div class="flex flex-col gap-3 self-stretch">
                    <div class="flex flex-row gap-3">
                        <ToggleSwitch 
                            v-model="form.schedule_check"
                        />
                        <span class="text-sm font-semibold text-gray-950">{{ $t("public.enable_ticket_center_scheduler") }}</span>
                    </div>
                    <span class="text-sm text-gray-950">{{ $t("public.ticket_center_scheduler_desc") }}</span>
                </div>
                <div class="flex flex-col self-stretch">
                    <div v-for="(setting, index) in form.settings" :key="setting.day" class="flex flex-col md:flex-row py-2 gap-3 items-center self-stretch">
                        <div class="flex flex-row gap-3 items-center flex-1 self-stretch">
                            <ToggleSwitch 
                                v-model="setting.status"
                                :disabled="!form.schedule_check"
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
                <Button
                    variant="primary-flat"
                    :disabled="form.processing"
                    @click="submitForm"
                    class="self-end"
                >
                    {{ $t('public.save_changes') }}
                </Button>
            </div>
        </form>
    </div>
</template>