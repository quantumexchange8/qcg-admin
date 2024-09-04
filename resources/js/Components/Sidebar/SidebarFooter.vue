<script setup>
import Button from '@/Components/Button.vue'
import { sidebarState } from '@/Composables'
import { Link } from "@inertiajs/vue3";
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import {
    IconTransferOut,
} from '@tabler/icons-vue';
</script>

<template>
    <Link
        class="w-full flex p-3 gap-3 items-center rounded hover:cursor-pointer hover:bg-gray-100 focus:outline-none focus:bg-gray-100"
        :href="route('logout')"
        method="post"
        as="button"
    >
        <IconTransferOut :size="20" stroke-width="1.25" color="#42A547" />
        <span class="text-start text-gray-700 text-sm font-medium">{{ $t('public.log_out') }}</span>
    </Link>
</template>
