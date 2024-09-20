<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        return Inertia::render('Team/Team');
    }
}
