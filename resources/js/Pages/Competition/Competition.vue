<script setup>
import Button from "@/Components/Button.vue";
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watchEffect } from "vue";
import { usePage, router, Link } from '@inertiajs/vue3';
import NewCompetition from "@/Pages/Competition/Partials/NewCompetition.vue";
import Action from "@/Pages/Competition/Partials/Action.vue";
import CompetitionHistory from "@/Pages/Competition/Partials/CompetitionHistory.vue";
import { IconPlus } from "@tabler/icons-vue";
import {useLangObserver} from "@/Composables/localeObserver.js";
import { transactionFormat } from "@/Composables/index.js";
import {
    PointIcon
} from '@/Components/Icons/outline.jsx';

const { formatAmount, formatDate } = transactionFormat();

// const openDialog = () => {
//     router.get(route('competition.newCompetition'));
// }

const {locale} = useLangObserver();

const loading = ref(false);
const competitions = ref();

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/competition/getCurrentCompetitions');
        
        competitions.value = response.data.competitions;
    } catch (error) {
        console.error('Error getting competitions:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const categoryBgClass = computed(() => (category) => {
    switch (category) {
        case 'profit_rate':
            return 'bg-purple';
        case 'trade_lot':
            return 'bg-indigo';
        case 'trade_position':
            return 'bg-violet';
        default:
            return 'bg-black';
    }
});

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

</script>

<template>
    <AuthenticatedLayout :title="$t('public.competition')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <div class="flex flex-col justify-center items-center py-5 px-3 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <div class="flex justify-between items-center self-stretch">
                    <span class="font-semibold text-gray-950">{{ $t('public.upcoming_ongoing_competition') }}</span>
                    <Link :href="route('competition.new_competition')" as="button">
                        <Button
                            type="button"
                            variant="primary-flat"
                            size="base"
                            class='w-auto'
                            as="div" >
                            <IconPlus size="20" stroke-width="1.25" />
                            {{ $t('public.new_competition') }}
                        </Button>
                    </Link>
                </div>

                <div class="flex flex-col items-start content-start gap-3 self-stretch flex-wrap">
                    <div v-for="competition in competitions"
                        class="flex flex-col w-full p-3 gap-2 items-center bg-white rounded border-1 border-gray-100 shadow-card"
                    >
                        <div class="flex self-stretch justify-between items-center">
                            <div class="flex gap-3 items-center">
                                <div class="flex px-2 py-1 content-center items-center rounded-sm  text-white text-xs"
                                    :class="categoryBgClass(competition.category)"
                                    >{{ $t(`public.${competition.category}`) }}
                                </div>
                                <div class="flex items-center gap-1 text-warning-500 text-sm font-medium">
                                    <PointIcon class="w-4 h-4"/>
                                    <span class="text-xs font-medium">{{ competition.total_points }}tp {{ $t('public.to_be_paid') }}</span>
                                </div>
                            </div>
                            <Action
                                :competition_id="competition.competition_id" 
                                :status="competition.status"
                            />
                        </div>
                        <span class="self-stretch text-gray-950 font-semibold">{{ competition.name[locale] }}</span>
                        <div class="flex self-stretch justify-between items-center text-sm">
                            <span class="text-error-600 font-medium">{{ formatDate(competition.start_date) }} - {{ formatDate(competition.end_date) }}</span>
                            <span class="text-gray-500 font-medium">{{ 0 }} {{ $t('public.qualified') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <CompetitionHistory />
        </div>
    </AuthenticatedLayout>
</template>
