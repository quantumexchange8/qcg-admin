<script setup>
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import Select from "primevue/select";
import { ref, watch } from "vue";

const props = defineProps({
    agents: Array,
})

const selectedAgent = ref(props.agents[0]);

watch(()=>props.agents, () => {
    selectedAgent.value = props.agents[0];
})
</script>

<template>
    <Select
        v-if="agents.length > 1"
        v-model="selectedAgent"
        :options="agents"
        filter
        :filterFields="['name']"
        optionLabel="name"
        class="w-full"
        scroll-height="236px"
    >
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <div>{{ slotProps.value.name }}</div>
                </div>
            </div>
        </template>
        <template #option="slotProps">
            <div class="flex items-center gap-2">
                <div>{{ slotProps.option.name }}</div>
            </div>
        </template>
    </Select>
    <div
        v-else
        class="flex items-center gap-3 w-full"
    >
        <div class="w-28 xl:w-auto truncate text-gray-950 text-sm">
            {{ agents[0].name }}
        </div>
    </div>
</template>
