<script setup>
import { ref, watch, watchEffect } from "vue";
import InputText from 'primevue/inputtext';
import Button from '@/Components/Button.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { FilterMatchMode } from "@primevue/core/api";
import Loader from "@/Components/Loader.vue";
import Select from "primevue/select";
import {
    IconSearch,
    IconCircleXFilled,
    IconAdjustmentsHorizontal,
} from '@tabler/icons-vue';
import { wTrans } from "laravel-vue-i18n";
import AgentDropdown from '@/Pages/RebateSetting/Partials/AgentDropdown.vue';
import InputNumber from "primevue/inputnumber";
import Empty from "@/Components/Empty.vue";
import Dialog from "primevue/dialog";

const props = defineProps({
    accountTypes: Array,
})

// Emit event back to parent
const emit = defineEmits(['update:accountType']);

const accountTypes = ref();

// Watch for changes in the accountTypes prop
watch(() => props.accountTypes, (newAccountTypes) => {
    accountTypes.value = newAccountTypes;
}, { immediate: true }); // immediate: true will execute the watcher immediately on component mount

const accountType = ref(accountTypes.value[0].value);
const loading = ref(false);
const dt = ref();
const agents = ref();

// Function to fetch agents based on the current account type
const getResults = async (type_id) => {
    loading.value = true;

    try {
        const response = await axios.get(`/rebate/getAgents?type_id=${type_id}`);
        agents.value = response.data;
    } catch (error) {
        console.error('Error getting agents:', error);
    } finally {
        loading.value = false;
    }
};

// Fetch results initially using the current accountType
getResults(accountType.value);

// Watch for changes in accountType and fetch new results accordingly
watch(accountType, (newValue) => {
    emit('update:accountType', newValue);  // Emit the new value to the parent
    getResults(newValue);
});

const changeAgent = async (newAgent) => {
    loading.value = true;

    try {
        const response = await axios.get(`/rebate/changeAgents?id=${newAgent.id}&type_id=${accountType.value}`);
        agents.value = response.data;
    } catch (error) {
        console.error('Error get change:', error);
    } finally {
        loading.value = false;
    }
}

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    upline_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    level: { value: null, matchMode: FilterMatchMode.EQUALS },
    role: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        upline_id: { value: null, matchMode: FilterMatchMode.EQUALS },
        level: { value: null, matchMode: FilterMatchMode.EQUALS },
        role: { value: null, matchMode: FilterMatchMode.EQUALS },
        status: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    upline_id.value = null;
    level.value = null;
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const editingRows = ref([]);
const visible = ref(false);
const agentRebateDetail = ref();
const productDetails = ref();

const openDialog = (agentData) => {
    visible.value = true;
    agentRebateDetail.value = agentData[0][0];
    productDetails.value = agentData[1];
}

const form = useForm({
    rebates: null
});
const onRowEditSave = (event) => {
    let { newData, index } = event;

    agents.value[index] = newData;

    form.rebates = agents.value[index][1];
    form.post(route('rebate.updateRebateAmount'));
};

const submitForm = (submitData) => {
    form.rebates = submitData;
    form.post(route('rebate.updateRebateAmount'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
    });
};

const closeDialog = () => {
    visible.value = false;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults(accountType.value);
    }
});

</script>

<template>
    <div class="flex flex-col items-center justify-center py-5 px-3 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6 overflow-x-auto">
        <DataTable
            v-model:editingRows="editingRows"
            :value="agents"
            tableStyle="min-width: 50rem"
            :globalFilterFields="['name']"
            ref="dt"
            :loading="loading"
            table-style="min-width:fit-content"
            editMode="row"
            :dataKey="agents && agents.length ? agents[0].id : 'id'"
            @row-edit-save="onRowEditSave"
        >
            <template #header>
                <div class="flex flex-col md:flex-row mb-5 gap-3 items-center self-stretch md:justify-between md:mb-6">
                    <div class="relative w-full md:w-60">
                        <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                            <IconSearch size="20" stroke-width="1.25" />
                        </div>
                        <InputText v-model="filters['global'].value" :placeholder="$t('public.search')" size="search" class="font-normal w-full md:w-60" />
                        <div
                            v-if="filters['global'].value !== null"
                            class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                            @click="clearFilterGlobal"
                        >
                            <IconCircleXFilled size="16" />
                        </div>
                    </div>
                    <Select
                        v-model="accountType"
                        :options="accountTypes"
                        optionLabel="name"
                        optionValue="value"
                        class="w-full md:w-60 font-normal"
                    />
                </div>
            </template>
            <template #empty><Empty :title="$t('public.no_agent_found_title')" :message="$t('public.no_agent_found_message')" /></template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loader />
                    <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                </div>
            </template>
            <Column v-if="agents && agents.length" field="level" style="width:10%;" class="px-3">
                <template #header>
                    <span>{{ $t('public.level') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data[0][0].level }}
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="agent" class="w-auto px-3">
                <template #header>
                    <span>{{ $t('public.agent') }}</span>
                </template>
                <template #body="slotProps">
                    <AgentDropdown :agents="slotProps.data[0]" @update:modelValue="changeAgent($event)" class="w-full" />
                </template>
                <template #editor="{ data }">
                    <div class="flex items-center gap-3">
                        <span>{{ data[0][0].name }}</span>
                    </div>
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="1" class="hidden md:table-cell" style="width:10%;">
                <template #header>
                    <span>{{ $t('public.forex') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data[1]['1'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data[1][field]"
                        :min="data[1].downline_forex ? data[1].downline_forex : 0"
                        :max="data[1].upline_forex"
                        :minFractionDigits="2"
                        fluid
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="2" class="hidden md:table-cell" style="width:10%;">
                <template #header>
                    <span>{{ $t('public.stocks') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data[1]['2'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data[1][field]"
                        :min="data[1].downline_stocks ? data[1].downline_stocks : 0"
                        :max="data[1].upline_stocks"
                        :minFractionDigits="2"
                        fluid
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="3" class="hidden md:table-cell" style="width:10%;">
                <template #header>
                    <span>{{ $t('public.indices') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data[1]['3'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data[1][field]"
                        :min="data[1].downline_indices ? data[1].downline_indices : 0"
                        :max="data[1].upline_indices"
                        :minFractionDigits="2"
                        fluid
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="4" class="hidden md:table-cell" style="width:10%;">
                <template #header>
                    <span class="w-12 truncate lg:w-auto">{{ $t('public.commodities') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data[1]['4'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data[1][field]"
                        :min="data[1].downline_commodities ? data[1].downline_commodities : 0"
                        :max="data[1].upline_commodities"
                        :minFractionDigits="2"
                        fluid
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                </template>
            </Column>
            <Column v-if="agents && agents.length" field="5" class="hidden md:table-cell" style="width:10%;">
                <template #header>
                    <span class="w-12 truncate lg:w-auto">{{ $t('public.cryptocurrency') }}</span>
                </template>
                <template #body="slotProps">
                    {{ slotProps.data[1]['5'] }}
                </template>
                <template #editor="{ data, field }">
                    <InputNumber
                        v-model="data[1][field]"
                        :min="data[1].downline_cryptocurrency ? data[1].downline_cryptocurrency : 0"
                        :max="data[1].upline_cryptocurrency"
                        :minFractionDigits="2"
                        fluid
                        size="sm"
                        inputClass="py-2 px-4 w-20"
                    />
                </template>
            </Column>
            <Column v-if="agents && agents.length" :rowEditor="true" style="width: 10%;" class="hidden md:table-cell" bodyStyle="text-align:center">
                <template #roweditoriniticon>
                    <Button
                        variant="gray-text"
                        type="button"
                        size="sm"
                        iconOnly
                        pill
                    >
                        <IconAdjustmentsHorizontal size="16" stroke-width="1.25" />
                    </Button>
                </template>
            </Column>
            <Column v-if="agents && agents.length" style="width: 15%;" class="md:hidden table-cell px-3">
                <template #body="slotProps">
                    <Button
                        variant="gray-text"
                        type="button"
                        size="sm"
                        iconOnly
                        pill
                        @click="openDialog(slotProps.data)"
                    >
                        <IconAdjustmentsHorizontal size="16" stroke-width="1.25" />
                    </Button>
                </template>
            </Column>
        </DataTable>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.edit_rebate_details')"
        class="dialog-xs"
    >
        <div class="flex flex-col py-4 md:py-6 gap-6 items-center self-stretch">
            <!-- agent details -->
            <div class="w-full flex items-center gap-3">
                <div class="w-full flex flex-col items-start">
                    <div class="w-full truncate font-semibold text-gray-950">
                        {{ agentRebateDetail.name }}
                    </div>
                    <div class="w-full truncate text-gray-500 text-sm">
                        {{ agentRebateDetail.email }}
                    </div>
                </div>
            </div>

            <!-- rebate allocation -->
            <div class="w-full flex flex-col items-center self-stretch">
                <div class="flex justify-between items-center py-3 self-stretch border-b border-gray-100 bg-gray-50">
                    <div
                        class="flex items-center w-full px-3 text-gray-950 text-xs font-semibold uppercase">
                        {{ $t('public.product') }}
                    </div>
                    <div
                        class="flex items-center px-3 w-full text-gray-950 text-xs font-semibold uppercase">
                        {{ $t('public.upline_rebate') }} ($)
                    </div>
                    <div
                        class="flex items-center px-3 w-full text-gray-950 text-xs font-semibold uppercase">
                        {{ $t('public.rebate') }} / Ł ($)
                    </div>
                </div>

                <div class="flex flex-col items-center self-stretch max-h-[400px] overflow-y-auto">
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full">
                            {{ $t('public.forex') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_forex }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['1']"
                                :min="productDetails.downline_forex ? productDetails.downline_forex : 0"
                                :max="productDetails.upline_forex"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full">
                            {{ $t('public.stocks') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_stocks }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['2']"
                                :min="productDetails.downline_stocks ? productDetails.downline_stocks : 0"
                                :max="productDetails.upline_stocks"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full">
                            {{ $t('public.indices') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_indices }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['3']"
                                :min="productDetails.downline_indices ? productDetails.downline_indices : 0"
                                :max="productDetails.upline_indices"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full truncate">
                            {{ $t('public.commodities') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_commodities }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['4']"
                                :min="productDetails.downline_commodities ? productDetails.downline_commodities : 0"
                                :max="productDetails.upline_commodities"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between py-2 items-center self-stretch text-gray-950">
                        <div class="px-3 w-full truncate">
                            {{ $t('public.cryptocurrency') }}
                        </div>
                        <div class="px-3 w-full">
                            {{ productDetails.upline_cryptocurrency }}
                        </div>
                        <div class="px-3 w-full">
                            <InputNumber
                                v-model="productDetails['5']"
                                :min="productDetails.downline_cryptocurrency ? productDetails.downline_cryptocurrency : 0"
                                :max="productDetails.upline_cryptocurrency"
                                :minFractionDigits="2"
                                fluid
                                size="sm"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
            <Button
                type="button"
                variant="gray-tonal"
                class="w-full md:w-[120px]"
                @click="closeDialog"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary-flat"
                class="w-full md:w-[120px]"
                @click="submitForm(productDetails)"
                :disabled="form.processing"
            >
                {{ $t('public.save') }}
            </Button>
        </div>
    </Dialog>

</template>
