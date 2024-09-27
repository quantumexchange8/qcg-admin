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
const { formatAmount } = transactionFormat();

const visible = ref(false);
const loading = ref(false);
const dt = ref(null);
const transactions = ref();
// Dummy data for transactions
transactions.value = [
    {
        name: "John Doe",
        email: "john@example.com",
        created_at: dayjs().subtract(1, 'day').toString(),
        id_number: "ID12345",
        meta_login: "MetaLogin001",
        transaction_amount: 1500.00,
        status: "successful",
        deleted_at: dayjs().subtract(1, 'day').toString(),
        from_wallet_address: 'test1from',
        to_wallet_address: 'test1to',
        remarks: 'test1'
    },
    {
        name: "Jane Smith",
        email: "jane@example.com",
        created_at: dayjs().subtract(3, 'days').toString(),
        id_number: "ID67890",
        meta_login: "MetaLogin002",
        transaction_amount: 2500.50,
        status: "processing",
        deleted_at: dayjs().subtract(3, 'days').toString(),
        from_wallet_address: 'test2from',
        to_wallet_address: 'test2to',
        remarks: 'test2'
    },
    {
        name: "Michael Johnson",
        email: "michael@example.com",
        created_at: dayjs().subtract(7, 'days').toString(),
        id_number: "ID11111",
        meta_login: "MetaLogin003",
        transaction_amount: 3200.75,
        status: "failed",
        deleted_at: dayjs().subtract(7, 'days').toString(),
        from_wallet_address: 'test3from',
        to_wallet_address: 'test3to',
        remarks: 'test3'
    }
];

const transferType = ref();
const filteredValueCount = ref(0);

// Define the transfer type options
const transferTypeOption = [
    { name: wTrans('public.rebate_to_account'), value: 'rebate_to_account' },
    { name: wTrans('public.account_to_account'), value: 'account_to_account' }
];

// const getResults = async () => {
//     loading.value = true;

//     try {
//         const response = await axios.get('/member/getAccountListingData?type=all');
//         transactions.value = response.data.transactions;

//     } catch (error) {
//     console.error('Error In Fetch Data:', error);
//         } finally {
//         loading.value = false;
//     }

// };

// getResults();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };

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
    <AuthenticatedLayout :title="`${$t('public.transactions')}&nbsp;-&nbsp;${$t('public.sidebar_transfer')}`">
        <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="flex flex-col pb-3 gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between md:pb-0">
                <span class="text-gray-950 font-semibold self-stretch">{{ $t('public.all_transfer') }}</span>
                <div class="flex flex-col gap-3 items-center self-stretch md:flex-row md:gap-5">
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
                    <Button variant="primary-outlined" @click="exportCSV" class="w-full md:w-auto">
                        <IconDownload size="20" stroke-width="1.25" />
                        {{ $t('public.export') }}
                    </Button>
                </div>
            </div>
            <DataTable
                v-model:filters="filters"
                :value="transactions"
                :paginator="transactions?.length > 0 && filteredValueCount > 0"
                removableSort
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :globalFilterFields="['name', 'email', 'transaction_amount']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @filter="handleFilter"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                        <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                            <!-- <Select
                                v-model="accountType"
                                :options="accountTypes"
                                filter
                                :filterFields="['name']"
                                optionLabel="name"
                                optionValue="value"
                                :placeholder="$t('public.filter_by_account_type')"
                                class="w-full md:w-60"
                                scroll-height="236px"
                            /> -->
                            <Select
                                v-model="transferType"
                                :options="transferTypeOption"
                                optionLabel="name"
                                optionValue="value"
                                :placeholder="$t('public.filter_by_transfer_type')"
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
                        :title="$t('public.empty_transfer_record_title')" 
                        :message="$t('public.empty_transfer_record_message')" 
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading_transactions_caption') }}</span>
                    </div>
                </template>
                <template v-if="transactions?.length > 0 && filteredValueCount > 0">
                    <Column field="name" sortable :header="$t('public.name')" class="w-1/2 md:w-[20%] max-w-0">
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
                                {{ slotProps.data.meta_login }}
                            </div>
                        </template>
                    </Column>
                    <Column field="to" :header="$t('public.to')" class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data.meta_login }}
                            </div>
                        </template>
                    </Column>
                    <Column field="transaction_amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable class="w-1/2 md:w-[15%]">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ formatAmount(slotProps.data.transaction_amount) }}
                            </div>
                        </template>
                    </Column>
                    <ColumnGroup type="footer">
                        <Row>
                            <Column class="hidden md:table-cell" :footer="$t('public.total') + '&nbsp;($)&nbsp;:'" :colspan="5" footerStyle="text-align:right" />
                            <Column class="hidden md:table-cell" :colspan="6" :footer="formatAmount(0)" />
                            <Column class="md:hidden" :footer="$t('public.total') + '&nbsp;($)&nbsp;:'" :colspan="1" footerStyle="text-align:right" />
                            <Column class="md:hidden" :colspan="2" :footer="formatAmount(0)" />
                        </Row>
                    </ColumnGroup>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>

    <Dialog v-model:visible="visible" modal :header="$t('public.transfer_details')" class="dialog-xs md:dialog-md">
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
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.from_meta_login }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.to') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ data.to_meta_login }}</span>
                </div>
            </div>
        </div>
    </Dialog>

</template>