;<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { onMounted, ref, watchEffect, watch, reactive, computed } from 'vue';
import { transactionFormat } from '@/Composables/index.js';
import { IconX, IconUserFilled, IconRefresh, IconCircleXFilled } from '@tabler/icons-vue';
import {
    DepositIcon,
    WithdrawalIcon,
    FeeIcon,
    NetBalanceIcon,
} from '@/Components/Icons/outline.jsx';
import Button from '@/Components/Button.vue';
import Calendar from 'primevue/calendar';
import Vue3Autocounter from 'vue3-autocounter';
import Empty from '@/Components/Empty.vue';
import { usePage } from '@inertiajs/vue3';
import { wTrans, trans } from "laravel-vue-i18n";
import DatePicker from 'primevue/datepicker';
import CreateTeam from '@/Pages/Team/Partials/CreateTeam.vue';
import SettlementReport from '@/Pages/Team/Partials/SettlementReport.vue'
import TeamAction from '@/Pages/Team/Partials/TeamAction.vue';

defineProps({
    teamCount: Number
})

const { formatAmount, formatDate } = transactionFormat();

const counterDuration = ref(10);
const totalDeposit = ref(0.00);
const totalWithdrawal = ref(0.00);
const totalFeeCharges = ref(0.00);
const totalNetBalance = ref(0.00);
const teams = ref([]);
const total = ref();
const isLoading = ref(false);

// data overview
const dataOverviews = computed(() => [
    {
        icon: DepositIcon,
        total: totalDeposit.value,
        label: trans('public.total_deposit'),
    },
    {
        icon: WithdrawalIcon,
        total: totalWithdrawal.value,
        label: trans('public.total_withdrawal'),
    },
    {
        icon: FeeIcon,
        total: totalFeeCharges.value,
        label: trans('public.total_fee_charge'),
    },
    {
        icon: NetBalanceIcon,
        total: totalNetBalance.value,
        label: trans('public.total_net_balance'),
    },
]);

// Get current date
const today = new Date();

// Define minDate and maxDate
const minDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const maxDate = ref(today);

// Reactive variable for selected date range
const selectedDate = ref([minDate.value, maxDate.value]);

// Clear date selection
const clearDate = () => {
    selectedDate.value = [];
};

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;

        if (startDate && endDate) {
            getResults([startDate, endDate]);
        } else if (startDate || endDate) {
            getResults([startDate || endDate, endDate || startDate]);
        } else {
            getResults([]);
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
});

const getResults = async (selectedDate = []) => {
    isLoading.value = true
    try {
        let response;
        const [startDate, endDate] = selectedDate;

        let url = `/team/getTeams`;

        if (startDate && endDate) {
            url += `?startDate=${formatDate(startDate)}&endDate=${formatDate(endDate)}`;
        }

        response = await axios.get(url);
        teams.value = response.data.teams;

        total.value = response.data.total;
        totalDeposit.value = total.value.total_deposit;
        totalWithdrawal.value = total.value.total_withdrawal;
        totalFeeCharges.value = total.value.total_charges;
        totalNetBalance.value = total.value.total_net_balance;
        counterDuration.value = 1;
    } catch (error) {
        console.error('Error fetching team:', error);
    } finally {
        isLoading.value = false
    }
};

onMounted(() => {
    getResults(selectedDate.value);
})

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults(selectedDate.value);
    }
});

const refreshingTeam = reactive({});

const refreshTeam = async (teamId) => {
    try {
        refreshingTeam[teamId] = true;
        let response;

        // Destructure selectedDate safely
        const [startDate, endDate] = selectedDate.value.length ? selectedDate.value : [null, null];

        let url = `/team/refreshTeam`;

        // If dates are available, construct the URL with dates
        if (startDate && endDate) {
            url += `?startDate=${formatDate(startDate)}&endDate=${formatDate(endDate)}`;
        } else if (startDate || endDate) {
            // If one date is available, use it for both start and end
            const dateToUse = startDate || endDate;
            url += `?startDate=${formatDate(dateToUse)}&endDate=${formatDate(dateToUse)}`;
        }

        // Add teamId to the URL if it exists
        if (teamId) {
            url += `${selectedDate.value.length > 0 ? '&' : '?'}team_id=${teamId}`;
        }

        // Make the API call
        response = await axios.get(url);
        const refreshedTeam = response.data.refreshed_team;

        // Find the index of the team to be replaced
        const teamIndex = teams.value.findIndex(team => team.id === refreshedTeam.id);

        if (teamIndex !== -1) {
            // Replace the team data with the refreshed team data
            teams.value[teamIndex] = refreshedTeam;
        }

        // Update totals from the response
        total.value = response.data.total;
        totalNetBalance.value = total.value.total_net_balance;
        totalDeposit.value = total.value.total_deposit;
        totalWithdrawal.value = total.value.total_withdrawal;
        totalFeeCharges.value = total.value.total_charges;
        counterDuration.value = 1;
    } catch (error) {
        console.error('Error fetching team:', error);
    } finally {
        refreshingTeam[teamId] = false; // Indicate that the refresh is complete
    }
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sales_team')">
        <div class="w-full flex flex-col items-center gap-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4 self-stretch md:gap-5">
                <div
                    v-for="(item, index) in dataOverviews"
                    :key="index"
                    class="flex flex-col items-center p-6 gap-3 self-stretch rounded-lg bg-white shadow-card"
                >
                    <div class="flex justify-between items-center self-stretch">
                        <span class="text-gray-500 text-sm">{{ item.label }}</span>
                        <component :is="item.icon" class="w-5 h-5 grow-0 shrink-0" 
                            :class="{
                                'text-success-600' : item.icon == DepositIcon,
                                'text-error-600' : item.icon == WithdrawalIcon,
                                'text-gray-700' : item.icon == FeeIcon,
                                'text-info-500' : item.icon == NetBalanceIcon,
                            }"
                        />
                    </div>
                    <span class="self-stretch text-gray-950 text-lg font-semibold">
                        $&nbsp;
                        <vue3-autocounter ref="counter" :startAmount="0" :endAmount="Number(item.total)" :duration="counterDuration" separator="," decimalSeparator="." :decimals="2" :autoinit="true" />
                    </span>
                </div>
            </div>

            <div class="w-full flex flex-col justify-center items-center px-3 py-5 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <!-- toolbar -->
                 <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:justify-between">
                    <div class="relative w-full md:w-60">
                        <DatePicker 
                            v-model="selectedDate"
                            selectionMode="range"
                            :manualInput="false"
                            :maxDate="maxDate"
                            dateFormat="dd/mm/yy"
                            showIcon
                            iconDisplay="input"
                            :placeholder="$t('public.select_date')"
                            class="font-normal w-full md:w-60"
                        />
                        <div
                            v-if="selectedDate && selectedDate.length > 0"
                            class="absolute top-[11px] right-3 flex justify-center items-center text-gray-400 select-none cursor-pointer bg-white w-6 h-6 "
                            @click="clearDate"
                        >
                            <IconCircleXFilled size="20" />
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                        <SettlementReport />
                        <CreateTeam />
                    </div>
                 </div>

                <!-- Team data -->
                <template v-if="(teamCount === 0 || !teams.length) && !isLoading">
                    <Empty :title="$t('public.no_sales_team_title')" :message="$t('public.no_sales_team_message')" />
                </template>

                <template v-else>
                    <div class="w-full grid grid-cols-1 place-items-center gap-6 md:grid-cols-2">
                        <div
                            v-if="isLoading"
                            class="w-full flex flex-col items-center self-stretch rounded bg-white shadow-card"
                        >
                            <div 
                                class="flex py-2 px-4 items-center gap-3 self-stretch bg-gray-500"
                            >
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
                            <div class="flex flex-col items-center px-4 pb-4 gap-3 self-stretch">
                                <div class="flex items-center py-3 gap-3 self-stretch border-b border-gray-200 truncate">
                                    <div class="w-full flex flex-col gap-1 justify-center items-start truncate animate-pulse">
                                        <span class="self-stretch truncate text-gray-950 font-semibold">
                                            <div class="h-3 bg-gray-200 rounded-full w-20"></div>
                                        </span>
                                        <span class="self-stretch truncate text-gray-500 text-sm">
                                            <div class="h-3 bg-gray-200 rounded-full w-20"></div>
                                        </span>
                                    </div>
                                    <Button
                                        variant="gray-outlined"
                                        size="sm"
                                        type="button"
                                        iconOnly
                                        pill
                                        disabled
                                    >
                                        <IconRefresh size="16" stroke-width="1.25" />
                                    </Button>
                                    <TeamAction :isLoading="isLoading" />
                                </div>
                                <div class="w-full grid grid-cols-2 gap-2 self-stretch xl:grid-cols-3">
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_deposit') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">
                                            <div class="h-3 bg-gray-200 rounded-full w-30"></div>
                                        </span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_withdrawal') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">
                                            <div class="h-3 bg-gray-200 rounded-full w-30"></div>
                                        </span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_fee_charges') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">
                                            <div class="h-3 bg-gray-200 rounded-full w-30"></div>
                                        </span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_net_balance') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">
                                            <div class="h-3 bg-gray-200 rounded-full w-30"></div>
                                        </span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_account_balance') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">
                                            <div class="h-3 bg-gray-200 rounded-full w-30"></div>
                                        </span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_account_equity') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">
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
                            class="w-full flex flex-col items-center self-stretch rounded bg-white shadow-card"
                        >
                            <div 
                                class="flex py-2 px-4 items-center gap-3 self-stretch"
                                :style="{'backgroundColor': `#${team.color}`}"
                            >
                                <span class="w-full text-white font-medium truncate">{{ team.name }}</span>
                                <div class="flex items-center gap-2 text-white">
                                    <IconUserFilled size="20" stroke-width="1.25" />
                                    <span class="text-right font-medium">{{ formatAmount(team.member_count, 0) }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-center px-4 pb-4 gap-3 self-stretch">
                                <div class="flex items-center py-3 gap-3 self-stretch border-b border-gray-200 truncate">
                                    <div class="w-full flex flex-col justify-center items-start truncate">
                                        <span class="self-stretch truncate text-gray-950 font-semibold">{{ team.leader_name }}</span>
                                        <span class="self-stretch truncate text-gray-500 text-sm">{{ team.leader_email }}</span>
                                    </div>
                                    <Button
                                        variant="gray-outlined"
                                        size="sm"
                                        type="button"
                                        iconOnly
                                        pill
                                        @click="refreshTeam(team.id)"
                                    >
                                        <div :class="{ 'animate-spin': refreshingTeam[team.id] }">
                                            <IconRefresh size="16" stroke-width="1.25" />
                                        </div>
                                    </Button>
                                    <TeamAction :team="team" />
                                </div>
                                <div class="w-full grid grid-cols-2 gap-2 self-stretch xl:grid-cols-3">
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_deposit') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team.deposit) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_withdrawal') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team.withdrawal) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_fee_charges') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team.transaction_fee_charges) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_net_balance') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team.net_balance) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_account_balance') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team.account_balance) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_account_equity') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team.account_equity) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
