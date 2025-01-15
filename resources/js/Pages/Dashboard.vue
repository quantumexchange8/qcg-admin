<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Button.vue';
import { usePage } from "@inertiajs/vue3";
import { transactionFormat, usePermission } from "@/Composables/index.js";
import { 
    IconDots,
    IconRefresh, 
    IconChevronRight, 
    IconChevronDown,
} from '@tabler/icons-vue';
import {
    DepositIcon,
    WithdrawalIcon,
    AgentIcon,
    MemberIcon,
} from '@/Components/Icons/outline.jsx';
import { computed, ref, watch, watchEffect, onMounted } from "vue";
import { trans } from "laravel-vue-i18n";
import Vue3autocounter from 'vue3-autocounter';
import Badge from '@/Components/Badge.vue';
import { router } from '@inertiajs/vue3'
import Select from "primevue/select";

const props = defineProps({
    months: Array,
});

const user = usePage().props.auth.user;
const { formatAmount } = transactionFormat();
const { hasRole, hasPermission } = usePermission();

const counterDuration = ref(10);

const totalDeposit = ref(0);
const totalWithdrawal = ref(0);
const totalAgent = ref(0);
const totalMember = ref(0);

const accountBalanceDuration = ref(10);
const counterEquity = ref(null);
const counterBalance = ref(null);
const balance = ref(0)
const equity = ref(0)
const accountLoading = ref(false);

const pendingWithdrawal = ref(0);
const pendingWithdrawalCount = ref(0);
const pendingBonus = ref(0);
const pendingBonusCount = ref(0);
// const pendingIncentive = ref(0);
// const pendingIncentiveCount = ref(0);
const pendingLoading = ref(false);

const selectedMonth = ref('');
const months = ref(props.months);

const tradeLotVolumeDuration = ref(10);
const counterTradeLot = ref(null);
const counterVolume = ref(null);
const trade_lot = ref(0)
const volume = ref(0)
const tradeLotVolumeLoading = ref(false);

// Watch for changes in the 'months' array and set 'selectedMonth' to the latest month
watch(months, (newMonths) => {
    if (newMonths.length > 0) {
        // Directly take the last item (most recent month)
        selectedMonth.value = newMonths[newMonths.length - 1].value;
    }
}, { immediate: true });  // Run immediately to set the initial value on component mount

// Function to navigate with query parameters
const navigateWithQueryParams = (route, type) => {
    // If 'type' exists, append it as a query parameter
    const url = route + (type ? `?type=${type}` : '');
    router.visit(url);
};

// data overview
const dataOverviews = computed(() => [
    {
        pendingCount: pendingWithdrawalCount.value,
        total: pendingWithdrawal.value,
        label: trans('public.dashboard_withdrawal_request'),
        route: 'pending/withdrawal',
    },
    {
        pendingCount: pendingBonusCount.value,
        total: pendingBonus.value,
        label: trans('public.dashboard_bonus_request'),
        route: 'pending/bonus',
    },
    {
        icon: AgentIcon,
        total: totalAgent.value,
        label: trans('public.dashboard_total_agent'),
        route: 'member/listing',
        type: 'agent'
    },
    {
        icon: MemberIcon,
        total: totalMember.value,
        label: trans('public.dashboard_total_member'),
        route: 'member/listing',
        type: 'member'
    },
    {
        icon: DepositIcon,
        total: totalDeposit.value,
        label: trans('public.dashboard_total_deposit'),
        route: 'transaction/deposit',
    },
    {
        icon: WithdrawalIcon,
        total: totalWithdrawal.value,
        label: trans('public.dashboard_total_withdrawal'),
        route: 'transaction/withdrawal',
    },
]);

const getDashboardData = async () => {
    try {
        const response = await axios.get('dashboard/getDashboardData');
        totalDeposit.value = response.data.totalDeposit;
        totalWithdrawal.value = response.data.totalWithdrawal;
        totalAgent.value = response.data.totalAgent;
        totalMember.value = response.data.totalMember;
    } catch (error) {
        console.error('Error pending counts:', error);
    } finally {
        counterDuration.value = 1
    }
};

getDashboardData();

const updateBalEquity = () => {
    counterEquity.value.reset();
    counterBalance.value.reset();
    getAccountData();
}

const getAccountData = async () => {
    accountLoading.value = true;
    try {
        const response = await axios.get('dashboard/getAccountData');
        balance.value = response.data.totalBalance;
        equity.value = response.data.totalEquity;

        accountBalanceDuration.value = 1
    } catch (error) {
        console.error('Error accounts data:', error);
        accountLoading.value = false;
    } finally {
        accountBalanceDuration.value = 1
        accountLoading.value = false;
    }
};

getAccountData();

const getPendingData = async () => {
    pendingLoading.value = true;
    try {
        const response = await axios.get('dashboard/getPendingData');
        pendingWithdrawal.value = response.data.pendingWithdrawal;
        pendingWithdrawalCount.value = response.data.pendingWithdrawalCount;
        pendingBonus.value = response.data.pendingBonus;
        pendingBonusCount.value = response.data.pendingBonusCount;
        // pendingIncentive.value = response.data.pendingIncentive;
        // pendingIncentiveCount.value = response.data.pendingIncentiveCount;
    } catch (error) {
        console.error('Error pending data:', error);
        pendingLoading.value = false;
    } finally {
        counterDuration.value = 1
        pendingLoading.value = false;
    }
};

getPendingData();

// Watch for changes in selectedMonth and trigger getTradeLotVolume
watch( selectedMonth,(newMonth, oldMonth) => {
        if (newMonth !== oldMonth) {
            getTradeLotVolume();
        }
    }
);

const updateTradeLotVolume = () => {
    counterTradeLot.value.reset();
    counterVolume.value.reset();
    getTradeLotVolume();
}

const getTradeLotVolume = async () => {
    tradeLotVolumeLoading.value = true;
    try {
        const response = await axios.get(`dashboard/getTradeLotVolume?selectedMonth=${selectedMonth.value}`);
        
        // Process response data here
        trade_lot.value = response.data.totalTradeLots;
        volume.value = response.data.totalVolume;

        tradeLotVolumeDuration.value = 1;
    } catch (error) {
        console.error('Error fetching data:', error);
        tradeLotVolumeLoading.value = false;
    } finally {
        tradeLotVolumeDuration.value = 1
        tradeLotVolumeLoading.value = false;
    }
};

getTradeLotVolume();

watch(() => usePage().props, (newProps, oldProps) => {
    if (newProps.toast !== oldProps.toast || newProps.notification !== oldProps.notification) {
        // If either toast or notification changes, trigger the actions
        getDashboardData();
        getAccountData();
        getPendingData();
        getTradeLotVolume();
    }
}, { deep: true });

</script>

<template>
    <AuthenticatedLayout :title="$t('public.dashboard')">
        <div v-if="hasRole('super-admin') || hasPermission('access_dashboard')" class="w-full grid grid-cols-1 md:grid-cols-2 items-center gap-3 md:gap-5">
            <div class="w-full flex flex-col items-start gap-3 md:gap-5">
                <!-- overview data -->
                <div class="w-full grid grid-cols-2 gap-3 md:grid-cols-1 md:gap-5 4xl:grid-cols-2">
                    <div 
                        class="w-full flex flex-col justify-center items-center rounded-lg bg-white shadow-card cursor-pointer"
                        v-for="(item, index) in dataOverviews"
                        :key="index"
                        @click="navigateWithQueryParams(item.route, item.type)"
                    >
                        <div class="w-full flex items-center px-2 pt-2 pb-1 gap-2 self-stretch md:px-6 md:pt-4 md:pb-2">
                            <div v-if="item.pendingCount" class="flex items-center justify-center">
                                <Badge variant="error" class="w-6 h-6 md:w-9 md:h-9 self-stretch truncate text-white text-center text-xs font-medium md:text-base">
                                    {{ item.pendingCount }}
                                </Badge>
                            </div>
                            <component v-if="item.icon" :is="item.icon" class="w-6 h-6 md:w-9 md:h-9 grow-0 shrink-0" 
                                :class="{
                                    'text-success-600' : item.icon == DepositIcon,
                                    'text-error-600' : item.icon == WithdrawalIcon,
                                    'text-orange' : item.icon == AgentIcon,
                                    'text-cyan' : item.icon == MemberIcon,
                                }"
                            />

                            <div class="w-full grid grid-cols-1 items-end gap-1">
                                <span class="self-stretch truncate text-gray-500 text-right text-xxs md:text-sm">{{ item.label }}</span>
                                <span v-if="(item.total || item.total === 0) && !pendingLoading" class="self-stretch truncate text-gray-950 text-right font-semibold md:text-xl">
                                    <template v-if="item.icon === AgentIcon || item.icon === MemberIcon">
                                        {{ formatAmount(item.total, 0) }}
                                    </template>
                                    <template v-else>
                                        $ {{ formatAmount(item.total) }}
                                    </template>
                                </span>
                                <span v-else class="self-stretch truncate text-gray-950 text-right font-semibold md:text-xl animate-pulse flex justify-end">
                                    <div class="h-2.5 bg-gray-200 rounded-full w-1/3"></div>
                                </span>
                            </div>
                        </div>
                        <div class="w-full flex justify-center items-center px-2 gap-2 self-stretch">
                            <IconDots class="w-4 h-4 text-gray-400" />
                        </div>
                    </div>
                </div>
                <!-- account listing and forum link -->
                <div class="w-full flex flex-col items-center gap-3 md:gap-5">
                    <div 
                        class="w-full flex items-center py-2 px-3 gap-3 rounded-lg bg-white shadow-card md:py-3 md:px-6 cursor-pointer"
                        @click="router.visit(route('member.account_listing'))"
                    >
                        <span class="w-full truncate text-gray-950 text-sm font-semibold md:text-base">{{ $t('public.ctrader_account_listing') }}</span>
                        <Button
                            variant="gray-text"
                            size="sm"
                            type="button"
                            iconOnly
                            v-slot="{ iconSizeClasses }"
                        >
                            <IconChevronRight size="16" stroke-width="1.25" color="#374151" />
                        </Button>
                    </div>
                    
                    <div 
                        class="w-full flex items-center py-2 px-3 gap-3 rounded-lg bg-white shadow-card md:py-3 md:px-6 cursor-pointer"
                        @click="router.visit(route('member.forum'))"
                    >
                        <span class="w-full truncate text-gray-950 text-sm font-semibold md:text-base">{{ $t('public.editing_forum') }}</span>
                        <Button
                            variant="gray-text"
                            size="sm"
                            type="button"
                            iconOnly
                            v-slot="{ iconSizeClasses }"
                        >
                            <IconChevronRight size="16" stroke-width="1.25" color="#374151" />
                        </Button>
                    </div>
                </div>
            </div>

            <div class="w-full h-full flex flex-col items-center gap-3 md:gap-5">
                <!-- account balance & equity, request -->
                <div class="w-full h-full flex flex-col items-center pt-2 pb-3 px-3 gap-3 rounded-lg bg-white shadow-card md:p-6 md:gap-8">
                    <div class="w-full flex items-center md:h-9">
                        <span class="w-full truncate text-gray-950 text-sm font-semibold md:text-base">{{ $t('public.account_balance_equity') }}</span>
                        <Button
                            variant="gray-text"
                            size="sm"
                            type="button"
                            iconOnly
                            v-slot="{ iconSizeClasses }"
                            @click="updateBalEquity()"
                        >
                            <IconRefresh size="16" stroke-width="1.25" color="#374151" />
                        </Button>
                    </div>

                    <div class="w-full h-full flex justify-center items-center gap-2 md:flex-col md:gap-5 4xl:flex-row">
                        <div class="w-full h-full grid grid-cols-1 justify-center items-center py-3 px-0.5 gap-1 bg-gray-50 md:px-0">
                            <span class="w-full truncate text-gray-500 text-center text-xxs md:text-sm">{{ $t('public.total_balance') }}</span>
                            <span v-if="(balance || balance === 0) && !accountLoading" class="w-full truncate text-gray-950 text-center font-semibold md:text-xl">
                                $ {{ formatAmount(balance) }}
                            </span>
                            <span v-else class="self-stretch truncate text-gray-950 text-right font-semibold md:text-xl animate-pulse flex justify-center items-center">
                                <div class="h-2.5 bg-gray-200 rounded-full w-1/3"></div>
                            </span>
                        </div>

                        <div class="w-full h-full grid grid-cols-1 justify-center items-center py-3 px-0.5 gap-1 bg-gray-50 md:px-0">
                            <span class="w-full truncate text-gray-500 text-center text-xxs md:text-sm">{{ $t('public.total_equity') }}</span>
                            <span v-if="(equity || equity === 0) && !accountLoading" class="w-full truncate text-gray-950 text-center font-semibold md:text-xl">
                                $ {{ formatAmount(equity) }}
                            </span>
                            <span v-else class="self-stretch truncate text-gray-950 text-right font-semibold md:text-xl animate-pulse flex justify-center items-center">
                                <div class="h-2.5 bg-gray-200 rounded-full w-1/3"></div>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="w-full h-full flex flex-col items-center p-3 gap-3 rounded-lg bg-white shadow-card md:p-6 md:gap-8">
                    <div class="w-full flex justify-between items-center">
                        <Select
                            v-model="selectedMonth"
                            :options="months"
                            optionLabel="name"
                            optionValue="value"
                            :placeholder="$t('public.month_placeholder')"
                            class="w-full md:w-60 font-normal truncate"
                            scroll-height="236px"
                        />
                        <Button
                            variant="gray-text"
                            size="sm"
                            type="button"
                            iconOnly
                            v-slot="{ iconSizeClasses }"
                            @click="updateTradeLotVolume()"
                        >
                            <IconRefresh size="16" stroke-width="1.25" color="#374151" />
                        </Button>
                    </div>


                    <div class="w-full h-full flex justify-center items-center gap-2 md:flex-col md:gap-5 4xl:flex-row">
                        <div class="w-full h-full grid grid-cols-1 justify-center items-center py-3 px-0.5 gap-1 bg-gray-50 md:px-0">
                            <span class="w-full truncate text-gray-500 text-center text-xxs md:text-sm">{{ $t('public.total_trade_lots') }}</span>
                            <span v-if="(trade_lot || trade_lot === 0) && !tradeLotVolumeLoading" class="w-full truncate text-gray-950 text-center font-semibold md:text-xl">
                                {{ formatAmount(trade_lot) }} Ł
                            </span>
                            <span v-else class="self-stretch truncate text-gray-950 text-right font-semibold md:text-xl animate-pulse flex justify-center items-center">
                                <div class="h-2.5 bg-gray-200 rounded-full w-1/3"></div>
                            </span>
                        </div>

                        <div class="w-full h-full grid grid-cols-1 justify-center items-center py-3 px-0.5 gap-1 bg-gray-50 md:px-0">
                            <span class="w-full truncate text-gray-500 text-center text-xxs md:text-sm">{{ $t('public.total_trade_volume') }}</span>
                            <span v-if="(volume || volume === 0) && !tradeLotVolumeLoading" class="w-full truncate text-gray-950 text-center font-semibold md:text-xl">
                                {{ formatAmount(volume, 0) }}
                            </span>
                            <span v-else class="self-stretch truncate text-gray-950 text-right font-semibold md:text-xl animate-pulse flex justify-center items-center">
                                <div class="h-2.5 bg-gray-200 rounded-full w-1/3"></div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
