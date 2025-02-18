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
import Vue3Autocounter from 'vue3-autocounter';
import Empty from '@/Components/Empty.vue';
import { usePage } from '@inertiajs/vue3';
import { wTrans, trans } from "laravel-vue-i18n";
import CreateTeam from '@/Pages/Team/Partials/CreateTeam.vue';
import SettlementReport from '@/Pages/Team/Partials/SettlementReport.vue'
import TeamAction from '@/Pages/Team/Partials/TeamAction.vue';
import Select from "primevue/select";
import dayjs from "dayjs";

defineProps({
    teamCount: Number
})

const { formatAmount, formatDate } = transactionFormat();

const counterDuration = ref(10);
const totalDeposit = ref(0);
const totalWithdrawal = ref(0);
const totalFeeCharges = ref(0);
const totalNetBalance = ref(0);
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

const months = ref([]);
const selectedMonth = ref('');

const getCurrentMonthYear = () => {
    const date = new Date();
    return `01 ${dayjs(date).format('MMMM YYYY')}`;
};

// Fetch settlement months from API
const getTransactionMonths = async () => {
    try {
        const response = await axios.get('/getTransactionMonths');
        months.value = ['select_all', ...response.data.months];

        if (months.value.length) {
            selectedMonth.value = [getCurrentMonthYear()];
        }
    } catch (error) {
        console.error('Error transaction months:', error);
    }
};

getTransactionMonths()

// Watchers for selectedMonths
watch(selectedMonth, (newMonths) => {
    getResults(newMonths);
});

const getResults = async (selectedMonth = '') => {
    isLoading.value = true
    try {
        let response;

        let url = `/team/getTeams`;

        if (selectedMonth) {
            const formattedMonth = selectedMonth === 'select_all' 
                ? 'select_all' 
                : dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');

            url += `?selectedMonth=${formattedMonth}`;
        }

        response = await axios.get(url);
        teams.value = response.data.teams;

        total.value = response.data.total;
        totalDeposit.value = total.value.total_deposit;
        totalWithdrawal.value = total.value.total_withdrawal;
        totalFeeCharges.value = total.value.total_charges;
        totalNetBalance.value = total.value.total_net_balance;
    } catch (error) {
        console.error('Error fetching team:', error);
    } finally {
        isLoading.value = false;
        counterDuration.value = 1;
    }
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults(selectedMonth.value);
    }
});

const refreshingTeam = reactive({});

const refreshTeam = async (teamId) => {
    try {
        refreshingTeam[teamId] = true;
        let response;

        let url = `/team/refreshTeam`;

        if (selectedMonth.value) {
            const formattedMonth = selectedMonth.value === 'select_all' 
                ? 'select_all' 
                : dayjs(selectedMonth.value, 'DD MMMM YYYY').format('MMMM YYYY');

            url += `?selectedMonth=${formattedMonth}`;
        }

        // Add teamId to the URL if it exists
        if (teamId) {
            url += `${url.includes('?') ? '&' : '?'}team_id=${teamId}`;
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
    } catch (error) {
        console.error('Error fetching team:', error);
    } finally {
        refreshingTeam[teamId] = false; // Indicate that the refresh is complete
        counterDuration.value = 1;
    }
};
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
                    <span v-if="(item.total || item.total === 0) && !isLoading" class="self-stretch text-gray-950 text-lg font-semibold">
                        $&nbsp;{{ formatAmount(item.total) }}
                    </span>
                    <span v-else class="self-stretch truncate text-gray-950 text-lg font-semibold animate-pulse">
                        <div class="h-2.5 bg-gray-200 rounded-full w-1/3"></div>
                    </span>
                </div>
            </div>

            <div class="w-full flex flex-col justify-center items-center px-3 py-5 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <!-- toolbar -->
                 <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:justify-between">
                    <Select 
                        v-model="selectedMonth" 
                        :options="months" 
                        :placeholder="$t('public.month_placeholder')"
                        class="w-full md:w-60 font-normal truncate" scroll-height="236px" 
                    >
                        <template #option="{option}">
                            <span class="text-sm">
                                <template v-if="option === 'select_all'">
                                    {{ $t('public.select_all') }}
                                </template>
                                <template v-else>
                                    {{ $t(`public.${option.split(' ')[1]}`) }} {{ option.split(' ')[2] }}
                                </template>
                            </span>
                        </template>
                        <template #value>
                            <span v-if="selectedMonth">
                                <template v-if="selectedMonth === 'select_all'">
                                    {{ $t('public.select_all') }}
                                </template>
                                <template v-else>
                                    {{ $t(`public.${dayjs(selectedMonth).format('MMMM')}`) }} {{ dayjs(selectedMonth).format('YYYY') }}
                                </template>
                            </span>
                            <span v-else>
                                {{ $t('public.month_placeholder') }}
                            </span>
                        </template>
                    </Select>

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
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ `${$t('public.team_fee_charges')}&nbsp;(%)` }}</span>
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
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team?.deposit || 0) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_withdrawal') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team?.withdrawal || 0) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ `${$t('public.team_fee_charges')}&nbsp;(${formatAmount(team?.fee_charges || 0)}%)` }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team?.transaction_fee_charges || 0) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_net_balance') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team?.net_balance || 0) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_account_balance') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team?.account_balance || 0) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch truncate text-gray-500 text-xs">{{ $t('public.team_account_equity') }}</span>
                                        <span class="self-stretch truncate text-gray-950 font-semibold">$&nbsp;{{ formatAmount(team?.account_equity || 0) }}</span>
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
