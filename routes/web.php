<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\PendingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
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
    Route::get('/getTradingAccountData', [GeneralController::class, 'getTradingAccountData'])->name('getTradingAccountData');


    /**
     * ==============================
     *          Dashboard
     * ==============================
     */
    Route::prefix('dashboard')->group(function() {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/getDashboardData', [DashboardController::class, 'getDashboardData'])->name('getDashboardData');
        Route::get('/getAccountData', [DashboardController::class, 'getAccountData'])->name('dashboard.getAccountData');
        Route::get('/getPendingData', [DashboardController::class, 'getPendingData'])->name('dashboard.getPendingData');    
    });

    /**
     * ==============================
     *           Pending
     * ==============================
     */
    Route::prefix('pending')->group(function () {
        Route::get('/withdrawal', [PendingController::class, 'withdrawal'])->name('pending.withdrawal');
        Route::get('/incentive', [PendingController::class, 'incentive'])->name('pending.incentive');
        Route::get('/getPendingWithdrawalData', [PendingController::class, 'getPendingWithdrawalData'])->name('pending.getPendingWithdrawalData');

    //     Route::get('/', [PendingController::class, 'index'])->name('pending');
    //     Route::get('/getPendingWithdrawalData', [PendingController::class, 'getPendingWithdrawalData'])->name('pending.getPendingWithdrawalData');
    //     Route::get('/getPendingRevokeData', [PendingController::class, 'getPendingRevokeData'])->name('pending.getPendingRevokeData');

    //     Route::post('withdrawalApproval', [PendingController::class, 'withdrawalApproval'])->name('pending.withdrawalApproval');
    //     Route::post('revokeApproval', [PendingController::class, 'revokeApproval'])->name('pending.revokeApproval');
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

        Route::post('/updateContactInfo', [MemberController::class, 'updateContactInfo'])->name('member.updateContactInfo');
        Route::post('/updateCryptoWalletInfo', [MemberController::class, 'updateCryptoWalletInfo'])->name('member.updateCryptoWalletInfo');
        Route::post('/updateKYCStatus', [MemberController::class, 'updateKYCStatus'])->name('member.updateKYCStatus');

        // network
        Route::get('/network', [NetworkController::class, 'network'])->name('member.network');
        Route::get('/getDownlineData', [NetworkController::class, 'getDownlineData'])->name('member.getDownlineData');

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
        Route::get('/team', [TeamController::class, 'index'])->name('team');
    });


    /**
     * ==============================
     *          Transaction
     * ==============================
     */
    Route::prefix('transaction')->group(function () {
        Route::get('transaction/{type}', [TransactionController::class, 'index'])->name('transaction.index');
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

Route::get('/test/component', function () {
    return Inertia::render('Welcome');
})->name('test.component');

require __DIR__.'/auth.php';
