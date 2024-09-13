<script setup>
defineProps({
    value: {
        type: String,
    },
    invalid: Boolean
});
</script>

<template>
    <label
        class="block text-sm"
        :class="{
            'text-gray-700': !invalid,
            'text-error-600': invalid
        }"
    >
        <div v-if="value">
            {{ value }}<span class="text-error-600 text-sm"> *</span>
        </div>
        <div v-else>
            <slot />
        </div>
    </label>
</template>
