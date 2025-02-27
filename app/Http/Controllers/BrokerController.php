<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BrokerController extends Controller
{
    public function index()
    {
        return Inertia::render('BrokerPnL/BrokerPnL', [
            // 'accountTypes' => (new GeneralController())->getAccountTypes(true),
        ]);
    }
}

