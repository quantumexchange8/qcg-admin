<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { transactionFormat } from "@/Composables/index.js";
import RadioButton from 'primevue/radiobutton';
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { useForm } from "@inertiajs/vue3";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";
import Chip from "primevue/chip";
import Select from "primevue/select";
import Button from "@/Components/Button.vue";

const props = defineProps({
    type: String,
    member: Object,
    account: Object,
});

const isLoading = ref(false);
const walletData = ref(null);
const accountData = ref(null);
const selectedAccount = ref(null);
const { formatAmount } = transactionFormat();
const emit = defineEmits(['update:visible']);
const getResults = async () => {
    isLoading.value = true;

    try {
        let response;
        if (props.account) {
            response = await axios.get(`/updateAccountData?meta_login=${props.account.meta_login}`);
            const updatedAccount = response.data;
            accountData.value = [updatedAccount];
            selectedAccount.value = updatedAccount;
        } else if (props.type == 'rebate') {
            response = await axios.get(`/getWalletData?user_id=${props.member.id}`);
            walletData.value = response.data.walletData;
        } else {
            response = await axios.get(`/getTradingAccountData?user_id=${props.member.id}`);
            accountData.value = response.data.accountData;
            if (accountData.value.length > 0) {
                selectedAccount.value = accountData.value[0];
            }
        }
    } catch (error) {
        console.error('Error updating data:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(getResults);

const form = useForm({
    user_id: props.member ? props.member.id : '',
    meta_login: props.account ? props.account.meta_login : '',
    action: '',
    amount: 0,
    remarks: '',
    type: props.type,
})

// Watch for changes in selectedAccount and update form.meta_login
watch(selectedAccount, (newValue) => {
    if (newValue) {
        form.meta_login = newValue.meta_login;
    }
});

const radioOptions = computed(() => {
    const options = {
        rebate: [
            { label: 'rebate_in', value: 'rebate_in' },
            { label: 'rebate_out', value: 'rebate_out' }
        ],
        account_balance: [
            { label: 'balance_in', value: 'balance_in' },
            { label: 'balance_out', value: 'balance_out' }
        ],
        account_credit: [
            { label: 'credit_in', value: 'credit_in' },
            { label: 'credit_out', value: 'credit_out' }
        ]
    };

    // Return the correct options based on type
    return options[props.type] || [];
});

// Computed Property for Chips
const chips = computed(() => {
    const chipsMapping = {
        rebate: [
            { label: 'Fix rebate balance' },
            { label: '修改返傭餘額' },
        ],
        account_balance: [
            { label: 'Fix account balance' },
            { label: '修改帳戶餘額' },
        ],
        account_credit: [
            { label: 'Fix account credit' },
            { label: '修改信用餘額' },
        ],
    };

    return chipsMapping[props.type] || [];
});

// Computed Property for Placeholder
const placeholderText = computed(() => {
    const placeholderMapping = {
        rebate: 'Rebate balance adjustment',
        account_balance: 'Account balance adjustment',
        account_credit: 'Account credit adjustment',
    };

    return placeholderMapping[props.type] || 'Enter remarks here';
});

const handleChipClick = (label) => {
    form.remarks = label;
};

const closeDialog = () => {
    emit('update:visible', false);
}

const submitForm = () => {
    if (form.remarks === '') {
        form.remarks = placeholderText.value;
    }

    // Determine the correct route based on the type
    const routeName = props.type === 'rebate' ? 'member.walletAdjustment' : 'member.accountAdjustment';

    form.post(route(routeName), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
    });
}
</script>

<template>
    <form>
        <div class="flex flex-col gap-5 items-center self-stretch py-4 md:py-6">
            <div class="flex flex-col justify-center items-start self-stretch">
                <span class="text-gray-950 font-semibold w-full truncate">{{ props.member ? props.member.name : props.account.name }}</span>
                <span class="text-gray-500 text-sm w-full truncate">{{ props.member ? props.member.email : props.account.email }}</span>
            </div>
            <div class="flex flex-col justify-center items-center px-8 py-3 gap-1 self-stretch bg-gray-100">
                <div class="text-gray-500 text-center text-xs">
                    {{
                        type === 'rebate' 
                        ? $t('public.rebate_balance') 
                        : `#${selectedAccount ? selectedAccount.meta_login : '' } - ${type === 'account_balance' ? $t('public.available_account_balance') : $t('public.current_account_credit')}`
                    }}
                </div>
                <div v-if="isLoading" class="animate-pulse">
                    <div class="h-3 bg-gray-400 rounded-full w-28 my-1"></div>
                </div>
                <div v-else class="text-gray-950 text-center text-lg font-semibold">
                    <span >
                        $
                        {{
                            type === 'rebate' 
                            ? formatAmount(walletData ? walletData.balance : 0) 
                            : type === 'account_balance' ? formatAmount(selectedAccount ? selectedAccount.balance : 0) : formatAmount(selectedAccount ? selectedAccount.credit : 0)
                        }}
                    </span>
                </div>
            </div>

            <!-- trading account -->
            <div v-if="props.type !== 'rebate' && !props.account" class="flex flex-col items-start gap-2 self-stretch">
                <InputLabel for="trading_account" :value="$t('public.trading_account')" :invalid="!!form.errors.meta_login" />
                <Select
                    v-model="selectedAccount"
                    :options="accountData"
                    filter
                    :filterFields="['meta_login']"
                    optionLabel="meta_login"
                    class="w-full"
                    scroll-height="236px"
                >
                </Select>

                <InputError :message="form.errors.meta_login" />
            </div>
            <!-- action -->
            <div class="flex flex-col items-start gap-2 self-stretch">
                <InputLabel for="action" :value="$t('public.action')" :invalid="!!form.errors.action" />
                <div class="flex items-center gap-4 md:gap-5">
                    <div
                        v-for="(action, index) in radioOptions"
                        :key="index"
                        class="flex items-center gap-2 text-gray-950"
                    >
                        <RadioButton
                            v-model="form.action"
                            :inputId="action.value"
                            :name="action.value"
                            :value="action.value"
                            class="w-5 h-5"
                        />
                        <label :for="action.value">{{ $t(`public.${action.label}`) }}</label>
                    </div>
                </div>
                <InputError :message="form.errors.action" />
            </div>

            <!-- amount -->
            <div class="flex flex-col items-start gap-2 self-stretch">
                <InputLabel for="amount" :value="$t('public.amount')" :invalid="!!form.errors.amount" />
                <InputNumber
                    v-model="form.amount"
                    inputId="currency-us"
                    prefix="$ "
                    class="w-full"
                    inputClass="py-3 px-4"
                    :min="0"
                    :step="100"
                    :minFractionDigits="2"
                    fluid
                    autofocus
                    :invalid="!!form.errors.amount"
                />
                <InputError :message="form.errors.amount" />
            </div>

            <!-- remarks -->
            <div class="flex flex-col items-start gap-2 self-stretch">
                <InputLabel for="remarks" :invalid="!!form.errors.remarks">{{ `${$t('public.remarks')}&nbsp;(${$t('public.optional')})` }}</InputLabel>
                <div class="flex items-center content-center gap-3 self-stretch flex-wrap">
                    <div v-for="(chip, index) in chips" :key="index">
                        <Chip
                            :label="chip.label"
                            :class="{
                                    'border-primary-600 bg-primary-50 text-primary-600': form.remarks === chip.label,
                                }"
                            @click="handleChipClick(chip.label)"
                        />
                    </div>
                </div>
                <Textarea
                    id="remarks"
                    type="text"
                    class="w-full h-20"
                    v-model="form.remarks"
                    :placeholder="placeholderText"
                    :invalid="!!form.errors.remarks"
                    rows="5"
                    cols="30"
                />
                <InputError :message="form.errors.remarks" />
            </div>
        </div>

        <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
            <Button
                variant="gray-tonal"
                class="w-full"
                :disabled="form.processing"
                @click.prevent="closeDialog"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary-flat"
                class="w-full"
                :disabled="form.processing || isLoading || selectedAccount == null && walletData == null"
                @click.prevent="submitForm"
            >
                {{ $t('public.confirm') }}
            </Button>
        </div>
    </form>
</template>

<!-- 
For wallet adjustment (rebate type)
<Adjustment
  type="rebate"
  :member="member"
/>

For account balance adjustment
<Adjustment
  type="account_balance"
  :member="member"
/>

For account credit adjustment
<Adjustment
  type="account_credit"
  :member="member"
/>

For account balance adjustment with provided account data
<Adjustment
  type="account_balance"
  :account="account"
/>

For account credit adjustment with provided account data
<Adjustment
  type="account_credit"
  :account="account"
/> -->
