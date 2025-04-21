<script setup>
import { ref, watch, watchEffect } from "vue";
import { usePage } from "@inertiajs/vue3";
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import Loader from "@/Components/Loader.vue";
import debounce from "lodash/debounce.js";
import Select from "primevue/select";
import Button from '@/Components/Button.vue';
import { FilterMatchMode } from '@primevue/core/api';
import { IconSearch, IconCircleXFilled, IconPlus, IconInfoCircle, IconX } from "@tabler/icons-vue";
import { trans, wTrans } from "laravel-vue-i18n";
import Empty from "@/Components/Empty.vue";
import dayjs from "dayjs";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import StatusBadge from '@/Components/StatusBadge.vue';
import Column from "primevue/column";
import ColumnGroup from "primevue/columngroup";
import Row from "primevue/row";
import Tag from 'primevue/tag';
import CreateAnnouncement from "./Partials/CreateAnnouncement.vue";

const statusOption = [
    { name: wTrans('public.all'), value: null },
    { name: wTrans('public.active'), value: 'active' },
    { name: wTrans('public.inactive'), value: 'inactive' },
    { name: wTrans('public.scheduled'), value: 'scheduled' },
    { name: wTrans('public.draft'), value: 'draft' },
    { name: wTrans('public.expired'), value: 'expired' }
];

const announcements = ref();
const loading = ref(false);
const filteredValue = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    title: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
};

const getResults = async () => {
    loading.value = true;

    try {
        // Create the base URL with the type parameter directly in the URL
        let url = `/highlights/getAnnouncement`;

        // if (selectedMonth) {
        //     const formattedMonth = selectedMonth === 'select_all' 
        //         ? 'select_all' 
        //         : dayjs(selectedMonth, 'DD MMMM YYYY').format('MMMM YYYY');

        //     url += `&selectedMonth=${formattedMonth}`;
        // }

        // if (selectedTeams && selectedTeams.length > 0) {
        //     const selectedTeamValues = selectedTeams.map((team) => team.value);
        //     url += `&selectedTeams=${selectedTeamValues.join(',')}`;
        // }

        // Make the API call with the constructed URL
        const response = await axios.get(url);
        announcements.value = response.data.announcements;

    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const noticeVisible = ref(true);

function formatColor(status) {
    switch (status) {
        case 'active':
            return 'bg-success-500 text-white';
        case 'inactive':
            return 'bg-gray-500 text-white';
        case 'scheduled':
            return 'bg-warning-500 text-white';
        case 'draft':
            return 'bg-info-500 text-white';
        case 'expired':
            return 'bg-error-600 text-white';
        default:
            return 'bg-gray-500 text-white';
    }
}
</script>

<template>
    <!-- <div v-if="loading" class="flex flex-col gap-2 items-center justify-center">
        <Loader />
        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
    </div> -->

    <DataTable
        v-model:filters="filters"
        :value="announcements"
        :paginator="announcements?.length > 0 && filteredValue?.length > 0"
        removableSort
        :rows="20"
        :rowsPerPageOptions="[20, 50, 100]"
        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        :currentPageReportTemplate="$t('public.paginator_caption')"
        :globalFilterFields="['title']"
        ref="dt"
        :loading="loading"
        selectionMode="single"
        @filter="handleFilter"
        @row-click="(event) => openDialog(event.data)"
    >
        <template #header>
            <div class="flex flex-col justify-center items-center gap-5 self-stretch md:gap-6 pb-6">
                <div class="flex flex-col justify-start xl:justify-between items-start gap-5 self-stretch xl:flex-row md:gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 items-center gap-3 w-full md:w-auto md:gap-5">
                        <div class="relative w-full md:max-w-60">
                            <div class="absolute top-2/4 -mt-[9px] left-3 text-gray-500">
                                <IconSearch size="20" stroke-width="1.25" />
                            </div>
                            <InputText
                                v-model="filters['global'].value"
                                :placeholder="$t('public.keyword_search')"
                                size="search"
                                class="font-normal w-full"
                            />
                            <div
                                v-if="filters['global'].value !== null"
                                class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                @click="clearFilterGlobal"
                            >
                                <IconCircleXFilled size="16" />
                            </div>
                        </div>
                        <Select
                            v-model="filters['status'].value"
                            :options="statusOption"
                            optionLabel="name"
                            optionValue="value"
                            :placeholder="$t('public.filter_by_status')"
                            class="w-full md:max-w-60 font-normal"
                            scroll-height="236px"
                        >
                            <template #value="data">
                                <span class="font-normal text-gray-950" >{{ $t('public.' + (data.value || 'all')) }}</span>
                            </template>
                        </Select>
                    </div>
                    <div class="w-full flex flex-col items-center gap-3 md:w-auto md:flex-row md:gap-2">
                        <CreateAnnouncement />
                        <!-- <Button
                            type="button"
                            variant="primary-flat"
                            size="base"
                            class='w-full md:w-auto truncate'
                        >
                            <IconPlus size="20" stroke-width="1.25" />
                            {{ $t('public.new_announcement') }}
                        </Button> -->
                    </div>
                </div>
                
                <TransitionGroup
                    tag="div"
                    enter-from-class="-translate-y-full opacity-0"
                    enter-active-class="duration-300"
                    leave-active-class="duration-300"
                    leave-to-class="-translate-y-full opacity-0"
                    class="w-full"
                >
                    <div
                        v-if="noticeVisible"
                        class="p-2 md:py-4 md:px-5 flex justify-center self-stretch gap-3 border-l-8 rounded border-info-500 shadow-card bg-info-100 items-start"
                        role="alert"
                    >
                        <div class="text-info-500">
                            <IconInfoCircle size="24" stroke-width="2.0"/>
                        </div>
                        <div
                            class="flex flex-col gap-1 items-start w-full"
                        >
                            <div class="text-info-500 font-semibold text-sm">
                                {{ $t('public.pinned_announcements') }}
                            </div>
                            <div class="text-gray-700 text-xs md:text-sm font-normal">
                                {{ $t('public.pinned_announcements_desc') }}
                            </div>
                        </div>
                        <div class="text-info-500 hover:text-info-700 hover:cursor-pointer select-none" @click="noticeVisible = false">
                            <IconX size="16" stroke-width="1.25" />
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </template>
        <template #empty>
            <Empty 
                :title="$t('public.empty_bonus_record_title')" 
                :message="$t('public.empty_bonus_record_message')" 
            />
        </template>
        <template #loading>
            <div class="flex flex-col gap-2 items-center justify-center">
                <Loader />
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>
        </template>
        <template v-if="announcements?.length > 0 && filteredValue?.length > 0">
            <Column field="title" :header="$t('public.subject')" class="w-1/3 md:w-1/4 px-3">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm truncate max-w-full">
                        {{ slotProps.data?.title || '-' }}
                    </div>
                </template>
            </Column>
            <Column field="status" :header="$t('public.status')" class="w-1/3 md:w-1/4">
                <template #body="slotProps">
                    <div class="flex items-center">
                        <div
                            class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                            :class="formatColor(slotProps.data.status)"
                        >
                            <div
                                class="text-white text-xs text-center"
                            >
                                {{ $t(`public.${slotProps.data.status}`) }}
                            </div>
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="start_date" :header="$t('public.start_date')" style="width: 20%" class="hidden md:table-cell">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm truncate max-w-full">
                        {{ dayjs(slotProps.data.start_date).format('YYYY/MM/DD') }}
                    </div>
                </template>
            </Column>
            <Column field="end_date" :header="$t('public.expiry_date')" style="width: 20%" class="hidden md:table-cell">
                <template #body="slotProps">
                    <div class="text-gray-950 text-sm truncate max-w-full">
                        {{ dayjs(slotProps.data.end_date).format('YYYY/MM/DD') }}
                    </div>
                </template>
            </Column>
        </template>
    </DataTable>
    <!-- <div class="flex flex-col justify-center items-center gap-5 self-stretch md:gap-6">
        <div class="flex flex-col justify-start xl:justify-between items-start gap-5 self-stretch xl:flex-row md:gap-6">
            <div class="grid grid-cols-1 md:grid-cols-2 items-center gap-3 w-full md:w-auto md:gap-5">
                <div class="relative w-full md:max-w-60">
                    <div class="absolute top-2/4 -mt-[9px] left-3 text-gray-500">
                        <IconSearch size="20" stroke-width="1.25" />
                    </div>
                    <InputText
                        v-model="filters['global'].value"
                        :placeholder="$t('public.keyword_search')"
                        size="search"
                        class="font-normal w-full"
                    />
                    <div
                        v-if="filters['global'].value !== null"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                        @click="clearFilterGlobal"
                    >
                        <IconCircleXFilled size="16" />
                    </div>
                </div>
                <Select
                    v-model="filters['status'].value"
                    :options="statusOption"
                    optionLabel="name"
                    optionValue="value"
                    :placeholder="$t('public.filter_by_status')"
                    class="w-full md:max-w-60 font-normal"
                    scroll-height="236px"
                >
                    <template #value="data">
                        <span class="font-normal text-gray-950" >{{ $t('public.' + (data.value || 'all')) }}</span>
                    </template>
                </Select>
            </div>
            <div class="w-full flex flex-col items-center gap-3 md:w-auto md:flex-row md:gap-2">
                <Button
                    type="button"
                    variant="primary-flat"
                    size="base"
                    class='w-full md:w-auto truncate'
                >
                    <IconPlus size="20" stroke-width="1.25" />
                    {{ $t('public.new_announcement') }}
                </Button>
            </div>
        </div>
        
        <TransitionGroup
            tag="div"
            enter-from-class="-translate-y-full opacity-0"
            enter-active-class="duration-300"
            leave-active-class="duration-300"
            leave-to-class="-translate-y-full opacity-0"
            class="w-full"
        >
            <div
                v-if="noticeVisible"
                class="p-2 md:py-4 md:px-5 flex justify-center self-stretch gap-3 border-l-8 rounded border-info-500 shadow-card bg-info-100 items-start"
                role="alert"
            >
                <div class="text-info-500">
                    <IconInfoCircle size="24" stroke-width="2.0"/>
                </div>
                <div
                    class="flex flex-col gap-1 items-start w-full"
                >
                    <div class="text-info-500 font-semibold text-sm">
                        {{ $t('public.pinned_announcements') }}
                    </div>
                    <div class="text-gray-700 text-xs md:text-sm">
                        {{ $t('public.pinned_announcements_desc') }}
                    </div>
                </div>
                <div class="text-info-500 hover:text-info-700 hover:cursor-pointer select-none" @click="noticeVisible = false">
                    <IconX size="16" stroke-width="1.25" />
                </div>
            </div>
        </TransitionGroup>
    </div> -->
</template>
