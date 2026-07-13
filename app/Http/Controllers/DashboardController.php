<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user()->load(['department', 'designation', 'parent']);

        return view('dashboard', compact('user'));
    }
}
