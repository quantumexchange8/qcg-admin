<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { IconUserCancel } from '@tabler/icons-vue';
import { h, ref, watch } from "vue";
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import InputError from '@/Components/InputError.vue';
import Checkbox from 'primevue/checkbox';
import Button from '@/Components/Button.vue';
import ConfirmDialog from 'primevue/confirmdialog';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const visible = ref(false);

const openConfirmDialog = () => {
    visible.value = true;
};

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onError: (response) => {
            // Check if the user is inactive
            if (response.inactive) {
                openConfirmDialog();
            }
        },
        onFinish: () => {
            form.reset('password');
        }
    });
};

</script>

<template>
    <GuestLayout :title="$t('public.log_in')">
        <div class="w-full flex flex-col justify-center items-center gap-8 self-stretch">
            <div class="flex flex-col items-center gap-6 self-stretch">
                <ApplicationLogo class="w-14 h-14 md:w-16 md:h-16" />
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <span class="self-stretch text-gray-950 text-center text-lg font-bold md:text-xl">{{ $t('public.login_header') }}</span>
                    <span class="self-stretch text-gray-500 text-center text-sm md:text-base">{{ $t('public.login_header_caption') }}</span>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col items-center gap-6 self-stretch rounded-xl">
                <div class="flex flex-col items-start gap-5 self-stretch">
                    <div class="flex flex-col items-start gap-2 self-stretch">
                        <InputLabel for="email" :invalid="!!form.errors.email">{{ $t('public.email') }}</InputLabel>

                        <InputText
                            id="email"
                            type="email"
                            class="block w-full"
                            v-model="form.email"
                            autofocus
                            autocomplete="username"
                            :placeholder="$t('public.enter_email')"
                            :invalid="!!form.errors.email"
                        />

                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="flex flex-col items-start gap-2 self-stretch">
                        <InputLabel for="password" :invalid="!!form.errors.password">{{ $t('public.password') }}</InputLabel>

                        <Password
                            id="password"
                            class="block w-full"
                            v-model="form.password"
                            toggleMask
                            :feedback="false"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            :invalid="!!form.errors.password"
                        />

                        <InputError :message="form.errors.password" />
                    </div>
                </div>

                <div class="flex justify-between items-center self-stretch">
                    <label class="flex items-center gap-2">
                        <Checkbox v-model="form.remember" binary class="w-6 h-6" />
                        <span class="text-sm text-gray-700 font-medium">{{ $t('public.remember_me') }}</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm text-primary-500 hover:text-primary-600 focus:underline focus:text-primary-600"
                    >
                        {{ $t('public.forgot_password') }}
                    </Link>
                </div>

                <Button
                    variant="primary-flat"
                    size="base"
                    class="w-full"
                    :disabled="form.processing"
                >
                    {{ $t('public.log_in') }}
                </Button>
                    
            </form>
        </div>
    </GuestLayout>

    <ConfirmDialog
        group="headless-primary"
        v-model:visible="visible"
    >
        <template #container>
            <div class="flex flex-col justify-center items-center px-4 pt-[60px] pb-6 gap-8 bg-white rounded shadow-dialog w-[90vw] md:w-[500px] md:px-6">
                <div class="flex flex-col items-center gap-2 self-stretch">
                    <span class="self-stretch text-gray-950 text-center font-bold md:text-lg">{{ $t('public.inactive_account_header') }}</span>
                    <span class="self-stretch text-gray-700 text-center text-sm">{{ $t('public.inactive_account_message') }}</span>
                </div>
                <div class="grid grid-cols-1 justify-items-end items-center gap-4 self-stretch">
                    <Button
                        type="button"
                        variant="primary-flat"
                        @click="visible = false"
                        class="w-full"
                        size="base"
                    >
                        {{ $t('public.alright') }}
                    </Button>
                </div>
                <div class="flex w-[84px] h-[84px] p-6 justify-center items-center absolute -top-[42px] rounded-full grow-0 shrink-0 bg-primary-600">
                    <IconUserCancel size="36" stroke-width="1.25" color="#FFFFFF" />
                </div>
            </div>
        </template>
    </ConfirmDialog>

</template>
