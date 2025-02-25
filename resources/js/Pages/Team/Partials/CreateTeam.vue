<script setup>
import Button from "@/Components/Button.vue"
import { usePage } from '@inertiajs/vue3';
import {IconPlus} from "@tabler/icons-vue";
import {ref, watch, watchEffect} from "vue";
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

const visible = ref(false);
const color = ref('ff0000');

const { formatAmount, formatDate } = transactionFormat();

const agents = ref();
const getAgents = async () => {
    try {
        const agentResponse = await axios.get('/team/getAgents');
        agents.value = agentResponse.data.users;
    } catch (error) {
        console.error('Error fetching agents:', error);
    }
};

getAgents();

const form = useForm({
    team_name: '',
    fee_charge: null,
    color: '',
    agent: '',
    team_members: null,
})

// Watch for changes to the selected agent
watch(() => form.agent, () => {
    if (form.agent) {
        form.team_members = form.agent.total; // Update team_members with total
    } else {
        form.team_members = null; // Reset if no agent is selected
    }
});

const submitForm = () => {
    form.color = color.value;

    form.post(route('team.createTeam'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            visible.value = false;
        },
    })
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getAgents();
    }
});

</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        class='w-full md:w-auto'
        @click="visible = true"
    >
        <IconPlus size="20" color="#ffffff" stroke-width="1.25" />
        {{ $t('public.create_sales_team') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.create_sales_team')"
        class="dialog-xs md:dialog-md"
        :dismissableMask="true"
    >
        <form>
            <div class="flex flex-col items-center py-4 gap-6 self-stretch md:py-6 md:gap-8">
                <div class="flex flex-col gap-2 items-center self-stretch">
                    <span class="text-gray-950 text-sm font-bold self-stretch w-full">{{ $t('public.sales_team_information') }}</span>
                    <div class="grid grid-cols-1 gap-3 items-center self-stretch md:grid-cols-2 md:gap-5">
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
                            />
                            <InputError :message="form.errors.team_name" />
                        </div>

                        <div class="flex flex-col items-start gap-2 self-stretch">
                            <InputLabel
                                for="fee_charge"
                                :value="`${$t('public.fee_charge')}&nbsp;(%)`"
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

                            <ColorPicker v-model="color" id="color"/>

                            <InputError :message="form.errors.color" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2 items-center self-stretch md:gap-3">
                    <span class="text-gray-950 text-sm font-bold self-stretch w-full">{{ $t('public.select_sales_agent') }}</span>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-5 w-full">
                        <div class="flex flex-col items-start gap-2 self-stretch">
                            <InputLabel
                                for="agent"
                                :value="$t('public.agent')"
                                :invalid="!!form.errors.agent"
                            />
                            <Select
                                id="agent"
                                v-model="form.agent"
                                :options="agents"
                                filter
                                :filterFields="['name', 'email']"
                                optionLabel="name"
                                :placeholder="$t('public.select_agent_placeholder')"
                                class="w-full"
                                scroll-height="236px"
                                :invalid="!!form.errors.agent"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center gap-3">
                                        <div>{{ slotProps.value.name }}</div>
                                    </div>
                                    <span v-else class="text-gray-400">
                                            {{ slotProps.placeholder }}
                                    </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <div>{{ slotProps.option.name }}</div>
                                    </div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.agent" />
                        </div>

                        <div class="flex flex-col items-start gap-2 self-stretch w-full">
                            <InputLabel
                                for="team_members"
                                :value="$t('public.total_sales_team_member')"
                                :invalid="!!form.errors.team_members"
                            />
                            <InputNumber
                                inputId="team_members"
                                v-model="form.team_members"
                                :min="0"
                                :step="100"
                                fluid
                                :invalid="!!form.errors.team_members"
                                class="w-full"
                                inputClass="py-3 px-4"
                                placeholder="0"
                                disabled
                            />
                            <InputError :message="form.errors.team_members" />
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
                    @click="visible = false"
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
                    {{ $t('public.create') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
