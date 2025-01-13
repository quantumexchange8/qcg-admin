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
Schedule::call(function () {
    dispatch(new UpdateCTraderAccountJob());
})->timezone('Asia/Kuala_Lumpur')->at('8:00');
