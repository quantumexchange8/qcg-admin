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
import { IconPlus, IconUpload } from "@tabler/icons-vue";

const props = defineProps({
    countries: Array,
    uplines: Array,
});

const visible = ref(false)

const closeDialog = () => {
    visible.value = false;
}

const form = useForm({
    name: '',
    email: '',
    dial_code: '',
    phone: '',
    phone_number: '',
    upline: '',
    kyc_verification: '',
    password: '',
    password_confirmation: '',
});

const countries = ref(props.countries)
const uplines = ref(props.uplines)
const selectedCountry = ref();

const submitForm = () => {
    form.dial_code = selectedCountry.value;

    if (selectedCountry.value) {
        form.phone_number = selectedCountry.value.phone_code + form.phone;
    }

    form.post(route('member.addNewMember'), {
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
        {{ $t('public.create_member') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.create_member')"
        class="dialog-xs md:dialog-md"
    >
        <form @submit.prevent="submitForm()">
            <div class="flex flex-col items-center py-4 gap-6 self-stretch md:py-6 md:gap-8">

                <!-- Basic Information -->
                <div class="flex flex-col gap-2 items-center self-stretch md:gap-3">
                    <div class="text-gray-950 font-bold text-sm self-stretch">
                        {{ $t('public.basic_information') }}
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                        <div class="space-y-2">
                            <InputLabel for="name" :value="$t('public.full_name')" :invalid="!!form.errors.name" />
                            <InputText
                                id="name"
                                type="text"
                                class="block w-full"
                                v-model="form.name"
                                :placeholder="$t('public.full_name_placeholder')"
                                :invalid="!!form.errors.name"
                                autofocus
                            />
                            <InputError :message="form.errors.name" />
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
                            <InputLabel for="phone" :value="$t('public.phone_number')" :invalid="!!form.errors.phone" />
                            <div class="flex gap-2 items-center self-stretch relative">
                                <Select
                                    v-model="selectedCountry"
                                    :options="countries"
                                    filter
                                    :filterFields="['name', 'phone_code']"
                                    optionLabel="name"
                                    :placeholder="$t('public.phone_code')"
                                    class="w-[100px]"
                                    scroll-height="236px"
                                    :invalid="!!form.errors.dial_code"
                                >
                                    <template #value="slotProps">
                                        <div v-if="slotProps.value" class="flex items-center">
                                            <div>{{ slotProps.value.phone_code }}</div>
                                        </div>
                                        <span v-else>
                                            {{ slotProps.placeholder }}
                                        </span>
                                    </template>
                                    <template #option="slotProps">
                                        <div class="flex items-center w-[262px] md:max-w-[236px]">
                                            <div>{{ slotProps.option.name }} <span class="text-gray-500">{{ slotProps.option.phone_code }}</span></div>
                                        </div>
                                    </template>
                                </Select>

                                <InputText
                                    id="phone"
                                    type="text"
                                    class="block w-full"
                                    v-model="form.phone"
                                    :placeholder="$t('public.phone_number')"
                                    :invalid="!!form.errors.phone"
                                />
                            </div>
                            <InputError :message="form.errors.phone" />
                        </div>
                        <div class="space-y-2">
                            <InputLabel for="email" :value="$t('public.upline')" :invalid="!!form.errors.upline" />
                            <Select
                                v-model="form.upline"
                                :options="uplines"
                                filter
                                :filterFields="['name', 'phone_code']"
                                optionLabel="name"
                                :placeholder="$t('public.upline_placeholder')"
                                class="w-full"
                                scroll-height="236px"
                                :invalid="!!form.errors.upline"
                            >
                                <!-- <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full overflow-hidden">
                                                <template v-if="slotProps.value.profile_photo">
                                                    <img :src="slotProps.value.profile_photo" alt="profile_picture" />
                                                </template>
                                                <template v-else>
                                                    <DefaultProfilePhoto />
                                                </template>
                                            </div>
                                            <div>{{ slotProps.value.name }}</div>
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">
                                            {{ slotProps.placeholder }}
                                    </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-full overflow-hidden">
                                            <template v-if="slotProps.option.profile_photo">
                                                <img :src="slotProps.option.profile_photo" alt="profile_picture" />
                                            </template>
                                            <template v-else>
                                                <DefaultProfilePhoto />
                                            </template>
                                        </div>
                                        <div>{{ slotProps.option.name }}</div>
                                    </div>
                                </template> -->
                            </Select>
                            <InputError :message="form.errors.upline" />
                        </div>
                    </div>
                </div>

                <!-- Create Password -->
                <div class="flex flex-col gap-2 items-center self-stretch md:gap-3">
                    <div class="text-gray-950 font-bold text-sm self-stretch">
                        {{ $t('public.create_password') }}
                    </div>
                    <div class="w-full space-y-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                            <div class="space-y-2">
                                <InputLabel for="password" :value="$t('public.password')" :invalid="!!form.errors.password" />
                                <Password
                                    v-model="form.password"
                                    toggleMask
                                    :feedback="false"
                                    placeholder="••••••••"
                                    :invalid="!!form.errors.password"
                                />
                                <InputError :message="form.errors.password" />
                                <span class="self-stretch text-gray-500 text-xs md:hidden">{{ $t('public.password_rule') }}</span>
                            </div>
                            <div class="space-y-2">
                                <InputLabel for="password_confirmation" :value="$t('public.confirm_password')" :invalid="!!form.password_confirmation.password" />
                                <Password
                                    v-model="form.password_confirmation"
                                    toggleMask
                                    :feedback="false"
                                    placeholder="••••••••"
                                    :invalid="!!form.errors.password_confirmation"
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                        </div>
                        <span class="hidden md:block self-stretch text-gray-500 text-xs">{{ $t('public.password_rule') }}</span>
                    </div>
                </div>

                <!-- Kyc Verification -->
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <div class="text-gray-950 font-bold text-sm self-stretch">
                        {{ $t('public.kyc_verification') }}
                    </div>
                    <div class="flex flex-col gap-3 items-start self-stretch">
                        <span class="text-xs text-gray-500">{{ $t('public.file_size_limit') }}</span>
                        <FileUpload
                            class="w-full"
                            name="kyc_verification"
                            url="/member/uploadKyc"
                            accept="image/*"
                            :maxFileSize="10485760"
                            auto
                        >
                            <template #header="{ chooseCallback }">
                                <div class="flex flex-wrap justify-between items-center flex-1 gap-2">
                                    <div class="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="primary-flat"
                                            @click="chooseCallback()"
                                        >
                                            <IconUpload size="20" stroke-width="1.25" />
                                            {{ $t('public.choose') }}
                                        </Button>
                                    </div>
                                </div>
                            </template>
                        </FileUpload>
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
                    @click="submitForm"
                    :disabled="form.processing"
                >
                    {{ $t('public.create') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
