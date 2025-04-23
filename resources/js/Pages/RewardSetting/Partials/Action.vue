<script setup>
import {
    IconDotsVertical,
    IconPencilMinus,
    IconTrashX,
    IconChevronRight,
    IconEyeCheck,
    IconEyeCancel,
} from "@tabler/icons-vue";
import Button from "@/Components/Button.vue";
import { h, ref, watch } from "vue";
import TieredMenu from "primevue/tieredmenu";
import ToggleSwitch from 'primevue/toggleswitch';
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import Dialog from "primevue/dialog";
import Edit from "@/Pages/RewardSetting/Partials/Edit.vue";

const props = defineProps({
    reward: Object,
})

const menu = ref();
const visible = ref(false)
const dialogType = ref('')

const items = ref([
    {
        label: 'edit',
        icon: h(IconPencilMinus),
        command: () => {
            visible.value = true;
            dialogType.value = 'edit';
        },
    },
    {
        label: 'delete_rewards',
        icon: h(IconTrashX),
        command: () => {
            requireConfirmation('delete_rewards')
        },
    },

]);

const toggle = (event) => {
    menu.value.toggle(event);
};

const checked = ref(props.reward.status === 'active')

watch(() => props.reward.status, (newStatus) => {
    checked.value = newStatus === 'active';
});

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        activate_rewards: {
            group: 'headless',
            color: 'primary',
            icon: h(IconEyeCheck),
            header: trans('public.activate_rewards'),
            message: trans('public.activate_rewards_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.activate'),
            action: () => {
                router.post(route('reward.updateRewardStatus'), {
                    reward_id: props.reward.reward_id,
                })

                checked.value = !checked.value;
            }
        },
        deactivate_rewards: {
            group: 'headless',
            color: 'error',
            icon: h(IconEyeCancel),
            header: trans('public.deactivate_rewards'),
            message: trans('public.deactivate_rewards_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.deactivate'),
            action: () => {
                router.post(route('reward.updateRewardStatus'), {
                    reward_id: props.reward.reward_id,
                })

                checked.value = !checked.value;
            }
        },
        delete_rewards: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_rewards'),
            message: trans('public.delete_rewards_desc'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.delete(route('reward.deleteReward'), {
                    data: {
                        reward_id: props.reward.reward_id,
                    },
                })
            }
        },
    };

    const { group, color, icon, header, message, cancelButton, acceptButton, action } = messages[action_type];

    confirm.require({
        group,
        color,
        icon,
        header,
        message,
        cancelButton,
        acceptButton,
        accept: action
    });
};

const handleRewardStatus = () => {
    if (props.reward.status === 'active') {
        requireConfirmation('deactivate_rewards')
    } else {
        requireConfirmation('activate_rewards')
    }
}

</script>

<template>
    <div class="flex gap-3 items-center justify-center">
        <ToggleSwitch
            v-model="checked"
            readonly
            @click="handleRewardStatus"
        />
        <Button
            variant="gray-text"
            size="sm"
            type="button"
            iconOnly
            pill
            @click="toggle"
            aria-haspopup="true"
            aria-controls="overlay_tmenu"
        >
            <IconDotsVertical size="16" stroke-width="1.25" color="#667085" />
        </Button>

        <TieredMenu ref="menu" id="overlay_tmenu" :model="items" popup>
            <template #item="{ item, props, hasSubmenu }">
                <div
                    class="flex items-center gap-3 self-stretch"
                    v-bind="props.action"
                >
                <component
                    :is="item.icon"
                    size="20"
                    stroke-width="1.25"
                    class="grow-0 shrink-0"
                    :class="{'text-error-500': item.label === 'delete_rewards'}"
                />
                    <span class="text-gray-700 text-sm">{{ $t(`public.${item.label}`) }}</span>
                    <!-- Conditionally render submenu indicator if the item has a submenu -->
                    <span v-if="hasSubmenu" class="ml-auto text-gray-500">
                        <IconChevronRight size="20" stroke-width="1.25" />
                    </span>
                </div>
            </template>
        </TieredMenu>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}`)"
        class="dialog-xs md:dialog-md"
        :dismissableMask="true"
    >
        <template v-if="dialogType === 'edit'">
            <Edit
                :reward="reward"
                @update:visible="visible = false"
            />
        </template>

    </Dialog>
</template>
