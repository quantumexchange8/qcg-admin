<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import { transactionFormat } from "@/Composables/index.js";
import { IconCircleXFilled, IconSearch, IconDownload } from "@tabler/icons-vue";
import { ref, watchEffect } from "vue";
import Loader from "@/Components/Loader.vue";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import Button from '@/Components/Button.vue';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import { FilterMatchMode } from '@primevue/core/api';
import dayjs from 'dayjs'
import Dialog from "primevue/dialog";
import InputLabel from "@/Components/InputLabel.vue";
import Chip from "primevue/chip";
import Textarea from "primevue/textarea";
import Empty from "@/Components/Empty.vue";

const user = usePage().props.auth.user;
const { formatAmount, formatDate } = transactionFormat();

const loading = ref(false);
const dt = ref();
// const pendingWithdrawals = ref([
//     { 
//         id: 1, 
//         user_profile_photo: 'https://via.placeholder.com/100', 
//         user_name: 'John Doe', 
//         user_email: 'john.doe@example.com', 
//         created_at: '2024-09-05T12:34:56Z', 
//         from: 'rebate_wallet', 
//         amount: '150.00', 
//         balance: '200.00', 
//         wallet_name: 'Main Wallet', 
//         wallet_address: '1234 Placeholder Ave' 
//     },
//     { 
//         id: 2, 
//         user_profile_photo: 'https://via.placeholder.com/100', 
//         user_name: 'Jane Smith', 
//         user_email: 'jane.smith@example.com', 
//         created_at: '2024-09-04T15:20:30Z', 
//         from: '80001234', 
//         amount: '75.00', 
//         balance: '100.00', 
//         wallet_name: 8000412, 
//         wallet_address: '5678 Dummy St' 
//     },
//     { 
//         id: 3, 
//         user_profile_photo: 'https://via.placeholder.com/100', 
//         user_name: 'Alice Johnson', 
//         user_email: 'alice.johnson@example.com', 
//         created_at: '2024-09-03T10:10:10Z', 
//         from: 'rebate_wallet', 
//         amount: '120.00', 
//         balance: '180.00', 
//         wallet_name: 8001547, 
//         wallet_address: '9101 Example Ave' 
//     },
//     { 
//         id: 4, 
//         user_profile_photo: 'https://via.placeholder.com/100', 
//         user_name: 'Bob Brown', 
//         user_email: 'bob.brown@example.com', 
//         created_at: '2024-09-02T08:15:45Z', 
//         from: '80005678', 
//         amount: '200.00', 
//         balance: '250.00', 
//         wallet_name: 8000518, 
//         wallet_address: '2345 Business Rd' 
//     },
//     { 
//         id: 5, 
//         user_profile_photo: 'https://via.placeholder.com/100', 
//         user_name: 'Carol White', 
//         user_email: 'carol.white@example.com', 
//         created_at: '2024-09-01T14:22:35Z', 
//         from: 'rebate_wallet', 
//         amount: '90.00', 
//         balance: '130.00', 
//         wallet_name: 'rebate_wallet', 
//         wallet_address: '6789 Invest St' 
//     }
// ]);
const pendingWithdrawals = ref();
const totalAmount = ref();
const filteredValueCount = ref(0);

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/pending/getPendingWithdrawalData');
        pendingWithdrawals.value = response.data.pendingWithdrawals;
        totalAmount.value = response.data.totalAmount;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const exportCSV = () => {
    dt.value.exportCSV();
};

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    user_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    user_email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    from: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

// watchEffect(() => {
//     if (usePage().props.toast !== null) {
//         getResults();
//     }
// });

const visible = ref(false);
const pendingData = ref({});
const approvalAction = ref('');

const rowClicked = (data) => {
    pendingData.value = data;
    visible.value = true;
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
        { label: 'Withdrawal successful' },
        { label: '您已成功提款' },
    ],
    reject: [
        { label: 'Withdrawal rejected' },
        { label: '提款已被拒絕' },
    ]
});

const handleChipClick = (label) => {
    form.remarks = label;
};

const form = useForm({
    id: '',
    action: '',
    remarks: '',
})

const submit = (transactionId) => {
    if (form.remarks === '') {
        form.remarks = approvalAction.value === 'approve' ? 'Withdrawal approved ' : 'Withdrawal rejected. Please submit again.'
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
    filteredValueCount.value = e.filteredValue.length;
};

</script>

<template>
    <AuthenticatedLayout :title="$t('public.withdrawal')">
        <div class="w-full flex flex-col items-center gap-5">
            <div class="flex flex-col justify-center items-center px-3 py-5 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <!-- data table -->
                <DataTable
                    v-model:filters="filters"
                    :value="pendingWithdrawals"
                    :paginator="pendingWithdrawals?.length > 0 && filteredValueCount > 0"
                    removableSort
                    :rows="10"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
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
                            <span class="self-stretch md:self-auto text-gray-950 font-semibold">{{ $t('public.withdrawal_request', {action: ''}) }}</span>
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
                                    @click="exportCSV($event)"
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
                    <template v-if="pendingWithdrawals?.length > 0 && filteredValueCount > 0">
                        <Column field="name" sortable :header="$t('public.name')" style="width: 25%; max-width: 0;" class="px-3">
                            <template #body="slotProps">
                                <div class="flex flex-col items-start max-w-full">
                                    <div class="font-medium truncate max-w-full">
                                        {{ slotProps.data.user_name }}
                                    </div>
                                    <div class="text-gray-500 text-xs truncate max-w-full">
                                        {{ slotProps.data.user_email }}
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="created_at" :header="$t('public.date')" sortable style="width: 25%" class="hidden md:table-cell">
                            <template #body="slotProps">
                                {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                            </template>
                        </Column>
                        <Column field="from" :header="$t('public.from')" style="width: 25%" class="hidden md:table-cell">
                            <template #body="slotProps">
                                {{ slotProps.data.from === 'rebate_wallet' ? $t(`public.${slotProps.data.from}`) : slotProps.data.from }}
                            </template>
                        </Column>
                        <Column field="amount" :header="`${$t('public.amount')}&nbsp;($)`" sortable style="width: 25%" class="px-3">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.amount) }}
                            </template>
                        </Column>
                        <ColumnGroup type="footer">
                            <Row>
                                <Column class="hidden md:table-cell" :footer="$t('public.total') + ' ($) :'" :colspan="3" footerStyle="text-align:right" />
                                <Column class="hidden md:table-cell" :footer="formatAmount(totalAmount ? totalAmount : 0)" />
                                
                                <Column class="md:hidden" :footer="$t('public.total') + ' ($) :'" :colspan="1" footerStyle="text-align:right" />
                                <Column class="md:hidden" :footer="formatAmount(totalAmount ? totalAmount : 0)" />
                            </Row>
                        </ColumnGroup>
                    </template>
                </DataTable>

                <Dialog
                    v-model:visible="visible"
                    modal
                    :header="$t('public.withdrawal_request', { action: approvalAction ? $t(`public.${approvalAction}`) : '' })"
                    class="dialog-xs md:dialog-md"
                >
                    <template
                        v-if="!approvalAction"
                    >
                        <div class="flex flex-col items-center gap-3 self-stretch py-4 md:py-6">
                            <div class="flex flex-col md:flex-row items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                                <div class="min-w-[140px] flex flex-col items-start w-full">
                                    <span class="self-stretch text-gray-950 font-medium truncate">{{ pendingData.user_name }}</span>
                                    <span class="self-stretch text-gray-500 text-sm truncate">{{ pendingData.user_email }}</span>
                                </div>
                                <div class="min-w-[180px] text-gray-950 font-semibold text-lg self-stretch md:text-right">
                                    $ {{ formatAmount(pendingData.amount) }}
                                </div>
                            </div>

                            <div class="flex flex-col items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.requested_date') }}
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        {{ dayjs(pendingData.created_at).format('YYYY/MM/DD HH:mm:ss') }}
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.from') }}
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        {{ pendingData.from === 'rebate_wallet' ? $t(`public.${pendingData.from}`) : pendingData.from }}
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.balance') }}
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        $ {{ formatAmount(pendingData.balance) }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.wallet_name') }}
                                    </div>
                                    <div class="text-gray-950 text-sm font-medium">
                                        {{ pendingData.wallet_name }}
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                    <div class="w-[140px] text-gray-500 text-sm">
                                        {{ $t('public.receiving_address') }}
                                    </div>
                                    <div class="text-gray-950 text-sm break-words font-medium">
                                        {{ pendingData.wallet_address }}
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
                                    $ {{ formatAmount(pendingData.amount) }}
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
                                    :placeholder="approvalAction === 'approve' ? 'Withdrawal approved' : 'Withdrawal rejected. Please submit again.'"
                                    :invalid="!!form.errors.remarks"
                                    rows="5"
                                    cols="30"
                                />
                                <span class="self-stretch text-gray-500 text-xs">{{ $t('public.remarks_caption') }}</span>
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