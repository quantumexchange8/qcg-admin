<script setup>
import Button from "@/Components/Button.vue"
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconDownload } from "@tabler/icons-vue";
import { transactionFormat } from "@/Composables/index.js";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import dayjs from 'dayjs'
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import { trans, wTrans } from "laravel-vue-i18n";
import Select from "primevue/select";

const { formatAmount, formatDate } = transactionFormat();

const props = defineProps({
    team: Object,
})

const emit = defineEmits(['update:visible'])

const closeDialog = () => {
    emit('update:visible', false);
}

const loading = ref(false);
const dt = ref();
const transactions = ref();
const totalAmount = ref(0);
const totalFee = ref(0);
const totalBalance = ref(0);

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
watch(selectedMonth, (newMonths) => {
    getResults(newMonths);
});

const getResults = async (selectedMonth = '') => {
    loading.value = true;

    try {
        let url = `/team/getTeamTransaction?team_id=${props.team.id}`;

        if (selectedMonth) {
            let formattedMonth = selectedMonth;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            url += `&selectedMonth=${formattedMonth}`;
        }

        const response = await axios.get(url);
        transactions.value = response.data.transactions;
        totalAmount.value = response.data.totalAmount;
        totalFee.value = response.data.totalFee;
        totalBalance.value = response.data.totalBalance;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = dt.value.value;

    // Specify the headers
    const headers = [
        trans('public.name'),
        trans('public.email'),
        trans('public.date'),
        trans('public.type'),
        `${trans('public.amount')} ($)`,
        `${trans('public.fee')} ($)`,
        `${trans('public.balance')} ($)`,
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        return [
            obj.name !== undefined ? obj.name : '',
            obj.email !== undefined ? obj.email : '',
            obj.approved_at !== undefined ? dayjs(obj.approved_at).format('YYYY/MM/DD') : '',
            obj.transaction_type !== undefined ? obj.transaction_type : '',
            obj.amount !== undefined ? obj.amount : '',
            obj.transaction_charges !== undefined ? obj.transaction_charges : '',
            obj.transaction_amount !== undefined ? obj.transaction_amount : '',
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
</script>

<template>
    <div class="w-full flex flex-col justify-center items-center pt-4 gap-4 self-stretch md:pt-6 md:gap-8">
        <DataTable
            :value="transactions"
            removableSort
            scrollable
            scrollHeight="400px"
            tableStyle="md:min-width: 50rem"
            ref="dt"
            :loading="loading"
        >
            <template #header>
                <div class="flex flex-col items-center gap-4 mb-4 md:mb-8">
                    <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:justify-between">
                        <Select 
                            v-model="selectedMonth" 
                            :options="months" 
                            :placeholder="$t('public.month_placeholder')"
                            class="w-full md:w-60 font-normal truncate" scroll-height="236px" 
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

                        <Button
                            variant="primary-outlined"
                            @click="transactions?.length > 0 ? exportXLSX($event) : null"
                            class="w-full md:w-auto"
                        >
                            <IconDownload size="20" stroke-width="1.25" />
                            {{ $t('public.export') }}
                        </Button>
                    </div>
                </div>
            </template>
            <template #empty><Empty :title="$t('public.empty_pending_request_title')" :message="$t('public.empty_pending_request_message')" /></template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loader />
                    <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                </div>
            </template>
            <template v-if="transactions?.length > 0">
            <!-- <template> -->
                <Column field="name" :header="$t('public.name')" sortable style="width: 20%" class="hidden md:table-cell">
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
                <Column field="approved_at" :header="$t('public.date')" sortable style="width: 20%" class="hidden md:table-cell">
                    <template #body="slotProps">
                        {{ dayjs(slotProps.data.approved_at).format('YYYY/MM/DD') }}
                    </template>
                </Column>
                <Column field="amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable style="width: 20%" class="hidden md:table-cell">
                    <template #body="slotProps">
                        {{ formatAmount(slotProps.data?.amount || 0) }}
                    </template>
                </Column>
                <Column field="transaction_charges" :header="`${$t('public.fee')}&nbsp;($)`" sortable style="width: 20%" class="hidden md:table-cell">
                    <template #body="slotProps">
                        {{ formatAmount(slotProps.data?.transaction_charges || 0) }}
                    </template>
                </Column>
                <Column field="transaction_amount" :header="`${$t('public.balance')}&nbsp;($)`" sortable style="width: 20%" class="hidden md:table-cell w-full max-w-0 truncate">
                    <template #body="slotProps">
                        <span :class="{
                                'text-success-600': ['deposit', 'balance_in', 'rebate_in'].includes(slotProps.data.transaction_type),
                                'text-error-600': ['withdrawal', 'balance_out', 'rebate_out'].includes(slotProps.data.transaction_type)
                            }"
                        >
                            {{ formatAmount(slotProps.data?.transaction_amount || 0) }}
                        </span>
                    </template>
                </Column>
                <ColumnGroup type="footer">
                    <Row>
                        <Column class="hidden md:table-cell" :footer="$t('public.total') + ' ($) :'" :colspan="2" footerStyle="text-align:right" />
                        <Column class="hidden md:table-cell" :footer="formatAmount(totalAmount ? totalAmount : 0)" />
                        <Column class="hidden md:table-cell" :footer="formatAmount(totalFee ? totalFee : 0)" />
                        <Column class="hidden md:table-cell" :footer="formatAmount(totalBalance ? totalBalance : 0)" />
                    </Row>
                </ColumnGroup>
                <Column class="w-1/2 max-w-0 md:hidden" headerClass="hidden">
                    <template #body="slotProps">
                        <div class="max-w-full truncate flex flex-col items-start gap-1 self-stretch">
                            <span class="max-w-full truncate  self-stretch text-base text-gray-950 font-semibold">{{ slotProps.data.name }}</span>
                            <div class="max-w-full truncate text-gray-500 text-xs">
                                {{ dayjs(slotProps.data.approved_at).format('YYYY/MM/DD') }}
                            </div>
                        </div>
                    </template>
                </Column>
                <Column class="w-1/2 max-w-0 md:hidden" headerClass="hidden">
                    <template #body="slotProps">
                        <div class="max-w-full truncate flex flex-col items-end gap-1">
                            <span 
                                class="max-w-full truncate self-stretch text-base text-right font-semibold"
                                :class="{
                                    'text-gray-950': !['deposit', 'balance_in', 'withdrawal', 'balance_out', 'rebate_out'].includes(slotProps.data.transaction_type),
                                    'text-success-600': ['deposit', 'balance_in'].includes(slotProps.data.transaction_type),
                                    'text-error-600': ['withdrawal', 'balance_out', 'rebate_out'].includes(slotProps.data.transaction_type)
                                }"
                            >
                                $&nbsp;{{ formatAmount(slotProps.data?.transaction_amount || 0) }}
                            </span>
                            <div v-if="slotProps.data.transaction_charges" class="max-w-full truncate flex justify-end items-center gap-1 self-stretch text-xs">
                                <span class="max-w-full truncate text-gray-500">{{ $t('public.fee') }}:</span>
                                <div class="max-w-full truncate text-gray-700 text-right">
                                    $&nbsp;{{ formatAmount(slotProps.data?.transaction_charges || 0) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </Column>
            </template>
        </DataTable>
    </div>
</template>
