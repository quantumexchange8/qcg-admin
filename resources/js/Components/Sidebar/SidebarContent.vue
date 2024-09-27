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
    <div
        tagname="nav"
        aria-label="main"
        class="relative w-full flex flex-col flex-1 max-w-full max-h-full gap-1 items-center "
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
        <SidebarCollapsible
            :title="$t('public.request')"
            :active="route().current('pending.*')"
        >
            <template #icon>
                <IconClockDollar :size="20" stroke-width="1.25" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.withdrawal')"
                :href="route('pending.withdrawal')"
                :active="route().current('pending.withdrawal')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.incentive')"
                :href="route('pending.incentive')"
                :active="route().current('pending.incentive')"
            />

        </SidebarCollapsible>

        <!-- Member -->
        <SidebarCollapsible
            :title="$t('public.sidebar_member')"
            :active="route().current('member.*')"
        >
            <template #icon>
                <IconUsers :size="20" stroke-width="1.25" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_listing')"
                :href="route('member.listing')"
                :active="route().current('member.listing') || route().current('member.detail')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_network')"
                :href="route('member.network')"
                :active="route().current('member.network')"
            />

            <!-- <SidebarCollapsibleItem
                :title="$t('public.sidebar_forum')"
                :href="route('member.forum')"
                :active="route().current('member.forum')"
            /> -->

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_account_listing')"
                :href="route('member.account_listing')"
                :active="route().current('member.account_listing')"
            />


        </SidebarCollapsible>

        <!-- Team -->
        <SidebarLink
            :title="$t('public.sales_team')"
            :href="route('team')"
            :active="route().current('team')"
        >
            <template #icon>
                <IconSitemap :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Rebate Setting -->
        <SidebarLink
            :title="$t('public.rebate_setting')"
            :href="route('rebate_setting')"
            :active="route().current('rebate_setting')"
        >
            <template #icon>
                <IconSettingsDollar :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Leaderboard -->
        <SidebarLink
            :title="$t('public.leaderboard')"
            :href="route('leaderboard')"
            :active="route().current('leaderboard')"
        >
            <template #icon>
                <IconTrophy :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>


        <!-- Transaction -->
        <SidebarCollapsible
            :title="$t('public.sidebar_transaction')"
            :active="route().current('transaction.index', { type: 'deposit' })"
        >
            <template #icon>
                <IconReportMoney :size="20" stroke-width="1.25" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_deposit')"
                :href="route('transaction.index', { type: 'deposit' })"
                :active="route().current('transaction.index', { type: 'deposit' })"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_withdrawal')"
                :href="route('transaction.index', { type: 'withdrawal' })"
                :active="route().current('transaction.index', { type: 'withdrawal' })"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_transfer')"
                :href="route('transaction.index', { type: 'transfer' })"
                :active="route().current('transaction.index', { type: 'transfer' })"
            />
            
            <SidebarCollapsibleItem
                :title="$t('public.sidebar_rebate_payout')"
                :href="route('transaction.index', { type: 'rebate' })"
                :active="route().current('transaction.index', { type: 'rebate' })"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_incentive_payout')"
                :href="route('transaction.index', { type: 'incentive' })"
                :active="route().current('transaction.index', { type: 'incentive' })"
            />
        </SidebarCollapsible>

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
       <!-- <SidebarCollapsible
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
       </SidebarCollapsible> -->


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

    </div>
</template>
