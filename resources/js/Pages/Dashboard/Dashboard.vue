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
    IconUserFilled,
    IconCaretUpFilled,
    IconCaretDownFilled,
    IconAlertCircle,
} from '@tabler/icons-vue';
import {
    DepositIcon,
    WithdrawalIcon,
    AgentIcon,
    MemberIcon,
    KycIcon,
    RewardsIcon
} from '@/Components/Icons/outline.jsx';
import { computed, ref, watch, watchEffect, onMounted } from "vue";
import { trans } from "laravel-vue-i18n";
import Vue3autocounter from 'vue3-autocounter';
import Badge from '@/Components/Badge.vue';
import { router } from '@inertiajs/vue3'
import Select from "primevue/select";
import dayjs from "dayjs";
import Divider from 'primevue/divider';
import DashboardTeamView from "@/Pages/Dashboard/DashboardTeamView.vue";

const props = defineProps({
    months: Array,
    teamMonths: Array,
});

const user = usePage().props.auth.user;
const { formatAmount } = transactionFormat();
const { hasRole, hasPermission } = usePermission();

const counterDuration = ref(10);

const totalDeposit = ref(0);
const totalWithdrawal = ref(0);
const totalAgent = ref(0);
const totalMember = ref(0);
const ytdDeposit = ref(0);
const todayDeposit = ref(0);
const lastMonthDeposit = ref(0);
const currentMonthDeposit = ref(0);
const ytdWithdrawal = ref(0);
const todayWithdrawal = ref(0);
const lastMonthWithdrawal = ref(0);
const currentMonthWithdrawal = ref(0);
const todayAgent = ref(0);
const todayMember = ref(0);

const accountBalanceDuration = ref(10);
const counterEquity = ref(null);
const counterBalance = ref(null);
const balance = ref(0)
const equity = ref(0)
const accountLoading = ref(false);

const selectedMonth = ref('');
const selectedTeamMonth = ref('');
const selectedPnlMonth = ref('');
const months = ref(props.months);
const teamMonths = ref(props.teamMonths);

const tradeLotVolumeDuration = ref(10);
const counterTradeLot = ref(null);
const counterVolume = ref(null);
const trade_lot = ref(0)
const volume = ref(0)
const tradeLotVolumeLoading = ref(false);

const tradeBrokerPnlDuration = ref(10);
const counterTradeBrokerPnl = ref(null);
const swap = ref(0);
const markup = ref(0);
const gross = ref(0);
const broker = ref(0);
const avgWin = ref(0);
const avgLoss = ref(0);
const trader = ref(0);
const tradeBrokerPnlLoading = ref(false);

const getCurrentMonthYear = () => {
    const date = new Date();
    return `01 ${dayjs(date).format('MMMM YYYY')}`;
};

const getCurrentMonth = () => {
    const date = new Date();
    return dayjs(date).format('MM/YYYY');
};

watch([months, teamMonths], ([newMonths, newTeamMonths]) => {
    if (newMonths.length > 0) {
        // console.log(newMonths[0].value)
        selectedMonth.value = getCurrentMonth();
        selectedPnlMonth.value = getCurrentMonth();
    }
    if (newTeamMonths.length > 0) {
        // console.log(newTeamMonths[0])
        selectedTeamMonth.value = getCurrentMonthYear();
    }
}, { immediate: true });

// Function to navigate with query parameters
const navigateWithQueryParams = (route, type) => {
    // If 'type' exists, append it as a query parameter
    const url = route + (type ? `?type=${type}` : '');
    router.visit(url);
};

// data overview
const dataOverviews = computed(() => [
    {
        icon: DepositIcon,
        total: totalDeposit.value,
        yesterday: ytdDeposit.value,
        today: todayDeposit.value,
        last_month: lastMonthDeposit.value,
        current_month: currentMonthDeposit.value,
        label: trans('public.dashboard_total_deposit'),
        route: 'transaction/deposit',
    },
    {
        icon: WithdrawalIcon,
        total: totalWithdrawal.value,
        yesterday: ytdWithdrawal.value,
        today: todayWithdrawal.value,
        last_month: lastMonthWithdrawal.value,
        current_month: currentMonthWithdrawal.value,
        label: trans('public.dashboard_total_withdrawal'),
        route: 'transaction/withdrawal',
    },
    {
        icon: AgentIcon,
        total: totalAgent.value,
        today: todayAgent.value,
        label: trans('public.dashboard_total_agent'),
        route: 'member/listing',
        type: 'agent'
    },
    {
        icon: MemberIcon,
        total: totalMember.value,
        today: todayMember.value,
        label: trans('public.dashboard_total_member'),
        route: 'member/listing',
        type: 'member'
    },
]);

const getDashboardData = async () => {
    try {
        const response = await axios.get('dashboard/getDashboardData');
        totalDeposit.value = response.data.totalDeposit;
        totalWithdrawal.value = response.data.totalWithdrawal;
        totalAgent.value = response.data.totalAgent;
        totalMember.value = response.data.totalMember;
        ytdDeposit.value = response.data.ytdDeposit;
        todayDeposit.value = response.data.todayDeposit;
        lastMonthDeposit.value = response.data.lastMonthDeposit;
        currentMonthDeposit.value = response.data.currentMonthDeposit;
        ytdWithdrawal.value = response.data.ytdWithdrawal;
        todayWithdrawal.value = response.data.todayWithdrawal;
        lastMonthWithdrawal.value = response.data.lastMonthWithdrawal;
        currentMonthWithdrawal.value = response.data.currentMonthWithdrawal;
        todayAgent.value = response.data.todayAgent;
        todayMember.value = response.data.todayMember;
    } catch (error) {
        console.error('Error pending counts:', error);
    } finally {
        counterDuration.value = 1
    }
};

getDashboardData();

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

const pendingWithdrawalCount = ref(0);
const pendingKycCount = ref(0);
const pendingTicketsCount = ref(0);
const pendingRewardsCount = ref(0);
const pendingTotalCount = ref(0);
const pendingLoading = ref(false);

const getPendingData = async () => {
    pendingLoading.value = true;
    try {
        const response = await axios.get('dashboard/getPendingCounts');
        pendingWithdrawalCount.value = response.data.pendingWithdrawals;
        pendingKycCount.value = response.data.pendingKyc;
        pendingTicketsCount.value = response.data.pendingTickets;
        pendingRewardsCount.value = response.data.pendingRewards;

        pendingTotalCount.value = pendingWithdrawalCount.value + pendingKycCount.value + pendingTicketsCount.value + pendingRewardsCount.value;
    } catch (error) {
        console.error('Error pending data:', error);
        pendingLoading.value = false;
    } finally {
        counterDuration.value = 1
        pendingLoading.value = false;
    }
};

getPendingData();

const pendingOverview = computed(() => [
    {
        pendingCount: pendingWithdrawalCount.value,
        label: trans('public.withdrawal'),
        route: 'pending/withdrawal',
    },
    {
        pendingCount: pendingKycCount.value,
        label: trans('public.kyc'),
        route: 'pending/kyc',
    },
    {
        pendingCount: pendingTicketsCount.value,
        label: trans('public.tickets'),
        route: 'tickets/pending',
    },
    {
        pendingCount: pendingRewardsCount.value,
        label: trans('public.rewards'),
        route: 'pending/rewards',
    },
]);

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

// Watch for changes in selectedMonth and trigger getTradeLotVolume
watch(selectedMonth,(newMonth, oldMonth) => {
        if (newMonth !== oldMonth) {
            getTradeLotVolume();
        }
    }
);

const getTradeBrokerPnl = async () => {
    tradeBrokerPnlLoading.value = true;
    try {
        const response = await axios.get(`dashboard/getTradeBrokerPnl?selectedMonth=${selectedPnlMonth.value}`);
        
        // Process response data here
        swap.value = response.data.totalSwap;
        markup.value = response.data.totalMarkup;
        gross.value = response.data.totalGross;
        broker.value = response.data.totalBroker;
        avgWin.value = response.data.avgWin;
        avgLoss.value = response.data.avgLoss;
        trader.value = -broker.value;
        tradeBrokerPnlDuration.value = 1;
    } catch (error) {
        console.error('Error fetching data:', error);
        tradeBrokerPnlLoading.value = false;
    } finally {
        tradeBrokerPnlDuration.value = 1;
        tradeBrokerPnlLoading.value = false;
    }
};

getTradeBrokerPnl();

// Watch for changes in selectedMonth and trigger getTradeLotVolume
watch(selectedPnlMonth,(newMonth, oldMonth) => {
        if (newMonth !== oldMonth) {
            getTradeBrokerPnl();
        }
    }
);

const updateBalEquity = () => {
    // Reset the counters if they are properly initialized and have a reset() method
    if (counterEquity.value && counterEquity.value.reset) {
        counterEquity.value.reset();
    }
    if (counterBalance.value && counterBalance.value.reset) {
        counterBalance.value.reset();
    }
    getAccountData();
};

const updateTradeLotVolume = () => {
    // Reset the trade lot and volume counters if they have a reset() method
    if (counterTradeLot.value && counterTradeLot.value.reset) {
        counterTradeLot.value.reset();
    }
    if (counterVolume.value && counterVolume.value.reset) {
        counterVolume.value.reset();
    }
    getTradeLotVolume();
};

const updateTradeBrokerPnl = () => {
    // Reset the trade lot and volume counters if they have a reset() method
    if (counterTradeBrokerPnl.value && counterTradeBrokerPnl.value.reset) {
        counterTradeBrokerPnl.value.reset();
    }

    getTradeBrokerPnl();
};

watch(() => usePage().props, (newProps, oldProps) => {
    if (newProps.toast !== oldProps.toast || newProps.notification !== oldProps.notification) {
        // If either toast or notification changes, trigger the actions
        getDashboardData();
        getAccountData();
        getPendingData();
        getTradeLotVolume();
        getTradeBrokerPnl();
    }
}, { deep: true });

</script>

<template>
    <AuthenticatedLayout :title="$t('public.dashboard')">
        <div v-if="hasRole('super-admin') || hasPermission('access_dashboard')" class="w-full grid grid-cols-1 2xl:grid-cols-2 items-center gap-3 md:gap-5">
            <div class="w-full flex flex-col items-start gap-3 md:gap-5">
                <!-- overview data -->
                <div class="grid grid-cols-2 w-full gap-2 md:gap-5">
                    <div class="flex flex-col bg-white justify-center rounded-lg shadow-box w-full cursor-pointer items-center"
                        :class="{'col-span-2 md:col-span-1': (item.icon === DepositIcon || item.icon === WithdrawalIcon)}"
                        v-for="(item, index) in dataOverviews" :key="index"
                        @click="navigateWithQueryParams(item.route, item.type)"
                    >
                        <div class="flex w-full gap-2 items-center md:pb-2 md:pt-4 md:px-6 pb-1 self-stretch"
                        :class="{
                            'px-6 pt-3.5': item.icon === DepositIcon || item.icon == WithdrawalIcon,
                            'px-2 pt-2': item.icon === AgentIcon || item.icon === MemberIcon,
                        }">
                            <component v-if="item.icon" :is="item.icon" class="grow-0 md:h-9 md:w-9 shrink-0"
                                :class="{
                                    'text-success-600 h-9 w-9': item.icon == DepositIcon,
                                    'text-error-600 h-9 w-9': item.icon == WithdrawalIcon,
                                    'text-orange h-6 w-6': item.icon == AgentIcon,
                                    'text-cyan h-6 w-6': item.icon == MemberIcon,
                                }" 
                            />

                            <div class="grid grid-cols-1 w-full gap-1 items-end">
                                <span class="text-gray-500 text-right text-xxs md:text-sm self-stretch truncate">{{ item.label }}</span>
                                <span v-if="(item.total || item.total === 0) && !pendingLoading" class="text-gray-950 text-right font-semibold md:text-xl self-stretch truncate"
                                    :class="{'text-xl': item.icon === DepositIcon || item.icon == WithdrawalIcon,}">
                                    <template v-if="item.icon === AgentIcon || item.icon === MemberIcon">
                                        {{ formatAmount(item.total, 0) }}
                                    </template>
                                    <template v-else>
                                        $ {{ formatAmount(item.total) }}
                                    </template>
                                </span>
                                <span v-else class="flex justify-end text-gray-950 text-right animate-pulse font-semibold md:text-xl self-stretch truncate">
                                    <div class="bg-gray-200 h-2.5 rounded-full w-1/3"></div>
                                </span>

                                <div v-if="(item.icon === AgentIcon || item.icon === MemberIcon)  && !pendingLoading" class="flex gap-2 items-center">
                                    <div class="flex justify-end w-full gap-0.5 items-center">
                                        <IconCaretUpFilled 
                                            v-if="[AgentIcon, MemberIcon].includes(item.icon)"
                                            class="h-3 w-3 md:h-4 md:w-4"
                                            :class="{'text-success-500': item.today > 0, 'text-gray-950': item.today <= 0}"
                                        />
                                        <span 
                                            class="text-right text-sm font-medium truncate"
                                            :class="{
                                                'text-success-500': item.today > 0 && item.icon !== WithdrawalIcon, 
                                                'text-error-600': item.icon === WithdrawalIcon && item.today > 0,
                                                'text-gray-950': item.today <= 0,
                                            }"
                                        >
                                            {{ formatAmount(item.today, 0) }}
                                        </span>
                                    </div>
                                    <span class="text-gray-500 text-nowrap text-right text-sm hidden md:block">{{ $t('public.today') }}</span>
                                </div>

                            
                            </div>
                        </div>
                        <div v-if="(item.icon === DepositIcon || item.icon === WithdrawalIcon)  && !pendingLoading" class="rounded-lg bg-gray-50 p-3 flex flex-row items-center md:mb-2 md:mx-6 mb-1 mx-3 self-stretch">
                            <div class="flex flex-col gap-2 flex-1 min-w-0">
                                <div class="flex flex-col items-end justify-center">
                                    <span class="text-gray-500 text-xxs text-right truncate w-full">{{ $t('public.yesterday') }}</span>
                                    <span class="text-gray-950 text-sm text-right font-medium truncate w-full">$ {{ formatAmount(item.yesterday) }}</span>
                                </div>
                                <div v-if="(item.today || item.today === 0)  && !pendingLoading" class="flex flex-col items-center">
                                    <span class="w-full text-gray-500 text-nowrap text-right text-xxs truncate">{{ $t('public.today') }}</span>
                                    <div class="flex justify-end w-full gap-0.5 items-center">
                                        <IconCaretUpFilled 
                                            v-if="item.today > item.yesterday"
                                            class="h-3 w-3 md:h-4 md:w-4"
                                            :class="{'text-success-500': item.icon === DepositIcon, 'text-error-600': item.icon === WithdrawalIcon}"
                                        />
                                        <IconCaretDownFilled 
                                            v-else-if="item.today < item.yesterday"
                                            class="h-3 w-3 md:h-4 md:w-4"
                                            :class="{'text-success-500': item.icon === DepositIcon, 'text-error-600': item.icon === WithdrawalIcon}"
                                        />
                                        <span 
                                            class="text-right text-sm truncate font-medium block"
                                            :class="{
                                                'text-success-500': item.today > 0 && item.icon !== WithdrawalIcon, 
                                                'text-error-600': item.icon === WithdrawalIcon && item.today > 0,
                                                'text-gray-950': item.today <= 0,
                                            }"
                                        >
                                            $ {{ formatAmount(item.today) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <Divider layout="vertical"/>
                            <div class="flex flex-col gap-2 flex-1 min-w-0">
                                <div class="flex flex-col items-end justify-center">
                                    <span class="text-gray-500 text-xxs text-right truncate w-full">{{ $t('public.last_month') }}</span>
                                    <span class="text-gray-950 text-sm text-right font-medium truncate w-full">$ {{ formatAmount(item.yesterday) }}</span>
                                </div>
                                <div v-if="(item.current_month || item.current_month === 0)  && !pendingLoading" class="flex flex-col items-center">
                                    <span class="w-full text-gray-500 text-nowrap text-right text-xxs truncate">{{ $t('public.current_month') }}</span>
                                    <div class="flex justify-end w-full gap-0.5 items-center">
                                        <IconCaretUpFilled 
                                            v-if="item.current_month > item.last_month"
                                            class="h-3 w-3 md:h-4 md:w-4"
                                            :class="{'text-success-500': item.icon === DepositIcon, 'text-error-600': item.icon === WithdrawalIcon}"
                                        />
                                        <IconCaretDownFilled 
                                            v-else-if="item.current_month < item.last_month"
                                            class="h-3 w-3 md:h-4 md:w-4"
                                            :class="{'text-success-500': item.icon === DepositIcon, 'text-error-600': item.icon === WithdrawalIcon}"
                                        />
                                        <span 
                                            class="text-right text-sm truncate font-medium block"
                                            :class="{
                                                'text-success-500': item.current_month > 0 && item.icon !== WithdrawalIcon, 
                                                'text-error-600': item.icon === WithdrawalIcon && item.current_month > 0,
                                                'text-gray-950': item.current_month <= 0,
                                            }"
                                        >
                                            $ {{ formatAmount(item.current_month) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center w-full gap-2 items-center px-2 self-stretch"
                        :class="{'py-2': item.icon === DepositIcon || item.icon == WithdrawalIcon}">
                            <IconDots class="h-4 text-gray-400 w-4" />
                        </div>
                    </div>
                </div>
                <!-- account listing and forum link -->
                <div class="flex w-full gap-2 items-center flex-row md:gap-5">
                    <div 
                        class="flex bg-white rounded-lg shadow-box w-full cursor-pointer gap-1 md:gap-3 items-center md:px-6 md:py-3 px-3 py-2 truncate"
                        @click="router.visit(route('member.account_listing'))"
                    >
                        <span class="text-gray-950 text-sm w-full font-semibold md:text-base truncate">{{ $t('public.ctrader_account_listing') }}</span>
                        <Button 
                            variant="gray-text" 
                            size="sm" 
                            type="button" 
                            iconOnly v-slot="{ iconSizeClasses }"
                        >
                            <IconChevronRight size="16" stroke-width="1.25" color="#374151" />
                        </Button>
                    </div>
                </div>
                <div class="w-full h-full flex flex-col items-center gap-3 md:gap-5">
                    <div class="w-full flex items-center pl-2.5 gap-1">
                        <span class="text-sm text-gray-950 font-semibold">{{ $t('public.pending_request') }}</span>
                        <IconAlertCircle v-if="!pendingLoading && pendingTotalCount > 0" class="h-4 text-red-500 w-4" />
                    </div>

                    <div class="w-full h-full flex flex-row justify-center items-center gap-2 md:gap-5 py-0 md:py-1">
                        <div class="w-full bg-white rounded-lg shadow-box px-3 py-2 md:px-6 md:pt-4 md:pb-2 flex flex-col gap-2 md:gap-3 items-center cursor-pointer"
                            v-for="(item, index) in pendingOverview" :key="index"
                            @click="navigateWithQueryParams(item.route)">
                            <span class="text-gray-500 text-xxs md:text-sm">{{ item.label }}</span>
                            <span v-if="pendingLoading" class="w-5 flex items-center h-5">
                                <div class="bg-gray-200 h-2.5 rounded-full w-full animate-pulse"></div>
                            </span>
                            <span v-else class="text-error-600 text-base md:text-lg font-semibold">{{ item.pendingCount }}</span>
                        </div>
                    </div>
                </div>
                <div class="w-full h-full flex flex-col items-center gap-3 md:gap-5">
                    <!-- account balance & equity, request -->
                    <div class="w-full h-full flex flex-col items-center pt-2 pb-3 px-3 gap-3 rounded-lg bg-white shadow-box md:p-6 md:gap-5">
                        <div class="w-full flex items-center md:h-9 gap-1">
                            <span class="w-full truncate text-gray-950 text-sm font-semibold md:text-base">{{ $t('public.account_balance_equity') }}</span>
                            <Button 
                                variant="gray-text" 
                                size="sm" 
                                type="button" 
                                iconOnly 
                                v-slot="{ iconSizeClasses }"
                                @click="updateBalEquity()"
                                pill
                                class="border border-gray-200"
                            >
                                <IconRefresh size="16" stroke-width="1.25" color="#374151" />
                            </Button>
                        </div>

                        <div class="w-full h-full flex flex-row justify-center items-center gap-2 md:gap-5 ">
                            <div class="w-full h-full grid grid-cols-1 justify-center items-center py-3 px-0.5 gap-1 bg-gray-50 md:px-0">
                                <span class="w-full truncate text-gray-500 text-center text-xxs md:text-sm">{{ $t('public.total_balance') }}</span>
                                <span v-if="(balance || balance === 0) && !accountLoading" class="w-full truncate text-gray-950 text-center font-semibold md:text-xl">
                                    $ {{ formatAmount(balance) }}
                                </span>
                                <span v-else class="flex justify-center text-gray-950 text-right animate-pulse font-semibold items-center md:text-xl self-stretch truncate">
                                    <div class="bg-gray-200 h-2.5 rounded-full w-1/3"></div>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 bg-gray-50 h-full justify-center w-full gap-1 items-center md:px-0 px-0.5 py-3">
                                <span class="text-center text-gray-500 text-xxs w-full md:text-sm truncate">{{ $t('public.total_equity') }}</span>
                                <span v-if="(equity || equity === 0) && !accountLoading" class="text-center text-gray-950 w-full font-semibold md:text-xl truncate">
                                    $ {{ formatAmount(equity) }}
                                </span>
                                <span v-else class="flex justify-center text-gray-950 text-right animate-pulse font-semibold items-center md:text-xl self-stretch truncate">
                                    <div class="bg-gray-200 h-2.5 rounded-full w-1/3"></div>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="w-full h-full flex flex-col items-center p-3 gap-3 rounded-lg bg-white shadow-box md:p-6 md:gap-5">
                        <div class="w-full flex justify-between items-center gap-1">
                            <Select 
                                v-model="selectedMonth" 
                                :options="months" 
                                optionLabel="name" 
                                optionValue="value"
                                :placeholder="$t('public.month_placeholder')"
                                class="w-full sm:w-60 font-normal truncate self-start" scroll-height="236px" 
                            />
                            <Button 
                                variant="gray-text" 
                                size="sm" 
                                type="button" 
                                iconOnly 
                                v-slot="{ iconSizeClasses }"
                                @click="updateTradeLotVolume()"
                                pill
                                class="border border-gray-200"
                            >
                                <IconRefresh size="16" stroke-width="1.25" color="#374151" />
                            </Button>
                        </div>

                        <span class="w-full truncate text-gray-950 text-sm font-semibold md:text-base">{{ $t('public.total_trade_dashboard') }}</span>

                        <div class="w-full h-full flex flex-row justify-center items-center gap-2 md:gap-5 ">
                            <div class="w-full h-full grid grid-cols-1 justify-center items-center py-3 px-0.5 gap-1 bg-gray-50 md:px-0">
                                <span class="w-full truncate text-gray-500 text-center text-xxs md:text-sm">{{
                                    $t('public.total_trade_lots') }}</span>
                                <span v-if="(trade_lot || trade_lot === 0) && !tradeLotVolumeLoading"
                                    class="text-center text-gray-950 w-full font-semibold md:text-xl truncate">
                                    {{ formatAmount(trade_lot) }} Ł
                                </span>
                                <span v-else class="flex justify-center text-gray-950 text-right animate-pulse font-semibold items-center md:text-xl self-stretch truncate">
                                    <div class="bg-gray-200 h-2.5 rounded-full w-1/3"></div>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 bg-gray-50 h-full justify-center w-full gap-1 items-center md:px-0 px-0.5 py-3">
                                <span class="text-center text-gray-500 text-xxs w-full md:text-sm truncate">{{ $t('public.total_trade_volume') }}</span>
                                <span v-if="(volume || volume === 0) && !tradeLotVolumeLoading" class="text-center text-gray-950 w-full font-semibold md:text-xl truncate">
                                    {{ formatAmount(volume, 0) }}
                                </span>
                                <span v-else class="flex justify-center text-gray-950 text-right animate-pulse font-semibold items-center md:text-xl self-stretch truncate">
                                    <div class="bg-gray-200 h-2.5 rounded-full w-1/3"></div>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- P&L Section -->
                    <div class="w-full h-full flex flex-col items-start p-3 gap-3 rounded-lg bg-white shadow-box md:p-6 md:gap-5">
                        <div class="w-full flex justify-between items-center gap-1">
                            <Select 
                                v-model="selectedPnlMonth" 
                                :options="months" 
                                optionLabel="name" 
                                optionValue="value"
                                :placeholder="$t('public.month_placeholder')"
                                class="w-full sm:w-60 font-normal truncate" scroll-height="236px" 
                            />
                            <Button 
                                variant="gray-text" 
                                size="sm" 
                                type="button" 
                                iconOnly 
                                v-slot="{ iconSizeClasses }"
                                @click="updateTradeBrokerPnl()"
                                pill
                                class="border border-gray-200"
                            >
                                <IconRefresh size="16" stroke-width="1.25" color="#374151" />
                            </Button>
                        </div>

                        <span class="w-full truncate text-gray-950 text-sm font-semibold md:text-base">{{ $t('public.total_brokerage_dashboard') }}</span>

                        <div class="w-full h-full flex flex-col justify-center items-center gap-2 md:gap-5 md:flex-row ">
                            <div class="min-w-60 w-full h-full grid grid-cols-1 justify-center items-center p-3 gap-2 bg-gray-50">
                                <div class="flex flex-row gap-1 w-full justify-between items-center">
                                    <span class="text-xs text-gray-500 w-[140px]">{{ $t('public.swap_pnl') }} ($)</span>
                                    <span v-if="tradeBrokerPnlLoading" class="w-20 flex items-center justify-end h-5">
                                        <div class="bg-gray-200 h-2.5 rounded-full w-full animate-pulse"></div>
                                    </span>
                                    <span v-else class="text-sm font-medium text-gray-950 self-stretch">{{ formatAmount(swap, 2) }}</span>
                                </div>
                                <div class="flex flex-row gap-1 w-full justify-between items-center">
                                    <span class="text-xs text-gray-500 w-[140px]">{{ $t('public.markup_pnl') }} ($)</span>
                                    <span v-if="tradeBrokerPnlLoading" class="w-20 flex items-center justify-end h-5">
                                        <div class="bg-gray-200 h-2.5 rounded-full w-full animate-pulse"></div>
                                    </span>
                                    <span v-else class="text-sm font-medium text-gray-950 self-stretch">{{ formatAmount(markup, 2) }}</span>
                                </div>
                                <div class="flex flex-row gap-1 w-full justify-between items-center">
                                    <span class="text-xs text-gray-500 w-[140px]">{{ $t('public.gross_pnl') }} ($)</span>
                                    <span v-if="tradeBrokerPnlLoading" class="w-20 flex items-center justify-end h-5">
                                        <div class="bg-gray-200 h-2.5 rounded-full w-full animate-pulse"></div>
                                    </span>
                                    <span v-else class="text-sm font-medium text-gray-950 self-stretch">{{ formatAmount(gross, 2) }}</span>
                                </div>
                                <div class="flex flex-row gap-1 w-full justify-between items-center">
                                    <span class="text-xs text-gray-500 w-[140px]">{{ $t('public.broker_pnl') }} ($)</span>
                                    <span v-if="tradeBrokerPnlLoading" class="w-20 flex items-center justify-end h-5">
                                        <div class="bg-gray-200 h-2.5 rounded-full w-full animate-pulse"></div>
                                    </span>
                                    <span v-else
                                        :class="['text-sm', 'font-medium', 'self-stretch', broker >= 0 ? 'text-green-500' : 'text-red-500']"
                                    >
                                        {{ formatAmount(broker, 2) }}
                                    </span>
                                </div>
                            </div>

                            <div class="min-w-60 w-full h-full grid grid-cols-1 justify-center items-center p-3 gap-2 bg-gray-50">
                                <div class="flex flex-row gap-1 w-full justify-between items-center">
                                    <span class="text-xs text-gray-500 w-[140px]">{{ $t('public.net_pnl') }} ($)</span>
                                    <span v-if="tradeBrokerPnlLoading" class="w-20 flex items-center justify-end h-5">
                                        <div class="bg-gray-200 h-2.5 rounded-full w-full animate-pulse"></div>
                                    </span>
                                    <span v-else class="text-sm font-medium text-gray-950 self-stretch">{{ formatAmount(trader, 2) }}</span>
                                </div>
                                <div class="flex flex-row gap-1 w-full justify-between items-center">
                                    <span class="text-xs text-gray-500 w-[140px]">{{ $t('public.losing_deals') }} ($)</span>
                                    <span v-if="tradeBrokerPnlLoading" class="w-20 flex items-center justify-end h-5">
                                        <div class="bg-gray-200 h-2.5 rounded-full w-full animate-pulse"></div>
                                    </span>
                                    <span v-else class="text-sm font-medium text-gray-950 self-stretch">Avg. {{ formatAmount(avgLoss, 0) }}</span>
                                </div>
                                <div class="flex flex-row gap-1 w-full justify-between items-center">
                                    <span class="text-xs text-gray-500 w-[140px]">{{ $t('public.win_deals') }} ($)</span>
                                    <span v-if="tradeBrokerPnlLoading" class="w-20 flex items-center justify-end h-5">
                                        <div class="bg-gray-200 h-2.5 rounded-full w-full animate-pulse"></div>
                                    </span>
                                    <span v-else class="text-sm font-medium text-gray-950 self-stretch">Avg. {{ formatAmount(avgWin, 0) }}</span>
                                </div>
                                <div class="flex flex-row gap-1 w-full justify-between items-center">
                                    <span class="text-xs text-gray-500 w-[140px]">{{ $t('public.trader_pnl') }} ($)</span>
                                    <span v-if="tradeBrokerPnlLoading" class="w-20 flex items-center justify-end h-5">
                                        <div class="bg-gray-200 h-2.5 rounded-full w-full animate-pulse"></div>
                                    </span>
                                    <span v-else
                                        :class="['text-sm', 'font-medium', 'self-stretch', trader >= 0 ? 'text-green-500' : 'text-red-500']"
                                    >
                                        {{ formatAmount(trader, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- sales team -->
            <DashboardTeamView  :months="months" :teamMonths="teamMonths"/>
        </div>
    </AuthenticatedLayout>
</template>
