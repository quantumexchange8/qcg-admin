<script setup>
import {  } from '@tabler/icons-vue';
import { DepositIcon, WithdrawalIcon } from '@/Components/Icons/outline';
import { computed, h, ref, watchEffect } from "vue";
import Empty from '@/Components/Empty.vue';
import { wTrans } from "laravel-vue-i18n";
import { transactionFormat } from "@/Composables/index.js";
import { usePage } from "@inertiajs/vue3";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import dayjs from "dayjs";
import Loader from "@/Components/Loader.vue";
import Vue3autocounter from 'vue3-autocounter';

const props = defineProps({
    user_id: Number
})

const totalDeposit = ref(99999);
const totalWithdrawal = ref(99999);
const transactionHistory = ref([]);
const rebateWallet = ref(null);
const isLoading = ref(false);
const counterDuration = ref(10);

const { formatAmount, formatDateTime } = transactionFormat();

const getFinancialData = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get(`/member/getFinancialInfoData?id=${props.user_id}`);

        totalDeposit.value = response.data.totalDeposit;
        totalWithdrawal.value = response.data.totalWithdrawal;
        transactionHistory.value = response.data.transactionHistory;
        rebateWallet.value = response.data.rebateWallet;
    } catch (error) {
        console.error('Error get info:', error);
    } finally {
        isLoading.value = false;
        counterDuration.value = 1
    }
};
getFinancialData();

const overviewData = computed(() =>  [
    {
        label: wTrans('public.total_deposit'),
        value: totalDeposit.value,
        icon: DepositIcon,
    },
    {
        label: wTrans('public.total_withdrawal'),
        value: totalWithdrawal.value,
        icon: WithdrawalIcon,
    },
]);

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getFinancialData();
    }
});
</script>

<template>
    <div class="flex flex-col md:flex-row items-start gap-5 self-stretch">
        <div class="flex flex-col md:flex-row gap-5 w-full">
            <!-- Overview -->
            <div class="flex flex-row md:flex-col items-center gap-3 md:gap-5 self-stretch w-full">
                <div
                    v-for="overview in overviewData"
                    class="w-full flex flex-col justify-center items-center px-3 py-7 gap-4 rounded-lg bg-white shadow-card md:px-6"
                >
                    <component :is="overview.icon" class="w-9 h-9" 
                        :class="{
                            'text-success-600' : overview.icon == DepositIcon,
                            'text-error-600' : overview.icon == WithdrawalIcon,
                        }"
                     />
                    <div class="self-stretch text-center text-gray-700 text-xs font-medium md:text-sm flex-wrap break-all w-full">{{ overview.label }}</div>
                    <div class="self-stretch text-center text-gray-950 font-semibold md:text-xl flex-wrap break-all w-full">$&nbsp;
                        <Vue3autocounter ref="counter" :startAmount="0" :endAmount="Number(overview.value)" :duration="counterDuration" separator="," decimalSeparator="." :decimals="2" :autoinit="true" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="bg-white flex flex-col p-3 md:py-5 md:px-6 gap-3 w-full self-stretch shadow-card rounded-lg max-h-[400px] md:max-h-[372px] overflow-auto">
            <div class="h-9 flex items-center gap-7 flex-shrink-0 self-stretch">
                <div class="text-gray-950 text-sm font-bold">{{ $t('public.recent_transaction' )}}</div>
            </div>
            <div v-if="isLoading" class="flex flex-col gap-2 items-center justify-center">
                <Loader />
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>
            <div v-else-if="transactionHistory?.length <= 0" class="flex flex-col items-center flex-1 self-stretch">
              <Empty :message="$t('public.empty_transaction_message')">
                  <template #image></template>
              </Empty>
            </div>

            <div v-else class="flex flex-col items-center flex-1 self-stretch overflow-auto" style="-ms-overflow-style: none; scrollbar-width: none;">
                <DataTable
                  :value="transactionHistory"
                  class="grid"
                >
                    <template v-if="transactionHistory?.length > 0">
                        <Column field="approved_at" :header="$t('public.date')" style="width: 25%" class="px-3">
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm break-all">
                                    {{ dayjs(slotProps.data.approved_at).format('YYYY/MM/DD') }}
                                </div>
                            </template>
                        </Column>
                        <Column field="meta_login" :header="$t('public.account')" style="width: 25%" class="px-3">
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm break-all">
                                    {{ slotProps.data?.from_meta_login || slotProps.data?.to_meta_login || '-' }}
                                </div>
                            </template>
                        </Column>
                        <Column field="transaction_amount" :header="`${$t('public.amount')}&nbsp;($)`" style="width: 25%" class="px-3">
                            <template #body="slotProps">
                                <div class="text-sm break-all"
                                    :class="{
                                    'text-success-600': slotProps.data.transaction_type === 'deposit' || slotProps.data.transaction_type === 'balance_in',
                                    'text-error-600': slotProps.data.transaction_type === 'withdrawal' || slotProps.data.transaction_type === 'balance_out',
                                    }"
                                >
                                    {{ formatAmount(slotProps.data?.transaction_amount || 0) }}
                                </div>
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </div>
    </div>
</template>