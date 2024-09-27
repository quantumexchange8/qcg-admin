<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import { transactionFormat } from "@/Composables/index.js";
import { IconCircleXFilled, IconSearch, IconPencilMinus } from "@tabler/icons-vue";
import { ref, watchEffect, computed } from "vue";
import Loader from "@/Components/Loader.vue";
import {
    AgentIcon,
    GroupAgentIcon,
    MinIcon,
    MaxIcon,
} from '@/Components/Icons/outline.jsx';
import { wTrans, trans } from "laravel-vue-i18n";
import Button from '@/Components/Button.vue';
import EditRebateDetails from "@/Pages/RebateSetting/Partials/EditRebateDetails.vue";
import RebateSettingTable from "@/Pages/RebateSetting/Partials/RebateSettingTable.vue";

const props = defineProps({
    accountTypes: Array,
})

const totalDirectAgent = ref(0.00);
const totalGroupAgent = ref(0.00);
const minimumLevel = ref(0.00);
const maximumLevel = ref(0.00);

// data overview
const dataOverviews = computed(() => [
    {
        icon: AgentIcon,
        total: totalDirectAgent.value,
        label: trans('public.direct_agent'),
    },
    {
        icon: GroupAgentIcon,
        total: totalGroupAgent.value,
        label: trans('public.group_agent'),
    },
    {
        icon: MinIcon,
        total: minimumLevel.value,
        label: trans('public.min_level'),
    },
    {
        icon: MaxIcon,
        total: maximumLevel.value,
        label: trans('public.max_level'),
    },
]);

const accountType = ref(1)
const companyProfile = ref();
const rebateDetails = ref();
const loading = ref(false);

const { formatAmount, formatDate } = transactionFormat();

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get(`/rebate/getCompanyProfileData?account_type_id=${accountType.value}`);
        companyProfile.value = response.data.companyProfile;
        rebateDetails.value = response.data.rebateDetails;

        // Directly updating values without a separate function
        minimumLevel.value = companyProfile.value.user.minimum_level || 0;
        maximumLevel.value = companyProfile.value.user.maximum_level || 0;
        totalDirectAgent.value = companyProfile.value.user.direct_agent || 0;
        totalGroupAgent.value = companyProfile.value.user.group_agent || 0;
    } catch (error) {
        console.error('Error fetch company profile:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const handleAccountTypeChange = (newType) => {
    accountType.value = newType
    getResults();
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

</script>

<template>
    <AuthenticatedLayout :title="$t('public.rebate_setting')">
        <div class="w-full flex flex-col items-center gap-5">
            <div class="w-full grid grid-cols-1 gap-5 md:grid-cols-2">

                <!-- data overview -->
                <div class="w-full grid grid-cols-2 gap-3 md:gap-5 self-stretch overflow-x-auto">
                    <div 
                        v-for="(item, index) in dataOverviews"
                        :key="index"
                        class="min-w-[140px] md:min-w-[150px] xl:min-w-[220px] md:h-[148px] flex flex-col justify-center items-center py-5 px-3 md:p-0 gap-3 rounded-lg bg-white shadow-card"
                    >
                        <component :is="item.icon" class="w-8 h-8 grow-0 shrink-0 text-primary-600" />
                        <span class="text-gray-500 text-sm">{{ item.label }}</span>
                        <span class="self-stretch text-gray-950 text-center text-lg font-semibold">{{ item.total }}</span>
                    </div>
                </div>
                
                <!-- Rebate detail -->
                <div class="flex flex-col items-center p-3 gap-3 self-stretch rounded-lg bg-white shadow-card md:py-5 md:px-6">
                    <div class="flex justify-between items-center self-stretch">
                        <span class="text-gray-950 text-sm font-bold">{{ $t('public.rebate_details') }}</span>

                        <EditRebateDetails
                            :rebateDetails="rebateDetails"
                        />
                    </div>
                    <div class="flex flex-col items-center self-stretch">
                        <div class="flex items-center w-full self-stretch py-3 text-gray-950 bg-gray-50 border-b border-gray-100">
                            <span class="uppercase text-xs font-semibold px-3 w-full">{{ $t('public.product') }}</span>
                            <span class="uppercase text-xs font-semibold px-3 w-full">{{ $t('public.rebate') }} / Ł ($)</span>
                        </div>

                        <!-- symbol groups -->
                        <div
                            v-if="rebateDetails"
                            v-for="rebateDetail in rebateDetails"
                            class="flex items-center w-full self-stretch py-2 text-gray-950"
                        >
                            <div class="text-sm px-3 w-full">{{ $t(`public.${rebateDetail.symbol_group.display}`) }}</div>
                            <div class="text-sm px-3 w-full">{{ formatAmount(rebateDetail.amount, 0) }}</div>
                        </div>
                        <div
                            v-else
                            v-for="index in 5"
                            class="flex items-center w-full self-stretch py-2 text-gray-950"
                        >
                            <div class="w-full">
                                <div class="h-2.5 bg-gray-200 rounded-full w-36 mt-1 mb-1.5"></div>
                            </div>
                            <div class="w-full">
                                <div class="h-2.5 bg-gray-200 rounded-full w-10 mt-1 mb-1.5"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- data table -->
            <RebateSettingTable :accountTypes="accountTypes" @update:accountType="handleAccountTypeChange" />
        </div>
    </AuthenticatedLayout>
</template>