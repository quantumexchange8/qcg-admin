<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { h, ref, watch } from "vue";
import AllAccount from "@/Pages/Member/Account/Partials/AllAccount.vue";
import PromotionAccount from "@/Pages/Member/Account/Partials/PromotionAccount.vue";
import DeletedAccount from "@/Pages/Member/Account/Partials/DeletedAccount.vue";
import Button from "@/Components/Button.vue";
import Select from "primevue/select";
import { IconRefresh, IconDownload } from "@tabler/icons-vue";
import { router } from "@inertiajs/vue3";
import { trans, wTrans } from "laravel-vue-i18n";
import dayjs from "dayjs";

const props = defineProps({
    accountTypes: Array,
});

const exportStatus = ref(false);
const loading = ref(false);

// Tab data
const tabs = ref([
    {
        title: 'individual',
        component: h(AllAccount),
        type: 'all'
    },
    {
        title: 'promotion',
        component: h(PromotionAccount),
        type: 'promotion'
    },
    {
        title: 'deleted',
        component: h(DeletedAccount),
        type: 'deleted'
    },
]);

// Initial selected type
const type = ref('all');
const selectedType = ref(tabs.value.find(tab => tab.type === type.value));

// Watch `selectedType` and update `type` and `selectedType` in one place
watch(selectedType, (newSelectedType) => {
    if (newSelectedType) {
        type.value = newSelectedType.type;
    }
});

// Set the initial selectedType based on `type`
watch(type, (newType) => {
    selectedType.value = tabs.value.find(tab => tab.type === newType);
});

// Refresh all accounts
const refreshAll = () => {
    router.post(route('member.refreshAllAccount'));
};

const filters = ref({
  global: '',
  account_type_id: null,
});

function handleFilters(newFilters) {
  filters.value = { ...filters.value, ...newFilters };
}

const exportAccount = () => {
    exportStatus.value = true;
    try {
        let url = `/member/getAccountListingPaginate`;

        if (exportStatus.value === true) {
            url += `?exportStatus=${exportStatus.value}`;
        }

        if (type.value) {
            url += `&type=${type.value}`;
        }

        if (filters.value.global) {
            url += `&search=${filters.value.global}`;
        }

        if (filters.value.account_type_id) {
            url += `&account_type_id=${filters.value.account_type_id}`;
        }

        window.location.href = url;
    } catch (e) {
        console.error('Error occurred during export:', e);
    } finally {
        loading.value = false;
        exportStatus.value = false;
    }
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_account_listing')">
        <div class="flex flex-col justify-center items-center py-5 px-3 gap-3 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="w-full flex flex-col-reverse md:flex-row justify-between items-center self-stretch gap-3 md:gap-0">
                <div class="w-full md:w-auto flex items-center">
                    <!-- Desktop Tabs -->
                    <Tabs v-model:value="type" class="w-full hidden md:block">
                        <TabList>
                            <Tab
                                v-for="(tab, index) in tabs"
                                :key="index"
                                :value="tab.type"
                            >
                                {{ $t('public.' + tab.title) }}
                            </Tab>
                        </TabList>
                    </Tabs>
                    <!-- Mobile Select -->
                    <Select
                        v-model="selectedType"
                        :options="tabs"
                        class="w-full font-normal md:hidden"
                        scroll-height="236px"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-3">
                                <div>{{ $t('public.' + slotProps.value.title) }}</div>
                            </div>
                        </template>
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <div>{{ $t('public.' + slotProps.option.title) }}</div>
                            </div>
                        </template>
                    </Select>
                </div>
                <div class="w-full md:w-auto flex flex-col-reverse items-center gap-3 md:hidden">
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

            <!-- Tabs Content -->
            <Tabs v-model:value="type" class="w-full">
                <TabPanels>
                    <TabPanel :key="type" :value="type">
                        <component :is="tabs.find(tab => tab.type === type).component" :accountTypes="props.accountTypes" @update:filters="handleFilters" />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
