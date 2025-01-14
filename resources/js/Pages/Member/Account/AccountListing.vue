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

const exportStatus = ref(false);
const loading = ref(false);

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
    // console.log(selectedType.value)
}

const refreshAll = () => {
    router.post(route('member.refreshAllAccount'));
};

// Create a reactive filter object
const filters = ref({
  global: '',
  account_type_id: null,
});

// Function to handle the update of filters
function handleFilters(newFilters) {
  // Update the filters with new values
  filters.value = { ...filters.value, ...newFilters };

  // Log the updated filters
//   console.log(newFilters);
}

const exportAccount = () => {
    exportStatus.value = true;
    try {
        // Directly construct the URL with exportStatus for export
        let url = `/member/getAccountListingPaginate`;

        if (exportStatus.value === true) {
            url += `?exportStatus=${exportStatus.value}`;
        }

        // Conditionally add the 'type' query parameter if the selectedType is 'all_accounts'
        if (selectedType.value === 'all_accounts') {
            url += `&type=all`;
        }

        // Add filters if present
        if (filters.value.global) {
            url += `&search=${filters.value.global}`;
        }

        if (filters.value.account_type_id) {
            url += `&account_type_id=${filters.value.account_type_id}`;
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

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_account_listing')">
        <div class="flex flex-col justify-center items-center py-5 px-3 gap-3 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="w-full flex flex-col-reverse md:flex-row justify-between items-center self-stretch gap-3 md:gap-0">
                <div class="w-full md:w-auto flex items-center">
                    <Tabs v-model:value="activeIndex" class="w-full">
                        <TabList>
                            <Tab
                                v-for="(tab, index) in tabs"
                                :key="index"
                                :value="index"
                                @click="updateType({ index })"
                            >
                                {{ $t('public.' + tab.title) }}
                            </Tab>
                        </TabList>
                    </Tabs>
                </div>
                <div class="w-full md:w-auto flex flex-col-reverse items-center gap-3 md:flex-row md:gap-5">
                    <Button variant="primary-outlined" @click="exportAccount()" class="w-full md:w-auto">
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
                        <component :is="tabs[activeIndex].component" :key="tabs[activeIndex].type" :accountTypes="props.accountTypes" @update:filters="handleFilters" />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
