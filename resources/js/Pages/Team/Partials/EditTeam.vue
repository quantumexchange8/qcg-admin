<script setup>
import Button from "@/Components/Button.vue"
import {IconPlus} from "@tabler/icons-vue";
import {ref, watch} from "vue";
import Dialog from "primevue/dialog";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from 'primevue/inputtext';
import InputNumber from "primevue/inputnumber";
import {useForm} from "@inertiajs/vue3";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import Select from "primevue/select";
import ColorPicker from 'primevue/colorpicker';
import { transactionFormat } from "@/Composables/index.js";

const { formatAmount, formatDate } = transactionFormat();

const props = defineProps({
    team: Object,
})


const visible = ref();
const color = ref(props.team.color);
const emit = defineEmits(['update:visible'])

const closeDialog = () => {
    emit('update:visible', false);
}

const form = useForm({
    team_id: props.team.id,
    team_name: props.team.name,
    fee_charge: Number(props.team.fee_charges),
    color: props.team.color,
})

const submitForm = () => {
    form.color = color.value;

    form.post(route('team.editTeam'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            closeDialog();
        },
    })
}

</script>

<template>
    <form>
        <div class="flex flex-col items-center py-4 gap-6 self-stretch md:py-6 md:gap-8">
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex flex-col justify-center items-center gap-3 self-stretch md:gap-5">
                    <div class="flex flex-col items-start gap-2 self-stretch">
                        <InputLabel
                            for="team_name"
                            :value="$t('public.team_name')"
                            :invalid="!!form.errors.team_name"
                        />
                        <InputText
                            id="team_name"
                            type="text"
                            class="block w-full"
                            v-model="form.team_name"
                            :placeholder="$t('public.team_name_placeholder')"
                            :invalid="!!form.errors.team_name"
                            autofocus
                        />
                        <InputError :message="form.errors.team_name" />
                    </div>

                    <div class="flex flex-col items-start gap-2 self-stretch">
                        <InputLabel
                            for="fee_charge"
                            :value="$t('public.fee_charge')"
                            :invalid="!!form.errors.fee_charge"
                        />
                        <InputNumber
                            inputId="fee_charge"
                            v-model="form.fee_charge"
                            :min="0"
                            :step="100"
                            :minFractionDigits="2"
                            fluid
                            :invalid="!!form.errors.fee_charge"
                            class="w-full"
                            inputClass="py-3 px-4"
                            :placeholder="`${formatAmount(0)}%`"
                        />
                        <InputError :message="form.errors.fee_charge" />
                    </div>

                    <div class="flex flex-col items-start gap-2 self-stretch md:col-span-2">
                        <InputLabel
                            for="color"
                            :value="$t('public.colour')"
                            :invalid="!!form.errors.color"
                        />

                        <ColorPicker v-model="color" id="Color"/>

                        <InputError :message="form.errors.color" />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
            <Button
                type="button"
                size="base"
                class="w-full"
                variant="gray-outlined"
                @click="closeDialog"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary-flat"
                size="base"
                class="w-full"
                @click="submitForm"
                :disabled="form.processing"
            >
                {{ $t('public.save') }}
            </Button>
        </div>
    </form>
</template>
