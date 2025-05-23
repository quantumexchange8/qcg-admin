<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePage } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff, IconCopy, IconCircleCheckFilled, IconExclamationCircleFilled, IconClockFilled } from "@tabler/icons-vue";
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
import { transactionFormat, generalFormat } from "@/Composables/index.js";
import dayjs from "dayjs";
import { trans, wTrans } from "laravel-vue-i18n";
import StatusBadge from '@/Components/StatusBadge.vue';
import MultiSelect from "primevue/multiselect";
import Tag from 'primevue/tag';

const { formatAmount } = transactionFormat();
const { formatRgbaColor } = generalFormat();

const props = defineProps({
    teams: Array,
});

const visible = ref(false);
const loading = ref(false);
const dt = ref(null);
const transactions = ref();
const totalAmount = ref();
const type = ref('deposit');
const selectedTeams = ref([]);
const teams = ref(props.teams);
const filteredValue = ref();

// Define the status options
const statusOption = [
    { name: wTrans('public.all'), value: null },
    { name: wTrans('public.successful'), value: 'successful' },
    { name: wTrans('public.processing'), value: 'processing' },
    { name: wTrans('public.failed'), value: 'failed' }
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

// Watchers for selectedMonths
watch(selectedMonth, (newMonth) => {
    getResults(newMonth, selectedTeams.value);
});

const getResults = async (selectedMonth = '', selectedTeams = []) => {
    loading.value = true;

    try {
        // Create the base URL with the type parameter directly in the URL
        let url = `/transaction/getTransactionData?type=${type.value}`;

        if (selectedMonth) {
            let formattedMonth = selectedMonth;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            url += `&selectedMonth=${formattedMonth}`;
        }

        if (selectedTeams && selectedTeams.length > 0) {
            const selectedTeamValues = selectedTeams.map((team) => team.value);
            url += `&selectedTeams=${selectedTeamValues.join(',')}`;
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

watch(selectedTeams, (newTeams) => {
    getResults(selectedMonth.value, newTeams);
    // console.log(newTeams)
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: 'successful', matchMode: FilterMatchMode.EQUALS },
});

const recalculateTotals = () => {
    const globalFilterValue = filters.value.global?.value?.toLowerCase();

    const filtered = transactions.value.filter(transaction => {
        const matchesGlobalFilter = globalFilterValue
            ? [
                transaction.name,
                transaction.email,
                transaction.transaction_number,
                transaction.to_meta_login,
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
        status: { value: 'successful', matchMode: FilterMatchMode.EQUALS },
        team_id: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
    selectedMonth.value = getCurrentMonthYear();
    selectedTeams.value = [];
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
        trans('public.sales_team'),
        trans('public.id'),
        trans('public.account'),
        trans('public.amount') + ' ($)',
        trans('public.status'),
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        const toDisplay = obj.to_meta_login ? obj.to_meta_login : (obj.to_wallet_name ? trans('public.' + obj.to_wallet_name) : '');

        return [
            obj.name !== undefined ? obj.name : '',
            obj.email !== undefined ? obj.email : '',
            obj.created_at !== undefined ? dayjs(obj.created_at).format('YYYY/MM/DD') : '',
            obj.team_name !== undefined ? obj.team_name : '',
            obj.transaction_number !== undefined ? obj.transaction_number : '',
            toDisplay,
            obj.transaction_amount !== undefined ? obj.transaction_amount : '',
            obj.status !== undefined ? trans('public.' + obj.status) : ''
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

</script>

<template>
    <AuthenticatedLayout :title="`${$t('public.transactions')}&nbsp;-&nbsp;${$t('public.sidebar_deposit')}`">
        <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="flex flex-col pb-3 gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between md:pb-0">
                <span class="text-gray-950 font-semibold self-stretch md:self-auto">{{ $t('public.all_deposit') }}</span>
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
                :rows="20"
                :rowsPerPageOptions="[20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :globalFilterFields="['name', 'email', 'transaction_number', 'to_meta_login']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @filter="handleFilter"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-3 md:gap-2 self-stretch">
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
                                v-model="filters['status'].value"
                                :options="statusOption"
                                optionLabel="name"
                                optionValue="value"
                                :placeholder="$t('public.filter_by_status')"
                                class="w-full md:max-w-60 font-normal"
                                scroll-height="236px"
                            >
                                <template #value="data">
                                    <span class="font-normal text-gray-950" >{{ $t('public.' + (data.value || 'all')) }}</span>
                                </template>
                            </Select>
                            <MultiSelect
                                v-model="selectedTeams"
                                :options="teams"
                                :placeholder="$t('public.filter_by_sales_team')"
                                :maxSelectedLabels="1"
                                :selectedItemsLabel="`${selectedTeams.length} ${$t('public.teams_selected')}`"
                                class="w-full md:max-w-60 h-12 font-normal"
                            >
                                <template #header>
                                    <div class="absolute flex left-10 top-2">
                                        {{ $t('public.select_all') }}
                                    </div>
                                </template>
                                <template #option="{option}">
                                    <div class="flex items-center gap-2">
                                        <div class="w-4 h-4 rounded-full overflow-hidden grow-0 shrink-0" :style="{ backgroundColor: `#${option.color}` }"></div>
                                        <div>{{ option.name }}</div>
                                    </div>
                                </template>
                                <template #value>
                                    <div v-if="selectedTeams.length === 1" class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-4 h-4 rounded-full overflow-hidden grow-0 shrink-0" :style="{ backgroundColor: `#${selectedTeams[0].color}` }"></div>
                                            <div>{{ selectedTeams[0].name }}</div>
                                        </div>
                                    </div>
                                    <span v-else-if="selectedTeams.length > 1">
                                        {{ selectedTeams.length }} {{ $t('public.teams_selected') }}
                                    </span>
                                    <span v-else class="text-gray-400">
                                        {{ $t('public.filter_by_sales_team') }}
                                    </span>
                                </template>
                            </MultiSelect>
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
                        <div class="flex flex-col md:flex-row gap-3 md:gap-2 w-full md:w-auto shrink-0">
                            <Button variant="primary-outlined" @click="filteredValue?.length > 0 ? exportXLSX() : null" class="w-full md:w-auto">
                                <IconDownload size="20" stroke-width="1.25" />
                                {{ $t('public.export') }}
                            </Button>
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
                        :title="$t('public.empty_deposit_record_title')"
                        :message="$t('public.empty_deposit_record_message')"
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="transactions?.length > 0 && filteredValue?.length > 0">
                    <Column field="name" sortable :header="$t('public.name')" class="w-2/3 md:w-[20%] max-w-0 px-3">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start max-w-full">
                                <div class="flex max-w-full gap-2 items-center">
                                    <div class="font-semibold truncate max-w-full">
                                        {{ slotProps.data.name }}
                                    </div>
                                    <IconCircleCheckFilled v-if="slotProps.data.kyc_status === 'verified'" size="16" stroke-width="1.25" class="text-success-500 grow-0 shrink-0" />
                                    <IconClockFilled v-else-if="slotProps.data.kyc_status === 'pending'" size="16" stroke-width="1.25" class="text-warning-500 grow-0 shrink-0" />
                                    <IconExclamationCircleFilled v-else size="16" stroke-width="1.25" class="text-error-500 grow-0 shrink-0" />
                                    <div
                                        v-if="slotProps.data.team_id"
                                        class="flex justify-center items-center gap-2 rounded-sm py-1 px-2 md:hidden"
                                        :style="{ backgroundColor: formatRgbaColor(slotProps.data.team_color, 1) }"
                                    >
                                        <div
                                            class="text-white text-xs text-center"
                                        >
                                            {{ slotProps.data.team_name }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-gray-500 text-xs truncate max-w-full hidden md:flex">
                                    {{ slotProps.data.email }}
                                </div>
                                <div class="text-gray-500 text-xs truncate max-w-full md:hidden flex">
                                    <span v-if="slotProps.data.status === 'processing'">{{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD HH:mm:ss') }}</span>
                                    <span v-else-if="slotProps.data.approved_at">{{ dayjs(slotProps.data.approved_at).format('YYYY/MM/DD HH:mm:ss') }}</span>
                                    <span v-else>-</span>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="approved_at" :header="$t('public.date')" sortable class="hidden md:table-cell w-full md:w-[20%] max-w-0">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm truncate max-w-full">
                                <span v-if="slotProps.data.status === 'processing'">{{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}</span>
                                <span v-else-if="slotProps.data.approved_at">{{ dayjs(slotProps.data.approved_at).format('YYYY/MM/DD') }}</span>
                                <span v-else>-</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="team" :header="$t('public.sales_team')" style="width: 15%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <div
                                    v-if="slotProps.data.team_id"
                                    class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                                    :style="{ backgroundColor: formatRgbaColor(slotProps.data.team_color, 1) }"
                                >
                                    <div
                                        class="text-white text-xs text-center"
                                    >
                                        {{ slotProps.data.team_name }}
                                    </div>
                                </div>
                                <div v-else>
                                    -
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="to_meta_login" :header="$t('public.account')" class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data?.to_meta_login || '-' }}
                            </div>
                        </template>
                    </Column>
                    <Column field="transaction_amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable class="w-1/3 md:w-[15%] px-3">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ formatAmount(slotProps.data?.transaction_amount || 0) }}
                            </div>
                        </template>
                    </Column>
                    <Column field="status" :header="$t('public.status')" class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <StatusBadge :variant="slotProps.data.status" :value="$t('public.' + slotProps.data.status)" />
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

    <Dialog v-model:visible="visible" modal :header="$t('public.deposit_details')" class="dialog-xs md:dialog-md" :dismissableMask="true">
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
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.requested_date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ dayjs(data.created_at).format('YYYY/MM/DD HH:mm:ss') }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.approved_date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.approved_at ? dayjs(data.approved_at).format('YYYY/MM/DD HH:mm:ss') : '-' }}</span>
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
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.account') }}</span>
                    <span class="flex gap-1 break-all text-gray-950 text-sm font-medium relative">
                        {{ data?.to_meta_login || '-' }}
                        <IconCopy
                            v-if="data?.to_meta_login"
                            size="20"
                            stroke-width="1.25"
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0"
                            v-tooltip.top="$t(`public.${tooltipText}`)"
                            @click="copyToClipboard('to_meta_login', data.to_meta_login)"
                        />
                        <Tag
                            v-if="activeTag === 'to_meta_login' && tooltipText === 'copied'"
                            class="absolute -top-7 -right-3"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.sales_team') }}</span>
                    <div class="flex items-center">
                        <div
                            v-if="data.team_id"
                            class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                            :style="{ backgroundColor: formatRgbaColor(data.team_color, 1) }"
                        >
                            <div
                                class="text-white text-xs text-center"
                            >
                                {{ data.team_name }}
                            </div>
                        </div>
                        <div v-else>
                            -
                        </div>
                    </div>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.status') }}</span>
                    <StatusBadge :variant="data.status" :value="$t('public.' + data.status)"/>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.sent_address') }}</span>
                    <span class="flex gap-1 break-all text-gray-950 text-sm font-medium relative">
                        {{ data?.from_wallet_address || '-' }}
                        <IconCopy
                            v-if="data?.from_wallet_address"
                            size="20"
                            stroke-width="1.25"
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0"
                            v-tooltip.top="$t(`public.${tooltipText}`)"
                            @click="copyToClipboard('from_wallet_address', data.from_wallet_address)"
                        />
                        <Tag
                            v-if="activeTag === 'from_wallet_address' && tooltipText === 'copied'"
                            class="absolute -top-7 -right-3"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.receiving_address') }}</span>
                    <span class="flex gap-1 break-all text-gray-950 text-sm font-medium relative">
                        {{ data?.to_wallet_address || '-' }}
                        <IconCopy
                            v-if="data?.to_wallet_address"
                            size="20"
                            stroke-width="1.25"
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0"
                            v-tooltip.top="$t(`public.${tooltipText}`)"
                            @click="copyToClipboard('to_wallet_address', data.to_wallet_address)"
                        />
                        <Tag
                            v-if="activeTag === 'to_wallet_address' && tooltipText === 'copied'"
                            class="absolute -top-7 -right-3"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.remarks') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data?.remarks || '-' }}</span>
                </div>
            </div>
        </div>
    </Dialog>

</template>
