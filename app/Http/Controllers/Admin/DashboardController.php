<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;

final class DashboardController
{
    public function __invoke(): View
    {
        return view('admin.dashboard');
    }
}
