<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SpaceGateController extends Controller
{
    /**
     * Global entry when the user is not on a tenant-prefixed URL.
     */
    public function show(): View
    {
        return view('auth.space-gate');
    }
}
