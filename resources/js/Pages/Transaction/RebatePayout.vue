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

const { formatAmount } = transactionFormat();

const props = defineProps({
    accountTypes: Array,
});

const visible = ref(false);
const loading = ref(false);
const dt = ref(null);
const transactions = ref();
const totalAmount = ref();

const filteredValue = ref();

// Define the account type options
const accountTypeOption = ref();

const getAccountTypes = async () => {
    try {
        const response = await axios.get('/getAccountTypes');
        accountTypeOption.value = response.data.accountTypes;

    } catch (error) {
        console.error('Error account types:', error);
    }
};

getAccountTypes()

const months = ref([]);
const selectedMonth = ref('');

const getCurrentMonthYear = () => {
    const date = new Date();
    return `01 ${dayjs(date).format('MMMM YYYY')}`;
};

// Fetch settlement months from API
const getRebateMonths = async () => {
    try {
        const response = await axios.get('/getRebateMonths');
        months.value = response.data.months;

        if (months.value.length) {
            selectedMonth.value = getCurrentMonthYear();
        }
    } catch (error) {
        console.error('Error rebate months:', error);
    }
};

watch(selectedMonth, (newMonth) => {
    getResults(newMonth);
});

getRebateMonths()
const getResults = async (selectedMonth = '') => {
    loading.value = true;

    try {
        let url = `/transaction/getRebatePayoutData`;

        if (selectedMonth) {
            let formattedMonth = selectedMonth;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            url += `?selectedMonth=${formattedMonth}`;
        }

        const response = await axios.get(url);
        transactions.value = response.data.transactions
        totalAmount.value = response.data.totalAmount;
    } catch (error) {
        console.error('Error fetching rebate payout data:', error);
        transactions.value = [];
        totalAmount.value = 0;
    } finally {
        loading.value = false;
    }
};

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    account_type: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const recalculateTotals = () => {
    const globalFilterValue = filters.value.global?.value?.toLowerCase();

    const filtered = transactions.value.filter(transaction => {
        const matchesGlobalFilter = globalFilterValue 
            ? [
                transaction.name,
                transaction.email,
            ].some(field => {
                // Convert field to string and check if it includes the global filter value
                const fieldValue = field !== undefined && field !== null ? field.toString() : '';
                return fieldValue.toLowerCase().includes(globalFilterValue);
            }) 
            : true; // If no global filter is set, match all

        // Apply individual field filters (name, email, status)
        const matchesNameFilter = !filters.value.name?.value || transaction.name.startsWith(filters.value.name.value);
        const matchesEmailFilter = !filters.value.email?.value || transaction.email.startsWith(filters.value.email.value);
        const matchesTransactionTypeFilter = !filters.value.account_type?.value || transaction.account_type.startsWith(filters.value.account_type.value);

        // Only return transactions that match both global and specific filters
        return matchesGlobalFilter && matchesNameFilter && matchesEmailFilter && matchesTransactionTypeFilter;
    });
    // Calculate the total for successful transactions
    totalAmount.value = filtered.reduce((acc, item) => acc + parseFloat(item.rebate || 0), 0);
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
        account_type: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
    selectedMonth.value = getCurrentMonthYear();
    filteredValue.value = null; 
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
};

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = filteredValue.value;

    // Specify the headers
    const headers = [
        trans('public.name'),
        trans('public.email'),
        trans('public.date'),
        trans('public.account_type'),
        trans('public.volume') + ' (Ł)',
        trans('public.amount') + ' ($)'
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        return [
            obj.name !== undefined ? obj.name : '',
            obj.email !== undefined ? obj.email : '',
            obj.execute_at !== undefined ? dayjs(obj.execute_at).format('YYYY/MM/DD') : '',
            obj.account_type !== undefined ? trans('public.' + obj.account_type) : '',
            obj.volume !== undefined ? obj.volume : '',
            obj.rebate !== undefined ? obj.rebate : ''
        ];
    });

    // Combine headers and rows into a single data array
    const sheetData = [headers, ...rows];

    // Create the XLSX content
    let csvContent = "data:text/xlsx;charset=utf-8,";
    
    sheetData.forEach((rowArray) => {
        const row = rowArray.join("\t"); // Use tabs for column separation
        csvContent += row + "\r\n"; // Add a new line after each row
    });

    // Create a temporary link element
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "export.xlsx");

    // Append the link to the document and trigger the download
    document.body.appendChild(link);
    link.click();

    // Clean up by removing the link
    document.body.removeChild(link);
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
    <AuthenticatedLayout :title="`${$t('public.transactions')}&nbsp;-&nbsp;${$t('public.sidebar_rebate_payout')}`">
        <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="flex flex-col pb-3 gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between md:pb-0">
                <span class="text-gray-950 font-semibold self-stretch md:self-auto">{{ $t('public.all_rebate_payout') }}</span>
            </div>
            <DataTable
                v-model:filters="filters"
                :value="transactions"
                :paginator="transactions?.length > 0 && filteredValue?.length > 0"
                removableSort
                :rows="20"
                :rowsPerPageOptions="[20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :globalFilterFields="['name', 'email']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @filter="handleFilter"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                        <div class="grid grid-cols-3 md:grid-cols-4 items-center gap-3 self-stretch md:flex-row md:gap-2">
                            <Select 
                                v-model="selectedMonth" 
                                :options="months" 
                                :placeholder="$t('public.month_placeholder')"
                                class="w-full md:max-w-60 font-normal truncate" scroll-height="236px" 
                            >
                                <template #option="{ option }">
                                    <span class="text-sm">
                                        <template v-if="option === 'select_all'">
                                            {{ $t('public.select_all') }}
                                        </template>
                                        <template v-else-if="option.startsWith('last_')">
                                            {{ $t(`public.${option}`) }}
                                        </template>
                                        <template v-else>
                                            {{ $t(`public.${option.split(' ')[1]}`) }} {{ option.split(' ')[2] }}
                                        </template>
                                    </span>
                                </template>
                                <template #value>
                                    <span v-if="selectedMonth">
                                        <template v-if="selectedMonth === 'select_all'">
                                            {{ $t('public.select_all') }}
                                        </template>
                                        <template v-else-if="selectedMonth.startsWith('last_')">
                                            {{ $t(`public.${selectedMonth}`) }}
                                        </template>
                                        <template v-else>
                                            {{ $t(`public.${dayjs(selectedMonth).format('MMMM')}`) }} {{ dayjs(selectedMonth).format('YYYY') }}
                                        </template>
                                    </span>
                                    <span v-else>
                                        {{ $t('public.month_placeholder') }}
                                    </span>
                                </template>
                            </Select>
                            <Select
                                v-model="filters['account_type'].value"
                                :options="accountTypeOption"
                                optionLabel="name"
                                optionValue="slug"
                                :placeholder="$t('public.filter_by_account_type')"
                                class="w-full md:max-w-60 font-normal"
                                scroll-height="236px"
                            />
                            <div class="relative w-full md:max-w-60">
                                <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                                    <IconSearch size="20" stroke-width="1.25" />
                                </div>
                                <InputText v-model="filters['global'].value" :placeholder="$t('public.keyword_search')" size="search" class="font-normal w-full" />
                                <div
                                    v-if="filters['global'].value !== null"
                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                    @click="clearFilterGlobal"
                                >
                                    <IconCircleXFilled size="16" />
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row gap-3 md:gap-2 w-full md:w-auto">
                            <Button variant="primary-outlined" @click="filteredValue?.length > 0 ? exportXLSX() : null" class="w-full">
                                <IconDownload size="20" stroke-width="1.25" />
                                {{ $t('public.export') }}
                            </Button>
                            <Button
                                type="button"
                                variant="error-outlined"
                                size="base"
                                class='w-full'
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
                        :title="$t('public.empty_rebate_payout_record_title')" 
                        :message="$t('public.empty_rebate_payout_record_message')" 
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="transactions?.length > 0 && filteredValue?.length > 0">
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
                    <Column field="execute_at" :header="$t('public.date')" sortable class="hidden md:table-cell w-full md:w-[20%] max-w-0">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm truncate max-w-full">
                                {{ dayjs(slotProps.data.execute_at).format('YYYY/MM/DD') }}
                            </div>
                        </template>
                    </Column>
                    <Column field="account_type" :header="$t('public.account_type')" class="hidden md:table-cell w-[20%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ $t('public.' + slotProps.data.account_type) }}
                            </div>
                        </template>
                    </Column>
                    <Column field="volume" :header="`${$t('public.volume')}&nbsp;(Ł)`" sortable class="hidden md:table-cell w-[20%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ formatAmount(slotProps.data?.volume || 0) }}
                            </div>
                        </template>
                    </Column>
                    <Column field="rebate" :header="`${$t('public.amount')}&nbsp;($)`" sortable class="w-1/2 md:w-[20%] px-3">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ formatAmount(slotProps.data?.rebate || 0) }}
                            </div>
                        </template>
                    </Column>
                    <ColumnGroup type="footer">
                        <Row>
                            <Column class="hidden md:table-cell" :footer="$t('public.total') + '&nbsp;($)&nbsp;:'" :colspan="4" footerStyle="text-align:right" />
                            <Column class="hidden md:table-cell" :colspan="5" :footer="formatAmount(totalAmount ? totalAmount : 0)" />
                            <Column class="md:hidden" :footer="$t('public.total') + '&nbsp;($)&nbsp;:'" :colspan="1" footerStyle="text-align:right" />
                            <Column class="md:hidden" :colspan="2" :footer="formatAmount(totalAmount ? totalAmount : 0)" />
                        </Row>
                    </ColumnGroup>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>

    <Dialog v-model:visible="visible" modal :header="$t('public.rebate_payout_details')" class="dialog-xs md:dialog-md" :dismissableMask="true">
        <div class="flex flex-col justify-center items-center gap-3 self-stretch pt-4 md:pt-6">
            <div class="flex flex-col justify-between items-center p-3 gap-3 self-stretch bg-gray-50 md:flex-row">
                <div class="flex flex-col items-start w-full truncate">
                    <span class="w-full truncate text-gray-950 font-semibold">{{ data.name }}</span>
                    <span class="w-full truncate text-gray-500 text-sm">{{ data.email }}</span>
                </div>
                <div class="flex items-center self-stretch">
                    <span class="w-full truncate text-gray-950 text-lg font-semibold">{{ `$&nbsp;${formatAmount(data?.rebate || 0)}` }}</span>
                </div>
            </div>
            
            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ dayjs(data.execute_at).format('YYYY/MM/DD') }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.account_type') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ $t('public.' + data.account_type) }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.total_volume') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ formatAmount(data?.volume || 0) }}&nbsp;Ł</span>
                </div>
            </div>

            <div class="w-full flex flex-col justify-center items-center">
                <!-- below md -->
                <div class="md:hidden flex flex-col items-center self-stretch" v-for="(product, index) in data.details" :key="index" :class="{'border-b border-gray-100': index !== data.details.length - 1}">
                    <div class="flex justify-between items-center py-2 self-stretch">
                        <div class="w-full truncate flex flex-col items-start gap-1">
                            <span class="w-full truncate text-gray-950 font-semibold" style="text-transform: capitalize;" >{{ $t('public.' + product.name) }}</span>
                            <div class="w-full truncate flex items-center gap-1 self-stretch text-xs">
                                <span class="truncate text-gray-700">{{ formatAmount(product?.volume || 0) }}</span>
                                <span class="truncate text-gray-500">|</span>
                                <span class="truncate text-gray-700">{{ formatAmount(product?.net_rebate || 0) }}</span>
                            </div>
                        </div>
                        <span class="w-[100px] truncate text-gray-950 text-right font-semibold">$&nbsp;{{ formatAmount(product?.rebate || 0) }}</span>
                    </div>
                </div>

                <!-- above md -->
                <div class="w-full hidden md:grid grid-cols-4 py-3 items-center border-b border-gray-100 bg-gray-50 uppercase text-gray-700 text-xs font-bold">
                    <div class="flex items-center px-3">
                        {{ $t('public.product') }}
                    </div>
                    <div class="flex items-center px-3">
                        {{ $t('public.volume') }} (Ł)
                    </div>
                    <div class="flex items-center px-3">
                        {{ $t('public.rebate') }} / Ł ($)
                    </div>
                    <div class="flex items-center px-3">
                        {{ $t('public.total') }} ($)
                    </div>
                </div>

                <div v-for="(product, index) in data.details" :key="index" class="w-full hidden md:grid grid-cols-4 py-2 items-center hover:bg-gray-50" :class="{'border-b border-gray-100': index !== data.details.length - 1}">
                    <div class="flex items-center px-3">
                        <span class="text-gray-950 text-sm" style="text-transform: capitalize;" >{{ $t('public.' + product.name) }}</span>
                    </div>
                    <div class="flex items-center px-3">
                        <span class="text-gray-950 text-sm">{{ formatAmount(product?.volume || 0) }}</span>
                    </div>
                    <div class="flex items-center px-3">
                        <span class="text-gray-950 text-sm">{{ formatAmount(product?.net_rebate || 0) }}</span>
                    </div>
                    <div class="flex items-center px-3">
                        <span class="text-gray-950 text-sm">{{ formatAmount(product?.rebate || 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>

</template>