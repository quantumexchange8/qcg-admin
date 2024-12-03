<script setup>
import {IconCircleArrowDown} from "@tabler/icons-vue";
import Button from "primevue/button";
import {useForm} from "@inertiajs/vue3";

const form = useForm({

});

const recover = () => {
    form.post(route('startRecovery'))
}
</script>

<template>
    <div class="flex items-center w-full self-stretch">
        <div class="flex flex-col my-40 gap-5 items-center w-full">
            <div class="animate-bounce">
                <IconCircleArrowDown size="48" />
            </div>
            <Button
                type="button"
                class="px-5 font-semibold text-white"
                @click="recover"
                :disabled="form.processing"
            >
                RECOVER
            </Button>
        </div>
    </div>
</template>
