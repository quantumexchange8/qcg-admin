<script setup>
import Button from "@/Components/Button.vue";
import { ref, watch, watchEffect, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import {
    IconPlaylistAdd
} from '@tabler/icons-vue';
import {usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { trans, wTrans } from "laravel-vue-i18n";
import Select from "primevue/select";

const accessLevels = [
    { name: wTrans('public.view_only'), value: 'view' },
    { name: wTrans('public.full_access'), value: 'full' },
];

const agentAccesses = ref();
const dt = ref();
const loading = ref(false);

const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/configuration/getAgentAccesses');
        
        agentAccesses.value = response.data.agentAccesses;
        console.log(agentAccesses)
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
</script>

<template>
    <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:px-6 md:gap-6">
        <form class="self-stretch">
            <div class="flex flex-col justify-center items-start gap-3 self-stretch">
                <div class="flex flex-row items-center justify-between self-stretch">
                    <span class="text-sm font-semibold text-gray-950">{{ $t("public.agent_ticket_access") }}</span>
                    <Button
                        type="button"
                        variant="primary-text"
                        @click=""
                    >
                        <IconPlaylistAdd stroke-width="1.25" class="w-4 h-4" />
                        {{ $t('public.add_another') }}
                    </Button>
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
                        <template>
                            <Column
                                field="agent"
                                :header="`${$t('public.agent')}`"
                            >
                                <template #body="slotProps">
                                    {{ slotProps.data.user.chinese_name ?? slotProps.data.user.first_name }}
                                </template>
                            </Column>
                            <Column
                                field="access_level"
                                :header="`${$t('public.access_level')}`"
                            >
                                <template #body="slotProps">
                                    {{ getAccessLevelDisplay(slotProps.data.access_level) }}
                                </template>
                            </Column>
                        </template>
                    </DataTable>

                    <Select
                        
                        :options="accessLevels"
                        optionLabel="name"
                        optionValue="value"
                        :placeholder="$t('public.select_access_level')"
                        class="w-full font-normal md:w-60"
                    />

                    <!-- <div class="flex items-center w-full self-stretch py-3 text-gray-700 bg-gray-50 border-b border-gray-100">
                        <span class="uppercase text-xs font-bold px-3 w-full">{{ $t('public.agent') }}</span>
                        <span class="uppercase text-xs font-bold px-3 w-full md:text-left text-right">{{ $t('public.access_level') }}</span>
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
                    variant="primary-flat"
                    class="self-end"
                >
                    {{ $t('public.save_changes') }}
                </Button>
            </div>
        </form>
    </div>
</template>