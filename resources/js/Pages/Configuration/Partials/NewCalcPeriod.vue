<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {ref, watch, computed} from "vue";
import { useForm } from "@inertiajs/vue3";
import {
    IconPlus,
} from "@tabler/icons-vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import dayjs from "dayjs";
import DatePicker from 'primevue/datepicker';
import { transactionFormat } from "@/Composables/index.js";

const { formatAmount, formatDate } = transactionFormat();

const newVisible = ref(false);

const form = useForm({
    period_name: '',
    start_date: '',
    end_date: '',
})

const today = new Date();

const openNewDialog = () => {
    form.reset();
    newVisible.value = true;
}

const submitForm = () => {
    if(form.start_date){
        form.start_date = formatDate(form.start_date);
    }

    if(form.end_date){
        form.end_date = formatDate(form.end_date);
    }

    // console.log(form);
    form.post(route('configuration.createTradePeriod'), {
        onSuccess: () => {
            form.reset();
            newVisible.value = false;
        },
    });
}


</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        size="base"
        class="w-full md:w-auto"
        @click="openNewDialog()"
    >
        <IconPlus size="20" stroke-width="1.25" />
        {{ $t('public.new_calculation_period') }}
    </Button>

    <Dialog v-model:visible="newVisible" modal :header="$t('public.new_calculation_period')" class="dialog-xs md:dialog-sm" :closeOnEscape="false">
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
                    @click.prevent="newVisible = false"
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
                    {{ $t('public.create') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
