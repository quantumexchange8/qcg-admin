<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AutoDeposit from "@/Pages/Configuration/Partials/AutoDeposit.vue";
import { h, ref, watch } from "vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';

// Tab data
const tabs = ref([
    {
        title: 'deposit_auto_approval',
        component: h(AutoDeposit),
        type: 'deposit'
    }
]);

// Initial selected type
const type = ref('deposit');
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

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_configuration')">
        <div class="flex flex-col justify-center items-center py-5 px-3 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
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

                </div>

            </div>

            <!-- Tabs Content -->
            <Tabs v-model:value="type" class="w-full">
                <TabPanels>
                    <TabPanel :key="type" :value="type">
                        <component :is="tabs.find(tab => tab.type === type).component"/>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>