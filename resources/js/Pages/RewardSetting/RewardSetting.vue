<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from "@/Components/Button.vue";
import Select from "primevue/select";
import { IconPlus } from "@tabler/icons-vue";
import {
    PointIcon
} from '@/Components/Icons/outline.jsx';
import { ref, watch, watchEffect } from "vue";
import Action from "@/Pages/RewardSetting/Partials/Action.vue"
import CreateRewards from "@/Pages/RewardSetting/Partials/CreateRewards.vue";

const selectedReward = ref('least_trade_point');

const rewards = ref([
    { name: 'Least Trade Point', value: 'least_trade_point' },
    { name: 'Most Redeemed', value: 'most_redeemed' },
    { name: 'Cash Rewards Only', value: 'cash_rewards_only' },
    { name: 'Physical Rewards Only', value: 'physical_rewards_only' },
]);

</script>

<template>
    <AuthenticatedLayout :title="$t('public.reward_setting')">
        <div class="flex flex-col justify-center items-center py-5 px-3 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="w-full flex flex-row items-center gap-3 justify-between md:gap-5">
                <CreateRewards />
                <!-- <Button type="button" variant="primary-flat" class="md:flex justify-center hidden w-auto">
                    <IconPlus color="white" size="20" stroke-width="1.25" />
                    <span>{{ $t('public.create_rewards') }}</span>
                </Button> -->
                <Select 
                    v-model="selectedReward" 
                    :options="rewards" 
                    optionLabel="name" 
                    optionValue="value"
                    :placeholder="$t('public.reward_placeholder')"
                    class="w-full md:w-60 font-normal truncate" scroll-height="236px" 
                />
            </div>
            <div class="grid gap-3 md:gap-5 w-full grid-cols-1 md:grid-cols-2 2xl:grid-cols-3">
                <div
                    class="flex flex-col gap-2 justify-center px-3 md:px-4 py-3 rounded w-full shadow-card bg-white border border-gray-100"
                >
                    <img src="/img/checker.png" alt="reward_image" class="h-[186px] md:h-[225px] xl:h-[321px]"/>
                    <div class="flex flex-col gap-1 md:gap-0 w-full">
                        <div class="flex flex-row justify-between items-center">
                            <div class="flex flex-row gap-3">
                                <span class="text-sm text-gray-500">CR-000000</span>
                                <span class="flex flex-row gap-1 text-sm text-warning-500 font-medium items-center">
                                    <PointIcon class="w-4 h-4"/>
                                    <span>100 tp</span>
                                </span>
                            </div>
                            <Action 
                                
                            />
                        </div>
                        <span class="font-semibold">💰 Cash Rewards or 🎁 Item here</span>
                        <div class="flex flex-row justify-between pt-1 md:pt-3">
                            <span class="text-error-600 text-sm font-medium">2025/01/01</span>
                            <span class="text-gray-500 text-sm">- redeemed</span>
                        </div>
                    </div>
                </div>
                <div
                    class="flex flex-col gap-2 justify-center px-3 md:px-4 py-3 rounded w-full shadow-card bg-white border border-gray-100"
                >
                    <img src="/img/checker.png" alt="reward_image" class="h-[186px] md:h-[225px] xl:h-[321px]"/>
                    <div class="flex flex-col gap-1 md:gap-0 w-full">
                        <div class="flex flex-row justify-between items-center">
                            <div class="flex flex-row gap-3">
                                <span class="text-sm text-gray-500">CR-000001</span>
                                <span class="flex flex-row gap-1 text-sm text-warning-500 font-medium items-center">
                                    <PointIcon class="w-4 h-4"/>
                                    <span>150 tp</span>
                                </span>
                            </div>
                            <Action 
                                
                            />
                        </div>
                        <span class="font-semibold">💰 Cash Rewards or 🎁 Item here</span>
                        <div class="flex flex-row justify-between pt-1 md:pt-3">
                            <span class="text-error-600 text-sm font-medium">2025/01/01</span>
                            <span class="text-gray-500 text-sm">- redeemed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>