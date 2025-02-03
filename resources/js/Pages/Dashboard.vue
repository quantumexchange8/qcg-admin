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
import dayjs from "dayjs";

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
const todayDeposit = ref(0);
const todayWithdrawal = ref(0);
const todayAgent = ref(0);
const todayMember = ref(0);

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
const selectedTeamMonth = ref('');
const months = ref(props.months);
const teamMonths = ref(props.teamMonths);

const tradeLotVolumeDuration = ref(10);
const counterTradeLot = ref(null);
const counterVolume = ref(null);
const trade_lot = ref(0)
const volume = ref(0)
const tradeLotVolumeLoading = ref(false);

const teams = ref();
const counterTeam = ref(null);
const teamLoading = ref(false);
const teamDuration = ref(10);

watch([months, teamMonths], ([newMonths, newTeamMonths]) => {
    if (newMonths.length > 0) {
        selectedMonth.value = newMonths[newMonths.length - 1].value;
    }
    if (newTeamMonths.length > 0) {
        selectedTeamMonth.value = newTeamMonths[newTeamMonths.length - 1];
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
    {
        icon: DepositIcon,
        total: totalDeposit.value,
        today: todayDeposit.value,
        label: trans('public.dashboard_total_deposit'),
        route: 'transaction/deposit',
    },
    {
        icon: WithdrawalIcon,
        total: totalWithdrawal.value,
        today: todayWithdrawal.value,
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
        todayDeposit.value = response.data.todayDeposit;
        todayWithdrawal.value = response.data.todayWithdrawal;
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

const getTeamsData = async () => {
    teamLoading.value = true;
    try {
        const response = await axios.get(`dashboard/getTeamsData?selectedMonth=${dayjs(selectedTeamMonth.value, 'DD MMMM YYYY').format('MMMM YYYY')}`);
        
        // Process response data here
        teams.value = response.data.teams;

        teamDuration.value = 1;
    } catch (error) {
        console.error('Error fetching data:', error);
        teamLoading.value = false;
    } finally {
        teamDuration.value = 1
        teamLoading.value = false;
    }
};

getTeamsData();

// Watch for changes in selectedMonth and trigger getTradeLotVolume
watch(selectedTeamMonth,(newTeamMonth, oldTeamMonth) => {
        if (newTeamMonth !== oldTeamMonth) {
            getTeamsData();
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

const updateTeamsData = () => {
    // Reset the team counters if it have a reset() method
    if (counterTeam.value && counterTeam.value.reset) {
        counterTeam.value.reset();
    }
    getTeamsData();
};

watch(() => usePage().props, (newProps, oldProps) => {
    if (newProps.toast !== oldProps.toast || newProps.notification !== oldProps.notification) {
        // If either toast or notification changes, trigger the actions
        getDashboardData();
        getAccountData();
        getPendingData();
        getTradeLotVolume();
        getTeamsData();
    }
}, { deep: true });

</script>

<template>
    <AuthenticatedLayout :title="$t('public.dashboard')">
        <div v-if="hasRole('super-admin') || hasPermission('access_dashboard')" class="w-full grid grid-cols-1 4xl:grid-cols-2 items-center gap-3 md:gap-5">
            <div class="w-full flex flex-col items-start gap-3 md:gap-5">
                <!-- overview data -->
                <div class="w-full grid grid-cols-2 gap-3 md:gap-5">
                    <div class="w-full flex flex-col justify-center items-center rounded-lg bg-white shadow-card cursor-pointer"
                        v-for="(item, index) in dataOverviews" :key="index"
                        @click="navigateWithQueryParams(item.route, item.type)"
                    >
                        <div class="w-full flex items-center px-2 pt-2 pb-1 gap-2 self-stretch md:px-6 md:pt-4 md:pb-2">
                            <div v-if="item.pendingCount" class="flex items-center justify-center">
                                <Badge 
                                    variant="error"
                                    class="w-6 h-6 md:w-9 md:h-9 self-stretch truncate text-white text-center text-xs font-medium md:text-base"
                                >
                                    {{ item.pendingCount }}
                                </Badge>
                            </div>
                            <component v-if="item.icon" :is="item.icon" class="w-6 h-6 md:w-9 md:h-9 grow-0 shrink-0"
                                :class="{
                                    'text-success-600': item.icon == DepositIcon,
                                    'text-error-600': item.icon == WithdrawalIcon,
                                    'text-orange': item.icon == AgentIcon,
                                    'text-cyan': item.icon == MemberIcon,
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
                                <div v-if="(item.today || item.today === 0)  && !pendingLoading" class="flex items-center gap-2">
                                    <div class="w-full justify-end flex items-center gap-0.5">
                                        <IconCaretUpFilled 
                                            v-if="[DepositIcon, AgentIcon, MemberIcon].includes(item.icon)"
                                            class="w-3 h-3 md:w-4 md:h-4"
                                            :class="{'text-success-500': item.today > 0, 'text-gray-950': item.today <= 0}"
                                        />
                                        <span 
                                            class="truncate text-right text-sm font-medium"
                                            :class="{
                                                'text-success-500': item.today > 0 && item.icon !== WithdrawalIcon, 
                                                'text-error-600': item.icon === WithdrawalIcon && item.today > 0,
                                                'text-gray-950': item.today <= 0,
                                            }"
                                        >
                                            <template v-if="item.icon === AgentIcon || item.icon === MemberIcon">
                                                {{ formatAmount(item.today, 0) }}
                                            </template>
                                            <template v-else>
                                                $ {{ formatAmount(item.today) }}
                                            </template>
                                        </span>
                                    </div>
                                    <span class="hidden md:block text-gray-500 text-right text-sm text-nowrap">{{ $t('public.today') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="w-full flex justify-center items-center px-2 gap-2 self-stretch">
                            <IconDots class="w-4 h-4 text-gray-400" />
                        </div>
                    </div>
                </div>
                <!-- account listing and forum link -->
                <div class="w-full flex flex-col md:flex-row items-center gap-3 md:gap-5">
                    <div 
                        class="w-full flex items-center py-2 px-3 gap-3 rounded-lg bg-white shadow-card md:py-3 md:px-6 cursor-pointer"
                        @click="router.visit(route('member.account_listing'))"
                    >
                        <span class="w-full truncate text-gray-950 text-sm font-semibold md:text-base">{{ $t('public.ctrader_account_listing') }}</span>
                        <Button 
                            variant="gray-text" 
                            size="sm" 
                            type="button" 
                            iconOnly v-slot="{ iconSizeClasses }"
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
                <div class="w-full h-full flex flex-col xl:flex-row 4xl:flex-col items-center gap-3 md:gap-5">
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

                        <div class="w-full h-full flex justify-center items-center gap-2 md:gap-5 xl:flex-col 4xl:flex-row">
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
                                class="w-60 font-normal truncate" scroll-height="236px" 
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

                        <div class="w-full h-full flex justify-center items-center gap-2 md:gap-5 xl:flex-col 4xl:flex-row">
                            <div class="w-full h-full grid grid-cols-1 justify-center items-center py-3 px-0.5 gap-1 bg-gray-50 md:px-0">
                                <span class="w-full truncate text-gray-500 text-center text-xxs md:text-sm">{{
                                    $t('public.total_trade_lots') }}</span>
                                <span v-if="(trade_lot || trade_lot === 0) && !tradeLotVolumeLoading"
                                    class="w-full truncate text-gray-950 text-center font-semibold md:text-xl">
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
            <div class="w-full h-full flex flex-col items-center p-3 gap-3 rounded-lg bg-white shadow-card md:p-6 md:gap-8">
                <div class="w-full flex justify-between items-center">
                    <Select 
                        v-model="selectedTeamMonth" 
                        :options="teamMonths" 
                        :placeholder="$t('public.month_placeholder')"
                        class="w-60 font-normal truncate" scroll-height="236px" 
                    >
                        <template #option="{option}">
                            <span class="text-sm">{{ option.replace(/^\d+\s/, '') }}</span>
                        </template>
                        <template #value>
                            <span v-if="selectedTeamMonth">
                                {{ dayjs(selectedTeamMonth).format('MMMM YYYY') }}
                            </span>
                            <span v-else>
                                {{ $t('public.month_placeholder') }}
                            </span>
                        </template>
                    </Select>
                    <Button 
                        variant="gray-text" 
                        size="sm" 
                        type="button" 
                        iconOnly 
                        v-slot="{ iconSizeClasses }"
                        @click="updateTeamsData()"
                    >
                        <IconRefresh size="16" stroke-width="1.25" color="#374151" />
                    </Button>
                </div>

                <div class="w-full grid grid-cols-1 xl:grid-cols-2 4xl:grid-cols-1 gap-3 md:gap-6">
                    <div
                        v-if="teamLoading"
                        class="w-full flex flex-col items-center rounded bg-white shadow-card"
                    >
                        <div class="w-full flex py-1 px-3 items-center gap-3 bg-gray-500 md:py-2 md:px-4">
                            <span class="w-full text-white font-medium truncate animate-pulse">
                                <div class="h-3 bg-gray-200 rounded-full w-20"></div>
                            </span>
                            <div class="flex items-center gap-2 text-white">
                                <IconUserFilled size="20" stroke-width="1.25" />
                                <span class="text-right font-medium animate-pulse">
                                    <div class="h-3 bg-gray-200 rounded-full w-5"></div>
                                </span>
                            </div>
                        </div>
                        <div class="w-full flex flex-col items-center p-3 gap-2 md:p-4 md:gap-3">
                            <div class="w-full grid grid-cols-2 gap-2 xl:grid-cols-3">
                                <div class="w-full flex flex-col items-start gap-1">
                                    <span class="w-full truncate text-gray-500 text-xxs md:text-xs">{{ $t('public.team_deposit') }}</span>
                                    <span class="w-full truncate text-gray-950 font-semibold text-sm md:text-base animate-pulse">
                                        <div class="h-3 bg-gray-200 rounded-full w-30"></div>
                                    </span>
                                </div>
                                <div class="w-full flex flex-col items-start gap-1">
                                    <span class="w-full truncate text-gray-500 text-xxs md:text-xs">{{ $t('public.team_withdrawal') }}</span>
                                    <span class="w-full truncate text-gray-950 font-semibold text-sm md:text-base animate-pulse">
                                        <div class="h-3 bg-gray-200 rounded-full w-30"></div>
                                    </span>
                                </div>
                                <div class="w-full flex flex-col items-start gap-1">
                                    <span class="w-full truncate text-gray-500 text-xxs md:text-xs">{{ $t('public.team_net_balance') }}</span>
                                    <span class="w-full truncate text-gray-950 font-semibold text-sm md:text-base animate-pulse">
                                        <div class="h-3 bg-gray-200 rounded-full w-30"></div>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        v-for="team in teams"
                        :key="team.id"
                        class="w-full flex flex-col items-center rounded bg-white shadow-card"
                    >
                        <div 
                            class="w-full flex py-1 px-3 items-center gap-3 md:py-2 md:px-4"
                            :style="{'backgroundColor': `#${team.color}`}"
                        >
                            <span class="w-full text-white font-medium truncate">{{ team.name }}</span>
                            <div class="flex items-center gap-2 text-white">
                                <IconUserFilled size="20" stroke-width="1.25" />
                                <span class="text-right font-medium">{{ formatAmount(team.member_count, 0) }}</span>
                            </div>
                        </div>
                        <div class="w-full flex flex-col items-center p-3 gap-2 md:p-4 md:gap-3">
                            <div class="w-full grid grid-cols-2 gap-2 xl:grid-cols-3">
                                <div class="w-full flex flex-col items-start gap-1">
                                    <span class="truncate text-gray-500 text-xxs md:text-xs">{{ $t('public.team_deposit') }}</span>
                                    <span class="truncate text-gray-950 font-semibold text-sm md:text-base">$&nbsp;{{ formatAmount(team?.deposit || 0) }}</span>
                                </div>
                                <div class="w-full flex flex-col items-start gap-1">
                                    <span class="truncate text-gray-500 text-xxs md:text-xs">{{ $t('public.team_withdrawal') }}</span>
                                    <span class="truncate text-gray-950 font-semibold text-sm md:text-base">$&nbsp;{{ formatAmount(team?.withdrawal || 0) }}</span>
                                </div>
                                <div class="w-full flex flex-col items-start gap-1">
                                    <span class="truncate text-gray-500 text-xxs md:text-xs">{{ $t('public.team_net_balance') }}</span>
                                    <span class="truncate text-gray-950 font-semibold text-sm md:text-base">$&nbsp;{{ formatAmount(team?.net_balance || 0) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
