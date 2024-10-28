<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { h, ref, watch } from "vue";
import AllAccount from "@/Pages/Member/Account/Partials/AllAccount.vue";
import DeletedAccount from "@/Pages/Member/Account/Partials/DeletedAccount.vue";
import Button from "@/Components/Button.vue";
import { IconRefresh, IconDownload } from "@tabler/icons-vue";
import { router } from "@inertiajs/vue3";
import { trans, wTrans } from "laravel-vue-i18n";
import dayjs from "dayjs";

const props = defineProps({
    accountTypes: Array,
});

const filteredValue = ref(null);

const tabs = ref([
    {
        title: 'all_accounts',
        component: h(AllAccount),
        type: 'all_accounts'
    },
    {
        title: 'deleted_accounts',
        component: h(DeletedAccount),
        type: 'deleted_accounts'
    },
]);

const selectedType = ref('all_accounts');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

// Watch for changes in selectedType and update the activeIndex accordingly
watch(selectedType, (newType) => {
    const index = tabs.value.findIndex(tab => tab.type === newType);
    if (index >= 0) {
        activeIndex.value = index;
    }
});

function updateType(event) {
    const selectedTab = tabs.value[event.index];
    selectedType.value = selectedTab.type;
}

const refreshAll = () => {
    router.post(route('member.refreshAllAccount'));
};

const exportXLSX = () => {
    // Retrieve the array from the reactive proxy
    const data = filteredValue.value;

    // Specify the headers
    const headers = [
        trans('public.name'),
        trans('public.email'),
        selectedType.value === 'all_accounts' ? trans('public.last_logged_in') : trans('public.deleted_time'),
        trans('public.account'),
        `${trans('public.balance')} ($)`,
        `${trans('public.equity')} ($)`,
    ];

    // Map the array data to XLSX rows
    const rows = data.map(obj => {
        return [
            obj.name !== undefined ? obj.name : '',
            obj.email !== undefined ? obj.email : '',
            selectedType.value === 'all_accounts' ? (obj.last_login !== undefined ? dayjs(obj.last_login).format('YYYY/MM/DD HH:mm:ss') : '' ) : (obj.deleted_at !== undefined ? dayjs(obj.deleted_at).format('YYYY/MM/DD HH:mm:ss') : ''),
            obj.meta_login !== undefined ? obj.meta_login : '',
            obj.balance !== undefined ? obj.balance : '',
            obj.equity !== undefined ? obj.equity : '',
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

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_account_listing')">
        <div class="flex flex-col justify-center items-center py-5 px-3 gap-3 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="w-full flex flex-col-reverse md:flex-row justify-between items-center self-stretch gap-3 md:gap-0">
                <div class="w-full md:w-auto flex items-center">
                    <Tabs v-model:value="activeIndex" class="w-full" @tab-change="updateType">
                        <TabList>
                            <Tab v-for="(tab, index) in tabs" :key="tab.title" :value="index">
                                {{ $t('public.' + tab.title) }}
                            </Tab>
                        </TabList>
                    </Tabs>
                </div>
                <div class="w-full md:w-auto flex flex-col-reverse items-center gap-3 md:flex-row md:gap-5">
                    <Button variant="primary-outlined" @click="filteredValue?.length > 0 ? exportXLSX() : null" class="w-full md:w-auto">
                        <IconDownload size="20" stroke-width="1.25" />
                        {{ $t('public.export') }}
                    </Button>
                    <Button type="button" variant="primary-flat" class="flex justify-center w-full md:w-auto" @click="refreshAll">
                        <IconRefresh color="white" size="20" stroke-width="1.25" />
                        <span>{{ $t('public.update_all') }}</span>
                    </Button>
                </div>
            </div>

            <Tabs v-model:value="activeIndex" class="w-full">
                <TabPanels>
                    <TabPanel :key="activeIndex" :value="activeIndex">
                        <component :is="tabs[activeIndex].component" :key="tabs[activeIndex].type" :accountTypes="props.accountTypes" @update:filteredValue="filteredValue = $event" />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
