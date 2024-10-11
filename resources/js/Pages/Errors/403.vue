<script setup>
import { router } from "@inertiajs/vue3";
import Button from '@/Components/Button.vue'
import ErrorLayout from '@/Layouts/ErrorLayout.vue';

const logout = () => {
    router.post(route('logout'))
};
</script>

<template>
    <ErrorLayout :title="$t('public.forbidden')">
        <div class="w-full flex-grow flex flex-col justify-start items-center self-stretch md:justify-center">
            <img src="/img/errors/illustration-403.svg" class="min-w-80 min-h-60 w-80 h-60 md:w-[360px] md:h-[270px]" alt="Error Illustration">
            <div class="flex flex-col justify-center items-center gap-8 self-stretch">
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <span class="w-full text-gray-950 text-center text-lg font-bold">
                        {{ $t('public.forbidden_title') }}
                    </span>
                    <span class="w-full text-gray-500 text-center text-sm">
                        {{ $t('public.forbidden_desc') }}
                    </span>
                </div>
                <div class="flex flex-col justify-center items-center gap-3 self-stretch md:flex-row md:gap-5">
                    <Button
                        type="button"
                        variant="primary-flat"
                        class="w-full md:w-auto"
                        :href="route('dashboard')"
                    >
                        {{ $t('public.back_to_dashboard') }}
                    </Button>
                    <Button
                        type="button"
                        variant="primary-outlined"
                        class="w-full md:w-auto"
                        @click="logout"
                        >
                        {{ $t('public.log_out') }}
                    </Button>
                </div>
            </div>
        </div>
    </ErrorLayout>
</template>
