<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from "@inertiajs/vue3";
import { generalFormat } from "@/Composables/index.js";
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff, IconCircleCheckFilled, IconExclamationCircleFilled, IconClockFilled } from "@tabler/icons-vue";
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
import { wTrans } from "laravel-vue-i18n";
import debounce from "lodash/debounce.js";

const props = defineProps({
    totalMembers: Number,
    totalAgents: Number,
    countries: Array,
    uplines: Array,
    teams: Array,
});

const type = ref(new URLSearchParams(window.location.search).get('type'));
const dt = ref(null);
const loading = ref(false);
const totalRecords = ref(0);
const users = ref(null);
const selectedBrand = ref(null);
const rows = ref(20);
const page = ref(0);
const first = ref(0);
const sortField = ref(null);  
const sortOrder = ref(null);  // (1 for ascending, -1 for descending)
const {formatRgbaColor} = generalFormat();
const total_members = ref(0);
const total_agents = ref(0);
const filteredValue = ref();
const teams = ref([
    {
        value: null,
        name: wTrans('public.all'),
        color: '000000'
    },
    ...props.teams
]);
const exportStatus = ref(false);

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

const selectedType = ref(type.value || 'member');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

const filters = ref({
    global: '',
    team_id: null
});

// Watch for changes on the entire 'filters' object and debounce the API call
watch(filters, debounce(() => {
    getResults(); // Call getResults function to fetch the data
}, 1000), { deep: true });


// Watch for changes in selectedType and update the activeIndex accordingly
watch(selectedType, (newType) => {
    const index = tabs.value.findIndex(tab => tab.type === newType);
    if (index >= 0) {
        activeIndex.value = index;
    }
    getResults();
});

function updateType(event) {
    const selectedTab = tabs.value[event.index];
    selectedType.value = selectedTab.type;
}

const getResults = async () => {
    loading.value = true;
    try {
        // Directly construct the URL with necessary query parameters
        let url = `/member/getMemberListingPaginate?type=${selectedType.value}&rows=${rows.value}&page=${page.value}`;

        // Add filters if present
        if (filters.value.global) {
            url += `&search=${filters.value.global}`;
        }

        if (filters.value.team_id?.value) {
            url += `&team_id=${filters.value.team_id?.value}`;
        }

        if (sortField.value && sortOrder.value !== null) {
            url += `&sortField=${sortField.value}&sortOrder=${sortOrder.value}`;
        }

        // Make the API request
        const response = await axios.get(url);
        const results = response.data;

        // Directly set the results data
        users.value = results?.data?.data || [];
        totalRecords.value = results?.data?.total || 0;
        total_members.value = results?.total_members || 0;
        total_agents.value = results?.total_agents || 0;

        // Update the total counts in the tabs array
        tabs.value = tabs.value.map(tab => ({
            ...tab,
            total: tab.type === 'member' ? total_members.value : total_agents.value
        }));

    } catch (error) {
        console.error('Error fetching leads data:', error);
        users.value = [];
    } finally {
        loading.value = false;
    }
};

const onPage = async (event) => {
    rows.value = event.rows;
    page.value = event.page;

    getResults();
};

const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;  // Store ascending or descending order
    page.value = 0;

    getResults();
};

onMounted(() => {
    getResults();
});

// Optimized exportMember function without using constructUrl
const exportMember = async () => {
    exportStatus.value = true;
    try {
        // Directly construct the URL with exportStatus for export
        let url = `/member/getMemberListingPaginate?type=${selectedType.value}`;

        // Add filters if present
        if (filters.value.global) {
            url += `&search=${filters.value.global}`;
        }

        if (filters.value.team_id?.value !== null) {
            url += `&team_id=${filters.value.team_id.value}`;
        }

        if (exportStatus.value === true) {
            url += `&exportStatus=${exportStatus.value}`;
        }
        
        // Send the request to trigger the export
        window.location.href = url;  // This will trigger the download directly
    } catch (e) {
        console.error('Error occurred during export:', e);
    } finally {
        loading.value = false;  // Reset loading state
        exportStatus.value = false;  // Reset export status
    }
};

const clearFilter = () => {
    filters.value['global'] = '';
    filters.value['team_id'] = null;
};

const clearFilterGlobal = () => {
    filters.value['global'] = '';
}

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
                    <div class="flex w-full md:hidden flex-col-reverse items-center gap-3">
                        <Button
                            variant="primary-outlined"
                            @click="exportMember()"
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
                    :rowsPerPageOptions="[20, 50, 100]"
                    lazy
                    paginator
                    removableSort
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport JumpToPageInput"
                    :currentPageReportTemplate="$t('public.paginator_caption')"
                    :first="first"
                    :rows="rows"
                    :page="page"
                    ref="dt"
                    dataKey="id"
                    :totalRecords="totalRecords"
                    :loading="loading"
                    @page="onPage($event)"
                    @sort="onSort($event)"
                    v-model:selection="selectedBrand"
                >
                    <template #header>
                        <div class="flex flex-col justify-between items-center pb-5 gap-5 self-stretch md:flex-row md:pb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 items-center gap-3 self-stretch md:flex-row md:gap-2">
                                <div class="relative w-full md:max-w-60">
                                    <div class="absolute top-2/4 -mt-[9px] left-3 text-gray-500">
                                        <IconSearch size="20" stroke-width="1.25" />
                                    </div>
                                    <InputText
                                        v-model="filters['global']"
                                        :placeholder="$t('public.keyword_search')"
                                        size="search"
                                        class="font-normal w-full"
                                    />
                                    <div
                                        v-if="filters['global'] !== null && filters['global'] !== ''"
                                        class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                        @click="clearFilterGlobal"
                                    >
                                        <IconCircleXFilled size="16" />
                                    </div>
                                </div>
                                <Select
                                    v-model="filters['team_id']"
                                    :options="teams"
                                    filter
                                    :filterFields="['name']"
                                    optionLabel="name"
                                    :placeholder="$t('public.filter_by_sales_team')"
                                    class="w-full md:max-w-60 font-normal"
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
                            <div class="w-full flex flex-col items-center gap-3 md:w-auto md:flex-row md:gap-2">
                                <div class="w-full hidden md:flex md:flex-row items-center md:gap-2 md:w-auto">
                                    <AddMember
                                        :countries="props.countries"
                                        :uplines="props.uplines"
                                    />
                                    <Button
                                        variant="primary-outlined"
                                        @click="exportMember()"
                                        class="w-full md:w-auto"
                                    >
                                        <IconDownload size="20" stroke-width="1.25" />
                                        {{ $t('public.export') }}
                                    </Button>
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
                            class="px-3 w-2/3 md:w-1/4 max-w-0"
                        >
                            <template #body="{data}">
                                <div class="flex flex-col items-start max-w-full">
                                    <div class="flex items-center max-w-full gap-2">
                                        <div class="font-semibold truncate max-w-full">
                                            {{ data.first_name }}
                                        </div>
                                        <IconCircleCheckFilled v-if="data.kyc_approval === 'verified'" size="16" stroke-width="1.25" class="text-success-500 grow-0 shrink-0" />
                                        <IconClockFilled v-else-if="data.kyc_approval === 'pending'" size="16" stroke-width="1.25" class="text-warning-500 grow-0 shrink-0" />
                                        <IconExclamationCircleFilled v-else size="16" stroke-width="1.25" class="text-error-500 grow-0 shrink-0" />
                                        <div
                                            v-if="data.team_has_user"
                                            class="flex justify-center items-center gap-2 rounded-sm py-1 px-2 md:hidden"
                                            :style="{ backgroundColor: formatRgbaColor(data.team_has_user.team.color, 1) }"
                                        >
                                            <div
                                                class="text-white text-xs text-center"
                                            >
                                                {{ data.team_has_user.team.name }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-gray-500 text-xs truncate max-w-full">
                                        {{ data.email }}
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="id_number" :header="$t('public.id')" sortable style="width: 20%" class="hidden md:table-cell">
                            <template #body="{ data }">
                                <div class="text-gray-950 text-sm">
                                    {{ data.id_number }}
                                </div>
                            </template>
                        </Column>
                        <Column field="team" :header="$t('public.sales_team')" style="width: 20%" class="hidden md:table-cell">
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
                        <Column field="email_verified_at" :header="$t('public.verified')" style="width: 15%" class="hidden md:table-cell">
                            <template #body="{ data }">
                                <div class="text-gray-950 text-sm">
                                    <IconCircleCheckFilled v-if="data.email_verified_at !== null" size="20" stroke-width="1.25" class="text-success-700 grow-0 shrink-0" />
                                    <span v-else>-</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="action" class="w-1/3 md:w-1/5">
                            <template #body="slotProps">
                                <div class="w-full flex justify-end">
                                    <MemberTableActions
                                        :member="slotProps.data"
                                    />
                                </div>
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
