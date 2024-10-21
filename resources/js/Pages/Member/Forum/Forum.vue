<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { IconSearch, IconCircleXFilled } from "@tabler/icons-vue";
import { ref, watch, watchEffect } from "vue";
import { usePage } from "@inertiajs/vue3";
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import CreatePost from '@/Pages/Member/Forum/Partials/CreatePost.vue';
import ManagePermission from '@/Pages/Member/Forum/Partials/ManagePermission.vue';
import ForumPost from '@/Pages/Member/Forum/Partials/ForumPost.vue';
import TogglePermission from '@/Pages/Member/Forum/Partials/TogglePermission.vue';
import Loader from "@/Components/Loader.vue";
import debounce from "lodash/debounce.js";

const props = defineProps({
    postCounts: Number,
})

const search = ref('');
const selectedAgents = ref([]);
const agents = ref([]);
const isLoading = ref(false);

const getAgents = async () => {
    isLoading.value = true;
    try {
        let url = '/member/getAgents';

        if (search.value) {
            url += `?search=${search.value}`;
        }

        const response = await axios.get(url);
        selectedAgents.value = response.data.selectedAgents;
        agents.value = response.data.agents;
    } catch (error) {
        console.error('Error changing agents:', error);
    } finally {
        isLoading.value = false;
    }
};

getAgents();

const clearSearch = () => {
    search.value = '';
}

watch(search, debounce(() => {
    getAgents();
}, 300));

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getAgents();
    }
});

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_forum')">
        <div class="w-full md:h-[90vh] flex flex-col items-center gap-5 md:flex-row md:justify-center md:items-start">
            <div class="w-full md:max-w-80 flex flex-col items-center gap-5 self-stretch">
                <div class="flex flex-col items-start px-3 py-5 gap-5 self-stretch rounded-lg bg-white md:p-6">
                    <span class="text-gray-500 text-sm">{{ $t('public.ready_share_something') }}</span>
                    <div class="flex flex-col items-center gap-3 self-stretch">
                        <CreatePost />
                        <ManagePermission
                            :isLoading="isLoading"
                            :search="search"
                            :selectedAgents="selectedAgents"
                            :agents="agents"
                            @update:search="(value) => search = value"
                        />
                    </div>
                </div>

                <div class="w-full h-full hidden md:flex flex-col items-center px-6 pt-6 gap-3 self-stretch rounded-lg bg-white shadow-card overflow-y-auto">
                    <div class="flex flex-col items-center gap-5 self-stretch bg-white">
                        <span class="self-stretch text-gray-950 text-sm font-bold">{{ $t('public.manage_posting_permissions') }}</span>
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
                    </div>
                    <div class="w-full flex flex-col items-center self-stretch overflow-y-auto">
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
                </div>
            </div>
            <ForumPost :postCounts="props.postCounts" />
        </div>
    </AuthenticatedLayout>
</template>
