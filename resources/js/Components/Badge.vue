<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'info',
    },
    pill: {
        type: Boolean,
        default: false,
    }
});

const { variant, pill } = props;

const baseClasses = [
    'flex flex-col justify-center items-center text-center'
]

const variantClasses = (variant) => ({
    'bg-info-400': variant === 'info',
    'bg-primary-600 rounded-sm px-1 py-0.5': variant === 'numberBadge'
})

const classes = computed(() => [
    ...baseClasses,
    variantClasses(variant),
    {
        'rounded p-0.5': !pill && variant !== 'numberBadge',
        'rounded-full py-px px-1': pill,
    }
]);
</script>

<template>
    <div
        :class="classes"
    >
        <slot />
    </div>
</template>
