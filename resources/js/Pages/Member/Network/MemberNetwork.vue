<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {IconSearch, IconCircleXFilled, IconUserCircle, IconChevronUp, IconMinus} from "@tabler/icons-vue";
import InputText from "primevue/inputtext";
import Button from "@/Components/Button.vue";
import {ref, watch} from "vue";
import Toggleswitch from 'primevue/toggleswitch';
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";

const search = ref('');
const checked = ref(true);
const upline = ref(null);
const parent = ref([]);
const children = ref([]);
const upline_id = ref();
const parent_id = ref();
const loading = ref(false);

const { formatAmount } = transactionFormat();

const getNetwork = async (filterUplineId = upline_id.value, filterParentId = parent_id.value, filterSearch = search.value) => {
    loading.value = true;
    try {
        let url = `/member/getDownlineData?search=` + filterSearch;

        if (filterUplineId) {
            url += `&upline_id=${filterUplineId}`;
        }

        if (filterParentId) {
            url += `&parent_id=${filterParentId}`;
        }

        const response = await axios.get(url);

        upline.value = response.data.upline;
        parent.value = response.data.parent;
        children.value = response.data.direct_children;
    } catch (error) {
        console.error('Error get network:', error);
    } finally {
        loading.value = false;
    }
};

getNetwork();

watch(search,
    debounce((newSearchValue) => {
        getNetwork(upline_id.value, parent_id.value, newSearchValue)
    }, 300)
);

const selectDownline = (downlineId) => {
    upline_id.value = parent.value.id;
    parent_id.value = downlineId;

    getNetwork(upline_id.value, parent_id.value)
}

const collapseAll = () => {
    upline_id.value = null;
    parent_id.value = null;
    getNetwork()
}

const backToUpline = (parentLevel) => {
    if (parentLevel === 1) {
        upline_id.value = null;
        parent_id.value = null;
        getNetwork()
    } else {
        parent_id.value = parent.value.upline_id;
        upline_id.value = parent.value.upper_upline_id;
        getNetwork(upline_id.value, parent_id.value)
    }
}

const clearSearch = () => {
    search.value = '';
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_network')">
        <div class="w-full flex flex-col justify-center items-center px-3 py-5 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-10">
            <div class="flex flex-col md:flex-row gap-3 items-center self-stretch">
                <div class="relative w-full md:w-60">
                    <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                        <IconSearch size="20" stroke-width="1.25" />
                    </div>
                    <InputText v-model="search" :placeholder="$t('public.search')" size="search" class="font-normal w-full md:w-60" />
                    <div
                        v-if="search"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                        @click="clearSearch"
                    >
                        <IconCircleXFilled size="16" />
                    </div>
                </div>
                <div class="grid grid-cols-2 w-full gap-3">
                    <div class="flex items-center gap-1">
                            <Toggleswitch v-model="checked" class="w-[42px] h-6" />
                        <div class="text-gray-950">
                            {{ $t('public.show_upline') }}
                        </div>
                    </div>
                    <div class="w-full flex justify-end">
                        <Button
                            variant="primary-outlined"
                            @click="collapseAll"
                            class="w-full md:w-auto flex gap-1"
                        >
                            <IconMinus size="20" stroke-width="1.25" />
                            {{ $t('public.collapse_all') }}
                        </Button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center gap-6 w-full">
                <!-- Upline Section -->
                <div v-if="checked && upline" class="flex flex-col items-center gap-6 w-full">
                    <div class="flex items-center self-stretch gap-3">
                        <span class="text-sm font-medium text-gray-700 uppercase">{{ $t('public.level' ) }} {{ upline.level ?? 0 }}</span>
                        <div class="h-[1px] flex-1 bg-gray-200" />
                    </div>

                    <!-- loading state -->
                    <div v-if="loading" class="flex justify-center flex-wrap w-full">
                        <div
                            class="rounded flex flex-col items-center md:max-w-[215px] shadow-card border-l-4 select-none cursor-pointer md:basis-1/3 xl:basis-1/4 bg-white"
                            :class="{
                                'border-primary-600 hover:border-t hover:border-t-primary-600 hover:border-b hover:border-b-primary-600 hover:border-r hover:border-r-primary-600': upline.length === 0 || upline.level === 0,
                                'border-orange hover:border-t hover:border-t-orange hover:border-b hover:border-b-orange hover:border-r hover:border-r-orange': upline.role === 'agent',
                                'border-info-500 hover:border-t hover:border-t-info-500 hover:border-b hover:border-b-info-500 hover:border-r hover:border-r-info-500': upline.role === 'member',
                            }"
                        >
                            <div class="pt-3 pb-2 px-3 rounded-t flex flex-col items-center w-full self-stretch animate-pulse">
                                <div class="w-full font-semibold text-gray-950 truncate">
                                    <div class="h-2.5 bg-gray-200 rounded-full mt-1 mb-2"></div>
                                </div>
                                <div class="w-full text-sm text-gray-500 truncate">
                                    <div class="h-2.5 bg-gray-200 rounded-full w-14 mb-1"></div>
                                </div>
                            </div>
                            <div class="pb-2 px-3 rounded-b flex items-center gap-3 w-full self-stretch">
                                <div class="flex gap-1 items-center w-full animate-pulse py-[1px]">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-info-500">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full w-6"></div>

                                </div>
                                <div class="flex gap-1 items-center w-full animate-pulse py-[1px]">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-orange">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full w-6"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex justify-center flex-wrap w-full relative">
                        <div
                            class="rounded flex flex-col items-center md:max-w-[215px] shadow-card border-l-4 select-none cursor-pointer md:basis-1/3 xl:basis-1/4 bg-white"
                            :class="{
                                'border-orange hover:border-t hover:border-t-orange hover:border-b hover:border-b-orange hover:border-r hover:border-r-orange': upline.role === 'agent',
                                'border-info-500 hover:border-t hover:border-t-info-500 hover:border-b hover:border-b-info-500 hover:border-r hover:border-r-info-500': upline.role === 'member',
                                'border-primary-600 hover:border-t hover:border-t-primary-600 hover:border-b hover:border-b-primary-600 hover:border-r hover:border-r-primary-600': upline.length === 0 || upline.level === 0,
                            }"
                        >
                            <div class="pt-3 pb-2 px-3 rounded-t flex flex-col items-center w-full self-stretch">
                                <div class="w-full font-semibold text-gray-950 truncate">
                                    {{ upline.name }}
                                </div>
                                <div class="w-full text-sm text-gray-500 truncate">
                                    {{ upline.id_number }}
                                </div>
                            </div>
                            <div class="pb-2 px-3 rounded-b flex items-center gap-3 w-full self-stretch">
                                <div class="flex gap-1 items-center w-1/2">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-info-500">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="text-sm text-gray-950 font-medium truncate">
                                        {{ formatAmount(upline.total_member_count, 0) }}
                                    </div>
                                </div>
                                <div class="flex gap-1 items-center w-1/2">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-orange">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="text-sm text-gray-950 font-medium truncate">
                                        {{ formatAmount(upline.total_agent_count, 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Parent Section -->
                <div  v-if="(parent.level === 0 && checked) || (parent.level !== 0 && parent)" class="flex flex-col items-center gap-6 w-full">
                    <div class="flex items-center self-stretch gap-3">
                        <span class="text-sm font-medium text-gray-700 uppercase">{{ $t('public.level' ) }} {{ parent.level ?? 0 }}</span>
                        <div class="h-[1px] flex-1 bg-gray-200" />
                    </div>

                    <!-- loading state -->
                    <div v-if="loading" class="flex justify-center flex-wrap w-full">
                        <div
                            class="rounded flex flex-col items-center md:max-w-[215px] shadow-card border-l-4 select-none cursor-pointer md:basis-1/3 xl:basis-1/4 bg-white"
                            :class="{
                                // 'border-primary-600 hover:border-t hover:border-t-primary-600 hover:border-b hover:border-b-primary-600 hover:border-r hover:border-r-primary-600': parent.length === 0 || parent.level === 0,
                                'border-orange hover:border-t hover:border-t-orange hover:border-b hover:border-b-orange hover:border-r hover:border-r-orange': parent && parent.role === 'agent',
                                'border-info-500 hover:border-t hover:border-t-info-500 hover:border-b hover:border-b-info-500 hover:border-r hover:border-r-info-500': parent && parent.role === 'member',
                            }"
                        >
                            <div class="pt-3 pb-2 px-3 rounded-t flex flex-col items-center w-full self-stretch animate-pulse">
                                <div class="w-full font-semibold text-gray-950 truncate">
                                    <div class="h-2.5 bg-gray-200 rounded-full mt-1 mb-2"></div>
                                </div>
                                <div class="w-full text-sm text-gray-500 truncate">
                                    <div class="h-2.5 bg-gray-200 rounded-full w-14 mb-1"></div>
                                </div>
                            </div>
                            <div class="pb-2 px-3 rounded-b flex items-center gap-3 w-full self-stretch">
                                <div class="flex gap-1 items-center w-full animate-pulse py-[1px]">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-info-500">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full w-6"></div>

                                </div>
                                <div class="flex gap-1 items-center w-full animate-pulse py-[1px]">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-orange">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full w-6"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex justify-center flex-wrap w-full relative">
                        <div class="absolute top-[-18px]">
                            <Button
                                type="button"
                                variant="gray-outlined"
                                size="sm"
                                pill
                                iconOnly
                                v-if="upline_id && !loading"
                                @click="backToUpline(parent.level)"
                            >
                                <IconChevronUp size="16" color="#0C111D" stroke-width="1.25"/>
                            </Button>
                        </div>
                        <div
                            class="rounded flex flex-col items-center md:max-w-[215px] shadow-card border-l-4 select-none cursor-pointer md:basis-1/3 xl:basis-1/4 bg-white"
                            :class="{
                                'border-primary-600 hover:border-t hover:border-t-primary-600 hover:border-b hover:border-b-primary-600 hover:border-r hover:border-r-primary-600': parent.length === 0 || parent.level === 0,
                                'border-orange hover:border-t hover:border-t-orange hover:border-b hover:border-b-orange hover:border-r hover:border-r-orange': parent.role === 'agent',
                                'border-info-500 hover:border-t hover:border-t-info-500 hover:border-b hover:border-b-info-500 hover:border-r hover:border-r-info-500': parent.role === 'member',
                            }"
                        >
                            <div class="pt-3 pb-2 px-3 rounded-t flex flex-col items-center w-full self-stretch">
                                <div class="w-full font-semibold text-gray-950 truncate">
                                    {{ parent.name }}
                                </div>
                                <div class="w-full text-sm text-gray-500 truncate">
                                    {{ parent.id_number }}
                                </div>
                            </div>
                            <div class="pb-2 px-3 rounded-b flex items-center gap-3 w-full self-stretch">
                                <div class="flex gap-1 items-center w-1/2">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-info-500">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="text-sm text-gray-950 font-medium truncate">
                                        {{ formatAmount(parent.total_member_count, 0) }}
                                    </div>
                                </div>
                                <div class="flex gap-1 items-center w-1/2">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-orange">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="text-sm text-gray-950 font-medium truncate">
                                        {{ formatAmount(parent.total_agent_count, 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Children Section -->
                <div v-if="children.length" class="flex flex-col items-center gap-6 w-full">
                    <div class="flex items-center self-stretch gap-3">
                        <span class="text-sm font-medium text-gray-700 uppercase">{{ $t('public.level' ) }} {{ children[0].level ?? 0 }}</span>
                        <div class="h-[1px] flex-1 bg-gray-200" />
                    </div>

                    <!-- loading state -->
                    <div v-if="loading" class="flex justify-center flex-wrap w-full">
                        <div
                            class="rounded flex flex-col items-center md:max-w-[215px] shadow-card border-l-4 select-none cursor-pointer md:basis-1/3 xl:basis-1/4 bg-white"
                            :class="{
                                // 'border-primary-600 hover:border-t hover:border-t-primary-600 hover:border-b hover:border-b-primary-600 hover:border-r hover:border-r-primary-600': parent && parent.role === 'member',
                                'border-orange hover:border-t hover:border-t-orange hover:border-b hover:border-b-orange hover:border-r hover:border-r-orange': parent && parent.role === 'agent',
                                'border-info-500 hover:border-t hover:border-t-info-500 hover:border-b hover:border-b-info-500 hover:border-r hover:border-r-info-500': parent && parent.role === 'member',
                            }"
                        >
                            <div class="pt-3 pb-2 px-3 rounded-t flex flex-col items-center w-full self-stretch animate-pulse">
                                <div class="w-full font-semibold text-gray-950 truncate">
                                    <div class="h-2.5 bg-gray-200 rounded-full mt-1 mb-2"></div>
                                </div>
                                <div class="w-full text-sm text-gray-500 truncate">
                                    <div class="h-2.5 bg-gray-200 rounded-full w-14 mb-1"></div>
                                </div>
                            </div>
                            <div class="pb-2 px-3 rounded-b flex items-center gap-3 w-full self-stretch">
                                <div class="flex gap-1 items-center w-1/2 animate-pulse py-[1px]">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-info-500">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full w-6"></div>

                                </div>
                                <div class="flex gap-1 items-center w-1/2 animate-pulse py-[1px]">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-orange">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full w-6"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-2 md:flex gap-3 md:gap-5 justify-center flex-wrap w-full">
                        <div
                            v-for="downline in children"
                            :key="downline.id"
                            class="rounded flex flex-col items-center md:max-w-[215px] shadow-card border-l-4 select-none cursor-pointer md:basis-1/3 xl:basis-1/4 bg-white"
                            :class="{
                                // 'border-primary-600 hover:border-t hover:border-t-primary-600 hover:border-b hover:border-b-primary-600 hover:border-r hover:border-r-primary-600': downline.role === 'member',
                                'border-orange hover:border-t hover:border-t-orange hover:border-b hover:border-b-orange hover:border-r hover:border-r-orange': downline.role === 'agent',
                                'border-info-500 hover:border-t hover:border-t-info-500 hover:border-b hover:border-b-info-500 hover:border-r hover:border-r-info-500': downline.role === 'member',
                            }"
                            @click="selectDownline(downline.id)"
                        >
                            <div class="pt-3 pb-2 px-3 rounded-t flex flex-col items-center w-full self-stretch">
                                <div class="w-full font-semibold text-gray-950 truncate">
                                    {{ downline.name }}
                                </div>
                                <div class="w-full text-sm text-gray-500 truncate">
                                    {{ downline.id_number }}
                                </div>
                            </div>
                            <div class="pb-2 px-3 rounded-b flex items-center gap-3 w-full self-stretch">
                                <div class="flex gap-1 items-center w-1/2">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-info-500">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="text-sm text-gray-950 font-medium truncate">
                                        {{ formatAmount(downline.total_member_count, 0) }}
                                    </div>
                                </div>
                                <div class="flex gap-1 items-center w-1/2">
                                    <div class="flex items-center justify-center rounded-full grow-0 shrink-0 text-orange">
                                        <IconUserCircle size="20" />
                                    </div>
                                    <div class="text-sm text-gray-950 font-medium truncate">
                                        {{ formatAmount(downline.total_agent_count, 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>