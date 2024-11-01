<script setup>
import Button from "@/Components/Button.vue";
import { onMounted, ref, watch, watchEffect } from "vue";
import { usePage } from '@inertiajs/vue3';
import Dialog from "primevue/dialog";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { transactionFormat } from "@/Composables/index.js";
import {
    IconReport,
    IconFilterOff,
    IconPlus,
    IconMinus,
    IconCloudDownload
} from "@tabler/icons-vue";
import { CalendarIcon } from "@/Components/Icons/outline.jsx";
import dayjs from "dayjs";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import { trans, wTrans } from "laravel-vue-i18n";

const visible = ref(false);
const dt = ref();
const settlementReports = ref();
const expandedRows = ref([{}]);
const months = ref([]);
const teams = ref([]);
const selectedMonths = ref([]);
const selectedTeam = ref();

const {formatAmount} = transactionFormat();

const getCurrentMonthYear = () => {
    const date = new Date();
    return `${dayjs(date).format('MMMM YYYY')}`;
};

// Fetch settlement months from API
const getSettlementMonths = async () => {
    try {
        const response = await axios.get('/getSettlementMonths');
        months.value = response.data.months;

        if (months.value.length) {
            selectedMonths.value = [getCurrentMonthYear()];
        }
    } catch (error) {
        console.error('Error transaction months:', error);
    }
};

const getTeams = async () => {
    try {
        const response = await axios.get('/getTeams');
        teams.value = response.data.teams;
    } catch (error) {
        console.error('Error teams:', error);
    }
};

const getResults = async (selectedMonths = [], selectedTeam = null) => {
    try {
        let url = '/team/getSettlementReport';

        // Convert the array to a comma-separated string if not empty
        if (selectedMonths && selectedMonths.length > 0) {
            const selectedMonthString = selectedMonths.map(month => dayjs(month, 'MMMM YYYY').format('MM/YYYY')).join(',');
            url += `?selectedMonths=${selectedMonthString}`;
        }

        if (selectedTeam) {
            url += selectedMonths.length > 0 ? `&selectedTeam=${selectedTeam}` : `?selectedTeam=${selectedTeam}`;
        }

        const response = await axios.get(url);
        settlementReports.value = response.data.settlementReports;
    } catch (error) {
        console.error('Error get Settlement Report:', error);
    }
};

// Watch for changes in the dialog visibility
watch(visible, (newVal) => {
    if (newVal) {
        getSettlementMonths();
        getTeams();
    }
});

watch(selectedMonths, (newMonths) => {
    getResults(newMonths, selectedTeam.value);
});

watch(selectedTeam, (newTeam) => {
    getResults(selectedMonths.value, newTeam);
});

const clearGlobal = () => {
    selectedMonths.value = [getCurrentMonthYear()];
    selectedTeam.value = null;
};

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = dt.value?.value || [];

    // Specify the headers
    const headers = [
        trans('public.month'),
        `${trans('public.total_fee')} ($)`,
        `${trans('public.total_balance')} ($)`,
        trans('public.sales_team'),
        `${trans('public.deposit')} ($)`,
        `${trans('public.withdrawal')} ($)`,
        `${trans('public.fee')} ($)`,
        `${trans('public.balance')} ($)`,
    ];

    // Map the main data to rows with expandable data nested directly after each main row
    const sheetData = [headers];

    data.forEach(obj => {
        // Add main row
        const mainRow = [
            obj.month ? dayjs(obj.month).format('MMMM YYYY') : '',
            obj.total_fee ?? '',
            obj.total_balance ?? '',
            '', '', '', '', ''  // Empty cells for expandable columns
        ];
        sheetData.push(mainRow);

        // Add each expandable row associated with this main row
        (obj.team_settlements || []).forEach(teamSettlement => {
            const expandableRow = [
                '', '', '',  // Empty cells for non-expandable columns
                teamSettlement.team_name ?? '',
                teamSettlement.team_deposit ?? '',
                teamSettlement.team_withdrawal ?? '',
                teamSettlement.team_fee ?? '',
                teamSettlement.team_balance ?? ''
            ];
            sheetData.push(expandableRow);
        });
    });

    // Create the XLSX content as tab-separated text
    let xlsxContent = "data:text/xlsx;charset=utf-8,";

    sheetData.forEach((row) => {
        xlsxContent += row.join("\t") + "\r\n";  // Join with tabs and add new line
    });

    // Create a temporary link element to trigger the download
    const encodedUri = encodeURI(xlsxContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "export.xlsx");

    // Append link, trigger click, and remove link
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const expandAll = () => {
    expandedRows.value = settlementReports.value.reduce((acc, p) => (acc[p.id] = true) && acc, {});
};
const collapseAll = () => {
    expandedRows.value = null;
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getSettlementMonths();
        getTeams();
    }
});

</script>

<template>
    <Button
        variant="primary-tonal"
        type="button"
        size="base"
        class="w-full md:w-auto"
        @click="visible = true"
    >
        <div class="w-full flex justify-center items-center gap-3 self-stretch">
            <IconReport  size="20" stroke-width="1.25" />
            <div class="text-center text-sm font-medium">
                {{ $t('public.settlement_report') }}
            </div>
        </div>
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.view_settlement_report')"
        class="dialog-xs md:dialog-lg"
    >
        <div class="w-full flex flex-col justify-center items-center pt-4 md:pt-6 self-stretch">
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="settlementReports"
                dataKey="month"
                removable-sort
                ref="dt"
            >
                <template #header>
                    <div class="flex flex-col gap-3 md:gap-8 items-center self-stretch mb-6 md:mb-8">
                        <div class="flex flex-col gap-3 md:flex-row md:justify-between items-center self-stretch">
                            <div class="flex flex-col md:flex-row gap-3 items-center w-full md:justify-between">
                                <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                                    <MultiSelect
                                        v-model="selectedMonths"
                                        :options="months"
                                        :placeholder="$t('public.month_placeholder')"
                                        :maxSelectedLabels="1"
                                        :selectedItemsLabel="`${selectedMonths.length} ${$t('public.months_selected')}`"
                                        class="w-full md:w-60 h-full font-normal"
                                    >
                                        <template #header>
                                            <div class="absolute flex left-10 top-2">
                                                {{ $t('public.select_all') }}
                                            </div>
                                        </template>
                                        <template #value="slotProps">
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
                                        v-model="selectedTeam"
                                        :options="teams"
                                        filter
                                        :filterFields="['name']"
                                        optionLabel="name"
                                        optionValue="value"
                                        :placeholder="$t('public.filter_by_sales_team')"
                                        class="w-full md:w-60 h-full font-normal"
                                        scroll-height="236px"
                                    />
                                </div>
                                <Button 
                                    type="button"
                                    size="base"
                                    variant="error-outlined"
                                    class="w-full md:w-auto"
                                    @click="clearGlobal"
                                    >
                                        <IconFilterOff size="20" stroke-width="1.25" />
                                        {{ $t('public.clear') }}
                                </Button>
                            </div>
                        </div>
                        <div class="w-full flex flex-col md:flex-row md:justify-between">
                            <Button
                                type="button"
                                variant="primary-outlined"
                                class="w-full md:w-36"
                                @click="settlementReports?.length > 0 ? exportXLSX($event) : null"
                            >
                                {{ $t('public.export') }}
                                <IconCloudDownload size="20" stroke-width="1.25" />
                            </Button>

                            <div class="hidden md:flex md:flex-wrap md:justify-end gap-3 w-full">
                                <Button
                                    type="button"
                                    variant="gray-text"
                                    @click="expandAll"
                                >
                                    <IconPlus size="20" stroke-width="1.25" />
                                    {{ $t('public.expand_all') }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="gray-text"
                                    @click="collapseAll"
                                >
                                    <IconMinus size="20" stroke-width="1.25" />
                                    {{ $t('public.collapse_all') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </template>
                <Column expander headerClass="hidden md:table-cell" class="w-[5%] md:w-[10%]" />
                <Column field="month" :header="$t('public.month')" sortable headerClass="hidden md:table-cell" class="w-full px-3 md:w-[30%]">
                    <template #body="slotProps">
                        <span class="font-semibold md:font-normal">{{ dayjs(slotProps.data.month).format('MMMM YYYY') }}</span>
                    </template>
                </Column>
                <Column field="total_fee" :header="$t('public.total_fee') + ' ($)'" headerClass="hidden md:table-cell" class="md:w-[30%]">
                    <template #body="slotProps">
                        <span class="md:hidden font-semibold">$&nbsp;</span>
                        <span class="font-semibold md:font-normal">{{ formatAmount(slotProps.data.total_fee) }}</span>
                    </template>
                </Column>
                <Column field="total_balance" :header="$t('public.total_balance') + ' ($)'" class="hidden md:table-cell w-[30%]">
                    <template #body="slotProps">
                        {{ formatAmount(slotProps.data.total_balance) }}
                    </template>
                </Column>
                <template #expansion="slotProps">
                    <DataTable
                        :value="slotProps.data.team_settlements"
                        removable-sort
                        class="pl-12 md:pl-16"
                    >
                        <Column field="team_name" :header="$t('public.sales_team')" class="text-nowrap hidden md:table-cell w-[15%] px-3" />
                        <Column field="team_deposit" :header="$t('public.deposit') + '&nbsp;($)'" sortable class="hidden md:table-cell w-[20%] px-3">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.team_deposit) }}
                            </template>
                        </Column>
                        <Column field="team_withdrawal" :header="$t('public.withdrawal') + '&nbsp;($)'" sortable class="hidden md:table-cell w-[20%] px-3">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.team_withdrawal) }}
                            </template>
                        </Column>
                        <Column field="team_fee" :header="$t('public.fee') + '&nbsp;($)'" sortable class="hidden md:table-cell w-[20%] px-3">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.team_fee) }}
                            </template>
                        </Column>
                        <Column field="team_balance" :header="$t('public.balance') + '&nbsp;($)'" sortable class="hidden md:table-cell w-[20%] px-3">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.team_balance) }}
                            </template>
                        </Column>
                        <Column headerClass="hidden" class="md:hidden">
                            <template #body="slotProps">
                                <div class="w-full flex justify-between items-center self-stretch">
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="self-stretch text-gray-950 text-xs font-semibold">{{ slotProps.data.team_name }}</span>
                                        <div class="flex items-center gap-1 text-xs flex-wrap">
                                            <span class="text-gray-500">{{ $t('public.bal') }}:</span>
                                            <span class="text-gray-700 break-all">$&nbsp;{{ formatAmount(slotProps.data.team_balance) }}</span>
                                        </div>
                                    </div>
                                    <span class="w-full text-gray-950 text-right break-all">$&nbsp;{{ formatAmount(slotProps.data.team_fee) }}</span>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </DataTable>
        </div>
    </Dialog>
</template>
