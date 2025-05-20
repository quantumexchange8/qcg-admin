<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ToggleSwitch from 'primevue/toggleswitch';
import Button from "@/Components/Button.vue";
import { computed, h, ref, watch, watchEffect } from "vue";
import { usePage, useForm } from "@inertiajs/vue3";
import {
    IconUserCheck,
    IconUserCancel,
    IconPlus,
} from "@tabler/icons-vue";
import { FilterMatchMode } from '@primevue/core/api';
import { trans, wTrans } from "laravel-vue-i18n";
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { useConfirm } from "primevue/useconfirm";
import ClearTradePoints from "./Partials/ClearTradePoints.vue";
import EditTradePointDetails from "./Partials/EditTradePointDetails.vue";
import NewCalcPeriod from "./Partials/NewCalcPeriod.vue";
import Action from "./Partials/Action.vue";
import { transactionFormat } from "@/Composables/index.js";
import dayjs from "dayjs";
import { router } from "@inertiajs/vue3";

const checked = ref(true);
const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        enable_point_calculation: {
            group: 'headless',
            color: 'primary',
            icon: h(IconUserCheck),
            header: trans('public.enable_trade_point_calculation'),
            message: trans('public.enable_trade_point_calculation_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.enable'),
            action: () => {
                router.post(route('configuration.updateCalculationStatus'), {
                    trade_period_id: overallPeriod.value?.id,
                })

                checked.value = !checked.value;
            }
        },
        disable_point_calculation: {
            group: 'headless',
            color: 'error',
            icon: h(IconUserCancel),
            header: trans('public.disable_trade_point_calculation'),
            message: trans('public.disable_trade_point_calculation_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.disable'),
            action: () => {
                router.post(route('configuration.updateCalculationStatus'), {
                    trade_period_id: overallPeriod.value?.id,
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

// Define the status options
const statusOption = [
    { name: wTrans('public.all'), value: null },
    { name: wTrans('public.active'), value: 'active' },
    { name: wTrans('public.inactive'), value: 'inactive' }
];

const filteredValue = ref();

const filters = ref({
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
};

const overallPeriod = ref({});
const tradeDetails = ref();
const tradePeriods = ref();
const loading = ref(false);

const { formatAmount, formatDate } = transactionFormat();

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/configuration/getTradePointData');
        overallPeriod.value = response.data.overallPeriod;
        tradeDetails.value = response.data.tradeDetails;
        tradePeriods.value = response.data.tradePeriods;
    } catch (error) {
        console.error('Error fetch trade point details:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

watch(
    () => overallPeriod.value?.status,
    (newStatus) => {
        checked.value = newStatus === 'active';
    },
    { immediate: true }
);

const handleCalculationStatus = () => {
    if (overallPeriod.value?.status === 'active') {
        requireConfirmation('disable_point_calculation')
    } else {
        requireConfirmation('enable_point_calculation')
    }
}

</script>

<template>
    <AuthenticatedLayout :title="`${$t('public.sidebar_configuration')}&nbsp;-&nbsp;${$t('public.sidebar_trade_point_setting')}`">
        <div class="flex flex-col justify-center items-center gap-5 self-stretch">
            <div class="flex flex-col justify-center items-center px-6 py-5 self-stretch rounded-lg bg-white shadow-card gap-3">
                <div class="flex flex-row gap-3 items-center self-stretch">
                    <ToggleSwitch 
                        v-model="checked"
                        readonly
                        @click="handleCalculationStatus"
                        :disabled="loading === true"
                    />
                    <span class="flex-1 text-sm text-gray-950 font-semibold">
                        {{ $t('public.enable_trade_point_calculation') }}
                    </span>
                    <ClearTradePoints/>
                </div>
                <span class="self-stretch text-sm text-gray-700">
                    {{ $t('public.trade_point_calculation_notice') }}
                </span>
            </div>
            <div class="flex flex-col justify-center items-center px-6 py-5 self-stretch rounded-lg bg-white shadow-card gap-3">
                <div class="flex flex-row justify-between items-center self-stretch">
                    <span class="text-sm text-gray-950 font-semibold">
                        {{ $t('public.trade_point_details') }}
                    </span>
                    <EditTradePointDetails 
                        :tradeDetails="tradeDetails"
                    />
                </div>

                <div class="flex flex-col items-center self-stretch">
                    <div class="flex items-center w-full self-stretch py-3 text-gray-700 bg-gray-50 border-b border-gray-100">
                        <span class="uppercase text-xs font-bold px-3 w-full">{{ $t('public.product') }}</span>
                        <span class="uppercase text-xs font-bold px-3 w-full">{{ $t('public.trade_point') }} / Ł (TP)</span>
                    </div>

                    <!-- symbol groups -->
                    <div
                        v-if="tradeDetails"
                        v-for="tradeDetail in tradeDetails"
                        class="flex items-center w-full self-stretch py-2 text-gray-950"
                    >
                        <div class="text-sm px-3 w-full">{{ $t(`public.${tradeDetail.symbol_group.display}`) }}</div>
                        <div class="text-sm px-3 w-full">{{ formatAmount(tradeDetail.trade_point_rate, 2) }}</div>
                    </div>
                    <div
                        v-else
                        v-for="index in 5"
                        class="flex items-center w-full self-stretch py-2 text-gray-950 animate-pulse"
                    >
                        <div class="w-full">
                            <div class="h-2.5 bg-gray-200 rounded-full w-36 mt-1 mb-1.5"></div>
                        </div>
                        <div class="w-full">
                            <div class="h-2.5 bg-gray-200 rounded-full w-10 mt-1 mb-1.5"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-col justify-center items-center px-6 py-5 self-stretch rounded-lg bg-white shadow-card gap-6">
                <div class="flex flex-row justify-between items-center self-stretch">
                    <Select
                        v-model="filters['status'].value"
                        :options="statusOption"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.filter_by_status')"
                        class="w-full md:max-w-60 font-normal"
                        scroll-height="236px"
                    >
                        <template #value="data">
                            <span class="font-normal text-gray-950" >{{ $t('public.' + (data.value || 'all')) }}</span>
                        </template>
                    </Select>
                    <NewCalcPeriod />
                </div>
                <DataTable
                    v-model:filters="filters"
                    :value="tradePeriods"
                    removableSort
                    :globalFilterFields="['period_name', 'status']"
                    ref="dt"
                    :loading="loading"
                    @filter="handleFilter"
                >
                    <template>
                        <Column field="period_name" :header="$t('public.period_name')">
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm truncate max-w-full">
                                    {{ slotProps.data.period_name }}
                                </div>
                            </template>
                        </Column>
                        <Column field="start_date" :header="$t('public.start_date')" sortable>
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm truncate max-w-full">
                                    {{ dayjs(slotProps.data.start_date).format('YYYY/MM/DD') }}
                                </div>
                            </template>
                        </Column>
                        <Column field="end_date" :header="$t('public.end_date')" sortable>
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm truncate max-w-full">
                                    {{ dayjs(slotProps.data.end_date).format('YYYY/MM/DD') }}
                                </div>
                            </template>
                        </Column>
                        <Column field="action" headless>
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm truncate max-w-full">
                                    <Action
                                        :tradePeriod="slotProps.data"
                                    />
                                </div>
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>

</template>