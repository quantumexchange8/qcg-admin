<script setup>
import { usePage } from "@inertiajs/vue3";
import { ref, watch, watchEffect } from "vue";
import Loader from "@/Components/Loader.vue";
import Dialog from "primevue/dialog";
import { FilterMatchMode } from '@primevue/core/api';
import Empty from "@/Components/Empty.vue";
import dayjs from "dayjs";
import Skeleton from 'primevue/skeleton';

const props = defineProps({
    ticket_id: Number,
    refreshKey: Number,
});

const loading = ref(false);
const ticket = ref();

const getReplies = async () => {
    loading.value = true;
    try {
        let url = `/tickets/getTicketReplies?ticket_id=${props.ticket_id}`;

        const response = await axios.get(url);
        ticket.value = response.data.ticket;

        // console.log(ticket)
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

getReplies();

watch(() => props.refreshKey, () => {
    getReplies();
})

const visiblePhoto = ref(false);
const selectedAttachment = ref(null);
const openPhotoDialog = (attachment) => {
    visiblePhoto.value = true;
    selectedAttachment.value = attachment;
}
</script>

<template>

    <div v-if="loading" class="flex flex-col items-center gap-2 self-stretch">
        <div class="flex flex-row justify-between items-center self-stretch">
            <Skeleton width="5rem" height="0.6rem" class="my-1" borderRadius="2rem"></Skeleton>
            <Skeleton width="5rem" height="0.6rem" class="my-1" borderRadius="2rem"></Skeleton>
        </div>
        <div class="flex flex-col p-2 justify-center items-start gap-2 self-stretch rounded bg-gray-100">
            <Skeleton width="9rem" height="0.6rem" borderRadius="2rem"></Skeleton>
            <Skeleton width="9rem" height="0.6rem" borderRadius="2rem"></Skeleton>
        </div>
    </div>

    <div v-else v-for="reply in ticket.replies" class="flex flex-col items-center gap-2 self-stretch">
        <div class="flex flex-row justify-between items-center self-stretch">
            <span class="text-xs font-semibold text-gray-950">{{ reply.name }}</span>
            <span class="text-xs text-gray-500">{{ dayjs(reply.sent_at).format('YYYY/MM/DD HH:mm') }}</span>
        </div>
        <div class="flex flex-col p-2 justify-center items-center gap-2 self-stretch rounded "
            :class="{'bg-primary-100': reply.user_id === ticket.user_id, 'bg-gray-100': reply.user_id !== ticket.user_id}"
        >
            <span class="text-sm text-gray-950 self-stretch whitespace-pre-line">{{ reply.message }}</span>
            <div v-if="reply.reply_attachments" class="grid grid-cols-2 md:grid-cols-3 gap-2 self-stretch">
                <div v-for="file in reply.reply_attachments" :key="file.id" @click="openPhotoDialog(file)" 
                    class="flex items-center gap-3 w-full p-2 bg-white rounded border border-gray-200 cursor-pointer hover:bg-gray-100"
                >
                    <img :src="file.original_url" :alt="file.file_name" class="w-16 h-12 rounded" />
                    <span class="text-sm text-gray-700 truncate">{{ file.file_name }}</span>
                </div>
            </div>
        </div>
    </div>

    <Dialog v-model:visible="visiblePhoto" modal headless class="dialog-xs md:dialog-md" :dismissableMask="true">
        <img
            :src="selectedAttachment?.original_url"
            class="w-full"
            alt="attachment"
        />
    </Dialog>
</template>