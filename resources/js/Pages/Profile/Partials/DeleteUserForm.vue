<script setup>
import { ref } from 'vue';
import Button from "@/Components/Button.vue";
import ConfirmDialog from 'primevue/confirmdialog';
import { IconTrashX } from "@tabler/icons-vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Password from 'primevue/password';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const visible = ref(false);

const openConfirmDialog = () => {
    visible.value = true;
};

const handleDelete = () => {
    form.delete(route('profile.destroy'), {
        onSuccess: () => {
            visible.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <div class="w-full h-full flex flex-col justify-between items-start p-3 gap-8 rounded-lg bg-white shadow-card md:p-6">
        <div class="w-full flex flex-col justify-center items-start gap-1">
            <span class="text-gray-950 font-bold">{{ $t('public.delete_account') }}</span>
            <span class="text-gray-500 text-xs">{{ $t('public.delete_account_desc') }}</span>
        </div>

        <Button
            type="button"
            variant="error-flat"
            size="base"
            @click="openConfirmDialog"
        >
            {{ $t('public.delete_account') }}
        </Button>
    </div>

    <ConfirmDialog
        group="headless-error"
        v-model:visible="visible"
    >
        <template #container>
            <div class="flex flex-col justify-center items-center px-4 pt-[60px] pb-6 gap-8 bg-white rounded shadow-dialog w-[90vw] md:w-[500px] md:px-6">
                <div class="flex flex-col items-center gap-2 self-stretch">
                    <span class="self-stretch text-gray-950 text-center font-bold md:text-lg">{{ $t('public.delete_account') }}</span>
                    <span class="self-stretch text-gray-700 text-center text-sm">{{ $t('public.delete_account_message') }}</span>
                </div>
                <div class="flex flex-col gap-1 w-full">
                    <InputLabel for="current_password" :value="$t('public.current_password')" />
                    <Password
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        toggleMask
                        placeholder="••••••••"
                        :invalid="!!form.errors.current_password"
                    />
                    <InputError :message="form.errors.current_password" />
                </div>
                <div class="grid grid-cols-2 justify-items-end items-center gap-4 self-stretch">
                    <Button
                        type="button"
                        variant="gray-outlined"
                        @click="visible = false"
                        class="w-full"
                        size="base"
                    >
                        {{ $t('public.cancel') }}
                    </Button>
                    <Button
                        type="button"
                        variant="error-flat"
                        @click="handleDelete"
                        class="w-full text-nowrap"
                        size="base"
                    >
                        {{ $t('public.delete') }}
                    </Button>
                </div>
                <div class="flex w-[84px] h-[84px] p-6 justify-center items-center absolute -top-[42px] rounded-full grow-0 shrink-0 bg-error-600">
                    <IconTrashX size="36" stroke-width="1.25" color="#FFFFFF" />
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>
