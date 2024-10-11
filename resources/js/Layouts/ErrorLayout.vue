<script setup>
import { IconLanguage } from '@tabler/icons-vue';
import { Head, usePage } from '@inertiajs/vue3'
import { loadLanguageAsync } from "laravel-vue-i18n";
import { ref } from "vue";
import TieredMenu from "primevue/tieredmenu";
import dayjs from "dayjs";
import axios from 'axios';
import Button from "@/Components/Button.vue";

defineProps({
    title: String,
})

const menu = ref(null);
const toggle = (event) => {
    menu.value.toggle(event);
};

const currentLocale = ref(usePage().props.locale);
const locales = [
    { label: 'English', value: 'en' },
    { label: '中文(繁体)', value: 'tw' },
    { label: '中文(简体)', value: 'cn' },
];

// Function to change the language
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
    <Head :title="title"></Head>

    <div class="min-h-screen flex flex-col justify-center items-start bg-white">
        <!-- Header with button and menu -->
        <div class="w-full flex h-[66px] py-2 px-5 justify-end items-center gap-3 flex-shrink-0">
            <Button
                type="button"
                variant="gray-text"
                size="sm"
                icon-only
                pill
                @click="toggle"
                aria-haspopup="true"
                aria-controls="overlay_tmenu"
            >
                <IconLanguage size="20" stroke-width="1.25" color="#374151" />
            </Button>
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
        </div>

        <div class="w-full flex-grow flex flex-col justify-between items-center px-3 pb-8 md:justify-center md:gap-[60px] md:px-24 md:py-12">
            <div class="w-full flex-grow flex flex-col justify-center items-center">
                <slot />
            </div>
            <div class="text-center text-gray-500 text-xs">© {{ dayjs().year() }} Quantum Capital Group. All rights reserved.</div>
        </div>
    </div>
</template>
