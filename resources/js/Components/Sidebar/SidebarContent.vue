<script setup>
import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import SidebarCollapsible from '@/Components/Sidebar/SidebarCollapsible.vue'
import SidebarCollapsibleItem from '@/Components/Sidebar/SidebarCollapsibleItem.vue'
import {onMounted, ref, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import {
    IconSmartHome,
    IconClockDollar,
    IconUsers,
    IconSitemap,
    IconSettingsDollar,
    IconTrophy,
    IconReportMoney,
    IconTool,
    IconShieldCheckeredFilled,
    IconCategory2,
    IconComponents,
} from '@tabler/icons-vue';

// const pendingWithdrawals = ref(0);
// const pendingPammAllocate = ref(0);
// const pendingBonusWithdrawal = ref(0);

// const getPendingCounts = async () => {
//     try {
//         const response = await axios.get('/getPendingCounts');
//         pendingWithdrawals.value = response.data.pendingWithdrawals
//         pendingPammAllocate.value = response.data.pendingPammAllocate
//         pendingBonusWithdrawal.value = response.data.pendingBonusWithdrawal
//     } catch (error) {
//         console.error('Error pending counts:', error);
//     }
// };

// onMounted(() => {
//     getPendingCounts();
// })

// watchEffect(() => {
//     if (usePage().props.toast !== null) {
//         getPendingCounts();
//     }
// });
</script>

<template>
    <PerfectScrollbar
        tagname="nav"
        aria-label="main"
        class="relative flex flex-col flex-1 max-w-full max-h-full gap-1 items-center"
    >
        <!-- Dashboard -->
        <SidebarLink
            :title="$t('public.dashboard')"
            :href="route('dashboard')"
            :active="route().current('dashboard')"
        >
            <template #icon>
                <IconSmartHome :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Pending -->
        <!-- <SidebarLink
            :title="$t('public.pending')"
            :href="route('pending')"
            :active="route().current('pending')"
            :pendingCounts="pendingWithdrawals"
        >
            <template #icon>
                <IconClockDollar :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

        <!-- Member -->
        <!-- <SidebarCollapsible
            :title="$t('public.member')"
            :active="route().current('member.*')"
        >
            <template #icon>
                <IconComponents :size="20" stroke-width="1.25" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.member_listing')"
                :href="route('member.listing')"
                :active="route().current('member.listing') || route().current('member.detail')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.member_network')"
                :href="route('member.network')"
                :active="route().current('member.network')"
            />

        </SidebarCollapsible> -->

        <!-- Group -->
        <!-- <SidebarLink
            :title="$t('public.group')"
            :href="route('group')"
            :active="route().current('group')"
        >
            <template #icon>
                <IconUsersGroup :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

        <!-- Pamm Allocate -->
        <!-- <SidebarLink
            :title="$t('public.pamm_allocate')"
            :href="route('pamm_allocate')"
            :active="route().current('pamm_allocate')"
            :pendingCounts="pendingPammAllocate"
        >
            <template #icon>
                <IconCoinMonero :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

        <!-- Rebate Allocate -->
        <!-- <SidebarLink
            :title="$t('public.rebate_allocate')"
            :href="route('rebate_allocate')"
            :active="route().current('rebate_allocate')"
        >
            <template #icon>
                <IconBusinessplan :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

        <!-- Billboard -->
        <!-- <SidebarLink
            :title="$t('public.billboard')"
            :href="route('billboard')"
            :active="route().current('billboard')"
            :pendingCounts="pendingBonusWithdrawal"
        >
            <template #icon>
                <IconAward :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

        <!-- Transaction -->
        <!-- <SidebarLink
            :title="$t('public.transaction')"
            :href="route('transaction')"
            :active="route().current('transaction')"
        >
            <template #icon>
                <IconReceiptDollar :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

        <!-- Account Type -->
        <!-- <SidebarLink
            :title="$t('public.account_type')"
            :href="route('accountType')"
            :active="route().current('accountType')"
        >
            <template #icon>
                <IconId :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

        <!-- Components -->
       <SidebarCollapsible
           title="Components"
           :active="route().current('components.*')"
       >
           <template #icon>
               <IconComponents :size="20" stroke-width="1.25" />
           </template>

           <SidebarCollapsibleItem
               title="Buttons"
               :href="route('components.buttons')"
               :active="route().current('components.buttons')"
           />
       </SidebarCollapsible>


        <!-- Profile -->
        <!-- <SidebarLink
            :title="$t('public.my_profile')"
            :href="route('profile')"
            :active="route().current('profile')"
        >
            <template #icon>
                <IconUserCircle :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

    </PerfectScrollbar>
</template>
