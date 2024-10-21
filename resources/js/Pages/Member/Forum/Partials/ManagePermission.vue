<script setup>
import { IconSearch, IconCircleXFilled } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted, onUnmounted } from "vue";
import Button from "@/Components/Button.vue";
import Dialog from "primevue/dialog";
import InputText from 'primevue/inputtext';
import TogglePermission from '@/Pages/Member/Forum/Partials/TogglePermission.vue';
import Loader from "@/Components/Loader.vue";
import debounce from "lodash/debounce.js";

const props = defineProps({
    isLoading: Boolean,
    search: String,
    selectedAgents: Array,
    agents: Array,
});

const emit = defineEmits(['update:search']);

const visible = ref(false);
const search = ref('');
const selectedAgents = ref([]);
const agents = ref([]);
const isLoading = ref(false);

watch(
  () => [props.search, props.selectedAgents, props.agents, props.isLoading], 
  ([newSearch, newSelectedAgents, newAgents, newIsLoading]) => {
    search.value = newSearch;
    selectedAgents.value = newSelectedAgents;
    agents.value = newAgents;
    isLoading.value = newIsLoading;
  },
  { immediate: true }
);

const clearSearch = () => {
    search.value = '';
    emit('update:search', search.value);
};

watch(search, debounce((newSearch) => {
    emit('update:search', newSearch);
}, 300));

watch(visible, (newValue) => {
    if (!newValue) {
        // clearSearch();
    }
});

const checkWindowWidth = () => {
    if (window.innerWidth > 768) {
        visible.value = false;
    }
};

onMounted(() => {
    window.addEventListener('resize', checkWindowWidth);
    checkWindowWidth();
});

onUnmounted(() => {
    window.removeEventListener('resize', checkWindowWidth);
});
</script>

<template>
    <Button
        type="button"
        variant="primary-outlined"
        class="w-full md:hidden"
        @click="visible = true"
    >
        {{ $t('public.manage_permission') }} 
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.manage_permission')"
        class="dialog-xs"
    >
        <div class="flex flex-col items-center py-4 gap-4 self-stretch md:py-6 md:gap-6">
            <div class="relative w-full">
                <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                    <IconSearch size="20" stroke-width="1.25" />
                </div>
                <InputText v-model="search" :placeholder="$t('public.keyword_search')" size="search" class="font-normal w-full" />
                <div
                    v-if="search"
                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                    @click="clearSearch"
                >
                    <IconCircleXFilled size="16" />
                </div>
            </div>

            <div v-if="isLoading" class="flex flex-col gap-2 items-center justify-center">
                <Loader />
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>

            <div v-else class="w-full flex flex-col items-center self-stretch overflow-y-auto">
                <!-- Selected -->
                <div v-if="selectedAgents.length > 0" class="flex py-3 items-center self-stretch">
                    <span class="self-stretch text-gray-500 text-xs font-medium uppercase">{{ $t('public.selected_agent') }}</span>
                </div>
                <div 
                    v-for="selected in selectedAgents"
                    class="flex items-center py-2 gap-3 self-stretch border-b border-gray-50"
                >
                    <div class="w-full truncate flex flex-col items-start">
                        <span class="truncate self-stretch text-gray-950 text-sm font-semibold">{{ selected.first_name }}</span>
                        <span class="truncate self-stretch text-gray-500 text-xs">{{ selected.email }}</span>
                    </div>
                    <TogglePermission
                        :agent="selected"
                    />
                </div>

                <!-- Agents -->
                 <div v-if="agents.length > 0" class="flex py-3 items-center self-stretch">
                    <span class="self-stretch text-gray-500 text-xs font-medium uppercase">{{ $t('public.agents') }}</span>
                 </div>
                 <div 
                    v-for="agent in agents"
                    class="flex items-center py-2 gap-3 self-stretch border-b border-gray-50"
                >
                    <div class="w-full truncate flex flex-col items-start">
                        <span class="truncate self-stretch text-gray-950 text-sm font-semibold">{{ agent.first_name }}</span>
                        <span class="truncate self-stretch text-gray-500 text-xs">{{ agent.email }}</span>
                    </div>
                    <TogglePermission
                        :agent="agent"
                    />
                </div>
                <div v-if="agents.length === 0" class="self-stretch text-gray-500 text-center text-sm">
                    {{ $t('public.empty_agents') }}
                </div>
            </div>
        </div>
    </Dialog>

</template>
