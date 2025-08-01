<script setup>
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff, IconCopy } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted, onUnmounted } from "vue";
import {usePage} from "@inertiajs/vue3";
import Loader from "@/Components/Loader.vue";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import Button from '@/Components/Button.vue';
import Select from "primevue/select";
import { FilterMatchMode } from '@primevue/core/api';
import Empty from "@/Components/Empty.vue";
import { transactionFormat } from "@/Composables/index.js";
import dayjs from "dayjs";
import {useLangObserver} from "@/Composables/localeObserver.js";
import { trans, wTrans } from "laravel-vue-i18n";

const { formatDate } = transactionFormat();

const categories = [
    { name: wTrans('public.profit_rate'), value: 'profit_rate' },
    { name: wTrans('public.trade_lot'), value: 'trade_lot' },
    { name: wTrans('public.trade_position'), value: 'trade_position' },
];

const selectedCategory = ref(null);
const loading = ref(false);
const competitions = ref();

const dt = ref(null);
const filteredValue = ref();

const getResults = async (category = null) => {
    loading.value = true;

    try {
        const params = new URLSearchParams();
        // Create the base URL with the type parameter directly in the URL
        if (category) {
            params.append('category', category);
        }

        const response = await axios.get('/competition/getCompetitionHistory', { params });

        competitions.value = response.data.competitions;
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

watch([selectedCategory], ([newCategory]) => {
    getResults(newCategory);
});


const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    category: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };

    selectedCategory.value = null;
    filteredValue.value = null; 
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
};
</script>

<template>
    <div class="flex flex-col justify-center items-center py-5 px-3 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
        <div class="flex items-center self-stretch">
            <span class="font-semibold text-gray-950">{{ $t('public.history') }}</span>
        </div>
        <DataTable
            v-model:filters="filters"
            :value="competitions"
            :paginator="competitions?.length > 0 && filteredValue?.length > 0"
            removableSort
            :rows="100"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            :currentPageReportTemplate="$t('public.paginator_caption')"
            :globalFilterFields="['name']"
            ref="dt"
            :loading="loading"
            @filter="handleFilter"
        >
            <template #header>
                <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 items-center gap-3 self-stretch md:gap-2">
                        <Select
                            v-model="selectedCategory"
                            :options="categories"
                            optionLabel="name"
                            optionValue="value"
                            :placeholder="$t('public.filter_by_category')"
                            class="w-full md:max-w-60 font-normal"
                            scroll-height="236px"
                        />
                        <div class="relative block col-span-1">
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
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:flex md:flex-row gap-3 md:gap-2 w-full md:w-auto shrink-0">
                        <Button
                            type="button"
                            variant="error-outlined"
                            size="base"
                            class='col-span-1 md:w-auto'
                            @click="clearFilter"
                        >
                            <IconFilterOff size="20" stroke-width="1.25" />
                            {{ $t('public.clear') }}
                        </Button>
                    </div>
                </div>
            </template>
            <template #empty>
                <Empty 
                    :title="$t('public.empty_competition_history_title')" 
                    :message="$t('public.empty_competition_history_message')" 
                />
            </template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loader />
                    <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                </div>
            </template>
            <template v-if="competitions?.length > 0 && filteredValue?.length > 0">
                <Column field="name" sortable :header="$t('public.name')" class="">
                    <template #body="slotProps">
                        <div class="text-gray-950 text-sm">
                            {{ slotProps.data.name[locale] }}
                        </div>
                    </template>
                </Column>
                <Column field="category" :header="$t('public.category')" class="">
                    <template #body="slotProps">
                        <div class="text-gray-950 text-sm">
                            {{ $t(`public.${slotProps.data.category}`) }}
                        </div>
                    </template>
                </Column>
                <Column field="period" sortable :header="$t('public.period')" class="">
                    <template #body="slotProps">
                        <div class="text-gray-950 text-sm max-w-full">
                            {{ formatDate(slotProps.data.start_date) }} - {{ formatDate(slotProps.data.end_date) }}
                        </div>
                    </template>
                </Column>
                <Column field="total_points" :header="$t('public.trade_points_paid') + ' (tp)'" class="">
                    <template #body="slotProps">
                        <div class="text-gray-950 text-sm max-w-full">
                            {{ slotProps.data.total_points }}
                        </div>
                    </template>
                </Column>
                <Column field="action" headless class="">
                    <template #body="slotProps">

                    </template>
                </Column>
            </template>
        </DataTable>
    </div>
</template>