<script setup>
import Button from "@/Components/Button.vue";
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from "vue";
import { usePage, router, Link } from '@inertiajs/vue3';
import NewCompetition from "@/Pages/Competition/Partials/NewCompetition.vue";
import { IconPlus } from "@tabler/icons-vue";

// const openDialog = () => {
//     router.get(route('competition.newCompetition'));
// }

</script>

<template>
    <AuthenticatedLayout :title="$t('public.competition')">
        <div class="flex flex-col justify-center items-center py-5 px-3 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
            <div class="flex justify-between items-center self-stretch">
                <span class="font-semibold text-gray-950">{{ $t('public.upcoming_ongoing_competition') }}</span>
                <Link :href="route('competition.new_competition')" as="button">
                    <Button
                        type="button"
                        variant="primary-flat"
                        size="base"
                        class='w-auto'
                        as="div" >
                        <IconPlus size="20" stroke-width="1.25" />
                        {{ $t('public.new_competition') }}
                    </Button>
                </Link>
            </div>

            <div class="flex flex-col items-start content-start gap-3 self-stretch flex-wrap">

            </div>
        </div>
    </AuthenticatedLayout>
</template>
