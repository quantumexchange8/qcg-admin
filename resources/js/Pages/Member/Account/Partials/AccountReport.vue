<script setup>
import { ref, watch } from 'vue';
import Datepicker from 'primevue/datepicker';
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { transactionFormat } from '@/Composables/index.js';
import Empty from '@/Components/Empty.vue';
import Loader from "@/Components/Loader.vue";
import { IconX } from '@tabler/icons-vue';
import Dialog from 'primevue/dialog';
import StatusBadge from '@/Components/StatusBadge.vue';
import { wTrans } from "laravel-vue-i18n";
import Tag from 'primevue/tag';
import dayjs from 'dayjs'

const { formatDate, formatDateTime, formatAmount } = transactionFormat();

const props = defineProps({
    account: Object,
});

const transactions = ref(null);
const selectedOption = ref('all');
const loading = ref(false);
const visible = ref(false);
const data = ref({});
const tooltipText = ref('copy')

const transferOptions = [
  { name: wTrans('public.all'), value: 'all' },
  { name: wTrans('public.deposit'), value: 'deposit' },
  { name: wTrans('public.withdrawal'), value: 'withdrawal' },
  { name: wTrans('public.transfer'), value: 'transfer' }
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
        months.value = ['select_all', ...response.data.months];

        if (months.value.length) {
            selectedMonth.value = [getCurrentMonthYear()];
        }
    } catch (error) {
        console.error('Error transaction months:', error);
    }
};

getTransactionMonths()

// Watchers for selectedMonths
watch(selectedMonth, (newMonths) => {
    getAccountReport(newMonths, selectedOption.value);
});

const getAccountReport = async (selectedMonth = '', selectedOption = null) => {
    if (loading.value) return;
    loading.value = true;

    try {
        let url = `/member/getAccountReport?meta_login=${props.account.meta_login}`;

        if (selectedMonth) {
            const formattedMonth = selectedMonth === 'select_all' 
                ? 'select_all' 
                : dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');

            url += `&selectedMonth=${formattedMonth}`;
        }

        if (selectedOption) {
            url += `&type=${selectedOption}`;
        }

        const response = await axios.get(url);
        
        transactions.value = response.data;
    } catch (error) {
        console.error('Error fetching account report:', error);
    } finally {
        loading.value = false;
    }
};

watch(selectedOption, (newOption) => {
    getAccountReport(selectedMonth.value, newOption);
});

const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};

function copyToClipboard(text) {
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
    <div class="pt-6">
        <DataTable
            :value="transactions"
            paginator
            removableSort
            selectionMode="single"
            :rows="20"
            :rowsPerPageOptions="[20, 50, 100]"
            tableStyle="md:min-width: 50rem"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
            @row-click="(event) => openDialog(event.data)"
            :loading="loading"
        >
            <template #header>
                <div class="grid grid-cols-1 md:grid-cols-2 w-full gap-3 mb-8">
                    <Select 
                        v-model="selectedMonth" 
                        :options="months" 
                        :placeholder="$t('public.month_placeholder')"
                        class="w-full font-normal truncate" scroll-height="236px" 
                    >
                        <template #option="{option}">
                            <span class="text-sm">
                                <template v-if="option === 'select_all'">
                                    {{ $t('public.select_all') }}
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
                        v-model="selectedOption"
                        :options="transferOptions"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.transaction_type_option_placeholder')"
                        class="w-full font-normal"
                        scroll-height="236px"
                    />
                </div>
            </template>
            <template #empty><Empty :message="$t('public.no_record_message')"/></template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loader />
                    <span class="text-sm text-gray-700">{{ $t('public.loading_caption') }}</span>
                </div>
            </template>
            <Column
                field="created_at"
                sortable
                :header="$t('public.date')"
                class="hidden md:table-cell"
            >
                <template #body="slotProps">
                    {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                </template>
            </Column>
            <Column
                :header="$t('public.description')"
                class="hidden md:table-cell"
            >
                <template #body="slotProps">
                    <div v-if="['transfer_to_account', 'account_to_account'].includes(slotProps.data.transaction_type)">
                        <div v-if="account.meta_login === slotProps.data.to_meta_login">
                            {{ $t('public.from') }} {{ slotProps.data.from_meta_login ?? $t(`public.${slotProps.data.wallet_type}`) }}
                        </div>
                        <div v-else>
                            {{ $t('public.to') }} {{ slotProps.data.to_meta_login }}
                        </div>
                    </div>
                    <div v-else>{{ $t(`public.${slotProps.data.transaction_type}`) }}</div>
                </template>
            </Column>
            <Column
                field="transaction_amount"
                sortable
                :header="$t('public.amount') + ' ($)'"
                class="hidden md:table-cell"
            >
                <template #body="slotProps">
                    <div
                        :class="{
                                'text-success-500': slotProps.data.to_meta_login,
                                'text-error-500': slotProps.data.from_meta_login,
                            }"
                    >
                        {{ formatAmount(slotProps.data.transaction_amount > 0 ? slotProps.data.transaction_amount : 0) }}
                    </div>
                </template>
            </Column>
            <Column class="md:hidden">
                <template #body="slotProps">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col items-start gap-1 flex-grow">
                            <span class="overflow-hidden text-gray-950 text-ellipsis text-sm font-semibold">
                                {{ $t(`public.${slotProps.data.transaction_type}`) }}
                            </span>
                            <span class="text-gray-500 text-xs">
                                {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                            </span>
                        </div>
                        <div
                            class="overflow-hidden text-right text-ellipsis font-semibold"
                            :class="{
                                'text-success-500': slotProps.data.to_meta_login,
                                'text-error-500': slotProps.data.from_meta_login,
                            }"
                        >
                            {{ formatAmount(slotProps.data.transaction_amount > 0 ? slotProps.data.transaction_amount : 0) }}
                        </div>
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>


    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.details')"
        class="dialog-xs md:dialog-sm"
    >
        <div
            class="flex flex-col items-center gap-3 self-stretch pt-6"
            :class="{
                'pb-4 border-b border-gray-200': ['deposit', 'withdrawal', 'balance_in', 'balance_out', 'credit_in', 'credit_out', 'rebate_in', 'rebate_out'].includes(data.transaction_type)
            }"
        >
            <div class="flex items-center gap-1 self-stretch">
                <span class="w-[120px] text-gray-500 text-xs font-medium">{{ $t('public.date') }}</span>
                <span class="flex-grow text-gray-950 text-sm font-medium">{{ formatDateTime(data.created_at) }}</span>
            </div>
            <div class="flex items-center gap-1 self-stretch">
                <span class="w-[120px] text-gray-500 text-xs font-medium">{{ $t('public.transaction_id') }}</span>
                <span class="flex-grow text-gray-950 text-sm font-medium">{{ data.transaction_number }}</span>
            </div>
            <div class="flex items-center gap-1 self-stretch">
                <span class="w-[120px] text-gray-500 text-xs font-medium">{{ $t('public.account') }}</span>
                <span class="flex-grow text-gray-950 text-sm font-medium">{{ account.meta_login }}</span>
            </div>
            <div class="flex items-center gap-1 self-stretch">
                <span class="w-[120px] text-gray-500 text-xs font-medium">{{ $t('public.description') }}</span>
                <div class="flex-grow text-gray-950 text-sm font-medium">
                    <div v-if="['transfer_to_account', 'account_to_account'].includes(data.transaction_type)">
                        <div v-if="account.meta_login === data.to_meta_login">
                            {{ $t('public.from') }} {{ data.from_meta_login ?? $t(`public.${data.wallet_type}`) }}
                        </div>
                        <div v-else>
                            {{ $t('public.to') }} {{ data.to_meta_login }}
                        </div>
                    </div>
                    <div v-else>{{ $t(`public.${data.transaction_type}`) }}</div>
                </div>
            </div>
            <div class="flex items-center gap-1 self-stretch">
                <span class="w-[120px] text-gray-500 text-xs font-medium">{{ $t('public.amount') }}</span>
                <span class="flex-grow text-gray-950 text-sm font-medium">$ {{ data.transaction_amount }}</span>
            </div>
            <div class="flex items-center gap-1 self-stretch">
                <span class="w-[120px] text-gray-500 text-xs font-medium">{{ $t('public.status') }}</span>
                <StatusBadge :variant="data.status">{{ $t('public.' + data.status) }}</StatusBadge>
            </div>
        </div>
        <div v-if="['deposit', 'withdrawal'].includes(data.transaction_type)" class="flex flex-col items-center py-4 gap-3 self-stretch border-b border-gray-200">
            <div v-if="data.transaction_type === 'deposit'" class="flex flex-col justify-center items-start gap-1 self-stretch md:flex-row md:justify-normal md:items-center relative">
                <Tag
                    v-if="tooltipText === 'copied'"
                    class="absolute -top-1 right-[120px] md:-top-7 md:right-20"
                    severity="contrast"
                    :value="$t(`public.${tooltipText}`)"
                ></Tag>
                <span class="self-stretch w-[120px] text-gray-500 text-xs font-medium">{{ $t('public.sent_address') }}</span>
                <div
                    class="w-full max-w-[360px] md:max-w-[220px] text-gray-950 font-medium text-sm truncate select-none cursor-pointer"
                    @click="copyToClipboard(data.to_wallet_address)"
                >
                    {{ data.to_wallet_address }}
                </div>
            </div>
            <div v-if="data.transaction_type === 'withdrawal'" class="h-[42px] flex flex-col justify-center items-start gap-1 self-stretch md:h-auto md:flex-row md:justify-normal md:items-center">
                <span class="self-stretch w-[120px] text-gray-500 text-xs font-medium">{{ $t('public.wallet_name') }}</span>
                <span class="w-full max-w-[360px] md:max-w-[220px] overflow-hidden text-gray-950 text-ellipsis text-sm font-medium">{{ data.wallet_name }}</span>
            </div>
            <div v-if="data.transaction_type === 'withdrawal'" class="h-[42px] flex flex-col justify-center items-start gap-1 self-stretch md:h-auto md:flex-row md:justify-normal md:items-center relative">
                <Tag
                    v-if="tooltipText === 'copied'"
                    class="absolute -top-1 right-[120px] md:-top-7 md:right-20"
                    severity="contrast"
                    :value="$t(`public.${tooltipText}`)"
                ></Tag>
                <span class="self-stretch w-[120px] text-gray-500 text-xs font-medium">{{ $t('public.receiving_address') }}</span>
                <div
                    class="w-full max-w-[360px] md:max-w-[220px] text-gray-950 font-medium text-sm truncate select-none cursor-pointer"
                    @click="copyToClipboard(data.to_wallet_address)"
                >
                    {{ data.to_wallet_address }}
                </div>
            </div>
        </div>
        <div v-if="['deposit', 'withdrawal'].includes(data.transaction_type)" class="flex flex-col items-center py-4 gap-3 self-stretch">
            <div class="flex flex-col items-start gap-1 self-stretch md:flex-row">
                <span class="h-5 flex flex-col justify-center self-stretch text-gray-500 text-xs font-medium md:w-[120px]">{{ $t('public.remarks') }}</span>
                <span class="md:max-w-[220px] text-gray-950 text-sm font-medium md:flex-grow">{{ data.remarks }}</span>
            </div>
        </div>
    </Dialog>
</template>
