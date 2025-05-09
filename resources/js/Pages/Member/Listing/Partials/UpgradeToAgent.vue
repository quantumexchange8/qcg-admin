<script setup>
import {IconArrowRight} from "@tabler/icons-vue"
import {computed, ref, watch} from "vue";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import {transactionFormat} from "@/Composables/index.js";
import InputError from "@/Components/InputError.vue";
import InputNumber from "primevue/inputnumber";
import {useForm} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import Select from "primevue/select";

const props = defineProps({
    member: Object
})

const availableUpline = ref(null);
const newIdNumber = ref('');
const accountTypes = ref([]);
const selectedAccountType = ref(null);
const rebateDetails = ref([]);
const accountRebateData = ref({}); // Stores rebate amounts for each account_type_id
const { formatAmount } = transactionFormat();
const emit = defineEmits(['update:visible'])

// Fetch and initialize data
const getAvailableUplineData = async () => {
    try {
        const response = await axios.get(`/member/getAvailableUplineData?user_id=${props.member.id}`);
        availableUpline.value = response.data.availableUpline;
        rebateDetails.value = response.data.rebateDetails;
        accountTypes.value = response.data.accountTypeSel;

        selectedAccountType.value = accountTypes.value[0];
        newIdNumber.value = props.member.id_number.replace(/^M/, 'A');

        initializeMemberRebateAmount();
    } catch (error) {
        console.error('Error fetching available upline data:', error);
    }
};

// Initialize or reset rebate amounts for the selected account type
const initializeMemberRebateAmount = () => {
    const currentAccountTypeId = selectedAccountType.value?.id;

    if (!currentAccountTypeId) return;

    const currentRebateDetails = rebateDetails.value.filter(detail => detail.account_type_id === currentAccountTypeId);

    if (!accountRebateData.value[currentAccountTypeId]) {
        accountRebateData.value[currentAccountTypeId] = currentRebateDetails.reduce((acc, detail) => {
            acc[detail.id] = {
                amount: acc[detail.id]?.amount || 0,
                symbol_group_id: detail.symbol_group_id
            };
            return acc;
        }, {});
    }
};

// Watch for changes in selectedAccountType to update data
watch(selectedAccountType, (newType, oldType) => {
    if (oldType) {
        // Save previous data before changing
        accountRebateData.value[oldType.id] = accountRebateData.value[oldType.id] || {};
    }
    initializeMemberRebateAmount();
});

const filteredRebateDetails = computed(() => {
    const currentAccountTypeId = selectedAccountType.value?.id;
    return rebateDetails.value.filter(detail => detail.account_type_id === currentAccountTypeId);
});

// Prepare data for submission
const prepareSubmissionData = () => {
    return Object.entries(accountRebateData.value).flatMap(([accountTypeId, rebates]) =>
        Object.entries(rebates).map(([rebateDetailId, data]) => ({
            account_type_id: accountTypeId,
            rebate_detail_id: rebateDetailId,
            amount: data.amount || 0,
            symbol_group_id: data.symbol_group_id
        }))
    );
};

const form = useForm({
    user_id: props.member.id,
    id_number: '',
    amounts: {}
});

const submitForm = () => {
    form.id_number = newIdNumber.value;
    form.amounts = prepareSubmissionData();
    form.post(route('member.upgradeAgent'), {
        onSuccess: () => {
            closeDialog();
        }
    });
};

getAvailableUplineData();

const closeDialog = () => {
    emit('update:visible', false);
}
</script>

<template>
    <div class="flex flex-col pt-4 gap-6 items-center self-stretch md:pt-6 md:gap-8">
        <div class="flex flex-col md:flex-row justify-center items-center gap-3 self-stretch md:gap-5">
            <!-- member -->
            <div class="flex flex-col gap-3 items-center self-stretch w-full">
                <div class="self-stretch text-sm text-gray-950 font-bold">
                    {{ $t('public.member') }}
                </div>
                <div class="flex flex-col justify-center items-start p-3 self-stretch bg-gray-50">
                    <span class="text-gray-950 font-semibold max-w-[220px] truncate">{{ member.first_name }}</span>
                    <div class="text-gray-500 text-sm max-w-[220px] truncate flex gap-1 items-center self-stretch">
                        <span>{{ member.id_number }}</span>
                        →
                        <span>{{ newIdNumber }}</span>
                    </div>
                </div>
            </div>

            <!-- agent -->
            <div class="flex flex-col gap-3 items-center self-stretch w-full">
                <div class="self-stretch text-sm text-gray-950 font-bold">
                    {{ $t('public.upline') }}
                </div>
                <div
                    v-if="availableUpline"
                    class="flex flex-col justify-center items-start p-3 self-stretch bg-gray-50"
                >
                    <span class="text-gray-950 font-semibold max-w-[220px] truncate">{{ availableUpline.first_name }}</span>
                    <span class="text-gray-500 text-sm max-w-[220px] truncate">{{ availableUpline.id_number }}</span>
                </div>

                <!-- lazy loading -->
                <div v-else class="flex gap-3 items-center self-stretch">
                    <div class="flex flex-col items-start animate-pulse">
                        <div class="h-2.5 bg-gray-200 rounded-full w-48 my-2"></div>
                        <div class="h-2 bg-gray-200 rounded-full w-20 mb-1"></div>
                    </div>
                </div>
            </div>
        </div>

        <form
            class="w-full space-y-3"
        >
            <!-- account type -->
            <div class="flex flex-col items-start gap-1">
                <Select
                    v-model="selectedAccountType"
                    id="accountTypes"
                    :options="accountTypes"
                    class="md:max-w-[240px] w-full"
                    optionLabel="name"
                    :disabled="!selectedAccountType"
                />
                <InputError :message="form.errors.account_type_id" />
            </div>

            <!-- rebate allocate -->
            <div class="flex flex-col items-center self-stretch">
                <div class="flex justify-center items-center w-full self-stretch py-2 text-gray-700 bg-gray-50 border-b border-gray-100">
                    <span class="uppercase text-xs font-bold px-2 w-full">{{ $t('public.product') }}</span>
                    <span class="uppercase text-xs font-bold px-2 w-full">{{ $t('public.upline_rebate') }}&nbsp;($)</span>
                    <span class="uppercase text-xs font-bold px-2 w-full">{{ $t('public.rebate') }}&nbsp;/&nbsp;Ł&nbsp;($)</span>
                </div>

                <!-- symbol groups -->
                <div
                    v-if="rebateDetails?.length > 0"
                    v-for="(rebateDetail, index) in filteredRebateDetails"
                    class="flex justify-center items-center w-full self-stretch py-2 text-gray-950"
                >
                    <div class="text-sm px-2 w-full truncate">{{ $t(`public.${rebateDetail.symbol_group.display}`) }}</div>
                    <div class="text-sm px-2 w-full">{{ formatAmount(rebateDetail.amount, 0) }}</div>
                    <div class="text-sm px-2 w-full">
                        <InputNumber
                            v-model="accountRebateData[selectedAccountType.id][rebateDetail.id].amount"
                            :min="0"
                            :max="Number(rebateDetail.amount)"
                            :minFractionDigits="2"
                            fluid
                            :placeholder="formatAmount(rebateDetail.amount, 0)"
                            :invalid="!!form.errors[`amounts.${index}`]"
                            inputClass="py-3 px-4"
                        />
                        <InputError :message="form.errors[`amounts.${index}`]" />
                    </div>
                </div>

                <!-- lazy loading -->
                <div
                    v-else
                    v-for="index in 5"
                    class="flex items-center w-full self-stretch py-4 text-gray-950"
                >
                    <div class="w-full">
                        <div class="h-2.5 bg-gray-200 rounded-full w-20 md:w-36 mt-1 mb-1.5"></div>
                    </div>
                    <div class="w-full">
                        <div class="h-2.5 bg-gray-200 rounded-full w-10 md:w-36 mt-1 mb-1.5"></div>
                    </div>
                    <div class="w-full">
                        <div class="h-2.5 bg-gray-200 rounded-full w-10 mt-1 mb-1.5"></div>
                    </div>
                </div>

                <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
                    <Button
                        type="button"
                        variant="gray-outlined"
                        size="base"
                        class="w-full"
                        @click="closeDialog"
                    >
                        {{ $t('public.cancel') }}
                    </Button>
                    <Button
                        variant="primary-flat"
                        size="base"
                        class="w-full"
                        @click="submitForm"
                        :disabled="form.processing"
                    >
                        {{ $t('public.upgrade') }}
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
