<script setup>
import {
    IconDotsVertical,
    IconHistory,
    IconPencilMinus,
    IconClipboardCheck,
    IconTrashX,
    IconCircleCheckFilled,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import { computed, h, ref, watch } from "vue";
import TieredMenu from "primevue/tieredmenu";
import { router, useForm } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans } from "laravel-vue-i18n";
import Dialog from "primevue/dialog";
import ViewTeamTransactions from "@/Pages/Team/Partials/ViewTeamTransactions.vue";
import EditTeam from "@/Pages/Team/Partials/EditTeam.vue";
import ConfirmDialog from "primevue/confirmdialog";
import Select from "primevue/select";
import dayjs from "dayjs";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    team: Object,
    isLoading: Boolean,
});

const menu = ref();
const visible = ref(false);
const dialogType = ref("");
const title = ref("");
const confirmVisible = ref(false); // Manage new item confirmation dialog
const months = ref([]);
const selectedMonth = ref([]);
const selectionLoading = ref(false);

const items = ref([
    {
        label: "transactions",
        icon: h(IconHistory),
        command: () => {
            visible.value = true;
            dialogType.value = "report";
            title.value = "view_team_transactions";
        },
    },
    {
        label: "edit",
        icon: h(IconPencilMinus),
        command: () => {
            visible.value = true;
            dialogType.value = "edit";
            title.value = "edit_sales_team";
        },
    },
    {
        label: "settlement",
        icon: h(IconClipboardCheck),
        command: () => {
            openConfirmDialog();
        },
    },
    {
        label: "remove_sales_team",
        icon: h(IconTrashX),
        command: () => {
            requireConfirmation("remove");
        },
    },
]);

const toggle = (event) => {
    menu.value.toggle(event);
};

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        remove: {
            group: "headless",
            color: "error",
            icon: h(IconTrashX),
            header: trans("public.remove_sales_team_header"),
            message: trans("public.remove_sales_team_message"),
            cancelButton: trans("public.cancel"),
            acceptButton: trans("public.sales_team_remove"),
            action: () => {
                router.delete(route("team.deleteTeam"), {
                    data: {
                        id: props.team.id,
                    },
                });
            },
        },
    };

    const { group, color, icon, header, message, cancelButton, acceptButton, action } = messages[action_type];

    confirm.require({
        group,
        color,
        icon,
        header,
        message,
        cancelButton,
        acceptButton,
        accept: action,
    });
};

const openConfirmDialog = () => {
    confirmVisible.value = true;
    getTeamSettlementMonths();  
};

const closeConfirmDialog = () => {
    confirmVisible.value = false;
};

const form = useForm({
    id: '',
    month: '',
});

const getTeamSettlementMonths = async () => {
    selectionLoading.value = true

    try {
        const response = await axios.get(`/team/getTeamSettlementMonth?id=${props.team.id}`);
        months.value = response.data.months;

    } catch (error) {
        console.error('Error transaction months:', error);
    } finally {
        selectionLoading.value = false;
    }
};

const submitForm = () => {
    form.id = props.team.id;
    form.month = selectedMonth.value?.month; // Extract only the `month` property
    form.post(route('team.markSettlementReport'), {
        preserveScroll: true,
        onSuccess: () => {
            closeConfirmDialog();
        },
    });
};

const filteredItems = ref(items.value); // Initialize with all items

// Watch for changes to props.team
watch(() => props.team, (newTeam) => {
    if (newTeam) {
        // Only filter if team is available
        filteredItems.value = newTeam.id !== 1 
            ? items.value 
            : items.value.filter(item => item.label !== 'settlement');
    }
}, { immediate: true }); // Run the watcher immediately to handle the initial state

</script>
<template>
    <Button
        variant="gray-text"
        size="sm"
        type="button"
        iconOnly
        pill
        @click="toggle"
        aria-haspopup="true"
        aria-controls="overlay_tmenu"
        :disabled="props.isLoading"
    >
        <IconDotsVertical size="16" stroke-width="1.25" color="#667085" />
    </Button>
    <TieredMenu ref="menu" id="overlay_tmenu" :model="filteredItems" popup>
        <template #item="{ item, props, hasSubmenu }">
            <div
                class="flex items-center gap-3 self-stretch"
                v-bind="props.action"
            >
                <component :is="item.icon" size="20" stroke-width="1.25" class="grow-0 shrink-0" />
                <span class="text-gray-700 text-sm">{{ $t(`public.${item.label}`) }}</span>
            </div>
        </template>
    </TieredMenu>

    <!-- Dialogs for Reports and Edit -->
    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.' + title)"
        class="dialog-xs"
        :class="{
            'md:dialog-lg': dialogType === 'report',
            'md:dialog-sm': dialogType === 'edit',
        }"
    >
        <template v-if="dialogType === 'report'">
            <ViewTeamTransactions
                :team="team"
                @update:visible="visible = false"
            />
        </template>
        <template v-if="dialogType === 'edit'">
            <EditTeam
                :team="team"
                @update:visible="visible = false"
            />
        </template>
    </Dialog>

    <!-- New Confirmation Dialog -->
    <ConfirmDialog
        v-model:visible="confirmVisible"
        group="headless-primary"
    >
        <template #container>
            <div class="flex flex-col justify-center items-center px-4 pt-[60px] pb-6 gap-8 bg-white rounded shadow-dialog w-[90vw] md:w-[500px] md:px-6">
                <div class="flex flex-col items-center gap-2 self-stretch">
                    <span class="self-stretch text-gray-950 text-center font-bold md:text-lg">{{ $t('public.mark_settlement_header') }}</span>
                    <span class="self-stretch text-gray-700 text-center text-sm">{{ $t('public.mark_settlement_message') }}</span>
                </div>
                <div class="w-full flex flex-col justify-center gap-2">
                    <InputLabel for="month" :value="$t('public.month')" :invalid="!!form.errors.month" />
                    <Select
                        v-model="selectedMonth"
                        :options="months"
                        :placeholder="$t('public.month_placeholder')"
                        class="w-full h-full font-normal"
                        scroll-height="236px"
                        :loading="selectionLoading"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value.month" class="flex items-center gap-3">
                                <div>{{ slotProps.value.month }}</div>
                            </div>
                            <div v-else class="text-gray-400">
                                {{ slotProps.placeholder }}
                            </div>
                        </template>
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <div>{{ slotProps.option.month }}</div>
                                <IconCircleCheckFilled v-if="slotProps.option.marked" size="20" stroke-width="1.25" class="text-success-700" />
                            </div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.month" />
                </div>
                <div class="flex gap-4 w-full">
                    <Button
                        type="button"
                        variant="gray-outlined"
                        @click="confirmVisible = false"
                        class="w-full"
                        size="base"
                    >
                        {{ $t('public.cancel') }}
                    </Button>
                    <Button
                        type="button"
                        variant="primary-flat"
                        @click="submitForm"
                        class="w-full"
                        size="base"
                    >
                        {{ $t('public.confirm') }}
                    </Button>
                </div>
                <div class="flex w-[84px] h-[84px] p-6 justify-center items-center absolute -top-[42px] rounded-full grow-0 shrink-0 bg-primary-600">
                    <IconClipboardCheck size="36" stroke-width="1.25" color="#FFFFFF" />
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>
