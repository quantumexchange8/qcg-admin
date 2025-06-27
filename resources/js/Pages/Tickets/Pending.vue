<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePage, useForm } from "@inertiajs/vue3";
import { IconCircleXFilled, IconSearch, IconDownload, IconFilterOff, IconCopy } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted, onUnmounted } from "vue";
import Loader from "@/Components/Loader.vue";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Column from "primevue/column";
import Button from '@/Components/Button.vue';
import Select from "primevue/select";
import { FilterMatchMode } from '@primevue/core/api';
import Empty from "@/Components/Empty.vue";
import { transactionFormat, generalFormat } from "@/Composables/index.js";
import dayjs from "dayjs";
import { trans, wTrans } from "laravel-vue-i18n";
import StatusBadge from '@/Components/StatusBadge.vue';
import {useLangObserver} from "@/Composables/localeObserver.js";
import Checkbox from 'primevue/checkbox';
import TextArea from "primevue/textarea";
import TicketReplies from "@/Pages/Tickets/Partials/TicketReplies.vue";
import { useConfirm } from "primevue/useconfirm";

const {locale} = useLangObserver();

const { formatAmount } = transactionFormat();
const { formatRgbaColor } = generalFormat();

const props = defineProps({
    categories: Object,
});

const visible = ref(false);
const loading = ref(false);
const dt = ref(null);
const tickets = ref();
// const selectedTeams = ref([]);
// const teams = ref(props.teams);
const categories = ref(props.categories);
const selectedCategory = ref(null);
const filteredValue = ref();

// Define the status options
const statusOption = [
    { name: wTrans('public.new'), value: 'new' },
    { name: wTrans('public.in_progress'), value: 'in_progress' },
];

const selectedStatus = ref(null);

const getResults = async (status = null, category_id = null) => {
    loading.value = true;

    try {
        const params = new URLSearchParams();
        // Create the base URL with the type parameter directly in the URL
        if (status) {
            params.append('status', status);
        }

        if (category_id) {
            params.append('category_id', category_id);
        }

        const response = await axios.get('/tickets/getPendingTickets', { params });

        tickets.value = response.data.tickets;
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

// Watchers for selectedMonths and selectedTeams
watch([selectedStatus, selectedCategory], ([newStatus, newCategory]) => {
    getResults(newStatus, newCategory);
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    category: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const clearFilter = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        email: { value: null, matchMode: FilterMatchMode.CONTAINS },
        subject: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };

    selectedStatus.value = null;
    selectedCategory.value = null;
    filteredValue.value = null; 
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const handleFilter = (e) => {
    filteredValue.value = e.filteredValue;
};

const pageLinkSize = ref(window.innerWidth < 768 ? 3 : 5)

const updatePageLinkSize = () => {
  pageLinkSize.value = window.innerWidth < 768 ? 3 : 5
}

onMounted(() => {
  window.addEventListener('resize', updatePageLinkSize)
})

onUnmounted(() => {
  window.removeEventListener('resize', updatePageLinkSize)
})

const form = useForm({
    ticket_id: '',
    message: '',
})

const submitForm = () => {
    // console.log(form)
    form.post(route('tickets.sendReply'), {
        onSuccess: () => {
            // closeDialog();
            // form.reset();
            form.message = '';
        },
    });
};

// dialog
const data = ref({});
const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;

    form.ticket_id = data.value.ticket_id;
};

const visiblePhoto = ref(false);
const selectedAttachment = ref(null);
const openPhotoDialog = (attachment) => {
    visiblePhoto.value = true;
    selectedAttachment.value = attachment;
}

const confirm = useConfirm();

// Function to trigger the custom confirmation dialog
const confirmResolve = (action_type) => {
    const messages = {
        mark_resolved: {
            group: 'message_only',
            color: 'primary',
            message: trans('public.mark_resolved_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                // router.post(route('reward.updateRewardStatus'), {
                //     reward_id: props.reward.reward_id,
                // })

            }
        }
    };

    const { group, color, message, cancelButton, acceptButton, action } = messages[action_type];

    // Show the custom confirmation dialog
    confirm.require({
        group,
        color,
        message,
        cancelButton,
        acceptButton,
        accept: action
    });
};
</script>

<template>
    <AuthenticatedLayout :title="`${$t('public.tickets')}&nbsp;-&nbsp;${$t('public.pending')}`">
        <div class="flex flex-col justify-center items-center px-3 py-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="flex flex-col pb-3 gap-3 items-center self-stretch md:flex-row md:gap-0 md:justify-between md:pb-0">
                <span class="text-gray-950 font-semibold self-stretch md:self-auto">{{ $t('public.pending_tickets') }}</span>
            </div>

            <DataTable
                v-model:filters="filters"
                :value="tickets"
                :paginator="tickets?.length > 0 && filteredValue?.length > 0"
                removableSort
                :rows="100"
                :pageLinkSize="pageLinkSize"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :globalFilterFields="['subject', 'name', 'email']"
                ref="dt"
                :loading="loading"
                selectionMode="single"
                @filter="handleFilter"
                @row-click="(event) => openDialog(event.data)"
            >
                <template #header>
                    <div class="flex flex-col justify-between items-center pb-5 gap-3 self-stretch md:flex-row md:pb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 items-center gap-3 self-stretch md:gap-2">
                            <Select
                                v-model="selectedStatus"
                                :options="statusOption"
                                optionLabel="name"
                                optionValue="value"
                                :placeholder="$t('public.filter_by_status')"
                                class="w-full md:max-w-60 font-normal"
                                scroll-height="236px"
                            />
                            <Select
                                v-model="selectedCategory"
                                :options="categories"
                                :optionLabel="(option) => option.category[locale]"
                                optionValue="category_id"
                                :placeholder="$t('public.filter_by_category')"
                                class="w-full md:max-w-60 font-normal"
                                scroll-height="236px"
                            />
                            <div class="relative block col-span-1">
                                <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                                    <IconSearch size="20" stroke-width="1.25" />
                                </div>
                                <InputText v-model="filters['global'].value" :placeholder="$t('public.keyword_search')" size="search" class="font-normal w-full" />
                                <div
                                    v-if="filters['global'].value !== null"
                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                    @click="clearFilterGlobal"
                                >
                                    <IconCircleXFilled size="16" />
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:flex md:flex-row gap-3 md:gap-2 w-full md:w-auto shrink-0">
                            <!-- <Button variant="primary-outlined" class="col-span-1 md:w-auto">
                                <IconDownload size="20" stroke-width="1.25" />
                                {{ $t('public.export') }}
                            </Button> -->
                            <Button
                                type="button"
                                variant="error-outlined"
                                size="base"
                                class='col-span-1 md:w-auto'
                                @click="clearFilter"
                            >
                                <IconFilterOff size="20" stroke-width="1.25" />
                                {{ $t('public.clear') }}
                            </Button>
                        </div>
                    </div>
                </template>
                <template #empty>
                    <Empty 
                        :title="$t('public.empty_pending_tickets_title')" 
                        :message="$t('public.empty_pending_tickets_message')" 
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                    </div>
                </template>
                <template v-if="tickets?.length > 0 && filteredValue?.length > 0">
                    <Column field="status" :header="$t('public.status')" class="hidden md:table-cell w-[15%]">
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <StatusBadge :variant="slotProps.data.status" :value="$t('public.' + slotProps.data.status)" />
                            </div>
                        </template>
                    </Column>
                    <Column field="subject" :header="$t('public.subject')" class="w-1/3 md:w-[15%] px-3 ">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data?.subject || '-' }}
                            </div>
                        </template>
                    </Column>
                    <Column field="name" sortable :header="$t('public.by')" class="w-2/3 md:w-[20%] max-w-0 px-3">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start max-w-full">
                                <div class="flex max-w-full gap-2 items-center">
                                    <div class="font-semibold truncate max-w-full">
                                        {{ slotProps.data.name }}
                                    </div>
                                    <!-- <div
                                        v-if="slotProps.data.team_id"
                                        class="flex justify-center items-center gap-2 rounded-sm py-1 px-2 md:hidden"
                                        :style="{ backgroundColor: formatRgbaColor(slotProps.data.team_color, 1) }"
                                    >
                                        <div
                                            class="text-white text-xs text-center"
                                        >
                                            {{ slotProps.data.team_name }}
                                        </div>
                                    </div> -->
                                </div>
                                <div class="text-gray-500 text-xs truncate max-w-full hidden md:flex">
                                    {{ slotProps.data.email }}
                                </div>
                                <!-- <div class="text-gray-500 text-xs truncate max-w-full md:hidden flex">
                                    {{ dayjs(slotProps.data.approved_at ?? slotProps.data.approved_at).format('YYYY/MM/DD HH:mm:ss') }}
                                </div> -->
                            </div>
                        </template>
                    </Column>
                    <Column field="created_at" :header="$t('public.submitted')" sortable class="hidden md:table-cell w-full md:w-[20%] max-w-0">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm truncate max-w-full">
                                {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD HH:mm') }}
                            </div>
                        </template>
                    </Column>
                    <Column field="category" :header="$t('public.category')" class="w-1/3 md:w-[15%] px-3 ">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ slotProps.data?.category[locale] }}
                            </div>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>

    <Dialog v-model:visible="visible" modal :header="$t('public.ticket')" class="dialog-xs md:dialog-lg">
        <div class="flex flex-col justify-center items-center gap-5 self-stretch pt-4 md:pt-6">
            <div class="flex flex-col items-center gap-2 self-stretch">
                <div class="flex flex-row justify-between items-center self-stretch">
                    <span class="text-xs font-semibold text-gray-950">{{ data.name }}</span>
                    <span class="text-xs text-gray-500">{{ dayjs(data.created_at).format('YYYY/MM/DD HH:mm') }}</span>
                </div>
                <div class="flex flex-col p-2 justify-center items-center gap-2 self-stretch rounded bg-primary-100">
                    <span class="text-sm font-semibold text-gray-950 self-stretch">{{ data.subject }}</span>
                    <span class="text-sm text-gray-950 self-stretch">{{ data.description }}</span>
                    <div v-if="data.ticket_attachments" class="grid grid-cols-2 md:grid-cols-3 gap-2 self-stretch">
                        <div v-for="file in data.ticket_attachments" :key="file.id" @click="openPhotoDialog(file)" 
                            class="flex items-center gap-3 w-full p-2 bg-white rounded border border-gray-200 cursor-pointer hover:bg-gray-100"
                        >
                            <img :src="file.original_url" :alt="file.file_name" class="w-16 h-12 rounded" />
                            <span class="text-sm text-gray-700 truncate">{{ file.file_name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <TicketReplies 
                :ticket_id="data.ticket_id"
            />
        </div>

        <template #footer>
            <div class="flex flex-col gap-2 md:gap-4 justify-center w-full">

                <TextArea
                    id="message"
                    type="text"
                    class="w-full h-24"
                    v-model="form.message"
                    :placeholder="$t('public.message_placeholder')"
                    rows="5"
                    cols="30"
                />
                <div class="flex flex-row justify-between items-center w-full">
                    <label class="flex items-center gap-2">
                        <Checkbox binary class="w-5 h-5" @click="confirmResolve('mark_resolved')"/>
                        <span class="text-sm text-gray-700 font-medium">{{ $t('public.mark_as_resolved') }}</span>
                    </label>
                    <Button
                        type="button"
                        variant="primary-flat"
                        @click="submitForm"
                    >
                        {{ $t('public.reply') }}
                    </Button>
                </div>
            </div>
        </template>
    </Dialog>

    <Dialog v-model:visible="visiblePhoto" modal headless class="dialog-xs md:dialog-md" :dismissableMask="true">
        <img
            :src="selectedAttachment?.original_url"
            class="w-full"
            alt="document"
        />
    </Dialog>
</template>