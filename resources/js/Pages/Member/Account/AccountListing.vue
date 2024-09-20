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

const props = defineProps({
    accountTypes: Array,
});

const dt = ref(null);

const tabs = ref([
    {
        title: 'all_accounts',
        component: h(AllAccount, { dt }),
        type: 'all_accounts'
    },
    {
        title: 'deleted_accounts',
        component: h(DeletedAccount, { dt }),
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

const exportCSV = () => {
    if (dt.value) {
        dt.value.exportCSV();
    }
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
                    <Button variant="primary-outlined" @click="exportCSV" class="w-full md:w-auto">
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
                        <component :is="tabs[activeIndex].component" :key="tabs[activeIndex].type" :accountTypes="props.accountTypes" />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
