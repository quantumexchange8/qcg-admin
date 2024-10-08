<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePage } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff } from "@tabler/icons-vue";
import { ref, watch, watchEffect } from "vue";
import Loader from "@/Components/Loader.vue";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import ColumnGroup from "primevue/columngroup";
import Row from "primevue/row";
import Button from '@/Components/Button.vue';
import Select from "primevue/select";
import { FilterMatchMode } from '@primevue/core/api';
import Empty from "@/Components/Empty.vue";
import { transactionFormat } from "@/Composables/index.js";
import dayjs from "dayjs";
import { trans, wTrans } from "laravel-vue-i18n";
import StatusBadge from '@/Components/StatusBadge.vue';
import MultiSelect from "primevue/multiselect";

const { formatAmount } = transactionFormat();

const visible = ref(false);
const loading = ref(false);
const dt = ref(null);
const transactions = ref();
const totalAmount = ref();
const type = ref('withdrawal');
const months = ref([]);
const selectedMonths = ref([]);
const filteredValueCount = ref(0);
const withdrawalFrom = ref();

// Define the status options
const statusOption = [
    { name: wTrans('public.successful'), value: 'successful' },
    { name: wTrans('public.rejected'), value: 'rejected' }
];

// Define the withdrawal from options
const withdrawalFromOption = [
    { name: wTrans('public.rebate'), value: 'rebate' },
    { name: wTrans('public.account'), value: 'account' },
    { name: wTrans('public.incentive'), value: 'incentive' }
];

const getCurrentMonthYear = () => {
    const date = new Date();
    return `${dayjs(date).format('MMMM YYYY')}`;
};

// Fetch settlement months from API
const getTransactionMonths = async () => {
    try {
        const response = await axios.get('/getTransactionMonths');
        months.value = response.data.months;

        if (months.value.length) {
            selectedMonths.value = [getCurrentMonthYear()];
        }
    } catch (error) {
        console.error('Error transaction months:', error);
    }
};

getTransactionMonths()

const getResults = async (selectedMonths = [], from = null) => {
    loading.value = true;

    try {
        // Create the base URL with the type parameter directly in the URL
        let url = `/transaction/getTransactionData?type=${type.value}`;

        // Convert the array to a comma-separated string if not empty
        if (selectedMonths && selectedMonths.length > 0) {
            const selectedMonthString = selectedMonths.map(month => dayjs(month, 'MMMM YYYY').format('MM/YYYY')).join(',');
            url += `&selectedMonths=${selectedMonthString}`;
        }

        // Check if from has a value and append to the URL
        if (from) {
            url += `&from=${from}`;
        }

        // Make the API call with the constructed URL
        const response = await axios.get(url);
        transactions.value = response.data.transactions;
        totalAmount.value = response.data.totalAmount;

    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

// Watch for changes in selectedMonths
watch(selectedMonths, (newMonths) => {
    getResults(newMonths, withdrawalFrom.value);
    // console.log('Selected Months:', newMonths);
});

// Watch for changes in withdrawalFrom
watch(withdrawalFrom, (newWithdrawalFrom) => {
    getResults(selectedMonths.value, newWithdrawalFrom);
    // console.log('Withdrawal From:', newWithdrawalFrom);
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const recalculateTotals = () => {
    const globalFilterValue = filters.value.global?.value?.toLowerCase();

    const filtered = transactions.value.filter(transaction => {
        const matchesGlobalFilter = globalFilterValue 
            ? [
                transaction.name, 
                transaction.email, 
                transaction.from_meta_login, 
                transaction.from_wallet_name
            ].some(field => {
                // Convert field to string and check if it includes the global filter value
                const fieldValue = field !== undefined && field !== null ? field.toString() : '';
                return fieldValue.toLowerCase().includes(globalFilterValue);
            }) 
            : true; // If no global filter is set, match all

        // Apply individual field filters (name, email, status)
        const matchesNameFilter = !filters.value.name?.value || transaction.name.startsWith(filters.value.name.value);
        const matchesEmailFilter = !filters.value.email?.value || transaction.email.startsWith(filters.value.email.value);
        const matchesStatusFilter = !filters.value.status?.value || transaction.status === filters.value.status.value;

        // Only return transactions that match both global and specific filters
        return matchesGlobalFilter && matchesNameFilter && matchesEmailFilter && matchesStatusFilter;
    });

    // Calculate the total for successful transactions
    totalAmount.value = filtered.filter(item => item.status === 'successful').reduce((acc, item) => acc + parseFloat(item.transaction_amount || 0), 0);
};

watch(filters, () => {
    recalculateTotals();
}, { deep: true });

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        email: { value: null, matchMode: FilterMatchMode.CONTAINS },
        status: { value: null, matchMode: FilterMatchMode.EQUALS },
    };
    selectedMonths.value = [getCurrentMonthYear()];
    withdrawalFrom.value = null;
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const handleFilter = (e) => {
    filteredValueCount.value = e.filteredValue.length;
};

const exportCSV = () => {
    dt.value.exportCSV();
};

// dialog
const data = ref({});
const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};

const tooltipText = ref('copy');
const copyToClipboard = (text) => {
    const textToCopy = text;

    const textArea = document.createElement('textarea');
    document.body.appendChild(textArea);

    textArea.value = textToCopy;
    textArea.select();

    try {
        const successful = document.execCommand('copy');

        tooltipText.value = 'copied';
        setTimeout(() => {
            tooltipText.value = 'copy';
        }, 1500);
    } catch (err) {
        console.error('Copy to clipboard failed:', err);
    }

    document.body.removeChild(textArea);
}

</script>

<template>
    <AuthenticatedLayout :title="`${$t('public.transactions')}&nbsp;-&nbsp;${$t('public.sidebar_withdrawal')}`">
        <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6 w-full">
            <div class="flex flex-col pb-3 gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between md:pb-0">
                <span class="text-gray-950 font-semibold self-stretch md:self-auto">{{ $t('public.all_withdrawal') }}</span>
                <div class="flex flex-col gap-3 items-center self-stretch md:flex-row md:gap-5">
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
                    <Button variant="primary-outlined" @click="exportCSV" class="w-full md:w-auto">
                        <IconDownload size="20" stroke-width="1.25" />
                        {{ $t('public.export') }}
                    </Button>
                </div>
            </div>
            
            <div v-if="months.length === 0" class="flex flex-col gap-2 items-center justify-center">
                <Loader />
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>

            <DataTable
                v-else
                v-model:filters="filters"
                :value="transactions"
                :paginator="transactions?.length > 0 && filteredValueCount > 0"
                removableSort
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :globalFilterFields="['name', 'email', 'from_meta_login', 'from_wallet_name']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @filter="handleFilter"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 items-center gap-3 self-stretch md:gap-5">
                            <MultiSelect
                                v-model="selectedMonths"
                                :options="months"
                                :placeholder="$t('public.month_placeholder')"
                                :maxSelectedLabels="1"
                                :selectedItemsLabel="`${selectedMonths.length} ${$t('public.months_selected')}`"
                                class="w-full md:w-auto font-normal"
                            >
                                <template #header>
                                    <div class="absolute flex left-10 top-2">
                                        {{ $t('public.select_all') }}
                                    </div>
                                </template>
                                <template #value>
                                    <span v-if="selectedMonths.length === 1">
                                        {{ dayjs(selectedMonths[0]).format('MMMM YYYY') }}
                                    </span>
                                    <span v-else-if="selectedMonths.length > 1">
                                        {{ selectedMonths.length }} {{ $t('public.months_selected') }}
                                    </span>
                                    <span v-else>
                                        {{ $t('public.month_placeholder') }}
                                    </span>
                                </template>
                            </MultiSelect>
                            <Select
                                v-model="filters['status'].value"
                                :options="statusOption"
                                optionLabel="name"
                                optionValue="value"
                                :placeholder="$t('public.filter_by_status')"
                                class="w-full md:w-auto font-normal"
                                scroll-height="236px"
                            />
                            <Select
                                v-model="withdrawalFrom"
                                :options="withdrawalFromOption"
                                optionLabel="name"
                                optionValue="value"
                                :placeholder="$t('public.filter_by_withdrawal_from')"
                                class="w-full md:w-auto font-normal"
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
                        :title="$t('public.empty_withdrawal_record_title')" 
                        :message="$t('public.empty_withdrawal_record_message')" 
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="transactions?.length > 0 && filteredValueCount > 0">
                    <Column field="name" sortable :header="$t('public.name')" class="w-1/2 md:w-[20%] max-w-0 px-3">
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
                    <Column field="created_at" :header="$t('public.date')" sortable class="hidden md:table-cell w-full md:w-[20%] max-w-0">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm truncate max-w-full">
                                {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                            </div>
                        </template>
                    </Column>
                    <Column field="id_number" :header="$t('public.id')" sortable class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data.id_number }}
                            </div>
                        </template>
                    </Column>
                    <Column field="from" :header="$t('public.from')" class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data?.from_meta_login || slotProps.data?.from_wallet_name }}
                            </div>
                        </template>
                    </Column>
                    <Column field="transaction_amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable class="w-1/2 md:w-[15%] px-3">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ formatAmount(slotProps.data.transaction_amount) }}
                            </div>
                        </template>
                    </Column>
                    <Column field="status" :header="`${$t('public.status')}&nbsp;($)`" class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <StatusBadge :variant="slotProps.data.status" :value="$t('public.' + slotProps.data.status)"/>
                            </div>
                        </template>
                    </Column>
                    <ColumnGroup type="footer">
                        <Row>
                            <Column class="hidden md:table-cell" :footer="$t('public.total') + '&nbsp;($)&nbsp;:'" :colspan="5" footerStyle="text-align:right" />
                            <Column class="hidden md:table-cell" :colspan="6" :footer="formatAmount(totalAmount)" />
                            <Column class="md:hidden" :footer="$t('public.total') + '&nbsp;($)&nbsp;:'" :colspan="1" footerStyle="text-align:right" />
                            <Column class="md:hidden" :colspan="2" :footer="formatAmount(totalAmount)" />
                        </Row>
                    </ColumnGroup>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>

    <Dialog v-model:visible="visible" modal :header="$t('public.withdrawal_details')" class="dialog-xs md:dialog-md">
        <div class="flex flex-col justify-center items-center gap-3 self-stretch pt-4 md:pt-6">
            <div class="flex flex-col justify-between items-center p-3 gap-3 self-stretch bg-gray-50 md:flex-row">
                <div class="flex flex-col items-start w-full truncate">
                    <span class="w-full truncate text-gray-950 font-semibold">{{ data.name }}</span>
                    <span class="w-full truncate text-gray-500 text-sm">{{ data.email }}</span>
                </div>
                <div class="flex items-center self-stretch">
                    <span class="w-full truncate text-gray-950 text-lg font-semibold">{{ `$&nbsp;${formatAmount(data.transaction_amount)}` }}</span>
                </div>
            </div>
            
            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ dayjs(data.created_at).format('YYYY/MM/DD') }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.transaction_id') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.transaction_number }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.from') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.from_meta_login || data?.from_wallet_name }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.status') }}</span>
                    <StatusBadge :variant="data.status" :value="$t('public.' + data.status)"/>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.wallet_name') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.wallet_name }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.receiving_address') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium" @click="copyToClipboard(data.wallet_address)">{{ data.wallet_address }}</span>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.remarks') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.remarks }}</span>
                </div>
            </div>
        </div>
    </Dialog>

</template>