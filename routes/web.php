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
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TradingAccountController;

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
    Route::get('/getLeverages', [GeneralController::class, 'getLeverages'])->name('getLeverages');
    Route::get('/getTradingAccountData', [GeneralController::class, 'getTradingAccountData'])->name('getTradingAccountData');
    Route::get('/getAgents', [GeneralController::class, 'getAgents'])->name('getAgents');    
    Route::get('/getTransactionMonths', [GeneralController::class, 'getTransactionMonths'])->name('getTransactionMonths');    
    Route::get('/getSettlementMonths', [GeneralController::class, 'getSettlementMonths'])->name('getSettlementMonths');    
    Route::get('/getAccountTypes', [GeneralController::class, 'getAccountTypes'])->name('getAccountTypes');    
    Route::get('/getAccountTypesWithSlugs', [GeneralController::class, 'getAccountTypesWithSlugs'])->name('getAccountTypesWithSlugs');    
    Route::get('/getCountries', [GeneralController::class, 'getCountries'])->name('getCountries');    
    Route::get('/getUplines', [GeneralController::class, 'getUplines'])->name('getUplines');    
    Route::get('/getTeams', [GeneralController::class, 'getTeams'])->name('getTeams');    
    Route::get('/getIncentiveMonths', [GeneralController::class, 'getIncentiveMonths'])->name('getIncentiveMonths');    

    /**
     * ==============================
     *          Dashboard
     * ==============================
     */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/getDashboardData', [DashboardController::class, 'getDashboardData'])->name('dashboard.getDashboardData');
    Route::get('/getAccountData', [DashboardController::class, 'getAccountData'])->name('dashboard.getAccountData');
    Route::get('/getPendingData', [DashboardController::class, 'getPendingData'])->name('dashboard.getPendingData');    
    Route::get('/getPendingCounts', [DashboardController::class, 'getPendingCounts'])->name('dashboard.getPendingCounts');    

    /**
     * ==============================
     *           Pending
     * ==============================
     */
    Route::prefix('pending')->group(function () {
        Route::get('/{type}', [PendingController::class, 'index'])->where('type', 'withdrawal|incentive')->name('pending.index');
        Route::get('/getPendingWithdrawalData', [PendingController::class, 'getPendingWithdrawalData'])->name('pending.getPendingWithdrawalData');
        Route::get('/getPendingIncentiveData', [PendingController::class, 'getPendingIncentiveData'])->name('pending.getPendingIncentiveData');

        Route::post('withdrawalApproval', [PendingController::class, 'withdrawalApproval'])->name('pending.withdrawalApproval');
        Route::post('revokeApproval', [PendingController::class, 'revokeApproval'])->name('pending.revokeApproval');
    });

    Route::prefix('member')->group(function () {
        // listing
        Route::get('/listing', [MemberController::class, 'listing'])->name('member.listing');
        Route::get('/getMemberListingData', [MemberController::class, 'getMemberListingData'])->name('member.getMemberListingData');
        Route::get('/getFilterData', [MemberController::class, 'getFilterData'])->name('member.getFilterData');
        Route::get('/getAvailableUplineData', [MemberController::class, 'getAvailableUplineData'])->name('member.getAvailableUplineData');
        Route::get('/access_portal/{user}', [MemberController::class, 'access_portal'])->name('member.access_portal');

        Route::post('/addNewMember', [MemberController::class, 'addNewMember'])->name('member.addNewMember');
        Route::post('/updateMemberStatus', [MemberController::class, 'updateMemberStatus'])->name('member.updateMemberStatus');
        Route::post('/upgradeAgent', [MemberController::class, 'upgradeAgent'])->name('member.upgradeAgent');
        Route::post('/uploadKyc', [MemberController::class, 'uploadKyc'])->name('member.uploadKyc');
        Route::post('/resetPassword', [MemberController::class, 'resetPassword'])->name('member.resetPassword');
        Route::post('/walletAdjustment', [MemberController::class, 'walletAdjustment'])->name('member.walletAdjustment');

        Route::delete('/deleteMember', [MemberController::class, 'deleteMember'])->name('member.deleteMember');

        // details
        Route::get('/detail/{id_number}', [MemberController::class, 'detail'])->name('member.detail');
        Route::get('/getUserData', [MemberController::class, 'getUserData'])->name('member.getUserData');
        Route::get('/getFinancialInfoData', [MemberController::class, 'getFinancialInfoData'])->name('member.getFinancialInfoData');
        Route::get('/getTradingAccounts', [MemberController::class, 'getTradingAccounts'])->name('member.getTradingAccounts');
        Route::get('/getAdjustmentHistoryData', [MemberController::class, 'getAdjustmentHistoryData'])->name('member.getAdjustmentHistoryData');

        Route::post('/updateMemberInfo', [MemberController::class, 'updateMemberInfo'])->name('member.updateMemberInfo');
        Route::post('/updateCryptoWalletInfo', [MemberController::class, 'updateCryptoWalletInfo'])->name('member.updateCryptoWalletInfo');
        Route::post('/updateKYCStatus', [MemberController::class, 'updateKYCStatus'])->name('member.updateKYCStatus');

        // network
        Route::get('/network', [NetworkController::class, 'network'])->name('member.network');
        Route::get('/getDownlineData', [NetworkController::class, 'getDownlineData'])->name('member.getDownlineData');

        // forum
        Route::get('/forum', [ForumController::class, 'index'])->name('member.forum');
        Route::get('/getPosts', [ForumController::class, 'getPosts'])->name('member.getPosts');

        Route::post('/createPost', [ForumController::class, 'createPost'])->name('member.createPost');

        // account listing
        Route::get('/account_listing', [TradingAccountController::class, 'index'])->name('member.account_listing');
        Route::get('/getAccountListingData', [TradingAccountController::class, 'getAccountListingData'])->name('member.getAccountListingData');
        Route::get('/getTradingAccountData', [TradingAccountController::class, 'getTradingAccountData'])->name('member.getTradingAccountData');

        Route::post('/accountAdjustment', [TradingAccountController::class, 'accountAdjustment'])->name('member.accountAdjustment');
        Route::post('/refreshAllAccount', [TradingAccountController::class, 'refreshAllAccount'])->name('member.refreshAllAccount');
        Route::delete('/accountDelete', [TradingAccountController::class, 'accountDelete'])->name('member.accountDelete');
    });

    /**
     * ==============================
     *          Team
     * ==============================
     */
    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('team');
        Route::get('/getTeams', [TeamController::class, 'getTeams'])->name('team.getTeams');
        Route::get('/refreshTeam', [TeamController::class, 'refreshTeam'])->name('team.refreshTeam');
        Route::get('/getTeamTransaction', [TeamController::class, 'getTeamTransaction'])->name('team.getTeamTransaction');
        Route::get('/getSettlementReport', [TeamController::class, 'getSettlementReport'])->name('team.getSettlementReport');

        Route::post('/createTeam', [TeamController::class, 'createTeam'])->name('team.createTeam');
        Route::post('/editTeam', [TeamController::class, 'editTeam'])->name('team.editTeam');
        Route::post('/markSettlementReport', [TeamController::class, 'markSettlementReport'])->name('team.markSettlementReport');
        Route::delete('/deleteTeam', [TeamController::class, 'deleteTeam'])->name('team.deleteTeam');
    });

    /**
     * ==============================
     *        Rebate Setting
     * ==============================
     */
    Route::prefix('rebate')->group(function () {
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
    Route::prefix('leaderboard')->group(function () {
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
    Route::prefix('transaction')->group(function () {
        Route::get('transaction/{type}', [TransactionController::class, 'index'])->name('transaction.index');
        Route::get('/getTransactionListingData', [TransactionController::class, 'getTransactionListingData'])->name('transaction.getTransactionListingData');
        Route::get('/getTransactionData', [TransactionController::class, 'getTransactionData'])->name('transaction.getTransactionData');
        Route::get('/getRebatePayoutData', [TransactionController::class, 'getRebatePayoutData'])->name('transaction.getRebatePayoutData');
        Route::get('/getIncentivePayoutData', [TransactionController::class, 'getIncentivePayoutData'])->name('transaction.getIncentivePayoutData');
    });

    /**
     * ==============================
     *        Account Type
     * ==============================
     */
    Route::prefix('accountType')->group(function () {
        Route::get('/', [AccountTypeController::class, 'index'])->name('accountType');
        Route::get('/getAccountTypes', [AccountTypeController::class, 'getAccountTypes'])->name('accountType.getAccountTypes');
        Route::get('/syncAccountTypes', [AccountTypeController::class, 'syncAccountTypes'])->name('accountType.syncAccountTypes');

        Route::post('/updateStatus', [AccountTypeController::class, 'updateStatus'])->name('accountType.updateStatus');
        Route::post('/updateAccountType', [AccountTypeController::class, 'updateAccountType'])->name('accountType.updateAccountType');

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
