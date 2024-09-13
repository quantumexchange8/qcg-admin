<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import { ref, watch, watchEffect, onMounted, h } from "vue";
import { IconChevronRight } from '@tabler/icons-vue';
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import Button from '@/Components/Button.vue';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Empty from "@/Components/Empty.vue";
import { wTrans } from "laravel-vue-i18n";
import ProfileInfo from "@/Pages/Member/Listing/MemberDetail/Partials/ProfileInfo.vue";
import KycVerification from "@/Pages/Member/Listing/MemberDetail/Partials/KycVerification.vue";
import CryptoWalletInfo from "@/Pages/Member/Listing/MemberDetail/Partials/CryptoWalletInfo.vue";
import TradingAccounts from "@/Pages/Member/Listing/MemberDetail/Partials/TradingAccounts.vue";
import FinancialInfo from "@/Pages/Member/Listing/MemberDetail/Partials/FinancialInfo.vue";
import AdjustmentHistory from "@/Pages/Member/Listing/MemberDetail/Partials/AdjustmentHistory.vue";

const props = defineProps({
    user: Object
})

const userDetail = ref();
const paymentAccounts = ref();

const getUserData = async () => {
    try {
        const response = await axios.get(`/member/getUserData?id=` + props.user.id);

        userDetail.value = response.data.userDetail;
        paymentAccounts.value = response.data.paymentAccounts;
    } catch (error) {
        console.error('Error get network:', error);
    }
};

getUserData();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getUserData();
    }
});

const tabs = ref([
    {   
        title: wTrans('public.trading_accounts'),
        type: 'trading_accounts',
        component: h(TradingAccounts, {user_id: props.user.id}),
    },
    {   
        title: wTrans('public.financial_info'),
        type: 'financial_info',
        component: h(FinancialInfo, {user_id: props.user.id}),
    },
    {   
        title: wTrans('public.adjustment_history'),
        type: 'adjustment_history',
        component: h(AdjustmentHistory, {user_id: props.user.id}),
    },
]);

const selectedType = ref('trading_accounts');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

// Watch for changes in selectedType and update the activeIndex accordingly
watch(selectedType, (newType) => {
    const index = tabs.value.findIndex(tab => tab.type === newType);
    if (index >= 0) {
        activeIndex.value = index;
        getResults();
    }
});

function updateType(event) {
    const selectedTab = tabs.value[event.index];
    selectedType.value = selectedTab.type;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_listing')">
        <div class="w-full flex flex-col items-center gap-5">
            <!-- Breadcrumb -->
            <div class="flex flex-wrap md:flex-nowrap items-center gap-2 self-stretch">
                <Button
                    external
                    type="button"
                    variant="primary-text"
                    size="sm"
                    :href="route('member.listing')"
                >
                    {{ $t('public.member_listing') }}
                </Button>
                <IconChevronRight
                    :size="16"
                    stroke-width="1.25"
                />
                <div class="flex px-4 py-2 text-gray-700 items-center justify-center rounded text-sm font-medium text-center">{{ user.name }} - {{ $t('public.view_details') }}</div>
            </div>

            <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-5">
                <ProfileInfo
                    :userDetail="userDetail"
                />
                <div class="flex flex-col w-full gap-5 self-stretch">
                    <CryptoWalletInfo
                        :userDetail="userDetail"
                        :paymentAccounts="paymentAccounts"
                    />
                    <KycVerification
                        :userDetail="userDetail"
                    />
                </div>
            </div>

            <Tabs v-model:value="activeIndex" class="w-full gap-5"
            @tab-change="updateType"
            >
                <TabList>
                    <Tab 
                        v-for="(tab, index) in tabs" 
                        :key="tab.title"
                        :value="index"
                    >
                        {{ `${tab.title}` }}
                </Tab>
                </TabList>
                <TabPanels>
                    <TabPanel :key="activeIndex" :value="activeIndex">
                        <component :is="tabs[activeIndex].component" :key="tabs[activeIndex].type" :user_id="props.user.id" v-if="tabs[activeIndex].component"/>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>