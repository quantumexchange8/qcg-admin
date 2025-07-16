<script setup>
import {Head, usePage} from '@inertiajs/vue3'
import Sidebar from '@/Components/Sidebar/Sidebar.vue'
import Navbar from '@/Components/Navbar.vue'
import { sidebarState } from '@/Composables'
import ToastList from "@/Components/ToastList.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";

defineProps({
    title: String
})
</script>

<template>
    <Head :title="title"></Head>

    <div class="min-h-screen bg-gray-100 flex flex-col items-center">
        <!-- Main container for Sidebar and Navbar -->
        <div class="flex flex-col px-3 md:px-5 flex-1 max-w-[1440px] w-full">
            <!-- Navbar -->
            <Navbar :title="title" />

            <div class="flex flex-1 py-3 md:py-5 gap-5">
                <!-- Sidebar -->
                <Sidebar  />

                <!-- Page Content -->
                <main class="flex flex-1 flex-col items-start justify-start relative min-w-0">
                    <div class="flex flex-col w-full flex-1">
                        <!-- Toast -->
                        <ToastList />
                        <!-- Confirmation Dialog -->
                        <ConfirmationDialog />

                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<!-- <script setup>
import {Head, usePage} from '@inertiajs/vue3'
import Sidebar from '@/Components/Sidebar/Sidebar.vue'
import Navbar from '@/Components/Navbar.vue'
import { sidebarState } from '@/Composables'
import ToastList from "@/Components/ToastList.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";

defineProps({
    title: String
})
</script>

<template>
    <Head :title="title"></Head>

    <div class="min-h-screen bg-gray-100 flex flex-col">
        <div class="w-full flex flex-col flex-1">
            <Navbar :title="title" />

            <div class="w-full flex flex-1 justify-center items-start">

                <main class="w-full max-w-[1440px] flex flex-1 justify-center items-start px-3 py-5 gap-5 md:px-5">
                    <Sidebar  />

                    <div class="w-full max-w-[1440px]">
                        <ToastList />
                        <ConfirmationDialog />

                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template> -->