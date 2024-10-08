<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import { transactionFormat } from "@/Composables/index.js";
import { IconRefresh, IconCircleXFilled, IconSearch, IconPencilMinus } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted } from "vue";
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import { wTrans, trans } from "laravel-vue-i18n";
import Button from '@/Components/Button.vue';
import debounce from "lodash/debounce.js";
import dayjs from "dayjs";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import AccountTypeActions from "@/Pages/AccountType/Partials/AccountTypeActions.vue"

const props = defineProps({
    accountTypeCount: Number,
    leverages: Array,
})

const accountTypes = ref();
const loading = ref(false);

const getAccountTypes = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/accountType/getAccountTypes');
        accountTypes.value = response.data.accountTypes;
    } catch (error) {
        console.error('Error getting account types:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    getAccountTypes();
})

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getAccountTypes();
    }
});

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_account_type')">
        <div class="w-full flex flex-col items-center gap-5">
            <div class="flex flex-col justify-center items-center px-3 py-5 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:justify-between">
                    <span class="self-stretch md:self-auto text-gray-950 font-semibold">{{ $t('public.all_account_type') }}</span>
                    <Button
                        type="button"
                        size="base"
                        variant="primary-flat"
                        class="w-full md:w-auto"
                        :href="route('accountType.syncAccountTypes')"
                    >
                        <IconRefresh size="20" stroke-width="1.25" />
                        {{ $t('public.synchronise') }}
                    </Button>
                </div>
                <DataTable
                    :value="accountTypes"
                    removableSort
                    ref="dt"
                    :loading="loading"
                    table-style="min-width:fit-content"
                >
                    <template #empty><Empty :title="$t('public.no_agent_found_title')" :message="$t('public.no_agent_found_message')" /></template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loader />
                            <span class="text-sm text-gray-700">{{ $t('public.loading_users_caption') }}</span>
                        </div>
                    </template>
                    <Column field="name" :header="$t('public.name')" sortable class="w-full md:w-[20%] max-w-0 px-3" headerClass="text-nowrap">
                        <template #body="slotProps">
                            <div class="font-semibold max-w-full">
                                {{ slotProps.data.name }}
                            </div>
                        </template>
                    </Column>
                    <Column field="maximum_account_number" :header="$t('public.max_account')" sortable class="hidden md:table-cell w-[20%] px-3" headerClass="text-nowrap">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data.maximum_account_number }}
                            </div>
                        </template>
                    </Column>
                    <Column field="trade_delay" :header="$t('public.trade_delay')" class="hidden md:table-cell w-[20%] px-3" headerClass="text-nowrap">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data.trade_delay }}
                            </div>
                        </template>
                    </Column>
                    <Column field="total_account" :header="$t('public.total_account')" sortable class="hidden md:table-cell w-[20%] px-3" headerClass="text-nowrap">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data.total_account }}
                            </div>
                        </template>
                    </Column>
                    <Column field="action" class="w-full md:w-[20%] px-3">
                        <template #body="slotProps">
                            <AccountTypeActions 
                                :accountType="slotProps.data"
                                :leverages="props.leverages"
                            />
                        </template>
                    </Column>

                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>