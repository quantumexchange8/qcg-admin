<script setup>
import { computed } from "vue";

const props = defineProps({
  variant: {
    type: String,
    default: "success",
  },
  borderRadius: {
    default: "rounded",
  },
  value: String,
});

const baseClasses = [
  "flex px-2 py-1 justify-center items-center text-xs font-semibold hover:-translate-y-1 transition-all duration-300 ease-in-out w-auto cursor-default",
];

const variantClasses = computed(() => {
    if (props.variant === 'primary' || props.variant === 'member') {
        return 'bg-primary-600 text-white'
    } else if (props.variant === 'successful' || props.variant === 'active' || props.variant === 'live') {
        return 'bg-success-500 text-white'
    } else if (props.variant === 'error' || props.variant === 'failed' || props.variant === 'rejected') {
        return 'bg-error-600 text-white'
    } else if (props.variant === 'warning' || props.variant === 'agent' || props.variant === 'ongoing') {
        return 'bg-warning-500 text-white'
    } else if (props.variant === 'info' || props.variant === 'demo' || props.variant === 'processing') {
        return 'bg-info-500 text-white'
    } else if (props.variant === 'gray' || props.variant === 'inactive' ) {
        return 'bg-gray-500 text-white'
    }
    return 'bg-primary-600 text-gray-950'
})

const borderClass = computed(() => {
  return {
    rounded: "rounded",
    20: "rounded-[20px]",
  }[props.borderRadius.toString()];
});

const classes = computed(() => [
    ...baseClasses,
    variantClasses.value
])

</script>

<template>
  <div :class="[borderClass, classes]">
    <slot>{{ value }}</slot>
  </div>
</template>
