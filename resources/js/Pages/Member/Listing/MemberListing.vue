<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import { generalFormat } from "@/Composables/index.js";
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted, h } from "vue";
import Loader from "@/Components/Loader.vue";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
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
import { wTrans } from "laravel-vue-i18n";
import AddMember from "@/Pages/Member/Listing/Partials/AddMember.vue";
import MemberTableActions from "@/Pages/Member/Listing/Partials/MemberTableActions.vue"

const { formatRgbaColor } = generalFormat();

const props = defineProps({
    totalMembers: Number,
    totalAgents: Number,
    groups: Array,
});

const loading = ref(false);
const dt = ref();
const users = ref();
const total_members = ref(0);
const total_agents = ref(0);
const filteredValueCount = ref(0);
const groups = ref(props.groups);
const group_id = ref(null)

const tabs = ref([
    {   
        title: wTrans('public.member'),
        type: 'member',
        total: props.totalMembers,
    },
    {   
        title: wTrans('public.agent'),
        type: 'agent',
        total: props.totalAgents,
    },
]);

const selectedType = ref('member');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

// Watch for changes in selectedType and update the activeIndex accordingly
watch(selectedType, (newType) => {
    const index = tabs.value.findIndex(tab => tab.type === newType);
    if (index >= 0) {
        activeIndex.value = index;
        getResults();
    }
});

function updateType(event) {
    const selectedTab = tabs.value[event.index];
    selectedType.value = selectedTab.type;
}

const getResults = async () => {
    loading.value = true;

    // Skip fetch if both totals are 0
    if (props.totalMembers === 0 && props.totalAgents === 0) {
        loading.value = false;
        return;
    }

    try {
        const response = await axios.get(`/member/getMemberListingData?role=${selectedType.value}`);
        users.value = response.data.users;
        total_members.value = response.data.total_members;
        total_agents.value = response.data.total_agents;

        // Update the total counts in the tabs array
        tabs.value = tabs.value.map(tab => ({
            ...tab,
            total: tab.type === 'member' ? total_members.value : total_agents.value
        }));
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }

};

// const getFilterData = async () => {
//     try {
//         const uplineResponse = await axios.get('/member/getFilterData');
//         groups.value = uplineResponse.data.groups;
//         groups.value = uplineResponse.data.groups;
//     } catch (error) {
//         console.error('Error changing locale:', error);
//     } finally {
//         loading.value = false;
//     }
// };

onMounted(() => {
    getResults();
    // getFilterData();
})

const exportCSV = () => {
    dt.value.exportCSV();
};

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    group_id: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        email: { value: null, matchMode: FilterMatchMode.CONTAINS },
        group_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    };

    group_id.value = null;
};

watch(group_id, (newGroupId) => {
    if (group_id.value !== null) {
        filters.value['group_id'].value = newGroupId.value
    }
})

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const handleFilter = (e) => {
    filteredValueCount.value = e.filteredValue.length;
};

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_listing')">
        <div class="w-full flex flex-col items-center gap-5">
            <div class="flex flex-col justify-center items-center px-3 py-5 gap-3 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <div class="w-full flex flex-col-reverse md:flex-row justify-between items-center self-stretch gap-3 md:gap-0">
                    <div class="flex items-center">
                        <Tabs v-model:value="activeIndex">
                            <TabList class="flex justify-center items-center">
                                <Tab 
                                    v-for="(tab, index) in tabs" 
                                    :key="index"
                                    :value="index"
                                    @click="updateType({ index })"
                                >
                                    {{ `${tab.title}&nbsp;(${tab.total})` }}
                            </Tab>
                            </TabList>
                        </Tabs>
                    </div>
                    <div class="w-full flex flex-col-reverse items-center gap-3 md:w-auto md:flex-row md:gap-5">
                        <Button
                            variant="primary-outlined"
                            @click="exportCSV()"
                            class="w-full md:w-auto"
                        >
                            <IconDownload size="20" stroke-width="1.25" />
                            {{ $t('public.export') }}
                        </Button>
                        <AddMember />
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
                    v-model:filters="filters"
                    :value="users"
                    :paginator="users?.length > 0 && filteredValueCount > 0"
                    removableSort
                    :rows="10"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="$t('public.paginator_caption')"
                    :globalFilterFields="['name', 'email']"
                    ref="dt"
                    :loading="loading"
                    selectionMode="single"
                    @filter="handleFilter"
                >
                    <template #header>
                        <div class="flex flex-col justify-between items-center pb-5 gap-5 self-stretch md:flex-row md:pb-6">
                            <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                                <div class="relative w-full md:w-60">
                                    <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                                        <IconSearch size="20" stroke-width="1.25" />
                                    </div>
                                    <InputText v-model="filters['global'].value" :placeholder="$t('public.keyword_search')" class="font-normal pl-12 w-full md:w-60" />
                                    <div
                                        v-if="filters['global'].value !== null"
                                        class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                        @click="clearFilterGlobal"
                                    >
                                        <IconCircleXFilled size="16" />
                                    </div>
                                </div>
                                <Select
                                    v-model="group_id"
                                    :options="groups"
                                    filter
                                    :filterFields="['name']"
                                    optionLabel="name"
                                    :placeholder="$t('public.filter_sales_team_placeholder')"
                                    class="w-full md:w-60"
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
                            <span class="text-sm text-gray-700">{{ $t('public.loading_transactions_caption') }}</span>
                        </div>
                    </template>
                    <template v-if="users?.length > 0 && filteredValueCount > 0">
                        <Column field="name" sortable :header="$t('public.name')" style="width: 25%; max-width: 0;">
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
                        <Column field="id_number" :header="$t('public.id')" sortable style="width: 25%" class="hidden md:table-cell">
                            <template #body="slotProps">
                                <div class="text-gray-950 text-sm">
                                    {{ slotProps.data.id_number }}
                                </div>
                            </template>
                        </Column>
                        <Column field="group" :header="$t('public.sales_team')" style="width: 25%" class="hidden md:table-cell">
                            <template #body="slotProps">
                                <div class="flex items-center">
                                    <div
                                        v-if="slotProps.data.group_id"
                                        class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                                        :style="{ backgroundColor: formatRgbaColor(slotProps.data.group_color, 1) }"
                                    >
                                        <div
                                            class="text-white text-xs text-center"
                                        >
                                            {{ slotProps.data.group_name }}
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