<script setup>
import { EmptyRecordIcon } from '@/Components/Icons/solid';
import { defineProps } from 'vue';

const props = defineProps({
  title: String,
  message: String
});
</script>

<template>
  <div class="flex flex-col flex-1 justify-center items-center py-5 px-6 gap-5 self-stretch">
    <!-- Slot for custom image or content with fallback -->
    <template v-if="$slots.image">
      <slot name="image"></slot>
    </template>
    <template v-else>
      <EmptyRecordIcon class="w-60 h-[180px]" />
    </template>

    <div class="flex flex-col items-center gap-2 self-stretch">
      <!-- Slot for custom title -->
      <slot name="title">
        <div v-if="title" class="self-stretch text-gray-700 text-center font-bold">
          {{ title }}
        </div>
      </slot>

      <!-- Slot for custom message -->
      <slot name="message">
        <div v-if="message" class="self-stretch text-gray-500 text-center text-sm">
          {{ message }}
        </div>
      </slot>
    </div>
  </div>
</template>
