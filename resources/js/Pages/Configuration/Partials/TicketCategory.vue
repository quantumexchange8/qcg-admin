<script setup>
import Button from "@/Components/Button.vue";
import { ref, watch, watchEffect } from "vue";
import { useForm } from "@inertiajs/vue3";
import {
    IconPlaylistAdd
} from '@tabler/icons-vue';
import {usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from 'primevue/inputtext';
import toast from '@/Composables/toast';
import { wTrans, trans } from "laravel-vue-i18n";

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

const addNewCategory = () => {
    ticketCategories.value.push({
        id: ticketCategories.value.length + 1, // Fake ID generation, when save in backend, dont save the id? (need test)
        category: {
            en: 'en',
            cn: 'cn',
            tw: 'tw',
        },
        order: ticketCategories.value.length + 2,
    });
};

const onCellEditComplete = (event) => { 
    // console.log(ticketCategories);
}

const saveChanges = async () => {
    try {
        loading.value = true;

        await axios.post('/configuration/updateTicketCategories', {
            categories: ticketCategories.value
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

</script>

<template>
    <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:px-6 md:gap-6">
        <form class="self-stretch">
            <div class="flex flex-col justify-center items-start gap-3 self-stretch">
                <div class="flex flex-row items-center justify-between self-stretch">
                    <span class="text-sm font-semibold text-gray-950">{{ $t("public.ticket_category") }}</span>
                    <Button
                        type="button"
                        variant="primary-text"
                        @click="addNewCategory"
                    >
                        <IconPlaylistAdd stroke-width="1.25" class="w-4 h-4" />
                        {{ $t('public.add_another') }}
                    </Button>
                </div>
                <div class="flex flex-col items-center self-stretch">
                    <DataTable
                        :value="ticketCategories"
                        tableStyle="md:min-width: 50rem"
                        ref="dt"
                        :loading="loading"
                        editMode="cell" 
                        @cell-edit-complete="onCellEditComplete"
                    >
                        <template>
                            <Column
                                field="order"
                                :header="`t`"
                                style="width:7%;"
                            >
                                <template #body="slotProps">
                                    te
                                </template>
                            </Column>
                            <Column
                                field="en"
                                :header="`${$t('public.english')}`"
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
                                :header="`${$t('public.simplified_chinese')}`"
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
                                :header="`${$t('public.traditional_chinese')}`"
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

                    <!-- <div class="flex items-center w-full self-stretch py-3 text-gray-700 bg-gray-50 border-b border-gray-100">
                        <span class="uppercase text-xs font-bold px-3 w-full">{{ $t('public.english') }}</span>
                        <span class="uppercase text-xs font-bold px-3 w-full">{{ $t('public.simplified_chinese') }}</span>
                        <span class="uppercase text-xs font-bold px-3 w-full">{{ $t('public.traditional_chinese') }}</span>
                    </div> -->

                    <!-- symbol groups -->
                    <!-- <div
                        v-if="tradeDetails"
                        v-for="tradeDetail in tradeDetails"
                        class="flex items-center w-full self-stretch py-2 text-gray-950"
                    >
                        <div class="text-sm px-3 w-full">{{ $t(`public.${tradeDetail.symbol_group.display}`) }}</div>
                        <div class="text-sm px-3 w-full md:text-left text-right">{{ formatAmount(tradeDetail.trade_point_rate, 2) }}</div>
                    </div>
                    <div
                        v-else
                        v-for="index in 5"
                        class="flex items-center w-full self-stretch py-2 text-gray-950 animate-pulse"
                    >
                        <div class="w-full">
                            <div class="h-2.5 bg-gray-200 rounded-full w-36 mt-1 mb-1.5"></div>
                        </div>
                        <div class="w-full">
                            <div class="h-2.5 bg-gray-200 rounded-full w-10 mt-1 mb-1.5"></div>
                        </div>
                    </div> -->
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