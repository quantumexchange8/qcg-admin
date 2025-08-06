<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {ref, watchEffect, computed, onMounted, onUnmounted, h} from "vue";
import { useForm, usePage, router, Link, Head } from '@inertiajs/vue3';
import { IconPlus, IconChevronRight, IconPencilMinus, IconUserCancel, IconSquareRoundedMinus, IconMan } from "@tabler/icons-vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from 'primevue/inputtext';
import InputNumber from "primevue/inputnumber";
import InputError from "@/Components/InputError.vue";
import DatePicker from 'primevue/datepicker';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import { transactionFormat } from "@/Composables/index.js";
import {useLangObserver} from "@/Composables/localeObserver.js";
import Empty from "@/Components/Empty.vue";
import Loader from "@/Components/Loader.vue";
import { differenceInDays, differenceInHours, differenceInMinutes, differenceInSeconds } from 'date-fns';
import ToastList from "@/Components/ToastList.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";

const props = defineProps({
    competition: Object,
})

const { formatAmount, formatDate } = transactionFormat();
const {locale} = useLangObserver();

const loading = ref(false);
const participants = ref();
const getResults = async () => {
    loading.value = true;

    try {
        const response = await axios.get(`/competition/getParticipants?competition_id=${props.competition.competition_id}`);
        
        participants.value = response.data.participants;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

// Create reactive variables to store the countdown values
const days = ref(0);
const hours = ref(0);
const minutes = ref(0);
const seconds = ref(0);
let timer = null;

// Determine if the countdown should be to the start or end date
// We'll countdown to the start date if it's in the future, otherwise we countdown to the end date
const countdownTarget = computed(() => {
    const now = new Date();
    const startDate = new Date(props.competition.start_datetime);

    if (now < startDate) {
        return startDate; // Countdown to the start of the competition
    } else {
        return new Date(props.competition.end_datetime); // Countdown to the end
    }
});

const isCompetitionActive = computed(() => {
    const now = new Date();
    const startDate = new Date(props.competition.start_datetime);
    const endDate = new Date(props.competition.end_datetime);

    return now >= startDate && now < endDate;
});

// The core function to calculate and update the countdown
const updateCountdown = () => {
    const now = new Date();
    const target = countdownTarget.value;

    const totalSeconds = differenceInSeconds(target, now);

    if (totalSeconds <= 0) {
        // Stop the countdown if the target date has passed
        clearInterval(timer);
        days.value = 0;
        hours.value = 0;
        minutes.value = 0;
        seconds.value = 0;
        return;
    }

    days.value = differenceInDays(target, now);
    const remainingHours = differenceInHours(target, now) - (days.value * 24);
    const remainingMinutes = differenceInMinutes(target, now) - (days.value * 24 * 60) - (remainingHours * 60);
    const remainingSeconds = totalSeconds - (days.value * 24 * 60 * 60) - (remainingHours * 60 * 60) - (remainingMinutes * 60);

    hours.value = remainingHours;
    minutes.value = remainingMinutes;
    seconds.value = remainingSeconds;
};

// Start the timer when the component is mounted
onMounted(() => {
    updateCountdown(); // Initial call to prevent a one-second delay
    timer = setInterval(updateCountdown, 1000);
});

// Clear the timer when the component is unmounted to prevent memory leaks
onUnmounted(() => {
    clearInterval(timer);
});

const dynamicSuffix = computed(() => {
    if (props.competition.category === 'profit_rate') {
        return ' %';
    } else if (props.competition.category === 'trade_lot') {
        return ' Ł';
    }
    return '';
});

const visible = ref(false);
const dialogType = ref('add');

const form = useForm({
    participant_id : null,
    competition_id : props.competition.competition_id,
    virtual_name : '',
    amount : null,
})

const addDialog = () => {
    form.reset();
    dialogType.value = 'add';
    visible.value = true;
}

const closeDialog = () => {
    visible.value = false;
    form.reset();
}

const addVirtual = () => {
    form.post(route('competition.addVirtual'), {
        onSuccess: () => {
            closeDialog();
        },
    })
}

const editVirtual = () => {
    form.post(route('competition.editVirtual'), {
        onSuccess: () => {
            closeDialog();
        },
    })
}

const editDialog = (data) => {
    dialogType.value = 'edit';
    form.participant_id = data.id;
    form.virtual_name = data.name;
    form.amount = data.score;
    visible.value = true;
}

const confirm = useConfirm();

const requireConfirmation = (action_type, participant_id) => {
    const messages = {
        delete_virtual: {
            group: 'headless',
            color: 'error',
            icon: h(IconUserCancel),
            header: trans('public.delete_virtual'),
            message: trans('public.delete_virtual_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.delete(route('competition.deleteVirtual'), {
                    data: {
                        participant_id: participant_id,
                    },
                })
            }
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
        accept: action
    });
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});
</script>

<template>
    <Head :title="$t('public.view_ranking')"></Head>

    <div class="min-h-screen bg-gray-100 flex flex-col">
        <div class="flex flex-col flex-1">
            <nav
                aria-label="secondary"
                class="flex w-full h-16 sticky top-0 z-10 py-2 px-5 gap-3 justify-between items-center bg-white"
            >
                <div class="flex items-center gap-2">
                    <Link :href="route('competition')" as="button"> 
                        <Button
                            type="button"
                            variant="primary-text"
                            size="sm"
                            as="div" >
                            {{ $t('public.competition') }}
                        </Button>
                    </Link>
                    <IconChevronRight
                        :size="20"
                        stroke-width="1.25"
                        class="text-gray-400 grow-0 shrink-0"
                    />
                    <div class="flex justify-center items-center py-2 px-4 gap-2 rounded min-w-0">
                        <span class="txt-gray-700 text-center text-sm font-medium truncate">
                            {{ $t('public.view_ranking') }} - {{ props.competition.name[locale] }}
                        </span>
                    </div>
                </div>
            </nav>
            <div class="flex flex-1 justify-center items-start p-5 gap-5 md:px-5">
                <ConfirmationDialog />
                <ToastList />
                <div class="w-full max-w-[1440px] flex justify-center">
                    <div class="w-full max-w-[728px] flex flex-col items-center">
                        <!-- Competition Information -->
                        <div class="w-full flex flex-col justify-center items-center py-6 px-12 rounded-t-lg bg-primary-500 bg-blend-multiply bg-[url(/img/Competition/competition-bg.jpg)] bg-cover bg-center">
                            <div class="flex flex-col self-stretch gap-8 items-center py-10 px-8 bg-black">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-white text-xl font-bold">{{ props.competition.name[locale] }}</span>
                                    <div class="flex px-2 py-1 justify-center items-center bg-primary-600 rounded-[50px]">
                                        <span class="text-center text-white text-xs">{{ formatDate(props.competition.start_datetime) }} - {{ formatDate(props.competition.end_datetime) }}</span>
                                    </div>
                                </div>
                                <div v-if="isCompetitionActive" class="flex w-full">
                                    <div class="flex items-center self-stretch flex-1">
                                        <div class="flex flex-col items-center gap-1 flex-1">
                                            <span class="text-xxl font-medium text-white">{{ days }}</span>
                                            <span class="text-xs text-gray-500">{{ $t('public.day_s') }}</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1 flex-1">
                                            <span class="text-xxl font-medium text-white">{{ hours }}</span>
                                            <span class="text-xs text-gray-500">{{ $t('public.hour_s') }}</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1 flex-1">
                                            <span class="text-xxl font-medium text-white">{{ minutes }}</span>
                                            <span class="text-xs text-gray-500">{{ $t('public.minute_s') }}</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1 flex-1">
                                            <span class="text-xxl font-medium text-white">{{ seconds }}</span>
                                            <span class="text-xs text-gray-500">{{ $t('public.second_s') }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-else class="flex flex-col w-full gap-1">
                                    <p class="text-white text-center">
                                        <span v-if="countdownTarget > new Date()">Competition starts in...</span>
                                        <span v-else>Competition has ended.</span>
                                    </p>
                                    
                                    <div v-if="countdownTarget > new Date()" class="flex items-center self-stretch flex-1">
                                        <div class="flex flex-col items-center gap-1 flex-1">
                                            <span class="text-xxl font-medium text-white">{{ days }}</span>
                                            <span class="text-xs text-gray-500">{{ $t('public.day_s') }}</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1 flex-1">
                                            <span class="text-xxl font-medium text-white">{{ hours }}</span>
                                            <span class="text-xs text-gray-500">{{ $t('public.hour_s') }}</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1 flex-1">
                                            <span class="text-xxl font-medium text-white">{{ minutes }}</span>
                                            <span class="text-xs text-gray-500">{{ $t('public.minute_s') }}</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1 flex-1">
                                            <span class="text-xxl font-medium text-white">{{ seconds }}</span>
                                            <span class="text-xs text-gray-500">{{ $t('public.second_s') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Competition Rank List -->
                        <div class="w-full flex flex-col justify-center items-center py-5 px-6 gap-5 rounded-b-lg bg-white ">
                            <!-- Ranking Legend and Action -->
                            <div class="flex self-stretch justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <div class="flex w-4 h-4 bg-error-600 p-0.5 items-center justify-center rounded-[50px]">
                                        <IconMan :size="12" class="text-white"/>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $t('public.indicates_virtual_participant') }}</span>
                                </div>
                                <Button
                                    type="button"
                                    variant="primary-flat"
                                    size="sm"
                                    @click="addDialog()"
                                >
                                    <IconPlus size="20" stroke-width="1.25" />
                                    {{ $t('public.virtual_participant') }}
                                </Button>
                            </div>
                            <!-- Ranking List -->
                            <DataTable
                                :value="participants"
                                removableSort
                                :rows="100"
                                ref="dt"
                                :loading="loading"
                            >
                                <!-- <template #header>
                                </template> -->
                                <template #empty>
                                    <Empty 
                                        :title="$t('public.empty_participants_title')" 
                                        :message="$t('public.empty_participants_message')" 
                                    />
                                </template>
                                <template #loading>
                                    <div class="flex flex-col gap-2 items-center justify-center">
                                        <Loader />
                                        <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                                    </div>
                                </template>
                                <template v-if="participants?.length > 0">
                                    <Column field="rank" sortable :header="$t('public.rank')" class="">
                                        <template #body="slotProps">
                                            <div class="text-gray-950 text-sm">
                                                {{ slotProps.data.rank }}
                                            </div>
                                        </template>
                                    </Column>
                                    <Column field="name" :header="$t('public.participant')" class="">
                                        <template #body="slotProps">
                                            <div class="flex gap-2 items-center">
                                                <div v-if="slotProps.data.user_type === 'virtual'" class="flex w-4 h-4 bg-error-600 p-0.5 items-center justify-center rounded-[50px]">
                                                    <IconMan :size="12" class="text-white"/>
                                                </div>
                                                <span class="text-gray-950 text-sm">{{ slotProps.data.name }}</span>
                                            </div>
                                        </template>
                                    </Column>
                                    <Column field="score" sortable :header="$t(`public.${props.competition.category}`)" class="">
                                        <template #body="slotProps">
                                            <div class="text-gray-950 text-sm max-w-full">
                                                {{ slotProps.data.score }}
                                            </div>
                                        </template>
                                    </Column>
                                    <Column field="title" :header="$t('public.category')" class="">
                                        <template #body="slotProps">
                                            <div class="text-gray-950 text-sm max-w-full">
                                                {{ slotProps.data.title[locale] }}
                                            </div>
                                        </template>
                                    </Column>
                                    <Column field="points_rewarded" :header="$t('public.rewards') + ' (tp)'" class="">
                                        <template #body="slotProps">
                                            {{ slotProps.data.points_rewarded }}
                                        </template>
                                    </Column>
                                    <Column
                                        field="action"
                                        headless
                                        
                                    >
                                        <template #body="slotProps">
                                            <div v-if="slotProps.data.user_type === 'virtual'" class="flex gap-1">
                                                <Button
                                                    variant="gray-text"
                                                    size="sm"
                                                    type="button"
                                                    iconOnly
                                                    pill
                                                    @click="editDialog(slotProps.data)"
                                                >
                                                    <IconPencilMinus size="16" stroke-width="1.5" />
                                                </Button>
                                                <Button
                                                    variant="gray-text"
                                                    size="sm"
                                                    type="button"
                                                    iconOnly
                                                    pill
                                                    @click="requireConfirmation('delete_virtual', slotProps.data.id)"
                                                >
                                                    <IconSquareRoundedMinus size="16" stroke-width="1.5" />
                                                </Button>
                                            </div>
                                        </template>
                                    </Column>
                                </template>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="dialogType == 'add' ? $t('public.virtual_participant') : $t('public.edit_virtual_participant')"
        class="dialog-sm"
    >
        <form>
            <div class="flex flex-col gap-5 items-center self-stretch py-6">
                <div class="flex flex-col gap-2 self-stretch">
                    <InputLabel for="name" :value="$t('public.name')"/>
                    <InputText
                        id="virtual_name"
                        type="text"
                        class="block w-full"
                        v-model="form.virtual_name"
                        :placeholder="$t('public.virtual_participant_placeholder')"
                    />
                    <InputError :message="form.errors.virtual_name" />
                </div>
                <div class="flex flex-col gap-2 self-stretch">
                    <InputLabel for="category" :value="$t(`public.${props.competition.category}`)"/>
                    <InputNumber
                        v-model="form.amount"
                        inputId="amount"
                        :suffix="dynamicSuffix"
                        inputClass="py-3 px-4"
                        :placeholder="`0.00` + dynamicSuffix"
                        :min="0"
                        :minFractionDigits="2"
                        fluid
                    />
                    <InputError :message="form.errors.amount" />
                </div>
            </div>
            <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
                <Button
                    variant="gray-tonal"
                    class="w-full"
                    @click.prevent="closeDialog()"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    v-if="dialogType ==='add'"
                    type="button"
                    variant="primary-flat"
                    class="w-full"
                    @click.prevent="addVirtual()"
                >
                    {{ $t('public.add') }}
                </Button>
                <Button
                    v-else
                    type="button"
                    variant="primary-flat"
                    class="w-full"
                    @click.prevent="editVirtual()"
                >
                    {{ $t('public.edit') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
