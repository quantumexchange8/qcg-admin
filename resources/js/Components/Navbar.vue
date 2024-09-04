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
    {'label': '中文', 'value': 'tw'},
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
        class="sticky top-0 z-10 py-2 px-3 md:px-5 bg-white flex items-center gap-3"
    >
        <div
            class="inline-flex justify-center items-center rounded-full hover:bg-gray-100 w-12 h-12 shrink-0 grow-0 hover:select-none hover:cursor-pointer"
            @click="sidebarState.isOpen = !sidebarState.isOpen"
        >
            <IconMenu2 size="20" color="#182230" stroke-width="1.25" />
        </div>
        <div class="w-full h-full flex items-center gap-2">
            <img src="/img/logo.svg" alt="no data" class="w-7 h-7" />
            <div class="flex flex-col items-start">
                <span class="text-gray-950 text-sm font-black tracking-[4.20px]">量子資本集團</span>
                <span class="text-gray-700 text-xxxs font-medium">QUANTUM CAPITAL GROUP</span>
            </div>
        </div>
        <!-- <div
            class="text-base md:text-lg font-semibold text-gray-950 w-full"
        >
            {{ title }}
        </div> -->
        <div class="flex items-center">
            <div
                class="w-12 h-12 p-3.5 flex items-center justify-center rounded-full hover:cursor-pointer hover:bg-gray-100 text-gray-700 focus:bg-gray-100"
                @click="toggle"
            >
                <IconLanguage size="20" stroke-width="1.25" />
            </div>
            <Link
                class="w-12 h-12 p-3.5 flex items-center justify-center rounded-full outline-none hover:cursor-pointer hover:bg-gray-100 text-gray-700 focus:bg-gray-100"
                :href="route('logout')"
                method="post"
                as="button"
            >
                <IconTransferOut size="20" stroke-width="1.25" />
            </Link>
            <Link
                class="w-12 h-12 p-2 items-center justify-center rounded-full outline-none hover:cursor-pointer hover:bg-gray-100 hidden md:block focus:bg-gray-100"
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
