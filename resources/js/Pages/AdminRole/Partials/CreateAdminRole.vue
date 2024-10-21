<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {ref, watchEffect} from "vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import {useForm, usePage} from '@inertiajs/vue3';
import Select from "primevue/select";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import Password from 'primevue/password';
import FileUpload from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
import { IconPlus, IconUpload, IconX } from "@tabler/icons-vue";
import Stepper from 'primevue/stepper';
import StepList from 'primevue/steplist';
import StepPanels from 'primevue/steppanels';
import Step from 'primevue/step';
import StepPanel from 'primevue/steppanel';
import ToggleSwitch from "primevue/toggleswitch";

const props = defineProps({
    permissionsList: Array,
})

const visible = ref(false)
const activeStep = ref(1);

const closeDialog = () => {
    visible.value = false;
    form.reset();
    activeStep.value = 1;
}

const form = useForm({
    first_name: '',
    email: '',
    role: '',
    profile_photo: '',
    permissions: props.permissionsList.map(permission => permission.name),  // Extract permission names
});


const permissionsState = ref({});

// Initialize permissionsState based on permissionsList
watchEffect(() => {
    // Reset permissionsState
    permissionsState.value = {};
    props.permissionsList.forEach(permission => {
        permissionsState.value[permission.name] = form.permissions.includes(permission.name);
    });
    // console.log(permissionsState)

});

// Update the togglePermission function to modify permissionsState
const togglePermission = (permission) => {
    const index = form.permissions.indexOf(permission);
    if (index > -1) {
        // Permission exists, remove it
        form.permissions.splice(index, 1);
    } else {
        // Permission does not exist, add it
        form.permissions.push(permission);
    }
    // Update the permissionsState to reflect the current state
    permissionsState.value[permission] = !permissionsState.value[permission]; // Toggle the state
    // console.log(form.permissions)
};

const selectedAttachment = ref(null);
const selectedAttachmentName = ref(null);
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
        form.profile_photo = event.target.files[0];
    } else {
        selectedAttachment.value = null;
    }
};

const removeAttachment = () => {
    selectedAttachment.value = null;
    form.profile_photo = '';
};

const validate = (activateCallback) => {
    form.post(route('adminRole.firstStep'), {
        onSuccess: () => {
            activateCallback(2);
        },
    });
}


const submit = () => {
    form.post(route('adminRole.addNewAdmin'), {
        onSuccess: () => {
            visible.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        size="base"
        class='w-full md:w-auto truncate'
        @click="visible = true"
    >
        <IconPlus size="20" stroke-width="1.25" />
        {{ $t('public.create_admin_role') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.create_admin_role')"
        class="dialog-xs md:dialog-sm"
    >
        <form>
            <div class="flex flex-col items-center pt-4 self-stretch md:pt-6">
                <Stepper v-model:value="activeStep" linear>
                    <StepList class="w-full">
                        <Step :value="1">{{$t('public.basic_info')}}</Step>
                        <Step :value="2">{{$t('public.permissions')}}</Step>
                    </StepList>
                    <StepPanels>
                        <StepPanel v-slot="{ activateCallback }" :value="1">
                            <div class="grid pt-6 pb-4 gap-6 md:pt-8 md:pb-6 md:gap-8">
                                <!-- Basic Information -->
                                <div class="flex flex-col gap-3 items-center self-stretch md:gap-5">
                                    <div class="grid grid-cols-1 gap-3 md:gap-5 w-full">
                                        <div class="space-y-2">
                                            <InputLabel for="first_name" :value="$t('public.name')" :invalid="!!form.errors.first_name" />
                                            <InputText
                                                id="first_name"
                                                type="text"
                                                class="block w-full"
                                                v-model="form.first_name"
                                                placeholder="eg. John Doe"
                                                :invalid="!!form.errors.first_name"
                                                autofocus
                                            />
                                            <InputError :message="form.errors.first_name" />
                                        </div>
                                        <div class="space-y-2">
                                            <InputLabel for="email" :value="$t('public.email')" :invalid="!!form.errors.email" />
                                            <InputText
                                                id="email"
                                                type="email"
                                                class="block w-full"
                                                v-model="form.email"
                                                :placeholder="$t('public.enter_email')"
                                                :invalid="!!form.errors.email"
                                            />
                                            <InputError :message="form.errors.email" />
                                        </div>
                                        <div class="space-y-2">
                                            <InputLabel for="role" :invalid="!!form.errors.role">{{ `${$t('public.role')}&nbsp;(${$t('public.optional')})` }}</InputLabel>
                                            <InputText
                                                id="role"
                                                type="role"
                                                class="block w-full"
                                                v-model="form.role"
                                                placeholder="eg. Manager"
                                                :invalid="!!form.errors.role"
                                            />
                                            <InputError :message="form.errors.role" />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center gap-3 self-stretch">
                                    <span class="self-stretch text-gray-950 text-sm font-bold">{{ $t('public.upload_profile_photo') }}</span>
                                    <div class="flex flex-col items-start gap-3 self-stretch">
                                        <span class="self-stretch text-gray-500 text-xs">{{ $t('public.file_size_limit') }}</span>
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
                                            >
                                                <IconUpload size="20" color="#ffffff" stroke-width="1.25" />

                                                {{ $t('public.choose') }}
                                            </Button>
                                            <InputError :message="form.errors.kyc_verification" />
                                        </div>
                                        <div
                                            v-if="selectedAttachment"
                                            class="relative w-full py-3 px-4 flex justify-between rounded-xl bg-gray-50"
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
                            <div class="w-full flex justify-end items-center gap-4 pt-6 self-stretch">
                                <Button
                                    type="button"
                                    size="base"
                                    class="w-full"
                                    variant="gray-outlined"
                                    @click="closeDialog"
                                >
                                    {{ $t('public.cancel') }}
                                </Button>
                                <Button
                                    variant="primary-flat"
                                    size="base"
                                    class="w-full"
                                    @click="() => validate(activateCallback)"
                                    :disabled="form.processing"
                                >
                                    {{ $t('public.next') }}
                                </Button>
                            </div>
                        </StepPanel>
                        <StepPanel v-slot="{ activateCallback }" :value="2">
                            <div class="grid pt-6 pb-4 gap-6 md:pt-8 md:pb-6 md:gap-8">
                                <!-- Permissions -->
                                <div class="flex flex-col items-center gap-5 self-stretch">
                                    <div
                                        v-for="permission in props.permissionsList"
                                        :key="permission.id"
                                        class="flex justify-center items-center gap-3 self-stretch"
                                    >
                                        <ToggleSwitch
                                            v-model="permissionsState[permission.name]"
                                            @change="() => togglePermission(permission.name)"
                                        />
                                        <span class="w-full text-gray-700 text-sm font-medium">{{ $t('public.allow_permission', {permission: $t(`public.${permission.name}`)})}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="w-full flex justify-end items-center gap-4 pt-6 self-stretch">
                                <Button
                                    type="button"
                                    size="base"
                                    class="w-full"
                                    variant="gray-outlined"
                                    @click="closeDialog"
                                >
                                    {{ $t('public.cancel') }}
                                </Button>
                                <Button
                                    variant="primary-flat"
                                    size="base"
                                    class="w-full"
                                    @click="submit"
                                    :disabled="form.processing"
                                >
                                    {{ $t('public.create') }}
                                </Button>
                            </div>
                        </StepPanel>
                    </StepPanels>
                </Stepper>
            </div>
        </form>
    </Dialog>
</template>
