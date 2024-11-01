<script setup>
import { usePage } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconFilterOff } from "@tabler/icons-vue";
import { ref, watch, watchEffect } from "vue";
import Loader from "@/Components/Loader.vue";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import Button from '@/Components/Button.vue';
import Select from "primevue/select";
import { FilterMatchMode } from '@primevue/core/api';
import Empty from "@/Components/Empty.vue";
import { transactionFormat } from "@/Composables/index.js";
import dayjs from "dayjs";

const { formatAmount } = transactionFormat();

const props = defineProps({
    accountTypes: Array,
});

const loading = ref(false);
const dt = ref(null);

const accounts = ref();
const filteredValue = ref();
const accountTypes = ref(props.accountTypes);
const accountType = ref(null)

const emit = defineEmits(['update:filteredValue']);

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/member/getAccountListingData');
        accounts.value = response.data.accounts;

    } catch (error) {
        console.error('Error In Fetch Data:', error);
    } finally {
        loading.value = false;
    }

};

getResults();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    account_type_id: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        email: { value: null, matchMode: FilterMatchMode.CONTAINS },
        account_type_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    accountType.value = null;
    filteredValue.value = null;
};

// Watch for changes in accountType and update the filter
watch(accountType, (newVal) => {
    filters.value['account_type_id'].value = newVal ? newVal : null;
});

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
    emit('update:filteredValue', filteredValue.value);
};

</script>

<template>
    <DataTable
        v-model:filters="filters"
        :value="accounts"
        :paginator="accounts?.length > 0 && filteredValue?.length > 0"
        removableSort
        :rows="10"
        :rowsPerPageOptions="[10, 20, 50, 100]"
        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        :currentPageReportTemplate="$t('public.paginator_caption')"
        :globalFilterFields="['name', 'email', 'balance', 'equity']"
        ref="dt"
        :loading="loading"
        selectionMode="single"
        @filter="handleFilter"
    >
        <template #header>
            <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
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
                    <Select
                        v-model="accountType"
                        :options="accountTypes"
                        filter
                        :filterFields="['name']"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.filter_by_account_type')"
                        class="w-full md:w-60 font-normal"
                        scroll-height="236px"
                    />
                </div>
                <Button
                    type="button"
                    variant="error-outlined"
                    size="base"
                    class='w-full md:w-auto'
                    @click="clearFilter"
                >
                    <IconFilterOff size="20" stroke-width="1.25" />
                    {{ $t('public.clear') }}
                </Button>
            </div>
        </template>
        <template #empty>
            <Empty 
                :title="$t('public.empty_agent_title')" 
                :message="$t('public.empty_agent_message')" 
            />
        </template>
        <template #loading>
            <div class="flex flex-col gap-2 items-center justify-center">
                <Loader />
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>
        </template>
        <template v-if="accounts?.length > 0 && filteredValue?.length > 0">
            <Column field="name" sortable :header="$t('public.name')" class="hidden md:table-cell w-[20%] max-w-0">
                <template #body="slotProps">
                    <div class="flex flex-col items-start max-w-full">
                        <div class="font-semibold truncate max-w-full">
                            {{ slotProps.data.name }}
                        </div>
                        <div class="text-gray-500 text-xs truncate max-w-full">
                            {{ slotProps.data.email }}
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="deleted_at" :header="$t('public.deleted_time')" sortable class="w-[20%] max-w-0" headerClass="hidden md:table-cell">
                <template #body="slotProps">
                    <div class="flex flex-col items-start max-w-full">
                        <div class="text-gray-950 text-sm font-semibold truncate max-w-full md:hidden">
                            {{ slotProps.data.meta_login }}
                        </div>
                        <div class="flex items-center gap-1 truncate max-w-full">
                            <div class="text-gray-500 text-xs md:hidden">{{ $t('public.deleted_time') }}:</div>
                            <div class="text-gray-700 md:text-gray-950 text-xs md:text-sm font-medium md:font-normal truncate max-w-full">
                                {{ dayjs(slotProps.data.deleted_at).format('YYYY/MM/DD HH:mm:ss') }}
                            </div>
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="meta_login" :header="$t('public.account')" sortable class="hidden md:table-cell w-[20%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ slotProps.data.meta_login }}
                    </div>
                </template>
            </Column>
            <Column field="balance" :header="$t('public.balance')" sortable class="hidden md:table-cell w-[20%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ formatAmount(slotProps.data.balance) }}
                    </div>
                </template>
            </Column>
            <Column field="equity" :header="$t('public.equity')" sortable class="hidden md:table-cell w-[20%]">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm">
                        {{ formatAmount(slotProps.data.equity) }}
                    </div>
                </template>
            </Column>
        </template>
    </DataTable>
</template>