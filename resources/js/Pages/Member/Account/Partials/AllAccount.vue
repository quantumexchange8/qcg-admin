<script setup>
import { usePage } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconFilterOff, IconAlertCircleFilled } from "@tabler/icons-vue";
import { ref, watch, watchEffect } from "vue";
import Loader from "@/Components/Loader.vue";
import Dialog from "primevue/dialog";
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
    accountTypes: Array,
});

const visible = ref(false);
const data = ref({});
const loading = ref(false);
const dt = ref(null);

const accounts = ref();
const filteredValue = ref();
const accountTypes = ref(props.accountTypes);
const accountType = ref(null)

const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};

const emit = defineEmits(['update:filteredValue']);

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
    filteredValue.value = null;
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
    filteredValue.value = e.filteredValue;
    emit('update:filteredValue', filteredValue.value);
};

</script>

<template>
    <DataTable
        v-model:filters="filters"
        :value="accounts"
        :paginator="accounts?.length > 0 && filteredValue?.length > 0"
        removableSort
        :rows="10"
        :rowsPerPageOptions="[10, 20, 50, 100]"
        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        :currentPageReportTemplate="$t('public.paginator_caption')"
        :globalFilterFields="['name', 'email', 'meta_login', 'balance', 'equity']"
        ref="dt"
        :loading="loading"
        selectionMode="single"
        @filter="handleFilter"
        @row-click="(event) => openDialog(event.data)"
    >
        <template #header>
            <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                    <div class="relative w-full md:w-60">
                        <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                            <IconSearch size="20" stroke-width="1.25" />
                        </div>
                        <InputText v-model="filters['global'].value" :placeholder="$t('public.keyword_search')" size="search" class="font-normal w-full md:w-60" />
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
                        class="w-full md:w-60 font-normal"
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
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>
        </template>
        <template v-if="accounts?.length > 0 && filteredValue?.length > 0">
            <Column field="name" sortable :header="$t('public.name')" class="hidden md:table-cell w-[20%] max-w-0">
                <template #body="slotProps">
                    <div class="flex flex-col items-start max-w-full">
                        <div class="font-semibold max-w-full truncate">
                            {{ slotProps.data.name }}
                        </div>
                        <div class="text-gray-500 text-xs max-w-full truncate">
                            {{ slotProps.data.email }}
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="last_login" :header="$t('public.last_logged_in')" sortable class="w-full md:w-[20%] max-w-0" headerClass="hidden md:table-cell truncate">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm font-semibold truncate max-w-full md:hidden">
                        {{ slotProps.data.meta_login }}
                    </div>
                    <div class="w-1/2 flex items-center gap-1">
                        <div class="text-gray-500 text-xs md:hidden">{{ $t('public.last_logged_in') }}</div>
                        <div class="text-gray-500 text-xs md:hidden">:</div>
                        <div class="text-gray-700 md:text-gray-950 text-xs md:text-sm font-medium md:font-normal">
                            {{ dayjs(slotProps.data.last_login).format('YYYY/MM/DD HH:mm:ss') }}
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="meta_login" :header="$t('public.account')" sortable class="hidden md:table-cell w-[12%] max-w-0" headerClass="truncate">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm flex flex-wrap gap-1 items-center truncate">
                        {{ slotProps.data.meta_login }}
                        <IconAlertCircleFilled  :size="20" stroke-width="1.25" class="text-error-500 grow-0 shrink-0" v-if="!slotProps.data.is_active" v-tooltip.top="$t('public.trading_account_inactive_warning')"/>
                    </div>
                </template>
            </Column>
            <Column field="balance" :header="`${$t('public.balance')}&nbsp;($)`" sortable class="hidden md:table-cell w-[12%] max-w-0" headerClass="truncate">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm truncate">
                        {{ formatAmount(slotProps.data.balance || 0) }}
                    </div>
                </template>
            </Column>
            <Column field="equity" :header="`${$t('public.equity')}&nbsp;($)`" sortable class="hidden md:table-cell w-[12%] max-w-0" headerClass="truncate">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm truncate">
                        {{ formatAmount((slotProps.data?.equity || 0) - (slotProps.data?.credit || 0)) }}
                    </div>
                </template>
            </Column>
            <Column field="credit" :header="`${$t('public.credit')}&nbsp;($)`" sortable class="hidden md:table-cell w-[12%] max-w-0" headerClass="truncate">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm truncate">
                        {{ formatAmount(slotProps.data.credit || 0) }}
                    </div>
                </template>
            </Column>
            <Column field="action" class="w-full md:w-[12%]" headerClass="hidden md:table-cell">
                <template #body="slotProps">
                    <Action 
                        :account="slotProps.data"
                    />
                </template>
            </Column>
        </template>
    </DataTable>

    <Dialog v-model:visible="visible" modal :header="$t('public.account_details')" class="dialog-xs md:dialog-md">
        <div class="flex flex-col justify-center items-center gap-3 self-stretch pt-4 md:pt-6">
            <div class="flex flex-col justify-between items-center p-3 gap-3 self-stretch bg-gray-50 md:flex-row">
                <div class="flex flex-col items-start w-full truncate">
                    <span class="w-full truncate text-gray-950 font-semibold">{{ data?.name }}</span>
                    <span class="w-full truncate text-gray-500 text-sm">{{ data?.email }}</span>
                </div>
            </div>
            
            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.account') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.meta_login || '-' }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.balance') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">$&nbsp;{{ formatAmount(data?.balance || 0) }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.equity') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">$&nbsp;{{ formatAmount((data?.equity || 0) - (data?.credit || 0)) }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.credit') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">$&nbsp;{{ formatAmount(data?.credit || 0) }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.leverage') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">1:{{ data?.leverage }}</span>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.last_logged_in') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">    
                        {{ dayjs(data.last_login).format('YYYY/MM/DD HH:mm:ss') }} ({{ dayjs().diff(dayjs(data.last_login), 'day') }} {{ $t('public.days') }})
                    </span>
                </div>
            </div>
        </div>
    </Dialog>

</template>