<script setup>
import { usePage } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconFilterOff, IconRefresh, IconDownload } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted, onUnmounted } from "vue";
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
import Action from "@/Pages/Member/Account/Partials/Action.vue"
import StatusBadge from '@/Components/StatusBadge.vue';

const { formatAmount } = transactionFormat();

const props = defineProps({
    accountTypes: Array,
});

const visible = ref(false);
const data = ref({});
const loading = ref(false);
const dt = ref(null);

const accounts = ref();
const accountTypes = ref(props.accountTypes);
const selectedBrand = ref(null);
const rows = ref(20);
const page = ref(0);
const first = ref(0);
const sortField = ref(null);  
const sortOrder = ref(null);  // (1 for ascending, -1 for descending)
const totalRecords = ref(0);

const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};

const emit = defineEmits(['update:filters']);

const filters = ref({
    global: '',
    account_type_id: null,
});

// Watch for changes on the entire 'filters' object and debounce the API call
watch(filters, debounce(() => {
    getResults(); // Call getResults function to fetch the data
}, 1000), { deep: true });


const clearFilterGlobal = () => {
    filters.value['global'] = '';
}

const clearFilter = () => {
    filters.value['global'] = '';
    filters.value['account_type_id'] = null;
};

const getResults = async () => {
    loading.value = true;
    try {
        // Directly construct the URL with necessary query parameters
        let url = `/member/getAccountListingPaginate?rows=${rows.value}&page=${page.value}`;

        // Add filters if present
        if (filters.value.global) {
            url += `&search=${filters.value.global}`;
        }

        if (filters.value.account_type_id) {
            url += `&account_type_id=${filters.value.account_type_id}`;
        }

        if (sortField.value && sortOrder.value !== null) {
            url += `&sortField=${sortField.value}&sortOrder=${sortOrder.value}`;
        }

        // Make the API request
        const response = await axios.get(url);
        const results = response.data;

        // Directly set the results data
        accounts.value = results?.data?.data;
        totalRecords.value = results?.data?.total;

        loading.value = false;
    } catch (error) {
        console.error('Error fetching leads data:', error);
        accounts.value = [];
        loading.value = false;
    } finally {
        loading.value = false;
    }
};

const onPage = async (event) => {
    rows.value = event.rows;
    page.value = event.page;

    getResults();
};

const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;  // Store ascending or descending order
    page.value = 0;

    getResults();
};

onMounted(() => {
    getResults();
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

// Refresh all accounts
const refreshAll = () => {
    router.post(route('member.refreshAllAccount'));
};

const exportStatus = ref(false);
const type = ref('deleted');

const exportAccount = () => {
    exportStatus.value = true;
    try {
        let url = `/member/getAccountListingPaginate`;

        if (exportStatus.value === true) {
            url += `?exportStatus=${exportStatus.value}`;
        }

        if (type.value) {
            url += `&type=${type.value}`;
        }

        if (filters.value.global) {
            url += `&search=${filters.value.global}`;
        }

        if (filters.value.account_type_id) {
            url += `&account_type_id=${filters.value.account_type_id}`;
        }

        window.location.href = url;
    } catch (e) {
        console.error('Error occurred during export:', e);
    } finally {
        loading.value = false;
        exportStatus.value = false;
    }
};

const pageLinkSize = ref(window.innerWidth < 768 ? 3 : 5)

const updatePageLinkSize = () => {
  pageLinkSize.value = window.innerWidth < 768 ? 3 : 5
}

onMounted(() => {
    window.addEventListener('resize', updatePageLinkSize);
    getResults();
})

onUnmounted(() => {
  window.removeEventListener('resize', updatePageLinkSize)
})
</script>

<template>
    <DataTable
        :value="accounts"
        :rows="20"
        :pageLinkSize="pageLinkSize"
        lazy
        paginator
        removableSort
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        :currentPageReportTemplate="$t('public.paginator_caption')"
        :first="first"
        :page="page"
        ref="dt"
        dataKey="id"
        :totalRecords="totalRecords"
        :loading="loading"
        @page="onPage($event)"
        @sort="onSort($event)"
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
                        <InputText v-model="filters['global']" :placeholder="$t('public.keyword_search')" size="search" class="font-normal w-full md:w-60" />
                        <div
                            v-if="filters['global'] !== null && filters['global'] !== ''"
                            class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                            @click="clearFilterGlobal"
                        >
                            <IconCircleXFilled size="16" />
                        </div>
                    </div>
                    <Select
                        v-model="filters['account_type_id']"
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
                <div class="w-full flex flex-col items-center gap-3 md:w-auto md:flex-row md:gap-2">
                    <div class="w-full hidden md:flex md:flex-row items-center md:gap-2 md:w-auto">
                        <Button type="button" variant="primary-flat" class="flex justify-center w-full md:w-auto" @click="refreshAll">
                            <IconRefresh color="white" size="20" stroke-width="1.25" />
                            <span>{{ $t('public.update_all') }}</span>
                        </Button>
                        <Button variant="primary-outlined" @click="exportAccount()" class="w-full md:w-auto">
                            <IconDownload size="20" stroke-width="1.25" />
                            {{ $t('public.export') }}
                        </Button>
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
            <Column field="name" sortable :header="$t('public.name')" class="hidden md:table-cell w-[19%] max-w-0">
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
            <Column field="deleted_at" :header="$t('public.deleted_time')" sortable class="w-[19%] max-w-0" headerClass="hidden md:table-cell">
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
            <Column field="meta_login" :header="$t('public.account')" sortable class="hidden md:table-cell w-[19%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ slotProps.data?.meta_login || '-' }}
                    </div>
                </template>
            </Column>
            <Column field="balance" :header="`${$t('public.balance')}&nbsp;($)`" sortable class="hidden md:table-cell w-[19%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ formatAmount((slotProps.data?.balance || 0) - (slotProps.data?.credit || 0)) }}
                    </div>
                </template>
            </Column>
            <Column field="equity" :header="`${$t('public.equity')}&nbsp;($)`" sortable class="hidden md:table-cell w-[19%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ formatAmount(slotProps.data?.equity || 0) }}
                    </div>
                </template>
            </Column>
            <Column field="action" class="w-full md:w-[5%]" headerClass="hidden md:table-cell">
                <template #body="slotProps">
                    <Action 
                        :account="slotProps.data"
                        type="deleted"
                    />
                </template>
            </Column>
        </template>
    </DataTable>
    <Dialog v-model:visible="visible" modal :header="$t('public.account_details')" class="dialog-xs md:dialog-md" :dismissableMask="true">
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
                    <span class="w-full truncate text-gray-950 text-sm font-medium">$&nbsp;{{ formatAmount((data?.balance || 0) - (data?.credit || 0)) }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.equity') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">$&nbsp;{{ formatAmount(data?.equity || 0) }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.credit') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">$&nbsp;{{ formatAmount(data?.credit || 0) }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.leverage') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">1:{{ data?.leverage }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.cbroker_status') }}</span>
                    <StatusBadge :variant="data?.cbroker_status" :value="$t('public.' + data?.cbroker_status)" class="text-nowrap"/>
                    <!-- <span class="w-full truncate text-gray-950 text-sm font-medium">{{ $t(`public.${data?.cbroker_status}`) }}</span> -->
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