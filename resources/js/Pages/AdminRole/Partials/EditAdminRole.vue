<script setup>
import Button from "@/Components/Button.vue";
import { ref } from "vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import { useForm } from '@inertiajs/vue3';
import { IconUpload, IconX } from "@tabler/icons-vue";

const props = defineProps({
    admin: Object,
})

const emit = defineEmits(['update:visible'])

const closeDialog = () => {
    emit('update:visible', false);
    form.reset();
}

const form = useForm({
    id: props.admin.id,
    first_name: props.admin.first_name,
    email: props.admin.email,
    role: props.admin.role,
    profile_photo: props.admin.profile_photo,
});

const selectedAttachment = ref(props.admin.profile_photo || null);
const selectedAttachmentName = ref(props.admin.profile_photo ? props.admin.profile_photo.split('/').pop() : null);

const handleAttachment = (event) => {
    const attachmentInput = event.target;
    const file = attachmentInput.files[0];

    if (file) {
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

const submit = () => {
    form.post(route('adminRole.editAdmin'), {
        onSuccess: () => {
            emit('update:visible', false);
            form.reset();
        },
    });
};
</script>

<template>
    <form>
        <div class="flex flex-col items-center pt-4 gap-6 self-stretch md:pt-6 md:gap-8">
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
                @click="submit"
                :disabled="form.processing"
            >
                {{ $t('public.save') }}
            </Button>
        </div>
    </form>
</template>
