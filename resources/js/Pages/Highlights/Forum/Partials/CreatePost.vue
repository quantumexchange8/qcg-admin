<script setup>
import Button from "@/Components/Button.vue"
import { IconEdit, IconUpload, IconX } from "@tabler/icons-vue";
import { ref, watch } from "vue";
import Dialog from "primevue/dialog";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from 'primevue/inputtext';
import Textarea from "primevue/textarea";
import { useForm } from "@inertiajs/vue3";
import TipTapEditor from "@/Components/TipTapEditor.vue";

const visible = ref(false);

const form = useForm({
    display_name: '',
    subject: '',
    message: '',
    attachment: ''
})

const openDialog = () => {
    form.reset();
    removeAttachments();
    visible.value = true;
}

const selectedAttachments = ref([]);
const handleAttachment = (event) => {
    const files = Array.from(event.target.files);

    if (files.length > 10) {
        alert("You can only upload up to 10 files.");
        return;
    }

    // selectedAttachments.value = [];
    // form.attachment = [];

    files.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            selectedAttachments.value.push({
                name: file.name,
                url: e.target.result,
                file: file,
            });
        };
        reader.readAsDataURL(file);
        form.attachment.push(file);
    });
};

const removeAttachment = (index) => {
    selectedAttachments.value.splice(index, 1);
    form.attachment.splice(index, 1);
};

const removeAttachments = () => {
    selectedAttachments.value = [];
    form.attachment = [];
};

// const selectedAttachmentName = ref(null);
// const handleAttachment = (event) => {
//     const attachmentInput = event.target;
//     const file = attachmentInput.files[0];

//     if (file) {
//         // Display the selected image
//         const reader = new FileReader();
//         reader.onload = () => {
//             selectedAttachment.value = reader.result;
//         };
//         reader.readAsDataURL(file);
//         selectedAttachmentName.value = file.name;
//         form.attachment = event.target.files[0];
//     } else {
//         selectedAttachment.value = null;
//     }
// };

// const removeAttachment = () => {
//     selectedAttachment.value = null;
//     form.attachment = '';
// };

const submitForm = () => {
    form.post(route('highlights.createPost'), {
        onSuccess: () => {
            visible.value = false;
            form.reset();
            removeAttachments();
        }
    })
}

</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        class='w-full'
        @click="openDialog()"
    >
        <IconEdit size="20" color="#ffffff" stroke-width="1.25" />
        {{ $t('public.create_post') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.create_post')"
        class="dialog-xs md:dialog-md"
        :dismissableMask="true"
    >
        <form>
            <div class="flex flex-col items-center py-4 gap-6 self-stretch md:py-6 md:gap-8 bg-white">
                <div class="flex flex-col gap-5 items-center self-stretch">
                    <div class="flex flex-col items-start gap-2 self-stretch">
                        <InputLabel
                            for="display_name"
                            :value="$t('public.display_name')"
                            :invalid="!!form.errors.display_name"
                        />
                        <InputText
                            id="display_name"
                            type="text"
                            class="block w-full"
                            v-model="form.display_name"
                            :placeholder="$t('public.display_name_placeholder')"
                            :invalid="!!form.errors.display_name"
                        />
                        <InputError :message="form.errors.display_name" />
                        <span class="self-stretch text-gray-500 text-xs">{{ $t('public.display_name_caption') }}</span>
                    </div>

                    <div class="flex flex-col items-start gap-2 self-stretch">
                        <InputLabel for="subject" :invalid="!!form.errors.subject">{{ $t('public.subject') }}</InputLabel>
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

                    <div class="h-[120px] flex flex-col items-start gap-2 self-stretch md:col-span-2">
                        <InputLabel for="message" :invalid="!!form.errors.message">{{ $t('public.message') }}</InputLabel>
                        <TipTapEditor
                            v-model="form.message"
                        />
                        <InputError :message="form.errors.message" />
                    </div>
                </div>
                
                <div class="flex flex-col items-center gap-2 self-stretch md:gap-3">
                    <span class="self-stretch text-gray-950 text-sm font-bold">{{ $t('public.attachment') }}</span>
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
                                multiple
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
                        <!-- <div
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
                        </div> -->

                        <div v-if="selectedAttachments.length" class="grid grid-cols-5 gap-1 w-full">
                            <div 
                                v-for="(file, index) in selectedAttachments"
                                :key="index"
                                class="relative py-0.5 pl-0.5 flex justify-between rounded bg-gray-50"
                            >
                                <div class="inline-flex items-center gap-3">
                                    <img :src="file.url" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                    <!-- <div class="text-sm text-gray-950">
                                        {{ file.name }}
                                    </div> -->
                                </div>
                                <Button
                                    type="button"
                                    variant="gray-text"
                                    @click="removeAttachment(index)"
                                    pill
                                    iconOnly
                                    size="sm"
                                >
                                    <IconX class="text-gray-700 w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
                <Button
                    type="button"
                    size="base"
                    class="w-full"
                    variant="gray-outlined"
                    @click="visible = false"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary-flat"
                    size="base"
                    class="w-full"
                    @click="submitForm"
                    :disabled="form.processing"
                >
                    {{ $t('public.create') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
