<script setup>
import { IconAlertCircleFilled, IconTrashX } from '@tabler/icons-vue';
import { ref, h, computed, watchEffect } from 'vue';
import Empty from '@/Components/Empty.vue';
import { generalFormat, transactionFormat } from "@/Composables/index.js";
import { usePage } from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import Loader from "@/Components/Loader.vue";

const props = defineProps({
    user_id: Number
})

const { formatAmount } = transactionFormat();
const { formatRgbaColor } = generalFormat()
const tradingAccounts = ref();
const isLoading = ref(false);

const getTradingAccounts = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get(`/member/getTradingAccounts?id=${props.user_id}`);

        // Check if the tradingAccounts array is empty
        if (response.data.tradingAccounts && response.data.tradingAccounts.length > 0) {
            tradingAccounts.value = response.data.tradingAccounts;
        } else {
            // Handle the case where tradingAccounts is empty
            tradingAccounts.value = [];
            // console.warn('No trading accounts found for the user.');
        }
    } catch (error) {
        console.error('Error get trading accounts:', error);
    } finally {
        isLoading.value = false;
    }
};
getTradingAccounts();

// Function to check if an account is inactive for 90 days
function isInactive(date) {
  const updatedAtDate = new Date(date);
  const currentDate = new Date();

  // Get only the date part (remove time)
  const updatedAtDay = new Date(updatedAtDate.getFullYear(), updatedAtDate.getMonth(), updatedAtDate.getDate());
  const currentDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate());

  // Calculate the difference in days by direct subtraction
  const diffDays = (currentDay - updatedAtDay) / (1000 * 60 * 60 * 24);

  // Determine if inactive (more than 90 days)
  return diffDays > 90;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getTradingAccounts();
    }
});

const confirm = useConfirm();

const requireConfirmation = (action_type, meta_login) => {
    const messages = {
        delete_trading_account: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_trading_account'),
            message: trans('public.delete_trading_account_desc' , {account: `${meta_login}`}),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.visit(route('member.accountDelete'), {
                    method: 'delete',
                    data: {
                        id: props.member.id,
                    },
                })
            }
        },
    };

    const { group, color, icon, header, message, cancelButton, acceptButton, action } = messages[action_type];

    confirm.require({
        group,
        color,
        icon,
        header,
        message,
        cancelButton,
        acceptButton,
        accept: action
    });
};

</script>

<template>
    <div v-if="tradingAccounts?.length <= 0" class="flex flex-col justify-center items-center h-[200px]">
        <Empty :message="$t('public.empty_trading_account_message')">
            <template #image></template>
        </Empty>
    </div>
    <div v-else-if="isLoading" class="flex flex-col gap-2 items-center justify-center">
        <Loader />
        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
    </div>

    <div v-else class="grid md:grid-cols-2 gap-3 md:gap-5">
        <div
            v-for="tradingAccount in tradingAccounts" :key="tradingAccount.id"
            class="flex flex-col justify-center items-center px-3 py-3 gap-3 rounded-lg border-l-[12px] bg-white shadow-card"
            :style="{'borderColor': `#${tradingAccount.account_type_color}`}"
        >
            <div class="flex items-center gap=5 self-stretch">
                <div class="w-full flex items-center content-center gap-x-4 gap-y-2 flex-wrap">
                    <div class="text-gray-950 font-semibold md:text-lg">#{{ tradingAccount.meta_login }}</div>
                    <div
                        v-if="tradingAccount.account_type"
                        class="flex px-2 py-1 justify-center items-center text-white text-xs font-semibold hover:-translate-y-1 transition-all duration-300 ease-in-out rounded-sm"
                        :style="{
                            backgroundColor: `#${tradingAccount.account_type_color}`,
                        }"
                    >
                        {{ $t('public.' + tradingAccount.account_type) }}
                    </div>
                    <div v-if="isInactive(tradingAccount.updated_at)" class="text-error-500">
                        <IconAlertCircleFilled :size="20" stroke-width="1.25" />
                    </div>
                </div>
                <Button
                    type="button"
                    variant="error-text"
                    size="sm"
                    pill
                    iconOnly
                    @click="requireConfirmation('delete_trading_account', tradingAccount.meta_login)"
                >
                    <IconTrashX size="16" />
                </Button>
            </div>
            <div class="grid grid-cols-2 gap-2 self-stretch md:grid-cols-4">
                <div class="w-full flex flex-col items-center gap-1 flex-grow">
                    <span class="self-stretch text-gray-500 text-xs">{{ $t('public.balance') }}:</span>
                    <span class="self-stretch text-gray-950 text-sm font-medium truncate w-full">$&nbsp;{{ formatAmount(tradingAccount.balance) }}</span>
                </div>
                <div class="w-full flex flex-col items-center gap-1 flex-grow">
                    <span class="self-stretch text-gray-500 text-xs">{{ $t('public.equity') }}:</span>
                    <span class="self-stretch text-gray-950 text-sm font-medium truncate w-full">$&nbsp;{{ formatAmount(tradingAccount.equity) }}</span>
                </div>
                <div class="w-full flex flex-col items-center gap-1 flex-grow">
                    <span class="self-stretch text-gray-500 text-xs">{{ tradingAccount.account_type === 'premium_account' ? $t('public.pamm') : $t('public.credit') }}:</span>
                    <div class="self-stretch text-gray-950 text-sm font-medium truncate w-full">
                        <span v-if="tradingAccount.account_type === 'premium_account'">{{ tradingAccount.asset_master_name ?? '-' }}</span>
                        <span v-else>$&nbsp;{{ formatAmount(tradingAccount.credit) }}</span>
                    </div>
                </div>
                <div class="w-full flex flex-col items-center gap-1 flex-grow">
                    <span class="self-stretch text-gray-500 text-xs">{{ tradingAccount.account_type === 'premium_account' ? $t('public.mature_in') : $t('public.leverage') }}:</span>
                    <div class="self-stretch text-gray-950 text-sm font-medium truncate w-full">
                        <span v-if="tradingAccount.account_type === 'premium_account'">{{ tradingAccount.asset_master_name ? tradingAccount.remaining_days + ' ' + $t('public.days') : '-' }}</span>
                        <span v-else>{{ `1:${tradingAccount.leverage}` }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
