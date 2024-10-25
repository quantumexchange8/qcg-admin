<script setup>
import { ref, watchEffect } from "vue";
import { usePage } from "@inertiajs/vue3";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { transactionFormat } from "@/Composables/index.js";
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import dayjs from "dayjs";

const props = defineProps({
    user_id: Number
})

const adjustmentHistories = ref([]);
const { formatAmount } = transactionFormat();
const loading = ref(false);

const getAdjustmentHistoryData = async () => {
    loading.value = true;

    try {
        const response = await axios.get(`/member/getAdjustmentHistoryData?id=${props.user_id}`);
        adjustmentHistories.value = response.data;
    } catch (error) {
        console.error('Error fetching adjustment history data:', error);
    } finally {
        loading.value = false;
    }
}
getAdjustmentHistoryData();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getAdjustmentHistoryData();
    }
});

// Toggle expand state on click
const toggleExpand = (index) => {
  adjustmentHistories.value[index].isExpanded = !adjustmentHistories.value[index].isExpanded;
};
</script>

<template>

    <!-- data table -->
    <div class="flex flex-col items-center justify-center p-6 gap-6 self-stretch rounded-lg shadow-card h-[400px] md:bg-white"
        :class="{
            'bg-white': adjustmentHistories?.length > 0,
        }"
    >
        <DataTable
            :value="adjustmentHistories"
            removableSort
            :loading="loading"
            selectionMode="single"
            class="hidden md:block h-full w-full"
            scrollable
            scrollHeight="350px"
        >
            <template #empty>
                <Empty class="h-[280px]" :message="$t('public.no_history_yet')">
                    <template #image></template>
                </Empty>
            </template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loader />
                    <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                </div>
            </template>
            <Column field="created_at" sortable style="width: 20%" headerClass="hidden md:table-cell">
                <template #header>
                    <span class="hidden md:block">{{ $t('public.date') }}</span>
                </template>
                <template #body="slotProps">
                    {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                </template>
            </Column>
            <Column field="account_no" style="width: 20%" headerClass="hidden md:table-cell">
                <template #header>
                    <span class="hidden md:block">{{ $t('public.account') }}</span>
                </template>
                <template #body="slotProps">
                    <span v-if="slotProps.data.transaction_type === 'balance_in' || slotProps.data.transaction_type === 'credit_in'">{{ slotProps.data.to_meta_login }}</span>
                    <span v-else-if="slotProps.data.transaction_type === 'balance_out' || slotProps.data.transaction_type === 'credit_out'">{{ slotProps.data.from_meta_login }}</span>
                    <span v-else>-</span>
                </template>
            </Column>
            <Column field="transaction_type" style="width: 20%" headerClass="hidden md:table-cell">
                <template #header>
                    <span class="hidden md:block">{{ $t('public.type') }}</span>
                </template>
                <template #body="slotProps">
                    {{ $t(`public.${slotProps.data.transaction_type}`) }}
                </template>
            </Column>
            <Column field="amount" sortable style="width: 20%" headerClass="hidden md:table-cell">
                <template #header>
                    <span class="hidden md:block">{{ $t('public.amount') }} ($)</span>
                </template>
                <template #body="slotProps">
                    {{ formatAmount(slotProps.data.transaction_amount) }}
                </template>
            </Column>
            <Column field="remarks" style="width: 20%" headerClass="hidden md:table-cell">
                <template #header>
                    <span class="hidden md:block">{{ $t('public.remarks') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data.remarks }}
                </template>
            </Column>
        </DataTable>

        <div v-if="adjustmentHistories?.length <= 0" class="flex flex-col items-center flex-1 self-stretch md:hidden">
            <Empty class="h-[280px]" :message="$t('public.no_history_yet')">
                <template #image></template>
            </Empty>
        </div>

        <div v-else class="flex flex-col items-center self-stretch overflow-auto md:hidden" style="-ms-overflow-style: none; scrollbar-width: none;">
            <div
                v-for="(history, index) in adjustmentHistories"
                :key="index"
                class="flex flex-col py-2 items-start gap-1 self-stretch border-b border-gray-200"
                :class="{ 'border-transparent': index === history.length - 1 }"
                @click="toggleExpand(index)"
            >
                <div class="flex justify-between items-center self-stretch">
                    <span v-if="history.transaction_type === 'balance_in' || history.transaction_type === 'credit_in'" class="text-gray-700 text-xs">
                        {{ history.to_meta_login }}
                    </span>
                    <span v-else-if="history.transaction_type === 'balance_out' || history.transaction_type === 'credit_out'" class="text-gray-700 text-xs">
                        {{ history.from_meta_login }}
                    </span>
                    <span v-else class="text-gray-700 text-xs">-</span>
                    <span class="text-gray-700 text-xs"> {{ dayjs(history.created_at).format('YYYY/MM/DD') }}</span>
                </div>
                <div class="flex justify-between items-center self-stretch">
                    <span class="text-gray-950 font-semibold">
                        {{ $t(`public.${history.transaction_type}`) }}
                    </span>
                    <span class="text-gray-950 font-semibold truncate">$&nbsp;{{ formatAmount(history.transaction_type === 'deposit' ? history.transaction_amount : history.amount) }}</span>
                </div>
                <div v-if="history.isExpanded" class="grid grid-cols-[auto_1fr] items-center gap-1">
                    <span class="text-gray-500 text-xs">{{ $t('public.remark') }}:</span>
                    <span class="text-gray-700 text-xs truncate">{{ history.remarks }}</span>
                </div>
            </div>
        </div>

    </div>
</template>
