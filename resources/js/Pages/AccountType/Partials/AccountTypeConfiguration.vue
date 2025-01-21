<script setup>
import { Head } from "@inertiajs/vue3";
import { h, ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { useForm } from "@inertiajs/vue3";
import {
    IconChevronRight,
    IconExclamationMark,
    IconCircleXFilled,
    IconSearch,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from 'primevue/inputtext';
import InputNumber from "primevue/inputnumber";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";
import DatePicker from 'primevue/datepicker';
import ColorPicker from 'primevue/colorpicker';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';
import { trans, wTrans } from "laravel-vue-i18n";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";

const props = defineProps({
    accountType: Object,
    leverages: Array,
    visibleToOptions: Array,
})

const confirm = useConfirm();

// Function to trigger the custom confirmation dialog
const requireConfirmation = (action_type, callback) => {
    const messages = {
        unsaved_changes: {
            group: 'headless',
            color: 'primary',
            icon: h(IconExclamationMark),
            header: trans('public.unsaved_changes'),
            message: trans('public.unsaved_changes_message'),
            cancelButton: trans('public.leave_page'),
            acceptButton: trans('public.stay_on_page'),
        }
    };

    const { group, color, icon, header, message, cancelButton, acceptButton } = messages[action_type];

    // Show the custom confirmation dialog
    confirm.require({
        group,
        color,
        icon,
        header,
        message,
        cancelButton,
        acceptButton,
        accept: () => {
            // Accepting will prevent navigation or action
        },
        reject: () => {
            // When rejected, allow proceeding with the original action
            callback();
        }
    });
};

// Check if form is dirty (including nested objects)
const checkIfDirty = (form, initialState) => {
    return !Object.keys(form).every(key => {
        // If the key is a nested object, we need to compare its properties deeply
        if (typeof form[key] === 'object' && form[key] !== null) {
            return JSON.stringify(form[key]) === JSON.stringify(initialState[key]);
        }
        return form[key] === initialState[key];
    });
};

// Handle Cancel button click
const handleCancel = (event) => {
    // Prevent default action if the form is dirty
    event.preventDefault();

    // Check if form is dirty
    if (checkIfDirty(form, initialFormState.value)) {
        // If dirty, ask for confirmation
        requireConfirmation('unsaved_changes', () => {
            // Proceed with navigation or action
            window.location.href = route('accountType'); // Use route helper or your own method
        });
    } else {
        // If no unsaved changes, proceed directly
        window.location.href = route('accountType');
    }
};

// Define language labels and keys
const languageLabels = {
  en: 'English',
  tw: '中文（繁體）',
  cn: '中文（简体）',
};

const categories = ref(['individual', 'manage', 'promotion']);
const trade_delay_duration_options = ref([
    {name: '0 sec', value: '0'},
    {name: '1 sec', value: '1'},
    {name: '2 sec', value: '2'},
    {name: '3 sec', value: '3'},
    {name: '4 sec', value: '4'},
    {name: '5 sec', value: '5'},
    {name: '6 sec', value: '6'},
    {name: '7 sec', value: '7'},
    {name: '8 sec', value: '8'},
    {name: '9 sec', value: '9'},
    {name: '10 sec', value: '10'},
    {name: '1 min', value: '60'},
    {name: '2 min', value: '120'},
    {name: '3 min', value: '180'},
    {name: '4 min', value: '240'},
    {name: '5 min', value: '300'},
])
const leverages = ref(props.leverages);
const visibleToOptions = ref(props.visibleToOptions);

const promotionPeriods = ref([
    'no_expiry_date',
    'specific_date_range',
    'from_account_opening'
]);

const PromotionTypes = ref([
    {name: 'account_type_deposit', value: 'deposit'},
    {name: 'account_type_trade_volume', value: 'trade_volume'},
]);
const promotionType = ref(PromotionTypes.value[0]);

const BonusTypes = ref([
    'credit_bonus',
    'cash_bonus',
]);

const BonusAmountTypes = ref([
    'specified_amount',
    'percentage_of_deposit',
]);

const applicableDepositTypes = ref([
    'first_deposit_only',
    'each_deposit',
]);

const radioOptions = ref([
    {name: 'credit_withdraw_policy_1', value: 'no_withdraw'},
    {name: 'credit_withdraw_policy_2', value: 'withdraw_on_date'},
    {name: 'credit_withdraw_policy_3', value: 'withdraw_after_period'},
]);

// Get current date
const today = new Date();

// Clear date selection
const clearDate = () => {
    form.promotion_period = null;
};

// Form state tracking (for dirty check)
const form = useForm({
    id: props.accountType?.id || null,
    account_type_name: props.accountType?.name || null,
    category: props.accountType?.category || null,
    descriptions: {
        en: props.accountType?.descriptions ? JSON.parse(props.accountType.descriptions).en : null,
        tw: props.accountType?.descriptions ? JSON.parse(props.accountType.descriptions).tw : null,
        cn: props.accountType?.descriptions ? JSON.parse(props.accountType.descriptions).cn : null,
    },
    visible_to: 'public',
    members: [],
    leverage: props.accountType.leverage,
    trade_delay_duration: props.accountType?.trade_open_duration || null,
    max_account: props.accountType?.maximum_account_number || null,
    color: props.accountType?.color || null,
    promotion_title: null,
    promotion_description: null,
    promotion_period_type: promotionPeriods.value[0] || null,
    promotion_period: null,
    promotion_type: null,
    minimum_target: null,
    bonus_type: BonusTypes.value[0] || null,
    bonus_amount_type: BonusAmountTypes.value[0] || null,
    bonus_amount: null,
    maximum_bonus_cap: null,
    applicable_deposit: applicableDepositTypes.value[0] || null,
    credit_withdraw_policy: radioOptions.value[0]?.value || null,
    credit_withdraw_date_period: null,
});

// Track the initial state for dirty checking
const initialFormState = ref({ ...form });

// Watch for changes in promotion_period_type
watch(() => form.promotion_period_type, (newPromotionPeriod) => {
  if (newPromotionPeriod) {
    form.promotion_period = null;
  }
});

// Watch for changes in promotion_type
watch(promotionType, (newPromotionType) => {
  if (newPromotionType) {
    form.promotion_type = newPromotionType.value
    form.minimum_target = null;
  }
});

watch(() => form.promotion_type, (newPromotionType) => {
  if (newPromotionType === 'trade_volume') {
    form.bonus_amount_type = BonusAmountTypes.value[0];
    form.maximum_bonus_cap = null;
    form.applicable_deposit = null;
  } else {
    form.applicable_deposit = applicableDepositTypes.value[0];
  }
});

watch(() => form.bonus_amount_type, (newType) => {
    if (newType) {
        form.bonus_amount_type = newType
        form.bonus_amount = null;
    }
});

watch(() => form.credit_withdraw_policy, (newPolicy) => {
    if (newPolicy) {
        form.credit_withdraw_date_period = null;
    }
});

const search = ref(null);

const clearSearch = () => {
    search.value = null;
}

// Arrays to track selected members and groups globally
const selectedMembers = ref([]);
const selectedGroups = ref([]);

// Helper to optimize lookups in arrays
const selectedMembersSet = computed(() => new Set(selectedMembers.value));

// Watch for changes in selectedMembers
watch(selectedMembers, (newSelectedMembers) => {
  // We use the Set for faster lookup and avoid processing large arrays multiple times
  const newSelectedMembersSet = new Set(newSelectedMembers);

  visibleToOptions.value.forEach((group) => {
    // Check if all members of the group are selected
    const allMembersSelected = group.members.every((member) => newSelectedMembersSet.has(member.value));

    if (allMembersSelected && !selectedGroups.value.includes(group.name)) {
      // If all members are selected, add group name to selectedGroups
      selectedGroups.value.push(group.name);
    } else if (!allMembersSelected && selectedGroups.value.includes(group.name)) {
      // If not all members are selected, check if any member is still selected
      const anyMemberSelected = group.members.some((member) => newSelectedMembersSet.has(member.value));
      if (!anyMemberSelected) {
        // If no members are selected, remove the group name from selectedGroups
        selectedGroups.value = selectedGroups.value.filter((groupName) => groupName !== group.name);
      }
    }
  });
}, { deep: false }); // Use shallow watch

// Watch for changes in selectedGroups
watch(selectedGroups, (newSelectedGroups, oldSelectedGroups) => {
  visibleToOptions.value.forEach((group) => {
    // Handle Group Removal: If the group was in oldSelectedGroups but is now removed
    if (oldSelectedGroups.includes(group.name) && !newSelectedGroups.includes(group.name)) {
      group.members.forEach((member) => {
        // Only remove the member if it isn't in any other selected group
        if (!newSelectedGroups.some((groupName) => {
          const group = visibleToOptions.value.find(g => g.name === groupName);
          return group && group.members.some(m => m.value === member.value);
        })) {
          selectedMembers.value = selectedMembers.value.filter((value) => value !== member.value);
        }
      });
    }

    // Handle Group Addition: If the group is now added in newSelectedGroups
    if (newSelectedGroups.includes(group.name) && !oldSelectedGroups.includes(group.name)) {
      group.members.forEach((member) => {
        if (!selectedMembersSet.value.has(member.value)) {
          selectedMembers.value.push(member.value);
        }
      });
    }
  });
}, { deep: false }); // Use shallow watch

// Watch for changes in form.visible_to
watch(() => form.visible_to, (newValue) => {
  if (newValue) {
    selectedMembers.value = [];
    selectedGroups.value = [];
  }
});

const submitForm = () => {
    form.members = [...selectedMembers.value];

    form.post(route('accountType.updatePromotionConfiguration'), {
        onSuccess: () => {
            form.reset();
        },
    });
}

</script>

<template>
    <Head :title="$t('public.account_configuration')"></Head>

    <div class="min-h-screen bg-gray-100 flex flex-col">
        <div class="flex flex-col flex-1">
            <nav
                aria-label="secondary"
                class="flex w-full h-16 sticky top-0 z-10 py-2 px-5 gap-3 justify-center items-center bg-white"
            >
                <div class="w-full flex items-center gap-2">
                    <Button
                        external
                        type="button"
                        variant="primary-text"
                        size="sm"
                        @click="handleCancel"
                    >
                        {{ $t('public.account_type') }}
                    </Button>
                    <IconChevronRight
                        :size="20"
                        stroke-width="1.25"
                        class="text-gray-400"
                    />
                    <div class="flex justify-center items-center py-2 px-4 gap-2 rounded">
                        <span class="txt-gray-700 text-center text-sm font-medium">
                            {{ (props?.accountType ? props?.accountType?.name : '') + ` - ` + $t('public.account_configuration') }}
                        </span>
                    </div>
                </div>
                <Button
                    external
                    type="button"
                    variant="gray-outlined"
                    size="sm"
                    class="whitespace-nowrap"
                    @click="handleCancel"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary-flat"
                    size="sm"
                    class="whitespace-nowrap"
                    @click="submitForm"
                >
                    {{ $t('public.save_settings') }}
                </Button>
            </nav>
            <div class="flex flex-1 justify-center items-start p-5 gap-5 md:px-5">
                <ConfirmationDialog />
                <div class="w-full max-w-[1440px] flex justify-center">
                    <div class="w-full max-w-[728px] flex flex-col items-center gap-5">
                        <!-- Account Information -->
                        <div class="w-full flex flex-col justify-center items-center p-6 gap-5 rounded-lg bg-white shadow-card">
                            <span class="w-full text-gray-950 font-bold">{{ $t('public.account_information') }}</span>
                            <div class="w-full grid grid-cols-2 gap-5">
                                <div class="w-full min-w-[300px] flex flex-col items-start gap-2">
                                    <InputLabel for="account_type_name" :invalid="!!form.errors.account_type_name">{{ $t('public.account_type_name') }}</InputLabel>
                                    <InputText
                                        id="account_type_name"
                                        type="text"
                                        class="block w-full"
                                        v-model="form.account_type_name"
                                        :placeholder="$t('public.enter_name')"
                                        :invalid="!!form.errors.account_type_name"
                                        disabled
                                    />
                                    <InputError :message="form.errors.account_type_name" />
                                </div>
                                <div class="w-full min-w-[300px] flex flex-col items-start gap-2">
                                    <InputLabel for="category" :value="$t('public.account_type_category')" :invalid="!!form.errors.category"/>
                                    <Select
                                        v-model="form.category"
                                        :options="categories"
                                        class="w-full font-normal"
                                        scroll-height="236px"
                                    >
                                        <template #value="slotProps">
                                            <div v-if="slotProps.value" class="flex items-center gap-3">
                                                <div class="flex items-center gap-2">
                                                    <div>{{ $t('public.' + slotProps.value) }}</div>
                                                </div>
                                            </div>
                                        </template>
                                        <template #option="slotProps">
                                                <div class="flex items-center gap-2">
                                                    <div>{{ $t('public.' + slotProps.option) }}</div>
                                                </div>
                                            </template>
                                    </Select>
                                    <InputError :message="form.errors.category" />
                                </div>
                                <div class="w-full min-w-[400px] col-span-2 flex flex-col justify-center items-start gap-5">
                                    <div class="w-full flex flex-col items-start gap-2">
                                        <span class="text-gray-700 text-sm">{{ $t('public.description') }}</span>
                                        <div class="w-full flex flex-col items-center gap-3">
                                            <div
                                                v-for="(label, key) in languageLabels"
                                                :key="key"
                                                class="w-full flex items-center gap-3"
                                            >
                                                <div class="w-[120px] h-11 flex flex-shrink-0 items-start py-3 px-4 gap-3 rounded border border-gray-300 bg-white">
                                                    <span class="w-full text-gray-950 text-sm whitespace-nowrap">{{ label }}</span>
                                                </div>
                                                <div class="w-full flex flex-col">
                                                    <InputText
                                                        :id="'descriptions_' + key"
                                                        type="text"
                                                        class="block w-full"
                                                        v-model="form.descriptions[key]"
                                                        :placeholder="$t('public.descriptions_' + key + '_placeholder')"
                                                        :invalid="!!form.errors['descriptions.' + key]"
                                                    />
                                                    <InputError :message="form.errors['descriptions.' + key]" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full min-w-[300px] col-span-2 flex flex-col items-start gap-2">
                                    <InputLabel for="visible_to" :value="$t('public.visible_to')" :invalid="!!form.errors.visible_to"/>
                                    <div class="w-full flex items-center gap-8">
                                        <div class="flex items-center gap-3">
                                            <RadioButton
                                                v-model="form.visible_to"
                                                value="public"
                                                class="w-4 h-4"
                                            />
                                            <span class="text-gray-950 text-sm">{{ $t('public.visible_to_public') }}</span>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <RadioButton
                                                v-model="form.visible_to"
                                                value="selected_members"
                                                class="w-4 h-4"
                                            />
                                            <span class="text-gray-950 text-sm">{{ $t('public.visible_to_selected_members') }}</span>
                                        </div>

                                    </div>
                                    <div v-if="form.visible_to === 'selected_members'" class="w-full h-[500px] flex flex-col items-center rounded border border-gray-200 bg-white">
                                        <div class="w-full flex flex-col justify-center items-center p-3 gap-3 bg-white">
                                            <div class="relative w-full">
                                                <div class="absolute top-2/4 -mt-[9px] left-3 text-gray-500">
                                                    <IconSearch size="20" stroke-width="1.25" />
                                                </div>
                                                <InputText
                                                    v-model="search"
                                                    :placeholder="$t('public.keyword_search')"
                                                    size="search"
                                                    class="font-normal w-full"
                                                />
                                                <div
                                                    v-if="search !== null && search !== ''"
                                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                                    @click="clearSearch"
                                                >
                                                    <IconCircleXFilled size="16" />
                                                </div>
                                            </div>
                                            <!-- Grouped Members with Accordion -->
                                            <Accordion multiple class="w-full flex flex-col justify-center items-center gap-1">
                                                <AccordionPanel
                                                    v-for="(group, index) in visibleToOptions"
                                                    :key="index"
                                                    :value="group.value"
                                                    class="w-full flex flex-col justify-center gap-1"
                                                >
                                                    <AccordionHeader class="w-full flex flex-row-reverse justify-end items-center gap-2">
                                                        <span class="text-gray-950 text-sm">{{ group.name }}</span>
                                                        <Checkbox
                                                            v-model="selectedGroups"
                                                            :value="group.name"
                                                            class="w-4 h-4"
                                                            @click.stop
                                                        />
                                                    </AccordionHeader>

                                                    <AccordionContent class="w-full flex flex-col justify-center gap-1 pl-[22px]">
                                                        <div
                                                            v-for="(member, idx) in group.members"
                                                            :key="member.value"
                                                            class="flex items-center gap-2"
                                                        >
                                                        <Checkbox
                                                            v-model="selectedMembers"
                                                            :value="member.value"
                                                            class="w-4 h-4"
                                                        />
                                                        <span class="text-gray-950 text-sm">{{ member.label }}</span>
                                                        </div>
                                                    </AccordionContent>
                                                </AccordionPanel>
                                            </Accordion>
                                        </div>
                                    </div>
                                    <!-- <MultiSelect
                                        v-if="form.visible_to === 'selected_members'"
                                        v-model="selectedMemberValues"
                                        :options="visibleToOptions"
                                        optionLabel="label"
                                        optionGroupLabel="name"
                                        optionGroupChildren="members"
                                        group
                                        class="w-full font-normal"
                                        scroll-height="500px"
                                    >
                                        <template #value="{ value }">
                                        <div v-if="value && value.length" class="flex items-center gap-3">
                                            <span v-for="(member, index) in value" :key="index" class="text-sm">
                                            {{ member.label }}
                                            </span>
                                        </div>
                                        </template>

                                        <template #option="{ option }">
                                        <div class="flex items-center gap-2">
                                            <span>{{ option.label }}</span>
                                        </div>
                                        </template>

                                        <template #optiongroup="slotProps">
                                        <div class="flex items-center gap-2">
                                            <Checkbox
                                            v-model="selectedMemberValues"
                                            :checked="isGroupChecked(slotProps.option.value)"
                                            @change="toggleGroupSelection(slotProps.option.value)"
                                            class="w-4 h-4"
                                            />
                                            <div>{{ slotProps.option.name }}</div>
                                        </div>
                                        </template>
                                    </MultiSelect> -->
                                    <InputError :message="form.errors.visible_to" />
                                </div>
                            </div>
                        </div>

                        <!-- Trading Conditions -->
                        <div class="w-full flex flex-col justify-center items-center p-6 gap-5 rounded-lg bg-white shadow-card">
                            <span class="w-full text-gray-950 font-bold">{{ $t('public.trading_conditions') }}</span>
                            <div class="w-full grid grid-cols-2 gap-5">
                                <div class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="leverage" :value="$t('public.leverage')" :invalid="!!form.errors.leverage"/>
                                    <Select
                                        v-model="form.leverage"
                                        :options="leverages"
                                        optionLabel="name"
                                        optionValue="value"
                                        class="w-full font-normal"
                                        scroll-height="236px"
                                    >
                                    </Select>
                                    <InputError :message="form.errors.leverage" />
                                </div>
                                <div class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="trade_delay_duration" :value="$t('public.trade_delay_duration')" :invalid="!!form.errors.trade_delay_duration"/>
                                    <Select
                                        v-model="form.trade_delay_duration"
                                        :options="trade_delay_duration_options"
                                        optionLabel="name"
                                        optionValue="value"
                                        class="w-full font-normal"
                                        scroll-height="236px"
                                    >
                                    </Select>
                                    <InputError :message="form.errors.trade_delay_duration" />
                                </div>
                            </div>
                        </div>

                        <!-- Other Settings -->
                        <div class="w-full flex flex-col justify-center items-center p-6 gap-5 rounded-lg bg-white shadow-card">
                            <span class="w-full text-gray-950 font-bold">{{ $t('public.other_settings') }}</span>
                            <div class="w-full grid grid-cols-2 gap-5">
                                <div class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="max_account" :value="$t('public.maximum_account_creation')" :invalid="!!form.errors.max_account"/>
                                    <InputNumber
                                        v-model="form.max_account"
                                        id="max_account"
                                        fluid
                                        size="sm"
                                        :min="0"
                                        :step="1"
                                        class="w-full"
                                        inputClass="py-3 px-4"
                                        placeholder="0"
                                    />
                                    <InputError :message="form.errors.max_account" />
                                </div>
                                <div class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="color" :value="$t('public.colour')" :invalid="!!form.errors.color"/>
                                    <ColorPicker v-model="form.color" id="Color"/>
                                    <InputError :message="form.errors.color" />
                                </div>
                            </div>
                        </div>

                        <!-- Promotion Details Configuration -->
                        <div class="w-full flex flex-col justify-center items-center p-6 gap-5 rounded-lg bg-white shadow-card">
                            <span class="w-full text-gray-950 font-bold">{{ $t('public.promotion_details_configuration') }}</span>
                            <div class="w-full flex flex-col justify-center items-center gap-5">
                                <div class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="promotion_title" :value="$t('public.promotion_title')" :invalid="!!form.errors.promotion_title"/>
                                    <InputText
                                        id="promotion_title"
                                        type="text"
                                        class="block w-full"
                                        v-model="form.promotion_title"
                                        :placeholder="$t('public.promotion_title_placeholder')"
                                        :invalid="!!form.errors.promotion_title"
                                    />
                                    <InputError :message="form.errors.promotion_title" />
                                </div>

                                <div class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="promotion_description" :value="$t('public.promotion_description')" :invalid="!!form.errors.promotion_description"/>
                                    <InputText
                                        id="promotion_description"
                                        type="text"
                                        class="block w-full"
                                        v-model="form.promotion_description"
                                        :placeholder="$t('public.promotion_description_placeholder')"
                                        :invalid="!!form.errors.promotion_description"
                                    />
                                    <InputError :message="form.errors.promotion_description" />
                                </div>
                                
                                <div class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="promotion_period_type" :value="$t('public.promotion_period')" :invalid="!!form.errors.promotion_period_type"/>
                                    <div class="w-full grid grid-cols-2 gap-5">
                                        <Select
                                            v-model="form.promotion_period_type"
                                            :options="promotionPeriods"
                                            class="block w-full font-normal"
                                            scroll-height="236px"
                                        >
                                            <template #value="slotProps">
                                                <div v-if="slotProps.value" class="flex items-center">
                                                    <div>{{ $t(`public.${slotProps.value}`) }}</div>
                                                </div>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center">
                                                    {{ $t(`public.${slotProps.option}`) }}
                                                </div>
                                            </template>
                                        </Select>
                                        
                                        <div v-if="form.promotion_period_type === 'specific_date_range'" class="relative w-full">
                                            <DatePicker 
                                                v-model="form.promotion_period"
                                                selectionMode="range"
                                                :manualInput="false"
                                                :minDate="today"
                                                dateFormat="dd/mm/yy"
                                                showIcon
                                                iconDisplay="input"
                                                :placeholder="$t('public.promotion_select_date')"
                                                class="font-normal w-full"
                                            />
                                            <div
                                                v-if="form.promotion_period && form.promotion_period.length > 0"
                                                class="absolute top-[11px] right-3 flex justify-center items-center text-gray-400 select-none cursor-pointer bg-white w-6 h-6 "
                                                @click="clearDate"
                                            >
                                                <IconCircleXFilled size="20" />
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.promotion_period" />

                                        <InputNumber
                                            v-if="form.promotion_period_type === 'from_account_opening'"
                                            v-model="form.promotion_period"
                                            :minFractionDigits="0"
                                            fluid
                                            size="sm"
                                            :min="0"
                                            :step="5"
                                            class="w-full"
                                            inputClass="py-3 px-4"
                                            autofocus
                                            :invalid="!!form.errors.promotion_period"
                                            :placeholder="'e.g., 30'"
                                            :disabled="form.promotion_period_type !== 'from_account_opening'"
                                        />
                                        <InputError :message="form.errors.promotion_period" />
                                    </div>
                                </div>

                                <div class="w-full grid grid-cols-2 gap-5">
                                    <div class="w-full h-full min-w-[300px] flex flex-col items-start gap-2">
                                        <InputLabel for="promotion_type" :value="$t('public.promotion_type')" :invalid="!!form.errors.promotion_type"/>
                                        <Select
                                            v-model="promotionType"
                                            :options="PromotionTypes"
                                            class="block w-full font-normal"
                                            scroll-height="236px"
                                        >
                                            <template #value="slotProps">
                                                <div v-if="slotProps.value" class="flex items-center">
                                                    <div>{{ $t(`public.${slotProps.value.name}`) }}</div>
                                                </div>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center">
                                                    {{ $t(`public.${slotProps.option.name}`) }}
                                                </div>
                                            </template>
                                        </Select>
                                    </div>
                                    <div class="w-full h-full min-w-[300px] flex flex-col items-start gap-2">
                                        <InputLabel
                                            for="minimum_target"
                                            :value="$t(form.promotion_type === 'deposit' ? 'public.minimum_deposit_amount' : 'public.minimum_trade_lot_target')"
                                            :invalid="!!form.errors.minimum_target"
                                        />
                                            
                                        <InputNumber
                                            v-model="form.minimum_target"
                                            :minFractionDigits="2"
                                            fluid
                                            size="sm"
                                            :min="0"
                                            :step="100"
                                            :prefix="form.promotion_type === 'deposit' ? '$ ' : null"
                                            :suffix="form.promotion_type === 'trade_volume' ? ' Ł' : null"
                                            class="w-full"
                                            inputClass="py-3 px-4"
                                            autofocus
                                            :invalid="!!form.errors.minimum_target"
                                            :placeholder="form.promotion_type === 'deposit' ? '$ 0.00' : '0.00 Ł'"
                                        />
                                        <InputError :message="form.errors.minimum_target" />
                                    </div>
                                </div>
                                
                                <div class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="bonus_type" :value="$t('public.bonus_type')" :invalid="!!form.errors.bonus_type"/>
                                    <div class="w-full flex items-center gap-5">
                                        <Select
                                            v-model="form.bonus_type"
                                            :options="BonusTypes"
                                            class="block w-full font-normal"
                                            scroll-height="236px"
                                        >
                                            <template #value="slotProps">
                                                <div v-if="slotProps.value" class="flex items-center">
                                                    <div>{{ $t(`public.${slotProps.value}`) }}</div>
                                                </div>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center">
                                                    {{ $t(`public.${slotProps.option}`) }}
                                                </div>
                                            </template>
                                        </Select>

                                        <Select
                                            v-model="form.bonus_amount_type"
                                            :options="BonusAmountTypes"
                                            class="block w-full font-normal"
                                            scroll-height="236px"
                                            :disabled="form.promotion_type === 'trade_volume'"
                                        >
                                            <template #value="slotProps">
                                                <div v-if="slotProps.value" class="flex items-center">
                                                    <div>{{ $t(`public.${slotProps.value}`) }}</div>
                                                </div>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center">
                                                    {{ $t(`public.${slotProps.option}`) }}
                                                </div>
                                            </template>
                                        </Select>

                                        <InputNumber
                                            v-model="form.bonus_amount"
                                            :minFractionDigits="form.bonus_amount_type == 'specified_amount' ? 2 : 0"
                                            fluid
                                            size="sm"
                                            :min="0"
                                            :step="100"
                                            :prefix="form.bonus_amount_type == 'specified_amount' ? '$ ' : null"
                                            :suffix="form.bonus_amount_type == 'percentage_of_deposit' ? ' %' : null"
                                            class="w-full"
                                            inputClass="py-3 px-4"
                                            autofocus
                                            :invalid="!!form.errors.bonus_amount"
                                            :placeholder="form.bonus_amount_type == 'specified_amount' ? '$ 0.00' : '0 %'"
                                        />
                                        <InputError :message="form.errors.bonus_amount" />

                                    </div>
                                </div>

                                <div v-if="form.promotion_type !== 'trade_volume'" class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="maximum_bonus_cap" :invalid="!!form.errors.maximum_bonus_cap">
                                        {{ $t('public.maximum_bonus_cap') }}
                                    </InputLabel>
                                    <InputNumber
                                        v-model="form.maximum_bonus_cap"
                                        :minFractionDigits="2"
                                        fluid
                                        size="sm"
                                        :min="0"
                                        :step="100"
                                        :prefix="'$ '"
                                        class="w-full"
                                        inputClass="py-3 px-4"
                                        autofocus
                                        :invalid="!!form.errors.maximum_bonus_cap"
                                        :placeholder="'$ 0.00'"
                                        :disabled="form.promotion_type === 'trade_volume'"
                                    />
                                    <InputError :message="form.errors.maximum_bonus_cap" />
                                </div>

                                <div v-if="form.promotion_type !== 'trade_volume'" class="w-full flex flex-col items-start gap-2">
                                    <InputLabel for="applicable_deposit" :invalid="!!form.errors.applicable_deposit">
                                        {{ $t('public.applicable_deposit') }}
                                    </InputLabel>
                                    <Select
                                        v-model="form.applicable_deposit"
                                        :options="applicableDepositTypes"
                                        class="block w-full font-normal"
                                        scroll-height="236px"
                                        :disabled="form.promotion_type === 'trade_volume'"
                                    >
                                        <template #value="slotProps">
                                            <div v-if="slotProps.value" class="flex items-center">
                                                <div>{{ $t(`public.${slotProps.value}`) }}</div>
                                            </div>
                                        </template>
                                        <template #option="slotProps">
                                            <div class="flex items-center">
                                                {{ $t(`public.${slotProps.option}`) }}
                                            </div>
                                        </template>
                                    </Select>
                                    <InputError :message="form.errors.applicable_deposit" />
                                </div>

                                <div class="w-full flex flex-col items-start gap-3">
                                    <InputLabel for="credit_withdraw_policy" :invalid="!!form.errors.credit_withdraw_policy">
                                        {{ $t('public.credit_withdraw_policy') }}
                                    </InputLabel>
                                    <div class="w-full flex flex-col justify-center items-start gap-4">
                                        <div v-for="option in radioOptions" :key="option.name" class="w-full flex flex-col justify-center items-start gap-2">
                                            <div class="w-full flex items-center gap-3">
                                                <RadioButton
                                                    v-model="form.credit_withdraw_policy"
                                                    :value="option.value"
                                                    class="w-4 h-4"
                                                />
                                                <span class="text-gray-950 text-sm">{{ $t('public.' + option.name) }}</span>
                                            </div>
                                            <div v-if="option.value === 'withdraw_on_date' && form.credit_withdraw_policy === 'withdraw_on_date'" class="w-full flex items-center pl-7 gap-3">
                                                <span class="text-gray-950 text-sm text-nowrap">{{ $t('public.promotion_select_date') }}:</span>
                                                <div class="relative w-full">
                                                    <DatePicker 
                                                        v-model="form.credit_withdraw_date_period"
                                                        selectionMode="single"
                                                        :manualInput="false"
                                                        :minDate="today"
                                                        dateFormat="dd/mm/yy"
                                                        showIcon
                                                        iconDisplay="input"
                                                        :placeholder="'dd/mm/yy'"
                                                        class="font-normal w-full"
                                                    />
                                                    <div
                                                        v-if="form.credit_withdraw_date_period"
                                                        class="absolute top-[11px] right-3 flex justify-center items-center text-gray-400 select-none cursor-pointer bg-white w-6 h-6 "
                                                        @click="form.credit_withdraw_date_period = null"
                                                    >
                                                        <IconCircleXFilled size="20" />
                                                    </div>
                                                </div>
                                                <InputError :message="form.errors.credit_withdraw_date_period" />

                                            </div>
                                            <div v-if="option.value === 'withdraw_after_period' && form.credit_withdraw_policy === 'withdraw_after_period'" class="w-full flex items-center pl-7 gap-3">
                                                <span class="text-gray-950 text-sm text-nowrap">{{ $t('public.enter_number_of_days') }}:</span>
                                                <InputNumber
                                                    v-model="form.credit_withdraw_date_period"
                                                    :minFractionDigits="0"
                                                    fluid
                                                    size="sm"
                                                    :min="0"
                                                    :step="5"
                                                    class="w-full"
                                                    inputClass="py-3 px-4"
                                                    autofocus
                                                    :invalid="!!form.errors.credit_withdraw_date_period"
                                                    :placeholder="'e.g., 30'"
                                                    :disabled="form.credit_withdraw_policy !== 'withdraw_after_period'"
                                                />
                                                <InputError :message="form.errors.credit_withdraw_date_period" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
