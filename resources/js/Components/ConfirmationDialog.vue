<script setup>
import ConfirmDialog from 'primevue/confirmdialog';
import Button from "@/Components/Button.vue";
</script>

<template>
    <ConfirmDialog group="headless">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col justify-center items-center px-4 pt-[60px] pb-6 gap-8 bg-white rounded shadow-dialog w-[90vw] md:w-[500px] md:px-6">
                <div class="flex flex-col items-center gap-2 self-stretch">
                    <span class="self-stretch text-gray-950 text-center font-bold md:text-lg">{{ message.header }}</span>
                    <span class="self-stretch text-gray-700 text-center text-sm">{{ message.message }}</span>
                    <span class="self-stretch text-gray-700 text-center text-sm">
                        {{ message.text }}
                        <span class="self-stretch text-gray-950 text-sm font-semibold">{{ message.dynamicText }}</span>
                        {{ message.suffix }}
                    </span>
                </div>
                <!-- <div class="flex justify-end items-center gap-4 self-stretch"> -->
                <div class="grid grid-cols-2 justify-items-end items-center gap-4 self-stretch">
                    <Button
                        type="button"
                        variant="gray-outlined"
                        @click="rejectCallback"
                        class="w-full"
                        size="base"
                    >
                        {{ message.cancelButton }}
                    </Button>
                    <Button
                        type="button"
                        :variant="`${message.color}-flat`"
                        @click="acceptCallback"
                        class="w-full text-nowrap"
                        size="base"
                    >
                        {{ message.acceptButton }}
                    </Button>
                </div>
                <div class="flex w-[84px] h-[84px] p-6 justify-center items-center absolute -top-[42px] rounded-full grow-0 shrink-0"
                    :class="{
                        'bg-primary-600': message.color === 'primary',
                        'bg-error-600': message.color === 'error'
                    }"
                >
                    <component :is="message.icon" size="36" stroke-width="1.25" color="#FFFFFF" />
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>
