<script setup>
import { sidebarState } from '@/Composables'
import {
    IconLanguage,
    IconTransferOut,
    IconMenu2
} from '@tabler/icons-vue';
import ProfilePhoto from "@/Components/ProfilePhoto.vue";
import {Link, usePage} from "@inertiajs/vue3";
import TieredMenu from "primevue/tieredmenu";
import {ref} from "vue";
import {loadLanguageAsync} from "laravel-vue-i18n";

defineProps({
    title: String
})

const menu = ref(null);
const toggle = (event) => {
    menu.value.toggle(event);
};

const currentLocale = ref(usePage().props.locale);
const locales = [
    {'label': 'English', 'value': 'en'},
    {'label': '中文(繁体)', 'value': 'tw'},
    {'label': '中文(简体)', 'value': 'cn'},
];

const changeLanguage = async (langVal) => {
    try {
        currentLocale.value = langVal;
        await loadLanguageAsync(langVal);
        await axios.get(`/locale/${langVal}`);
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};
</script>

<template>
    <nav
        aria-label="secondary"
        class="sticky top-0 z-10 py-3.5 md:py-2 px-2 md:px-5 bg-white flex items-center gap-2 md:gap-3 justify-between rounded-b-lg opacity-80 shadow-box"
    >
        <div
            class="inline-flex justify-center items-center rounded-full border border-gray-200 hover:bg-gray-100 w-9 h-9 md:w-12 md:h-12 shrink-0 grow-0 hover:select-none hover:cursor-pointer"
            @click="sidebarState.isOpen = !sidebarState.isOpen"
        >
            <IconMenu2 size="20" color="#374151" stroke-width="1.25" />
        </div>
        <!-- <div class="w-full h-full flex items-center">
            <Link class="h-full flex items-center gap-2"
                :href="route('dashboard')"
            >
                <img src="/img/logo.svg" alt="no data" class="w-7 h-7" />
                <div class="hidden md:flex flex-col items-start">
                    <span class="text-gray-950 text-sm font-bold">Quantum</span>
                    <span class="text-gray-700 text-xxxs font-medium tracking-[1.04px]">Capital Group</span>
                </div>
            </Link>
        </div> -->
        <!-- <div
            class="text-base md:text-lg font-semibold text-gray-950 w-full"
        >
            {{ title }}
        </div> -->
        <div class="flex gap-2 items-center">
            <div
                class="w-9 h-9 md:w-12 md:h-12 p-2 md:p-3.5 flex items-center justify-center rounded-full border border-gray-200 hover:cursor-pointer hover:bg-gray-100 text-gray-700 focus:bg-gray-100"
                @click="toggle"
            >
                <IconLanguage size="20" stroke-width="1.25" />
            </div>
            <Link
                class="w-9 h-9 md:w-12 md:h-12 p-2 md:p-3.5 hidden md:flex items-center justify-center rounded-full border border-gray-200 outline-none hover:cursor-pointer hover:bg-gray-100 text-gray-700 focus:bg-gray-100"
                :href="route('logout')"
                method="post"
                as="button"
            >
                <IconTransferOut size="20" stroke-width="1.25" />
            </Link>
            <Link
                class="w-9 h-9 md:w-12 md:h-12 p-1.5 md:p-2 items-center justify-center rounded-full outline-none border border-gray-200 hover:cursor-pointer hover:bg-gray-100 focus:bg-gray-100"
                :href="route('profile')"
            >
                <ProfilePhoto class="w-8 h-8" />
            </Link>
        </div>
    </nav>

    <TieredMenu ref="menu" id="overlay_tmenu" :model="locales" popup>
        <template #item="{ item, props }">
            <div
                class="flex items-center gap-3 self-stretch text-gray-700"
                :class="{'bg-primary-100 text-primary-600': item.value === currentLocale}"
                v-bind="props.action"
                @click="changeLanguage(item.value)"
            >
                {{ item.label }}
            </div>
        </template>
    </TieredMenu>
</template>
