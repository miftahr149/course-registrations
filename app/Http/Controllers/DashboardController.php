<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The landing page after login: who you are and what your role can do.
     */
    public function __invoke(Request $request): View
    {
        return view('dashboard', ['user' => $request->user()]);
    }
}
