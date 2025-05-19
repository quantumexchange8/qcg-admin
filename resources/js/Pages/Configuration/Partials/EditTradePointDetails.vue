<script setup>
import Button from "@/Components/Button.vue";
import Dialog from 'primevue/dialog';
import {ref, watch, computed} from "vue";
import { useForm } from "@inertiajs/vue3";
import {
    IconPencilMinus,
} from "@tabler/icons-vue";
import InputError from "@/Components/InputError.vue";
import InputNumber from "primevue/inputnumber";

const props = defineProps({
    tradeDetails: {
        type: Object,
        default: []
    }
})

const editVisible = ref(false);

const openEditDialog = () => {
    editVisible.value = true;
}

const form = useForm({
    id: '',
    amount: 0,
});


watch(
    () => props.tradeDetails,
    (newTrade) => {
        if (props.tradeDetails.length > 0) {
            form.id = newTrade.map((trade, index) => trade.id || form.id[index] || '');
            form.amount = newTrade.map((trade, index) => Number(trade.trade_point_rate) || form.amount[index] || 0);
        }
    },
    { immediate: true }
);

const submitForm = () => {
    form.post(route('configuration.updateTradePointRate'), {
        onSuccess: () => {
            editVisible.value = false;
        }
    })
}
</script>

<template>
    <Button
        variant="gray-text"
        size="base"
        type="button"
        iconOnly
        @click="openEditDialog()"
    >
        <IconPencilMinus size="20" stroke-width="1.5" />
    </Button>

    <Dialog v-model:visible="editVisible" modal :header="$t('public.edit_trade_point_details')" class="dialog-sm" :closeOnEscape="false">
        <form>
            <div class="flex flex-col items-center py-4 self-stretch md:py-6">
                <div class="flex items-center w-full self-stretch py-3 text-gray-700 bg-gray-50 border-b border-gray-100">
                    <span class="uppercase text-xs font-bold px-3 w-full">{{ $t('public.product') }}</span>
                    <span class="uppercase text-xs font-bold px-3 w-full">{{ $t('public.trade_point') }} / Ł (TP)</span>
                </div>

                <!-- symbol groups -->
                <div
                    v-for="(tradeDetail, index) in tradeDetails"
                    class="flex items-center w-full self-stretch py-2 text-gray-950"
                >
                    <div class="text-sm px-3 w-full">{{ $t(`public.${tradeDetail.symbol_group.display}`) }}</div>
                    <div class="px-3 w-full">
                        <InputNumber
                            v-model="form.amount[index]"
                            :min="0"
                            :minFractionDigits="2"
                            fluid
                            :invalid="!!form.errors[`amount.${index}`]"
                            inputClass="py-3 px-4 w-full"
                        />
                        <InputError :message="form.errors[`amount.${index}`]" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
                <Button
                    type="button"
                    variant="gray-tonal"
                    class="w-full"
                    :disabled="form.processing"
                    @click.prevent="editVisible = false"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary-flat"
                    class="w-full"
                    :disabled="form.processing"
                    @click="submitForm"
                >
                    {{ $t('public.save') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
