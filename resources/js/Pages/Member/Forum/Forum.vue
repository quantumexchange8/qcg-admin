<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { IconSearch, IconCircleXFilled } from "@tabler/icons-vue";
import { computed, h, ref, watch } from "vue";
import { useConfirm } from "primevue/useconfirm";
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import { trans, wTrans } from "laravel-vue-i18n";
import CreatePost from '@/Pages/Member/Forum/Partials/CreatePost.vue';
import ManagePermission from '@/Pages/Member/Forum/Partials/ManagePermission.vue';
import ForumPost from '@/Pages/Member/Forum/Partials/ForumPost.vue';

const props = defineProps({
    postCounts: Number,
})

const search = ref('');
const checked = ref()

const clearSearch = () => {
    search.value = '';
}

// watch(() => props.member.status, (newStatus) => {
//     checked.value = newStatus === 'active';
// });

// const confirm = useConfirm();

// const requireConfirmation = (action_type) => {
//     const messages = {
//         activate_member: {
//             group: 'headless',
//             color: 'primary',
//             icon: h(IconUserCheck),
//             header: trans('public.activate_member'),
//             message: trans('public.activate_member_caption'),
//             cancelButton: trans('public.cancel'),
//             acceptButton: trans('public.confirm'),
//             action: () => {
//                 router.visit(route('member.updateMemberStatus', props.member.id), {
//                     method: 'post',
//                     data: {
//                         id: props.member.id,
//                     },
//                 })

//                 checked.value = !checked.value;
//             }
//         },
//         deactivate_member: {
//             group: 'headless',
//             color: 'error',
//             icon: h(IconUserCancel),
//             header: trans('public.deactivate_member'),
//             message: trans('public.deactivate_member_caption'),
//             cancelButton: trans('public.cancel'),
//             acceptButton: trans('public.confirm'),
//             action: () => {
//                 router.visit(route('member.updateMemberStatus', props.member.id), {
//                     method: 'post',
//                     data: {
//                         id: props.member.id,
//                     },
//                 })

//                 checked.value = !checked.value;
//             }
//         },
//         delete_member: {
//             group: 'headless',
//             color: 'error',
//             icon: h(IconTrashX),
//             header: trans('public.delete_member'),
//             message: trans('public.delete_member_desc'),
//             cancelButton: trans('public.cancel'),
//             acceptButton: trans('public.delete'),
//             action: () => {
//                 router.visit(route('member.deleteMember'), {
//                     method: 'delete',
//                     data: {
//                         id: props.member.id,
//                     },
//                 })
//             }
//         },
//     };

//     const { group, color, icon, header, message, cancelButton, acceptButton, action } = messages[action_type];

//     confirm.require({
//         group,
//         color,
//         icon,
//         header,
//         message,
//         cancelButton,
//         acceptButton,
//         accept: action
//     });
// };

const handleMemberStatus = () => {
    if (props.member.status === 'active') {
        requireConfirmation('deactivate_member')
    } else {
        requireConfirmation('activate_member')
    }
}

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_forum')">
        <div class="w-full flex flex-col items-center gap-5 md:flex-row md:justify-center md:items-start">
            <div class="w-full md:h-[90vh] md:max-w-80 flex flex-col items-center gap-5 self-stretch">
                <div class="flex flex-col items-start px-3 py-5 gap-5 self-stretch rounded-lg bg-white md:p-6">
                    <span class="text-gray-500 text-sm">{{ $t('public.ready_share_something') }}</span>
                    <div class="flex flex-col items-center gap-3 self-stretch">
                        <CreatePost />
                        <!-- <ManagePermission /> -->
                    </div>
                </div>

                <!-- <div class="w-full h-[90vh] hidden md:flex flex-col items-center px-6 pt-6 gap-3 self-stretch rounded-lg bg-white shadow-card">
                    <div class="flex flex-col items-center gap-5 self-stretch bg-white">
                        <span class="self-stretch text-gray-950 text-sm font-bold">{{ $t('public.manage_posting_permissions') }}</span>
                        <div class="relative w-full">
                            <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-500">
                                <IconSearch size="20" stroke-width="1.25" />
                            </div>
                            <InputText v-model="search" :placeholder="$t('public.keyword_search')" size="search" class="font-normal w-full" />
                            <div
                                v-if="search"
                                class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                @click="clearSearch"
                            >
                                <IconCircleXFilled size="16" />
                            </div>
                        </div>
                    </div>
                    <div class="w-full h-[65vh] flex flex-col items-center self-stretch overflow-y-auto">
                        <div class="flex py-3 items-center self-stretch">
                            <span class="self-stretch text-gray-500 text-xs font-medium uppercase">{{ $t('public.selected') }}</span>
                        </div>
                        <div class="flex items-center py-2 gap-3 self-stretch border-b border-gray-50">
                            <div class="w-full flex flex-col items-start">
                                <span class="truncate self-stretch text-gray-950 text-sm font-semibold">{{ 'name' }}</span>
                                <span class="truncate self-stretch text-gray-500 text-xs">{{ 'email' }}</span>
                            </div>
                            <ToggleSwitch
                                v-model="checked"
                                readonly
                                @click="handleMemberStatus"
                            />
                        </div>
                    </div>
                </div> -->
            </div>
            <ForumPost :postCounts="props.postCounts" />
        </div>
    </AuthenticatedLayout>
</template>
