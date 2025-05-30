<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePage } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff, IconCopy } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted, onUnmounted } from "vue";
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
import Tag from 'primevue/tag';

const { formatAmount } = transactionFormat();

const visible = ref(false);
const loading = ref(false);
const dt = ref(null);
const transactions = ref();
const totalAmount = ref();
const type = ref('transfer');
const filteredValue = ref();

// Define the transfer type options
const transferTypeOption = [
    { name: wTrans('public.rebate_to_account'), value: 'transfer_to_account' },
    { name: wTrans('public.account_to_account'), value: 'account_to_account' }
];

const months = ref([]);
const selectedMonth = ref('');

const getCurrentMonthYear = () => {
    const date = new Date();
    return `01 ${dayjs(date).format('MMMM YYYY')}`;
};

// Fetch settlement months from API
const getTransactionMonths = async () => {
    try {
        const response = await axios.get('/getTransactionMonths');
        months.value = response.data.months;

        if (months.value.length) {
            selectedMonth.value = getCurrentMonthYear();
        }
    } catch (error) {
        console.error('Error transaction months:', error);
    }
};

getTransactionMonths()

// Watch for changes in selectedMonths
watch(selectedMonth, (newMonth) => {
    getResults(newMonth);
    // console.log('Selected Months:', newMonths);
});

const getResults = async (selectedMonth = '', from = null) => {
    loading.value = true;

    try {
        // Create the base URL with the type parameter directly in the URL
        let url = `/transaction/getTransactionData?type=${type.value}`;

        // Convert the array to a comma-separated string if not empty
        if (selectedMonth) {
            let formattedMonth = selectedMonth;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            url += `&selectedMonth=${formattedMonth}`;
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

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    transaction_type: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const recalculateTotals = () => {
    const globalFilterValue = filters.value.global?.value?.toLowerCase();

    const filtered = transactions.value.filter(transaction => {
        const matchesGlobalFilter = globalFilterValue 
            ? [
                transaction.name,
                transaction.email,
                transaction.transaction_number,
                transaction.from_meta_login,
                transaction.from_wallet_name,
                transaction.to_meta_login,
                transaction.to_wallet_name,
            ].some(field => {
                // Convert field to string and check if it includes the global filter value
                const fieldValue = field !== undefined && field !== null ? field.toString() : '';
                return fieldValue.toLowerCase().includes(globalFilterValue);
            }) 
            : true; // If no global filter is set, match all

        // Apply individual field filters (name, email, status)
        const matchesNameFilter = !filters.value.name?.value || transaction.name.startsWith(filters.value.name.value);
        const matchesEmailFilter = !filters.value.email?.value || transaction.email.startsWith(filters.value.email.value);
        const matchesTransactionTypeFilter = !filters.value.transaction_type?.value || transaction.transaction_type.startsWith(filters.value.transaction_type.value);

        // Only return transactions that match both global and specific filters
        return matchesGlobalFilter && matchesNameFilter && matchesEmailFilter && matchesTransactionTypeFilter;
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
        transaction_type: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
    selectedMonth.value = getCurrentMonthYear();
    filteredValue.value = null; 
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults(selectedMonth.value);
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
        trans('public.id'),
        trans('public.from'),
        trans('public.to'),
        trans('public.amount') + ' ($)'
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        const fromDisplay = obj.from_meta_login ? obj.from_meta_login : (obj.from_wallet_name ? trans('public.' + obj.from_wallet_name) : '');
        const toDisplay = obj.to_meta_login ? obj.to_meta_login : (obj.to_wallet_name ? trans('public.' + obj.to_wallet_name) : '');

        return [
            obj.name !== undefined ? obj.name : '',
            obj.email !== undefined ? obj.email : '',
            obj.created_at !== undefined ? dayjs(obj.created_at).format('YYYY/MM/DD') : '',
            obj.transaction_number !== undefined ? obj.transaction_number : '',
            fromDisplay,
            toDisplay,
            obj.transaction_amount !== undefined ? obj.transaction_amount : ''
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

const activeTag = ref(null);
const tooltipText = ref('copy');
const copyToClipboard = (addressType, text) => {
    const textToCopy = text;

    const textArea = document.createElement('textarea');
    document.body.appendChild(textArea);

    textArea.value = textToCopy;
    textArea.select();

    try {
        const successful = document.execCommand('copy');

        tooltipText.value = 'copied';
        activeTag.value = addressType;
        setTimeout(() => {
            tooltipText.value = 'copy';
            activeTag.value = null;
        }, 1500);
    } catch (err) {
        console.error('Copy to clipboard failed:', err);
    }

    document.body.removeChild(textArea);
}

const pageLinkSize = ref(window.innerWidth < 768 ? 3 : 5)

const updatePageLinkSize = () => {
  pageLinkSize.value = window.innerWidth < 768 ? 3 : 5
}

onMounted(() => {
  window.addEventListener('resize', updatePageLinkSize)
})

onUnmounted(() => {
  window.removeEventListener('resize', updatePageLinkSize)
})
</script>

<template>
    <AuthenticatedLayout :title="`${$t('public.transactions')}&nbsp;-&nbsp;${$t('public.sidebar_transfer')}`">
        <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="flex flex-col pb-3 gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between md:pb-0">
                <span class="text-gray-950 font-semibold self-stretch md:self-auto">{{ $t('public.all_transfer') }}</span>
            </div>
            <div v-if="months.length === 0" class="flex flex-col gap-2 items-center justify-center">
                <Loader />
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>

            <DataTable
                v-else
                v-model:filters="filters"
                :value="transactions"
                :paginator="transactions?.length > 0 && filteredValue?.length > 0"
                removableSort
                :rows="100"
                :pageLinkSize="pageLinkSize"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :globalFilterFields="['name', 'email', 'transaction_number', 'from_meta_login', 'from_wallet_name', 'to_meta_login', 'to_wallet_name']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @filter="handleFilter"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                        <div class="grid grid-cols-2 md:grid-cols-3 items-center gap-3 self-stretch md:flex-row md:gap-2">
                            <Select 
                                v-model="selectedMonth" 
                                :options="months" 
                                :placeholder="$t('public.month_placeholder')"
                                class="col-span-2 md:col-span-1 font-normal truncate" scroll-height="236px" 
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
                            <div class="relative col-span-2 block md:hidden">
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
                            <Select
                                v-model="filters['transaction_type'].value"
                                :options="transferTypeOption"
                                optionLabel="name"
                                optionValue="value"
                                :placeholder="$t('public.filter_by_transfer_type')"
                                class="col-span-2 md:col-span-1 font-normal"
                                scroll-height="236px"
                            />
                            <div class="relative hidden md:block md:col-span-1">
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
                        <div class="grid grid-cols-2 md:flex md:flex-row gap-3 md:gap-2 w-full md:w-auto shrink-0">
                            <Button variant="primary-outlined" @click="filteredValue?.length > 0 ? exportXLSX() : null" class="col-span-1 md:w-auto">
                                <IconDownload size="20" stroke-width="1.25" />
                                {{ $t('public.export') }}
                            </Button>
                            <Button
                                type="button"
                                variant="error-outlined"
                                size="base"
                                class='col-span-1 md:w-auto'
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
                        :title="$t('public.empty_transfer_record_title')" 
                        :message="$t('public.empty_transfer_record_message')" 
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
                                <div class="text-gray-500 text-xs truncate max-w-full hidden md:flex">
                                    {{ slotProps.data.email }}
                                </div>
                                <div class="text-gray-500 text-xs truncate max-w-full md:hidden flex">
                                    {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD HH:mm:ss') }}
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
                    <Column field="transaction_number" :header="$t('public.id')" sortable class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data?.transaction_number || '-' }}
                            </div>
                        </template>
                    </Column>
                    <Column field="from" :header="$t('public.from')" class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data?.from_meta_login ? slotProps.data?.from_meta_login : (slotProps.data?.from_wallet_name ? $t('public.' + slotProps.data?.from_wallet_name) : '-') }}
                            </div>
                        </template>
                    </Column>
                    <Column field="to" :header="$t('public.to')" class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data?.to_meta_login ? slotProps.data?.to_meta_login : (slotProps.data?.to_wallet_name ? $t('public.' + slotProps.data?.to_wallet_name) : '-') }}
                            </div>
                        </template>
                    </Column>
                    <Column field="transaction_amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable class="w-1/2 md:w-[15%] px-3">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ formatAmount(slotProps.data.transaction_amount || 0) }}
                            </div>
                        </template>
                    </Column>
                    <ColumnGroup type="footer">
                        <Row>
                            <Column class="hidden md:table-cell" :footer="$t('public.total') + '&nbsp;($)&nbsp;:'" :colspan="5" footerStyle="text-align:right" />
                            <Column class="hidden md:table-cell" :colspan="6" :footer="formatAmount(totalAmount ? totalAmount : 0)" />
                            <Column class="md:hidden" :footer="$t('public.total') + '&nbsp;($)&nbsp;:'" :colspan="1" footerStyle="text-align:right" />
                            <Column class="md:hidden" :colspan="2" :footer="formatAmount(totalAmount ? totalAmount : 0)" />
                        </Row>
                    </ColumnGroup>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>

    <Dialog v-model:visible="visible" modal :header="$t('public.transfer_details')" class="dialog-xs md:dialog-md" :dismissableMask="true">
        <div class="flex flex-col justify-center items-center gap-3 self-stretch pt-4 md:pt-6">
            <div class="flex flex-col justify-between items-center p-3 gap-3 self-stretch bg-gray-50 md:flex-row">
                <div class="flex items-center self-stretch">
                    <span class="flex gap-1 items-center text-gray-950 font-semibold relative">
                        {{ data?.name || '-' }}
                        <IconCopy 
                            v-if="data?.name"
                            size="20" 
                            stroke-width="1.25" 
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0" 
                            v-tooltip.top="$t(`public.${tooltipText}`)" 
                            @click="copyToClipboard('name', data.name)"
                        />
                        <Tag
                            v-if="activeTag === 'name' && tooltipText === 'copied'"
                            class="absolute -top-7 -right-3"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>

                </div>
                <div class="flex items-center self-stretch">
                    <span class="flex gap-1 items-center text-gray-950 text-lg font-semibold relative">
                        {{ `$&nbsp;${formatAmount(data?.transaction_amount || 0)}` }}
                        <IconCopy 
                            v-if="data?.transaction_amount"
                            size="20" 
                            stroke-width="1.25" 
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0" 
                            v-tooltip.top="$t(`public.${tooltipText}`)" 
                            @click="copyToClipboard('transaction_amount', data.transaction_amount)"
                        />
                        <Tag
                            v-if="activeTag === 'transaction_amount' && tooltipText === 'copied'"
                            class="absolute -top-7 -right-3"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>
                </div>
            </div>
            
            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ dayjs(data.created_at).format('YYYY/MM/DD HH:mm:ss') }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.email') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.email }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.transaction_id') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.transaction_number || '-' }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.from') }}</span>
                    <span class="flex gap-1 break-all text-gray-950 text-sm font-medium relative">
                        {{ data?.from_meta_login ? data?.from_meta_login : (data?.from_wallet_name ? $t('public.' + data?.from_wallet_name) : '-') }}
                        <IconCopy
                            v-if="data?.from_meta_login || data?.from_wallet_name"
                            size="20"
                            stroke-width="1.25"
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0"
                            v-tooltip.top="$t(`public.${tooltipText}`)"
                            @click="copyToClipboard(
                                data?.from_meta_login ? 'from_meta_login' : 'from_wallet_name',
                                data?.from_meta_login || $t('public.' + data?.from_wallet_name)
                            )"
                        />
                        <Tag
                            v-if="activeTag === (data?.from_meta_login ? 'from_meta_login' : 'from_wallet_name') && tooltipText === 'copied'"
                            class="absolute -top-7 -right-3"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.to') }}</span>
                    <span class="flex gap-1 break-all text-gray-950 text-sm font-medium relative">
                        {{ data?.to_meta_login ? data?.to_meta_login : (data?.to_wallet_name ? $t('public.' + data?.to_wallet_name) : '-') }}
                        <IconCopy
                            v-if="data?.to_meta_login || data?.to_wallet_name"
                            size="20"
                            stroke-width="1.25"
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0"
                            v-tooltip.top="$t(`public.${tooltipText}`)"
                            @click="copyToClipboard(
                                data?.to_meta_login ? 'from_meta_login' : 'to_wallet_name',
                                data?.to_meta_login || $t('public.' + data?.to_wallet_name)
                            )"
                        />
                        <Tag
                            v-if="activeTag === (data?.to_meta_login ? 'to_meta_login' : 'to_wallet_name') && tooltipText === 'copied'"
                            class="absolute -top-7 -right-3"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>
                </div>
            </div>
        </div>
    </Dialog>

</template>