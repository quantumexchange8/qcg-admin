<script setup>
import Button from '@/Components/Button.vue';
import { 
    IconRefresh, 
    IconUserFilled,
} from '@tabler/icons-vue';
import Select from "primevue/select";
import dayjs from "dayjs";
import { ref, watch } from "vue";
import { transactionFormat } from "@/Composables/index.js";
import axios from 'axios';
import Skeleton from 'primevue/skeleton';

const props = defineProps({
    teamMonths: Array,
});

const teams = ref([]);
const loadingTeams = ref(false);
const { formatAmount } = transactionFormat();
const teamDuration = ref(10);
const selectedTeamMonth = ref('');
const teamMonths = ref(props.teamMonths);

const getCurrentMonthYear = () => {
    const date = new Date();
    return `01 ${dayjs(date).format('MMMM YYYY')}`;
};

watch([teamMonths], ([newTeamMonths]) => {
    if (newTeamMonths.length > 0) {
        // console.log(newTeamMonths)
        selectedTeamMonth.value = getCurrentMonthYear();
    }
}, { immediate: true });

const getTeams = async () => {
    loadingTeams.value = true;
    
    try {
        const response = await axios.get(`dashboard/getTeams`);
                
        // Process response data here
        teams.value = response.data.teams;
        teamDuration.value = 1;
        loadingTeams.value = false;

        for (const team of teams.value) {
            try {
                // Format month parameter
                let formattedMonth = selectedTeamMonth.value;
                if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                    formattedMonth = dayjs(selectedTeamMonth.value, 'DD MMMM YYYY').format('MMMM YYYY');
                }

                // Fetch team data
                const teamResponse = await axios.get(`dashboard/getTeamData`, {
                    params: {
                        selectedMonth: formattedMonth,
                        teamId: team.id
                    }
                });
                // Update team data
                const teamIndex = teams.value.findIndex(t => t.id === team.id);
                if (teamIndex !== -1 && teamResponse.data?.team) {
                    teams.value[teamIndex] = { 
                        ...teams.value[teamIndex], 
                        ...teamResponse.data.team,
                    };
                }
            } catch (error) {
                console.error('Error processing team', error);
            }
        }
    } catch (error) {
        console.error('Error fetching data:', error);
        loadingTeams.value = false;
    } finally {
        teamDuration.value = 1
        loadingTeams.value = false;
    }
}

getTeams();

// Watch for changes in selectedMonth and trigger getTradeLotVolume
watch(selectedTeamMonth,(newTeamMonth, oldTeamMonth) => {
        if (newTeamMonth !== oldTeamMonth) {
            getTeams();
        }
    }
);
</script>

<template>
    <div class="w-full h-full flex flex-col items-center p-3 gap-3 rounded-lg bg-white shadow-card md:p-6 md:gap-5 overflow-y-auto">
        <div class="w-full flex justify-between items-center">
            <Select 
                v-model="selectedTeamMonth" 
                :options="teamMonths" 
                :placeholder="$t('public.month_placeholder')"
                class="w-60 font-normal truncate" scroll-height="236px" 
            >
                <template #option="{ option }">
                    <span class="text-sm">
                        <template v-if="option === 'select_all'">
                            {{ $t('public.select_all') }}
                        </template>
                        <template v-else-if="option.startsWith('last_')">
                            {{ $t(`public.${option}`) }}
                        </template>
                        <template v-else>
                            {{ $t(`public.${option.split(' ')[1]}`) }} {{ option.split(' ')[2] }}
                        </template>
                    </span>
                </template>
                <template #value>
                    <span v-if="selectedTeamMonth">
                        <template v-if="selectedTeamMonth === 'select_all'">
                            {{ $t('public.select_all') }}
                        </template>
                        <template v-else-if="selectedTeamMonth.startsWith('last_')">
                            {{ $t(`public.${selectedTeamMonth}`) }}
                        </template>
                        <template v-else>
                            {{ $t(`public.${dayjs(selectedTeamMonth).format('MMMM')}`) }} {{ dayjs(selectedTeamMonth).format('YYYY') }}
                        </template>
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

        <div class="w-full max-h-[770px] grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-1 gap-3 md:gap-6">
            <div v-for="team in teams" :key="team.id">
                <div v-if="loadingTeams"
                    class="w-full flex flex-col items-center rounded bg-white shadow-card"
                >
                    <div class="flex bg-gray-500 w-full gap-3 items-center md:px-4 md:py-2 px-3 py-1">
                        <span class="text-white w-full animate-pulse font-medium truncate">
                            <div class="bg-gray-200 h-3 rounded-full w-20"></div>
                        </span>
                        <div class="flex text-white gap-2 items-center">
                            <IconUserFilled size="20" stroke-width="1.25" />
                            <span class="text-right animate-pulse font-medium">
                                <div class="bg-gray-200 h-3 rounded-full w-5"></div>
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col p-3 w-full gap-2 items-center md:gap-3 md:p-4">
                        <div class="grid grid-cols-3 w-full gap-2">
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.team_deposit') }}</span>
                                <span class="text-gray-950 text-sm w-full animate-pulse font-semibold md:text-base truncate">
                                    <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.team_withdrawal') }}</span>
                                <span class="text-gray-950 text-sm w-full animate-pulse font-semibold md:text-base truncate">
                                    <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.team_net_balance') }}</span>
                                <span class="text-gray-950 text-sm w-full animate-pulse font-semibold md:text-base truncate">
                                    <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.dashboard_team_account_equity') }}</span>
                                <span class="text-gray-950 text-sm w-full animate-pulse font-semibold md:text-base truncate">
                                    <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.dashboard_team_adjustment_in') }}</span>
                                <span class="text-gray-950 text-sm w-full animate-pulse font-semibold md:text-base truncate">
                                    <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.dashboard_team_adjustment_out') }}</span>
                                <span class="text-gray-950 text-sm w-full animate-pulse font-semibold md:text-base truncate">
                                    <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else
                    class="w-full flex flex-col items-center rounded bg-white shadow-card"
                >
                    <div 
                        class="flex w-full gap-3 items-center md:px-4 md:py-2 px-3 py-1"
                        :style="{'backgroundColor': `#${team.color}`}"
                    >
                        <span class="text-white w-full font-medium truncate">{{ team.name }}</span>
                        <div class="flex text-white gap-2 items-center">
                            <IconUserFilled size="20" stroke-width="1.25" />
                            <span class="text-right font-medium">{{ formatAmount(team.member_count, 0) }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col p-3 w-full gap-2 items-center md:gap-3 md:p-4">
                        <div class="grid grid-cols-3 w-full gap-2">
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.team_deposit') }}</span>
                                <span class="text-gray-950 text-sm w-full font-semibold md:text-base truncate">$
                                    <template v-if="team?.deposit">
                                        {{ formatAmount(team.deposit) }}
                                    </template>
                                    <template v-else-if="team?.deposit === 0">
                                        {{ formatAmount(0) }}
                                    </template>
                                    <template v-else>
                                        <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                    </template>
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.team_withdrawal') }}</span>
                                <span class="text-gray-950 text-sm w-full font-semibold md:text-base truncate">$
                                    <template v-if="team?.withdrawal">
                                        {{ formatAmount(team.withdrawal) }}
                                    </template>
                                    <template v-else-if="team?.withdrawal === 0">
                                        {{ formatAmount(0) }}
                                    </template>
                                    <template v-else>
                                        <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                    </template>
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.team_net_balance') }}</span>
                                <span class="text-gray-950 text-sm w-full font-semibold md:text-base truncate">$
                                    <template v-if="team?.net_balance">
                                        {{ formatAmount(team.net_balance) }}
                                    </template>
                                    <template v-else-if="team?.net_balance === 0">
                                        {{ formatAmount(0) }}
                                    </template>
                                    <template v-else>
                                        <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                    </template>
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.dashboard_team_account_equity') }}</span>
                                <span class="text-gray-950 text-sm w-full font-semibold md:text-base truncate">$
                                    <template v-if="team?.account_equity">
                                        {{ formatAmount(team.account_equity) }}
                                    </template>
                                    <template v-else-if="team?.account_equity === 0">
                                        {{ formatAmount(0) }}
                                    </template>
                                    <template v-else>
                                        <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                    </template>
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.dashboard_team_adjustment_in') }}</span>
                                <span class="text-gray-950 text-sm w-full font-semibold md:text-base truncate">$
                                    <template v-if="team?.adjustment_in">
                                        {{ formatAmount(team.adjustment_in) }}
                                    </template>
                                    <template v-else-if="team?.adjustment_in === 0">
                                        {{ formatAmount(0) }}
                                    </template>
                                    <template v-else>
                                        <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                    </template>
                                </span>
                            </div>
                            <div class="flex flex-col w-full gap-1 items-start">
                                <span class="text-gray-500 text-xxs w-full md:text-xs truncate">{{ $t('public.dashboard_team_adjustment_out') }}</span>
                                <span class="text-gray-950 text-sm w-full font-semibold md:text-base truncate">$
                                    <template v-if="team?.adjustment_out">
                                        {{ formatAmount(team.adjustment_out) }}
                                    </template>
                                    <template v-else-if="team?.adjustment_out === 0">
                                        {{ formatAmount(0) }}
                                    </template>
                                    <template v-else>
                                        <Skeleton width="5rem" height="0.75rem" class=" inline-block align-middle" />
                                    </template>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>