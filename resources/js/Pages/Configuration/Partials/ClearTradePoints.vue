<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {ref, h, watch, computed} from "vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import { useConfirm } from "primevue/useconfirm";
import Checkbox from 'primevue/checkbox';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';
import debounce from "lodash/debounce.js";
import { useForm } from "@inertiajs/vue3";
import { trans, wTrans } from "laravel-vue-i18n";
import {
    IconSearch,
    IconCircleXFilled,
    IconTrashX,
} from "@tabler/icons-vue";
import { router } from "@inertiajs/vue3";

const clearVisible = ref(false);

const form = useForm({
    select_member: 'all_members',
    members: [],
    remarks: '',
})

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        clear_points: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.confirm_trade_points_clear'),
            message: trans('public.confirm_trade_points_clear_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.clear_now'),
            action: () => {
                submitForm();
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

const submitForm = () => {
    form.members = [...selectedMembers.value];

    form.post(route('configuration.clearPoints'), {
        onSuccess: () => {
            clearVisible.value = false;
            form.reset();
        },
    });
}

const openClearDialog = () => {
    form.reset();
    form.clearErrors(); 
    clearVisible.value = true;
}

const search = ref('');

const clearSearch = () => {
    search.value = null;
}

const loading = ref(false);
const visibleToOptions = ref([]);
const getResults = async () => {
    loading.value = true;

    try {
        let url = `/configuration/getVisibleToOptions`;

        if (search.value) {
            url += `?search=${search.value}`;
        }

        const response = await axios.get(url);
        visibleToOptions.value = response.data.visibleToOptions;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
        // After the results are updated, we check group selection states
        updateGroupSelection();
    }
};

getResults();

// Watch for changes in search and trigger the getResults function
watch(search, debounce(() => {
    getResults();
}, 1000));

// Arrays to track selected members and groups globally
const selectedMembers = ref([]);
const selectedGroups = ref([]);

// Helper to optimize lookups in arrays
const selectedMembersSet = computed(() => new Set(selectedMembers.value));

// Track when a group name removal is due to deselection of all members
let removingDueToDeselect = false;

// Watch for changes in selectedMembers
watch(selectedMembers, (newSelectedMembers) => {
    const newSelectedMembersSet = new Set(newSelectedMembers);

    // Track which groups should be removed from selectedGroups
    const groupsToRemove = [];

    visibleToOptions.value.forEach((group) => {
        const allMembersSelected = group.members.every((member) => newSelectedMembersSet.has(member.value));

        if (allMembersSelected && !selectedGroups.value.includes(group.name)) {
            selectedGroups.value.push(group.name);
        } else if (!allMembersSelected && selectedGroups.value.includes(group.name)) {
            // This indicates the group is incomplete (not all members are selected)
            groupsToRemove.push(group.name);
        }
    });

    // Remove the flagged groups from selectedGroups (only the group name, not members)
    selectedGroups.value = selectedGroups.value.filter((groupName) => !groupsToRemove.includes(groupName));

    // Set the flag for group removal due to member deselection
    removingDueToDeselect = groupsToRemove.length > 0;

}, { deep: false }); // Use shallow watch

// Watch for changes in selectedGroups
watch(selectedGroups, (newSelectedGroups, oldSelectedGroups) => {
    visibleToOptions.value.forEach((group) => {
        // Handle Group Removal: If the group was in oldSelectedGroups but is now removed
        if (oldSelectedGroups.includes(group.name) && !newSelectedGroups.includes(group.name)) {
            if (!removingDueToDeselect) {
                // Explicit group removal: Remove all members of the group from selectedMembers
                group.members.forEach((member) => {
                    selectedMembers.value = selectedMembers.value.filter((value) => value !== member.value);
                });
            }
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

    // Reset the flag after processing
    removingDueToDeselect = false;
}, { deep: false }); // Use shallow watch

// Function to update group selection status based on the current visibleToOptions and selectedMembers
const updateGroupSelection = () => {
    visibleToOptions.value.forEach((group) => {
        // Check if all members of the group are selected
        const allMembersSelected = group.members.every((member) => selectedMembers.value.includes(member.value));

        // If all members are selected and the group is not in selectedGroups, add it
        if (allMembersSelected && !selectedGroups.value.includes(group.name)) {
            selectedGroups.value.push(group.name);
        }

        // If not all members are selected and the group is in selectedGroups, remove it
        if (!allMembersSelected && selectedGroups.value.includes(group.name)) {
            // Only remove the group name, not the members
            selectedGroups.value = selectedGroups.value.filter(groupName => groupName !== group.name);
        }
    });

    // Update the removingDueToDeselect flag after group removal logic
    removingDueToDeselect = selectedGroups.value.length === 0;
};

// Watch for changes in form.select_member
watch(() => form.select_member, (newValue) => {
  if (newValue) {
    selectedMembers.value = [];
    selectedGroups.value = [];
  }
});
</script>

<template>
    <Button
        type="button"
        variant="error-outlined"
        size="base"
        @click="openClearDialog()"
        class="w-full md:w-auto"
    >
        {{ $t('public.clear_trade_points') }}
    </Button>

    <Dialog v-model:visible="clearVisible" modal :header="$t('public.clear_trade_points')" class="dialog-xs md:dialog-sm" :closeOnEscape="false">
        <form @submit.prevent>
            <div class="flex flex-col gap-5 py-6">
                <div class="flex flex-col gap-2">
                    <InputLabel
                        for="select_member"
                        :value="$t('public.select_member')"
                        :invalid="!!form.errors.select_member"
                    />
                    <div class="w-full flex flex-row items-center gap-3 md:gap-5 self-stretch">
                        <div class="flex items-center gap-2 md:gap-3 text-gray-950 text-sm">
                            <RadioButton
                                v-model="form.select_member"
                                inputId="all_members"
                                value="all_members"
                                class="w-5 h-5"
                            />
                            <label for="public">{{ $t('public.public') }}</label>
                        </div>
                        <div class="flex items-center gap-2 md:gap-3 text-gray-950 text-sm">
                            <RadioButton
                                v-model="form.select_member"
                                inputId="selected_members"
                                value="selected_members"
                                class="w-5 h-5"
                            />
                            <label for="selected_members">{{ $t('public.selected_members') }}</label>
                        </div>
                    </div>
                    <div 
                        v-if="form.select_member === 'selected_members'" 
                        class="w-full flex flex-col items-center border bg-white"
                        :class="{'rounded border-gray-200': !form.errors.members, 'border-error-500': form.errors.members}"
                    >
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
                            <div class="w-full flex flex-col justify-center items-center">
                                <Accordion multiple class="w-full flex flex-col justify-center items-center gap-1">
                                    <div class="w-full max-h-[415px] overflow-auto">
                                        <AccordionPanel
                                            v-for="(group, index) in visibleToOptions"
                                            :key="index"
                                            :value="group.value"
                                            class="w-full flex flex-col justify-center gap-1"
                                        >
                                            <AccordionHeader class="w-full flex flex-row-reverse justify-end items-center gap-2">
                                                <span class="truncate text-gray-950 text-sm">{{ group.name }}</span>
                                                <Checkbox
                                                    v-model="selectedGroups"
                                                    :value="group.name"
                                                    class="w-4 h-4 grow-0 shrink-0"
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
                                                        class="w-4 h-4 grow-0 shrink-0"
                                                    />
                                                    <span class="w-full truncate text-gray-950 text-sm">{{ member.label }}</span>
                                                </div>
                                            </AccordionContent>
                                        </AccordionPanel>
                                    </div>
                                </Accordion>
                            </div>
                        </div>
                    </div>
                    <InputError v-if="form.select_member === 'selected_members'" :message="form.errors.members" />
                </div>
                <div class="flex flex-col gap-2 self-stretch">
                    <InputLabel
                        for="remarks"
                        :value="$t('public.remarks')"
                        :invalid="!!form.errors.remarks"
                    />
                    <InputText
                        id="remarks"
                        type="text"
                        class="block w-full"
                        v-model="form.remarks"
                        :invalid="!!form.errors.remarks"
                    />
                    <span class="text-xs text-gray-500">
                        {{ $t('public.clear_trade_points_notice') }}
                    </span>
                    <InputError :message="form.errors.remarks" />
                </div>
            </div>
            <div class="flex flex-row items-center justify-center w-full">
                <Button
                    variant="primary-flat"
                    size="base"
                    class="w-full"
                    @click="requireConfirmation('clear_points')"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
