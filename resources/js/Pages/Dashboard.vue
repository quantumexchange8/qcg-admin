<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Button.vue';
import { usePage } from "@inertiajs/vue3";
import { transactionFormat, usePermission } from "@/Composables/index.js";
import { 
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
import { computed, ref, watchEffect, onMounted } from "vue";
import { trans } from "laravel-vue-i18n";
import Vue3autocounter from 'vue3-autocounter';
import Badge from '@/Components/Badge.vue';

const user = usePage().props.auth.user;
const { formatAmount } = transactionFormat();
const { hasRole, hasPermission } = usePermission();

const counterDuration = ref(10);

const totalDeposit = ref(99999.00);
const totalWithdrawal = ref(99999.00);
const totalAgent = ref(99999.00);
const totalMember = ref(99999.00);

const accountBalanceDuration = ref(10);
const counterEquity = ref(null);
const counterBalance = ref(null);
const balance = ref(99999.00)
const equity = ref(99999.00)

const pendingWithdrawal = ref(99999.00);
const pendingWithdrawalCount = ref(0);
const pendingIncentive = ref(99999.00);
const pendingIncentiveCount = ref(0);

// data overview
const dataOverviews = computed(() => [
    {
        icon: DepositIcon,
        total: totalDeposit.value,
        label: trans('public.dashboard_total_deposit'),
    },
    {
        icon: WithdrawalIcon,
        total: totalWithdrawal.value,
        label: trans('public.dashboard_total_withdrawal'),
    },
    {
        icon: AgentIcon,
        total: totalAgent.value,
        label: trans('public.dashboard_total_agent'),
    },
    {
        icon: MemberIcon,
        total: totalMember.value,
        label: trans('public.dashboard_total_member'),
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
    try {
        const response = await axios.get('dashboard/getAccountData');
        balance.value = response.data.totalBalance;
        equity.value = response.data.totalEquity;

        accountBalanceDuration.value = 1
    } catch (error) {
        console.error('Error accounts data:', error);
    }
};

getAccountData();

const getPendingData = async () => {
    try {
        const response = await axios.get('dashboard/getPendingData');
        pendingWithdrawal.value = response.data.pendingWithdrawal;
        pendingWithdrawalCount.value = response.data.pendingWithdrawalCount;
        pendingIncentive.value = response.data.pendingIncentive;
        pendingIncentiveCount.value = response.data.pendingIncentiveCount;
    } catch (error) {
        console.error('Error pending data:', error);
    } finally {
        counterDuration.value = 1
    }
};

getPendingData();

watchEffect(() => {
    if (usePage().props.toast !== null || usePage().props.notification !== null) {
        getDashboardData();
        getAccountData();
        getPendingData();
    }
});

</script>

<template>
    <AuthenticatedLayout :title="$t('public.dashboard')">
        <div v-if="hasRole('super-admin') || hasPermission('access_dashboard')" class="w-full flex flex-col items-center gap-5">
            <!-- greeting banner -->
            <div class="relative h-[120px] py-2.5 pl-3 pr-[99px] self-stretch rounded-lg bg-white shadow-card md:h-40 md:py-[26px] md:px-0 xl:py-[52px] overflow-hidden">
                <div class="w-full flex flex-col items-start md:items-center">
                    <div class="w-full md:w-[300px] lg:w-[304px] xl:w-[560px] flex flex-col items-start gap-1 md:items-center">
                        <span class="self-stretch text-primary-700 font-bold md:text-center md:text-xl">
                            {{ $t('public.welcome_back', {'name': user.first_name}) }}
                        </span>
                        <span class="self-stretch text-primary-950 text-xs md:text-center md:text-sm">
                            {{ $t('public.greeting_caption') }}
                        </span>
                    </div>
                </div>

                <!-- Image for small screens positioned on the right -->
                <img src="/img/small-background-greeting-right.svg" alt="no data" class="absolute top-0 right-0 w-[100px] h-[120px] object-contain md:hidden"/>

                <!-- Images for md and larger screens -->
                <!-- Wrapper for the left image -->
                <div class="hidden md:block absolute top-0 left-0 w-[220px] h-[300px] overflow-hidden">
                    <img src="/img/background-greeting-left.svg" alt="no data" />
                </div>

                <!-- Wrapper for the right image -->
                <div class="hidden md:block absolute top-0 right-0 w-[220px] h-[300px] overflow-hidden">
                    <img src="/img/background-greeting-right.svg" alt="no data" />
                </div>
            </div>
            <!-- overview data -->
            <div class="w-full grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-5">
                <div
                    class="w-full flex flex-col justify-center items-center px-3 py-5 gap-3 rounded-lg bg-white shadow-card md:px-6 md:py-7 md:gap-4"
                    v-for="(item, index) in dataOverviews"
                    :key="index"
                >
                    <component :is="item.icon" class="w-9 h-9 grow-0 shrink-0" 
                        :class="{
                            'text-success-600' : item.icon == DepositIcon,
                            'text-error-600' : item.icon == WithdrawalIcon,
                            'text-orange' : item.icon == AgentIcon,
                            'text-cyan' : item.icon == MemberIcon,
                        }"
                    />
                    <span class="self-stretch text-gray-700 text-center text-sm font-medium">{{ item.label }}</span>
                    <div class="self-stretch text-gray-950 text-center text-lg font-semibold md:text-xxl">
                        <template v-if="item.icon === DepositIcon || item.icon === WithdrawalIcon">
                            $&nbsp;<Vue3autocounter ref="counter" :startAmount="0" :endAmount="Number(item.total)" :duration="counterDuration" separator="," decimalSeparator="." :decimals="2" :autoinit="true" />
                        </template>
                        <template v-else>
                            {{ formatAmount(item.total, 0) }}
                        </template>
                    </div>
                </div>
            </div>
            <!-- account balance & equity, request -->
            <div class="w-full grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="w-full flex flex-col items-center px-3 py-5 gap-5 self-stretch rounded-lg bg-white shadow-card md:px-8 md:py-6 md:gap-8">
                    <div class="w-full flex h-9 items-center self-stretch">
                        <span class="w-full text-gray-950 font-bold">{{ $t('public.account_balance_equity') }}</span>
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
                    <div class="w-full flex flex-col justify-center items-center gap-3 self-stretch md:gap-5">
                        <div class="w-full flex flex-col justify-center items-center py-5 gap-1 self-stretch bg-gray-50 md:py-3">
                            <span class="self-stretch text-gray-950 text-center text-lg font-semibold md:text-xxl">
                                <Vue3autocounter ref="counterBalance" :startAmount="0" :endAmount="Number(balance)" :duration="accountBalanceDuration" separator="," decimalSeparator="." :decimals="2" :autoinit="true" />
                            </span>
                            <span class="self-stretch text-gray-500 text-center text-sm">{{ $t('public.total_balance') }}</span>
                        </div>
                        <div class="w-full flex flex-col justify-center items-center py-5 gap-1 self-stretch bg-gray-50 md:py-3">
                            <span class="self-stretch text-gray-950 text-center text-lg font-semibold md:text-xxl">
                                <Vue3autocounter ref="counterEquity" :startAmount="0" :endAmount="Number(equity)" :duration="accountBalanceDuration" separator="," decimalSeparator="." :decimals="2" :autoinit="true" />
                            </span>
                            <span class="self-stretch text-gray-500 text-center text-sm">{{ $t('public.total_equity') }}</span>
                        </div>
                    </div>
                </div>
                <div class="w-full flex flex-col items-center gap-5">
                    <div class="w-full flex flex-col items-start px-3 py-5 gap-4 self-stretch rounded-lg bg-white shadow-card md:p-6">
                        <div class="flex items-center self-stretch">
                            <span class="flex-1 text-gray-950 font-bold">{{ $t('public.dashboard_withdrawal_request') }}</span>
                            <Button
                                variant="gray-text"
                                size="sm"
                                type="button"
                                iconOnly
                                v-slot="{ iconSizeClasses }"
                                :href="route('pending.withdrawal')"
                            >
                                <IconChevronRight size="16" stroke-width="1.25" color="#374151" />
                            </Button>
                        </div>
                        <span class="self-stretch text-gray-950 text-xl font-semibold md:text-xxl">
                            $&nbsp;<Vue3autocounter ref="counter" :startAmount="0" :endAmount="Number(pendingWithdrawal)" :duration="counterDuration" separator="," decimalSeparator="." :decimals="2" :autoinit="true" />
                        </span>
                        <div class="flex items-center gap-2">
                            <Badge variant="numberBadge" class="text-white text-xs">{{ pendingWithdrawalCount }}</Badge>
                            <span class="text-gray-500 text-sm">{{ $t('public.account_withdrawal_request') }}</span>
                        </div>
                    </div>

                    <div class="w-full flex flex-col items-start px-3 py-5 gap-4 self-stretch rounded-lg bg-white shadow-card md:p-6">
                        <div class="flex items-center self-stretch">
                            <span class="flex-1 text-gray-950 font-bold">{{ $t('public.dashboard_incentive_request') }}</span>
                            <Button
                                variant="gray-text"
                                size="sm"
                                type="button"
                                iconOnly
                                v-slot="{ iconSizeClasses }"
                                :href="route('pending.incentive')"
                            >
                                <IconChevronRight size="16" stroke-width="1.25" color="#374151" />
                            </Button>
                        </div>
                        <span class="self-stretch text-gray-950 text-xl font-semibold md:text-xxl">
                            $&nbsp;<Vue3autocounter ref="counter" :startAmount="0" :endAmount="Number(pendingIncentive)" :duration="counterDuration" separator="," decimalSeparator="." :decimals="2" :autoinit="true" />
                        </span>
                        <div class="flex items-center gap-2">
                            <Badge variant="numberBadge" class="text-white text-xs">{{ pendingIncentiveCount }}</Badge>
                            <span class="text-gray-500 text-sm">{{ $t('public.account_incentive_request') }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
