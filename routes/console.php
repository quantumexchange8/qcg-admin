<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Schedule::command('distribute:sales-incentive')->weekly();
Schedule::command('tradingAccount:check-status')->daily();
Schedule::command('check:deposit-account')->everyMinute();
