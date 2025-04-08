<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePage } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff, IconCopy } from "@tabler/icons-vue";
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
import Tag from 'primevue/tag';

const { formatAmount } = transactionFormat();
const { formatRgbaColor } = generalFormat();

const visible = ref(false);
const loading = ref(false);
const dt = ref(null);
const verifiedMembers = ref();
const filteredValue = ref();

const months = ref([]);
const selectedMonth = ref('');

const getCurrentMonthYear = () => {
    const date = new Date();
    return `01 ${dayjs(date).format('MMMM YYYY')}`;
};

// Fetch settlement months from API
const getKycMonths = async () => {
    try {
        const response = await axios.get('/getKycMonths');
        months.value = response.data.months;

        if (months.value.length) {
            selectedMonth.value = getCurrentMonthYear();
        }
    } catch (error) {
        console.error('Error Kyc months:', error);
    }
};

getKycMonths()

const getResults = async (selectedMonth = '') => {
    loading.value = true;

    try {
        // Create the base URL with the type parameter directly in the URL
        let url = `/member/getApprovedListing`;

        if (selectedMonth) {
            let formattedMonth = selectedMonth;

            if (!formattedMonth.startsWith('select_') && !formattedMonth.startsWith('last_')) {
                formattedMonth = dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');
            }

            url += `?selectedMonth=${formattedMonth}`;
        }

        // Make the API call with the constructed URL
        const response = await axios.get(url);
        verifiedMembers.value = response.data.verifiedMembers;

    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

watch(selectedMonth, (newMonth) => {
    getResults(newMonth);
    // console.log(newMonths)
});

// const filters = ref({
//     global: { value: null, matchMode: FilterMatchMode.CONTAINS },
//     name: { value: null, matchMode: FilterMatchMode.CONTAINS },
//     email: { value: null, matchMode: FilterMatchMode.CONTAINS },
//     status: { value: 'successful', matchMode: FilterMatchMode.EQUALS },
// });

// const clearFilterGlobal = () => {
//     filters.value['global'].value = null;
// }

// const clearFilter = () => {
//     filters.value = {
//         global: { value: null, matchMode: FilterMatchMode.CONTAINS },
//         name: { value: null, matchMode: FilterMatchMode.CONTAINS },
//         email: { value: null, matchMode: FilterMatchMode.CONTAINS },
//         status: { value: 'successful', matchMode: FilterMatchMode.EQUALS },
//     };
//     selectedMonth.value = getCurrentMonthYear();
//     filteredValue.value = null; 
// };

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    team_id: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
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

const visiblePhoto = ref(false);
const selectedKycVerification = ref(null);
const openPhotoDialog = (verification) => {
    visiblePhoto.value = true;
    selectedKycVerification.value = verification;
}
</script>

<template>
    <AuthenticatedLayout :title="`${$t('public.member')}&nbsp;-&nbsp;${$t('public.sidebar_kyc_listing')}`">
        <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="flex flex-col pb-3 gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between md:pb-0">
                <span class="text-gray-950 font-semibold self-stretch md:self-auto">{{ $t('public.all_kyc_approvals') }}</span>
            </div>
            
            <div v-if="months.length === 0" class="flex flex-col gap-2 items-center justify-center">
                <Loader />
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>

            <DataTable
                v-else
                v-model:filters="filters"
                :value="verifiedMembers"
                :paginator="verifiedMembers?.length > 0 && filteredValue?.length > 0"
                removableSort
                :rows="20"
                :rowsPerPageOptions="[20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :globalFilterFields="['name', 'email', 'transaction_number', 'transaction_type']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @filter="handleFilter"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 items-center gap-3 self-stretch md:flex-row md:gap-2">
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
                            <!-- <Select
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
                            </div> -->
                        </div>
                        <!-- <div class="flex flex-col md:flex-row gap-3 md:gap-2 w-full md:w-auto">
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
                        </div> -->
                    </div>
                </template>
                <template #empty>
                    <Empty 
                        :title="$t('public.empty_adjustment_record_title')" 
                        :message="$t('public.empty_adjustment_record_message')" 
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="verifiedMembers?.length > 0 && filteredValue?.length > 0">
                    <Column field="name" sortable :header="$t('public.name')" class="w-1/2 md:w-[30%] max-w-0 px-3">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start max-w-full">
                                <div class="flex max-w-full gap-2 items-center">
                                    <div class="font-semibold truncate max-w-full">
                                        {{ slotProps.data.name }}
                                    </div>
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
                                <div class="text-gray-500 text-xs truncate max-w-full md:hidden flex w-full">
                                    {{ dayjs(slotProps.data.approved_at).format('YYYY/MM/DD HH:mm:ss') }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="approved_at" :header="$t('public.approved_at')" sortable class="hidden md:table-cell w-full md:w-[30%] max-w-0">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm truncate max-w-full">
                                {{ dayjs(slotProps.data.approved_at).format('YYYY/MM/DD') }}
                            </div>
                        </template>
                    </Column>
                    <Column field="team" :header="$t('public.sales_team')" class="hidden md:table-cell w-[20%]">
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
                    <Column field="status" :header="$t('public.status')" class="table-cell w-[20%]">
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <StatusBadge :variant="slotProps.data.kyc_status" :value="$t('public.' + slotProps.data.kyc_status)" class="text-nowrap"/>
                            </div>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>

    <Dialog v-model:visible="visible" modal :header="$t('public.approval_detail')" class="dialog-xs md:dialog-md" :dismissableMask="true">
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
            </div>
            
            <div class="flex flex-col items-center p-3 gap-3 self-stretch bg-gray-50">
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.submission_date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ dayjs(data.submitted_at).format('YYYY/MM/DD HH:mm:ss') }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1 md:flex-row">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.approved_date') }}</span>
                    <span class="w-full truncate text-gray-950 text-sm font-medium">{{ dayjs(data.approved_at).format('YYYY/MM/DD HH:mm:ss') }}</span>
                </div>
                <div class="w-full flex flex-col items-start gap-1">
                    <span class="w-full max-w-[140px] truncate text-gray-500 text-sm">{{ $t('public.uploaded_files') }}</span>
                    <div class="flex flex-row gap-1 w-full">
                        <div v-for="file in data.kyc_files" :key="file.id" @click="openPhotoDialog(file)" 
                            class="flex items-center gap-3 w-full px-3 py-4 bg-white rounded border border-gray-200 cursor-pointer hover:bg-gray-100"
                        >
                            <img :src="file.original_url" :alt="file.file_name" class="w-12 h-9 rounded" />
                            <span class="text-sm text-gray-700">{{ file.file_name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>

    <Dialog v-model:visible="visiblePhoto" modal headless class="dialog-xs md:dialog-md" :dismissableMask="true">
        <img
            :src="selectedKycVerification?.original_url || '/img/member/kyc_sample_illustration.png'"
            class="w-full"
            alt="kyc_verification"
        />
    </Dialog>
</template>