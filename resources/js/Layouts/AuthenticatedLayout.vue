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

    <div class="min-h-screen bg-gray-100 flex flex-col">
        <!-- Main container for Sidebar and Navbar -->
        <div class="flex flex-col flex-1">
            <!-- Navbar -->
            <Navbar :title="title" />

            <div class="flex flex-1 px-3 py-5 gap-5 md:px-5">
                <!-- Sidebar -->
                <Sidebar  />

                <!-- Page Content -->
                <main class="flex flex-1 justify-center items-start">
                    <div class="w-full max-w-[1440px]">
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