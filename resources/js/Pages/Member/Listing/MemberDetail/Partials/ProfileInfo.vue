<script setup>
import Button from "@/Components/Button.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import { IconPencilMinus, IconCircleCheckFilled } from "@tabler/icons-vue";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import InputSwitch from "primevue/inputswitch";
import { ref, watch } from "vue";
import Dialog from "primevue/dialog";
import InputLabel from "@/Components/InputLabel.vue";
import Select from "primevue/select";
import InputError from "@/Components/InputError.vue";
import InputText from "primevue/inputtext";
import { useForm } from "@inertiajs/vue3";
import { generalFormat, transactionFormat } from "@/Composables/index.js";
import { useConfirm } from "primevue/useconfirm";
import { trans } from "laravel-vue-i18n";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    userDetail: Object,
    countries: Array,
})

const visible = ref(false)
const countries = ref(props.countries)
const selectedCountry = ref();
const { formatRgbaColor } = generalFormat();
const { formatAmount } = transactionFormat();

const openDialog = () => {
    visible.value = true
}

const form = useForm({
    user_id: '',
    name: '',
    email: '',
    dial_code: '',
    phone: '',
    phone_number: '',
});

watch(() => props.userDetail, (user) => {
    form.user_id = props.userDetail.id
    form.name = props.userDetail.name
    form.email = props.userDetail.email
    form.phone = props.userDetail.phone

    // Set selectedCountry based on dial_code
    selectedCountry.value = countries.value.find(country => country.phone_code === user.dial_code);

});

const submitForm = () => {
    form.dial_code = selectedCountry.value;

    if (selectedCountry.value) {
        form.phone_number = selectedCountry.value.phone_code + form.phone;
    }

    form.post(route('member.updateMemberInfo'), {
        onSuccess: () => {
            visible.value = false;
        },
    });
};
</script>

<template>
    <div class="w-full flex flex-col items-center p-3 gap-3 self-stretch rounded-lg bg-white shadow-card md:px-6 md:py-5 md:justify-evenly">
        <div class="flex flex-col justify-center items-center gap-4 self-stretch">
            <div class="flex justify-between items-start self-stretch">
                <div class="w-[120px] h-[120px] grow-0 shrink-0 overflow-hidden bg-primary-600">
                    <div v-if="userDetail">
                        <div v-if="userDetail.profile_photo">
                            <img :src="userDetail.profile_photo" alt="Profile Photo" class="w-full object-cover" />
                        </div>
                        <div v-else class="p-4">
                            <DefaultProfilePhoto />
                        </div>
                    </div>
                    <div v-else class="animate-pulse p-4">
                        <DefaultProfilePhoto />
                    </div>
                </div>
                <Button
                    type="button"
                    iconOnly
                    size="base"
                    variant="gray-text"
                    pill
                    @click="openDialog()"
                    :disabled="!userDetail"
                >
                    <IconPencilMinus size="20" />
                </Button>
            </div>
            <div v-if="userDetail" class="flex flex-col items-start gap-1 self-stretch">
                <span class="w-full truncate self-stretch text-gray-950 text-xl font-bold">
                    {{ userDetail.name }}
                </span>
                <span class="w-full flex items-center gap-1 text-gray-700 font-medium">
                    <span class="truncate">
                        {{ userDetail.email }}
                    </span>
                    <IconCircleCheckFilled v-if="userDetail.email_verified_at !== null" size="20" stroke-width="1.25" class="text-success-700 grow-0 shrink-0" />
                </span>
            </div>
            <div v-else class="animate-pulse flex flex-col items-start gap-1.5 self-stretch">
                <div class="h-4 bg-gray-200 rounded-full w-48 my-2 md:my-3"></div>
                <div class="h-2 bg-gray-200 rounded-full w-20 mb-1"></div>
            </div>
        </div>
        <div class="h-[1px] self-stretch bg-gray-200" />
        <div v-if="userDetail" class="w-full grid grid-cols-2 gap-5">
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.id') }}</div>
                <div class="truncate text-gray-700 font-medium">{{ userDetail?.id_number ?? '-' }}</div>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.phone_number') }}</div>
                <div class="truncate text-gray-700 font-medium">{{ userDetail.dial_code }} {{ userDetail.phone }}</div>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.sales_team') }}</div>
                <div class="flex items-center">
                    <div 
                        v-if="userDetail.team_id" 
                        class="flex items-center gap-2 rounded py-1 px-2"
                        :style="{ backgroundColor: formatRgbaColor(userDetail.team_color, 0.1) }"
                    >
                        <div class="flex items-center">
                            <div 
                                class="text-xs font-semibold"
                                :style="{ color: `#${userDetail.team_color}` }"
                            >
                                {{ userDetail?.team_name || '-' }}
                            </div>
                        </div>
                    </div>
                    <div v-else>-</div>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.upline') }}</div>
                <div class="truncate text-gray-700 font-medium">{{ userDetail.upline_name ?? '-' }}</div>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.total_referee') }}</div>
                <div class="truncate text-gray-700 font-medium">{{ formatAmount(userDetail.total_direct_member + userDetail.total_direct_agent, 0) }}</div>
            </div>
        </div>
        <div v-else class="w-full grid grid-cols-2 gap-5 animate-pulse">
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.email_address') }}</div>
                <div class="truncate text-gray-700 font-medium">
                    <div class="h-2 bg-gray-200 rounded-full w-48 my-2"></div>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.phone_number') }}</div>
                <div class="h-2 bg-gray-200 rounded-full w-36 my-2"></div>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.group') }}</div>
                <div class="h-3 bg-gray-200 rounded-full w-20 mt-1 mb-1.5"></div>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.upline') }}</div>
                <div class="h-3 bg-gray-200 rounded-full w-36 mt-1 mb-1.5"></div>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-gray-500 truncate">{{ $t('public.total_referred_member') }}</div>
                <div class="h-2 bg-gray-200 rounded-full w-36 mt-2 mb-1"></div>
            </div>
        </div>
    </div>

    <!-- edit contact info -->
    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.edit_member_info')"
        class="dialog-xs md:dialog-sm"
        :dismissableMask="true"
    >
        <form>
            <div class="flex flex-col gap-5 py-4 md:py-6">
                <div class="flex flex-col gap-1">
                    <InputLabel for="name" :value="$t('public.name')" :invalid="!!form.errors.name" />
                    <InputText
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        :placeholder="$t('public.enter_name')"
                        :invalid="!!form.errors.name"
                        autocomplete="name"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="flex flex-col gap-1">
                    <InputLabel for="email" :value="$t('public.email_address')" :invalid="!!form.errors.email" />
                    <InputText
                        id="email"
                        type="email"
                        class="block w-full"
                        v-model="form.email"
                        :placeholder="$t('public.enter_email')"
                        :invalid="!!form.errors.email"
                        autocomplete="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel for="phone" :value="$t('public.phone_number')" :invalid="!!form.errors.phone" />
                    <div class="flex gap-2 items-center self-stretch relative">
                        <Select
                            v-model="selectedCountry"
                            :options="countries"
                            filter
                            :filterFields="['name', 'phone_code']"
                            optionLabel="name"
                            :placeholder="$t('public.phone_code')"
                            class="w-[100px]"
                            scroll-height="236px"
                            :invalid="!!form.errors.dial_code"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div>{{ slotProps.value.phone_code }}</div>
                                </div>
                                <span v-else>
                                    {{ slotProps.placeholder }}
                                </span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center w-[262px] md:max-w-[236px]">
                                    <div>{{ slotProps.option.name }} <span class="text-gray-500">{{ slotProps.option.phone_code }}</span></div>
                                </div>
                            </template>
                        </Select>

                        <InputText
                            id="phone"
                            type="text"
                            class="block w-full"
                            v-model="form.phone"
                            :placeholder="$t('public.phone_number')"
                            :invalid="!!form.errors.phone"
                        />
                    </div>
                    <InputError :message="form.errors.phone" />
                </div>
            </div>
            <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
                <Button
                    type="button"
                    variant="gray-tonal"
                    class="w-full"
                    :disabled="form.processing"
                    @click.prevent="visible = false"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    type="button"
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

