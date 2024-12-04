<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from "@inertiajs/vue3";
import { generalFormat } from "@/Composables/index.js";
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted, h } from "vue";
import Loader from "@/Components/Loader.vue";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import Button from '@/Components/Button.vue';
import Select from "primevue/select";
import { FilterMatchMode } from '@primevue/core/api';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import Empty from "@/Components/Empty.vue";
import { trans } from "laravel-vue-i18n";
import AddMember from "@/Pages/Member/Listing/Partials/AddMember.vue";
import MemberTableActions from "@/Pages/Member/Listing/Partials/MemberTableActions.vue"
import debounce from "lodash/debounce.js";

const props = defineProps({
    totalMembers: Number,
    totalAgents: Number,
    countries: Array,
    uplines: Array,
    teams: Array,
});

const dt = ref(null);
const loading = ref(false);
const totalRecords = ref(0);
const users = ref(null);
const selectedBrand = ref(null);
const first = ref(0);
const {formatRgbaColor} = generalFormat();
const total_members = ref(0);
const total_agents = ref(0);
const filteredValue = ref();
const teams = ref(props.teams);

const tabs = ref([
    {
        title: 'member',
        type: 'member',
        total: props.totalMembers,
    },
    {
        title: 'agent',
        type: 'agent',
        total: props.totalAgents,
    },
]);

const selectedType = ref('member');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type: { value: selectedType.value, matchMode: FilterMatchMode.EQUALS },
    team_id: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const lazyParams = ref({});

const loadLazyData = (event) => {
    loading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };

    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value)
            };

            const url = route('member.getMemberListingPaginate', params);
            const response = await fetch(url);
            const results = await response.json();

            users.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            total_members.value = results.total_members;
            total_agents.value = results.total_agents;

            // Update the total counts in the tabs array
            tabs.value = tabs.value.map(tab => ({
                ...tab,
                total: tab.type === 'member' ? total_members.value : total_agents.value
            }));

            loading.value = false;
        }, 100);
    }  catch (e) {
        users.value = [];
        totalRecords.value = 0;
        loading.value = false;
    }
};
const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};
const onSort = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};
const onFilter = (event) => {
    lazyParams.value.filters = filters.value ;
    loadLazyData(event);
    filteredValue.value = event.filteredValue;
    console.log(event)
};

onMounted(() => {
    lazyParams.value = {
        first: dt.value.first,
        rows: dt.value.rows,
        sortField: null,
        sortOrder: null,
        filters: filters.value
    };

    loadLazyData();
});

watch(
    filters.value['global'],
    debounce(() => {
        loadLazyData();
    }, 300)
);

watch([filters.value['type'], filters.value['team_id']], () => {
    loadLazyData()
});

// Watch for changes in selectedType and update the activeIndex accordingly
watch(selectedType, (newType) => {
    const index = tabs.value.findIndex(tab => tab.type === newType);
    if (index >= 0) {
        activeIndex.value = index;

    }
});

function updateType(event) {
    const selectedTab = tabs.value[event.index];
    filters.value['type'].value = selectedTab.type;
}

const clearFilter = () => {
    filters.value['global'].value = null;
    filters.value['team_id'].value = null;
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = filteredValue.value;

    // Specify the headers
    const headers = [
        trans('public.name'),
        trans('public.email'),
        trans('public.id'),
        trans('public.sales_team'),
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        return [
            obj.name !== undefined ? obj.name : '',
            obj.email !== undefined ? obj.email : '',
            obj.id_number !== undefined ? obj.id_number : '',
            obj.team_name !== undefined ? obj.team_name : '',
        ];
    });

    // Combine headers and rows into a single data array
    const sheetData = [headers, ...rows];

    // Create the XLSX content
    let csvContent = "data:text/xlsx;charset=utf-8,";

    sheetData.forEach((rowArray) => {
        const row = rowArray.join("\t"); // Use tabs for column separation
        csvContent += row + "\r\n"; // Add a new line after each row
    });

    // Create a temporary link element
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "export.xlsx");

    // Append the link to the document and trigger the download
    document.body.appendChild(link);
    link.click();

    // Clean up by removing the link
    document.body.removeChild(link);
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_listing')">
        <div class="w-full flex flex-col items-center gap-5">
            <div class="flex flex-col justify-center items-center px-3 py-5 gap-3 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <div class="w-full flex flex-col-reverse md:flex-row justify-between items-center self-stretch gap-3 md:gap-0">
                    <div class="w-full md:w-auto flex items-center">
                        <Tabs v-model:value="activeIndex">
                            <TabList class="flex justify-center items-center">
                                <Tab
                                    v-for="(tab, index) in tabs"
                                    :key="index"
                                    :value="index"
                                    @click="updateType({ index })"
                                >
                                    {{ `${$t('public.' + tab.title)}&nbsp;(${tab.total})` }}
                            </Tab>
                            </TabList>
                        </Tabs>
                    </div>
                    <div class="w-full flex flex-col-reverse items-center gap-3 md:w-auto md:flex-row md:gap-5">
                        <Button
                            variant="primary-outlined"
                            @click="filteredValue?.length > 0 ? exportXLSX($event) : null"
                            class="w-full md:w-auto"
                        >
                            <IconDownload size="20" stroke-width="1.25" />
                            {{ $t('public.export') }}
                        </Button>
                        <AddMember
                            :countries="props.countries"
                            :uplines="props.uplines"
                        />
                    </div>
                </div>
                <!-- data table -->
                <div v-if="props.totalMembers === 0 && props.totalAgents === 0">
                    <Empty
                        :title="selectedType === 'member' ? $t('public.empty_member_title') : $t('public.empty_agent_title')"
                        :message="selectedType === 'member' ? $t('public.empty_member_message') : $t('public.empty_agent_message')"
                    />
                </div>
                <DataTable
                    v-else
                    :value="users"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    lazy
                    paginator
                    removableSort
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="$t('public.paginator_caption')"
                    :first="first"
                    :rows="10"
                    v-model:filters="filters"
                    ref="dt"
                    dataKey="id"
                    :totalRecords="totalRecords"
                    :loading="loading"
                    @page="onPage($event)"
                    @sort="onSort($event)"
                    @filter="onFilter($event)"
                    :globalFilterFields="['first_name', 'email', 'id_number']"
                    v-model:selection="selectedBrand"
                >
                    <template #header>
                        <div class="flex flex-col justify-between items-center pb-5 gap-5 self-stretch md:flex-row md:pb-6">
                            <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                                <div class="relative w-full md:w-60">
                                    <div class="absolute top-2/4 -mt-[9px] left-3 text-gray-500">
                                        <IconSearch size="20" stroke-width="1.25" />
                                    </div>
                                    <InputText
                                        v-model="filters['global'].value"
                                        :placeholder="$t('public.keyword_search')"
                                        size="search"
                                        class="font-normal w-full md:w-60"
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
                                    v-model="filters['team_id'].value"
                                    :options="teams"
                                    filter
                                    :filterFields="['name']"
                                    optionLabel="name"
                                    :placeholder="$t('public.filter_by_sales_team')"
                                    class="w-full md:w-60 font-normal"
                                    scroll-height="236px"
                                >
                                    <template #value="slotProps">
                                        <div v-if="slotProps.value" class="flex items-center gap-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-4 h-4 rounded-full overflow-hidden grow-0 shrink-0" :style="{ backgroundColor: `#${slotProps.value.color}` }"></div>
                                                <div>{{ slotProps.value.name }}</div>
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                                    </template>
                                    <template #option="slotProps">
                                        <div class="flex items-center gap-2">
                                            <div class="w-4 h-4 rounded-full overflow-hidden grow-0 shrink-0" :style="{ backgroundColor: `#${slotProps.option.color}` }"></div>
                                            <div>{{ slotProps.option.name }}</div>
                                        </div>
                                    </template>
                                </Select>
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
                            :title="selectedType === 'member' ? $t('public.empty_member_title') : $t('public.empty_agent_title')"
                            :message="selectedType === 'member' ? $t('public.empty_member_message') : $t('public.empty_agent_message')"
                        />
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loader />
                            <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                        </div>
                    </template>
                    <template v-if="users?.length > 0">
                        <Column
                            field="first_name"
                            sortable
                            :header="$t('public.name')"
                            style="width: 25%; max-width: 0;"
                            class="px-3"
                        >
                            <template #body="{data}">
                                <div class="flex flex-col items-start max-w-full">
                                    <div class="font-semibold truncate max-w-full">
                                        {{ data.first_name }}
                                    </div>
                                    <div class="text-gray-500 text-xs truncate max-w-full">
                                        {{ data.email }}
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="id_number" :header="$t('public.id')" sortable style="width: 25%" class="hidden md:table-cell">
                            <template #body="{ data }">
                                <div class="text-gray-950 text-sm">
                                    {{ data.id_number }}
                                </div>
                            </template>
                        </Column>
                        <Column field="team" :header="$t('public.sales_team')" style="width: 25%" class="hidden md:table-cell">
                            <template #body="{data}">
                                <div class="flex items-center">
                                    <div
                                        v-if="data.team_has_user"
                                        class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                                        :style="{ backgroundColor: formatRgbaColor(data.team_has_user.team.color, 1) }"
                                    >
                                        <div
                                            class="text-white text-xs text-center"
                                        >
                                            {{ data.team_has_user.team.name }}
                                        </div>
                                    </div>
                                    <div v-else>
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="action" header="" style="width: 25%">
                            <template #body="slotProps">
                                <MemberTableActions
                                    :member="slotProps.data"
                                />
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
