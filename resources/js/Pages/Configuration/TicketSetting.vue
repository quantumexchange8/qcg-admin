<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TicketSchedule from "./Partials/TicketSchedule.vue";
import TicketCategory from "./Partials/TicketCategory.vue";
import AgentAccess from "./Partials/AgentAccess.vue";

</script>

<template>
    <AuthenticatedLayout :title="`${$t('public.sidebar_configuration')}&nbsp;-&nbsp;${$t('public.sidebar_ticket_setting')}`">
        <div class="flex flex-col justify-center items-center gap-5">
            <TicketSchedule />

            <TicketCategory />

            <AgentAccess />
        </div>
    </AuthenticatedLayout>
</template>