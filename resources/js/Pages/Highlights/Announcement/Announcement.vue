<script setup>
import { ref, watch, watchEffect, computed, onMounted, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import Loader from "@/Components/Loader.vue";
import debounce from "lodash/debounce.js";
import Select from "primevue/select";
import Button from '@/Components/Button.vue';
import { FilterMatchMode } from '@primevue/core/api';
import { IconSearch, IconCircleXFilled, IconPlus, IconInfoCircle, IconX, IconMenu2 } from "@tabler/icons-vue";
import { trans, wTrans } from "laravel-vue-i18n";
import Empty from "@/Components/Empty.vue";
import dayjs from "dayjs";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import StatusBadge from '@/Components/StatusBadge.vue';
import Column from "primevue/column";
import ColumnGroup from "primevue/columngroup";
import Row from "primevue/row";
import Tag from 'primevue/tag';
import CreateAnnouncement from "./Partials/CreateAnnouncement.vue";
import DeleteAnnouncement from "./Partials/Delete.vue";
import Edit from "./Partials/Edit.vue";
import Action from "./Partials/Action.vue";
import 'mobile-drag-drop/default.css';
import { polyfill } from 'mobile-drag-drop';

const statusOption = [
    { name: wTrans('public.all'), value: null },
    { name: wTrans('public.active'), value: 'active' },
    { name: wTrans('public.inactive'), value: 'inactive' },
    { name: wTrans('public.scheduled'), value: 'scheduled' },
    { name: wTrans('public.draft'), value: 'draft' },
    { name: wTrans('public.expired'), value: 'expired' }
];

const announcements = ref([]);
const loading = ref(false);
// const filteredValue = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    title: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

// const handleFilter = (e) => {
//     filteredValue.value = e.filteredValue;
// };

const getResults = async () => {
    loading.value = true;

    try {
        let url = `/highlights/getAnnouncement`;

        const response = await axios.get(url);
        announcements.value = response.data.announcements;

    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const noticeVisible = ref(true);

function formatColor(status) {
    switch (status) {
        case 'active':
            return 'bg-success-500 text-white';
        case 'inactive':
            return 'bg-gray-500 text-white';
        case 'scheduled':
            return 'bg-warning-500 text-white';
        case 'draft':
            return 'bg-info-500 text-white';
        case 'expired':
            return 'bg-error-600 text-white';
        default:
            return 'bg-gray-500 text-white';
    }
}

watch(() => usePage().props.toast, (newValue) => {
    if (newValue !== null) {
        visible.value = false;
        getResults();
    }
});

const visible = ref(false);
const data = ref({});
const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};

const draggedAnnouncement = ref(null);

const onDragStart = (item) => {
  draggedAnnouncement.value = item;
};

const MAX_PINNED_COUNT = 5;

const onDropToPin = async () => {
  if (draggedAnnouncement.value) {
    if (pinnedAnnouncements.value.length >= MAX_PINNED_COUNT) {
    //   pinnedAnnouncements.value.shift(); // Removes the first item (oldest)

      alert('You can only pin up to 5 announcements!');
      return;
    }

    draggedAnnouncement.value.pinned = true;

    try {
        // console.log('Sending pin/unpin request for ID:', draggedAnnouncement.value.id);
        const response = await axios.post(`/highlights/announcements/${draggedAnnouncement.value.id}/pin`, {
            pinned: true
        });
        // console.log(response.data);
    } catch (error) {
      console.error('Error pinning announcement:', error);
    }

    draggedAnnouncement.value = null;
  }
};

const onDragEnter = (e) => {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
};

const onDragOver = (e) => {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
};

const onDropToUnpin = async () => {
  if (draggedAnnouncement.value) {
    draggedAnnouncement.value.pinned = false;

    try {
        // console.log('Sending pin/unpin request for ID:', draggedAnnouncement.value.id);
      await axios.post(`/highlights/announcements/${draggedAnnouncement.value.id}/pin`, {
        pinned: false
      });
    } catch (error) {
      console.error('Error unpinning announcement:', error);
    }
    draggedAnnouncement.value = null;
  }
};

const pinnedAnnouncements = computed(() =>
  announcements.value.filter((a) => a.pinned)
);
const regularAnnouncements = computed(() =>
  announcements.value.filter((a) => !a.pinned)
);

const pageLinkSize = ref(window.innerWidth < 768 ? 3 : 5)

const updatePageLinkSize = () => {
  pageLinkSize.value = window.innerWidth < 768 ? 3 : 5
}

onMounted(() => {
  window.addEventListener('resize', updatePageLinkSize)
})

const dt = ref(null);

onMounted(() => {
    // Only run the polyfill initialization code once across the entire application's lifecycle.
    if (!window.dragDropPolyfillLoaded) {
        // Find the root element of your DataTable component.
        const dtElement = dt.value?.$el;
        if (dtElement) {
            // Apply the polyfill with a check to ensure it's not already on the element.
            if (!dtElement.dragAndDropInitialized) {
                polyfill({
                    dragImageTranslateOverride: () => ({ x: 0, y: 0 }),
                }, dtElement);

                // Add a marker to the element to prevent re-initialization.
                dtElement.dragAndDropInitialized = true;
                
                // Mark the polyfill as loaded globally.
                window.dragDropPolyfillLoaded = true;
            }
        }
    }
});

onUnmounted(() => {
  window.removeEventListener('resize', updatePageLinkSize)
})
</script>

<template>
    <div class="flex flex-col gap-1 md:gap-2 self-stretch">
        <DataTable
            :value="[]" showGridlines
        >
            <template #header>
                <div class="flex flex-col justify-center items-center gap-5 self-stretch md:gap-6 pb-6">
                    <div class="flex flex-col justify-start xl:justify-between items-start gap-5 self-stretch xl:flex-row md:gap-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 items-center gap-3 w-full md:w-auto md:gap-5">
                            <div class="relative w-full md:max-w-60">
                                <div class="absolute top-2/4 -mt-[9px] left-3 text-gray-500">
                                    <IconSearch size="20" stroke-width="1.25" />
                                </div>
                                <InputText
                                    v-model="filters['global'].value"
                                    :placeholder="$t('public.keyword_search')"
                                    size="search"
                                    class="font-normal w-full"
                                />
                                <div
                                    v-if="filters['global'].value !== null"
                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                    @click="clearFilterGlobal"
                                >
                                    <IconCircleXFilled size="16" />
                                </div>
                            </div>
                            <Select
                                v-model="filters['status'].value"
                                :options="statusOption"
                                optionLabel="name"
                                optionValue="value"
                                :placeholder="$t('public.filter_by_status')"
                                class="w-full md:max-w-60 font-normal"
                                scroll-height="236px"
                            >
                                <template #value="data">
                                    <span class="font-normal text-gray-950" >{{ $t('public.' + (data.value || 'all')) }}</span>
                                </template>
                            </Select>
                        </div>
                        <div class="w-full flex flex-col items-center gap-3 md:w-auto md:flex-row md:gap-2">
                            <CreateAnnouncement />
                        </div>
                    </div>
                    
                    <TransitionGroup
                        tag="div"
                        enter-from-class="-translate-y-full opacity-0"
                        enter-active-class="duration-300"
                        leave-active-class="duration-300"
                        leave-to-class="-translate-y-full opacity-0"
                        class="w-full"
                    >
                        <div
                            v-if="noticeVisible"
                            class="p-2 md:py-4 md:px-5 flex justify-center self-stretch gap-3 border-l-8 rounded border-info-500 shadow-card bg-info-100 items-start"
                            role="alert"
                        >
                            <div class="text-info-500">
                                <IconInfoCircle size="24" stroke-width="2.0"/>
                            </div>
                            <div
                                class="flex flex-col gap-1 items-start w-full"
                            >
                                <div class="text-info-500 font-semibold text-sm">
                                    {{ $t('public.pinned_announcements') }}
                                </div>
                                <div class="text-gray-700 text-xs md:text-sm font-normal">
                                    {{ $t('public.pinned_announcements_desc') }}
                                </div>
                            </div>
                            <div class="text-info-500 hover:text-info-700 hover:cursor-pointer select-none" @click="noticeVisible = false">
                                <IconX size="16" stroke-width="1.25" />
                            </div>
                        </div>
                    </TransitionGroup>
                </div>
            </template>

            <!-- Shared Column Headers  -->
            <Column field="drag" headless class="w-[12.5%] md:w-[5%]"/>
            <Column field="title" :header="$t('public.subject')" class="w-1/2 md:w-[30%]"/>
            <Column field="status" :header="$t('public.status')" class="w-1/4 md:w-[15%]"/>
            <Column field="start_date" style="width: 20%" :header="$t('public.start_date')" class="hidden md:table-cell"/>
            <Column field="end_date" style="width: 20%" :header="$t('public.expiry_date')" class="hidden md:table-cell"/>
            <Column field="action" headless class="w-[12.5%] md:w-[10%]" />
        </DataTable>

        <Empty v-if="announcements.length === 0 && loading === false"
            :title="$t('public.empty_announcement_title')" 
            :message="$t('public.empty_announcement_message')" 
        />

        <div v-else class="flex flex-col gap-1 md:gap-2 self-stretch">
            <span class="text-sm font-semibold text-gray-950 px-2">{{ $t('public.pinned_announcements') }}</span>

            <DataTable
                v-model:filters="filters"
                :value="pinnedAnnouncements"
                removableSort
                :globalFilterFields="['title']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @row-click="(event) => openDialog(event.data)"
                @dragenter="onDragEnter"
                @dragover="onDragOver"
                @drop="onDropToPin"
                class="no-column-headers drop-zone"
                tableStyle="table-layout: fixed; width: 100%;"
            >
                <template #header>

                </template>
                <template #empty>

                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                
                <template v-if="pinnedAnnouncements.length > 0">
                    <Column field="drag" headless class="w-[12.5%] md:w-[5%] px-[10px]">
                        <template #body="slotProps">
                            <div
                                draggable="true"
                                @dragstart="() => onDragStart(slotProps.data)"
                                class="cursor-move"
                                >
                                <IconMenu2 size="16" stroke-width="1.25" />
                            </div>
                        </template>
                    </Column>
                    <Column field="title" headless class="w-1/2 md:w-[30%]">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start gap-1 flex-grow overflow-hidden">
                                <span class="text-gray-950 text-sm w-full truncate">
                                    {{ slotProps.data?.title || '-' }}
                                </span>
                                <div class="flex flex-row overflow-hidden md:hidden">
                                    <span class="text-gray-500 text-xs w-full truncate">
                                        {{ 
                                            slotProps.data.start_date 
                                                ? dayjs(slotProps.data.start_date).format('YYYY/MM/DD') + ' - ' + 
                                                (slotProps.data.end_date ? dayjs(slotProps.data.end_date).format('YYYY/MM/DD') : 'N/A') 
                                                : (slotProps.data.end_date ? ' - ' + dayjs(slotProps.data.end_date).format('YYYY/MM/DD') : '-') 
                                            }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="status" headless class="w-1/4 md:w-[15%]">
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <div
                                    class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                                    :class="formatColor(slotProps.data.status)"
                                >
                                    <div
                                        class="text-white text-xs text-center"
                                    >
                                        {{ $t(`public.${slotProps.data.status}`) }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="start_date" headless style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm truncate max-w-full">
                                {{ slotProps.data.start_date ? dayjs(slotProps.data.start_date).format('YYYY/MM/DD') : '-' }}
                            </div>
                        </template>
                    </Column>
                    <Column field="end_date" headless style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm truncate max-w-full">
                                {{ slotProps.data.end_date ? dayjs(slotProps.data.end_date).format('YYYY/MM/DD') : '-' }}
                            </div>
                        </template>
                    </Column>
                    <Column field="action" headless class="w-[12.5%] md:w-[10%]">
                        <template #body="slotProps">
                            <Action 
                                :announcement="slotProps.data"
                            />
                        </template>
                    </Column>
                </template>
            </DataTable>

            <span class="text-sm font-semibold text-gray-950 px-2">{{ $t('public.announcements') }}</span>

            <DataTable
                v-model:filters="filters"
                :value="regularAnnouncements"
                :paginator="regularAnnouncements.length > 0"
                removableSort
                :rows="100"
                :pageLinkSize="pageLinkSize"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :globalFilterFields="['title']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @row-click="(event) => openDialog(event.data)"
                @dragenter="onDragEnter"
                @dragover="onDragOver"
                @drop="onDropToUnpin"
                class="no-column-headers drop-zone"
                tableStyle="table-layout: fixed; width: 100%;"
            >
                <template #header>
                </template>
                <template #empty>
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                
                <template v-if="regularAnnouncements.length > 0">
                    <Column field="drag" headless class="w-[12.5%] md:w-[5%] px-[10px]">
                        <template #body="slotProps">
                            <div
                                draggable="true"
                                @dragstart="() => onDragStart(slotProps.data)"
                                class="cursor-move"
                                >
                                <IconMenu2 size="16" stroke-width="1.25" />
                            </div>
                        </template>
                    </Column>
                    <Column field="title" headless class="w-1/2 md:w-[30%]">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start gap-1 flex-grow overflow-hidden">
                                <span class="text-gray-950 text-sm w-full truncate">
                                    {{ slotProps.data?.title || '-' }}
                                </span>
                                <div class="flex flex-row overflow-hidden md:hidden">
                                    <span class="text-gray-500 text-xs w-full truncate">
                                        {{ 
                                            slotProps.data.start_date 
                                                ? dayjs(slotProps.data.start_date).format('YYYY/MM/DD') + ' - ' + 
                                                (slotProps.data.end_date ? dayjs(slotProps.data.end_date).format('YYYY/MM/DD') : 'N/A') 
                                                : (slotProps.data.end_date ? ' - ' + dayjs(slotProps.data.end_date).format('YYYY/MM/DD') : '-') 
                                            }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="status" headless class="w-1/4 md:w-[15%]">
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <div
                                    class="flex justify-center items-center gap-2 rounded-sm py-1 px-2"
                                    :class="formatColor(slotProps.data.status)"
                                >
                                    <div
                                        class="text-white text-xs text-center"
                                    >
                                        {{ $t(`public.${slotProps.data.status}`) }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="start_date" headless style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm truncate max-w-full">
                                {{ slotProps.data.start_date ? dayjs(slotProps.data.start_date).format('YYYY/MM/DD') : '-' }}
                            </div>
                        </template>
                    </Column>
                    <Column field="end_date" headless style="width: 20%" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm truncate max-w-full">
                                {{ slotProps.data.end_date ? dayjs(slotProps.data.end_date).format('YYYY/MM/DD') : '-' }}
                            </div>
                        </template>
                    </Column>
                    <Column field="action" headless class="w-[12.5%] md:w-[10%]">
                        <template #body="slotProps">
                            <Action 
                                :announcement="slotProps.data"
                            />
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>

    </div>
    
    <Dialog v-model:visible="visible" modal :header="$t('public.announcement')" class="dialog-xs md:dialog-md no-header-border" :dismissableMask="true">
        <div class="flex flex-col justify-center items-start gap-8 pb-6 self-stretch">
            <img v-if="data.thumbnail" :src="data.thumbnail" alt="announcement_image" class="w-full h-[144px] md:h-[310.5px]" />

            <span class="text-lg font-bold text-gray-950">{{ data.title }}</span>

            <!-- need to ask nic about this content if got html tag -->
            <span class="text-md font-regular text-gray-950 whitespace-pre-line" v-html="data.content"></span>
        </div>
        <div class="w-full flex justify-end items-center gap-4 pt-6 self-stretch">
            <DeleteAnnouncement 
                :announcement="data"
            />
            <Edit 
                :announcement="data"
            />
        </div>
    </Dialog>
</template>

<style scoped>
.no-column-headers ::v-deep thead {
  display: none;
}

.drop-zone {
  touch-action: auto;
  pointer-events: auto;
  min-height: 100px;
}
</style>
