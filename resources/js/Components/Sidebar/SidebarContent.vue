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
    IconShieldCheckered,
    IconCategory2,
    IconComponents,
} from '@tabler/icons-vue';
import { usePermission } from "@/Composables/index.js";

const { hasRole, hasPermission } = usePermission();

const pendingWithdrawals = ref(0);
const pendingBonus = ref(0);
const pendingIncentive = ref(0);

const getPendingCounts = async () => {
    try {
        const response = await axios.get(route('dashboard.getPendingCounts'));
        pendingWithdrawals.value = response.data.pendingWithdrawals
        pendingBonus.value = response.data.pendingBonus
        pendingIncentive.value = response.data.pendingIncentive
    } catch (error) {
        console.error('Error pending counts:', error);
    }
};

onMounted(() => {
    getPendingCounts();
})

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getPendingCounts();
    }
});
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
            v-if="hasRole('super-admin') || hasPermission('access_dashboard')"
        >
            <template #icon>
                <IconSmartHome :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Pending -->
        <SidebarCollapsible
            :title="$t('public.request')"
            :active="route().current('pending.*')"
            :pendingCounts="pendingWithdrawals + pendingIncentive"
            v-if="hasRole('super-admin') || hasPermission([
                'access_withdrawal_request',
                'access_incentive_request',
            ])"
        >
            <template #icon>
                <IconClockDollar :size="20" stroke-width="1.25" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.withdrawal')"
                :href="route('pending.withdrawal')"
                :active="route().current('pending.withdrawal')"
                :pendingCounts="pendingWithdrawals"
                v-if="hasRole('super-admin') || hasPermission('access_withdrawal_request')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.bonus')"
                :href="route('pending.bonus')"
                :active="route().current('pending.bonus')"
                :pendingCounts="pendingBonus"
                v-if="hasRole('super-admin') || hasPermission('access_bonus_request')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.incentive')"
                :href="route('pending.incentive')"
                :active="route().current('pending.incentive')"
                :pendingCounts="pendingIncentive"
                v-if="hasRole('super-admin') || hasPermission('access_incentive_request')"
            />

        </SidebarCollapsible>

        <!-- Member -->
        <SidebarCollapsible
            :title="$t('public.sidebar_member')"
            :active="route().current('member.*')"
            v-if="hasRole('super-admin') || hasPermission([
                'access_member_listing',
                'access_member_network',
                'access_member_forum',
                'access_account_listing',
            ])"
        >
            <template #icon>
                <IconUsers :size="20" stroke-width="1.25" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_listing')"
                :href="route('member.listing')"
                :active="route().current('member.listing') || route().current('member.detail')"
                v-if="hasRole('super-admin') || hasPermission('access_member_listing')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_network')"
                :href="route('member.network')"
                :active="route().current('member.network')"
                v-if="hasRole('super-admin') || hasPermission('access_member_network')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_forum')"
                :href="route('member.forum')"
                :active="route().current('member.forum')"
                v-if="hasRole('super-admin') || hasPermission('access_member_forum')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_account_listing')"
                :href="route('member.account_listing')"
                :active="route().current('member.account_listing')"
                v-if="hasRole('super-admin') || hasPermission('access_account_listing')"
            />


        </SidebarCollapsible>

        <!-- Team -->
        <SidebarLink
            :title="$t('public.sales_team')"
            :href="route('team')"
            :active="route().current('team')"
            v-if="hasRole('super-admin') || hasPermission('access_sales_team')"
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
            v-if="hasRole('super-admin') || hasPermission('access_rebate_setting')"
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
            v-if="hasRole('super-admin') || hasPermission('access_leaderboard')"
        >
            <template #icon>
                <IconTrophy :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>


        <!-- Transaction -->
        <SidebarCollapsible
            :title="$t('public.sidebar_transaction')"
            :active="route().current('transaction.*')"
            v-if="hasRole('super-admin') || hasPermission([
                'access_deposit',
                'access_withdrawal',
                'access_transfer',
                'access_rebate_payout',
                'access_incentive_payout',
            ])"
        >
            <template #icon>
                <IconReportMoney :size="20" stroke-width="1.25" />
            </template>

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_deposit')"
                :href="route('transaction.deposit')"
                :active="route().current('transaction.deposit')"
                v-if="hasRole('super-admin') || hasPermission('access_deposit')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_withdrawal')"
                :href="route('transaction.withdrawal')"
                :active="route().current('transaction.withdrawal')"
                v-if="hasRole('super-admin') || hasPermission('access_withdrawal')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_transfer')"
                :href="route('transaction.transfer')"
                :active="route().current('transaction.transfer')"
                v-if="hasRole('super-admin') || hasPermission('access_transfer')"
            />
            
            <SidebarCollapsibleItem
                :title="$t('public.sidebar_rebate_payout')"
                :href="route('transaction.rebate')"
                :active="route().current('transaction.rebate')"
                v-if="hasRole('super-admin') || hasPermission('access_rebate_payout')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_incentive_payout')"
                :href="route('transaction.incentive')"
                :active="route().current('transaction.incentive')"
                v-if="hasRole('super-admin') || hasPermission('access_incentive_payout')"
            />

            <SidebarCollapsibleItem
                :title="$t('public.sidebar_adjustment')"
                :href="route('transaction.adjustment')"
                :active="route().current('transaction.adjustment')"
            />

        </SidebarCollapsible>

        <!-- Account Type -->
        <SidebarLink
            :title="$t('public.sidebar_account_type')"
            :href="route('accountType')"
            :active="route().current('accountType')"
            v-if="hasRole('super-admin') || hasPermission('access_account_type')"
        >
            <template #icon>
                <IconTool :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Admin Role -->
        <SidebarLink
            :title="$t('public.sidebar_admin_role')"
            :href="route('adminRole')"
            :active="route().current('adminRole')"
            v-if="hasRole('super-admin') || hasPermission('access_admin_role')"
        >
            <template #icon>
                <IconShieldCheckered :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

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

    </div>
</template>
