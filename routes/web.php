<?php

use Inertia\Inertia;
use App\Models\AccountType;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\RebateController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\PendingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TradingAccountController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\BrokerController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\TicketController;

Route::get('locale/{locale}', function ($locale) {
    App::setLocale($locale);
    Session::put("locale", $locale);

    return redirect()->back();
});

Route::get('/', function () {
    return redirect(route('login'));
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:super-admin|admin'])->group(function () {
    Route::get('/getWalletData', [GeneralController::class, 'getWalletData'])->name('getWalletData');
    Route::get('/getTradePointData', [GeneralController::class, 'getTradePointData'])->name('getTradePointData');
    Route::get('/getLeverages', [GeneralController::class, 'getLeverages'])->name('getLeverages');
    Route::get('/getTradingAccountData', [GeneralController::class, 'getTradingAccountData'])->name('getTradingAccountData');
    Route::get('/updateAccountData', [GeneralController::class, 'updateAccountData'])->name('updateAccountData');
    Route::get('/getTransactionMonths', [GeneralController::class, 'getTransactionMonths'])->name('getTransactionMonths');
    Route::get('/getSettlementMonths', [GeneralController::class, 'getSettlementMonths'])->name('getSettlementMonths');
    Route::get('/getAccountTypes', [GeneralController::class, 'getAccountTypes'])->name('getAccountTypes');
    Route::get('/getAccountTypesWithSlugs', [GeneralController::class, 'getAccountTypesWithSlugs'])->name('getAccountTypesWithSlugs');
    Route::get('/getCountries', [GeneralController::class, 'getCountries'])->name('getCountries');
    Route::get('/getUplines', [GeneralController::class, 'getUplines'])->name('getUplines');
    Route::get('/getTeams', [GeneralController::class, 'getTeams'])->name('getTeams');
    Route::get('/getIncentiveMonths', [GeneralController::class, 'getIncentiveMonths'])->name('getIncentiveMonths');
    Route::get('/getTradeMonths', [GeneralController::class, 'getTradeMonths'])->name('getTradeMonths');
    Route::get('/getRebateMonths', [GeneralController::class, 'getRebateMonths'])->name('getRebateMonths');
    Route::get('/getKycMonths', [GeneralController::class, 'getKycMonths'])->name('getKycMonths');
    Route::get('/getVisibleToOptions', [GeneralController::class, 'getVisibleToOptions'])->name('getVisibleToOptions');

    /**
     * ==============================
     *          Dashboard
     * ==============================
     */
    Route::prefix('dashboard')->middleware('role_and_permission:admin,access_dashboard')->group(callback: function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/getDashboardData', [DashboardController::class, 'getDashboardData'])->name('dashboard.getDashboardData');
        Route::get('/getAccountData', [DashboardController::class, 'getAccountData'])->name('dashboard.getAccountData');
        Route::get('/getTradeLotVolume', [DashboardController::class, 'getTradeLotVolume'])->name('dashboard.getTradeLotVolume');
        Route::get('/getTeams', [DashboardController::class, 'getTeams'])->name('dashboard.getTeams');
        Route::get('/getTeamData', [DashboardController::class, 'getTeamData'])->name('dashboard.getTeamData');
        Route::get('/getPendingData', [DashboardController::class, 'getPendingData'])->name('dashboard.getPendingData');
        Route::get('/getPendingCounts', [DashboardController::class, 'getPendingCounts'])->name('dashboard.getPendingCounts');
        Route::get('/getTradeBrokerPnl', [DashboardController::class, 'getTradeBrokerPnl'])->name('dashboard.getTradeBrokerPnl');
    });


    /**
     * ==============================
     *           Pending
     * ==============================
     */
    Route::prefix('pending')->middleware('role_and_permission:admin,access_withdrawal_request,access_incentive_request')->group(callback: function () {
        Route::get('withdrawal', [PendingController::class, 'withdrawal'])->name('pending.withdrawal')->middleware('role_and_permission:admin,access_withdrawal_request');
        Route::get('bonus', [PendingController::class, 'bonus'])->name('pending.bonus')->middleware('role_and_permission:admin,access_bonus_request');
        Route::get('incentive', [PendingController::class, 'incentive'])->name('pending.incentive')->middleware('role_and_permission:admin,access_incentive_request');
        Route::get('rewards', [PendingController::class, 'rewards'])->name('pending.rewards')->middleware('role_and_permission:admin,access_rewards_request');
        Route::get('kyc', [PendingController::class, 'kyc'])->name('pending.kyc')->middleware('role_and_permission:admin,access_kyc_request');
        Route::get('/getPendingWithdrawalData', [PendingController::class, 'getPendingWithdrawalData'])->name('pending.getPendingWithdrawalData')->middleware('role_and_permission:admin,access_withdrawal_request');
        Route::get('/getPendingBonusData', [PendingController::class, 'getPendingBonusData'])->name('pending.getPendingBonusData')->middleware('role_and_permission:admin,access_bonus_request');
        Route::get('/getPendingIncentiveData', [PendingController::class, 'getPendingIncentiveData'])->name('pending.getPendingIncentiveData')->middleware('role_and_permission:admin,access_incentive_request');
        Route::get('/getPendingRewardsData', [PendingController::class, 'getPendingRewardsData'])->name('pending.getPendingRewardsData')->middleware('role_and_permission:admin,access_rewards_request');
        Route::get('/getPendingKycData', [PendingController::class, 'getPendingKycData'])->name('pending.getPendingKycData')->middleware('role_and_permission:admin,access_kyc_request');

        Route::post('withdrawalApproval', [PendingController::class, 'withdrawalApproval'])->name('pending.withdrawalApproval');
        Route::post('kycApproval', [PendingController::class, 'kycApproval'])->name('pending.kycApproval');
    });

    /**
     * ==============================
     *            Tickets
     * ==============================
     */
    Route::prefix('tickets')->middleware('role_and_permission:admin,access_pending_tickets,access_ticket_history')->group(callback: function () {

        Route::get('pending', [TicketController::class, 'pending'])->name('tickets.pending')->middleware('role_and_permission:admin,access_pending_tickets');
        Route::get('history', [TicketController::class, 'history'])->name('tickets.history')->middleware('role_and_permission:admin,access_ticket_history');
        Route::get('/getPendingTickets', [TicketController::class, 'getPendingTickets'])->name('tickets.getPendingTickets');
        Route::get('/getTicketHistory', [TicketController::class, 'getTicketHistory'])->name('tickets.getTicketHistory');

        Route::get('/getTicketReplies', [TicketController::class, 'getTicketReplies'])->name('tickets.getTicketReplies');
        Route::post('/sendReply', [TicketController::class, 'sendReply'])->name('tickets.sendReply');
        Route::post('/resolveTicket', [TicketController::class, 'resolveTicket'])->name('tickets.resolveTicket');
    });

    Route::prefix('member')->middleware('role_and_permission:admin,access_member_listing,access_member_network,access_member_forum,access_account_listing')->group(function () {

        // Middleware for member listing actions
        Route::middleware('role_and_permission:admin,access_member_listing')->group(function () {
            // Listing Routes
            Route::get('/listing', [MemberController::class, 'listing'])->name('member.listing');
            Route::get('/getMemberListingData', [MemberController::class, 'getMemberListingData'])->name('member.getMemberListingData');


            Route::get('/getMemberListingPaginate', [MemberController::class, 'getMemberListingPaginate'])->name('member.getMemberListingPaginate');


            Route::get('/getAvailableUplines', [MemberController::class, 'getAvailableUplines'])->name('member.getAvailableUplines');
            Route::get('/getFilterData', [MemberController::class, 'getFilterData'])->name('member.getFilterData');
            Route::get('/getAvailableUplineData', [MemberController::class, 'getAvailableUplineData'])->name('member.getAvailableUplineData');
            Route::get('/access_portal/{user}', [MemberController::class, 'access_portal'])->name('member.access_portal');

            Route::post('/addNewMember', [MemberController::class, 'addNewMember'])->name('member.addNewMember');
            Route::post('/updateMemberStatus', [MemberController::class, 'updateMemberStatus'])->name('member.updateMemberStatus');
            Route::post('/transferUpline', [MemberController::class, 'transferUpline'])->name('member.transferUpline');
            Route::post('/upgradeAgent', [MemberController::class, 'upgradeAgent'])->name('member.upgradeAgent');
            Route::post('/uploadKyc', [MemberController::class, 'uploadKyc'])->name('member.uploadKyc');
            Route::post('/resetPassword', [MemberController::class, 'resetPassword'])->name('member.resetPassword');
            Route::post('/walletAdjustment', [MemberController::class, 'walletAdjustment'])->name('member.walletAdjustment');
            Route::post('/pointAdjustment', [MemberController::class, 'pointAdjustment'])->name('member.pointAdjustment');
            Route::post('/updateKyc', [MemberController::class, 'updateKyc'])->name('member.updateKyc');
            Route::post('/verifyEmail', [MemberController::class, 'verifyEmail'])->name('member.verifyEmail');
            Route::delete('/deleteMember', [MemberController::class, 'deleteMember'])->name('member.deleteMember');

            // Details Routes
            Route::get('/detail/{id_number}', [MemberController::class, 'detail'])->name('member.detail');
            Route::get('/getUserData', [MemberController::class, 'getUserData'])->name('member.getUserData');
            Route::get('/getFinancialInfoData', [MemberController::class, 'getFinancialInfoData'])->name('member.getFinancialInfoData');
            Route::get('/getTradingAccounts', [MemberController::class, 'getTradingAccounts'])->name('member.getTradingAccounts');
            Route::get('/getAdjustmentHistoryData', [MemberController::class, 'getAdjustmentHistoryData'])->name('member.getAdjustmentHistoryData');

            Route::post('/updateMemberInfo', [MemberController::class, 'updateMemberInfo'])->name('member.updateMemberInfo');
            Route::post('/updateCryptoWalletInfo', [MemberController::class, 'updateCryptoWalletInfo'])->name('member.updateCryptoWalletInfo');
            Route::post('/updateKYCStatus', [MemberController::class, 'updateKYCStatus'])->name('member.updateKYCStatus');
        });

        // Network Routes
        Route::middleware('role_and_permission:admin,access_member_network')->group(function () {
            Route::get('/network', [NetworkController::class, 'network'])->name('member.network');
            Route::get('/getDownlineData', [NetworkController::class, 'getDownlineData'])->name('member.getDownlineData');
        });

        // KYC Routes
        Route::middleware('role_and_permission:admin,access_kyc_listing')->group(function () {
            Route::get('/kyc_listing', [KycController::class, 'kyc_listing'])->name('member.kyc_listing');
            Route::get('/getApprovedListing', [KycController::class, 'getApprovedListing'])->name('member.getApprovedListing');
        });

        // Account Listing Routes
        Route::middleware('role_and_permission:admin,access_account_listing')->group(function () {
            Route::get('/account_listing', [TradingAccountController::class, 'index'])->name('member.account_listing');
            Route::get('/getAccountListingData', [TradingAccountController::class, 'getAccountListingData'])->name('member.getAccountListingData');
            Route::get('/getTradingAccountData', [TradingAccountController::class, 'getTradingAccountData'])->name('member.getTradingAccountData');

            Route::get('/getAccountListingPaginate', [TradingAccountController::class, 'getAccountListingPaginate'])->name('member.getAccountListingPaginate');
            Route::get('/getAccountReport', [TradingAccountController::class, 'getAccountReport'])->name('member.getAccountReport');

            Route::post('/accountAdjustment', [TradingAccountController::class, 'accountAdjustment'])->name('member.accountAdjustment');
            Route::post('/updateAccountStatus', [TradingAccountController::class, 'updateAccountStatus'])->name('member.updateAccountStatus');
            Route::post('/refreshAllAccount', [TradingAccountController::class, 'refreshAllAccount'])->name('member.refreshAllAccount');
            Route::post('/changeAccountGroup', [TradingAccountController::class, 'changeAccountGroup'])->name('member.changeAccountGroup');
            Route::delete('/accountDelete', [TradingAccountController::class, 'accountDelete'])->name('member.accountDelete');
        });
    });

    /**
     * ==============================
     *          Team
     * ==============================
     */
    Route::prefix('team')->middleware('role_and_permission:admin,access_sales_team')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('team');
        Route::get('/getTeams', [TeamController::class, 'getTeams'])->name('team.getTeams');
        Route::get('/getAgents', [TeamController::class, 'getAgents'])->name('team.getAgents');
        Route::get('/refreshTeam', [TeamController::class, 'refreshTeam'])->name('team.refreshTeam');
        Route::get('/getTeamTransaction', [TeamController::class, 'getTeamTransaction'])->name('team.getTeamTransaction');
        Route::get('/getSettlementReport', [TeamController::class, 'getSettlementReport'])->name('team.getSettlementReport');
        Route::get('/getTeamSettlementMonth', [TeamController::class, 'getTeamSettlementMonth'])->name('team.getTeamSettlementMonth');

        Route::post('/createTeam', [TeamController::class, 'createTeam'])->name('team.createTeam');
        Route::post('/editTeam', [TeamController::class, 'editTeam'])->name('team.editTeam');
        Route::post('/markSettlementReport', [TeamController::class, 'markSettlementReport'])->name('team.markSettlementReport');
        Route::delete('/deleteTeam', [TeamController::class, 'deleteTeam'])->name('team.deleteTeam');
    });

    /**
     * ==============================
     *          Highlights
     * ==============================
     */
    Route::prefix('highlights')->middleware('role_and_permission:admin,access_highlights_announcement,access_member_forum')->group(function () {
        // Announcement Routes
        Route::middleware('role_and_permission:admin,access_highlights_announcement')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('highlights');
            Route::get('/getAnnouncement', [AnnouncementController::class, 'getAnnouncement'])->name('highlights.getAnnouncement');

            Route::post('/createAnnouncement', [AnnouncementController::class, 'createAnnouncement'])->name('highlights.createAnnouncement');
            Route::post('/updateAnnouncementStatus', [AnnouncementController::class, 'updateAnnouncementStatus'])->name('highlights.updateAnnouncementStatus');
            Route::post('/editAnnouncement', [AnnouncementController::class, 'editAnnouncement'])->name('highlights.editAnnouncement');
            Route::delete('/deleteAnnouncement', [AnnouncementController::class, 'deleteAnnouncement'])->name('highlights.deleteAnnouncement');
            Route::get('/getVisibleToOptions', [AnnouncementController::class, 'getVisibleToOptions'])->name('highlights.getVisibleToOptions');
            Route::post('/announcements/{announcement}/pin', [AnnouncementController::class, 'togglePinStatus']);
        });

        // Forum Routes
        Route::middleware('role_and_permission:admin,access_member_forum')->group(function () {
            // Route::get('/forum', [ForumController::class, 'index'])->name('highlights.forum');
            Route::get('/getPosts', [ForumController::class, 'getPosts'])->name('highlights.getPosts');
            Route::get('/getAgents', [ForumController::class, 'getAgents'])->name('highlights.getAgents');

            Route::post('/createPost', [ForumController::class, 'createPost'])->name('highlights.createPost');
            Route::post('/updatePostPermission', [ForumController::class, 'updatePostPermission'])->name('highlights.updatePostPermission');
            Route::post('/updateLikeCounts', [ForumController::class, 'updateLikeCounts'])->name('highlights.updateLikeCounts');
            Route::delete('/deletePost', [ForumController::class, 'deletePost'])->name('highlights.deletePost');
            Route::post('/editPost', [ForumController::class, 'editPost'])->name('highlights.editPost');
        });
    });

    /**
     * ==============================
     *        Reward Setting
     * ==============================
     */
    Route::prefix('reward')->middleware('role_and_permission:admin,access_reward_setting')->group(function () {
        Route::get('/', [RewardController::class, 'index'])->name('reward_setting');
        Route::post('/createReward', [RewardController::class, 'createReward'])->name('reward.createReward');
        Route::get('/getRewardData', [RewardController::class, 'getRewardData'])->name('reward.getRewardData');
        Route::post('/editReward', [RewardController::class, 'editReward'])->name('reward.editReward');
        Route::delete('/deleteReward', [RewardController::class, 'deleteReward'])->name('reward.deleteReward');
        Route::post('/updateRewardStatus', [RewardController::class, 'updateRewardStatus'])->name('reward.updateRewardStatus');
    });

    /**
     * ==============================
     *        Rebate Setting
     * ==============================
     */
    Route::prefix('rebate')->middleware('role_and_permission:admin,access_rebate_setting')->group(function () {
        Route::get('/', [RebateController::class, 'index'])->name('rebate_setting');
        Route::get('/getCompanyProfileData', [RebateController::class, 'getCompanyProfileData'])->name('rebate.getCompanyProfileData');
        Route::get('/getRebateStructureData', [RebateController::class, 'getRebateStructureData'])->name('rebate.getRebateStructureData');
        Route::get('/getAgents', [RebateController::class, 'getAgents'])->name('rebate.getAgents');
        Route::get('/changeAgents', [RebateController::class, 'changeAgents'])->name('rebate.changeAgents');

        Route::post('/updateRebateAllocation', [RebateController::class, 'updateRebateAllocation'])->name('rebate.updateRebateAllocation');
        Route::post('/updateRebateAmount', [RebateController::class, 'updateRebateAmount'])->name('rebate.updateRebateAmount');
    });

    /**
     * ==============================
     *          Leaderboard
     * ==============================
     */
    Route::prefix('leaderboard')->middleware('role_and_permission:admin,access_leaderboard')->group(function () {
        Route::get('/', [LeaderboardController::class, 'index'])->name('leaderboard');
        Route::get('/getIncentiveProfiles', [LeaderboardController::class, 'getIncentiveProfiles'])->name('leaderboard.getIncentiveProfiles');
        Route::get('/getAgents', [LeaderboardController::class, 'getAgents'])->name('leaderboard.getAgents');
        Route::get('/getStatementData', [LeaderboardController::class, 'getStatementData'])->name('leaderboard.getStatementData');

        Route::post('/createIncentiveProfile', [LeaderboardController::class, 'createIncentiveProfile'])->name('leaderboard.createIncentiveProfile');
        Route::post('/editIncentiveProfile', [LeaderboardController::class, 'editIncentiveProfile'])->name('leaderboard.editIncentiveProfile');
        Route::delete('/deleteIncentiveProfile', [LeaderboardController::class, 'deleteIncentiveProfile'])->name('leaderboard.deleteIncentiveProfile');


    });

    /**
     * ==============================
     *          Transaction
     * ==============================
     */
    Route::prefix('transaction')->middleware('role_and_permission:admin,access_deposit,access_withdrawal,access_transfer,access_rebate_payout,access_incentive_payout')->group(function () {
        Route::get('deposit', [TransactionController::class, 'deposit'])->name('transaction.deposit')->middleware('role_and_permission:admin,access_deposit');
        Route::get('withdrawal', [TransactionController::class, 'withdrawal'])->name('transaction.withdrawal')->middleware('role_and_permission:admin,access_withdrawal');
        Route::get('transfer', [TransactionController::class, 'transfer'])->name('transaction.transfer')->middleware('role_and_permission:admin,access_transfer');
        Route::get('bonus', [TransactionController::class, 'bonus'])->name('transaction.bonus')->middleware('role_and_permission:admin,access_bonus');
        Route::get('rewards', [TransactionController::class, 'rewards'])->name('transaction.rewards')->middleware('role_and_permission:admin,access_rewards');
        Route::get('rebate', [TransactionController::class, 'rebate'])->name('transaction.rebate')->middleware('role_and_permission:admin,access_rebate_payout');
        Route::get('incentive', [TransactionController::class, 'incentive'])->name('transaction.incentive')->middleware('role_and_permission:admin,access_incentive_payout');
        Route::get('adjustment', [TransactionController::class, 'adjustment'])->name('transaction.adjustment');

        Route::get('/getTransactionData', [TransactionController::class, 'getTransactionData'])->name('transaction.getTransactionData')->middleware('role_and_permission:admin,access_deposit,access_withdrawal,access_transfer');
        Route::get('/getRewardsData', [TransactionController::class, 'getRewardsData'])->name('transaction.getRewardsData')->middleware('role_and_permission:admin,access_rewards');
        Route::get('/getRebatePayoutData', [TransactionController::class, 'getRebatePayoutData'])->name('transaction.getRebatePayoutData')->middleware('role_and_permission:admin,access_rebate_payout');
        Route::get('/getIncentivePayoutData', [TransactionController::class, 'getIncentivePayoutData'])->name('transaction.getIncentivePayoutData')->middleware('role_and_permission:admin,access_incentive_payout');
        Route::get('/getAdjustmentHistoryData', [TransactionController::class, 'getAdjustmentHistoryData'])->name('transaction.getAdjustmentHistoryData');
    });

    /**
     * ==============================
     *        Broker P&L
     * ==============================
     */
    Route::prefix('broker')->middleware('role_and_permission:admin,access_broker_pnl')->group(function () {
        Route::get('/', [BrokerController::class, 'index'])->name('broker_pnl');

    });

    /**
     * ==============================
     *        Account Type
     * ==============================
     */
    Route::prefix('accountType')->middleware('role_and_permission:admin,access_account_type')->group(function () {
        Route::get('/', [AccountTypeController::class, 'index'])->name('accountType');
        Route::get('/accountTypeConfiguration', [AccountTypeController::class, 'accountTypeConfiguration'])->name('accountType.accountTypeConfiguration');
        Route::get('/getAccountTypes', [AccountTypeController::class, 'getAccountTypes'])->name('accountType.getAccountTypes');
        Route::get('/getVisibleToOptions', [AccountTypeController::class, 'getVisibleToOptions'])->name('accountType.getVisibleToOptions');
        Route::get('/syncAccountTypes', [AccountTypeController::class, 'syncAccountTypes'])->name('accountType.syncAccountTypes');

        Route::post('/updateStatus', [AccountTypeController::class, 'updateStatus'])->name('accountType.updateStatus');
        Route::post('/updateAccountType', [AccountTypeController::class, 'updateAccountType'])->name('accountType.updateAccountType');
        Route::post('/updatePromotionConfiguration', [AccountTypeController::class, 'updatePromotionConfiguration'])->name('accountType.updatePromotionConfiguration');

    });

    /**
     * ==============================
     *          Admin Role
     * ==============================
     */
    Route::prefix('adminRole')->middleware('role_and_permission:admin,access_admin_role')->group(function () {
        Route::get('/', [AdminRoleController::class, 'index'])->name('adminRole');
        Route::get('/getAdminRole', [AdminRoleController::class, 'getAdminRole'])->name('adminRole.getAdminRole');

        Route::post('/firstStep', [AdminRoleController::class, 'firstStep'])->name('adminRole.firstStep');
        Route::post('/addNewAdmin', [AdminRoleController::class, 'addNewAdmin'])->name('adminRole.addNewAdmin');
        Route::post('/updateAdminStatus', [AdminRoleController::class, 'updateAdminStatus'])->name('adminRole.updateAdminStatus');
        Route::post('/adminUpdatePermissions', [AdminRoleController::class, 'adminUpdatePermissions'])->name('adminRole.adminUpdatePermissions');
        Route::post('/editAdmin', [AdminRoleController::class, 'editAdmin'])->name('adminRole.editAdmin');
        Route::delete('/deleteAdmin', [AdminRoleController::class, 'deleteAdmin'])->name('adminRole.deleteAdmin');
    });

    /**
     * ==============================
     *          Configuration
     * ==============================
     */
    Route::prefix('configuration')->middleware('role_and_permission:admin,access_auto_deposit,access_trade_point_setting,access_ticket_setting')->group(function () {
        Route::get('auto_deposit', [ConfigController::class, 'auto_deposit'])->name('configuration.auto_deposit')->middleware('role_and_permission:admin,access_auto_deposit');
        Route::get('trade_point_setting', [ConfigController::class, 'trade_point_setting'])->name('configuration.trade_point_setting')->middleware('role_and_permission:admin,access_trade_point_setting');
        Route::get('ticket_setting', [ConfigController::class, 'ticket_setting'])->name('configuration.ticket_setting')->middleware('role_and_permission:admin,access_trade_point_setting');

        Route::get('/getAutoApprovalSettings', [ConfigController::class, 'getAutoApprovalSettings'])->name('configuration.getAutoApprovalSettings');
        Route::post('/updateAutoApprovalSettings', [ConfigController::class, 'updateAutoApprovalSettings'])->name('configuration.updateAutoApprovalSettings');
        Route::get('/getTicketScheduleSettings', [ConfigController::class, 'getTicketScheduleSettings'])->name('configuration.getTicketScheduleSettings');
        Route::post('/updateTicketScheduleSettings', [ConfigController::class, 'updateTicketScheduleSettings'])->name('configuration.updateTicketScheduleSettings');
        Route::get('/getTicketCategories', [ConfigController::class, 'getTicketCategories'])->name('configuration.getTicketCategories');
        Route::post('/updateTicketCategories', [ConfigController::class, 'updateTicketCategories'])->name('configuration.updateTicketCategories');
        Route::get('/getAgentAccesses', [ConfigController::class, 'getAgentAccesses'])->name('configuration.getAgentAccesses');
        Route::post('/updateAgentAccesses', [ConfigController::class, 'updateAgentAccesses'])->name('configuration.updateAgentAccesses');

        Route::get('/getVisibleToOptions', [ConfigController::class, 'getVisibleToOptions'])->name('configuration.getVisibleToOptions');
        Route::post('/clearPoints', [ConfigController::class, 'clearPoints'])->name('configuration.clearPoints');
        Route::get('/getTradePointData', [ConfigController::class, 'getTradePointData'])->name('configuration.getTradePointData');
        Route::post('/updateTradePointRate', [ConfigController::class, 'updateTradePointRate'])->name('configuration.updateTradePointRate');
        Route::post('/createTradePeriod', [ConfigController::class, 'createTradePeriod'])->name('configuration.createTradePeriod');
        Route::post('/updatePeriodStatus', [ConfigController::class, 'updatePeriodStatus'])->name('configuration.updatePeriodStatus');
        Route::post('/editTradePeriod', [ConfigController::class, 'editTradePeriod'])->name('configuration.editTradePeriod');
        Route::delete('/deleteTradePeriod', [ConfigController::class, 'deleteTradePeriod'])->name('configuration.deleteTradePeriod');
        Route::post('/updateCalculationStatus', [ConfigController::class, 'updateCalculationStatus'])->name('configuration.updateCalculationStatus');
    });

    /**
     * ==============================
     *           Profile
     * ==============================
     */
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('profile');

        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/updateProfilePhoto', [ProfileController::class, 'updateProfilePhoto'])->name('profile.updateProfilePhoto');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});



Route::get('/components/buttons', function () {
    return Inertia::render('Components/Buttons');
})->name('components.buttons');

Route::get('/test', function () {
    return Inertia::render('Welcome');
})->name('test');

Route::get('/error', function () {
    return Inertia::render('Errors/504');
})->name('error');

require __DIR__.'/auth.php';
