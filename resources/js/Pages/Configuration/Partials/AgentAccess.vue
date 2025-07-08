<script setup>
import Button from "@/Components/Button.vue";
import { ref, watch, watchEffect, computed } from "vue";
import {usePage} from "@inertiajs/vue3";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { trans, wTrans } from "laravel-vue-i18n";
import Select from "primevue/select";
import toast from '@/Composables/toast';
import AddAnother from "./AddAnother.vue";

const accessLevels = [
    { name: wTrans('public.view_only'), value: 'view' },
    { name: wTrans('public.full_access'), value: 'full' },
];

const agentAccesses = ref();
const agents = ref();
const dt = ref();
const loading = ref(false);

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/configuration/getAgentAccesses');
        
        agents.value = response.data.agents;
        // console.log(agents.value)
        agentAccesses.value = response.data.agentAccesses;
        // console.log(agentAccesses.value)
    } catch (error) {
        console.error('Error getting accessbilities:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const getAccessLevelDisplay = computed(() => (accessLevel) => {
  switch (accessLevel) {
    case 'full':
      return wTrans('public.full_access');
    case 'view':
      return wTrans('public.view_only');
    default:
      return wTrans('public.unknown');
  }
});

const onCellEditComplete = (event) => {
    let { data, newValue, field } = event;

    if (field === 'user') {
        data[field] = newValue;
        // console.log(event)
    } else if (field === 'access_level') {
        data[field] = newValue;
    }
};

const saveChanges = async () => {
    try {
        loading.value = true;

        await axios.post('/configuration/updateAgentAccesses', {
            agentAccesses: agentAccesses.value
        })

        // const response = await axios.post('/configuration/updateTicketCategories', ticketCategories.value);
        // const result = await response.json();

        // console.log('Changes saved:', ticketCategories.value);

        toast.add({ type: 'success', title: wTrans('public.changes_saved_successfully') });

    } catch (error) {
        console.error('Error saving changes:', error);
        toast.add({ type: 'error', title: wTrans('public.failed_to_save_changes') });
    } finally {
        loading.value = false;
    }
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const filterText = ref('');

const handleFilter = (event) => {
    filterText.value = event.value;
};
</script>

<template>
    <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:px-6 md:gap-6">
        <form class="self-stretch">
            <div class="flex flex-col justify-center items-start gap-3 self-stretch">
                <div class="flex flex-row items-center justify-between self-stretch">
                    <span class="text-sm font-semibold text-gray-950">{{ $t("public.agent_ticket_access") }}</span>
                    <AddAnother 
                        actionType="agent_ticket_access"
                        :agents="agents"
                    />
                </div>
                <div class="flex flex-col items-center self-stretch">
                    <DataTable
                        :value="agentAccesses"
                        tableStyle="md:min-width: 50rem"
                        ref="dt"
                        :loading="loading"
                        editMode="cell" 
                        @cell-edit-complete="onCellEditComplete"
                    >
                        <Column
                            field="user"
                            :header="$t('public.agent')"
                            class="w-1/2"
                        >
                            <template #body="slotProps">
                                {{ slotProps.data.user.name }}
                            </template>
                            <template #editor="{ data, field }">
                                <div class="w-full flex flex-col">
                                    <Select
                                        v-model="data[field]"
                                        :options="filterText.length > 0 ? agents : []"
                                        filter
                                        :filterFields="['name', 'email']"
                                        optionLabel="name"
                                        :placeholder="$t('public.select_agent')"
                                        class="w-full font-normal md:w-60"
                                        @filter="handleFilter"
                                    />
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="access_level"
                            :header="$t('public.access_level')"
                            class="w-1/2"
                        >
                            <template #body="slotProps">
                                {{ getAccessLevelDisplay(slotProps.data.access_level) }}
                            </template>
                            <template #editor="{ data, field }">
                                <div class="w-full flex flex-col">
                                    <Select
                                        v-model="data[field]"
                                        :options="accessLevels"
                                        optionLabel="name"
                                        optionValue="value"
                                        :placeholder="$t('public.select_access_level')"
                                        class="w-full font-normal md:w-60"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
                <Button
                    type="button"
                    variant="primary-flat"
                    class="self-end"
                    @click="saveChanges"
                >
                    {{ $t('public.save_changes') }}
                </Button>
            </div>
        </form>
    </div>
</template>