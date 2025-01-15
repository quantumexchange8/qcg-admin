<script setup>
import { useForm } from "@inertiajs/vue3";
import { transactionFormat, generalFormat } from "@/Composables/index.js";
import { IconCopy } from "@tabler/icons-vue";
import { ref, watch } from "vue";
import Button from '@/Components/Button.vue';
import dayjs from 'dayjs'
import InputLabel from "@/Components/InputLabel.vue";
import Chip from "primevue/chip";
import Textarea from "primevue/textarea";
import { trans, wTrans } from "laravel-vue-i18n";
import Tag from 'primevue/tag';

const { formatAmount, formatDate } = transactionFormat();
const { formatRgbaColor } = generalFormat();

const props = defineProps({
    pendingData: Object,
    selectedRequests: Array,
    approvalAction: String,
    type: String,
})

const emit = defineEmits(['update:visible','update:approvalAction']);
const closeDialog = () => {
    approvalAction.value = '';
    emit('update:approvalAction', approvalAction.value);
    emit('update:visible', false); 
}

const approvalAction = ref(props.approvalAction || '');
const handleApproval = (action) => {
    approvalAction.value = action;
    emit('update:approvalAction', action);
}

const selectedTotalAmount = ref(0);
const selectedRequestsCount = ref(0);
// Watch selectedRequests for changes
watch(() => props.selectedRequests, (newValue) => {
  selectedTotalAmount.value = newValue.reduce((sum, request) => {
    const transactionAmount = parseFloat(request.transaction_amount);
    if (!isNaN(transactionAmount)) {
      return sum + transactionAmount;
    }
    return sum;
  }, 0);

    // Update selectedRecordCount to reflect the number of records
    selectedRequestsCount.value = newValue.length;

}, { immediate: true });

// Chips data based on approvalAction
const chips = ref({
  withdrawal: {
    approve: [
      { label: 'Withdrawal successful' },
      { label: '您已成功提款' },
    ],
    reject: [
      { label: 'Withdrawal rejected' },
      { label: '提款已被拒絕' },
    ],
  },
  bonus: {
    approve: [
      { label: 'Bonus request successful' },
      { label: '您已成功提取信用獎勳' },
    ],
    reject: [
      { label: 'Bonus request rejected' },
      { label: '提取信用獎勳已被拒絕' },
    ],
  },
  incentive: {
    approve: [
      { label: 'Incentive request successful' },
      { label: '您已成功提取獎金' },
    ],
    reject: [
      { label: 'Incentive request rejected' },
      { label: '提取獎金已被拒絕' },
    ],
  },
});

// Handle Chip Click to update remarks
const handleChipClick = (label) => {
  form.remarks = label;
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

const form = useForm({
    id: '',
    action: '',
    remarks: '',
    type: props?.type,
    selectedIds: [],
})

const submit = () => {
    // Set remarks based on the passed props.type and approval action
    if (form.remarks === '') {
        if (props.type === 'withdrawal') {
            form.remarks = approvalAction.value === 'approve' 
                ? 'Withdrawal approved' 
                : 'Withdrawal rejected. Please submit again.';
        } else if (props.type === 'bonus') {
            form.remarks = approvalAction.value === 'approve' 
                ? 'Bonus request has been approved' 
                : 'Bonus request has been rejected';
        } else if (props.type === 'incentive') {
            form.remarks = approvalAction.value === 'approve' 
                ? 'Incentive request has been approved.' 
                : 'Incentive request has been rejected.';
        }
    }

    form.action = approvalAction.value;

    // Handle form data population based on availability of props
    if (props.pendingData) {
        // If pendingData is available, use it to populate form directly
        form.id = props.pendingData?.id;
    } else if (Array.isArray(props.selectedRequests) && props.selectedRequests.length > 0) {
        // If pendingData is not available, but selectedRequests is, populate selectedIds with request IDs
        form.selectedIds = props.selectedRequests.map(req => req.id);  // Extract all selected IDs
    }

    // Handle edge case where neither pendingData nor selectedRequests are available
    if (!form.id && !form.selectedIds.length) {
        console.error('No data available for form submission.');
        return; // Prevent submission if there are no IDs
    }

    // Proceed with the form submission
    form.post(route('pending.withdrawalApproval'), {
        onSuccess: () => {
            form.reset();
            closeDialog();
        },
    });
};
</script>

<template>
    <template v-if="!approvalAction">
        <div class="flex flex-col items-center gap-3 self-stretch py-4 md:py-6">
            <div class="flex flex-col md:flex-row items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                <div class="min-w-[140px] flex flex-col items-start w-full">
                    <span class="self-stretch text-gray-950 font-semibold truncate" @click="copyToClipboard('user_name', props?.pendingData.user_name)">
                        {{ props?.pendingData?.user_name || '-' }}
                        <IconCopy 
                            v-if="props?.pendingData?.user_name"
                            size="20" 
                            stroke-width="1.25" 
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0" 
                            v-tooltip.top="$t(`public.${tooltipText}`)" 
                            @click="copyToClipboard('user_name', props?.pendingData.user_name)"
                        />
                        <Tag
                            v-if="activeTag === 'user_name' && tooltipText === 'copied'"
                            class="font-normal"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>

                    <span class="self-stretch text-gray-500 text-sm truncate">{{ props?.pendingData.user_email }}</span>
                </div>
                <div class="min-w-[180px] text-gray-950 font-semibold text-lg self-stretch md:text-right">
                    $ {{ formatAmount(props?.pendingData?.transaction_amount || 0) }}
                </div>
            </div>

            <div v-if="props.type === 'withdrawal'" class="flex flex-col items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.requested_date') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium">
                        {{ dayjs(props?.pendingData.created_at).format('YYYY/MM/DD HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.from') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium" @click="copyToClipboard('from', props?.pendingData.from)">
                        {{ props?.pendingData.from === 'rebate_wallet' ? ($t(`public.${props?.pendingData.from}`) || '-') : (props?.pendingData.from || '-') }}
                        <IconCopy 
                            v-if="props?.pendingData?.from && props?.pendingData.from !== 'rebate_wallet'"
                            size="20" 
                            stroke-width="1.25" 
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0" 
                            v-tooltip.top="$t(`public.${tooltipText}`)" 
                            @click="copyToClipboard('from', props?.pendingData.from)"
                        />
                        <Tag
                            v-if="activeTag === 'from' && tooltipText === 'copied'"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.sales_team') }}
                    </div>
                    <div class="flex items-center">
                        <div
                            v-if="props?.pendingData.team_id"
                            class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                            :style="{ backgroundColor: formatRgbaColor(props?.pendingData.team_color, 1) }"
                        >
                            <div
                                class="text-white text-xs text-center"
                            >
                                {{ props?.pendingData.team_name }}
                            </div>
                        </div>
                        <div v-else>
                            -
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.balance') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium">
                        $ {{ formatAmount(props?.pendingData?.balance || 0) }}
                    </div>
                </div>
            </div>

            <div v-if="props.type === 'bonus'" class="flex flex-col items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.requested_date') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium">
                        {{ dayjs(props?.pendingData.created_at).format('YYYY/MM/DD HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.sales_team') }}
                    </div>
                    <div class="flex items-center">
                        <div
                            v-if="props?.pendingData.team_id"
                            class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                            :style="{ backgroundColor: formatRgbaColor(props?.pendingData.team_color, 1) }"
                        >
                            <div
                                class="text-white text-xs text-center"
                            >
                                {{ props?.pendingData.team_name }}
                            </div>
                        </div>
                        <div v-else>
                            -
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.from') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium" @click="copyToClipboard('from', props?.pendingData.from)">
                        {{ props?.pendingData.from === 'bonus_wallet' ? ($t(`public.${props?.pendingData.from}`) || '-') : (props?.pendingData.from || '-') }}
                        <IconCopy 
                            v-if="props?.pendingData?.from && props?.pendingData.from !== 'bonus_wallet'"
                            size="20" 
                            stroke-width="1.25" 
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0" 
                            v-tooltip.top="$t(`public.${tooltipText}`)" 
                            @click="copyToClipboard('from', props?.pendingData.from)"
                        />
                        <Tag
                            v-if="activeTag === 'from' && tooltipText === 'copied'"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.deposit_date') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium">
                        {{ dayjs(props?.pendingData.deposit_date).format('YYYY/MM/DD HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.deposit_amount') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium">
                        $ {{ formatAmount(props?.pendingData?.deposit_amount || 0) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.balance') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium">
                        $ {{ formatAmount(props?.pendingData?.balance || 0) }}
                    </div>
                </div>
            </div>

            <div v-if="props.type === 'incentive'" class="flex flex-col items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.requested_date') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium">
                        {{ dayjs(props?.pendingData.created_at).format('YYYY/MM/DD HH:mm:ss') }}
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-sm">
                        {{ $t('public.wallet_name') }}
                    </div>
                    <div class="text-gray-950 text-sm font-medium">
                        {{ props?.pendingData?.wallet_name || '-' }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="min-w-[140px] text-gray-500 text-sm">
                        {{ $t('public.receiving_address') }}
                    </div>
                    <span class="text-gray-950 text-sm break-all font-medium">
                        {{ props?.pendingData?.wallet_address || '-' }}
                        <IconCopy 
                            v-if="props?.pendingData?.wallet_address"
                            size="20" 
                            stroke-width="1.25" 
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0" 
                            v-tooltip.top="$t(`public.${tooltipText}`)" 
                            @click="copyToClipboard('wallet_address', props?.pendingData.wallet_address)"
                        />
                        <Tag
                            v-if="activeTag === 'wallet_address' && tooltipText === 'copied'"
                            class="font-normal"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>
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

    <template v-if="approvalAction">
        <div class="flex flex-col items-center gap-3 self-stretch py-4 md:py-6">
            <div v-if="props?.pendingData" class="flex flex-col md:flex-row items-center p-3 gap-3 self-stretch w-full bg-gray-50">
                <div class="min-w-[140px] flex flex-col items-start w-full">
                    <span class="self-stretch text-gray-950 font-semibold truncate" @click="copyToClipboard('user_name', props?.pendingData.user_name)">
                        {{ props?.pendingData?.user_name || '-' }}
                        <IconCopy 
                            v-if="props?.pendingData?.user_name"
                            size="20" 
                            stroke-width="1.25" 
                            class="text-gray-500 inline-block cursor-pointer grow-0 shrink-0" 
                            v-tooltip.top="$t(`public.${tooltipText}`)" 
                            @click="copyToClipboard('user_name', props?.pendingData.user_name)"
                        />
                        <Tag
                            v-if="activeTag === 'user_name' && tooltipText === 'copied'"
                            class="font-normal"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        ></Tag>
                    </span>
                    <span class="self-stretch text-gray-500 text-sm truncate">{{ props?.pendingData.user_email }}</span>
                </div>
                <div class="min-w-[180px] text-gray-950 font-semibold text-lg self-stretch md:text-right">
                    $ {{ formatAmount(props?.pendingData?.transaction_amount || 0) }}
                </div>
            </div>

            <div v-else class="w-full flex flex-col justify-center items-center py-3 px-8 gap-1 bg-gray-100">
                <span class="text-gray-500 text-center text-xs">{{ $t(`public.selected_request_count`, { count: selectedRequestsCount }) }}</span>
                <span class="text-gray-950 text-center text-lg font-semibold">$ {{ formatAmount(selectedTotalAmount ? selectedTotalAmount : 0) }}</span>
            </div>

            <div class="flex flex-col items-start gap-2 self-stretch">
                <InputLabel for="remarks">{{ $t('public.remarks') }}</InputLabel>
                <div class="flex items-center gap-2 self-stretch overflow-x-auto">
                    <div v-for="(chip, index) in chips[props.type]?.[approvalAction]" :key="index">
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
                    :placeholder="approvalAction === 'approve' ? chips[props.type]?.approve[0]?.label : chips[props.type]?.reject[0]?.label"
                    :invalid="!!form.errors.remarks"
                    rows="5"
                    cols="30"
                />
                <span class="self-stretch text-gray-500 text-xs">
                    {{ $t( approvalAction === 'approve' ? `public.approve_${props.type}_request_remark_desc` : `public.reject_${props.type}_request_remark_desc`) }}
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
                @click="submit()"
                :disabled="form.processing"
            >
                {{ $t('public.confirm') }}
            </Button>
        </div>
    </template>
</template>