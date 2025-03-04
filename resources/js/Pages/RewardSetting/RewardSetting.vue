<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import Select from "primevue/select";
import { IconPlus } from "@tabler/icons-vue";
import {
    PointIcon
} from '@/Components/Icons/outline.jsx';
import { ref, watch, watchEffect } from "vue";
import Action from "@/Pages/RewardSetting/Partials/Action.vue"
import CreateRewards from "@/Pages/RewardSetting/Partials/CreateRewards.vue";
import {useLangObserver} from "@/Composables/localeObserver.js";
import { transactionFormat } from "@/Composables/index.js";

const {locale} = useLangObserver();
const selectedReward = ref('least_trade_point');
const { formatAmount, formatDate } = transactionFormat();

const rewardFilters = ref([
    { name: 'Least Trade Point', value: 'least_trade_point' },
    { name: 'Most Redeemed', value: 'most_redeemed' },
    { name: 'Cash Rewards Only', value: 'cash_rewards_only' },
    { name: 'Physical Rewards Only', value: 'physical_rewards_only' },
]);

const rewards = ref([]);
const loading = ref(false);

const getRewardData = async (selectedReward = '') => {
    if (loading.value) return;
    loading.value = true;

    try {
        let url = `reward/getRewardData`;

        if (selectedReward) {
            url += `?filter=${selectedReward}`;
        }

        const response = await axios.get(url);
        rewards.value = response.data.rewards;
    } catch (error) {
        console.error('Error fetching rewards:', error);
    } finally {
        loading.value = false;
    }
};

getRewardData();

watch(selectedReward, (newReward) => {
    getRewardData(newReward);
});


watchEffect(() => {
    if (usePage().props.toast !== null) {
        getRewardData();
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.reward_setting')">
        <div class="flex flex-col justify-center items-center py-5 px-3 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="w-full flex flex-row items-center gap-3 justify-between md:gap-5">
                <CreateRewards />
                <Select 
                    v-model="selectedReward" 
                    :options="rewardFilters" 
                    optionLabel="name" 
                    optionValue="value"
                    :placeholder="$t('public.reward_placeholder')"
                    class="w-full md:w-60 font-normal truncate" scroll-height="236px" 
                />
            </div>
            <div class="grid gap-3 md:gap-5 w-full grid-cols-1 md:grid-cols-2 2xl:grid-cols-3">
                <div v-for="(item, index) in rewards" :key="index"
                    class="flex flex-col gap-2 justify-center px-3 md:px-4 py-3 rounded w-full shadow-card bg-white border border-gray-100"
                >
                    <img :src="item.reward_thumbnail" alt="reward_image" class="h-[186px] md:h-[225px] xl:h-[321px]"/>
                    <div class="flex flex-col gap-1 md:gap-0 w-full">
                        <div class="flex flex-row justify-between items-center">
                            <div class="flex flex-row gap-3">
                                <span class="text-sm text-gray-500">{{ item.code }}</span>
                                <span class="flex flex-row gap-1 text-sm text-warning-500 font-medium items-center">
                                    <PointIcon class="w-4 h-4"/>
                                    <span>{{ item.trade_point_required }} tp</span>
                                </span>
                            </div>
                            <Action 
                                :reward="item"
                            />
                        </div>
                        <span class="font-semibold">{{ item.type === 'cash_rewards' ? '💰 ' : '🎁 '  }}{{ item.name[locale] }}</span>
                        <div class="flex flex-row justify-between pt-1 md:pt-3">
                            <span class="text-error-600 text-sm font-medium">
                                {{
                                    item.expiry_date
                                    ? formatDate(item.expiry_date)
                                    : $t('public.no_expiry_date')
                                }}
                            </span>
                            <span class="text-gray-500 text-sm">- redeemed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>