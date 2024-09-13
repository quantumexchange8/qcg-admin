<script setup>
import {onMounted} from "vue";
import {
    IconCheck,
    IconAlertTriangle,
    IconCircleX,
    IconInfoCircle,
    IconX
} from '@tabler/icons-vue';

const props = defineProps({
    title: String,
    message: String,
    type: String, // Accepts 'success', 'info', 'warning', 'error'
    duration: {
        type: Number,
        default: 3000
    }
});

onMounted(() => {
    setTimeout(() => emit('remove'), props.duration);
});

const emit = defineEmits(['remove']);

// Determine icon based on the type
const iconComponent = {
    success: IconCheck,
    warning: IconAlertTriangle,
    error: IconCircleX,
    info: IconInfoCircle
}[props.type];

const borderColor = {
    success: 'border-success-600',
    warning: 'border-warning-600',
    error: 'border-error-600',
    info: 'border-info-600',
}[props.type];

const bgColor = {
    success: 'bg-success-100',
    warning: 'bg-warning-100',
    error: 'bg-error-100',
    info: 'bg-info-100',
}[props.type];


const textColor = {
    success: 'text-success-600',
    warning: 'text-warning-600',
    error: 'text-error-600',
    info: 'text-info-600',
}[props.type];

</script>
<template>
    <div
        class="mx-3 md:mx-0 py-3 px-4 flex justify-center items-start self-stretch gap-3 border-l-8 rounded"
        :class="[
            borderColor,
            bgColor,
        ]"
        role="alert"
    >
        <div :class="textColor">
            <component :is="iconComponent" size="24" />
        </div>
        <div
            class="flex flex-col items-start gap-1 w-full text-sm"
            :class="{
                'gap-1': message
            }"
        >
            <div class="font-semibold" :class="textColor">
                {{ title }}
            </div>
            <div class="text-gray-700">
                {{ message }}
            </div>
        </div>
        <div 
            class="hover:cursor-pointer select-none" 
            :class="[
                textColor,
            ]"
            @click="emit('remove')"
        >
            <IconX size="16" stroke-width="1.25" />
        </div>
    </div>
</template>
