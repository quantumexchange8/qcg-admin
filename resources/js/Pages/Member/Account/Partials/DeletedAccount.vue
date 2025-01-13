<script setup>
import { usePage } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconFilterOff } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted } from "vue";
import Loader from "@/Components/Loader.vue";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import Button from '@/Components/Button.vue';
import Select from "primevue/select";
import { FilterMatchMode } from '@primevue/core/api';
import Empty from "@/Components/Empty.vue";
import { transactionFormat } from "@/Composables/index.js";
import dayjs from "dayjs";
import debounce from "lodash/debounce.js";

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
const selectedBrand = ref(null);
const first = ref(0);
const totalRecords = ref(0);

const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};

const emit = defineEmits(['update:filters']);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    account_type_id: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const clearFilter = () => {
    filters.value['global'].value = null;
    filters.value['account_type_id'].value = null;

    accountType.value = null;
    filteredValue.value = null;
};

const lazyParams = ref({});

const loadLazyData = (event) => {
    loading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };

    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value),
            };

            const url = route('member.getAccountListingPaginate', params);
            const response = await fetch(url);
            const results = await response.json();

            accounts.value = results?.data?.data;
            totalRecords.value = results?.data?.total;

            loading.value = false;
        }, 100);
    }  catch (e) {
        users.value = [];
        totalRecords.value = 0;
        loading.value = false;
    }
};
const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};
const onSort = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};
const onFilter = (event) => {
    lazyParams.value.filters = filters.value ;
    loadLazyData(event);
    filteredValue.value = event.filteredValue;
    console.log(event)
};

onMounted(() => {
    lazyParams.value = {
        first: dt.value.first,
        rows: dt.value.rows,
        sortField: null,
        sortOrder: null,
        filters: filters.value
    };

    loadLazyData();
});

watch(
    filters.value['global'],
    debounce(() => {
        loadLazyData();
    }, 1000)
);

watch([filters.value['account_type_id']], () => {
    loadLazyData()
});

watch(() => usePage().props.toast, (newToast) => {
        if (newToast !== null) {
            getResults();
        }
    }
);

watch(filters, (newFilters) => {
    emit('update:filters', newFilters); // Emit the filters to the parent component
}, { deep: true });

</script>

<template>
    <DataTable
        v-model:filters="filters"
        :value="accounts"
        :rowsPerPageOptions="[10, 20, 50, 100]"
        lazy
        paginator
        removableSort
        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport JumpToPageInput"
        :currentPageReportTemplate="$t('public.paginator_caption')"
        :first="first"
        :rows="10"
        ref="dt"
        dataKey="id"
        :totalRecords="totalRecords"
        :loading="loading"
        @page="onPage($event)"
        @sort="onSort($event)"
        @filter="onFilter($event)"
        :globalFilterFields="['name', 'email', 'balance', 'equity']"
        v-model:selection="selectedBrand"
        selectionMode="single"
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
                        v-model="filters['account_type_id'].value"
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
        <template v-if="accounts?.length > 0">
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
            <Column field="deleted_at" :header="$t('public.deleted_time')" sortable class="w-[20%] max-w-0" headerClass="hidden md:table-cell">
                <template #body="slotProps">
                    <div class="flex flex-col items-start max-w-full">
                        <div class="text-gray-950 text-sm font-semibold truncate max-w-full md:hidden">
                            {{ slotProps.data?.meta_login || '-' }}
                        </div>
                        <div class="flex items-center gap-1 w-full md:w-1/2">
                            <div class="text-gray-500 text-xs md:hidden">{{ $t('public.deleted_time') }}:</div>
                            <div class="text-gray-700 md:text-gray-950 text-xs md:text-sm font-medium md:font-normal w-1/2">
                                {{ dayjs(slotProps.data.deleted_at).format('YYYY/MM/DD HH:mm:ss') }}
                            </div>
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="meta_login" :header="$t('public.account')" sortable class="hidden md:table-cell w-[20%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ slotProps.data?.meta_login || '-' }}
                    </div>
                </template>
            </Column>
            <Column field="balance" :header="`${$t('public.balance')}&nbsp;($)`" sortable class="hidden md:table-cell w-[20%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ formatAmount(slotProps.data?.balance || 0) }}
                    </div>
                </template>
            </Column>
            <Column field="equity" :header="`${$t('public.equity')}&nbsp;($)`" sortable class="hidden md:table-cell w-[20%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ formatAmount(slotProps.data?.equity || 0) }}
                    </div>
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
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{  data?.meta_login || '-' }}</span>
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
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.deleted_time') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">    
                        {{ dayjs(data.deleted_at).format('YYYY/MM/DD HH:mm:ss') }} ({{ dayjs().diff(dayjs(data.deleted_at), 'day') }} {{ $t('public.days') }})
                    </span>
                </div>
            </div>
        </div>
    </Dialog>
</template>