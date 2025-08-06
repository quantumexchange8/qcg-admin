<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {ref, watchEffect, computed} from "vue";
import { useForm, usePage, router, Link, Head } from '@inertiajs/vue3';
import { IconPlus, IconChevronRight, IconClock, IconPlaylistAdd, IconWorld, IconSquareRoundedMinus, IconUpload } from "@tabler/icons-vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from 'primevue/inputtext';
import InputError from "@/Components/InputError.vue";
import InputNumber from "primevue/inputnumber";
import RadioButton from 'primevue/radiobutton';
import DatePicker from 'primevue/datepicker';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import cloneDeep from 'lodash/cloneDeep';

const languageLabels = {
  en: 'English',
  tw: '中文（繁體）',
  cn: '中文（简体）',
};

const titleLabels = {
  en: 'title_en',
  cn: 'title_cn',
  tw: 'title_tw',
};

const categories = ref([
    { name: 'profit_rate', key: 'profit' },
    { name: 'trade_lot', key: 'lot' },
    { name: 'trade_position', key: 'position' },
]);

const DEFAULT_UNRANKED_BADGE_PATH = '/img/Competition/pepe-2.svg';

let newRowIdCounter = -1; // Start from -1 or any negative number to avoid conflicting with actual database IDs

const RAW_STATIC_INITIAL_REWARDS = [
    { id: 1, min_rank: 1, max_rank: 1, title:{ en: '1st' }, points_rewarded: 500, rank_badge: "/img/Competition/profit-rate-1.svg", },
    { id: 2, min_rank: 2, max_rank: 2, title:{ en: '2nd' }, points_rewarded: 300, rank_badge: "/img/Competition/profit-rate-2.svg", },
    { id: 3, min_rank: 3, max_rank: 3, title:{ en: '3rd' }, points_rewarded: 100, rank_badge: "/img/Competition/profit-rate-3.svg", },
    { id: 4, min_rank: 4, max_rank: 10, title:{ en: 'Top 10' }, points_rewarded: 50, rank_badge: "/img/Competition/pepe-1.svg", },
    { id: 5, min_rank: 11, max_rank: 20, title:{ en: 'Top 20' }, points_rewarded: 30, rank_badge: "/img/Competition/pepe-1.svg", },
];

const processedInitialRewards = RAW_STATIC_INITIAL_REWARDS.map(reward => {
    const newTempId = newRowIdCounter--;
    return {
        ...reward,
        id: newTempId,
        __isNew: true,
    };
});

const form = useForm({
    category: 'profit_rate',
    name: {
        en: '',
        tw: '',
        cn: '',
    },
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: '',
    min_amount: null,
    unranked_badge: DEFAULT_UNRANKED_BADGE_PATH,
    rewards: processedInitialRewards,
});

const today = new Date();

const submitForm = () => {
    // if (form.expiry_date) {
    //     form.expiry_date = formatDate(form.expiry_date);
    // }
    
    form.post(route('competition.createCompetition'), {
        onSuccess: () => {
            visible.value = false;
            form.reset();
        },
    });
};

const dynamicSuffix = computed(() => {
    if (form.category === 'profit_rate') {
        return ' %';
    } else if (form.category === 'trade_lot') {
        return ' Ł';
    }
    return '';
});

const onCellEditComplete = (event) => {
    let { data, newValue, field } = event;

    if (['min_rank', 'max_rank', 'points_rewarded'].includes(field)) {
        const parsedValue = Number(newValue);

        if (Number.isInteger(parsedValue) && parsedValue >= 0) {
            data[field] = parsedValue;

            const index = form.rewards.findIndex(item => item.id === data.id);
            if (index !== -1) {
                form.rewards[index][field] = parsedValue;
                // console.log(`Updated ${field} for row ID ${data.id}: ${parsedValue}`);
            }
        } else {
            console.warn(`Validation failed for ${field}: ${newValue} is not a valid number.`);
            event.preventDefault(); // Revert cell display in DataTable

        }
    }
    // else if (field === 'en') { 
    //     if (newValue && typeof newValue === 'string' && newValue.trim().length > 0) {
    //         if (!data.title) {
    //             data.title = {};
    //         }
    //         data.title[field] = newValue;

    //         const index = rewards.value.findIndex(item => item.id === data.id);
    //         if (index !== -1) {
    //             if (!rewards.value[index].title) {
    //                 rewards.value[index].title = {};
    //             }
    //             rewards.value[index].title[field] = newValue;
    //             console.log(`Updated ${field} for row ID ${data.id}: ${newValue}`);
    //         }
    //     } else {
    //         console.warn(`Validation failed for ${field}: Title cannot be empty.`);
    //         event.preventDefault();
    //     }
    // }
};

const data = ref({});
const visible = ref(false);

const openTitleDialog = (rowData) => {
    visible.value = true;
    data.value = cloneDeep(rowData);
}

const closeTitleDialog = () => {
    visible.value = false;
    data.value = {};
}

const saveTitle = () => {
    const index = form.rewards.findIndex(item => item.id === data.value.id);

    if (index !== -1) {
        if (!form.rewards[index].title) {
            form.rewards[index].title = {};
        }
        form.rewards[index].title = data.value.title;
    }
    closeTitleDialog();
}

const addNewRow = () => {
    let newMinRank = 1;
    let newMaxRank = 1;

    if (form.rewards.length > 0) {
        // Find the highest max_rank among all existing rewards
        // We use reduce to iterate through the array and keep track of the maximum.
        // Math.max ensures we compare numbers correctly.
        // We use '|| 0' as a fallback in case 'max_rank' is undefined or null for some reason.
        const highestMaxRank = form.rewards.reduce((max, reward) => {
            return Math.max(max, reward.max_rank || 0);
        }, 0); // Start 'max' accumulator at 0

        newMinRank = highestMaxRank + 1;
        newMaxRank = highestMaxRank + 1;
    }

    const newReward = {
        id: newRowIdCounter--,
        min_rank: newMinRank, 
        max_rank: newMaxRank,
        title: { en: '' },
        points_rewarded: 0,
        created_at: null,
        updated_at: null,
        __isNew: true,
    };

    form.rewards.push(newReward);

    // Optional: If you want to immediately put the new row into edit mode,
    // you'd need to programmatically trigger PrimeVue's cell editing here.
    // This often involves storing the new row's data and the field to edit,
    // and then calling a DataTable method or managing active cell state.
    // For simplicity, we'll just add the row for now.
};

// --- Example delete function (for the delete button) ---
const deleteRow = (rowData) => {
    const index = form.rewards.findIndex(item => item.id === rowData.id);
    if (index !== -1) {
        // Option 1: Optimistic UI - remove immediately
        form.rewards.splice(index, 1);
        // Option 2: Confirm then delete from backend
        // if (confirm('Are you sure you want to delete this row?')) {
        //     axios.delete(`/api/rewards/${rowData.id}`)
        //         .then(() => {
        //             rewards.value.splice(index, 1);
        //             console.log('Row deleted successfully!');
        //         })
        //         .catch(error => console.error('Error deleting row:', error));
        // }
    }
};

const selectedUnrankedBadge = ref(DEFAULT_UNRANKED_BADGE_PATH);
const handleUploadUnranked = (event) => {
    const unrankedInput = event.target;
    const file = unrankedInput.files[0];

    if (file) {
        // Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedUnrankedBadge.value = reader.result;
        };
        reader.readAsDataURL(file);
        form.unranked_badge = event.target.files[0];
    } else {
        selectedUnrankedBadge.value = DEFAULT_UNRANKED_BADGE_PATH;
        form.unranked_badge = DEFAULT_UNRANKED_BADGE_PATH;
    }
};

const handleUploadRanked = (event, rowData) => {
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = () => {
            rowData.rank_badge = reader.result; // update preview
        };
        reader.readAsDataURL(file);

        rowData._uploadedBadgeFile = file; // store file separately for backend
    }
};
</script>

<template>
    <Head :title="$t('public.new_competition')"></Head>

    <div class="min-h-screen bg-gray-100 flex flex-col">
        <div class="flex flex-col flex-1">
            <nav
                aria-label="secondary"
                class="flex w-full h-16 sticky top-0 z-10 py-2 px-5 gap-3 justify-center items-center bg-white"
            >
                <div class="w-full flex items-center gap-2">
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
                            {{ $t('public.new_competition') }}
                        </span>
                    </div>
                </div>
                <Button
                    variant="primary-flat"
                    size="sm"
                    :disabled="form.processing"
                    @click="submitForm"
                >
                    {{ $t('public.create') }}
                </Button>
            </nav>
            <div class="flex flex-1 justify-center items-start p-5 gap-5 md:px-5">
                <!-- <ConfirmationDialog /> -->
                <div class="w-full max-w-[1440px] flex justify-center">
                    <div class="w-full max-w-[728px] flex flex-col items-center gap-5">
                        <!-- Competition Information -->
                        <div class="w-full flex flex-col justify-center items-center p-6 gap-5 rounded-lg bg-white shadow-card">
                            <span class="w-full text-gray-950 font-bold">{{ $t('public.competition_information') }}</span>
                            <div class="w-full grid grid-cols-2 gap-5">
                                <div class="flex flex-col gap-2 self-stretch text-sm col-span-2">
                                    <InputLabel for="category" :value="$t('public.category')"/>
                                    <div class="flex items-center self-stretch gap-8">
                                        <div v-for="category in categories" :key="category.key" class="flex items-center gap-3">
                                            <RadioButton
                                                v-model="form.category"
                                                :inputId="category.key"
                                                :value="category.name"
                                                class="w-5 h-5"
                                            />
                                            <label :for="category.name">{{ $t(`public.${category.name}`) }}</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 self-stretch text-sm col-span-2">
                                    <InputLabel for="category" :value="$t('public.competition_name')"/>
                                    <div class="w-full flex flex-col items-center gap-3">
                                        <div
                                            v-for="(label, key) in languageLabels"
                                            :key="key"
                                            class="w-full flex flex-row gap-3"
                                        >
                                            <div class="w-[120px] h-11 flex flex-shrink-0 items-start py-3 px-4 gap-3 rounded border border-gray-300 bg-white">
                                                <span class="w-full text-gray-950 text-sm whitespace-nowrap">{{ label }}</span>
                                            </div>
                                            <div class="w-full flex flex-col">
                                                <InputText
                                                    :id="'name_' + key"
                                                    type="text"
                                                    class="block w-full"
                                                    v-model="form.name[key]"
                                                    :placeholder="$t('public.name_' + key + '_placeholder')"
                                                    :invalid="!!form.errors['name.' + key]"
                                                />
                                                <InputError :message="form.errors['name.' + key]" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 self-stretch text-sm">
                                    <InputLabel for="start_date" :value="$t('public.start_date')"/>
                                    <div class="flex items-center self-stretch gap-2">
                                        <DatePicker
                                            v-model="form.start_date"
                                            :minDate="today"
                                            selectionMode="single"
                                            dateFormat="yy/mm/dd"
                                            showIcon
                                            iconDisplay="input"
                                            :placeholder="$t('public.select_date')"
                                            class="w-full font-normal"
                                        />
                                        <DatePicker class="w-full font-normal" id="start_time" placeholder="00:00" v-model="form.start_time" timeOnly fluid iconDisplay="input" showIcon>
                                            <template #inputicon="slotProps">
                                                <span class="cursor-pointer" @click="slotProps.clickCallback">
                                                    <IconClock stroke-width="1.25" class="w-5 h-5 text-gray-500" />
                                                </span>
                                            </template>
                                        </DatePicker>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 self-stretch text-sm">
                                    <InputLabel for="end_date" :value="$t('public.end_date')"/>
                                    <div class="flex items-center self-stretch gap-2">
                                        <DatePicker
                                            v-model="form.end_date"
                                            :minDate="today"
                                            selectionMode="single"
                                            dateFormat="yy/mm/dd"
                                            showIcon
                                            iconDisplay="input"
                                            :placeholder="$t('public.select_date')"
                                            class="w-full font-normal"
                                        />
                                        <DatePicker class="w-full font-normal" id="end_time" placeholder="00:00" v-model="form.end_time" timeOnly fluid iconDisplay="input" showIcon>
                                            <template #inputicon="slotProps">
                                                <span class="cursor-pointer" @click="slotProps.clickCallback">
                                                    <IconClock stroke-width="1.25" class="w-5 h-5 text-gray-500" />
                                                </span>
                                            </template>
                                        </DatePicker>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Minimum Qualification -->
                        <div class="w-full flex flex-col justify-center items-center p-6 gap-5 rounded-lg bg-white shadow-card">
                            <span class="w-full text-gray-950 font-bold">{{ $t('public.minimum_qualification') }}</span>
                            <div class="flex flex-col gap-2 self-stretch">
                                <span class="text-sm text-gray-700">{{ $t('public.min_amount') }}</span>
                                <InputNumber
                                    v-model="form.min_amount"
                                    inputId="minAmount"
                                    :suffix="dynamicSuffix"
                                    inputClass="py-3 px-4"
                                    :placeholder="`0.00` + dynamicSuffix"
                                    :min="0"
                                    :minFractionDigits="2"
                                    fluid
                                />
                                <span class="text-xs text-gray-500">{{ $t('public.minimum_qualification_desc') }}</span>
                                <InputError :message="form.errors.min_amount" />
                            </div>
                        </div>

                        <!-- Ranking & Rewards -->
                        <div class="w-full flex flex-col justify-center items-center p-6 gap-5 rounded-lg bg-white shadow-card">
                            <div class="flex justify-between self-stretch items-center">
                                <span class="text-gray-950 font-bold">{{ $t('public.ranking_n_rewards') }}</span>
                                <Button
                                    type="button"
                                    variant="primary-text"
                                    size="sm"
                                    @click="addNewRow()"
                                >
                                    <IconPlaylistAdd stroke-width="1.25" class="w-4 h-4" />
                                    {{ $t('public.add_another') }}
                                </Button>
                            </div>
                            <DataTable
                                :value="form.rewards"
                                tableStyle="md:min-width: 50rem"
                                dataKey="id"
                                editMode="cell" 
                               @cell-edit-complete="onCellEditComplete"
                            >
                                <template>
                                    <Column
                                        field="min_rank"
                                        :header="$t('public.from')"
                                        style="width:15%;"
                                    >
                                        <template #body="slotProps">
                                            {{ slotProps.data.min_rank }}
                                        </template>
                                        <template #editor="{ data, field }">
                                            <div class="w-full flex flex-col">
                                                <InputNumber
                                                    v-model="data[field]"
                                                    :min="0"
                                                    class="block w-full" 
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                    <Column
                                        field="max_rank"
                                        :header="$t('public.to')"
                                        style="width:15%;"
                                    >
                                        <template #body="slotProps">
                                            {{ slotProps.data.max_rank }}
                                        </template>
                                        <template #editor="{ data, field }">
                                            <div class="w-full flex flex-col">
                                                <InputNumber
                                                    v-model="data[field]"
                                                    :min="0"
                                                    class="block w-full" 
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                    <Column
                                        field="en"
                                        :header="$t('public.title_en')"
                                        style="width:18%;"
                                    >
                                        <template #body="slotProps">
                                            {{ slotProps.data.title['en'] }}
                                        </template>
                                        <!-- <template #editor="{ data, field }">
                                            <div class="w-full flex flex-col">
                                                <InputText
                                                    v-model="data.title[field]"
                                                    type="text"
                                                    class="block w-full" 
                                                />
                                            </div>
                                        </template> -->
                                    </Column>
                                    <Column
                                        field="points_rewarded"
                                        :header="$t('public.rewards') + ' (tp)'"
                                        style="width:18%;"
                                    >
                                        <template #body="slotProps">
                                            {{ slotProps.data.points_rewarded }}
                                        </template>
                                        <template #editor="{ data, field }">
                                            <div class="w-full flex flex-col">
                                                <InputNumber
                                                    v-model="data[field]"
                                                    :min="0"
                                                    class="block w-full" 
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                    <Column
                                        field="badge"
                                        :header="$t('public.badge')"
                                        style="width:18%;"
                                    >
                                        <template #body="slotProps">
                                            <input
                                                :ref="`rankedInput_${slotProps.index}`"
                                                :id="`ranked_badge_${slotProps.index}`"
                                                type="file"
                                                class="hidden"
                                                accept="image/*"
                                                @change="(e) => handleUploadRanked(e, slotProps.data)"
                                            />
                                            <div v-if="slotProps.data.rank_badge" class="relative group w-[60px] h-[60px]">
                                                <div
                                                    class="absolute inset-0 bg-gray-300 opacity-0 group-hover:opacity-60 transition-opacity rounded pointer-events-none"
                                                ></div>
                                                <div
                                                    class="w-full h-full bg-cover bg-center bg-no-repeat rounded flex items-center justify-center hover:cursor-pointer"
                                                    :style="{ backgroundImage: `url('${slotProps.data.rank_badge}')` }"
                                                    @click="$refs[`rankedInput_${slotProps.index}`].click()"
                                                >
                                                    <IconUpload class="w-4 h-4 text-white opacity-0 group-hover:opacity-100 z-10" />
                                                </div>
                                            </div>
                                            <Button
                                                v-else
                                                type="button"
                                                variant="primary-flat"
                                                size="sm"
                                                @click="$refs[`rankedInput_${slotProps.index}`].click()"
                                            >
                                                {{ $t('public.choose') }}
                                            </Button>
                                        </template>
                                    </Column>
                                    <Column
                                        field="action"
                                        headless
                                        style="width:16%;"
                                    >
                                        <template #body="slotProps">
                                            <Button
                                                variant="gray-text"
                                                size="sm"
                                                type="button"
                                                iconOnly
                                                pill
                                                @click="openTitleDialog(slotProps.data)"
                                            >
                                                <IconWorld size="16" stroke-width="1.5" />
                                            </Button>
                                            <Button
                                                variant="gray-text"
                                                size="sm"
                                                type="button"
                                                iconOnly
                                                pill
                                                @click="deleteRow(slotProps.data)"
                                            >
                                                <IconSquareRoundedMinus size="16" stroke-width="1.5" />
                                            </Button>
                                        </template>
                                    </Column>
                                </template>
                            </DataTable>
                        </div>

                        <!-- Default Badge Icon -->
                        <div class="w-full flex flex-col justify-center items-center p-6 gap-5 rounded-lg bg-white shadow-card">
                            <div class="flex flex-col gap-1 self-stretch items-center">
                                <span class="w-full text-gray-950 font-bold">{{ $t('public.default_badge_icon') }}</span>
                                <span class="w-full text-xs text-gray-500">{{ $t('public.default_badge_desc') }}</span>
                            </div>
                            <div class="flex items-end gap-5 self-stretch">
                                <div class="w-40 h-40">
                                    <img :src="selectedUnrankedBadge" alt="unranked_badge" class="w-40 h-40" />
                                </div>
                                <input
                                    ref="unrankedInput"
                                    id="unranked_badge"
                                    type="file"
                                    class="hidden"
                                    accept="image/*"
                                    @change="handleUploadUnranked"
                                />
                                <Button
                                    type="button"
                                    variant="primary-flat"
                                    size="sm"
                                    @click="$refs.unrankedInput.click()"
                                >
                                    {{ $t('public.upload') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.set_multiple_languages')"
        class="dialog-sm"
    >
        <form>
            <div class="flex flex-col gap-6 items-center self-stretch py-4 md:py-8">
                <div class="flex flex-col gap-5 self-stretch items-center">
                    <div
                        v-for="(label, key) in titleLabels"
                        :key="key" 
                        class="flex flex-col gap-2 self-stretch items-start"
                    >
                        <InputLabel :for="label" :value="$t(`public.${label}`)"/>
                        <InputText
                            :id="label"
                            type="text"
                            class="block w-full"
                            v-model="data.title[key]"
                        />
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
                <Button
                    variant="gray-tonal"
                    class="w-full"
                    @click.prevent="closeTitleDialog()"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    type="button"
                    variant="primary-flat"
                    class="w-full"
                    @click.prevent="saveTitle()"
                >
                    {{ $t('public.save_changes') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
