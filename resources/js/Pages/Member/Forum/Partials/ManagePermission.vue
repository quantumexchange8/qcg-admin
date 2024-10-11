<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { IconSearch, IconCircleXFilled } from "@tabler/icons-vue";
import { computed, h, ref, watch, onMounted, onUnmounted } from "vue";
import { useConfirm } from "primevue/useconfirm";
import Button from "@/Components/Button.vue";
import Dialog from "primevue/dialog";
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import { trans, wTrans } from "laravel-vue-i18n";

const props = defineProps({
    search: String,
});

const visible = ref(false);
const search = ref('');
const checked = ref()

const clearSearch = () => {
    search.value = '';
}

// Watch for changes in the 'visible' ref
watch(visible, (newValue) => {
    if (newValue === false) {
        clearSearch();
    }
});

const checkWindowWidth = () => {
    if (window.innerWidth > 768) {
        visible.value = false;
    }
};

// Add event listener when the component is mounted
onMounted(() => {
    window.addEventListener('resize', checkWindowWidth);
    checkWindowWidth(); // Initial check on load
});

// Remove event listener when the component is unmounted
onUnmounted(() => {
    window.removeEventListener('resize', checkWindowWidth);
});

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

// const handleMemberStatus = () => {
//     if (props.member.status === 'active') {
//         requireConfirmation('deactivate_member')
//     } else {
//         requireConfirmation('activate_member')
//     }
// }

</script>

<template>
    <Button
        type="button"
        variant="primary-outlined"
        class="w-full md:hidden"
        @click="visible = true"
    >
        {{ $t('public.manage_permission') }} 
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.manage_permission')"
        class="dialog-xs"
    >
        <div class="flex flex-col items-center py-4 gap-4 self-stretch md:py-6 md:gap-6">
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
            <div class="w-full flex flex-col items-center self-stretch overflow-y-auto">
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
                    />
                    <!-- <ToggleSwitch
                        v-model="checked"
                        readonly
                        @click="handleMemberStatus"
                    /> -->
                </div>
            </div>

        </div>
    </Dialog>

</template>
