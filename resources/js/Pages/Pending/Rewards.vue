<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import { transactionFormat, generalFormat } from "@/Composables/index.js";
import { IconCircleXFilled, IconSearch, IconDownload, IconCopy } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted, onUnmounted } from "vue";
import Loader from "@/Components/Loader.vue";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import Button from '@/Components/Button.vue';
import ColumnGroup from 'primevue/group';
import Row from 'primevue/row';
import { FilterMatchMode } from '@primevue/core/api';
import dayjs from 'dayjs'
import Dialog from "primevue/dialog";
import InputLabel from "@/Components/InputLabel.vue";
import Chip from "primevue/chip";
import Textarea from "primevue/textarea";
import Empty from "@/Components/Empty.vue";
import { trans, wTrans } from "laravel-vue-i18n";
import Tag from 'primevue/tag';
import {useLangObserver} from "@/Composables/localeObserver.js";

const {locale} = useLangObserver();
const user = usePage().props.auth.user;
const { formatAmount, formatDate } = transactionFormat();
const { formatRgbaColor } = generalFormat();

const loading = ref(false);
const dt = ref();
const pendingRewards = ref();
const totalAmount = ref();
const filteredValue = ref();

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/pending/getPendingRewardsData');
        pendingRewards.value = response.data.pendingRewards;
        totalAmount.value = response.data.totalAmount;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    user_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    user_email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    from: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
    filteredValue.value = null;
}

const recalculateTotals = () => {
    const globalFilterValue = filters.value.global?.value?.toLowerCase();

    const filtered = pendingRewards.value.filter(pendingReward => {
        const matchesGlobalFilter = globalFilterValue 
            ? [
                pendingReward.user_name, 
                pendingReward.user_email, 
            ].some(field => {
                // Convert field to string and check if it includes the global filter value
                const fieldValue = field !== undefined && field !== null ? field.toString() : '';
                return fieldValue.toLowerCase().includes(globalFilterValue);
            }) 
            : true; // If no global filter is set, match all

        // Apply individual field filters (name, email, status)
        const matchesNameFilter = !filters.value.user_name?.value || pendingReward.user_name.startsWith(filters.value.user_name.value);
        const matchesEmailFilter = !filters.value.user_email?.value || pendingReward.user_email.startsWith(filters.value.user_email.value);

        // Only return pendingRewards that match both global and specific filters
        return matchesGlobalFilter && matchesNameFilter && matchesEmailFilter;
    });

    // Calculate the total for successful pendingRewards
    totalAmount.value = filtered.reduce((acc, item) => acc + parseFloat(item.transaction_amount || 0), 0);
};

watch(filters, () => {
    recalculateTotals();
}, { deep: true });

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const visible = ref(false);
const pendingData = ref({});
const approvalAction = ref('');

const rowClicked = (data) => {
    pendingData.value = data;
    visible.value = true;
    form.reset();
}

const handleApproval = (action) => {
    approvalAction.value = action;
}

const closeDialog = () => {
    visible.value = false;
    approvalAction.value = '';
}

const chips = ref({
    approve: [
        { label: 'Reward successful' },
        { label: '您已成功提取獎勵' },
    ],
    reject: [
        { label: 'Reward rejected' },
        { label: '提取獎勵已被拒絕' },
    ]
});

const handleChipClick = (label) => {
    form.remarks = label;
};

const form = useForm({
    id: '',
    action: '',
    remarks: '',
    type: 'rewards',
})

const submit = (transactionId) => {
    if (form.remarks === '') {
        form.remarks = approvalAction.value === 'approve' ? 'Reward approved ' : 'Reward rejected. Please submit again.';
    }

    form.id = transactionId;
    form.action = approvalAction.value;

    form.post(route('pending.withdrawalApproval'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
    });
};

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
        trans('public.from'),
        trans('public.amount') + ' ($)',
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        const fromDisplay = obj.from === 'rebate_wallet' ? trans('public.' + obj.from) : obj.from || '';

        return [
            obj.user_name !== undefined ? obj.user_name : '',
            obj.user_email !== undefined ? obj.user_email : '',
            obj.created_at !== undefined ? dayjs(obj.created_at).format('YYYY/MM/DD') : '',
            fromDisplay,
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
    <AuthenticatedLayout :title="$t('public.rewards')">
        <div class="w-full flex flex-col items-center gap-5">
            <div class="flex flex-col justify-center items-center px-3 py-5 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <!-- data table -->
                <DataTable
                    v-model:filters="filters"
                    :value="pendingRewards"
                    :paginator="pendingRewards?.length > 0 && filteredValue?.length > 0"
                    removableSort
                    :rows="100"
                    :pageLinkSize="pageLinkSize"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="$t('public.paginator_caption')"
                    :globalFilterFields="['user_name', 'user_email', 'from']"
                    ref="dt"
                    :loading="loading"
                    selectionMode="single"
                    @row-click="rowClicked($event.data)"
                    @filter="handleFilter"
                >
                    <template #header>
                        <div class="flex flex-col justify-between items-center pb-5 gap-5 self-stretch md:flex-row md:pb-6">
                            <span class="self-stretch md:self-auto text-gray-950 font-semibold">{{ $t('public.rewards_request', {action: ''}) }}</span>
                            <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
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
                                <Button
                                    variant="primary-outlined"
                                    @click="filteredValue?.length > 0 ? exportXLSX($event) : null"
                                    class="w-full"
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
                    <template v-if="pendingRewards?.length > 0 && filteredValue?.length > 0">
                        <Column field="name" sortable style="width: 20%; max-width: 0;" class="px-3">
                            <template #header>
                                <span class="block truncate">{{ $t('public.name') }}</span>
                            </template>

                            <template #body="slotProps">
                                <div class="flex flex-col items-start max-w-full">
                                    <div class="flex max-w-full gap-2 items-center">
                                        <div class="font-semibold truncate max-w-full">
                                            {{ slotProps.data.user_name }}
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
                                        {{ slotProps.data.user_email }}
                                    </div>
                                    <div class="text-gray-500 text-xs truncate max-w-full md:hidden flex">
                                        {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD HH:mm:ss') }}
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="created_at" :header="$t('public.date')" sortable style="width: 20%" class="hidden md:table-cell">
                            <template #body="slotProps">
                                {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                            </template>
                        </Column>
                        <!-- <Column field="from" :header="$t('public.from')" style="width: 20%" class="hidden md:table-cell">
                            <template #body="slotProps">
                                {{ slotProps.data.from === 'rebate_wallet' ? ($t(`public.${slotProps.data.from}`) || '-') : (slotProps.data.from || '-') }}
                            </template>
                        </Column> -->
                        <Column field="team" :header="$t('public.sales_team')" style="width: 20%" class="hidden md:table-cell">
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
                        <Column field="reward_code" :header="$t('public.code')" sortable style="width: 20%; max-width: 0" class="px-3">
                            <template #body="slotProps">
                                {{ slotProps.data.reward_code }}
                            </template>
                        </Column>
                        <Column field="transaction_amount" sortable style="width: 20%;" class="hidden md:table-cell">
                            <template #header>
                                <span class="block truncate">{{ $t(`${$t('public.tp_used')}`) }}</span>
                            </template>

                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data?.transaction_amount || 0) }}
                            </template>
                        </Column>
                        <!-- <ColumnGroup type="footer">
                            <Row>
                                <Column class="hidden md:table-cell" :footer="$t('public.total') + ' ($) :'" :colspan="4" footerStyle="text-align:right" />
                                <Column class="hidden md:table-cell" :footer="formatAmount(totalAmount ? totalAmount : 0)" />
                                
                                <Column class="md:hidden" :footer="$t('public.total') + ' ($) :'" :colspan="1" footerStyle="text-align:right" />
                                <Column class="md:hidden" :footer="formatAmount(totalAmount ? totalAmount : 0)" />
                            </Row>
                        </ColumnGroup> -->
                    </template>
                </DataTable>

                <Dialog
                    v-model:visible="visible"
                    modal
                    :header="$t('public.rewards_request', { action: approvalAction ? $t(`public.${approvalAction}`) : '' })"
                    class="dialog-xs md:dialog-md"
                    :dismissableMask="true"
                >
                    <template
                        v-if="!approvalAction"
                    >
                        <div class="flex flex-col items-center gap-3 self-stretch py-4 md:py-6">
                            <div class="flex flex-col md:flex-row items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                                <div class="min-w-[140px] flex flex-col items-start w-full">
                                    <span class="self-stretch text-gray-950 font-medium flex gap-1 relative" @click="copyToClipboard('user_name', pendingData.user_name)">
                                        {{ pendingData?.user_name || '-' }}
                                        <IconCopy 
                                            v-if="pendingData?.user_name"
                                            size="20" 
                                            stroke-width="1.25" 
                                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0" 
                                            v-tooltip.top="$t(`public.${tooltipText}`)" 
                                            @click="copyToClipboard('user_name', pendingData.user_name)"
                                        />
                                        <Tag
                                            v-if="activeTag === 'user_name' && tooltipText === 'copied'"
                                            class="absolute -top-7 -right-3"
                                            severity="contrast"
                                            :value="$t(`public.${tooltipText}`)"
                                        ></Tag>
                                    </span>

                                </div>
                                <div class="min-w-[180px] text-gray-950 font-semibold text-lg self-stretch md:text-right">
                                    {{ formatAmount(pendingData?.transaction_amount || 0) }} tp
                                    <IconCopy 
                                        v-if="pendingData?.transaction_amount"
                                        size="20" 
                                        stroke-width="1.25" 
                                        class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0" 
                                        v-tooltip.top="$t(`public.${tooltipText}`)" 
                                        @click="copyToClipboard('transaction_amount', pendingData.transaction_amount)"
                                    />
                                    <Tag
                                        v-if="activeTag === 'transaction_amount' && tooltipText === 'copied'"
                                        class="absolute -top-7 -right-3"
                                        severity="contrast"
                                        :value="$t(`public.${tooltipText}`)"
                                    ></Tag>
                                </div>
                            </div>

                            <div class="flex flex-col items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.requested_date') }}
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        {{ dayjs(pendingData.created_at).format('YYYY/MM/DD') }}
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.email') }}
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        {{ pendingData.user_email }}
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.sales_team') }}
                                    </div>
                                    <div class="flex items-center">
                                        <div
                                            v-if="pendingData.team_id"
                                            class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                                            :style="{ backgroundColor: formatRgbaColor(pendingData.team_color, 1) }"
                                        >
                                            <div
                                                class="text-white text-xs text-center"
                                            >
                                                {{ pendingData.team_name }}
                                            </div>
                                        </div>
                                        <div v-else>
                                            -
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.rewards_code') }}
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                         {{ pendingData.reward_code }}
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.rewards_name') }}
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        {{ pendingData.reward_type === 'cash_rewards' ? '💰 ' : '🎁 '  }}{{ pendingData.reward_name[locale] }}
                                    </div>
                                </div>
                                <div v-if="pendingData.reward_type === 'cash_rewards'" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.receiving_account') }}
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        {{ pendingData.reward_details.receiving_account }}
                                    </div>
                                </div>
                                <div v-else class="flex flex-col gap-3 self-stretch">
                                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                        <div class="w-[140px] text-gray-500 text-sm">
                                            {{ $t('public.recipient_name') }}
                                        </div>
                                        <div class="text-gray-950 text-sm font-medium">
                                            {{ pendingData.reward_details.recipient_name }}
                                        </div>
                                    </div>
                                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                        <div class="w-[140px] text-gray-500 text-sm">
                                            {{ $t('public.phone_number') }}
                                        </div>
                                        <div class="text-gray-950 text-sm font-medium">
                                            {{ pendingData.reward_details.phone_number }}
                                        </div>
                                    </div>
                                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                        <div class="w-[140px] text-gray-500 text-sm">
                                            {{ $t('public.provided_address') }}
                                        </div>
                                        <div class="text-gray-950 text-sm font-medium">
                                            {{ pendingData.reward_details.address }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end items-center pt-6 gap-4 self-stretch w-full">
                            <Button
                                type="button"
                                variant="error-flat"
                                class="w-full"
                                @click="handleApproval('reject')"
                            >
                                {{ $t('public.reject') }}
                            </Button>
                            <Button
                                variant="success-flat"
                                class="w-full"
                                @click="handleApproval('approve')"
                            >
                                {{ $t('public.approve') }}
                            </Button>
                        </div>
                    </template>

                    <template
                        v-if="approvalAction"
                    >
                        <div class="flex flex-col items-center gap-3 self-stretch py-4 md:py-6">
                            <div class="flex flex-col md:flex-row items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                                <div class="min-w-[140px] flex flex-col items-start w-full">
                                    <span class="self-stretch text-gray-950 font-medium truncate">{{ pendingData.user_name }}</span>
                                    <span class="self-stretch text-gray-500 text-sm truncate">{{ pendingData.user_email }}</span>
                                </div>
                                <div class="min-w-[180px] text-gray-950 font-semibold text-lg self-stretch md:text-right">
                                    {{ formatAmount(pendingData?.transaction_amount || 0) }} tp
                                </div>
                            </div>

                            <div class="flex flex-col items-start gap-2 self-stretch">
                                <InputLabel for="remarks">{{ $t('public.remarks') }}</InputLabel>
                                <div class="flex items-center gap-2 self-stretch overflow-x-auto">
                                    <div v-for="(chip, index) in chips[approvalAction]" :key="index">
                                        <Chip
                                            :label="chip.label"
                                            class="w-full text-gray-950 whitespace-nowrap overflow-hidden"
                                            :class="{
                                                'border-primary-300 bg-primary-50 text-primary-500 hover:bg-primary-50': form.remarks === chip.label,
                                            }"
                                            @click="handleChipClick(chip.label)"
                                        />
                                    </div>
                                </div>
                                <Textarea
                                    id="remarks"
                                    type="text"
                                    class="h-20 flex self-stretch"
                                    v-model="form.remarks"
                                    :placeholder="approvalAction === 'approve' ? 'Rewards request has been approved.' : 'Rewards request has been rejected.'"
                                    :invalid="!!form.errors.remarks"
                                    rows="5"
                                    cols="30"
                                />
                                <span class="self-stretch text-gray-500 text-xs">
                                    {{ $t('public.remarks_caption') }}
                                </span>
                            </div>
                        </div>

                        <div class="flex justify-end items-center pt-6 gap-4 self-stretch w-full">
                            <Button
                                type="button"
                                variant="gray-tonal"
                                class="w-full"
                                @click="closeDialog"
                            >
                                {{ $t('public.cancel') }}
                            </Button>
                            <Button
                                variant="primary-flat"
                                class="w-full"
                                @click="submit(pendingData.id)"
                            >
                                {{ $t('public.confirm') }}
                            </Button>
                        </div>
                    </template>
                </Dialog>

            </div>
        </div>
    </AuthenticatedLayout>
</template>