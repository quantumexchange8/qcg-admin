<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import { Head, useForm } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import { ref } from 'vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const isResetSuccessful = ref(false);

const submit = async () => {
    form.post(route('password.store'), {
        onSuccess: () => {
            isResetSuccessful.value = true;
            form.reset('password', 'password_confirmation');
        },
        onError: (error) => {
            console.error('Password reset failed:', error);
        },
    });
};

const goToLoginPage = () => {
    window.location.href = '/login'; // Redirect to the login page URL
};

</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <div v-if="!isResetSuccessful" class="w-full flex flex-col justify-center items-center gap-8 pt-8 md:pt-0">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="text-gray-950 text-center text-lg md:text-xl font-semibold self-stretch">{{ $t('public.choose_password') }}</div>
                <div class="text-gray-500 text-center self-stretch text-sm md:text-base">{{ $t('public.choose_password_caption') }}</div>
            </div>
            <div class="flex flex-col items-center gap-6 self-stretch">
                <form @submit.prevent="submit" class="flex flex-col items-start gap-5 self-stretch">
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel for="password" :value="$t('public.password')" :invalid="!!form.errors.password" />

                        <Password
                            id="password"
                            class="block w-full"
                            v-model="form.password"
                            toggleMask
                            :feedback="false"
                            :invalid="!!form.errors.password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                        />

                        <InputError :message="form.errors.password" />
                        
                        <span class="text-gray-500 text-xs self-stretch">{{ $t('public.password_rule') }}</span>
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel for="password_confirmation" :value="$t('public.confirm_password')" :invalid="!!form.errors.password_confirmation" />

                        <Password
                            id="password_confirmation"
                            class="block w-full"
                            v-model="form.password_confirmation"
                            toggleMask
                            :feedback="false"
                            :invalid="!!form.errors.password_confirmation"
                            placeholder="••••••••"
                            autocomplete="new-password"
                        />

                        <InputError :message="form.errors.password_confirmation" />
                    </div>
                </form>
                <div class="flex flex-col items-center gap-4 self-stretch">
                    <Button size="base" variant="primary-flat" class="w-full" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click.prevent="submit">{{ $t('public.reset_password') }}</Button>
                    <Button size="base" variant="gray-text" class="w-full" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click.prevent="goToLoginPage">{{ $t('public.back_to_login') }}</Button>
                </div>
            </div>
        </div>

        <div v-else class="w-full flex flex-col justify-center items-center">
            <div class="flex flex-col items-center justify-center">
                <img src="/img/reset-password.svg" alt="no data" class="w-80 h-60">
            </div>
            <div class="flex flex-col justify-center items-center gap-8 self-stretch">
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div class="text-gray-950 text-center text-lg md:text-xl font-bold self-stretch">{{ $t('public.password_reset') }}</div>
                    <div class="text-gray-500 text-center self-stretch text-sm md:text-base">{{ $t('public.password_reset_caption') }}</div>
                </div>
                <Button size="base" variant="primary-flat" class="w-full" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click.prevent="goToLoginPage">{{ $t('public.back_to_login') }}</Button>
            </div>
        </div>
    </GuestLayout>
</template>
