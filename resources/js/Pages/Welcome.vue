<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, watch } from 'vue';
import Button from '@/Components/Button.vue';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import ToggleSwitch from 'primevue/toggleswitch';
import Chip from 'primevue/chip';
import Textarea from 'primevue/textarea';
import Password from 'primevue/password';
import FileUpload from 'primevue/fileupload';
import {
    IconUpload
} from '@tabler/icons-vue';

const selectedCity = ref();
const cities = ref([
    { name: 'New York', code: 'NY' },
    { name: 'Rome', code: 'RM' },
    { name: 'London', code: 'LDN' },
    { name: 'Istanbul', code: 'IST' },
    { name: 'Paris', code: 'PRS' }
]);

const selectedCountry = ref();
const countries = ref([
    { name: 'Australia', code: 'AU' },
    { name: 'Brazil', code: 'BR' },
    { name: 'China', code: 'CN' },
    { name: 'Egypt', code: 'EG' },
    { name: 'France', code: 'FR' },
    { name: 'Germany', code: 'DE' },
    { name: 'India', code: 'IN' },
    { name: 'Japan', code: 'JP' },
    { name: 'Spain', code: 'ES' },
    { name: 'United States', code: 'US' }
]);
const ingredient = ref('');
const pizza = ref();
const checked = ref(false);
const checkedtrue = ref(true);
const value = ref('');

</script>

<template>
    <AuthenticatedLayout title="Component">
        <!-- <Select v-model="selectedCity" showClear fluid filter :filterFields="['name']" :options="cities" optionLabel="name" placeholder="Select a City" >
            <template #value="slotProps">
                <div v-if="slotProps.value" class="flex items-center">
                    <div>{{ slotProps.value.name }}</div>
                </div>
                <span v-else>
                    {{ slotProps.placeholder }}
                </span>
            </template>
            <template #option="slotProps">
                <div class="flex items-center w-[262px] md:max-w-[236px]">
                    <div>{{ slotProps.option.name }} <span class="text-gray-500">{{ slotProps.option.code }}</span></div>
                </div>
            </template>
        </Select> -->
        <!-- <div class="flex flex-wrap gap-4">
            <div class="flex items-center">
                <RadioButton v-model="ingredient" inputId="ingredient1" name="pizza" value="Cheese" class="w-6 h-6"/>
                <label for="ingredient1" class="ml-2">Cheese</label>
            </div>
            <div class="flex items-center">
                <RadioButton v-model="ingredient" inputId="ingredient2" name="pizza" value="Mushroom" class="w-6 h-6"/>
                <label for="ingredient2" class="ml-2">Mushroom</label>
            </div>
            <div class="flex items-center">
                <RadioButton v-model="ingredient" inputId="ingredient3" name="pizza" value="Pepper" class="w-6 h-6"/>
                <label for="ingredient3" class="ml-2">Pepper</label>
            </div>
            <div class="flex items-center">
                <RadioButton v-model="ingredient" inputId="ingredient4" name="pizza" value="Onion" class="w-6 h-6"/>
                <label for="ingredient4" class="ml-2">Onion</label>
            </div>
        </div> -->
        <!-- <div class="flex items-center">
            <Checkbox v-model="pizza" inputId="ingredient1" name="pizza" value="Cheese" class="w-6 h-6" />
            <label for="ingredient1" class="ml-2"> Cheese </label>
        </div>
        <div class="flex items-center">
            <Checkbox v-model="pizza" inputId="ingredient2" name="pizza" value="Mushroom" class="w-6 h-6" />
            <label for="ingredient2" class="ml-2"> Mushroom </label>
        </div>
        <div class="flex items-center">
            <Checkbox v-model="pizza" inputId="ingredient3" name="pizza" value="Pepper" class="w-6 h-6" />
            <label for="ingredient3" class="ml-2"> Pepper </label>
        </div>
        <div class="flex items-center">
            <Checkbox v-model="pizza" inputId="ingredient4" name="pizza" value="Onion" class="w-6 h-6" />
            <label for="ingredient4" class="ml-2"> Onion </label>
        </div> -->
        <!-- <ToggleSwitch v-model="checked" />
        <ToggleSwitch v-model="checkedtrue" /> -->
        <!-- <div class="card flex flex-wrap gap-2">
            <Chip label="Action" />
            <Chip label="Comedy" />
            <Chip label="Mystery" />
            <Chip label="Thriller" removable />
        </div> -->
        <!-- <Textarea v-model="value" rows="5" cols="30"    class="h-20" /> -->
        <!-- <Password v-model="value" toggleMask :feedback="false"  /> -->
        <!-- Kyc Verification -->
        <!-- <div class="flex flex-col gap-3 items-center self-stretch">
            <div class="text-gray-950 font-semibold text-sm self-stretch">
                {{ $t('public.kyc_verification') }}
            </div>
            <div class="flex flex-col gap-3 items-start self-stretch">
                <span class="text-xs text-gray-500">{{ $t('public.kyc_caption') }}</span>
                <FileUpload
                    class="w-full"
                    name="kyc_verification"
                    url="/member/uploadKyc"
                    accept="image/*"
                    :maxFileSize="10485760"
                    auto
                >
                    <template #header="{ chooseCallback }">
                        <div class="flex flex-wrap justify-between items-center flex-1 gap-2">
                            <div class="flex gap-2">
                                <Button
                                    type="button"
                                    variant="primary-flat"
                                    @click="chooseCallback()"
                                >
                                    <IconUpload :size="20" stroke-width="1.25" />
                                    {{ $t('public.browse') }}
                                </Button>
                            </div>
                        </div>
                    </template>
                </FileUpload>
            </div>
        </div> -->

    </AuthenticatedLayout>
</template>
