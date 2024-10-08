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
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import toast from '@/Composables/toast';
import DatePicker from 'primevue/datepicker';
import MultiSelect from 'primevue/multiselect';

const selectedCity = ref();
const selectedCities = ref();
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
// const value = ref('');

// Function to trigger a toast notification
function triggerToast() {
    toast.add({
        title: 'Success Toast',
        message: 'This is a success toast message.',
        type: 'success',
    });
    toast.add({
        title: 'Warning Toast',
        message: 'This is a warning toast message.',
        type: 'warning',
    });
    toast.add({
        title: 'Error Toast',
        message: 'This is a error toast message.',
        type: 'error',
    });
    toast.add({
        title: 'Info Toast',
        message: 'This is a info toast message.',
        type: 'info',
    });

}
const value = ref('0');
const dates = ref();

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
        <!-- <div class="card">
            <Tabs value="0">
                <TabList>
                    <Tab value="0">Header I</Tab>
                    <Tab value="1">Header II</Tab>
                    <Tab value="2">Header III</Tab>
                </TabList>
                <TabPanels class="bg-primary-500 p-5">
                    <TabPanel value="0" class="bg-primary-700">
                        <p class="m-0">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                            consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                        </p>
                    </TabPanel>
                    <TabPanel value="1" class="bg-primary-700">
                        <p class="m-0">
                            Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim
                            ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Consectetur, adipisci velit, sed quia non numquam eius modi.
                        </p>
                    </TabPanel>
                    <TabPanel value="2" class="bg-primary-700">
                        <p class="m-0">
                            At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa
                            qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus.
                        </p>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
 -->
        <!-- <Button
            type="button"
            variant="primary-flat"
            size="base"
            @click="triggerToast"
        >
            Toast
        </Button> -->

        <!-- <div class="card">
        <Tabs v-model:value="value">
            <TabList>
                <Tab value="0">Header I</Tab>
                <Tab value="1">Header II</Tab>
                <Tab value="2">Header III</Tab>
            </TabList>
            <TabPanels>
                <TabPanel value="0">
                    <p class="m-0">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                        consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                    </p>
                </TabPanel>
                <TabPanel value="1">
                    <p class="m-0">
                        Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim
                        ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Consectetur, adipisci velit, sed quia non numquam eius modi.
                    </p>
                </TabPanel>
                <TabPanel value="2">
                    <p class="m-0">
                        At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa
                        qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus.
                    </p>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div> -->

    <!-- <DatePicker v-model="dates" selectionMode="range" showIcon iconDisplay="input" /> -->

    <MultiSelect v-model="selectedCities" :options="cities" optionLabel="name" filter placeholder="Select Cities" :maxSelectedLabels="3" class="w-full md:w-80" />

    </AuthenticatedLayout>
</template>
