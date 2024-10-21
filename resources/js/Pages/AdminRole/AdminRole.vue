<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import { transactionFormat } from "@/Composables/index.js";
import { IconRefresh, IconCircleXFilled, IconSearch, IconPencilMinus } from "@tabler/icons-vue";
import { ref, watch, watchEffect, onMounted } from "vue";
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import { wTrans, trans } from "laravel-vue-i18n";
import Button from '@/Components/Button.vue';
import debounce from "lodash/debounce.js";
import dayjs from "dayjs";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import CreateAdminRole from "@/Pages/AdminRole/Partials/CreateAdminRole.vue";
import AdminRoleActions from "@/Pages/AdminRole/Partials/AdminRoleActions.vue";

const props = defineProps({
    totalAdminRoles: Number,
    permissionsList: Array,
})

const admins = ref();
const loading = ref(false);

const getAdminRole = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/adminRole/getAdminRole');
        admins.value = response.data.admins;
    } catch (error) {
        console.error('Error getting account types:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    getAdminRole();
})

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getAdminRole();
    }
});

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar_admin_role')">
        <div class="w-full flex flex-col items-center gap-5">
            <div class="flex flex-col justify-center items-center px-3 py-5 gap-5 self-stretch rounded-lg bg-white shadow-card md:p-6 md:gap-6">
                <div class="flex flex-col items-center gap-3 self-stretch md:flex-row md:justify-between">
                    <span class="self-stretch md:self-auto text-gray-950 font-semibold">{{ $t('public.all_admin_role') }}</span>
                    <CreateAdminRole :permissionsList="props.permissionsList" />
                </div>
                <div v-if="totalAdminRoles <= 0">
                    <Empty 
                        :title="$t('public.empty_admin_role_title')" 
                        :message="$t('public.empty_admin_role_message')" 
                    />
                </div>
                <DataTable
                    v-else
                    :value="admins"
                    removableSort
                    ref="dt"
                    :loading="loading"
                    table-style="min-width:fit-content"
                >
                    <template #empty><Empty :title="$t('public.empty_admin_role_title')" :message="$t('public.empty_admin_role_message')" /></template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loader />
                            <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
                        </div>
                    </template>
                    <Column field="first_name" :header="$t('public.name')" sortable class="w-full md:w-[25%] max-w-0 px-3" headerClass="text-nowrap">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start max-w-full">
                                <div class="text-gray-950 text-sm font-semibold truncate max-w-full">
                                    {{ slotProps.data.first_name }}
                                </div>
                                <div class="text-gray-500 text-xs truncate max-w-full">
                                    {{ slotProps.data.email }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="created_at" :header="$t('public.created_date')" sortable class="hidden md:table-cell w-[25%] px-3" headerClass="text-nowrap">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm">
                                {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                            </div>
                        </template>
                    </Column>
                    <Column field="role" :header="$t('public.role')" class="hidden md:table-cell w-[25%] px-3" headerClass="text-nowrap">
                        <template #body="slotProps">
                            <div class="text-gray-950 text-sm capitalize">
                                {{ slotProps.data.role }}
                            </div>
                        </template>
                    </Column>
                    <Column field="action" class="w-full md:w-[25%] px-3">
                        <template #body="slotProps">
                            <AdminRoleActions 
                                :admin="slotProps.data"
                                :permissionsList="props.permissionsList"
                            />
                        </template>
                    </Column>

                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>