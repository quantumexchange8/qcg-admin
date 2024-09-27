<script setup>
import Button from "@/Components/Button.vue"
import {IconPlus} from "@tabler/icons-vue";
import {ref, watch} from "vue";
import Dialog from "primevue/dialog";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputNumber from "primevue/inputnumber";
import {useForm} from "@inertiajs/vue3";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import Select from "primevue/select";
import RadioButton from "primevue/radiobutton";
import { transactionFormat } from "@/Composables/index.js";

const visible = ref(false);

const closeDialog = () => {
    visible.value = false;
}

const { formatAmount, formatDate } = transactionFormat();

const selectedMode = ref('personal_sales');
const selectedSalesCategory = ref('');
const selectedThreshold = ref('');

const categories = ref([
    'gross_deposit',
    'net_deposit',
    'trade_volume'
]);

const calculationThresholdOptions = ref([
    '50',
    '75',
    '100'
])

const calculationPeriodOptions = ref([
    'every_sunday',
    'every_second_sunday',
    'first_sunday_of_every_month'
])

const form = useForm({
    agent: '',
    sales_calculation_mode: '',
    sales_category: '',
    target_amount: null,
    incentive: null,
    calculation_threshold: '',
    calculation_period: 'first_sunday_of_every_month',
})

const agents = ref();
const getAgents = async () => {
    try {
        const agentResponse = await axios.get('/getAgents');
        agents.value = agentResponse.data.users;
    } catch (error) {
        console.error('Error fetching agents:', error);
    }
};

getAgents();

watch(selectedSalesCategory, () => {
    if (selectedSalesCategory.value === 'trade_volume') {
        selectedThreshold.value = '100'
    }
})

const submitForm = () => {
    form.sales_calculation_mode = selectedMode.value;
    form.sales_category = selectedSalesCategory.value;
    form.calculation_threshold = selectedThreshold.value;

    form.post(route('leaderboard.createIncentiveProfile'), {
        onSuccess: () => {
            form.reset();
            selectedMode.value = 'personal_sales';
            selectedSalesCategory.value = '';
            selectedThreshold.value = '';
            visible.value = false;
        }
    });
}
</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        class='w-full md:w-auto'
        @click="visible = true"
    >
        <IconPlus size="20" color="#ffffff" stroke-width="1.25" />
        {{ $t('public.create_incentive_profile') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.create_incentive_profile')"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <div class="flex flex-col items-center py-4 gap-6 self-stretch md:py-6 md:gap-8">
                <div class="flex flex-col gap-2 items-center self-stretch">
                    <span class="text-gray-950 text-sm font-bold self-stretch w-full">{{ $t('public.agent_information') }}</span>
                    <div class="flex flex-col gap-3 items-center self-stretch md:gap-5">
                        <div class="flex flex-col items-start gap-2 self-stretch">
                            <InputLabel
                                for="agent"
                                :value="$t('public.agent')"
                                :invalid="!!form.errors.agent"
                            />
                            <Select
                                id="agent"
                                v-model="form.agent"
                                :options="agents"
                                filter
                                :filterFields="['name']"
                                optionLabel="name"
                                :placeholder="$t('public.select_agent_placeholder')"
                                class="w-full"
                                scroll-height="236px"
                                :invalid="!!form.errors.agent"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center gap-3">
                                        <div>{{ slotProps.value.name }}</div>
                                    </div>
                                    <span v-else class="text-gray-400">
                                            {{ slotProps.placeholder }}
                                    </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <div>{{ slotProps.option.name }}</div>
                                    </div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.agent" />
                        </div>

                        <div class="flex flex-col items-start gap-2 self-stretch w-full">
                            <InputLabel
                                for="sales_calculation_mode"
                                :value="$t('public.sales_calculation_mode')"
                                :invalid="!!form.errors.sales_calculation_mode"
                            />
                            <div class="w-full flex flex-wrap items-center content-center gap-x-5 gap-y-3 self-stretch">
                                <div class="flex items-center gap-3 text-gray-950">
                                    <RadioButton
                                        v-model="selectedMode"
                                        inputId="personal_sales"
                                        value="personal_sales"
                                        class="w-5 h-5"
                                    />
                                    <label for="personal_sales">{{ $t('public.personal_sales') }}</label>
                                </div>
                                <div class="flex items-center gap-3 text-gray-950">
                                    <RadioButton
                                        v-model="selectedMode"
                                        inputId="group_sales"
                                        value="group_sales"
                                        class="w-5 h-5"
                                    />
                                    <label for="group_sales">{{ $t('public.group_sales') }}</label>
                                </div>
                            </div>
                            <span class="text-gray-500 text-xs">{{ $t('public.sales_calculation_mode_caption') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2 items-center self-stretch md:gap-3">
                    <span class="text-gray-950 text-sm font-bold self-stretch w-full">{{ $t('public.incentive_structure') }}</span>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-5 w-full">
                        <div class="flex flex-col items-start gap-2 self-stretch w-full">
                            <InputLabel
                                for="sales_category"
                                :value="$t('public.sales_category')"
                                :invalid="!!form.errors.sales_category"
                            />
                            <Select
                                v-model="selectedSalesCategory"
                                :options="categories"
                                :placeholder="$t('public.select_category')"
                                class="w-full"
                                scroll-height="236px"
                                :invalid="!!form.errors.sales_category"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <div>{{ $t(`public.${slotProps.value}`) }}</div>
                                    </div>
                                    <span v-else>
                                            {{ slotProps.placeholder }}
                                        </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center">
                                        {{ $t(`public.${slotProps.option}`) }}
                                    </div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.sales_category" />
                            <span class="text-gray-500 text-xs">{{ $t('public.sales_category_caption') }}</span>
                        </div>
                        <div class="flex flex-col items-start gap-2 self-stretch w-full">
                            <InputLabel
                                for="target_amount"
                                :value="$t('public.set_target_amount') + (selectedSalesCategory === 'trade_volume' ? ' (Ł)' : ' ($)')"
                                :invalid="!!form.errors.target_amount"
                            />
                            <InputNumber
                                inputId="target_amount"
                                v-model="form.target_amount"
                                :min="0"
                                :step="100"
                                fluid
                                :invalid="!!form.errors.target_amount"
                                class="w-full"
                                inputClass="py-3 px-4"
                                :placeholder="formatAmount(0)"
                            />
                            <InputError :message="form.errors.target_amount" />
                            <span class="text-gray-500 text-xs">{{ $t('public.set_target_amount_caption') }}</span>
                        </div>

                        <div class="flex flex-col items-start gap-2 self-stretch w-full">
                            <InputLabel
                                for="incentive"
                                :value="$t('public.incentive') + (selectedSalesCategory === 'trade_volume' ? ' ($)' : ' (%)')"
                                :invalid="!!form.errors.incentive"
                            />
                            <InputNumber
                                inputId="incentive"
                                v-model="form.incentive"
                                :min="0"
                                :step="100"
                                fluid
                                :invalid="!!form.errors.incentive"
                                class="w-full"
                                inputClass="py-3 px-4"
                                :placeholder="formatAmount(0)"
                            />
                            <InputError :message="form.errors.incentive" />
                            <span v-if="selectedSalesCategory === 'trade_volume'" class="text-gray-500 text-xs">{{ $t('public.incentive_caption_trade') }}</span>
                            <span v-else class="text-gray-500 text-xs">{{ $t('public.incentive_caption') }}</span>
                        </div>
                        <div class="flex flex-col items-start gap-2 self-stretch w-full">
                            <InputLabel
                                for="calculation_threshold"
                                :value="$t('public.calculation_threshold')"
                                :invalid="!!form.errors.calculation_threshold"
                            />
                            <Select
                                v-model="selectedThreshold"
                                :options="calculationThresholdOptions"
                                :placeholder="$t('public.select_threshold')"
                                class="w-full"
                                scroll-height="236px"
                                :invalid="!!form.errors.calculation_threshold"
                                :disabled="selectedSalesCategory === 'trade_volume'"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <div>{{ slotProps.value }}%</div>
                                    </div>
                                    <span v-else>
                                            {{ slotProps.placeholder }}
                                        </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center">
                                        {{ slotProps.option }}%
                                    </div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.calculation_threshold" />
                            <span class="text-gray-500 text-xs">{{ $t('public.calculation_threshold_caption') }}</span>
                        </div>
                        <div class="flex flex-col items-start gap-2 self-stretch w-full">
                            <InputLabel
                                for="calculationPeriodOptions"
                                :value="$t('public.calculation_period')"
                                :invalid="!!form.errors.calculation_period"
                            />
                            <Select
                                v-model="form.calculation_period"
                                :options="calculationPeriodOptions"
                                :placeholder="$t('public.select_period')"
                                class="w-full"
                                scroll-height="236px"
                                :invalid="!!form.errors.calculation_period"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <div>{{ $t(`public.${slotProps.value}`) }}</div>
                                    </div>
                                    <span v-else>
                                            {{ slotProps.placeholder }}
                                        </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center">
                                        {{ $t(`public.${slotProps.option}`) }}
                                    </div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.calculation_period" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
                <Button
                    type="button"
                    size="base"
                    class="w-full"
                    variant="gray-outlined"
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
                    {{ $t('public.create') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
