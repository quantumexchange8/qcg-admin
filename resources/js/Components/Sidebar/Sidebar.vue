<script setup>
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { sidebarState } from '@/Composables'
import SidebarHeader from '@/Components/Sidebar/SidebarHeader.vue'
import SidebarContent from '@/Components/Sidebar/SidebarContent.vue'
import SidebarFooter from '@/Components/Sidebar/SidebarFooter.vue'

onMounted(() => {
    window.addEventListener('resize', sidebarState.handleWindowResize)

    router.on('navigate', () => {
        if (window.innerWidth <= 1024) {
            sidebarState.isOpen = false
        }
    })
})
</script>

<template>
    <transition
        enter-active-class="transition"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-show="sidebarState.isOpen"
            @click="sidebarState.isOpen = false"
            class="fixed inset-0 z-20 bg-black/50 lg:hidden"
        ></div>
    </transition>

    <aside
        style="
            transition-property: width, transform;
            transition-duration: 150ms;
        "
        :class="[
            'fixed h-full top-0 lg:sticky left-0 z-20 bg-gray-100 flex flex-col lg:top-20  lg:overflow-y-auto lg:h-[calc(90vh)]',
            {
                'translate-x-0 w-[232px]':
                    sidebarState.isOpen || sidebarState.isHovered,
                '-translate-x-full lg:hidden':
                    !sidebarState.isOpen && !sidebarState.isHovered,
            },
        ]"
        @mouseenter="sidebarState.handleHover(true)"
        @mouseleave="sidebarState.handleHover(false)"
    >
        <div class="w-full h-full flex flex-col flex-1 px-5 py-8 gap-8 bg-white items-center">
                    <!-- <SidebarHeader /> -->

                    <SidebarContent />

                    <SidebarFooter />

        </div>
    </aside>
</template>
