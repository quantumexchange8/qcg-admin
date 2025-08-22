<?php

use Illuminate\Foundation\Inspiring;
use App\Jobs\UpdateCTraderAccountJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Schedule::command('distribute:sales-incentive')->weekly();
Schedule::command('tradingAccount:check-status')->daily();
Schedule::command('accountType:updatePromotionStatus')->daily();
// Schedule::command('tradingUser:refresh_accounts')->timezone('Asia/Kuala_Lumpur')->at('8:00');
Schedule::command('update:deposit-status')->hourly();
Schedule::command('wallet:updateTradePoints')->timezone('Asia/Kuala_Lumpur')->at('9:00');
Schedule::command('check:daily-schedule')->daily();
Schedule::command('competition:updateRankings')->everyFourHours();

