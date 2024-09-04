<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthenticatedLayout :title="$t('public.dashboard')">
        <div class="flex flex-col gap-5">
            <div class="h-[120px] self-stretch rounded-lg bg-white shadow-card md:h-40">
                <div class="w-[185px] flex flex-col items-start gap-1 md:w-[300px] lg:w-[304px] xl:w-[560px]">
                    <span class="self-stretch text-primary-700 font-bold md:text-center md:text-xl"></span>
                    <span class="self-stretch text-primary-950 text-xs md:text-center md:text-sm"></span>
                </div>
            </div>
        </div>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">You're logged in!</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
