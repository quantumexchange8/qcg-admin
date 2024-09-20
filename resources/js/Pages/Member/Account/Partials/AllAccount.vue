<script setup>
import { usePage } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconFilterOff } from "@tabler/icons-vue";
import { ref, watch, watchEffect } from "vue";
import Loader from "@/Components/Loader.vue";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import Button from '@/Components/Button.vue';
import Select from "primevue/select";
import { FilterMatchMode } from '@primevue/core/api';
import Empty from "@/Components/Empty.vue";
import Action from "@/Pages/Member/Account/Partials/Action.vue"
import { transactionFormat } from "@/Composables/index.js";
import dayjs from "dayjs";

const { formatAmount } = transactionFormat();

const props = defineProps({
    dt: Object,
    accountTypes: Array,
});

const loading = ref(false);
const dt = ref(null);

const accounts = ref();
// const accounts = ref(
//     [
//         { 
//             name: 'John Doe', 
//             email: 'john.doe@example.com', 
//             deleted_at: '2024-01-01T02:30:51Z', 
//             meta_login: '8000798', 
//             balance: 500.75, 
//             equity: 1000.50
//         },
//         { 
//             name: 'Jane Smith', 
//             email: 'jane.smith@example.com', 
//             deleted_at: '2024-02-01T05:10:34Z', 
//             meta_login: '8000486', 
//             balance: 750.00, 
//             equity: 1200.00
//         },
//         { 
//             name: 'Michael Brown', 
//             email: 'michael.brown@example.com', 
//             deleted_at: '2024-03-01T07:03:30Z', 
//             meta_login: '8000153', 
//             balance: 300.25, 
//             equity: 800.00
//         }
//     ]
// );
const filteredValueCount = ref(0);
const accountTypes = ref(props.accountTypes);
const accountType = ref(null)

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/member/getAccountListingData?type=all');
        accounts.value = response.data.accounts;

    } catch (error) {
        console.error('Error In Fetch Data:', error);
    } finally {
        loading.value = false;
    }

};

getResults();

watch(() => props.dt, (newVal) => {
    if (newVal) {
        newVal.value = dt.value; // Pass DataTable ref to parent
    }
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    account_type_id: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        email: { value: null, matchMode: FilterMatchMode.CONTAINS },
        account_type_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    accountType.value = null;
};

// Watch for changes in accountType and update the filter
watch(accountType, (newVal) => {
    filters.value['account_type_id'].value = newVal ? newVal : null;
});


watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const handleFilter = (e) => {
    filteredValueCount.value = e.filteredValue.length;
};

</script>

<template>
    <DataTable
        v-model:filters="filters"
        :value="accounts"
        :paginator="accounts?.length > 0 && filteredValueCount > 0"
        removableSort
        :rows="10"
        :rowsPerPageOptions="[10, 20, 50, 100]"
        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        :currentPageReportTemplate="$t('public.paginator_caption')"
        :globalFilterFields="['name', 'email', 'balance', 'equity']"
        ref="dt"
        :loading="loading"
        selectionMode="single"
        @filter="handleFilter"
    >
        <template #header>
            <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                    <div class="relative w-full md:w-60">
                        <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                            <IconSearch size="20" stroke-width="1.25" />
                        </div>
                        <InputText v-model="filters['global'].value" :placeholder="$t('public.keyword_search')" class="font-normal pl-12 w-full md:w-60" />
                        <div
                            v-if="filters['global'].value !== null"
                            class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                            @click="clearFilterGlobal"
                        >
                            <IconCircleXFilled size="16" />
                        </div>
                    </div>
                    <Select
                        v-model="accountType"
                        :options="accountTypes"
                        filter
                        :filterFields="['name']"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.filter_by_account_type')"
                        class="w-full md:w-60"
                        scroll-height="236px"
                    />
                </div>
                <Button
                    type="button"
                    variant="error-outlined"
                    size="base"
                    class='w-full md:w-auto'
                    @click="clearFilter"
                >
                    <IconFilterOff size="20" stroke-width="1.25" />
                    {{ $t('public.clear') }}
                </Button>
            </div>
        </template>
        <template #empty>
            <Empty 
                :title="$t('public.empty_agent_title')" 
                :message="$t('public.empty_agent_message')" 
            />
        </template>
        <template #loading>
            <div class="flex flex-col gap-2 items-center justify-center">
                <Loader />
                <span class="text-sm text-gray-700">{{ $t('public.loading_transactions_caption') }}</span>
            </div>
        </template>
        <template v-if="accounts?.length > 0 && filteredValueCount > 0">
            <Column field="name" sortable :header="$t('public.name')" class="hidden md:table-cell w-[20%] max-w-0">
                <template #body="slotProps">
                    <div class="flex flex-col items-start max-w-full">
                        <div class="font-semibold truncate max-w-full">
                            {{ slotProps.data.name }}
                        </div>
                        <div class="text-gray-500 text-xs truncate max-w-full">
                            {{ slotProps.data.email }}
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="last_logged_in" :header="$t('public.last_logged_in')" sortable class="w-full md:w-[20%] max-w-0" headerClass="hidden md:table-cell">
                <template #body="slotProps">
                    <div class="flex flex-col items-start max-w-full">
                        <div class="text-gray-950 text-sm font-semibold truncate max-w-full md:hidden">
                            {{ slotProps.data.meta_login }}
                        </div>
                        <div class="flex items-center gap-1 truncate max-w-full">
                            <div class="text-gray-500 text-xs md:hidden">{{ $t('public.last_logged_in') }}:</div>
                            <div class="text-gray-700 md:text-gray-950 text-xs md:text-sm font-medium md:font-normal truncate max-w-full">
                                {{ dayjs(slotProps.data.last_login).format('YYYY/MM/DD HH:mm:ss') }}
                            </div>
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="meta_login" :header="$t('public.account')" sortable class="hidden md:table-cell w-[15%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ slotProps.data.meta_login }}
                    </div>
                </template>
            </Column>
            <Column field="balance" :header="`${$t('public.balance')}&nbsp;($)`" sortable class="hidden md:table-cell w-[15%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ formatAmount(slotProps.data.balance) }}
                    </div>
                </template>
            </Column>
            <Column field="equity" :header="`${$t('public.equity')}&nbsp;($)`" sortable class="hidden md:table-cell w-[15%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ formatAmount(slotProps.data.equity) }}
                    </div>
                </template>
            </Column>
            <Column field="action" class="w-full md:w-[15%]" headerClass="hidden md:table-cell">
                <template #body="slotProps">
                    <Action 
                        :account="slotProps.data"
                    />
                </template>
            </Column>
        </template>
    </DataTable>
</template>