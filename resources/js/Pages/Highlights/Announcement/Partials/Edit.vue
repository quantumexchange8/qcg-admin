<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {h, ref, watch, computed, watchEffect} from "vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import InputNumber from "primevue/inputnumber";
import {useForm, usePage} from '@inertiajs/vue3';
import Select from "primevue/select";
import FileUpload from 'primevue/fileupload';
import Datepicker from 'primevue/datepicker';
import { IconPlus, IconUpload, IconX, IconAlertTriangle, IconSearch, IconCircleXFilled } from "@tabler/icons-vue";
import RadioButton from 'primevue/radiobutton';
import ToggleSwitch from 'primevue/toggleswitch';
import { transactionFormat } from "@/Composables/index.js";
import Textarea from "primevue/textarea";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import Checkbox from 'primevue/checkbox';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';
import debounce from "lodash/debounce.js";

const props = defineProps({
    announcement: Object,
});

const { formatAmount, formatDate } = transactionFormat();

const visible = ref(false)

const openDialog = () => {
    form.reset();
    form.clearErrors();
    selectedAttachment.value = props.announcement.thumbnail || null;
    visible.value = true;
}

// const closeDialog = () => {
//     visible.value = false;
// }

// const form = useForm({
//     visible_to: 'public',
//     members: [],
//     popup: 'none',
//     start_date: '',
//     end_date: '',
//     subject: '',
//     message: '',
//     thumbnail: '',
// });

const form = useForm({
    announcement_id: props.announcement.id,
    visible_to: props.announcement.recipient || 'public',
    members: [],
    popup: props.announcement.popup_login ? props.announcement.popup_login : 'none',
    start_date: props.announcement.start_date ? formatDate(props.announcement.start_date) : '',
    end_date: props.announcement.end_date ? formatDate(props.announcement.end_date) : '',
    subject: props.announcement.title ? props.announcement.title : '',
    message: props.announcement.content ? props.announcement.content : '',
    thumbnail: props.announcement.thumbnail ? props.announcement.thumbnail : '',
    status: props.announcement.status ? props.announcement.status : '',
});

const selectedAttachment = ref(props.announcement.thumbnail || null);
const selectedAttachmentName = ref(props.announcement.thumbnail ? props.announcement.thumbnail.split('/').pop() : null);

const handleAttachment = (event) => {
    const attachmentInput = event.target;
    const file = attachmentInput.files[0];

    if (file) {
        // Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedAttachment.value = reader.result;
        };
        reader.readAsDataURL(file);
        selectedAttachmentName.value = file.name;
        form.thumbnail = event.target.files[0];
    } else {
        selectedAttachment.value = null;
    }
};

const removeAttachment = () => {
    selectedAttachment.value = null;
    form.thumbnail = '';
};

const today = new Date();

const submitForm = (status) => {
    form.members = [...selectedMembers.value];
    form.status = status;
    
    if (form.start_date) {
        form.start_date = formatDate(form.start_date);
    }

    if (form.end_date) {
        form.end_date = formatDate(form.end_date);
    }

    form.post(route('highlights.editAnnouncement'), {
        onSuccess: () => {
            visible.value = false;
            form.reset();
            removeAttachment();
        },
    });
}

const confirm = useConfirm();

const publishConfirmation = (action_type) => {
    const messages = {
        no_date_publish: {
            group: 'headless',
            color: 'primary',
            icon: h(IconAlertTriangle),
            header: trans('public.publish_announcement_no_date'),
            message: trans('public.publish_announcement_no_date_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.publish_now'),
            action: () => {
                submitForm('inactive');

                // checked.value = !checked.value;
            }
        },
        with_date_publish: {
            group: 'headless',
            color: 'primary',
            icon: h(IconAlertTriangle),
            header: trans('public.publish_announcement_with_date'),
            message: trans('public.publish_announcement_with_date_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.schedule'),
            action: () => {
                submitForm('scheduled');

                // checked.value = !checked.value;
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

const search = ref('');

const clearSearch = () => {
    search.value = null;
}

const loading = ref(false);
const visibleToOptions = ref([]);
const getResults = async () => {
    loading.value = true;

    try {
        let url = `/highlights/getVisibleToOptions`;

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

// Watch for changes in form.visible_to
watch(() => form.visible_to, (newValue) => {
  if (newValue) {
    selectedMembers.value = [];
    selectedGroups.value = [];
  }
});

const previewVisible = ref(false);
const data = ref({});
const openPreviewDialog = () => {
    previewVisible.value = true;
    data.value = form.data();
};
</script>

<template>
    <Button
        v-if="props.announcement.status === 'draft' || props.announcement.status === 'scheduled'"
        type="button"
        variant="gray-outlined"
        size="base"
        class="w-full"
        @click="openDialog()"
    >
        {{ $t('public.edit') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.new_announcement')"
        class="dialog-xs md:dialog-md"
        :closeOnEscape="false"
    >
        <form @submit.prevent="submitForm()">
            <div class="flex flex-col py-4 gap-6 self-stretch md:py-6 md:gap-8">

                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-2">
                            <InputLabel
                                for="visible_to"
                                :value="$t('public.visible_to')"
                                :invalid="!!form.errors.visible_to"
                            />
                            <div class="w-full flex flex-row items-center gap-3 md:gap-5 self-stretch">
                                <div class="flex items-center gap-2 md:gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.visible_to"
                                        inputId="public"
                                        value="public"
                                        class="w-5 h-5"
                                    />
                                    <label for="public">{{ $t('public.public') }}</label>
                                </div>
                                <div class="flex items-center gap-2 md:gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.visible_to"
                                        inputId="selected_members"
                                        value="selected_members"
                                        class="w-5 h-5"
                                    />
                                    <label for="selected_members">{{ $t('public.selected_members') }}</label>
                                </div>
                            </div>
                            <div 
                                v-if="form.visible_to === 'selected_members'" 
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
                        </div>
                        <div class="flex flex-col gap-2">
                            <InputLabel
                                for="popup"
                                :value="$t('public.popup_label')"
                                :invalid="!!form.errors.popup"
                            />
                            <div class="w-full flex flex-col md:flex-row items-start md:items-center gap-3 md:gap-5 self-stretch">
                                <div class="flex items-center gap-2 md:gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.popup"
                                        inputId="none"
                                        value="none"
                                        class="w-5 h-5"
                                    />
                                    <label for="none">{{ $t('public.no') }}</label>
                                </div>
                                <div class="flex items-center gap-2 md:gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.popup"
                                        inputId="first_login"
                                        value="first"
                                        class="w-5 h-5"
                                    />
                                    <label for="first_login">{{ $t('public.first_login_desc') }}</label>
                                </div>
                                <div class="flex items-center gap-2 md:gap-3 text-gray-950 text-sm">
                                    <RadioButton
                                        v-model="form.popup"
                                        inputId="every_login"
                                        value="every"
                                        class="w-5 h-5"
                                    />
                                    <label for="every_login">{{ $t('public.every_login_desc') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-5">
                            <div class="flex flex-col gap-2">
                                <InputLabel>{{ $t('public.start_date') }}</InputLabel>
                                <Datepicker
                                    v-model="form.start_date"
                                    selectionMode="single"
                                    dateFormat="yy/mm/dd"
                                    showIcon
                                    iconDisplay="input"
                                    :placeholder="$t('public.select_date')"
                                    class="w-full font-normal"
                                />
                            </div>
                            <div class="flex flex-col gap-2">
                                <InputLabel>{{ $t('public.end_date') }}</InputLabel>
                                <Datepicker
                                    v-model="form.end_date"
                                    :minDate="today"
                                    selectionMode="single"
                                    dateFormat="yy/mm/dd"
                                    showIcon
                                    iconDisplay="input"
                                    :placeholder="$t('public.select_date')"
                                    class="w-full font-normal"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <InputLabel
                                for="subject"
                                :value="$t('public.subject')"
                            />
                            <InputText
                                id="subject"
                                type="text"
                                class="block w-full"
                                v-model="form.subject"
                                :placeholder="$t('public.subject_placeholder')"
                                :invalid="!!form.errors.subject"
                            />
                            <InputError :message="form.errors.subject" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <InputLabel
                                for="message"
                                :value="$t('public.message')"
                            />
                            <Textarea
                                id="message"
                                type="text"
                                class="w-full h-24"
                                v-model="form.message"
                                :placeholder="$t('public.message_placeholder')"
                                :invalid="!!form.errors.message"
                                rows="5"
                                cols="30"
                            />
                            <InputError :message="form.errors.message" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <div class="flex flex-col gap-3">
                                <InputLabel
                                    for="thumbnail"
                                    :value="$t('public.thumbnail')"
                                    class="font-bold"
                                />
                                <span class="self-stretch text-gray-500 text-xs">{{ $t('public.file_size_limit') }}</span>
                            </div>
                            <div class="flex flex-col gap-3">
                                <input
                                    ref="attachmentInput"
                                    id="attachment"
                                    type="file"
                                    class="hidden"
                                    accept="image/*"
                                    @change="handleAttachment"
                                />
                                <Button
                                    type="button"
                                    variant="primary-flat"
                                    @click="$refs.attachmentInput.click()"
                                    class="w-fit"
                                >
                                    <IconUpload size="20" color="#ffffff" stroke-width="1.25" />

                                    {{ $t('public.choose') }}
                                </Button>
                                <InputError :message="form.errors.thumbnail" />
                            </div>
                            <div
                                v-if="selectedAttachment"
                                class="relative w-full py-3 pl-4 flex justify-between rounded-xl bg-gray-50"
                            >
                                <div class="inline-flex items-center gap-3">
                                    <img :src="selectedAttachment" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                    <div class="text-sm text-gray-950">
                                        {{ selectedAttachmentName }}
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    variant="gray-text"
                                    @click="removeAttachment"
                                    pill
                                    iconOnly
                                >
                                    <IconX size="20" color="#374151" stroke-width="1.25" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full md:flex md:flex-row grid grid-cols-2 justify-center items-stretch gap-4 pt-6 self-stretch">
                <Button
                    type="button"
                    size="base"
                    class="w-full"
                    variant="gray-outlined"
                    @click="openPreviewDialog()"
                >
                    {{ $t('public.preview') }}
                </Button>
                <Button
                    type="button"
                    size="base"
                    class="w-full"
                    variant="gray-outlined"
                    @click="submitForm('draft')"
                    :disabled="form.processing"
                >
                    {{ $t('public.save_as_draft') }}
                </Button>
                <Button
                    type="button"
                    variant="primary-flat"
                    size="base"
                    class="w-full col-span-2"
                    @click="form.start_date ? publishConfirmation('with_date_publish') : publishConfirmation('no_date_publish')"
                    :disabled="form.processing"
                >
                    {{ $t('public.publish') }}
                </Button>
            </div>
        </form>
    </Dialog>

    <Dialog v-model:visible="previewVisible" modal :header="$t('public.preview')"  class="dialog-xs md:dialog-md no-header-border" :dismissableMask="true">
        <div class="flex flex-col justify-center items-start gap-8 pb-6 self-stretch">
            <img v-if="data.thumbnail" :src="selectedAttachment" :alt="data.thumbnail.name" class="w-full h-[144px] md:h-[310.5px]" />

            <span class="text-lg font-bold text-gray-950">{{ data.subject }}</span>

            <!-- need to ask nic about this content if got html tag -->
            <span class="text-md font-regular text-gray-950" v-html="data.message"></span>

        </div>
    </Dialog>
</template>
