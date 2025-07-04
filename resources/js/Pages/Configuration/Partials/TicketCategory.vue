<script setup>
import Button from "@/Components/Button.vue";
import { ref, watch, watchEffect } from "vue";
import {usePage} from "@inertiajs/vue3";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from 'primevue/inputtext';
import toast from '@/Composables/toast';
import { wTrans, trans } from "laravel-vue-i18n";
import AddAnother from "./AddAnother.vue";

const ticketCategories = ref();
const dt = ref();
const loading = ref(false);

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/configuration/getTicketCategories');
        
        ticketCategories.value = response.data.ticketCategories;
    } catch (error) {
        console.error('Error getting categories:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const onCellEditComplete = (event) => { 
    // console.log(event);
}

const saveChanges = async () => {
    try {
        loading.value = true;

        await axios.post('/configuration/updateTicketCategories', {
            categories: ticketCategories.value
        })

        toast.add({ type: 'success', title: wTrans('public.changes_saved_successfully') });

    } catch (error) {
        console.error('Error saving changes:', error);
        toast.add({ type: 'error', title: wTrans('public.failed_to_save_changes') });
    } finally {
        loading.value = false;
    }
};

const onRowReorder = (event) => {
    ticketCategories.value = event.value;
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});
</script>

<template>
    <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:px-6 md:gap-6">
        <form class="self-stretch">
            <div class="flex flex-col justify-center items-start gap-3 self-stretch">
                <div class="flex flex-row items-center justify-between self-stretch">
                    <span class="text-sm font-semibold text-gray-950 ">{{ $t("public.ticket_category") }}</span>
                    <AddAnother 
                        actionType="ticket_category"
                    />
                </div>
                <div class="flex flex-col items-center self-stretch">
                    <DataTable
                        :value="ticketCategories"
                        tableStyle="md:min-width: 50rem"
                        ref="dt"
                        :loading="loading"
                        @rowReorder="onRowReorder"
                        editMode="cell" 
                        @cell-edit-complete="onCellEditComplete"
                    >
                        <template>
                            <Column rowReorder style="width:7%;" />
                            <Column
                                field="en"
                                :header="$t('public.english')"
                                style="width:31%;"
                            >
                                <template #body="slotProps">
                                    {{ slotProps.data.category['en'] }}
                                </template>
                                <template #editor="{ data, field }">
                                    <div class="w-full flex flex-col">
                                        <InputText
                                            v-model="data.category[field]"
                                            type="text"
                                            class="block w-full" 
                                        />
                                    </div>
                                </template>
                            </Column>
                            <Column
                                field="cn"
                                :header="$t('public.simplified_chinese')"
                                style="width:31%;"
                            >
                                <template #body="slotProps">
                                    {{ slotProps.data.category['cn'] }}
                                </template>
                                <template #editor="{ data, field }">
                                    <div class="w-full flex flex-col">
                                        <InputText
                                            v-model="data.category[field]"
                                            type="text"
                                            class="block w-full" 
                                        />
                                    </div>
                                </template>
                            </Column>
                            <Column
                                field="tw"
                                :header="$t('public.traditional_chinese')"
                                style="width:31%;"
                            >
                                <template #body="slotProps">
                                    {{ slotProps.data.category['tw'] }}
                                </template>
                                <template #editor="{ data, field }">
                                    <div class="w-full flex flex-col">
                                        <InputText
                                            v-model="data.category[field]"
                                            type="text"
                                            class="block w-full" 
                                        />
                                    </div>
                                </template>
                            </Column>
                        </template>
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