<?php

namespace App\Http\Controllers\Member;

use Illuminate\Contracts\View\View;

final class DashboardController
{
    public function __invoke(): View
    {
        return view('member.dashboard');
    }
}
